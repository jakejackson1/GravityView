<?php
/**
 * Roles and Capabilities
 *
 * @package     GravityView
 * @license     GPL2+
 * @since       1.14
 * @author      Katz Web Services, Inc.
 * @link        http://gravityview.co
 * @copyright   Copyright 2015, Katz Web Services, Inc.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * GravityView Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * @since 1.15
 */
class GravityView_Roles_Capabilities {

	/**
	 * @var GravityView_Roles_Capabilities|null
	 */
	static $instance = null;

	/**
	 * @since 1.15
	 * @return GravityView_Roles_Capabilities
	 */
	public static function get_instance() {

		if( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get things going
	 *
	 * @since 1.15
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add Members plugin hook
	 * @since 1.15
	 */
	private function add_hooks() {
		add_filter( 'members_get_capabilities', array( 'GravityView_Roles_Capabilities', 'merge_with_all_caps' ) );
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 20 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 4 );
	}


	/**
	 * Add support for `gravityview_full_access` capability, and
	 *
	 * @see map_meta_cap()
	 *
	 * @since 1.15
	 *
	 * @param array   $allcaps An array of all the user's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User|null $user    The user object, in WordPress 3.7.0 or higher
	 *
	 * @return mixed
	 */
	public function filter_user_has_cap( $usercaps = array(), $caps = array(), $args = array(), $user = NULL ) {

		// Empty caps_to_check array
		if( ! $usercaps || ! $caps ) {
			return $usercaps;
		}

		/**
		 * Enable all GravityView caps_to_check if `gravityview_full_access` is enabled
		 */
		if( ! empty( $usercaps['gravityview_full_access'] ) ) {

			$all_gravityview_caps = self::all_caps();

			foreach( $all_gravityview_caps as $gv_cap ) {
				$usercaps[ $gv_cap ] = true;
			}

			unset( $all_gravityview_caps );
		}

		$usercaps = $this->add_gravity_forms_usercaps_to_gravityview_caps( $usercaps );

		return $usercaps;
	}

	/**
	 *
	 * @param $usercaps
	 *
	 * @return mixed
	 */
	function add_gravity_forms_usercaps_to_gravityview_caps( $usercaps ) {

		$gfcaps_to_gvcaps = $this->map_gravity_forms_caps();;
		foreach( $gfcaps_to_gvcaps as $gf_cap => $gv_cap ) {
			if( isset( $usercaps[ $gf_cap ] ) && !isset( $usercaps[ $gv_cap ]) ) {
				$usercaps[ $gv_cap ] = $usercaps[ $gf_cap ];
			}
		}

		return $usercaps;
	}

	/**
	 * If a user has been assigned custom capabilities for Gravity Forms, but they haven't been assigned similar abilities
	 * in GravityView yet, we give temporary access to the permissions, until they're set.
	 *
	 * @return array
	 */
	function map_gravity_forms_caps() {

		return array(
			'gravityforms_edit_entries' => 'gravityview_edit_others_entries',
			'gravityforms_delete_entries' => 'gravityview_delete_others_entries',
			'gravityforms_edit_entry_notes' => 'gravityview_edit_others_entry_notes',
		);
	}

	/**
	 * Add GravityView group to Members 1.x plugin management screen
	 * @see members_register_cap_group()
	 * @since 1.15
	 * @return void
	 */
	function members_register_cap_group() {
		if ( function_exists( 'members_register_cap_group' ) ) {
			$args = array(
				'label'       => __( 'GravityView' ),
				'icon'        => 'gv-icon-astronaut-head',
				'caps_to_check'        => self::all_caps(),
				'merge_added' => true,
				'diff_added'  => false,
			);
			members_register_cap_group( 'gravityview', $args );
		}
	}

	/**
	 * Merge capabilities array with GravityView capabilities
	 *
	 * @since 1.15 Used to add GravityView caps to the Members plugin
	 * @param array $caps Existing capabilities
	 * @return array Modified capabilities array
	 */
	public static function merge_with_all_caps( $caps ) {

		$return_caps = array_merge( $caps, self::all_caps() );

		return array_unique( $return_caps );
	}

	/**
	 * Retrieves the global WP_Roles instance and instantiates it if necessary.
	 *
	 * @see wp_roles() This method uses the exact same code as wp_roles(), here for backward compatibility
	 *
	 * @global WP_Roles $wp_roles WP_Roles global instance.
	 *
	 * @return WP_Roles WP_Roles global instance if not already instantiated.
	 */
	private function wp_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		return $wp_roles;
	}

