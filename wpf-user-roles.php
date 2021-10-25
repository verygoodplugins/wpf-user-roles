<?php

/*
Plugin Name: WP Fusion - User Roles
Description: Allows linking a tag with a WordPress user role to automatically set roles when tags are modified, as well as applying tags based on user role changes
Plugin URI: https://verygoodplugins.com/
Version: 1.2
Author: Very Good Plugins
Author URI: https://verygoodplugins.com/
*/


/**
 * Triggered when tags are modified, updates user roles
 *
 * @return void
 */

function wpf_user_roles_tags_modified( $user_id, $user_tags ) {

	// Don't run for admins
	if ( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'wpf_roles_settings', array() );

	if ( empty( $settings ) ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );

	remove_action( 'profile_update', array( wp_fusion()->user, 'profile_update' ), 10, 2 );

	foreach ( $settings as $role_slug => $setting ) {

		if ( empty( $setting['tag_link'] ) ) {
			continue;
		}

		$result = array_intersect( $user_tags, $setting['tag_link'] );

		if ( ! empty( $result ) && ! in_array( $role_slug, $user->roles ) ) {

			wp_fusion()->logger->handle( 'info', $user_id, 'Adding user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . $setting['tag_link'][0] . '</strong>' );

			$user->add_role( $role_slug );

		} elseif ( empty( $result ) && in_array( $role_slug, $user->roles ) ) {

			wp_fusion()->logger->handle( 'info', $user_id, 'Removing user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . $setting['tag_link'][0] . '</strong>' );

			$user->remove_role( $role_slug );

		}
	}

}

add_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

/**
 * Apply tags when role added
 *
 * @return void
 */

function wpf_user_roles_added_role( $user_id ) {

	// Don't run for admins.

	if ( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'wpf_roles_settings', array() );

	if ( empty( $settings ) ) {
		return;
	}

	remove_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

	$user = get_userdata( $user_id );

	if ( ! empty( $user->caps ) && is_array( $user->caps ) ) {

		$roles = array_keys( $user->caps );

		foreach ( $settings as $role_slug => $setting ) {

			if ( in_array( $role_slug, $roles ) ) {

				if ( ! empty( $setting['tag_link'] ) ) {
					wp_fusion()->user->apply_tags( $setting['tag_link'], $user_id );
				}

				if ( ! empty( $setting['apply_tags'] ) ) {
					wp_fusion()->user->apply_tags( $setting['apply_tags'], $user_id );
				}

			}

		}
	}

	add_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

}

add_action( 'add_user_role', 'wpf_user_roles_added_role' );
add_action( 'set_user_role', 'wpf_user_roles_added_role' );
add_action( 'user_register', 'wpf_user_roles_added_role' );


/**
 * Remove linked tag when role removed
 *
 * @return void
 */

function wpf_user_roles_removed_role( $user_id, $role ) {

	error_log('removed ' . $role);

	// Don't run for admins

	if ( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'wpf_roles_settings', array() );

	if ( empty( $settings ) ) {
		return;
	}

	remove_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

	foreach ( $settings as $role_slug => $setting ) {

		if ( $role == $role_slug ) {

			if ( ! empty( $setting['tag_link'] ) ) {
				wp_fusion()->user->remove_tags( $setting['tag_link'], $user_id );
			}

			break;

		}

	}

	add_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

}

add_action( 'remove_user_role', 'wpf_user_roles_removed_role', 10, 2 );


/**
 * Register admin menu
 *
 * @return void
 */

function wpf_user_roles_admin_menu() {

	$id = add_submenu_page(
		'options-general.php',
		'WP Fusion - User Roles',
		'WP Fusion Roles',
		'manage_options',
		'wpf-roles-settings',
		'wpf_user_roles_render_admin_menu'
	);

	add_action( 'load-' . $id, 'wpf_user_roles_enqueue_scripts' );

}

add_action( 'admin_menu', 'wpf_user_roles_admin_menu' );

function wpf_user_roles_enqueue_scripts() {

	wp_enqueue_style( 'bootstrap', WPF_DIR_URL . 'includes/admin/options/css/bootstrap.min.css' );
	wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
	wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );

}

function wpf_user_roles_render_admin_menu() {

	// Save settings
	if ( isset( $_POST['wpf_roles_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_roles_settings_nonce'], 'wpf_roles_settings' ) ) {
		update_option( 'wpf_roles_settings', $_POST['wpf-settings'] );
		echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
	}

	?>

	<div class="wrap">
		<h2>WP Fusion Roles</h2>

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
					<th>Role</th>
					<th>Apply tags when role is granted</th>
					<th>Link with tag</th>
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

					<tr>
						<td><?php echo $role['name']; ?></td>

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
