# Changelog

本檔案紀錄 YS Editor Scope Wrapper 的版本變更。

格式參考 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)，版本管理採用 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

## [1.1.2] - 2026-05-26

### Fixed

- **「設定已儲存。」notice 顯示兩次** — 改用 PRG (Post-Redirect-Get) pattern：save handler 直接 `wp_safe_redirect` 到 `?ys_scope_message=saved`，render 端讀 query 印一次 notice。同步移除 `add_settings_error` + `settings_errors()`。
- **textarea placeholder `&#10;` 沒解析成換行** — HTML placeholder 屬性本來就不支援多行（HTML spec），改成單行範例字串 `e.g. my-page-design`。

## [1.1.1] - 2026-05-26

### Fixed

- **偵測雜訊太多**：原本掃所有 `dynamicGClasses[].value` 與 `<style>` 內所有 `.classname {` selector，會把 sub-element class（.btn、.hero__title、.work__media...）一起列出，user 看了反而更困惑
- 現在只抓「**根作用域 class**」：Style Manager block 的 `dynamicGClasses[0]`（第一個）+ wp:html `<style>` 的**第一個** selector
- Source 標籤改為 `stylemanager-root` / `inline-style-root` 更精確反映用途
- `alignfull` / `alignwide` 加上 `sample_post` 記錄

## [1.1.0] - 2026-05-26

### Added

- **自動偵測候選 class**：掃描所有已發布的 page/post，找出 GreenShift Style Manager 的 `dynamicGClasses` value、`wp:html` 內 `<style>` 區塊的最外層 selector、以及 WordPress 核心的 `alignfull` / `alignwide`
- **Checkbox UI**：使用者直接勾選要套用的 class，不用手寫
- **Source 標籤**：每個候選顯示來源（Style Manager / Inline style / WordPress core）與出現頁數
- **「立即重新偵測」按鈕**：手動清掉 transient cache 重新掃描
- **save_post hook 自動清 cache**：新增/編輯文章後候選清單自動更新
- **完整 i18n**：所有字串走 `__()`，英文為來源，附 `lang/ys-editor-scope-wrapper-zh_TW.po`
- **`includes/detector.php`** 新檔案，職責分離

### Changed

- 設定 UI 從「單純 textarea」改為「checkbox 候選 + textarea 進階」雙層
- 儲存採自訂 POST handler（兩個欄位合併為一個 option），非 Settings API
- 預設字串改英文（textdomain `ys-editor-scope-wrapper`），中文走 `.mo` 翻譯

### Migration notes

- v1.0.0 的 option key `ys_editor_scope_classes` 仍沿用，**升級不需重新設定**
- 升級後候選清單會自動列出當前使用的 class，勾選狀態反映現有 option

## [1.0.0] - 2026-05-26

### Added

- 初版發布
- 設定頁可維護 class 清單
- JS 同時支援 iframe canvas 與 `.editor-styles-wrapper`
- MutationObserver 處理 iframe lazy load
- register_activation_hook 寫入預設值（v5-page、alignfull）

### Background

從 mu-plugin `ys-editor-v5page-wrapper.php`（dev-ysdesign 案例）升級成正式外掛。
