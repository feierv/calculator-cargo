<?php
/**
 * Plugin config – default values for shortcodes and features.
 * Edit this file to change defaults (e.g. countries) without touching shortcode attributes.
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default configuration for the shipping calculator and other plugin features.
 *
 * @return array
 */
function my_plugin_get_config() {
	$defaults = array(
		// Shipping calculator – default countries (shortcode [shipping_calculator]).
		'shipping_calculator_loading_country'  => 'China',
		'shipping_calculator_delivery_country' => 'Romania',

		// Adresa încărcării – default country (shortcode [adresa_incarcarii]).
		'adresa_incarcarii_default_country'   => 'China',

		// Email destinatar solicitări calculator (DVG-Cargo). Gol = folosește admin_email.
		'dvg_cargo_offer_recipient_email'     => '',

		// Pas 3 – cotație trimisă prin Resend (implicit).
		'order_confirmation_recipient_email'  => 'updeveloplab@gmail.com',

		// Resend „From”: ex. „DVG Cargo <noreply@domeniu-verificat.ro>”. Gol = onboarding@resend.dev (doar test).
		'resend_from_email'                   => '',

		// Add more defaults here as needed.
	);

	return apply_filters( 'my_plugin_config', $defaults );
}

/**
 * Get a single config value.
 *
 * @param string $key Config key.
 * @param mixed  $fallback Fallback if key not set.
 * @return mixed
 */
function my_plugin_config( $key, $fallback = null ) {
	$config = my_plugin_get_config();
	return array_key_exists( $key, $config ) ? $config[ $key ] : $fallback;
}

/**
 * Meta for the Excel file uploaded in Settings (path, URL, original name).
 *
 * @return array|null Keys: path, url, name, size, uploaded (timestamp). Null if none.
 */
function my_plugin_get_uploaded_excel_meta() {
	$data = get_option( 'my_plugin_excel_upload', null );
	if ( ! is_array( $data ) || empty( $data['path'] ) || ! file_exists( $data['path'] ) ) {
		return null;
	}
	return $data;
}

/**
 * Absolute filesystem path to the uploaded Excel file, for reading later.
 *
 * @return string Empty string if none.
 */
function my_plugin_get_uploaded_excel_path() {
	$meta = my_plugin_get_uploaded_excel_meta();
	return $meta && ! empty( $meta['path'] ) ? $meta['path'] : '';
}

/**
 * Email where DVG-Cargo offer requests are sent (filterable).
 *
 * @return string
 */
function my_plugin_get_dvg_offer_recipient_email() {
	$from_config = my_plugin_config( 'dvg_cargo_offer_recipient_email', '' );
	$email       = is_string( $from_config ) && is_email( $from_config )
		? $from_config
		: get_option( 'admin_email' );
	return apply_filters( 'my_plugin_dvg_cargo_recipient_email', $email );
}

/**
 * Destinatar pentru emailul de confirmare cotație (pasul 3 – calculator).
 *
 * @return string
 */
function my_plugin_get_order_confirmation_recipient_email() {
	$default = 'updeveloplab@gmail.com';
	$email   = my_plugin_config( 'order_confirmation_recipient_email', $default );
	if ( ! is_string( $email ) || ! is_email( $email ) ) {
		$email = $default;
	}
	return apply_filters( 'my_plugin_order_confirmation_recipient_email', $email );
}

/**
 * Whether to show the sidebar “Tarife Excel (test local)” block on the calculator.
 * By default: only on local / development (hidden on production).
 *
 * Override in wp-config.php:
 *   define( 'MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL', true );  // force show (e.g. staging)
 *   define( 'MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL', false ); // force hide
 *
 * Or: add_filter( 'my_plugin_show_local_excel_panel', '__return_true' );
 *
 * @return bool
 */
function my_plugin_should_show_local_excel_panel() {
	if ( defined( 'MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL' ) ) {
		return (bool) MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL;
	}

	$show = false;

	if ( function_exists( 'wp_get_environment_type' ) ) {
		$env = wp_get_environment_type();
		if ( in_array( $env, array( 'local', 'development' ), true ) ) {
			$show = true;
		}
	}

	if ( ! $show && isset( $_SERVER['HTTP_HOST'] ) ) {
		$host = strtolower( trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) );
		$host = preg_replace( '/:\d+$/', '', $host );
		if ( 'localhost' === $host || '127.0.0.1' === $host ) {
			$show = true;
		} elseif ( preg_match( '/\.(local|test)$/', $host ) ) {
			$show = true;
		}
	}

	return (bool) apply_filters( 'my_plugin_show_local_excel_panel', $show );
}
