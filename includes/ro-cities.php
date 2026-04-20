<?php
/**
 * Lista orașelor din România pentru câmpul „Alegeți oraș” (datalist).
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returnează numele orașelor (ordinea din ro-cities.txt), pentru autocomplete la livrare în RO.
 * Filtru: my_plugin_ro_city_list
 *
 * @return string[]
 */
function my_plugin_get_ro_cities() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$path = MY_PLUGIN_PATH . 'includes/data/ro-cities.txt';
	if ( is_readable( $path ) ) {
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( is_array( $lines ) ) {
			$lines   = array_map( 'trim', $lines );
			$lines   = array_filter( $lines );
			$ordered = array();
			$seen    = array();
			foreach ( $lines as $line ) {
				if ( '' === $line || isset( $seen[ $line ] ) ) {
					continue;
				}
				$seen[ $line ]   = true;
				$ordered[]      = $line;
			}
			$cached = apply_filters( 'my_plugin_ro_city_list', $ordered );
			return $cached;
		}
	}

	$cached = apply_filters( 'my_plugin_ro_city_list', my_plugin_get_ro_cities_fallback() );
	return $cached;
}

/**
 * Listă minimală dacă lipsește fișierul .txt.
 *
 * @return string[]
 */
function my_plugin_get_ro_cities_fallback() {
	return array(
		'București',
		'Timișoara',
		'Cluj-Napoca',
		'Craiova',
		'Iași',
		'Arad',
		'Brașov',
		'Galați',
		'Satu Mare',
		'Oradea',
		'Târgu Mureș',
		'Slatina',
		'Odorheiu Secuiesc',
		'Ribița',
		'Sibiu',
		'Deva',
		'Alba Iulia',
		'Tg. Mureș',
	);
}
