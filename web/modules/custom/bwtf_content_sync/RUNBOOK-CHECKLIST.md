# BWTF Content Sync Checklist

## Before export

- [ ] Confirm Drupal 8 production is the editorial source of truth.
- [ ] Confirm the cutoff is January 1, 2026.
- [ ] Confirm the bundle scope (`all` by default).
- [ ] Back up Drupal 11 staging database.
- [ ] Back up or snapshot staging public files.
- [ ] Confirm the same module version is installed on production and staging.

## Export

- [ ] Run the production SQL selection query.
- [ ] Save the TSV list of selected nodes.
- [ ] Export the package.
- [ ] Review export warnings in `manifest.json`.
- [ ] Confirm the package is readable by staging.

## Files

- [ ] Run `rsync` with `-n` first.
- [ ] Review the dry-run list.
- [ ] Run `rsync` without `--delete`.

## Import

- [ ] Run staging preflight without `--apply`.
- [ ] Review `conflict`, `error`, and `alias_conflict` counts.
- [ ] Resolve staging ID/UUID collisions.
- [ ] Review any destination-newer records.
- [ ] Review the deletion report (`missing_on_source`, `id_mismatch`).
- [ ] Confirm the staging backup can be restored.
- [ ] Apply the package.
- [ ] Rebuild caches.

## Validation

- [ ] Compare selected node counts.
- [ ] Verify newest ten pages.
- [ ] Verify updated older pages.
- [ ] Verify aliases.
- [ ] Verify images and galleries.
- [ ] Verify taxonomy references.
- [ ] Verify menu links.
- [ ] Verify unpublished pages.
- [ ] Verify author ownership.
- [ ] Save the import report with the release notes.

## Automation

- [ ] Run `bwtf_sync_cron.sh --preflight-only` by hand once.
- [ ] Confirm the log, package and backup directories are being written.
- [ ] Add the cron entry.
- [ ] Confirm the first scheduled run succeeded.
- [ ] Re-check the log after any production editorial burst.
