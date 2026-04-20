#!/usr/bin/env zsh
#
# Build a ZIP of the plugin for uploading via WP Admin (Plugins → Add New → Upload).
# Run from the plugin root (this folder). Output: wordpress_plugin.zip in parent directory.
#

set -e

SCRIPT_DIR="${0:A:h}"
PLUGIN_DIR="${SCRIPT_DIR:A}"
PARENT_DIR="${PLUGIN_DIR:h}"
ZIP_NAME="${PLUGIN_DIR:t}.zip"
ZIP_PATH="${PARENT_DIR}/${ZIP_NAME}"

cd "$PLUGIN_DIR"

# Asigură-te că fișierele calculatorului (ultima versiune locală) există
CALC_FILES=(
  "assets/css/calculator.css"
  "assets/js/calculator.js"
  "includes/class-my-plugin-public.php"
)
for f in "${CALC_FILES[@]}"; do
  [[ -f "$f" ]] || { echo "Lipsește: $f" >&2; exit 1; }
done

# Remove old zip if present
[[ -f "$ZIP_PATH" ]] && rm -f "$ZIP_PATH"

# Zip contents of current dir; exclude junk (WordPress uses zip name as folder name)
zip -r "$ZIP_PATH" . \
  -x "*.DS_Store" \
  -x ".git/*" \
  -x "build-upload-zip.zsh" \
  -x "*.zip"

echo "Created: $ZIP_PATH"
echo "Upload this file in WP Admin → Plugins → Add New → Upload Plugin"
