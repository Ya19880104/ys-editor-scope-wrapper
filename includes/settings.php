<?php
/**
 * YS Editor Scope Wrapper — Settings page (Settings → YS Editor Scope).
 *
 * UI is two-tier:
 *   1) Detected candidates (checkboxes) — auto-found from post_content.
 *   2) Custom classes (textarea) — for advanced users / classes the scanner missed.
 *
 * On save: union(detected_checked, custom_textarea) → sanitized → saved as newline-separated string.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register menu item.
 */
add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'YS Editor Scope Wrapper', 'ys-editor-scope-wrapper' ),
			__( 'YS Editor Scope', 'ys-editor-scope-wrapper' ),
			'manage_options',
			'ys-editor-scope-wrapper',
			'ys_editor_scope_wrapper_render_settings_page'
		);
	}
);

/**
 * Handle save (POST). We don't use the standard Settings API because the UI
 * has two fields (checkboxes + textarea) that need to be merged into one option.
 */
add_action(
	'admin_init',
	function () {
		if ( ! isset( $_POST['ys_editor_scope_save'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'ys_editor_scope_save', 'ys_editor_scope_nonce' );

		$detected_checked = isset( $_POST['ys_scope_detected'] ) && is_array( $_POST['ys_scope_detected'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['ys_scope_detected'] ) )
			: array();

		$custom_raw = isset( $_POST['ys_scope_custom'] )
			? sanitize_textarea_field( wp_unslash( $_POST['ys_scope_custom'] ) )
			: '';

		$custom_classes = preg_split( '/[\r\n,]+/', $custom_raw );
		$merged         = array_merge( $detected_checked, (array) $custom_classes );

		$clean = array();
		foreach ( $merged as $cls ) {
			$cls = trim( (string) $cls );
			if ( '' === $cls ) {
				continue;
			}
			if ( preg_match( '/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $cls ) ) {
				$clean[] = $cls;
			}
		}
		$clean = array_values( array_unique( $clean ) );

		update_option( YS_EDITOR_SCOPE_WRAPPER_OPTION, implode( "\n", $clean ) );

		// PRG (Post-Redirect-Get) pattern — avoids duplicate notice + refresh re-submit.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'ys-editor-scope-wrapper',
					'ys_scope_message' => 'saved',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
);

/**
 * Handle "Re-detect" action — clears transient cache.
 */
add_action(
	'admin_init',
	function () {
		if ( ! isset( $_GET['ys_scope_action'] ) || 'redetect' !== $_GET['ys_scope_action'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'ys_scope_redetect' );

		delete_transient( YS_EDITOR_SCOPE_WRAPPER_CACHE_KEY );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'ys-editor-scope-wrapper',
					'ys_scope_message' => 'redetected',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
);

/**
 * Render the settings page.
 */
function ys_editor_scope_wrapper_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$active   = ys_editor_scope_wrapper_get_classes();
	$detected = ys_editor_scope_wrapper_detect_classes();

	// Classes the user typed manually but the scanner didn't find (so they don't disappear from textarea).
	$detected_names = array_keys( $detected );
	$custom_only    = array_values( array_diff( $active, $detected_names ) );

	if ( isset( $_GET['ys_scope_message'] ) ) {
		$msg = sanitize_key( wp_unslash( $_GET['ys_scope_message'] ) );
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Settings saved.', 'ys-editor-scope-wrapper' ) .
				'</p></div>';
		} elseif ( 'redetected' === $msg ) {
			echo '<div class="notice notice-info is-dismissible"><p>' .
				esc_html__( 'Re-detection complete.', 'ys-editor-scope-wrapper' ) .
				'</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'YS Editor Scope Wrapper', 'ys-editor-scope-wrapper' ); ?></h1>

		<div class="notice notice-info inline" style="margin:12px 0;">
			<p>
				<?php
				echo wp_kses_post(
					__(
						'This plugin applies the selected CSS classes to the Block Editor wrapper (and iframe canvas body), so wrapper-scoped CSS that only exists on the frontend also works inside the editor preview.',
						'ys-editor-scope-wrapper'
					)
				);
				?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Currently active:', 'ys-editor-scope-wrapper' ); ?></strong>
				<?php
				if ( empty( $active ) ) {
					echo '<em>' . esc_html__( '(none)', 'ys-editor-scope-wrapper' ) . '</em>';
				} else {
					foreach ( $active as $cls ) {
						echo '<code style="margin-right:6px;">.' . esc_html( $cls ) . '</code>';
					}
				}
				?>
			</p>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'ys_editor_scope_save', 'ys_editor_scope_nonce' ); ?>
			<input type="hidden" name="ys_editor_scope_save" value="1" />

			<h2 style="margin-top:24px;">
				<?php esc_html_e( 'Detected candidates', 'ys-editor-scope-wrapper' ); ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=ys-editor-scope-wrapper&ys_scope_action=redetect' ), 'ys_scope_redetect' ) ); ?>"
					class="button button-secondary" style="margin-left:12px;font-size:13px;vertical-align:middle;">
					<?php esc_html_e( 'Re-detect now', 'ys-editor-scope-wrapper' ); ?>
				</a>
			</h2>
			<p class="description">
				<?php
				esc_html_e(
					'These classes were auto-detected from your existing posts and pages. Tick the ones you want applied to the editor preview.',
					'ys-editor-scope-wrapper'
				);
				?>
			</p>

			<?php if ( empty( $detected ) ) : ?>
				<p><em><?php esc_html_e( 'No candidates detected. Use the "Custom classes" field below.', 'ys-editor-scope-wrapper' ); ?></em></p>
			<?php else : ?>
				<table class="wp-list-table widefat striped" style="max-width:760px;">
					<thead>
						<tr>
							<th style="width:60px;"><?php esc_html_e( 'Use', 'ys-editor-scope-wrapper' ); ?></th>
							<th><?php esc_html_e( 'Class', 'ys-editor-scope-wrapper' ); ?></th>
							<th><?php esc_html_e( 'Source', 'ys-editor-scope-wrapper' ); ?></th>
							<th style="width:120px;"><?php esc_html_e( 'Used in posts', 'ys-editor-scope-wrapper' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $detected as $cls => $info ) : ?>
							<?php
								$is_active = in_array( $cls, $active, true );
								$is_core   = in_array( 'wordpress-core', $info['sources'], true );
							?>
							<tr>
								<td>
									<input type="checkbox"
										id="ys_scope_<?php echo esc_attr( $cls ); ?>"
										name="ys_scope_detected[]"
										value="<?php echo esc_attr( $cls ); ?>"
										<?php checked( $is_active ); ?> />
								</td>
								<td>
									<label for="ys_scope_<?php echo esc_attr( $cls ); ?>" style="cursor:pointer;">
										<code>.<?php echo esc_html( $cls ); ?></code>
									</label>
								</td>
								<td>
									<?php
									$labels = array_map( 'ys_editor_scope_wrapper_format_source', $info['sources'] );
									echo esc_html( implode( ', ', $labels ) );
									?>
								</td>
								<td>
									<?php
									if ( $is_core && 0 === $info['count'] ) {
										echo '<em>' . esc_html__( '(built-in)', 'ys-editor-scope-wrapper' ) . '</em>';
									} else {
										printf(
											esc_html(
												/* translators: %d: number of posts the class appears in */
												_n( '%d post', '%d posts', $info['count'], 'ys-editor-scope-wrapper' )
											),
											(int) $info['count']
										);
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2 style="margin-top:32px;"><?php esc_html_e( 'Custom classes (advanced)', 'ys-editor-scope-wrapper' ); ?></h2>
			<p class="description">
				<?php
				esc_html_e(
					'Add any extra classes the scanner missed. One per line, without the leading dot.',
					'ys-editor-scope-wrapper'
				);
				?>
			</p>
			<textarea
				id="ys_scope_custom"
				name="ys_scope_custom"
				rows="4"
				cols="40"
				class="large-text code"
				placeholder="<?php echo esc_attr__( 'e.g. my-page-design', 'ys-editor-scope-wrapper' ); ?>"
			><?php echo esc_textarea( implode( "\n", $custom_only ) ); ?></textarea>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save changes', 'ys-editor-scope-wrapper' ); ?>
				</button>
			</p>
		</form>

		<hr />

		<h2><?php esc_html_e( 'When this plugin helps', 'ys-editor-scope-wrapper' ); ?></h2>
		<ul style="list-style:disc; padding-left:1.5em;">
			<li><?php esc_html_e( 'Block builders (GreenShift / GL Page Builder) that inject a wrapper class on the frontend only', 'ys-editor-scope-wrapper' ); ?></li>
			<li><?php esc_html_e( 'Pages where CSS is scoped under .your-page-name to avoid polluting the global stylesheet', 'ys-editor-scope-wrapper' ); ?></li>
			<li><?php esc_html_e( 'Themes that switch design via a body class which the editor doesn\'t replicate', 'ys-editor-scope-wrapper' ); ?></li>
		</ul>
	</div>
	<?php
}
