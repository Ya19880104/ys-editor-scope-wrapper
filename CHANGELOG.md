# Changelog

> 中文版本: [CHANGELOG.zh-TW.md](CHANGELOG.zh-TW.md)

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning follows [Semantic Versioning](https://semver.org/).

## [1.1.2] - 2026-05-26

### Fixed

- **"Settings saved." notice appearing twice** — switched to PRG (Post-Redirect-Get) pattern: the save handler now `wp_safe_redirect`s to `?ys_scope_message=saved` and the render side reads the query string to print the notice exactly once. Removed `add_settings_error` + `settings_errors()`.
- **textarea placeholder `&#10;` not parsed as newline** — HTML `placeholder` attributes do not support multi-line per spec; replaced with a single-line example string `e.g. my-page-design`.

## [1.1.1] - 2026-05-26

### Fixed

- **Detection too noisy** — v1.1.0 scanned every `dynamicGClasses[].value` and every `.classname {` selector inside `<style>` blocks, so sub-element classes (`.btn`, `.hero__title`, `.work__media`, ...) flooded the candidates list. Users found it harder, not easier, to choose.
- Now extracts **root scope classes only**: the first `dynamicGClasses` entry of each Style Manager block + the first selector of each `wp:html <style>` block.
- Source labels updated to `stylemanager-root` / `inline-style-root` to better reflect intent.
- `alignfull` / `alignwide` now record a `sample_post` reference for traceability.

## [1.1.0] - 2026-05-26

### Added

- **Auto-detection of candidate classes**: scans all published pages/posts for GreenShift Style Manager `dynamicGClasses`, top-level selectors inside `wp:html <style>` blocks, and the WordPress core `alignfull` / `alignwide`.
- **Checkbox UI**: tick to enable rather than typing class names by hand.
- **Source labels**: each candidate shows where it was detected (Style Manager / inline style / WordPress core) and how many posts it appears in.
- **"Re-detect now"** button: manually clears the transient cache and rescans.
- **save_post hook**: auto-clears the detection cache so newly added classes show up next time you visit Settings.
- **Full i18n**: all strings wrapped in `__()` with English as source; ships with `lang/ys-editor-scope-wrapper-zh_TW.po`.
- New `includes/detector.php` for separation of concerns.

### Changed

- Settings UI went from "single textarea" to "checkbox candidates + advanced textarea".
- Save handler now custom POST (not Settings API) to merge the two input modes into one option.
- Source strings switched to English (text domain `ys-editor-scope-wrapper`); Chinese loads via `.mo`.

### Migration notes

- v1.0.0's `ys_editor_scope_classes` option key is preserved — **upgrading requires no reconfiguration**.
- After upgrade, the candidates list will show your currently active classes pre-ticked.

## [1.0.0] - 2026-05-26

### Added

- First release.
- Settings page lets you maintain the class list.
- JS supports both iframe canvas and `.editor-styles-wrapper` modes of the Block Editor.
- `MutationObserver` handles iframe lazy load.
- `register_activation_hook` writes defaults (`v5-page`, `alignfull`).

### Background

Upgraded from a mu-plugin (`ys-editor-v5page-wrapper.php`, dev-ysdesign case) into a full plugin.
