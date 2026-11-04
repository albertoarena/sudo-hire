#!/usr/bin/env bash
# Renders the app icon from resources/icons/app-icon.svg to the files that NativePHP reads:
# public/icon.png (1024 by 1024) and public/icon.icns (16 to 1024 pixels, with the 2x sizes).
# Needs rsvg-convert (brew install librsvg) and iconutil (macOS). Run it from any folder.
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
svg="$root/resources/icons/app-icon.svg"

for tool in rsvg-convert iconutil; do
    if ! command -v "$tool" > /dev/null; then
        echo "render-app-icon: $tool is missing." >&2
        exit 1
    fi
done

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
iconset="$work/icon.iconset"
mkdir "$iconset"

for size in 16 32 128 256 512; do
    rsvg-convert -w "$size" -h "$size" "$svg" -o "$iconset/icon_${size}x${size}.png"
    rsvg-convert -w "$((size * 2))" -h "$((size * 2))" "$svg" -o "$iconset/icon_${size}x${size}@2x.png"
done

iconutil -c icns "$iconset" -o "$root/public/icon.icns"
rsvg-convert -w 1024 -h 1024 "$svg" -o "$root/public/icon.png"

echo "Rendered public/icon.png and public/icon.icns."
