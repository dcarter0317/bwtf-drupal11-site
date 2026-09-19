#!/usr/bin/env bash
#
# BWTF sync - Step 1: discovery. READ ONLY.
# Nothing here writes, enables, deletes, or changes anything on either site.
#
# Run in one SSH session on NameHero:
#   bash step1-discovery.sh 2>&1 | tee ~/bwtf-step1.txt
# Then paste the output back into the chat.

PROD="${PROD:-/home/bwtfcom/public_html}"
STAGE="${STAGE:-/home/bwtfcom/stage.bwtf.com}"

hr() { echo; echo "######## $* ########"; }

hr "paths"
ls -d "$PROD" "$STAGE" 2>&1
echo "whoami: $(whoami)   home: $HOME"

hr "production drush + drupal version"
cd "$PROD" 2>/dev/null && {
  PD="$(command -v drush || echo ./vendor/bin/drush)"
  echo "drush: $PD"
  "$PD" --version 2>&1 | head -2
  "$PD" status 2>&1 | head -20
}

hr "staging drush + drupal version"
cd "$STAGE" 2>/dev/null && {
  vendor/bin/drush --version 2>&1 | head -2
  vendor/bin/drush status 2>&1 | head -20
}

hr "php versions"
php -v 2>&1 | head -1
echo "prod php: $(cd "$PROD" && php -v 2>&1 | head -1)"

hr "custom modules present"
echo "--- production:"; ls -1 "$PROD/modules/custom/" 2>&1
echo "--- staging:";    ls -1 "$STAGE/web/modules/custom/" 2>&1

hr "is staging a git checkout"
cd "$STAGE" 2>/dev/null && git log --oneline -1 2>&1 && git status --short 2>&1 | head -10

hr "production nodes by type, and how many changed since Jan 1"
cd "$PROD" && "$PD" sql:query "
SELECT type,
       COUNT(*) AS total,
       SUM(created >= UNIX_TIMESTAMP('2026-01-01 00:00:00') OR changed >= UNIX_TIMESTAMP('2026-01-01 00:00:00')) AS touched_since_jan1,
       SUM(created >= UNIX_TIMESTAMP('2026-01-01 00:00:00')) AS created_since_jan1
FROM node_field_data
WHERE default_langcode = 1
GROUP BY type;" 2>&1

hr "production other content volumes"
cd "$PROD" && "$PD" sql:query "
SELECT 'media' AS kind, COUNT(*) AS n FROM media_field_data WHERE default_langcode = 1
UNION ALL SELECT 'files', COUNT(*) FROM file_managed
UNION ALL SELECT 'taxonomy_terms', COUNT(*) FROM taxonomy_term_field_data WHERE default_langcode = 1
UNION ALL SELECT 'menu_links', COUNT(*) FROM menu_link_content_data
UNION ALL SELECT 'users', COUNT(*) FROM users_field_data;" 2>&1

hr "staging nodes by type"
cd "$STAGE" && vendor/bin/drush sql:query "
SELECT type, COUNT(*) AS total
FROM node_field_data
WHERE default_langcode = 1
GROUP BY type;" 2>&1

hr "site uuids (must match, or the importer refuses the package)"
echo "prod:  $(cd "$PROD" && "$PD" config:get system.site uuid 2>&1 | tail -1)"
echo "stage: $(cd "$STAGE" && vendor/bin/drush config:get system.site uuid 2>&1 | tail -1)"

hr "disk and files size"
df -h "$HOME" 2>&1 | tail -1
echo "prod files:  $(du -sh "$PROD/sites/default/files" 2>/dev/null | cut -f1)"
echo "stage files: $(du -sh "$STAGE/web/sites/default/files" 2>/dev/null | cut -f1)"

hr "done"
