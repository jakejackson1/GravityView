<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The GravityView WordPress plugin class.
 *
 * Contains functionality related to GravityView being
 * a WordPress plugin and doing WordPress pluginy things.
 *
 * Accessible via gravityview()->plugin
 */
final class Plugin {
	/**
	 * @var string The plugin version.
	 *
	 * @api
	 * @since future
	 */
	public $version = GV_PLUGIN_VERSION;

	/**
	 * @var string Minimum WordPress version.
	 *
	 * GravityView requires at least this version of WordPress to function properly.
	 */
	private static $min_wp_version = '4.0';

	/**
	 * @var string Minimum Gravity Forms version.
	 *
	 * GravityView requires at least this version of Gravity Forms to function properly.
	 */
	private static $min_gf_version = '1.9.14';

	/**
	 * @var string Minimum PHP version.
	 *
	 * GravityView requires at least this version of PHP to function properly.
	 */
	private static $min_php_version = '5.3.0';

	/**
	 * @var string|bool Minimum future PHP version.
	 *
	 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
	 */
	private static $future_min_php_version = false;

	/**
	 * @var string|bool Minimum future Gravity Forms version.
	 *
	 * GravityView will require this version of Gravity Forms soon. False if no future Gravity Forms version changes are planned.
	 */
	private static $future_min_gf_version = false;

	/**
	 * @var \GV\Plugin The \GV\Plugin static instance.
	 */
	private static $__instance = null;

	/**
	 * Get the global instance of \GV\Plugin.
	 *
	 * @return \GV\Plugin The global instance of GravityView Plugin.
	 */
	public static function get() {
		if ( ! self::$__instance instanceof self ) {
			self::$__instance = new self;
		}
		return self::$__instance;
	}


	private function __construct() {
		/**
		 * Load translations.
		 */
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Load some frontend-related legacy files.
		 */
		add_action( 'init', array( $this, 'include_legacy_frontend' ) );
	}
	
