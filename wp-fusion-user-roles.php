<?php

/**
 * Plugin Name: WP Fusion - User Roles Addon
 * Description: Allows linking a CRM tag with a WordPress user role to automatically set roles when tags are modified, as well as applying tags based on user role changes.
 * Plugin URI: https://wpfusion.com/
 * Version: 1.2.2
 * Author: Very Good Plugins
 * Author URI: https://verygoodplugins.com/
 * Text Domain: wp-fusion-user-roles
 */

/**
 * @copyright Copyright (c) 2022. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPF_USER_ROLES_VERSION', '1.2.2' );

/**
 * Class WP_Fusion_User_Roles
 *
 * @since 1.2.0
 */
final class WP_Fusion_User_Roles {

	/** Singleton *************************************************************/

	/**
	 * The one true WP_Fusion_User_Roles.
	 *
	 * @var WP_Fusion_User_Roles
	 * @since 1.2.0
	 */
	private static $instance;

	/**
	 * Allows interfacing with the main class.
	 *
	 * @var WP_Fusion_User_Roles_Public
	 * @since 1.2.0
	 */
	public $public;

	/**
	 * Allows interfacing with the admin class.
	 *
	 * @var WP_Fusion_User_Roles_Admin
	 * @since 1.1.3
	 */
	public $admin;


	/**
	 * Main WP_Fusion_User_Roles Instance
	 *
	 * Insures that only one instance of WP_Fusion_User_Roles exists in
	 * memory at any one time. Also prevents needing to define globals all over
	 * the place.
	 *
	 * @since  1.2.0
	 * @static var array $instance
	 *
	 * @return WP_Fusion_User_Roles The one true WP_Fusion_User_Roles
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Fusion_User_Roles ) ) {

			self::$instance = new WP_Fusion_User_Roles();

			self::$instance->setup_constants();

			if ( ! is_wp_error( self::$instance->check_install() ) ) {

				self::$instance->includes();

				self::$instance->public = new WP_Fusion_User_Roles_Public();

				if ( is_admin() ) {
					self::$instance->admin = new WP_Fusion_User_Roles_Admin();
				}

				add_action( 'init', array( self::$instance, 'updater' ) );

			} else {
				add_action( 'admin_notices', array( self::$instance, 'admin_notices' ) );
			}
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 *
	 * @since  1.2.0.
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-fusion-user-roles' ), '1.2.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access protected
	 *
	 * @since  1.2.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-fusion-user-roles' ), '1.2.0' );
	}

	/**
	 * If the method doesn't exist here, call it from the public class.
	 *
	 * @since  1.2.0
	 *
	 * @param  string $name      The method name.
	 * @param  array  $arguments The arguments.
	 * @return mxied  The returned value.
	 */
	public function __call( $name, $arguments ) {

		if ( ! method_exists( self::$instance, $name ) && method_exists( self::$instance->public, $name ) ) {
			return call_user_func_array( array( self::$instance->public, $name ), $arguments );
		}

	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 *
	 * @since  1.2.0
	 */
	private function setup_constants() {

		if ( ! defined( 'WPF_USER_ROLES_DIR_PATH' ) ) {
			define( 'WPF_USER_ROLES_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WPF_USER_ROLES_PLUGIN_PATH' ) ) {
			define( 'WPF_USER_ROLES_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WPF_USER_ROLES_DIR_URL' ) ) {
			define( 'WPF_USER_ROLES_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

	}


	/**
	 * Include required files.
	 *
	 * @access private
	 *
	 * @since  1.2.0
	 */
	private function includes() {

		require_once WPF_USER_ROLES_DIR_PATH . 'includes/class-wp-fusion-user-roles-public.php';

		if ( is_admin() ) {
			require_once WPF_USER_ROLES_DIR_PATH . 'includes/class-wp-fusion-user-roles-admin.php';
		}

	}


	/**
	 * Check install.
	 *
	 * Checks if WP Fusion plugin is active, configured correctly, and if it
	 * supports the user's chosen CRM. If not, returns error message defining
	 * failure.
	 *
	 * @since  1.2.0
	 *
	 * @return mixed True on success, WP_Error on error
	 */
	public function check_install() {

		if ( ! function_exists( 'wp_fusion' ) || ! is_object( wp_fusion()->crm ) || ! wp_fusion()->is_full_version() ) {
			return new WP_Error( 'error', 'WP Fusion is required for <strong>WP Fusion - User Roles Addon</strong> to work.' );
		}

		if ( ! wpf_get_option( 'connection_configured' ) ) {
			return new WP_Error( 'warning', 'WP Fusion must be connected to a CRM for <strong>WP Fusion - User Roles Addon</strong> to work. Please deactivate the WP Fusion - Enhanced User Roles Addon plugin.' );
		}

		return true;
	}


	/**
	 * Show error message if install check failed.
	 *
	 * @since  1.2.0
	 *
	 * @return mixed error message.
	 */
	public function admin_notices() {

		$return = self::$instance->check_install();

		if ( is_wp_error( $return ) && 'error' === $return->get_error_code() ) {

			echo '<div class="notice notice-error">';
			echo '<p>' . wp_kses_post( $return->get_error_message() ) . '</p>';
			echo '</div>';

		}

	}

	/**
	 * Set up EDD plugin updater.
	 *
	 * @since 1.2.0
	 */
	public function updater() {

		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.

		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;

		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$license_status = wpf_get_option( 'license_status' );
		$license_key    = wpf_get_option( 'license_key' );

		if ( 'valid' === $license_status ) {

			$edd_updater = new WPF_Plugin_Updater(
				WPF_STORE_URL,
				__FILE__,
				array(
					'version' => WPF_USER_ROLES_VERSION,
					'license' => $license_key,
					'item_id' => 117103,
					'author'  => 'Very Good Plugins',
				)
			);

		} else {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {
				add_action(
					'after_plugin_row_' . WPF_USER_ROLES_PLUGIN_PATH,
					array(
						wp_fusion(),
						'wpf_update_message',
					),
					10,
					3
				);
			}
		}

	}

}


/**
 * The main function responsible for returning the one true WP Fusion User
 * Roles Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing to
 * declare the global.
 *
 * @since  1.2.0
 *
 * @return object The one true WP Fusion User Roles
 */
function wp_fusion_user_roles() {

	return WP_Fusion_User_Roles::instance();

}

add_action( 'wp_fusion_init', 'wp_fusion_user_roles' );

