# BWTF Content Sync

A temporary, site-specific synchronization module for moving content changed on the Drupal 8 production site into the in-place-upgraded Drupal 11 staging site.

This module does **not** copy Drupal database tables and does **not** depend on the Migrate API. It exports content through Drupal 8's Entity API and imports it through Drupal 11's Entity API.

## Intended environment

- Production: `https://bwtf.com/` — Drupal 8
- Staging: `https://stage.bwtf.com/` — Drupal 11
- Cutoff: `2026-01-01 00:00:00 UTC`
- Node bundles: every bundle on the source site (`--bundles=all`, the default)

An earlier production query found 65 changed nodes, all of type `page`. The
export now covers every node bundle by default so a bundle that starts
changing later, such as `glossary_term`, is never silently missed. Narrow it
with `--bundles=page` when you want the older, smaller selection.

## What it exports

The export starts with nodes whose `created` or `changed` timestamp is on or after the cutoff. It then recursively includes supported referenced content entities, including:

- taxonomy terms;
- files and image file entities;
- media;
- paragraphs and other revisioned referenced entities;
- custom blocks referenced through entity fields;
- menu-link content entities pointing to the changed nodes;
- path aliases for the changed nodes.

It also writes `inventory.json`: one row for every node that currently exists
on the source site, in scope bundles, regardless of the cutoff. The importer
uses it to report destination content that no longer exists on production. The
inventory carries no field data, only `nid`, `uuid`, `bundle`, `title`,
`status`, `created` and `changed`.

Referenced users are not exported by default because production and staging originated from the same site and should already share user IDs. Use `--include-users` only after reviewing the security and account implications.

## Important limitations

1. **Deletions are reported, never applied.** The importer compares
   `inventory.json` against staging nodes and lists anything on staging that
   production no longer has, split into `missing_on_source` and `id_mismatch`.
   It never deletes or unpublishes: a staging node missing from production was
   either deleted there or created only on staging, and only a human can tell
   those apart. Rows created on staging after the cutoff are labelled
   `created_on_staging_after_cutoff` to make that judgement easier.
2. **Revision history is not cloned.** The current production revision is imported. Existing staging entities receive a new synchronization revision.
3. **Staging-newer records are conflicts by default.** The importer stops if staging was changed later than the source package. Review before using `--overwrite-newer`.
4. **ID/UUID collisions are blocking.** If a new production entity's numeric ID is already used by unrelated staging content, the importer will not overwrite it.
5. **Embedded files still require an rsync.** Files referenced only inside HTML may not be discoverable as entity references. Synchronize `sites/default/files` separately without `--delete`.
6. Test this module on a clone and keep database/file backups. It is a temporary bridge until the Drupal 11 launch, not a permanent two-way editorial synchronization system.

# Installation

Install the same module code on both environments.

## Drupal 8 production

Place it at:

```text
/home/bwtfcom/public_html/modules/custom/bwtf_content_sync
```

Then run from the Drupal 8 document root:

```bash
drush en bwtf_content_sync -y
drush cr
```

## Drupal 11 staging

Place it at:

```text
/home/bwtfcom/stage.bwtf.com/web/modules/custom/bwtf_content_sync
```

Then run from `/home/bwtfcom/stage.bwtf.com`:

```bash
vendor/bin/drush en bwtf_content_sync -y
vendor/bin/drush updb -y
vendor/bin/drush cr
```

# Recommended runbook

## 1. Back up staging

From `/home/bwtfcom/stage.bwtf.com`:

```bash
mkdir -p /home/bwtfcom/backups

vendor/bin/drush sql:dump \
  --result-file=/home/bwtfcom/backups/stage-before-content-sync-$(date +%F-%H%M%S).sql

tar -czf /home/bwtfcom/backups/stage-files-before-content-sync-$(date +%F-%H%M%S).tar.gz \
  web/sites/default/files
```