	/**
	 * Check whether GravityView is network activated.
	 *
	 * @return bool Whether it's network activated or not.
	 */
	public static function is_network_activated() {
		return is_multisite() && ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'gravityview/gravityview.php' ) );
	}

	/**
	 * Include more legacy stuff.
	 *
	 * @param boolean $force Whether to force the includes.
	 *
	 * @return void
	 */
	public function include_legacy_frontend( $force = false ) {
		if ( gravityview()->request->is_admin() && ! $force ) {
			return;
		}

		include_once $this->dir( 'includes/class-gravityview-image.php' );
		include_once $this->dir( 'includes/class-template.php' );
		include_once $this->dir( 'includes/class-api.php' );
		include_once $this->dir( 'includes/class-frontend-views.php' );
		include_once $this->dir( 'includes/class-gravityview-change-entry-creator.php' );

		/**
		 * @action `gravityview_include_frontend_actions` Triggered after all GravityView frontend files are loaded
		 *
		 * @deprecated Use `gravityview/loaded` along with \GV\Request::is_admin(), etc.
		 *
		 * Nice place to insert extensions' frontend stuff
		 */
		do_action( 'gravityview_include_frontend_actions' );
	}

	/**
	 * Load more legacy core files.
	 *
	 * @return void
	 */
	public function include_legacy_core() {
		// Load fields
		include_once $this->dir( 'includes/fields/class-gravityview-fields.php' );
		include_once $this->dir( 'includes/fields/class-gravityview-field.php' );

		// Load all field files automatically
		foreach ( glob( $this->dir( 'includes/fields/class-gravityview-field*.php' ) ) as $gv_field_filename ) {
			include_once $gv_field_filename;
		}

		include_once $this->dir( 'includes/class-gravityview-entry-approval-status.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-approval.php' );

		include_once $this->dir( 'includes/class-gravityview-entry-notes.php' );
		include_once $this->dir( 'includes/load-plugin-and-theme-hooks.php' );

		// Load Extensions
		// @todo: Convert to a scan of the directory or a method where this all lives
		include_once $this->dir( 'includes/extensions/edit-entry/class-edit-entry.php' );
		include_once $this->dir( 'includes/extensions/delete-entry/class-delete-entry.php' );
		include_once $this->dir( 'includes/extensions/entry-notes/class-gravityview-field-notes.php' );

		// Load WordPress Widgets
		include_once $this->dir( 'includes/wordpress-widgets/register-wordpress-widgets.php' );

		// Load GravityView Widgets
		include_once $this->dir( 'includes/widgets/register-gravityview-widgets.php' );

		// Add oEmbed
		include_once $this->dir( 'includes/class-oembed.php' );

		// Add logging
		include_once $this->dir( 'includes/class-gravityview-logging.php' );

		include_once $this->dir( 'includes/class-ajax.php' );
		include_once $this->dir( 'includes/class-gravityview-settings.php' );
		include_once $this->dir( 'includes/class-frontend-views.php' );
		include_once $this->dir( 'includes/class-gravityview-admin-bar.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-list.php' );
		include_once $this->dir( 'includes/class-gravityview-merge-tags.php'); /** @since 1.8.4 */
		include_once $this->dir( 'includes/class-data.php' );
		include_once $this->dir( 'includes/class-gravityview-shortcode.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-link-shortcode.php' );
		include_once $this->dir( 'includes/class-gvlogic-shortcode.php' );
		include_once $this->dir( 'includes/presets/register-default-templates.php' );

		include_once $this->dir( 'includes/class-gravityview-gfformsmodel.php' );

		if ( ! class_exists( '\GravityView_Extension' ) ) {
			include_once $this->dir( 'includes/class-gravityview-extension.php' );
		}
	}

	/**
	 * Load the translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$loaded = load_plugin_textdomain( 'gravityview', false, $this->dir( 'languages' ) );
		
		if ( ! $loaded ) {
			$loaded = load_muplugin_textdomain( 'gravityview', '/languages/' );
		}
		if ( ! $loaded ) {
			$loaded = load_theme_textdomain( 'gravityview', '/languages/' );
		}
		if ( ! $loaded ) {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'gravityview' );
			$mofile = $this->dir( 'languages' ) . '/gravityview-'. $locale .'.mo';
			load_textdomain( 'gravityview', $mofile );
		}
	}

	/**
	 * Register hooks that are fired when the plugin is activated and deactivated.
	 *
	 * @return void
	 */
	public function register_activation_hooks() {
		register_activation_hook( $this->dir( 'gravityview.php' ), array( $this, 'activate' ) );
		register_deactivation_hook( $this->dir( 'gravityview.php' ), array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation function.
	 *
	 * @internal
	 * @return void
	 */
	public function activate() {
		gravityview();

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->dir( 'future/includes/class-gv-view.php' );
		View::register_post_type();

		/** Add the entry rewrite endpoint. */
		require_once $this->dir( 'future/includes/class-gv-entry.php' );
		Entry::add_rewrite_endpoint();

		/** Flush all URL rewrites. */
		flush_rewrite_rules();

		update_option( 'gv_version', \GravityView_Plugin::version );

		/** Add the transient to redirect to configuration page. */
		set_transient( '_gv_activation_redirect', true, 60 );

		/** Clear settings transient. */
		delete_transient( 'gravityview_edd-activate_valid' );

		\GravityView_Roles_Capabilities::get_instance()->add_caps();
	}

	/**
	 * Plugin deactivation function.
	 *
	 * @internal
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Retrieve an absolute path within the Gravity Forms plugin directory.
	 *
	 * @api
	 * @since future
	 *
	 * @param string $path Optional. Append this extra path component.
	 * @return string The absolute path to the plugin directory.
	 */
	public function dir( $path = '' ) {
		return GRAVITYVIEW_DIR . ltrim( $path, '/' );
	}

	/**
	 * Retrieve a URL within the Gravity Forms plugin directory.
	 *
	 * @api
	 * @since future
	 *
	 * @param string $path Optional. Extra path appended to the URL.
	 * @return string The URL to this plugin, with trailing slash.
	 */
	public function url( $path = '/' ) {
		return plugins_url( $path, $this->dir( 'gravityview.php' ) );
	}

	/**
	 * Is everything compatible with this version of GravityView?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool
	 */
	public function is_compatible() {
		return
			$this->is_compatible_php()
			&& $this->is_compatible_wordpress()
			&& $this->is_compatible_gravityforms();
	}

	/**
	 * Is this version of GravityView compatible with the current version of PHP?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_php() {
		return version_compare( $this->get_php_version(), self::$min_php_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of WordPress?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_wordpress() {
		return version_compare( $this->get_wordpress_version(), self::$min_wp_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of Gravity Forms?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise (or not active/installed).
	 */
	public function is_compatible_gravityforms() {
		$version = $this->get_gravityforms_version();
		return $version ? version_compare( $version, self::$min_gf_version, '>=' ) : false;
	}

	/**
	 * Retrieve the current PHP version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE during testing.
	 *
	 * @return string The version of PHP.
	 */
	private function get_php_version() {
		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] : phpversion();
	}

	/**
	 * Retrieve the current WordPress version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE during testing.
	 *
	 * @return string The version of WordPress.
	 */
	private function get_wordpress_version() {
		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] : $GLOBALS['wp_version'];
	}

	/**
	 * Retrieve the current Gravity Forms version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE during testing.
	 *
	 * @return string|null The version of Gravity Forms or null if inactive.
	 */
	private function get_gravityforms_version() {
		if ( ! class_exists( '\GFCommon' ) || ! empty( $GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] ) ) {
			gravityview()->log->error( 'Gravity Forms is inactive or not installed.' );
			return null;
		}

		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] : \GFCommon::$version;
	}

	private function __clone() { }

	private function __wakeup() { }
}
