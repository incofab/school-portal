# One-time commands

Commands in this directory perform legacy-data backfills or schema/data conversions. They are intended to be reviewed and run during a controlled deployment or maintenance window, not scheduled as recurring application commands.

Use each command's `--dry-run` option where available before running the write operation. The commands keep their existing Artisan signatures so deployment scripts and documented instructions do not need to change.
