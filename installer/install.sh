#!/usr/bin/env sh
set -eu
REPO="${NEXPH_REPO:-rawp-id/nexph}"
REF="${NEXPH_REF:-main}"
PREFIX="${NEXPH_HOME:-$HOME/.nexph}"
TMP="${TMPDIR:-/tmp}/nexph-install-$$"
mkdir -p "$TMP" "$PREFIX"
URL="https://github.com/$REPO/archive/refs/heads/$REF.tar.gz"
if command -v curl >/dev/null 2>&1; then
  curl -fsSL "$URL" -o "$TMP/nexph.tar.gz"
elif command -v wget >/dev/null 2>&1; then
  wget -qO "$TMP/nexph.tar.gz" "$URL"
else
  echo "curl or wget required" >&2
  exit 1
fi
tar -xzf "$TMP/nexph.tar.gz" -C "$TMP"
SRC="$(find "$TMP" -maxdepth 1 -type d -name 'nexph-*' | head -n 1)"
rm -rf "$PREFIX/runtime"
mkdir -p "$PREFIX/bin" "$PREFIX/runtime"
cp -R "$SRC"/* "$PREFIX/runtime/"
ln -sf "$PREFIX/runtime/nexph" "$PREFIX/bin/nexph"
chmod +x "$PREFIX/runtime/nexph" "$PREFIX/bin/nexph"
echo "Nexph installed: $PREFIX/bin/nexph"
echo "Add to PATH: export PATH=\"$PREFIX/bin:\$PATH\""