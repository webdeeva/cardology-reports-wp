#!/usr/bin/env bash
# Build a distributable cardology-reports zip locally.
#
# Usage: ./scripts/build-zip.sh [version]
#   If version is omitted, the Version: header from cardology-reports.php is used.
set -euo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
	VERSION=$(grep -E '^\s*\*\s*Version:' cardology-reports.php | awk -F: '{print $2}' | xargs)
fi

STAGE=$(mktemp -d)
PLUGIN_DIR="$STAGE/cardology-reports"
mkdir -p "$PLUGIN_DIR"

rsync -av \
	--exclude='.git/' \
	--exclude='.github/' \
	--exclude='.gitignore' \
	--exclude='.DS_Store' \
	--exclude='.wp-env.json' \
	--exclude='.wp-env.override.json' \
	--exclude='README.md' \
	--exclude='node_modules/' \
	--exclude='vendor/' \
	--exclude='tests/' \
	--exclude='scripts/' \
	--exclude='*.zip' \
	--exclude='composer.lock' \
	./ "$PLUGIN_DIR/"

ZIP="$ROOT/cardology-reports-${VERSION}.zip"
(cd "$STAGE" && zip -r "$ZIP" cardology-reports >/dev/null)
echo "✓ Built $ZIP"
unzip -l "$ZIP" | tail -10
