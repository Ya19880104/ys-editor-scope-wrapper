/**
 * YS Editor Scope Wrapper — frontend script
 *
 * 把 PHP 提供的 scope class 套到：
 *  1) iframe canvas body（新版 Gutenberg / FSE 預設 iframe 模式）
 *  2) .editor-styles-wrapper（舊版或被 plugin 切換到 non-iframe 模式）
 *
 * 用 MutationObserver 處理 iframe lazy load 與編輯器重 mount。
 * 加 class 是冪等操作；若該頁 CSS 沒對應規則也無視覺副作用。
 */
( function () {
	'use strict';

	var settings        = window.YSEditorScopeWrapper || {};
	var TARGET_CLASSES  = Array.isArray( settings.classes ) ? settings.classes : [];

	if ( ! TARGET_CLASSES.length ) {
		return;
	}

	function applyToElement( el ) {
		if ( ! el || ! el.classList ) {
			return;
		}
		TARGET_CLASSES.forEach( function ( cls ) {
			if ( ! el.classList.contains( cls ) ) {
				el.classList.add( cls );
			}
		} );
	}

	function tryApply() {
		// 1) iframe canvas（新版 Gutenberg / FSE）
		var iframes = document.querySelectorAll( 'iframe[name="editor-canvas"]' );
		iframes.forEach( function ( iframe ) {
			try {
				var doc = iframe.contentDocument || iframe.contentWindow.document;
				if ( doc && doc.body ) {
					applyToElement( doc.body );
				}
			} catch ( e ) {
				/* cross-origin / 尚未 ready，忽略 */
			}
		} );

		// 2) non-iframe fallback wrapper
		var wrappers = document.querySelectorAll( '.editor-styles-wrapper' );
		wrappers.forEach( applyToElement );
	}

	// 立即試一次
	tryApply();

	// 觀察 DOM 變動（iframe 載入、編輯器重 mount、頁面切換）
	var observer = new MutationObserver( function () {
		tryApply();
	} );
	observer.observe( document.body, { childList: true, subtree: true } );

	// iframe load 事件也觸發
	document.addEventListener(
		'load',
		function ( e ) {
			if ( e.target && e.target.tagName === 'IFRAME' ) {
				tryApply();
			}
		},
		true
	);

	// 給 wp.domReady 一次保險
	if ( window.wp && window.wp.domReady ) {
		window.wp.domReady( tryApply );
	}
} )();
