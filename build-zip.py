#!/usr/bin/env python3
"""
Build distribution zip for ys-editor-scope-wrapper.

Usage:
    python build-zip.py

Output:
    outputs/ys-editor-scope-wrapper.zip

Rules:
    - Plugin uses POSIX forward slashes (Linux/WP compatible)
    - No version number in filename (avoid WP plugin folder name change)
    - Excludes dev-only files (.git, .gitignore, build-zip.py, outputs/, .mo)
    - Single top-level directory `ys-editor-scope-wrapper/`
"""
import os
import zipfile
import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parent
SLUG = "ys-editor-scope-wrapper"
OUT_DIR = ROOT / "outputs"
OUT_ZIP = OUT_DIR / f"{SLUG}.zip"

EXCLUDE_DIRS = {".git", "outputs", "tests", ".dev", "node_modules", ".idea", ".vscode"}
EXCLUDE_FILES = {".gitignore", "build-zip.py", "DEVELOPMENT.md", ".DS_Store", "Thumbs.db"}
EXCLUDE_EXTS = {".mo"}  # regenerated from .po on deploy


def should_skip(rel_path: pathlib.Path) -> bool:
    parts = rel_path.parts
    if any(p in EXCLUDE_DIRS for p in parts):
        return True
    if rel_path.name in EXCLUDE_FILES:
        return True
    if rel_path.suffix in EXCLUDE_EXTS:
        return True
    return False


def main() -> int:
    OUT_DIR.mkdir(exist_ok=True)
    if OUT_ZIP.exists():
        OUT_ZIP.unlink()

    file_count = 0
    with zipfile.ZipFile(OUT_ZIP, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in sorted(ROOT.rglob("*")):
            if path.is_dir():
                continue
            rel = path.relative_to(ROOT)
            if should_skip(rel):
                continue
            arc = f"{SLUG}/{rel.as_posix()}"  # POSIX forward slashes
            zf.write(path, arc)
            file_count += 1

    print(f"OK  wrote {OUT_ZIP}")
    print(f"    {file_count} files, {OUT_ZIP.stat().st_size:,} bytes")

    # Verify invariants (per dev_packaging memory)
    with zipfile.ZipFile(OUT_ZIP) as zf:
        names = zf.namelist()
        top_dirs = sorted({n.split("/", 1)[0] for n in names})
        backslashes = [n for n in names if "\\" in n]
        flat_root = [n for n in names if "/" not in n]
        has_tests = any("/tests/" in n or n.startswith("tests/") for n in names)

    print()
    print("Invariants:")
    print(f"  top-level dirs:          {top_dirs}")
    print(f"  backslash paths:         {len(backslashes)}")
    print(f"  flat files at root:      {flat_root}")
    print(f"  tests/ present:          {has_tests}")
    assert top_dirs == [SLUG], f"expected single top dir [{SLUG}], got {top_dirs}"
    assert not backslashes, "found backslash paths"
    assert not flat_root, "found flat root files"
    assert not has_tests, "tests/ leaked into zip"
    print("  ALL PASS")
    return 0


if __name__ == "__main__":
    sys.exit(main())
