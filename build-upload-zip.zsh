#!/usr/bin/env zsh
#
# Build a ZIP of the plugin for uploading via WP Admin (Plugins → Add New → Upload).
# Run from the plugin root (this folder). Output: wordpress_plugin.zip in current directory.
#

set -e

SCRIPT_DIR="${0:A:h}"
PLUGIN_DIR="${SCRIPT_DIR:A}"
# Forțează numele folderului plugin-ului în arhivă să coincidă cu textdomain-ul,
# astfel încât WordPress să recunoască zip-ul ca update la pluginul existent.
PLUGIN_FOLDER_NAME="my-plugin"
ZIP_NAME="${PLUGIN_FOLDER_NAME}.zip"
ZIP_PATH="${PLUGIN_DIR}/${ZIP_NAME}"

cd "$PLUGIN_DIR"

# Asigură-te că fișierele calculatorului (ultima versiune locală) există
CALC_FILES=(
  "assets/css/calculator.css"
  "assets/js/calculator.js"
  "includes/class-my-plugin-public.php"
  "my-plugin.php"
)
for f in "${CALC_FILES[@]}"; do
  [[ -f "$f" ]] || { echo "Lipsește: $f" >&2; exit 1; }
done

# Remove old zip if present
[[ -f "$ZIP_PATH" ]] && rm -f "$ZIP_PATH"

# Zip contents of current dir into a parent folder named after the plugin
# (WordPress installs the plugin into a folder with the zip's root name).
TMP_DIR="$(mktemp -d -t mpc-zip-XXXXXX)"
STAGE_DIR="${TMP_DIR}/${PLUGIN_FOLDER_NAME}"
mkdir -p "$STAGE_DIR"

# Touch fișierele asset ca filemtime să fie cel mai recent
# (asta forțează cache-bust pe URL-urile JS/CSS imediat după upload).
touch "$PLUGIN_DIR/assets/css/calculator.css" "$PLUGIN_DIR/assets/js/calculator.js" 2>/dev/null || true

# rsync excludes — fișiere/dosare dev-only care nu merg în plugin-ul de producție
rsync -a \
  --exclude='.DS_Store' \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='.qodo' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='tmp_*' \
  --exclude='tmp_transcripts' \
  --exclude='tmp_numbers_extract' \
  --exclude='tmp_openpyxl' \
  --exclude='docs' \
  --exclude='scripts' \
  --exclude='tools' \
  --exclude='*.zip' \
  --exclude='build-upload-zip.zsh' \
  --exclude='dev-send-quote.php' \
  --exclude='preview-email-template.php' \
  --exclude='*-preview.html' \
  --exclude='shipping-calculator-preview.html' \
  --exclude='index.html' \
  --exclude='AIRFREIGHT_ESTIMATOR_STEPS.md' \
  --exclude='RAIL_LCL_CALCULATION.md' \
  --exclude='DEPLOY.md' \
  --exclude='README.md' \
  --exclude='client-observations-*.md' \
  --exclude='rail-service-summary.md' \
  --exclude='.resend-local.php' \
  --exclude='.resend-local.example.php' \
  --exclude='.wp-root-local.php' \
  --exclude='.wp-root-local.php.example' \
  --exclude='*.log' \
  ./ "$STAGE_DIR/"

cd "$TMP_DIR"
zip -r -q "$ZIP_PATH" "$PLUGIN_FOLDER_NAME"
cd "$PLUGIN_DIR"
rm -rf "$TMP_DIR"

echo "Created: $ZIP_PATH"
echo "Versiune: $(grep -E '^[[:space:]]*\*[[:space:]]*Version:' my-plugin.php | head -1 | awk -F: '{print $2}' | xargs)"
echo "Upload acest fișier în WP Admin → Plugins → Add New → Upload Plugin"
