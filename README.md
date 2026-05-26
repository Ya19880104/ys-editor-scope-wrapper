# YS Editor Scope Wrapper

> 中文版本: [README.zh-TW.md](README.zh-TW.md)

Apply CSS scope classes to the WordPress Block Editor wrapper and iframe canvas, so wrapper-scoped CSS that only exists on the frontend also works inside the editor preview.

## The problem

Many page-builder workflows write CSS as `.scope-class .xxx { ... }`:

- **On the frontend** — the page builder (GreenShift / GL Page Builder / etc.) injects `<div class="scope-class">` around `the_content`, so `.scope-class .xxx` rules match.
- **In the Block Editor** — that wrapper does not exist. `.editor-styles-wrapper` has no `scope-class` on it, so every wrapper-scoped rule becomes dead. The editor shows raw text, no fonts, no colors, no layout.

This plugin closes that gap.

## What it does

On every Block Editor load:

1. Reads the configured class list from `ys_editor_scope_classes` option.
2. Adds each class to `.editor-styles-wrapper` (and `iframe[name="editor-canvas"]` body, for the new iframe canvas mode).
3. Uses `MutationObserver` so iframe lazy-loading and editor remounting are handled.

The added classes are pure DOM additions — they are **not** written into `post_content`.

## Auto-detection

The Settings screen automatically scans your published posts and surfaces candidate classes you can tick on/off:

| Source | What it picks up |
|--------|------------------|
| Style Manager root class | The first `dynamicGClasses[].value` inside each `<!-- wp:greenshift-blocks/element ... isVariation:"stylemanager" -->` block. |
| Inline `<style>` root selector | The first `.class-name {` selector inside each `<!-- wp:html -->` `<style>` block. |
| WordPress core | `alignfull`, `alignwide` — always offered. |

The detector deliberately ignores deeper element classes (`.btn`, `.hero__title`, etc.) so you only see candidates that are plausibly the outer wrapper. You can always add anything the scanner missed in the "Custom classes" textarea below the table.

The detection cache is invalidated automatically when any post is saved.

## Installation

1. Download `ys-editor-scope-wrapper.zip` from the [latest release](https://github.com/Ya19880104/ys-editor-scope-wrapper/releases/latest).
2. WordPress admin → **Plugins → Add New → Upload Plugin** → choose the zip.
3. Activate.
4. Go to **Settings → YS Editor Scope** to configure the class list.

Defaults out of the box: `v5-page` and `alignfull` are pre-populated.

## Settings UI

| Field | Purpose |
|-------|---------|
| Detected candidates | Checkbox list of classes found in your posts. Tick the ones you want applied. |
| Re-detect now | Clears the detection cache and rescans. Useful after major content imports. |
| Custom classes | Free-form textarea, one class per line (no leading dot). For classes the scanner couldn't find. |

When you save:

- All ticked checkboxes + all valid lines from the textarea are merged.
- Each class is sanitized — only `a-z` `A-Z` `0-9` `_` `-` characters survive, leading character must be a letter or `_`.
- Duplicates removed, stored as a newline-separated string in `ys_editor_scope_classes`.
- PRG (Post-Redirect-Get) pattern is used to avoid duplicate notices and resubmission on refresh.

## Plugin metadata

| | |
|---|---|
| Slug | `ys-editor-scope-wrapper` |
| Function prefix | `ys_editor_scope_wrapper_` |
| Class / Constant prefix | `YS_EDITOR_SCOPE_WRAPPER_` |
| Option key | `ys_editor_scope_classes` |
| Transient cache key | `ys_editor_scope_detected` |
| Text domain | `ys-editor-scope-wrapper` |
| Version | 1.1.2 |

## Scope and limits

- Active only on the post/page Block Editor.
- **Not** active on the Site Editor, Widgets screen, or Customizer (intentional — avoids polluting Full Site Editing).
- Classes are DOM-only additions, not persisted to `post_content`.
- If the page builder rewrites its wrapper mechanism in a future version, you may need to update the class list. The detector will surface the new candidate automatically on next scan.

## Internationalization

English source strings shipped with full Traditional Chinese (`zh_TW`) translation under `lang/`.

To regenerate the `.mo` file after editing the `.po`:

```bash
wp i18n make-mo lang/
```

To add a new language, copy `lang/ys-editor-scope-wrapper-zh_TW.po` to `lang/ys-editor-scope-wrapper-{locale}.po`, translate the `msgstr` lines, then compile.

## Development

Source structure:

```
ys-editor-scope-wrapper/
├── ys-editor-scope-wrapper.php   Main plugin file
├── includes/
│   ├── detector.php              Scanning + caching of candidate classes
│   └── settings.php              Settings page UI + save handler
├── assets/
│   └── js/editor-scope-wrapper.js   Client-side class applier
├── lang/                         Translations
├── readme.txt                    WordPress.org-style readme
├── README.md                     This file (English)
├── README.zh-TW.md               Chinese version
├── CHANGELOG.md                  English changelog
└── CHANGELOG.zh-TW.md            Chinese changelog
```

`.dev/build-zip.py` (local-only) packages a release zip into `outputs/ys-editor-scope-wrapper.zip` using Python `zipfile` (POSIX-safe paths).

## License

GPL-2.0-or-later
