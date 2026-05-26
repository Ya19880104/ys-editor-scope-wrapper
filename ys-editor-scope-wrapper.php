<?php
/**
 * Plugin Name: YS Editor Scope Wrapper
 * Plugin URI:  https://yangsheep.com.tw
 * Description: Apply CSS scope classes (e.g. <code>.v5-page</code>, <code>.alignfull</code>) to the Block Editor wrapper and iframe canvas, so wrapper-scoped CSS that only exists on the frontend also works inside the editor preview. Auto-detects candidate classes from your existing posts.
 * Version:     1.1.2
 * Author:      YANGSHEEP DESIGN
 * Author URI:  https://yangsheep.com.tw
 * Text Domain: ys-editor-scope-wrapper
 * Domain Path: /lang
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'YS_EDITOR_SCOPE_WRAPPER_VERSION', '1.1.2' );
define( 'YS_EDITOR_SCOPE_WRAPPER_FILE', __FILE__ );
define( 'YS_EDITOR_SCOPE_WRAPPER_DIR', plugin_dir_path( __FILE__ ) );
define( 'YS_EDITOR_SCOPE_WRAPPER_URL', plugin_dir_url( __FILE__ ) );
define( 'YS_EDITOR_SCOPE_WRAPPER_OPTION', 'ys_editor_scope_classes' );
define( 'YS_EDITOR_SCOPE_WRAPPER_DEFAULT', "v5-page\nalignfull" );
define( 'YS_EDITOR_SCOPE_WRAPPER_CACHE_KEY', 'ys_editor_scope_detected' );

require_once YS_EDITOR_SCOPE_WRAPPER_DIR . 'includes/detector.php';
require_once YS_EDITOR_SCOPE_WRAPPER_DIR . 'includes/settings.php';

/**
 * 載入翻譯檔。
 */
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain(
			'ys-editor-scope-wrapper',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/lang'
		);
	}
);

/**
 * Get sanitized active scope classes from option.
 *
 * @return array<int,string>
 */
function ys_editor_scope_wrapper_get_classes() {
	$raw     = get_option( YS_EDITOR_SCOPE_WRAPPER_OPTION, YS_EDITOR_SCOPE_WRAPPER_DEFAULT );
	$lines   = preg_split( '/[\r\n,]+/', (string) $raw );
	$classes = array();
	foreach ( (array) $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $line ) ) {
			$classes[] = $line;
		}
	}
	return array_values( array_unique( $classes ) );
}

/**
 * Enqueue Block Editor JS — excludes Site Editor / Widgets / Customizer.
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->base, array( 'site-editor', 'widgets', 'customize' ), true ) ) {
			return;
		}

		$classes = ys_editor_scope_wrapper_get_classes();
		if ( empty( $classes ) ) {
			return;
		}

		wp_register_script(
			'ys-editor-scope-wrapper',
			YS_EDITOR_SCOPE_WRAPPER_URL . 'assets/js/editor-scope-wrapper.js',
			array( 'wp-dom-ready' ),
			YS_EDITOR_SCOPE_WRAPPER_VERSION,
			true
		);

		wp_localize_script(
			'ys-editor-scope-wrapper',
			'YSEditorScopeWrapper',
			array(
				'classes' => $classes,
			)
		);

		wp_enqueue_script( 'ys-editor-scope-wrapper' );
	},
	20
);

/**
 * Add "Settings" link on plugins.php.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$url           = admin_url( 'options-general.php?page=ys-editor-scope-wrapper' );
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Settings', 'ys-editor-scope-wrapper' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
);

/**
 * Activation hook — write defaults if option not yet set.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( YS_EDITOR_SCOPE_WRAPPER_OPTION, false ) ) {
			update_option( YS_EDITOR_SCOPE_WRAPPER_OPTION, YS_EDITOR_SCOPE_WRAPPER_DEFAULT );
		}
	}
);

/**
 * Clear detection cache when posts are updated (so newly added classes show up).
 */
add_action( 'save_post', function () {
	delete_transient( YS_EDITOR_SCOPE_WRAPPER_CACHE_KEY );
} );
