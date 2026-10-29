# Auto-deploy Nugasin

Workflow: `.github/workflows/deploy.yml`, triggered by pushes to `main` or Run workflow on `main`.

1. Install locked dependencies and run the PHP tests on PHP 8.5.
2. Exercise successful deployment, checksum rejection and rollback after PHP/HTTP failures in disposable fixtures.
3. Build an allowlisted production artifact with no dev vendor, `.env`, storage, database or paid question bank.
4. Verify the pinned SSH host key, upload the artifact, verify its SHA-256, and acquire a server deployment lock.
5. Enter maintenance, move the previous managed code to a private backup, install new code, and refresh Laravel caches.
6. Verify application bootstrap, database connectivity, required tables/routes, unchanged private files and public HTTP endpoints. Restore the previous code if an installation or health check fails.

Target: `/home/nugasinclass/htdocs/nugasinclass.web.id` using the dedicated `nugasindeploy` account. PHP CLI is explicitly `/usr/bin/php8.5`, matching the site's PHP-FPM version at setup.

GitHub repository Secrets: `NUGASIN_DEPLOY_SSH_KEY` and `NUGASIN_DEPLOY_KNOWN_HOSTS`. The dedicated key disables SSH forwarding and PTY. Neither value belongs in source control. Only trusted maintainers should be able to push to `main` or change workflows.

The pipeline does **not** run migrations, seeders, key generation, database restores, or private question-bank imports. Review schema changes separately before releasing features that need them. It does not delete uploaded files or customer data. Rollback restores code, not database transactions.

`/home/nugasindeploy/.nugasin-deploy/` contains incoming archives, private code backups, `current-commit`, and `last-backup`. Backup directories are mode 700. Backups are retained for manual review; monitor disk space and prune only obsolete releases after checking their paths. These are code backups, not a substitute for database/upload backups.

For a normal rollback, revert the faulty commit in Git and push the revert to `main`; the pipeline tests and deploys it. For an interrupted deployment or unavailable workflow, inspect the recorded private backup and maintenance state before restoring managed code paths. Never overwrite `.env`, storage or private materials from a code backup.

To pause auto-deploy, disable **Test and deploy Nugasin** in GitHub Actions. To update the SSH key, replace the dedicated key in the server's authorized_keys and the corresponding encrypted GitHub Secret. Keep the local maintenance key separate.

No deployment UI is added to the application's backoffice.

CloudPanel extra SSH users share the site group but may not own existing directories extracted with mode 755. Initial setup uses CloudPanel's authorized `clpctl system:permissions:reset --directories=770 --files=660 --path=...` for the specific managed code directories that need it, keeping the site's group access. The deployment preflight checks both parent and directory write permissions before entering maintenance. If a later manual upload restores incompatible permissions, correct only the reported code directory through CloudPanel and rerun the workflow. Never reset permissions recursively on unrelated sites or private user data.
