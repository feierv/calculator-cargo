<?php
/**
 * Main plugin class.
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class My_Plugin
 */
class My_Plugin {

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		require_once MY_PLUGIN_PATH . 'includes/config.php';
		require_once MY_PLUGIN_PATH . 'includes/ro-cities.php';
		require_once MY_PLUGIN_PATH . 'includes/cn-cities.php';
		require_once MY_PLUGIN_PATH . 'includes/email-order-confirmation.php';
		require_once MY_PLUGIN_PATH . 'includes/resend-email.php';
		require_once MY_PLUGIN_PATH . 'includes/local-orders-sqlite.php';
		require_once MY_PLUGIN_PATH . 'includes/order-confirm-submit.php';
		require_once MY_PLUGIN_PATH . 'includes/class-my-plugin-public.php';
		if ( is_admin() ) {
			require_once MY_PLUGIN_PATH . 'admin/class-my-plugin-admin.php';
		}
	}

	/**
	 * Define the locale for internationalization.
	 */
	private function set_locale() {
		load_plugin_textdomain(
			'my-plugin',
			false,
			dirname( MY_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Register admin hooks.
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		$plugin_admin = new My_Plugin_Admin();
	}

	/**
	 * Register public-facing hooks (and shortcodes in admin so WP knows about them).
	 */
	private function define_public_hooks() {
		$plugin_public = new My_Plugin_Public();
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		// Activation logic.
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		// Deactivation logic.
	}
}
