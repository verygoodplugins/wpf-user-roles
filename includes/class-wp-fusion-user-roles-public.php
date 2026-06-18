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
		add_action( 'wpf_user_created', array( $this, 'added_role' ) );

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
		remove_action( 'profile_update', array( wp_fusion()->user, 'profile_update' ) );
		remove_action( 'add_user_role', array( $this, 'added_role' ) );
		remove_action( 'set_user_role', array( $this, 'added_role' ) );
		remove_action( 'remove_user_role', array( $this, 'removed_role' ) );

		foreach ( $settings as $role_slug => $setting ) {

			if ( empty( $setting['tag_link'] ) ) {
				continue;
			}

			$tag_link = $setting['tag_link'];

			// Translate legacy tag IDs (e.g. HubSpot v1→v3 list migration).
			if ( isset( wp_fusion()->tag_migration ) && is_object( wp_fusion()->tag_migration ) && method_exists( wp_fusion()->tag_migration, 'translate_tags' ) ) {
				$tag_link = wp_fusion()->tag_migration->translate_tags( $tag_link );
			}

			$result = array_intersect( $user_tags, $tag_link );

			if ( ! empty( $result ) && ! in_array( $role_slug, (array) $user->roles ) ) {

				wp_fusion()->logger->handle( 'info', $user_id, 'Adding user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . wpf_get_tag_label( $tag_link[0] ) . '</strong>.' );

				if ( function_exists( 'hmmr_fs' ) || class_exists( 'Members_Plugin' ) || defined( 'URE_VERSION' ) ) {
					// "HM Multiple Roles", "Members", and User Role Editor allow for visually managing multiple roles.
					$user->add_role( $role_slug );
				} else {
					$user->set_role( $role_slug );
				}

				$this->prevent_profile_role_overwrite();

			} elseif ( empty( $result ) && in_array( $role_slug, (array) $user->roles ) ) {

				wp_fusion()->logger->handle( 'info', $user_id, 'Removing user role <strong>' . $role_slug . '</strong>, triggered by linked tag <strong>' . wpf_get_tag_label( $tag_link[0] ) . '</strong>.' );

				$user->remove_role( $role_slug );

				$this->prevent_profile_role_overwrite();

			}
		}

		add_action( 'profile_update', array( wp_fusion()->user, 'profile_update' ), 10, 3 );
		add_action( 'add_user_role', array( $this, 'added_role' ) );
		add_action( 'set_user_role', array( $this, 'added_role' ) );
		add_action( 'remove_user_role', array( $this, 'removed_role' ), 10, 2 );

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

		if ( ! wpf_get_contact_id( $user_id ) ) {
			// Don't apply the tags if WPF hasn't synced the new user to the CRM yet.
			return;
		}

		$settings = get_option( 'wpf_roles_settings', array() );

		if ( empty( $settings ) ) {
			return;
		}

		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) );

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

	/**
	 * Prevent WordPress from saving a stale posted role after linked tags update it.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private function prevent_profile_role_overwrite() {

		if (
			doing_action( 'edit_user_profile_update' ) ||
			doing_action( 'personal_options_update' )
		) {
			unset( $_POST['role'] );
		}
	}
}
