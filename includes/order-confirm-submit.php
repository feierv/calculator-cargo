<?php
/**
 * Procesare trimitere cotație (pas 3) — folosit de AJAX și de dev-send-quote.php (localhost).
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the HTTP request is from localhost (IPv4/IPv6 loopback).
 *
 * @return bool
 */
function my_plugin_is_localhost_request() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return in_array( $ip, array( '127.0.0.1', '::1' ), true );
}

/**
 * Build and send order confirmation email from POST-like array.
 *
 * @param array $post        Unslashed POST data (same keys as AJAX).
 * @param array $args        { skip_nonce: bool } — only true from dev-send-quote.php after localhost check.
 * @return array|\WP_Error   On success: array with keys message, quote_ref. On failure: WP_Error.
 */
function my_plugin_process_order_confirmation_from_post( array $post, array $args = array() ) {
	$skip_nonce = ! empty( $args['skip_nonce'] );

	if ( ! $skip_nonce ) {
		$nonce = isset( $post['nonce'] ) ? sanitize_text_field( $post['nonce'] ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'my_plugin_order_confirm' ) ) {
			return new WP_Error( 'nonce', __( 'Sesiune expirată. Reîncărcați pagina și încercați din nou.', 'my-plugin' ) );
		}
	}

	$sanitize = static function ( $key ) use ( $post ) {
		return isset( $post[ $key ] ) ? sanitize_text_field( $post[ $key ] ) : '';
	};
	$sanitize_area = static function ( $key ) use ( $post ) {
		return isset( $post[ $key ] ) ? sanitize_textarea_field( $post[ $key ] ) : '';
	};

	$tarife_lines = array();
	if ( isset( $post['tarife_lines'] ) && is_string( $post['tarife_lines'] ) ) {
		$decoded = json_decode( $post['tarife_lines'], true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $line ) {
				if ( ! is_array( $line ) ) {
					continue;
				}
				$tarife_lines[] = array(
					'label' => isset( $line['label'] ) ? sanitize_text_field( $line['label'] ) : '',
					'eur'   => isset( $line['eur'] ) ? sanitize_text_field( $line['eur'] ) : '',
				);
			}
		}
	}

	$quote_ref = 'C' . strtoupper( wp_generate_password( 10, false, false ) );

	$data = array(
		'quote_ref'                => $quote_ref,
		'transport_label'          => $sanitize( 'transport_label' ),
		'tip_persoana'             => $sanitize( 'tip_persoana' ),
		'client_nume'              => $sanitize( 'client_nume' ),
		'client_prenume'           => $sanitize( 'client_prenume' ),
		'nume_prenume'             => $sanitize( 'nume_prenume' ),
		'companie'                 => $sanitize( 'companie' ),
		'client_adresa'            => $sanitize_area( 'client_adresa' ),
		'client_telefon'           => $sanitize( 'client_telefon' ),
		'client_email'             => isset( $post['client_email'] ) ? sanitize_email( $post['client_email'] ) : '',
		'supplier_provincie'       => $sanitize( 'supplier_provincie' ),
		'supplier_oras'            => $sanitize( 'supplier_oras' ),
		'supplier_adresa'          => $sanitize_area( 'supplier_adresa' ),
		'supplier_telefon'         => $sanitize( 'supplier_telefon' ),
		'supplier_email'           => isset( $post['supplier_email'] ) ? sanitize_email( $post['supplier_email'] ) : '',
		'supplier_observatii'      => $sanitize_area( 'supplier_observatii' ),
		'cargo_tip'                => $sanitize( 'cargo_tip' ),
		'cargo_volum'              => $sanitize( 'cargo_volum' ),
		'cargo_volum_taxabil'      => $sanitize( 'cargo_volum_taxabil' ),
		'cargo_greutate_declarata' => $sanitize( 'cargo_greutate_declarata' ),
		'cargo_greutate'           => $sanitize( 'cargo_greutate' ),
		'route_incoterms'          => $sanitize( 'route_incoterms' ),
		'route_loading'            => $sanitize_area( 'route_loading' ),
		'route_delivery'           => $sanitize_area( 'route_delivery' ),
		'tarife_lines'             => $tarife_lines,
		'total_eur'                => $sanitize( 'total_eur' ),
		'page_url'                 => isset( $post['page_url'] ) ? esc_url_raw( $post['page_url'] ) : '',
	);

	if ( empty( $data['client_email'] ) || ! is_email( $data['client_email'] ) ) {
		return new WP_Error( 'invalid_email', __( 'Adresa de email a clientului nu este validă.', 'my-plugin' ) );
	}

	$to = my_plugin_get_order_confirmation_recipient_email();
	if ( ! is_email( $to ) ) {
		return new WP_Error( 'config', __( 'Configurare email invalidă.', 'my-plugin' ) );
	}

	$html = my_plugin_build_order_confirmation_email_html( $data );

	$subject_client_name = isset( $data['nume_prenume'] ) && $data['nume_prenume'] !== ''
		? $data['nume_prenume']
		: trim( ( isset( $data['client_nume'] ) ? $data['client_nume'] : '' ) . ' ' . ( isset( $data['client_prenume'] ) ? $data['client_prenume'] : '' ) );
	if ( $subject_client_name === '' ) {
		$subject_client_name = __( 'Client', 'my-plugin' );
	}

	$subject = sprintf(
		/* translators: 1: quote ref, 2: client name */
		__( '[DVG-Cargo] Cotație %1$s – %2$s', 'my-plugin' ),
		$quote_ref,
		$subject_client_name
	);

	$result     = null;
	$resend_key = function_exists( 'my_plugin_get_resend_api_key' ) ? my_plugin_get_resend_api_key() : '';
	$sent       = false;
	$is_local   = function_exists( 'my_plugin_is_localhost_request' ) ? my_plugin_is_localhost_request() : false;
	$local_id   = null;
	if ( function_exists( 'my_plugin_local_orders_insert' ) ) {
		$insert = my_plugin_local_orders_insert( $data, 'queued_local', '' );
		if ( ! is_wp_error( $insert ) ) {
			$local_id = (int) $insert;
		}
	}

	if ( $resend_key ) {
		$result = my_plugin_send_html_email_via_resend( $to, $subject, $html, $data['client_email'] );
		$sent   = ( true === $result );
	} else {
		if ( $is_local ) {
			return new WP_Error(
				'send_failed',
				__(
					'Localhost: Resend nu este configurat. Setați MY_PLUGIN_RESEND_API_KEY / MY_PLUGIN_RESEND_FROM (env sau wp-config), ori creați fișierul .resend-local.php în rădăcina pluginului.',
					'my-plugin'
				)
			);
		}
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, $html, $headers );
	}

	if ( ! $sent ) {
		$msg = __( 'Emailul nu a putut fi trimis. Încercați mai târziu.', 'my-plugin' );
		if ( $resend_key && is_wp_error( $result ) ) {
			$msg = $result->get_error_message();
		}
		if ( $local_id && function_exists( 'my_plugin_local_orders_update_status' ) ) {
			my_plugin_local_orders_update_status( $local_id, 'remote_error', $msg );
		}
		return new WP_Error( 'send_failed', $msg );
	}

	if ( $local_id && function_exists( 'my_plugin_local_orders_update_status' ) ) {
		my_plugin_local_orders_update_status( $local_id, 'remote_success', __( 'Trimis cu succes.', 'my-plugin' ) );
	}

	return array(
		'message'   => __( 'Comanda a fost înregistrată și cotația a fost trimisă pe email. Veți fi contactat în curând.', 'my-plugin' ),
		'quote_ref' => $quote_ref,
		'local_id'  => $local_id,
	);
}