	/**
	 * Add capabilities to their respective roles
	 *
	 * @since 1.15
	 * @return void
	 */
	public function add_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			foreach( $wp_roles->get_names() as $role_slug => $role_label ) {

				$capabilities = self::all_caps( $role_slug );

				foreach( $capabilities as $cap ) {
					$wp_roles->add_cap( $role_slug, $cap );
				}
			}
		}
	}

	/**
	 * Get an array of GravityView capabilities
	 *
	 * @see get_post_type_capabilities()
	 *
	 * @since 1.15
	 *
	 * @param string $role If set, get the caps_to_check for a specific role. Pass 'all' to get all caps_to_check in a flat array. Default: `all`
	 *
	 * @return array If $role is set, flat array of caps_to_check. Otherwise, a multi-dimensional array of roles and their caps_to_check with the following keys: 'administrator', 'editor', 'author', 'contributor', 'subscriber'
	 */
	public static function all_caps( $role = 'all' ) {

		// Change settings
		$administrator = array(
			'gravityview_full_access', // Grant access to all caps_to_check
			'gravityview_view_settings',
			'gravityview_edit_settings',
			'gravityview_uninstall', // Ability to trigger the Uninstall @todo
		);

		// Edit, publish, delete own and others' stuff
		$editor = array(
			'edit_others_gravityviews',
			'read_private_gravityviews',
			'delete_private_gravityviews',
			'delete_others_gravityviews',
			'edit_private_gravityviews',
			'publish_gravityviews',
			'delete_published_gravityviews',
			'edit_published_gravityviews',
			'gravityview_contact_support',
			'copy_gravityviews', // For duplicate/clone View functionality

			// GF caps_to_check
			'gravityview_edit_others_entries',
			'gravityview_view_others_entry_notes',
			'gravityview_edit_others_entry_notes',
			'gravityview_moderate_entries', // Approve or reject entries from the Admin; show/hide approval column in Entries screen
			'gravityview_delete_others_entries',
		);

		// Edit, publish and delete own stuff
		$author = array(
			// GF caps_to_check
			'gravityview_edit_entries',
			'gravityview_edit_form_entries', // This is similar to `gravityview_edit_entries`, but checks against a Form ID $object_id
			'gravityview_view_entry_notes',
			'gravityview_delete_entries',
			'gravityview_delete_entry',
		);

		// Edit and delete drafts but not publish
		$contributor = array(
			'edit_gravityviews', // Affects if you're able to see the Views menu in the Admin
			'delete_gravityviews',
			'gravityview_support_port', // Display GravityView Help beacon
		);

		// Read only
		$subscriber = array(
			'gravityview_view_entries',
			'gravityview_view_others_entries',
		);

		$capabilities = array();

		switch( $role ) {
			case 'subscriber':
				$capabilities = $subscriber;
				break;
			case 'contributor':
				$capabilities = array_merge( $contributor, $subscriber );
				break;
			case 'author':
				$capabilities = array_merge( $author, $contributor, $subscriber );
				break;
			case 'editor':
				$capabilities = array_merge( $editor, $author, $contributor, $subscriber );
				break;
			case 'administrator':
			case 'all':
				$capabilities = array_merge( $administrator, $editor, $author, $contributor, $subscriber );
				break;
		}

		// If role is set, return caps_to_check for just that role.
		if( $role ) {
			return $capabilities;
		}

		// By default, return multi-dimensional array of all caps_to_check
		return compact( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	}

	/**
	 * Check whether the current user has a capability
	 *
	 * @since 1.15
	 *
	 * @see WP_User::user_has_cap()
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/user_has_cap  You can filter permissions based on entry/View/form ID using `user_has_cap` filter
	 *
	 * @see  GFCommon::current_user_can_any
	 * @uses GFCommon::current_user_can_any
	 *
	 * @param string|array $caps_to_check Single capability or array of capabilities
	 * @param int|null $object_id (optional) Parameter can be used to check for capabilities against a specific object, such as a post or us
	 * @param int|null $user_id (optional) Check the capabilities for a user who is not necessarily the currently logged-in user
	 *
	 * @return bool True: user has at least one passed capability; False: user does not have any defined capabilities
	 */
	public static function has_cap( $caps_to_check = '', $object_id = null, $user_id = null ) {

		$has_cap = false;

		if( empty( $caps_to_check ) ) {
			return $has_cap;
		}

		// Add full access caps for GV & GF
		$caps_to_check = self::maybe_add_full_access_caps( (array)$caps_to_check );

		foreach ( $caps_to_check as $cap ) {
			if( ! is_null( $object_id ) ) {
				$has_cap = $user_id ? user_can( $user_id, $cap, $object_id ) : current_user_can( $cap, $object_id );
			} else {
				$has_cap = $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );
			}
			// At the first successful response, stop checking
			if( $has_cap ) {
				break;
			}
		}

		return $has_cap;
	}

	/**
	 * @since 1.15

	 * @param array $caps_to_check
	 *
	 * @return array
	 */
	public static function maybe_add_full_access_caps( $caps_to_check = array() ) {

		$all_gravityview_caps = self::all_caps();

		// Are there any $caps_to_check that are from GravityView?
		if( $has_gravityview_caps = array_intersect( $caps_to_check, $all_gravityview_caps ) ) {
			$caps_to_check[] = 'gravityview_full_access';
		}

		$all_gravity_forms_caps = class_exists( 'GFCommon' ) ? GFCommon::all_caps() : array();

		// Are there any $caps_to_check that are from Gravity Forms?
		if( $all_gravity_forms_caps = array_intersect( $caps_to_check, $all_gravity_forms_caps ) ) {
			$caps_to_check[] = 'gform_full_access';
		}

		return array_unique( $caps_to_check );
	}

	/**
	 * Remove all GravityView caps_to_check from all roles
	 *
	 * @since 1.15
	 * @return void
	 */
	public function remove_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			/** Remove all GravityView caps_to_check from all roles */
			$capabilities = self::all_caps();

			// Loop through each role and remove GV caps_to_check
			foreach( $wp_roles->get_names() as $role_slug => $role_name ) {
				foreach ( $capabilities as $cap ) {
					$wp_roles->remove_cap( $role_slug, $cap );
				}
			}
		}
	}
}

add_action( 'init', array( 'GravityView_Roles_Capabilities', 'get_instance' ), 1 );