<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Fusion_User_Roles_Admin
 *
 * Handles the admin functionality.
 *
 * @since 1.2.0
 */
class WP_Fusion_User_Roles_Admin {

	/**
	 * Constructs a new instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

	}
	/**
	 * Register admin menu
	 *
	 * @return void
	 */

	public function admin_menu() {

		$id = add_submenu_page(
			'options-general.php',
			__( 'WP Fusion - User Roles', 'wp-fusion-user-roles' ),
			__( 'WP Fusion Roles', 'wp-fusion-user-roles' ),
			'manage_options',
			'wpf-roles-settings',
			array( $this, 'render_admin_menu' ),
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );

	}


	/**
	 * Load the styles on the settings page.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );

	}

	/**
	 * Render the admin settings.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_menu() {

		// Save settings.
		if ( isset( $_POST['wpf_roles_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_roles_settings_nonce'], 'wpf_roles_settings' ) ) {
			update_option( 'wpf_roles_settings', wpf_clean( $_POST['wpf-settings'] ) );
			echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
		}

		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'WP Fusion Roles', 'wp-fusion-user-roles' ); ?></h2>

			<form id="wpf-roles-settings" action="" method="post">
				<?php wp_nonce_field( 'wpf_roles_settings', 'wpf_roles_settings_nonce' ); ?>
				<input type="hidden" name="action" value="update">


				<?php global $wp_roles; ?>

				<?php $all_roles      = $wp_roles->roles; ?>
				<?php $editable_roles = apply_filters( 'editable_roles', $all_roles ); ?>

				<?php

				$settings = get_option( 'wpf_roles_settings', array() );

				if ( empty( $settings ) ) {
					$settings = array();
				}

				?>

				<table class="table table-hover wpf-settings-table">
					<thead>
					<tr>
						<th>
							<?php esc_html_e( 'Role', 'wp-fusion-user-roles' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Apply tags when role is granted', 'wp-fusion-user-roles' ); ?>
							<p class="description" style="font-weight: 500">
								<?php esc_html_e( 'Any tags specified here will be applied when the role is given to a user. They will not be removed later.', 'wp-fusion-user-roles' ); ?>
							</p>
						</th>
						<th>
							<?php esc_html_e( 'Link with tag', 'wp-fusion-user-roles' ); ?>
							<p class="description" style="font-weight: 500">
								<?php esc_html_e( 'You can select a single tag to become "linked" with the role. When the tag is applied, the role will be granted. When the tag is removed, the role will be removed.', 'wp-fusion-user-roles' ); ?>
							</p>
						</th>
					</tr>
					</thead>
					<tbody>

					<?php foreach ( $editable_roles as $slug => $role ) : ?>

						<?php

						if ( ! isset( $settings[ $slug ]['apply_tags'] ) ) {
							$settings[ $slug ]['apply_tags'] = array();
						}

						if ( ! isset( $settings[ $slug ]['tag_link'] ) ) {
							$settings[ $slug ]['tag_link'] = array();
						}
						?>

						<tr
							<?php if ( ! empty( array_filter( $settings[ $slug ] ) ) ) {
								echo ' class="success" ';
							} ?>
						>
							<td><label><?php echo $role['name']; ?></label></td>

							<td>
								<?php

								$args = array(
									'setting'   => $settings[ $slug ]['apply_tags'],
									'meta_name' => "wpf-settings[{$slug}][apply_tags]",
								);

								wpf_render_tag_multiselect( $args );

								?>

							</td>
							<td id="wpf-tags-td">
								<?php

								$args = array(
									'setting'   => $settings[ $slug ]['tag_link'],
									'meta_name' => "wpf-settings[{$slug}][tag_link]",
									'limit'     => 1,
								);

								wpf_render_tag_multiselect( $args );

								?>

							</td>
						</tr>

					<?php endforeach; ?>

					</tbody>

				</table>

				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes"/>
				</p>

			</form>

		</div>

		<?php

	}
}