For a 45 GB file tree, a filesystem snapshot or hosting backup may be more practical than creating a large tar archive.

## 2. Confirm the production selection

From `/home/bwtfcom/public_html`:

```bash
drush sql:query "
SELECT
  nid,
  type,
  title,
  FROM_UNIXTIME(created) AS created_date,
  FROM_UNIXTIME(changed) AS changed_date,
  status
FROM node_field_data
WHERE type = 'page'
  AND (
    created >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
    OR changed >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
  )
ORDER BY changed ASC;
"
```

Save the list for comparison:

```bash
drush sql:query "
SELECT nid, type, title, created, changed, status
FROM node_field_data
WHERE type = 'page'
  AND (
    created >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
    OR changed >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
  )
ORDER BY nid;
" --extra=--batch \
> /home/bwtfcom/backups/production-pages-since-2026-01-01.tsv
```

## 3. Export a production package

From `/home/bwtfcom/public_html`:

```bash
PACKAGE=/home/bwtfcom/backups/bwtf-content-$(date +%F-%H%M%S)

# Use the path appropriate for the production Drush installation.
drush scr modules/custom/bwtf_content_sync/scripts/bwtf_content_export.php -- \
  --since=2026-01-01 \
  --bundles=all \
  --output="$PACKAGE"

echo "$PACKAGE"
```

The package contains:

```text
manifest.json
entities.json
inventory.json
aliases.json
files/
```

Because both sites are under the same NameHero account, staging may be able to read the package directly from `/home/bwtfcom/backups`. If not, copy the entire directory to a staging-readable location.

## 4. Synchronize public files

First run a dry run:

```bash
rsync -avhn --itemize-changes \
  /home/bwtfcom/public_html/sites/default/files/ \
  /home/bwtfcom/stage.bwtf.com/web/sites/default/files/
```

Review the output, then run without `-n`:

```bash
rsync -avh --itemize-changes \
  /home/bwtfcom/public_html/sites/default/files/ \
  /home/bwtfcom/stage.bwtf.com/web/sites/default/files/
```

Do not add `--delete`. Staging may contain generated derivatives or testing files that do not exist on production.

## 5. Run the staging preflight

From `/home/bwtfcom/stage.bwtf.com`:

```bash
PACKAGE=/home/bwtfcom/backups/bwtf-content-YYYY-MM-DD-HHMMSS

vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_import.php -- \
  --package="$PACKAGE"
```

This is a dry run. It writes a report inside the package directory:

```text
import-report-preflight-YYYYMMDD-HHMMSS.json
```

The preflight will classify entities as:

- `create`
- `update`
- `unchanged`
- `conflict`
- `error`

Do not apply the package while `conflict`, `error`, or `alias_conflict` is nonzero.

### Common conflict: staging ID collision

Example:

```text
Source ID 3025 is already occupied by a different UUID.
```

This means staging-only content used an ID that production later assigned. Preserve/export the staging-only content, remove or relocate it, and rerun preflight. Do not force an overwrite.

### Common conflict: destination newer

Example:

```text
Destination was changed later than production.
```

Compare the production and staging versions. When the production version should win, rerun the preflight with:

```bash
--overwrite-newer
```

Use that option only after review.

## 6. Apply the package

After the preflight is clean and the backup is confirmed:

```bash
vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_import.php -- \
  --package="$PACKAGE" \
  --apply

vendor/bin/drush cr
```

If the approved preflight required production to overwrite newer staging values:

```bash
vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_import.php -- \
  --package="$PACKAGE" \
  --overwrite-newer \
  --apply
```

The apply script performs a second complete preflight before writing anything.

## 7. Validate staging

Run the same changed-content query on staging:

```bash
vendor/bin/drush sql:query "
SELECT nid, type, title,
  FROM_UNIXTIME(created) AS created_date,
  FROM_UNIXTIME(changed) AS changed_date,
  status
FROM node_field_data
WHERE type = 'page'
  AND (
    created >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
    OR changed >= UNIX_TIMESTAMP('2026-01-01 00:00:00')
  )
ORDER BY changed ASC;
"
```

