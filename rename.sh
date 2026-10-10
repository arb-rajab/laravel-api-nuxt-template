#!/usr/bin/env bash
# Renames the template's placeholder project name.
#
# Usage: ./rename.sh <new-name> [vendor]
#
#   <new-name>  your project name, e.g. "acme-shop" or "acme_shop"
#   [vendor]    Composer/npm vendor replacing "your-org" (default: leave as is)
#
# Replaces, in every text file (skipping .git, vendor, node_modules, .nuxt):
#   starter_app  -> acme_shop   (database/role names, env defaults, queue names)
#   starter-app  -> acme-shop   (package names, UI brand, titles)
#   your-org     -> <vendor>    (only if a vendor is given)
#
# Run it once, right after creating a repo from this template, before the first
# commit/migration. Review `git diff`, then delete this script.
set -euo pipefail

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
  echo "Usage: $0 <new-name> [vendor]" >&2
  exit 1
fi

normalize() { echo "$1" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/'"$2"'/g; s/^'"$2"'+|'"$2"'+$//g'; }

SNAKE="$(normalize "$1" _)"
KEBAB="$(normalize "$1" -)"
VENDOR="${2:-}"

if [ -z "$SNAKE" ] || ! [[ "$SNAKE" =~ ^[a-z] ]]; then
  echo "The new name must start with a letter and contain letters or digits." >&2
  exit 1
fi

cd "$(dirname "$0")"

mapfile -t FILES < <(grep -rIl --exclude-dir=.git --exclude-dir=vendor --exclude-dir=node_modules \
  --exclude-dir=.nuxt --exclude-dir=.output --exclude-dir=build --exclude=rename.sh \
  -e 'starter_app' -e 'starter-app' -e 'your-org' . || true)

for f in "${FILES[@]}"; do
  sed -i -e "s/starter_app/${SNAKE}/g" -e "s/starter-app/${KEBAB}/g" "$f"
  if [ -n "$VENDOR" ]; then
    sed -i -e "s#your-org#${VENDOR}#g" "$f"
  fi
done

echo "Renamed to '${SNAKE}' / '${KEBAB}' in ${#FILES[@]} files."
echo "If you already created the Postgres roles/databases with the old names, recreate them"
echo "(docker/postgres/init/01-roles-and-database.sql) before running migrations."
echo "Review 'git diff', then delete rename.sh."
