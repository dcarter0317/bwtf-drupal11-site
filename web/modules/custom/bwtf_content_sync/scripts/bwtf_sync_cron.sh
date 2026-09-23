#!/usr/bin/env bash
#
# BWTF production (Drupal 8) -> staging (Drupal 11) content sync.
#
# One-way only. Production is the source of truth; nothing is ever written
# back to production and nothing is ever deleted on either side.
#
# Run it by hand first:
#   bash bwtf_sync_cron.sh --preflight-only
#
# Then from cron, e.g. nightly at 02:30 server time:
#   30 2 * * * /bin/bash /home/bwtfcom/bin/bwtf_sync_cron.sh >/dev/null 2>&1
#
# Override any setting with an environment variable of the same name.

set -uo pipefail

# ---------------------------------------------------------------- settings --

PROD_ROOT="${PROD_ROOT:-/home/bwtfcom/public_html}"
STAGE_ROOT="${STAGE_ROOT:-/home/bwtfcom/stage.bwtf.com}"
STAGE_DOCROOT="${STAGE_DOCROOT:-$STAGE_ROOT/web}"

# Where packages, backups and logs are written.
WORK_DIR="${WORK_DIR:-/home/bwtfcom/backups}"
PACKAGE_DIR="${PACKAGE_DIR:-$WORK_DIR/packages}"
LOG_DIR="${LOG_DIR:-$WORK_DIR/sync-logs}"
DB_BACKUP_DIR="${DB_BACKUP_DIR:-$WORK_DIR/stage-db}"

# Content selection.
SINCE="${SINCE:-2026-01-01}"
BUNDLES="${BUNDLES:-all}"

# Retention, in days / copies.
KEEP_PACKAGE_DAYS="${KEEP_PACKAGE_DAYS:-14}"
KEEP_LOG_DAYS="${KEEP_LOG_DAYS:-30}"
KEEP_DB_BACKUPS="${KEEP_DB_BACKUPS:-10}"

# Email a report. Leave empty to disable.
MAILTO="${MAILTO:-}"

# Sender address. Without this, mail goes out as <user>@<server hostname>,
# which no SPF or DKIM record covers, so Gmail and Yahoo greylist it and file
# it as spam. Use an address on a domain this server is authorised to send
# for. Leave empty to keep the default sender.
MAIL_FROM="${MAIL_FROM:-}"

# Set to 1 to stop before writing to staging.
PREFLIGHT_ONLY="${PREFLIGHT_ONLY:-0}"
# Production is the single source of truth until the cutover, so it wins even
# when a staging row is newer. Set to 0 to have the run stop for review
# instead, which is the safer setting once staging carries real edits.
OVERWRITE_NEWER="${OVERWRITE_NEWER:-1}"
# Set to 1 to skip the pre-apply staging database dump (not recommended).
SKIP_DB_BACKUP="${SKIP_DB_BACKUP:-0}"

MODULE_REL="modules/custom/bwtf_content_sync"

for arg in "$@"; do
  case "$arg" in
    --preflight-only|--dry-run) PREFLIGHT_ONLY=1 ;;
    --overwrite-newer)          OVERWRITE_NEWER=1 ;;
    --skip-db-backup)           SKIP_DB_BACKUP=1 ;;
    --since=*)                  SINCE="${arg#*=}" ;;
    --bundles=*)                BUNDLES="${arg#*=}" ;;
    -h|--help)
      sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'
      exit 0 ;;
    *)
      echo "Unknown option: $arg" >&2
      exit 2 ;;
  esac
done

# ----------------------------------------------------------------- plumbing --

RUN_ID="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$PACKAGE_DIR" "$LOG_DIR" "$DB_BACKUP_DIR" || {
  echo "Cannot create working directories under $WORK_DIR" >&2
  exit 1
}
LOG_FILE="$LOG_DIR/sync-$RUN_ID.log"
PACKAGE="$PACKAGE_DIR/bwtf-content-$RUN_ID"

log() { printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "$LOG_FILE"; }
die() {
  log "FAILED: $*"
  finish 1
}

finish() {
  local code="${1:-0}"
  if [ "$code" -eq 0 ]; then
    log "=== Sync finished successfully (run $RUN_ID) ==="
  else
    log "=== Sync finished with errors (run $RUN_ID, exit $code) ==="
  fi

  if [ -n "$MAILTO" ] && command -v mail >/dev/null 2>&1; then
    local subject
    if [ "$code" -eq 0 ]; then
      subject="BWTF content sync OK - $RUN_ID"
    else
      subject="BWTF content sync FAILED - $RUN_ID"
    fi
    if [ -n "$MAIL_FROM" ]; then
      # -r is mailx/s-nail; fall back to the default sender if unsupported.
      mail -s "$subject" -r "$MAIL_FROM" "$MAILTO" < "$LOG_FILE" 2>/dev/null \
        || mail -s "$subject" "$MAILTO" < "$LOG_FILE" \
        || true
    else
      mail -s "$subject" "$MAILTO" < "$LOG_FILE" || true
    fi
  fi

  exit "$code"
}

