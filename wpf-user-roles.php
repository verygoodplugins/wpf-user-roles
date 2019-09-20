<?php 

/*
Plugin Name: WP Fusion - User Roles
Description: Allows linking a tag with a WordPress user role to automatically set roles when tags are modified.
Plugin URI: https://verygoodplugins.com/
Version: 1.0
Author: Very Good Plugins
Author URI: https://verygoodplugins.com/
*/

function wpf_user_roles_tags_modified( $user_id, $user_tags ) {

	// Don't run for admins
	if( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'wpf_roles_settings', array() );

	$user = get_user_by( 'id', $user_id );

	foreach( $settings as $role_slug => $setting ) {

		if( empty( $setting['tag_link'] ) ) {
			continue;
		}

		$result = array_intersect($user_tags, $setting['tag_link']);

		if( ! empty( $result ) && ! in_array($role_slug, $user->roles) ) {

			wp_fusion()->logger->handle( 'info', $user_id, 'Changing user role to <strong>' . $role_slug . '</strong> from linked tag <strong>' . $setting['tag_link'][0] . '</strong>' );

			remove_action( 'profile_update', array( wp_fusion()->user, 'profile_update'), 10, 2);

			wp_update_user( array( 'ID' => $user_id, 'role' => $role_slug ) );
			return;

		}

	}

}

add_action( 'wpf_tags_modified', 'wpf_user_roles_tags_modified', 10, 2 );

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

add_action( 'admin_menu', 'wpf_user_roles_admin_menu');

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

    		<?php $all_roles = $wp_roles->roles; ?>
    		<?php $editable_roles = apply_filters('editable_roles', $all_roles); ?>

			<?php $settings = get_option( 'wpf_roles_settings', array() ); ?>

            <table class="table table-hover" id="wpf-mm-products-table">
                <thead>
                <tr>
                    <th>Role</th>
                    <th>Link With Tag</th>
                </tr>
                </thead>
                <tbody>

				<?php foreach ( $editable_roles as $slug => $role ) : ?>

					<?php if ( ! isset( $settings[$slug]['tag_link'] ) ) {
						$settings[$slug]['tag_link'] = array();
					} ?>

                    <tr>
                        <td><?php echo $role['name']; ?></td>
                        <td id="wpf-tags-td">
                        	<?php 

							$args = array(
								'setting' 		=> $settings[$slug],
								'meta_name'		=> 'wpf-settings',
								'field_id'		=> $slug,
								'field_sub_id'	=> 'tag_link',
								'placeholder'	=> 'Select a tag',
								'limit'			=> 1
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
