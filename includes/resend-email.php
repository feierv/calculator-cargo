<?php
/**
 * Send HTML email via Resend HTTP API (server-side only).
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read optional local Resend config from plugin root:
 * - .resend-local.php (returns array with keys: api_key, from)
 * Used only as a developer convenience for localhost.
 *
 * @return array{api_key:string,from:string}
 */
function my_plugin_get_local_resend_config() {
	static $cfg = null;
	if ( null !== $cfg ) {
		return $cfg;
	}
	$cfg = array(
		'api_key' => '',
		'from'    => '',
	);
	$path = dirname( __DIR__ ) . '/.resend-local.php';
	if ( ! is_readable( $path ) ) {
		return $cfg;
	}
	$data = include $path;
	if ( is_array( $data ) ) {
		if ( isset( $data['api_key'] ) && is_string( $data['api_key'] ) ) {
			$cfg['api_key'] = trim( $data['api_key'] );
		}
		if ( isset( $data['from'] ) && is_string( $data['from'] ) ) {
			$cfg['from'] = trim( $data['from'] );
		}
	}
	return $cfg;
}

/**
 * Resend API key: MY_PLUGIN_RESEND_API_KEY, getenv, or filter.
 *
 * @return string
 */
function my_plugin_get_resend_api_key() {
	if ( defined( 'MY_PLUGIN_RESEND_API_KEY' ) && is_string( MY_PLUGIN_RESEND_API_KEY ) && MY_PLUGIN_RESEND_API_KEY !== '' ) {
		return apply_filters( 'my_plugin_resend_api_key', MY_PLUGIN_RESEND_API_KEY );
	}
	$env = getenv( 'MY_PLUGIN_RESEND_API_KEY' );
	if ( is_string( $env ) && $env !== '' ) {
		return apply_filters( 'my_plugin_resend_api_key', $env );
	}
	$local = my_plugin_get_local_resend_config();
	if ( ! empty( $local['api_key'] ) ) {
		return apply_filters( 'my_plugin_resend_api_key', $local['api_key'] );
	}
	return apply_filters( 'my_plugin_resend_api_key', '' );
}

/**
 * “From” address for Resend (must be verified in Resend for your domain).
 *
 * Priority: MY_PLUGIN_RESEND_FROM → getenv MY_PLUGIN_RESEND_FROM → config resend_from_email → default.
 *
 * @return string
 */
function my_plugin_get_resend_from_header() {
	if ( defined( 'MY_PLUGIN_RESEND_FROM' ) && is_string( MY_PLUGIN_RESEND_FROM ) && MY_PLUGIN_RESEND_FROM !== '' ) {
		return apply_filters( 'my_plugin_resend_from_email', MY_PLUGIN_RESEND_FROM );
	}
	$env = getenv( 'MY_PLUGIN_RESEND_FROM' );
	if ( is_string( $env ) && $env !== '' ) {
		return apply_filters( 'my_plugin_resend_from_email', $env );
	}
	$local = my_plugin_get_local_resend_config();
	if ( ! empty( $local['from'] ) ) {
		return apply_filters( 'my_plugin_resend_from_email', $local['from'] );
	}
	$default_fallback = 'DVG Cargo <onboarding@resend.dev>';
	$from             = my_plugin_config( 'resend_from_email', '' );
	if ( ! is_string( $from ) || $from === '' ) {
		$from = $default_fallback;
	}
	return apply_filters( 'my_plugin_resend_from_email', $from );
}

/**
 * Send one HTML email via Resend.
 *
 * @param string $to       Recipient email.
 * @param string $subject  Subject line.
 * @param string $html     HTML body.
 * @param string $reply_to Optional Reply-To (e.g. client email).
 * @return true|\WP_Error
 */
function my_plugin_send_html_email_via_resend( $to, $subject, $html, $reply_to = '' ) {
	$key = my_plugin_get_resend_api_key();
	if ( '' === $key ) {
		return new WP_Error( 'my_plugin_resend_no_key', __( 'Cheia Resend nu este configurată.', 'my-plugin' ) );
	}
	if ( ! is_email( $to ) ) {
		return new WP_Error( 'my_plugin_resend_bad_to', __( 'Destinatar invalid.', 'my-plugin' ) );
	}

	$payload = array(
		'from'    => my_plugin_get_resend_from_header(),
		'to'      => array( $to ),
		'subject' => $subject,
		'html'    => $html,
	);
	if ( is_string( $reply_to ) && $reply_to !== '' && is_email( $reply_to ) ) {
		$payload['reply_to'] = array( $reply_to );
	}

	$response = wp_remote_post(
		'https://api.resend.com/emails',
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( $code >= 200 && $code < 300 ) {
		return true;
	}

	$msg = __( 'Resend a respins trimiterea.', 'my-plugin' );
	if ( is_array( $data ) && ! empty( $data['message'] ) ) {
		$msg = (string) $data['message'];
	} elseif ( is_string( $body ) && $body !== '' ) {
		$msg = $body;
	}

	return new WP_Error( 'my_plugin_resend_http', $msg, array( 'status' => $code ) );
}