# One run at a time. A slow export must never overlap the next cron tick.
LOCK_DIR="$WORK_DIR/.bwtf-sync.lock"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
  echo "$(date '+%Y-%m-%d %H:%M:%S')  Another sync is already running ($LOCK_DIR). Exiting." | tee -a "$LOG_FILE"
  exit 0
fi
trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT

# Each site needs its own PHP binary, and neither is the one on PATH.
#
#  - Production is Drupal 8.9 on PHP 7.4 with Drush 10. The account's default
#    `php` is 8.3 and the interactive shell aliases `drush` to the STAGING
#    site, so both must be named explicitly here or this script would
#    silently export from the wrong database.
#  - Staging is Drupal 11 on PHP 8.3 with Drush 13, and the import needs an
#    unlimited memory_limit: the package decodes to more than the default.
PHP74="${PHP74:-/opt/alt/php74/usr/bin/php}"
PHP83="${PHP83:-/opt/cpanel/ea-php83/root/usr/bin/php}"

PROD_DRUSH="${PROD_DRUSH:-$PHP74 $PROD_ROOT/vendor/bin/drush}"
STAGE_DRUSH="${STAGE_DRUSH:-$PHP83 -d memory_limit=-1 $STAGE_ROOT/vendor/bin/drush.php}"

# -------------------------------------------------------------------- start --

log "=== BWTF content sync, run $RUN_ID ==="
log "Production:  $PROD_ROOT"
log "Staging:     $STAGE_ROOT"
log "Since:       $SINCE"
log "Bundles:     $BUNDLES"
log "Package:     $PACKAGE"
log "Prod drush:  $PROD_DRUSH"
log "Stage drush: $STAGE_DRUSH"
log "Mode:        $([ "$PREFLIGHT_ONLY" = "1" ] && echo 'preflight only' || echo 'preflight and apply')"

[ -d "$PROD_ROOT" ]  || die "Production root $PROD_ROOT does not exist."
[ -d "$STAGE_ROOT" ] || die "Staging root $STAGE_ROOT does not exist."
[ -x "$PHP74" ] || die "PHP 7.4 binary not found at $PHP74. Production cannot run."
[ -x "$PHP83" ] || die "PHP 8.3 binary not found at $PHP83. Staging cannot run."
[ -f "$PROD_ROOT/vendor/bin/drush" ] || die "No drush at $PROD_ROOT/vendor/bin/drush."
[ -f "$STAGE_ROOT/vendor/bin/drush.php" ] || die "No drush at $STAGE_ROOT/vendor/bin/drush.php."
[ -f "$PROD_ROOT/$MODULE_REL/scripts/bwtf_content_export.php" ] \
  || die "The sync module is not installed at $PROD_ROOT/$MODULE_REL."
[ -f "$STAGE_DOCROOT/$MODULE_REL/scripts/bwtf_content_import.php" ] \
  || die "The sync module is not installed at $STAGE_DOCROOT/$MODULE_REL."

# ------------------------------------------------------- 1. export from prod --

log "--- Step 1/5: exporting changed content from production ---"
cd "$PROD_ROOT" || die "Cannot enter $PROD_ROOT."
$PROD_DRUSH scr "$MODULE_REL/scripts/bwtf_content_export.php" -- \
  --since="$SINCE" \
  --bundles="$BUNDLES" \
  --output="$PACKAGE" >>"$LOG_FILE" 2>&1 \
  || die "Export failed. See $LOG_FILE."

[ -f "$PACKAGE/manifest.json" ] || die "Export produced no manifest at $PACKAGE."
log "Export complete."

# ------------------------------------------------------------ 2. sync files --

log "--- Step 2/5: syncing public files ---"
PROD_FILES="$PROD_ROOT/sites/default/files/"
STAGE_FILES="$STAGE_DOCROOT/sites/default/files/"

