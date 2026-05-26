# YS Editor Scope Wrapper

> 把指定 CSS class 套到 Block Editor 的 wrapper / iframe canvas，讓「前台才有的外層 wrapper class」在後台預覽也能 match，使編輯器看得到實際視覺。

## 解決什麼問題

當頁面 CSS 寫成 `.scope-class .xxx { ... }` 形式：

- **前台**：page builder（GreenShift / GL Page Builder 等）會在 `the_content` 渲染時，於外層自動加上 `<div class="scope-class">`，所有 wrapper-scoped CSS 都能 match。
- **後台 Block Editor**：沒有那層包裝，`.editor-styles-wrapper` 上沒有 `.scope-class`，所有 wrapper-scoped CSS 全部 match 不到 → **編輯器只剩 raw 文字、字型 / 顏色 / layout 全失效**。

此外掛在 Block Editor 載入時：

1. 從 PHP option 讀取 class 清單
2. 透過 inline JS 把每個 class 加到 `.editor-styles-wrapper`
3. 同時也加到 `iframe[name="editor-canvas"]` 的 `body`（新版 Gutenberg / FSE）
4. 用 `MutationObserver` 處理 iframe lazy load 與編輯器重 mount

## 使用情境

- GreenShift / GL Page Builder 站，post 內以 Style Manager 的 `dynamicGClasses` 註冊 scope class（前台 plugin 在外層 wrapper 自動套）
- 主題前台用 body class 切換設計風格（後台 body 沒這 class）
- 任何「頁面 CSS 用 `.page-x` 作為 scope」的設計模式

## 安裝

複製目錄到 `wp-content/plugins/`，啟用即可。設定頁路徑：**設定 → YS Editor Scope**。

預設套用 `.v5-page` 與 `.alignfull`（沿用 dev-ysdesign 案例的 mu-plugin 行為）。

## 設定

進 **設定 → YS Editor Scope**，一行一個 class（不含 `.`）：

```
v5-page
alignfull
page-design
```

僅接受合法 CSS class 字元（`a-z` `A-Z` `0-9` `_` `-`，開頭為字母或 `_`）。其餘輸入會被 sanitize 過濾掉。

## 開發資訊

| 欄位 | 值 |
|------|-----|
| Slug | `ys-editor-scope-wrapper` |
| Main class prefix | `YS` |
| Function prefix | `ys_editor_scope_wrapper_` |
| Option key | `ys_editor_scope_classes` |
| Text domain | `ys-editor-scope-wrapper` |
| Version | 1.0.0 |

## 限制

- 僅作用於 post/page 的 Block Editor。**不**作用於 Site Editor / Widgets / Customizer（為避免污染全站 FSE）。
- 加 class 是 DOM-only 操作，不寫入 `post_content`。
- 若 page builder 升級改變外層 wrapper 機制，class 名稱可能要跟著更新。

## 授權

GPL-2.0-or-later
