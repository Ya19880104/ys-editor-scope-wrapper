# YS Editor Scope Wrapper（繁體中文）

> English version: [README.md](README.md)

把指定的 CSS class 套到 WordPress Block Editor 的 wrapper 與 iframe canvas，讓「只在前台才有的外層 wrapper class」對應的 CSS 在後台編輯時也能生效。

## 解決什麼問題

很多 page builder 在頁面 CSS 中使用 `.scope-class .xxx { ... }` 形式：

- **前台**：page builder（GreenShift / GL Page Builder 等）在 `the_content` 渲染時於外層自動加 `<div class="scope-class">`，所以 `.scope-class .xxx` 規則都能 match。
- **後台 Block Editor**：沒有那層 wrapper。`.editor-styles-wrapper` 上沒有 `scope-class`，所有 wrapper-scoped 規則全部 dead — 編輯器只剩 raw 文字、字型/顏色/layout 全失效。

這個外掛就是補上這個缺口。

## 它做了什麼

每次 Block Editor 載入時：

1. 從 `ys_editor_scope_classes` 選項讀出設定的 class 清單
2. 把每個 class 加到 `.editor-styles-wrapper`（以及新版 Gutenberg 的 `iframe[name="editor-canvas"]` body）
3. 透過 `MutationObserver` 處理 iframe lazy load 與編輯器重 mount

加上去的 class **只存在於 DOM**，不會寫入 `post_content`。

## 自動偵測候選

設定頁會自動掃描你已發佈的文章，列出可能的候選 class 讓你勾選：

| 來源 | 抓什麼 |
|------|--------|
| Style Manager 根作用域 | 每個 `<!-- wp:greenshift-blocks/element ... isVariation:"stylemanager" -->` 區塊的 `dynamicGClasses[].value` **第一項** |
| 內嵌 `<style>` 根選擇器 | 每個 `<!-- wp:html -->` 中 `<style>` 區塊內的**第一個** `.class-name {` 選擇器 |
| WordPress 核心 | `alignfull`、`alignwide` — 一律列出 |

偵測器刻意忽略深層元素 class（`.btn`、`.hero__title` 等），只保留可能是「外層 wrapper」的候選。如果掃描器漏掉的，可在表格下方「自訂 class」textarea 手動加。

任何文章儲存時，偵測快取會自動清除。

## 安裝

1. 從 [最新 Release](https://github.com/Ya19880104/ys-editor-scope-wrapper/releases/latest) 下載 `ys-editor-scope-wrapper.zip`
2. WordPress 後台 → **外掛 → 安裝外掛 → 上傳外掛** → 選擇 zip
3. 啟用
4. 進入 **設定 → YS 編輯器作用域** 設定 class 清單

預設值：`v5-page`、`alignfull`（首次啟用自動寫入）。

## 設定 UI

| 欄位 | 用途 |
|------|------|
| 自動偵測到的候選 | 文章中找到的 class 清單（checkbox）。勾選要套用的項目即可。 |
| 立即重新偵測 | 清除偵測快取重新掃描。大量匯入內容後使用。 |
| 自訂 class | 一行一個 class（不含前面的 `.`），給掃描器抓不到的特例使用。 |

儲存時：

- 勾選的 checkbox + textarea 中所有合法的 class 都會合併
- 每個 class 都會 sanitize — 只接受 `a-z` `A-Z` `0-9` `_` `-`，且開頭必須是字母或 `_`
- 去除重複後，以換行分隔字串存進 `ys_editor_scope_classes`
- 採 PRG（Post-Redirect-Get）pattern 避免 notice 重複顯示與重新整理重送

## 外掛 metadata

| | |
|---|---|
| Slug | `ys-editor-scope-wrapper` |
| Function 前綴 | `ys_editor_scope_wrapper_` |
| Class / 常數前綴 | `YS_EDITOR_SCOPE_WRAPPER_` |
| Option key | `ys_editor_scope_classes` |
| Transient cache key | `ys_editor_scope_detected` |
| Text domain | `ys-editor-scope-wrapper` |
| 版本 | 1.1.2 |

## 適用範圍與限制

- 只作用於文章 / 頁面的 Block Editor
- **不**作用於完整網站編輯器（Site Editor）、小工具、自訂器（避免污染 FSE）
- 加上去的 class 僅存在於 DOM，不寫入 `post_content`
- 若 page builder 在新版改寫了 wrapper 機制，可能需要更新 class 清單。重新偵測會自動列出新候選。

## i18n

英文為來源語言，附完整繁體中文（`zh_TW`）翻譯於 `lang/` 目錄。

修改 `.po` 後重新編譯 `.mo`：

```bash
wp i18n make-mo lang/
```

新增其他語言：複製 `lang/ys-editor-scope-wrapper-zh_TW.po` 到 `lang/ys-editor-scope-wrapper-{locale}.po`，翻譯 `msgstr` 後編譯。

## 開發資訊

原始碼結構：

```
ys-editor-scope-wrapper/
├── ys-editor-scope-wrapper.php   主檔
├── includes/
│   ├── detector.php              候選 class 掃描與快取
│   └── settings.php              設定頁 UI + 儲存處理
├── assets/
│   └── js/editor-scope-wrapper.js   前端套 class 邏輯
├── lang/                         翻譯檔
├── readme.txt                    WordPress.org 標準 readme
├── README.md                     英文說明
├── README.zh-TW.md               本檔
├── CHANGELOG.md                  英文版更紀錄
└── CHANGELOG.zh-TW.md            繁體中文版更紀錄
```

`.dev/build-zip.py`（本地保留、不入公開 repo）用 Python `zipfile` 打包 release zip，輸出到 `outputs/ys-editor-scope-wrapper.zip`（POSIX 路徑、Linux/WP 相容）。

## 授權

GPL-2.0-or-later
