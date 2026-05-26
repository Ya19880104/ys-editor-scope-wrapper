<?php
/**
 * YS Editor Scope Wrapper — Detector
 *
 * Scans post_content across the site to find candidate wrapper classes:
 *  - GreenShift Style Manager `dynamicGClasses[].value`
 *  - Top-level CSS selectors inside `<!-- wp:html -->` <style> blocks
 *  - WordPress core wrapper classes (alignfull / alignwide)
 *
 * Results are cached via transient and refreshed automatically on save_post.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detect candidate scope classes used in published posts/pages.
 *
 * @param bool $force_refresh Skip the cache and re-scan.
 * @return array<string,array{count:int,sources:array<int,string>,sample_post:int}>
 *               Keyed by class name. `count` = how many posts reference it.
 */
function ys_editor_scope_wrapper_detect_classes( $force_refresh = false ) {
	if ( ! $force_refresh ) {
		$cached = get_transient( YS_EDITOR_SCOPE_WRAPPER_CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	global $wpdb;

	// Only scan posts that actually look interesting (cheap LIKE filter first).
	$rows = $wpdb->get_results(
		"SELECT ID, post_content FROM {$wpdb->posts}
		 WHERE post_status = 'publish'
		   AND post_type IN ('page', 'post')
		   AND (
		     post_content LIKE '%dynamicGClasses%'
		     OR post_content LIKE '%<style%'
		     OR post_content LIKE '%alignfull%'
		   )
		 LIMIT 200"
	);

	$detected = array();

	// WordPress core always-available wrappers (built-in).
	$detected['alignfull'] = array(
		'count'       => 0,
		'sources'     => array( 'wordpress-core' ),
		'sample_post' => 0,
	);
	$detected['alignwide'] = array(
		'count'       => 0,
		'sources'     => array( 'wordpress-core' ),
		'sample_post' => 0,
	);

	foreach ( (array) $rows as $row ) {
		$content   = (string) $row->post_content;
		$post_id   = (int) $row->ID;
		$seen_here = array();

		// (A) GreenShift Style Manager block — only the FIRST dynamicGClasses entry per block.
		//     That first entry is the page-level outer wrapper (e.g. v5-page).
		//     Subsequent entries are inner element classes (.btn, .hero__title, ...) — NOT wrappers.
		if ( preg_match_all(
			'/<!--\s*wp:greenshift-blocks\/element\s+\{[^}]*?"isVariation"\s*:\s*"stylemanager"[^}]*?"dynamicGClasses"\s*:\s*\[\s*\{\s*"value"\s*:\s*"([a-zA-Z_][a-zA-Z0-9_-]{1,63})"/s',
			$content,
			$matches
		) ) {
			foreach ( $matches[1] as $cls ) {
				$seen_here[ $cls ] = 'stylemanager-root';
			}
		}

		// (B) wp:html block — find ONLY the first top-level selector `.class-name {`.
		//     That's the outermost scope class (e.g. .v5-page in dev-ysdesign).
		if ( preg_match_all( '/<!--\s*wp:html\s*-->\s*<style[^>]*>(.*?)<\/style>/s', $content, $style_blocks ) ) {
			foreach ( (array) $style_blocks[1] as $css ) {
				// Strip CSS comments so they don't hide the first selector.
				$css_no_comments = preg_replace( '#/\*.*?\*/#s', '', $css );
				// First selector match only.
				if ( preg_match( '/\.([a-zA-Z_][a-zA-Z0-9_-]{2,63})\s*\{/', (string) $css_no_comments, $first_match ) ) {
					$cls = $first_match[1];
					if ( ! in_array( $cls, array( 'container', 'wrap', 'wrapper', 'row', 'col', 'btn', 'flex' ), true ) ) {
						if ( ! isset( $seen_here[ $cls ] ) ) {
							$seen_here[ $cls ] = 'inline-style-root';
						}
					}
				}
			}
		}

		// (C) alignfull / alignwide presence in HTML.
		if ( strpos( $content, 'alignfull' ) !== false ) {
			$detected['alignfull']['count']++;
			if ( 0 === $detected['alignfull']['sample_post'] ) {
				$detected['alignfull']['sample_post'] = $post_id;
			}
		}
		if ( strpos( $content, 'alignwide' ) !== false ) {
			$detected['alignwide']['count']++;
			if ( 0 === $detected['alignwide']['sample_post'] ) {
				$detected['alignwide']['sample_post'] = $post_id;
			}
		}

		// Aggregate per-post seen classes.
		foreach ( $seen_here as $cls => $source ) {
			if ( ! isset( $detected[ $cls ] ) ) {
				$detected[ $cls ] = array(
					'count'       => 0,
					'sources'     => array(),
					'sample_post' => $post_id,
				);
			}
			$detected[ $cls ]['count']++;
			if ( ! in_array( $source, $detected[ $cls ]['sources'], true ) ) {
				$detected[ $cls ]['sources'][] = $source;
			}
		}
	}

	// Sort: classes with higher count first; alignfull/alignwide pinned to top.
	uksort(
		$detected,
		function ( $a, $b ) use ( $detected ) {
			// Pin core to top.
			$core = array( 'alignfull', 'alignwide' );
			$a_is_core = in_array( $a, $core, true );
			$b_is_core = in_array( $b, $core, true );
			if ( $a_is_core && ! $b_is_core ) {
				return -1;
			}
			if ( ! $a_is_core && $b_is_core ) {
				return 1;
			}
			$count_diff = $detected[ $b ]['count'] - $detected[ $a ]['count'];
			if ( 0 !== $count_diff ) {
				return $count_diff;
			}
			return strcmp( $a, $b );
		}
	);

	set_transient( YS_EDITOR_SCOPE_WRAPPER_CACHE_KEY, $detected, HOUR_IN_SECONDS );

	return $detected;
}

/**
 * Format source key into a human-readable label.
 *
 * @param string $source Source key from detector.
 * @return string Translated label.
 */
function ys_editor_scope_wrapper_format_source( $source ) {
	switch ( $source ) {
		case 'stylemanager-root':
			return __( 'Style Manager root class', 'ys-editor-scope-wrapper' );
		case 'inline-style-root':
			return __( 'Inline <style> root selector', 'ys-editor-scope-wrapper' );
		case 'wordpress-core':
			return __( 'WordPress core', 'ys-editor-scope-wrapper' );
		default:
			return $source;
	}
}
