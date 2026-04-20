<?php
/**
 * Plugin Name:       My Plugin
 * Plugin URI:        https://example.com/my-plugin
 * Description:       A WordPress plugin.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-plugin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MY_PLUGIN_VERSION', '1.0.0' );
define( 'MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Optional: define MY_PLUGIN_RESEND_API_KEY for step-3 quote emails (see includes/resend-local.php.example).
$resend_local = MY_PLUGIN_PATH . 'includes/resend-local.php';
if ( is_readable( $resend_local ) ) {
	require_once $resend_local;
}

require_once MY_PLUGIN_PATH . 'includes/class-my-plugin.php';

function my_plugin_run() {
	$plugin = new My_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'my_plugin_run' );

register_activation_hook( __FILE__, array( 'My_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'My_Plugin', 'deactivate' ) );
