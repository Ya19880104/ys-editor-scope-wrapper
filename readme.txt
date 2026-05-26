=== YS Editor Scope Wrapper ===
Contributors: yangsheepdesign
Tags: block-editor, gutenberg, editor-styles, page-builder, css
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

把指定 CSS class 套到 Block Editor 的 wrapper / iframe canvas，讓前台才有的外層 wrapper class 在後台預覽也能生效。

== Description ==

當頁面 CSS 寫成 `.scope-class .xxx { ... }` 形式 — 前台靠 page builder（GreenShift / GL Page Builder 等）在 the_content 外層動態注入 `.scope-class`，但 Block Editor 沒有那層包裝，所有 wrapper-scoped CSS 在後台都 match 不到，編輯器只剩 raw 文字、layout 全失效。

此外掛在 Block Editor 中：

* 把使用者指定的 class 套到 `.editor-styles-wrapper`
* 同時套到 `iframe[name="editor-canvas"]` 的 body（新版 Gutenberg / FSE）
* 用 MutationObserver 處理 iframe lazy load 與編輯器重 mount

無副作用：若該頁 CSS 沒有對應 wrapper-scoped 規則，加 class 也不影響任何視覺。

= 使用方式 =

1. 啟用外掛
2. 進入「設定 → YS Editor Scope」
3. 在 textarea 一行一個填入要套的 class（不含 `.`），例如 `v5-page`、`alignfull`
4. 儲存設定，重新整理編輯器即可看到效果

== Installation ==

1. 將 `ys-editor-scope-wrapper` 目錄上傳到 `/wp-content/plugins/`
2. 在「外掛」頁面啟用
3. 進入「設定 → YS Editor Scope」設定 scope class 清單（預設：`v5-page`、`alignfull`）

== Frequently Asked Questions ==

= 會影響前台嗎？ =

不會。本外掛只在 Block Editor (`enqueue_block_editor_assets`) 載入 JS，前台完全不執行。

= 加的 class 會被存到 post 內容嗎？ =

不會。class 只在編輯器 DOM 即時加上，不寫入 post_content。

= 設定頁找不到？ =

請到「設定 → YS Editor Scope」，或在外掛列表頁點該外掛的「設定」連結。

== Changelog ==

= 1.0.0 =
* 初版發布
* 設定頁可維護 class 清單
* 同時支援 iframe 與 non-iframe Block Editor 模式
* register_activation_hook 寫入預設值（v5-page、alignfull）
