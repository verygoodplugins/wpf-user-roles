<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Fusion_User_Roles_Public
 *
 * Handles the public-facing functionality.
 *
 * @since 1.2.0
 */
class WP_Fusion_User_Roles_Public {

	/**
	 * Constructs a new instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		add_action( 'add_user_role', array( $this, 'added_role' ) );
		add_action( 'set_user_role', array( $this, 'added_role' ) );
		add_action( 'user_register', array( $this, 'added_role' ) );

		add_action( 'remove_user_role', array( $this, 'removed_role' ), 10, 2 );

	}

	/**
	 * Triggered when tags are modified, updates user roles.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user's CRM tags.
	 */
	public function tags_modified( $user_id, $user_tags ) {

		if ( user_can( $user_id, 'manage_options' ) ) {
			return; // Don't run for admins.
		}

		$settings = get_option( 'wpf_roles_settings', array() );

		if ( empty( $settings ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		// Prevent looping.
		remove_action( 'profile_update', array( wp_fusion()->user, 'profile_update' ), 10, 2 );
		remove_action( 'add_user_role', array( $this, 'added_role' ) );
		remove_action( 'set_user_role', array( $this, 'added_role' ) );
		remove_action( 'remove_user_role', array( $this, 'removed_role' ), 10, 2 );

		foreach ( $settings as $role_slug => $setting ) {

			if ( empty( $setting['tag_link'] ) ) {
				continue;
			}

			$result = array_intersect( $user_tags, $setting['tag_link'] );

			if ( ! empty( $result ) && ! in_array( $role_slug, (array) $user->roles ) ) {

				wp_fusion()->logger->handle( 'info', $user_id, 'Adding user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . $setting['tag_link'][0] . '</strong>' );

				if ( function_exists( 'hmmr_fs' ) || class_exists( 'Members_Plugin' ) ) {
					// "HM Multiple Roles" and "Members" allow for visually managing multiple roles.
					$user->add_role( $role_slug );
				} else {
					$user->set_role( $role_slug );
				}

			} elseif ( empty( $result ) && in_array( $role_slug, (array) $user->roles ) ) {

				wp_fusion()->logger->handle( 'info', $user_id, 'Removing user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . $setting['tag_link'][0] . '</strong>' );

				$user->remove_role( $role_slug );

			}
		}

	}


	/**
	 * Apply tags when role added.
	 *
	 * @since 1.1.0
	 *
	 * @param int $user_id The user ID.
	 */
	public function added_role( $user_id ) {

		if ( user_can( $user_id, 'manage_options' ) ) {
			return; // Don't run for admins.
		}

		$settings = get_option( 'wpf_roles_settings', array() );

		if ( empty( $settings ) ) {
			return;
		}

		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

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

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

	}


	/**
	 * Remove linked tag when role removed.
	 *
	 * @since 1.0.1
	 *
	 * @param int    $user_id  The user ID.
	 * @param string $role The role that was removed.
	 */
	public function removed_role( $user_id, $role ) {

		if ( user_can( $user_id, 'manage_options' ) ) {
			return; // Don't run for admins.
		}

		$settings = get_option( 'wpf_roles_settings', array() );

		if ( empty( $settings ) ) {
			return;
		}

		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		foreach ( $settings as $role_slug => $setting ) {

			if ( $role === $role_slug ) {

				if ( ! empty( $setting['tag_link'] ) ) {
					wp_fusion()->user->remove_tags( $setting['tag_link'], $user_id );
				}

				break;

			}

		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

	}
}