Also verify:

- the newest ten production pages;
- updated older pages;
- path aliases;
- taxonomy links;
- galleries and embedded images;
- menu links;
- unpublished content;
- author ownership;
- any paragraph or custom-block content.

# Repeating the synchronization

The package can be regenerated from the same January 1 cutoff. Existing entities are matched by UUID and ID, and unchanged entities are skipped.

Suggested schedule while testing continues:

1. Keep Drupal 8 production as the editorial source of truth.
2. Avoid editing production-owned pages on staging.
3. Export and import a fresh package before each major test round.
4. Repeat immediately before final launch.

# Automated nightly sync

`scripts/bwtf_sync_cron.sh` runs the whole sequence unattended: export,
`rsync`, staging database backup, preflight, and apply only when the preflight
is clean. It takes a lock so two runs never overlap, writes a timestamped log,
and prunes old packages, logs and backups.

Install it on the NameHero account:

```bash
mkdir -p /home/bwtfcom/bin
cp /home/bwtfcom/stage.bwtf.com/web/modules/custom/bwtf_content_sync/scripts/bwtf_sync_cron.sh \
  /home/bwtfcom/bin/bwtf_sync_cron.sh
chmod +x /home/bwtfcom/bin/bwtf_sync_cron.sh
```

Always do a preflight-only run first:

```bash
bash /home/bwtfcom/bin/bwtf_sync_cron.sh --preflight-only
```

Then schedule it, for example nightly at 02:30 server time:

```cron
30 2 * * * /bin/bash /home/bwtfcom/bin/bwtf_sync_cron.sh >/dev/null 2>&1
```

Every setting is an environment variable with the same name as the constant at
the top of the script: `PROD_ROOT`, `STAGE_ROOT`, `STAGE_DOCROOT`, `WORK_DIR`,
`SINCE`, `BUNDLES`, `MAILTO`, `KEEP_PACKAGE_DAYS`, `KEEP_LOG_DAYS`,
`KEEP_DB_BACKUPS`, `OVERWRITE_NEWER`, `PREFLIGHT_ONLY`, `SKIP_DB_BACKUP`.

To be emailed a report on every run:

```cron
30 2 * * * MAILTO_ADDR=you@example.com /bin/bash -c 'MAILTO="$MAILTO_ADDR" /home/bwtfcom/bin/bwtf_sync_cron.sh' >/dev/null 2>&1
```

What the nightly run will *not* do on its own:

- overwrite a staging row that is newer than production (set
  `OVERWRITE_NEWER=1` only after reviewing why staging is newer);
- delete or unpublish anything;
- continue past a preflight that reports `conflict`, `error` or
  `alias_conflict`. The run stops, staging is untouched, and the log names the
  report to read.

Because the run aborts on conflicts, a failed night is a signal to look, not a
reason to force. The most common cause is somebody editing a
production-owned page on staging.

# Final launch

1. Put Drupal 8 production into maintenance mode or enforce an editorial freeze.
2. Take final production and staging backups.
3. Export a final package from January 1, 2026.
4. Run file rsync.
5. Run staging preflight and apply.
6. Validate the newest content and critical paths.
7. Complete the Drupal 11 production cutover.
8. Stop using this Drupal 8-to-11 bridge after Drupal 11 becomes production.

# Optional flags

## Export

```text
--since=2026-01-01
--bundles=all
--bundles=page,glossary_term
--output=/absolute/package/path
--include-users
--skip-menu-links
```

## Import

```text
--package=/absolute/package/path
--apply
--overwrite-newer
--fallback-owner=1
--skip-validation
--allow-site-uuid-mismatch
```

`--skip-validation` and `--allow-site-uuid-mismatch` are emergency diagnostic options, not normal operating options.
