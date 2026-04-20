<?php
/**
 * Preview local pentru template-ul email de confirmare comandă.
 * Deschide în browser: http://localhost:8080/preview-email-template.php
 */

$plugin_root = __DIR__;
$wp_root     = '';

$env = getenv( 'MY_PLUGIN_WP_ROOT' );
if ( is_string( $env ) && $env !== '' && is_readable( rtrim( $env, '/' ) . '/wp-load.php' ) ) {
	$wp_root = rtrim( $env, '/' );
}

if ( $wp_root === '' && is_readable( $plugin_root . '/.wp-root-local.php' ) ) {
	$lines = file( $plugin_root . '/.wp-root-local.php', FILE_IGNORE_NEW_LINES );
	if ( is_array( $lines ) ) {
		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( $line === '' || ( isset( $line[0] ) && '#' === $line[0] ) ) {
				continue;
			}
			if ( is_readable( rtrim( $line, '/' ) . '/wp-load.php' ) ) {
				$wp_root = rtrim( $line, '/' );
				break;
			}
		}
	}
}

if ( $wp_root === '' ) {
	$dir = $plugin_root;
	for ( $i = 0; $i < 12; $i++ ) {
		if ( is_readable( $dir . '/wp-load.php' ) ) {
			$wp_root = $dir;
			break;
		}
		$parent = dirname( $dir );
		if ( $parent === $dir ) {
			break;
		}
		$dir = $parent;
	}
}

if ( $wp_root === '' || ! is_readable( $wp_root . '/wp-load.php' ) ) {
	http_response_code( 500 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo "Nu găsesc WordPress (wp-load.php).\nSetează MY_PLUGIN_WP_ROOT sau .wp-root-local.php.";
	exit;
}

require_once $wp_root . '/wp-load.php';

if ( ! function_exists( 'my_plugin_build_order_confirmation_email_html' ) || ! function_exists( 'my_plugin_get_demo_order_confirmation_data' ) ) {
	http_response_code( 500 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Activează pluginul My Plugin în WordPress.';
	exit;
}

$data = my_plugin_get_demo_order_confirmation_data();
$html = my_plugin_build_order_confirmation_email_html( $data );

header( 'Content-Type: text/html; charset=UTF-8' );
echo $html;

