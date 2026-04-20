#!/usr/bin/env php
<?php
/**
 * Test local: trimite prin Resend același HTML ca la confirmarea din calculator (date demo).
 *
 * Rulare:
 *   php tools/test-resend-local.php /cale/către/wordpress
 *
 * Necesită WordPress cu pluginul activ. Cheie API: .resend-local.php (root) / includes/resend-local.php / MY_PLUGIN_RESEND_API_KEY.
 * Adresă „From”: MY_PLUGIN_RESEND_FROM, sau resend_from_email în config, sau implicit onboarding@resend.dev.
 *
 * @package My_Plugin
 */

if ( ! isset( $argv[1] ) ) {
	fwrite( STDERR, "Usage: php " . basename( __FILE__ ) . " /path/to/wordpress\n" );
	fwrite( STDERR, "Sends the quote email template (demo data) via Resend.\n" );
	exit( 1 );
}

$wp_root = rtrim( $argv[1], '/' );
$wp_load = $wp_root . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	fwrite( STDERR, "Nu găsesc wp-load.php la: $wp_load\n" );
	exit( 1 );
}

require_once $wp_load;

if ( ! function_exists( 'my_plugin_build_order_confirmation_email_html' ) ) {
	fwrite( STDERR, "Activează pluginul „My Plugin” în WordPress.\n" );
	exit( 1 );
}

if ( ! function_exists( 'my_plugin_send_html_email_via_resend' ) ) {
	fwrite( STDERR, "Lipsește modulul Resend al pluginului.\n" );
	exit( 1 );
}

$data = my_plugin_get_demo_order_confirmation_data();
$html = my_plugin_build_order_confirmation_email_html( $data );

$quote_ref = isset( $data['quote_ref'] ) ? $data['quote_ref'] : 'TEST';
$name      = isset( $data['nume_prenume'] ) ? $data['nume_prenume'] : __( 'Client', 'my-plugin' );
$subject   = sprintf( '[DVG-Cargo] Cotație %s – %s (test local)', $quote_ref, $name );

$to = getenv( 'MY_PLUGIN_TEST_TO' );
if ( ! is_string( $to ) || ! is_email( $to ) ) {
	$to = my_plugin_get_order_confirmation_recipient_email();
}

$client_email = isset( $data['client_email'] ) ? $data['client_email'] : '';

$result = my_plugin_send_html_email_via_resend( $to, $subject, $html, $client_email );

if ( is_wp_error( $result ) ) {
	fwrite( STDERR, 'Resend: ' . $result->get_error_message() . "\n" );
	exit( 1 );
}

$from = function_exists( 'my_plugin_get_resend_from_header' ) ? my_plugin_get_resend_from_header() : '';
echo "OK — trimis către: $to\n";
echo "Subiect: $subject\n";
if ( $from !== '' ) {
	echo "From: $from\n";
}
exit( 0 );
