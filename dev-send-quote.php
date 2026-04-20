<?php
/**
 * Trimitere cotație pentru test local (fără admin-ajax + nonce WordPress).
 *
 * Permis doar de la 127.0.0.1 / ::1. Încarcă WordPress din același proiect sau din MY_PLUGIN_WP_ROOT / .wp-root-local.php.
 *
 * Rulare: din folderul pluginului — php -S localhost:8080 — apoi deschide calculator-preview.html.
 *
 * @package My_Plugin
 */

if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
	header( 'Content-Type: application/json; charset=UTF-8' );
	http_response_code( 405 );
	echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'POST only' ) ) );
	exit;
}

$plugin_root = dirname( __FILE__ );
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
	header( 'Content-Type: application/json; charset=UTF-8' );
	http_response_code( 500 );
	echo json_encode(
		array(
			'success' => false,
			'data'    => array(
				'message' => 'Nu găsesc WordPress (wp-load.php). Setează MY_PLUGIN_WP_ROOT, sau fișierul .wp-root-local.php (o linie: calea către WordPress), sau instalează pluginul în wp-content/plugins și pornește php -S din acel folder.',
			),
		)
	);
	exit;
}

require_once $wp_root . '/wp-load.php';

header( 'Content-Type: application/json; charset=UTF-8' );

if ( ! function_exists( 'my_plugin_process_order_confirmation_from_post' ) ) {
	http_response_code( 500 );
	echo wp_json_encode(
		array(
			'success' => false,
			'data'    => array( 'message' => 'Activează pluginul în WordPress.' ),
		)
	);
	exit;
}

if ( ! function_exists( 'my_plugin_is_localhost_request' ) || ! my_plugin_is_localhost_request() ) {
	http_response_code( 403 );
	echo wp_json_encode(
		array(
			'success' => false,
			'data'    => array( 'message' => 'dev-send-quote.php: permis doar de pe localhost.' ),
		)
	);
	exit;
}

$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
if ( 'my_plugin_local_orders_list' === $action ) {
	if ( ! function_exists( 'my_plugin_local_orders_list' ) ) {
		http_response_code( 500 );
		echo wp_json_encode( array( 'success' => false, 'data' => array( 'message' => 'SQLite local orders not available.' ) ) );
		exit;
	}
	$type = isset( $_POST['transport_type'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_type'] ) ) : '';
	$rows = my_plugin_local_orders_list( $type, 500 );
	if ( is_wp_error( $rows ) ) {
		http_response_code( 500 );
		echo wp_json_encode( array( 'success' => false, 'data' => array( 'message' => $rows->get_error_message() ) ) );
		exit;
	}
	echo wp_json_encode( array( 'success' => true, 'data' => array( 'rows' => $rows ) ) );
	exit;
}

if ( 'my_plugin_local_orders_clear' === $action ) {
	if ( ! function_exists( 'my_plugin_local_orders_clear' ) ) {
		http_response_code( 500 );
		echo wp_json_encode( array( 'success' => false, 'data' => array( 'message' => 'SQLite local orders not available.' ) ) );
		exit;
	}
	$ok = my_plugin_local_orders_clear();
	if ( is_wp_error( $ok ) ) {
		http_response_code( 500 );
		echo wp_json_encode( array( 'success' => false, 'data' => array( 'message' => $ok->get_error_message() ) ) );
		exit;
	}
	echo wp_json_encode( array( 'success' => true, 'data' => array( 'message' => 'Comenzile locale au fost șterse.' ) ) );
	exit;
}

$result = my_plugin_process_order_confirmation_from_post( wp_unslash( $_POST ), array( 'skip_nonce' => true ) );

if ( is_wp_error( $result ) ) {
	$status = 400;
	if ( $result->get_error_code() === 'send_failed' ) {
		$status = 500;
	}
	if ( $result->get_error_code() === 'nonce' ) {
		$status = 403;
	}
	http_response_code( $status );
	echo wp_json_encode( array( 'success' => false, 'data' => array( 'message' => $result->get_error_message() ) ) );
	exit;
}

echo wp_json_encode( array( 'success' => true, 'data' => $result ) );
