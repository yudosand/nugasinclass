#!/usr/bin/env bash
set -Eeuo pipefail
umask 0007
COMMIT=${1:-}
DIGEST=${2:-}
[[ "$COMMIT" =~ ^[a-f0-9]{40}$ && "$DIGEST" =~ ^[a-f0-9]{64}$ ]] || { echo 'Invalid release identity'; exit 1; }
APP=/home/nugasinclass/htdocs/nugasinclass.web.id
STATE=/home/nugasindeploy/.nugasin-deploy
PHP=/usr/bin/php8.5
ARCHIVE="$STATE/incoming/$COMMIT.tar.gz"
[[ "$(realpath "$APP")" == "$APP" && -f "$APP/.env" && -d "$APP/storage" ]] || { echo 'Unexpected application path'; exit 1; }
mkdir -p "$STATE/backups"
chmod 700 "$STATE" "$STATE/backups"
exec 9>"$STATE/deploy.lock"
flock -n 9 || { echo 'Another deployment is running'; exit 1; }
echo "$DIGEST  $ARCHIVE" | sha256sum --check --status
STAGE=$(mktemp -d "$STATE/stage-$COMMIT-XXXXXX")
chmod 750 "$STAGE"
BACKUP="$STATE/backups/$(date -u +%Y%m%dT%H%M%SZ)-$COMMIT"
mkdir -m 700 "$BACKUP"
python3 - "$ARCHIVE" "$STAGE" <<'PY'
import sys,tarfile,pathlib
with tarfile.open(sys.argv[1],'r:gz') as archive:
    for member in archive.getmembers():
        path=pathlib.PurePosixPath(member.name)
        if path.is_absolute() or '..' in path.parts or not (member.isdir() or member.isfile()):
            raise SystemExit('Unsafe release archive')
    archive.extractall(sys.argv[2],filter='data')
PY
[[ "$(python3 -c 'import json,sys;print(json.load(open(sys.argv[1]))["commit"])' "$STAGE/release.json")" == "$COMMIT" ]] || exit 1
# Only these code paths can be replaced. Runtime configuration, uploads and paid media are never targets.
PATHS=(app config routes database/migrations database/seeders database/factories resources/views resources/css resources/js public/assets vendor bootstrap/app.php bootstrap/providers.php artisan composer.json composer.lock package.json vite.config.js public/index.php public/.htaccess public/robots.txt public/favicon.ico public/favicon-32.png public/favicon-192.png public/apple-touch-icon.png bootstrap/cache)
for path in "${PATHS[@]}"; do
  [[ -e "$STAGE/$path" && ! -L "$APP/$path" && -w "$(dirname "$APP/$path")" ]] || { echo "Unusable code path: $path"; exit 1; }
done
find "$STAGE" -type d -exec chmod 2770 {} +
find "$STAGE" -type f -exec chmod 660 {} +
sha256sum "$APP/.env" "$APP/resources/exams/manifest.json" "$APP/resources/exams/questions.json" > "$BACKUP/preserved.sha256"
[[ ! -f "$APP/storage/framework/down" ]] || { echo 'Application is already in maintenance'; exit 1; }
installed=()
maintenance=0
rollback() {
  local code=$?
  if ((code==0)); then code=1; fi
  trap - ERR INT TERM
  set +e
  echo 'Deployment failed; restoring previous code.' >&2
  if ((${#installed[@]})); then
    (cd "$APP" && "$PHP" artisan down --retry=60) >/dev/null 2>&1
    for ((i=${#installed[@]}-1;i>=0;i--)); do
      path=${installed[$i]}
      mkdir -p "$BACKUP/failed/$(dirname "$path")"
      if [[ -e "$APP/$path" ]]; then mv "$APP/$path" "$BACKUP/failed/$path" || { echo 'Rollback move failed; maintenance retained'; exit 1; }; fi
      if [[ -e "$BACKUP/code/$path" ]]; then mv "$BACKUP/code/$path" "$APP/$path" || { echo 'Rollback restore failed; maintenance retained'; exit 1; }; fi
    done
    (cd "$APP" && "$PHP" artisan view:clear) >/dev/null 2>&1
  fi
  if ((maintenance)); then (cd "$APP" && "$PHP" artisan up) >/dev/null 2>&1; fi
  echo "Previous code backup: $BACKUP" >&2
  exit "${code:-1}"
}
trap rollback ERR INT TERM
cd "$APP"
"$PHP" artisan down --retry=60
maintenance=1
for path in "${PATHS[@]}"; do
  mkdir -p "$BACKUP/code/$(dirname "$path")"
  if [[ -e "$APP/$path" ]]; then mv "$APP/$path" "$BACKUP/code/$path"; fi
  installed+=("$path")
  mv "$STAGE/$path" "$APP/$path"
done
# Cache operations run in the final path, so compiled config never refers to a temporary release directory.
"$PHP" artisan package:discover --ansi
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:clear
"$PHP" "$STAGE/verify-release.php" "$APP"
sha256sum --check --status "$BACKUP/preserved.sha256"
"$PHP" artisan up
for route in /up / /login /simulasi; do
  curl --fail --silent --show-error --retry 3 --retry-delay 2 --max-time 20 "https://nugasinclass.web.id$route" -o /dev/null
done
LIVE_COMMIT=$(curl --fail --silent --show-error --max-time 20 "https://nugasinclass.web.id/assets/release.json?commit=$COMMIT" | python3 -c 'import json,sys;print(json.load(sys.stdin)["commit"])')
[[ "$LIVE_COMMIT" == "$COMMIT" ]]
printf '%s\n' "$COMMIT" > "$STATE/current-commit"
printf '%s\n' "$BACKUP" > "$STATE/last-backup"
trap - ERR INT TERM
echo "DEPLOYED $COMMIT; health checks passed; backup: $BACKUP"