if [ -d "$PROD_FILES" ] && [ -d "$STAGE_FILES" ]; then
  # Both sites are on the same account and filesystem, so this is a local
  # copy, not a network transfer. Nothing is ever deleted on staging: it
  # holds generated image derivatives that production does not have.
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --itemize-changes \
      --exclude 'php/' --exclude 'css/' --exclude 'js/' --exclude 'styles/' \
      --exclude 'config_*/' --exclude '.htaccess' \
      "$PROD_FILES" "$STAGE_FILES" >>"$LOG_FILE" 2>&1 \
      || die "rsync of public files failed."
  else
    # NameHero shared hosting has no rsync. cp -a preserves timestamps and
    # -u copies only files that are newer than their destination, which
    # makes repeat runs cheap. Generated directories are skipped.
    log "rsync not available; using cp -au."
    ( cd "$PROD_FILES" || exit 1
      find . -type d \
        \( -name php -o -name css -o -name js -o -name styles -o -name 'config_*' \) -prune -o \
        -type f ! -name '.htaccess' -print0 2>/dev/null \
      | while IFS= read -r -d '' f; do
          dest="$STAGE_FILES/${f#./}"
          dest_dir="$(dirname "$dest")"
          [ -d "$dest_dir" ] || mkdir -p "$dest_dir" || continue
          cp -au "$f" "$dest" 2>/dev/null
        done
    ) >>"$LOG_FILE" 2>&1 || die "Local file copy failed."
  fi
  log "File sync complete."
else
  log "WARNING: skipping file sync, one of the files directories was not found."
fi

# ------------------------------------------------- 3. back up the stage DB ---

if [ "$PREFLIGHT_ONLY" = "1" ] || [ "$SKIP_DB_BACKUP" = "1" ]; then
  log "--- Step 3/5: staging database backup skipped ---"
else
  log "--- Step 3/5: backing up the staging database ---"
  cd "$STAGE_ROOT" || die "Cannot enter $STAGE_ROOT."
  DB_FILE="$DB_BACKUP_DIR/stage-before-sync-$RUN_ID.sql"
  $STAGE_DRUSH sql:dump --result-file="$DB_FILE" >>"$LOG_FILE" 2>&1 \
    || die "Staging database backup failed. Nothing was imported."
  gzip -f "$DB_FILE" >>"$LOG_FILE" 2>&1 || true
  log "Backup written to $DB_FILE.gz"
fi

# --------------------------------------------------- 4. preflight and apply --

cd "$STAGE_ROOT" || die "Cannot enter $STAGE_ROOT."

IMPORT_ARGS=(--package="$PACKAGE")
[ "$OVERWRITE_NEWER" = "1" ] && IMPORT_ARGS+=(--overwrite-newer)

log "--- Step 4/5: staging preflight ---"
$STAGE_DRUSH scr "$MODULE_REL/scripts/bwtf_content_import.php" -- \
  "${IMPORT_ARGS[@]}" >>"$LOG_FILE" 2>&1
PREFLIGHT_STATUS=$?

if [ $PREFLIGHT_STATUS -ne 0 ]; then
  log "Preflight reported blocking conflicts or errors. Nothing was imported."
  log "Review the newest import-report-preflight-*.json inside $PACKAGE."
  die "Preflight is not clean."
fi
log "Preflight is clean."

if [ "$PREFLIGHT_ONLY" = "1" ]; then
  log "--- Step 5/5: apply skipped (preflight-only run) ---"
  log "Package kept at $PACKAGE"
  finish 0
fi

log "--- Step 5/5: applying the package to staging ---"
$STAGE_DRUSH scr "$MODULE_REL/scripts/bwtf_content_import.php" -- \
  "${IMPORT_ARGS[@]}" --apply >>"$LOG_FILE" 2>&1 \
  || die "Apply failed. Review $LOG_FILE and the import report in $PACKAGE."

$STAGE_DRUSH cr >>"$LOG_FILE" 2>&1 || log "WARNING: cache rebuild returned a non-zero status."
log "Apply complete."

# ------------------------------------------------------------- 5. retention --

find "$PACKAGE_DIR" -maxdepth 1 -type d -name 'bwtf-content-*' -mtime "+$KEEP_PACKAGE_DAYS" \
  -exec rm -rf {} + 2>/dev/null || true
find "$LOG_DIR" -maxdepth 1 -type f -name 'sync-*.log' -mtime "+$KEEP_LOG_DAYS" \
  -delete 2>/dev/null || true
ls -1t "$DB_BACKUP_DIR"/stage-before-sync-*.sql.gz 2>/dev/null \
  | tail -n "+$((KEEP_DB_BACKUPS + 1))" | xargs -r rm -f 2>/dev/null || true

# Summarise what the run did, straight from the applied report.
APPLIED_REPORT="$(ls -1t "$PACKAGE"/import-report-applied-*.json 2>/dev/null | head -1)"
if [ -n "$APPLIED_REPORT" ]; then
  log "Report: $APPLIED_REPORT"
  # The report is pretty-printed, so the value follows a space after the
  # colon. Pull the summary counts out and log them one per line.
  grep -oE '"(create|update|unchanged|conflict|error|alias_create|alias_update|alias_unchanged|alias_conflict|missing_on_source|id_mismatch)": *[0-9]+' \
    "$APPLIED_REPORT" | head -12 | sed 's/^/  /' >>"$LOG_FILE" 2>/dev/null || true
fi

finish 0
