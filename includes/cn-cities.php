<?php
/**
 * Lista orașelor / regiunilor China pentru câmpul „Alegeți oraș” la preluare (datalist).
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returnează numele (sortate), pentru autocomplete la încărcare China.
 * Filtru: my_plugin_cn_city_list
 *
 * @return string[]
 */
function my_plugin_get_cn_cities() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$path = MY_PLUGIN_PATH . 'includes/data/cn-cities.txt';
	if ( is_readable( $path ) ) {
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( is_array( $lines ) ) {
			$lines = array_map( 'trim', $lines );
			$lines = array_filter( $lines );
			$lines = array_unique( $lines );
			sort( $lines, SORT_NATURAL | SORT_FLAG_CASE );
			$cached = array_values( $lines );
			$cached = apply_filters( 'my_plugin_cn_city_list', $cached );
			return $cached;
		}
	}

	$cached = apply_filters( 'my_plugin_cn_city_list', my_plugin_get_cn_cities_fallback() );
	return $cached;
}

/**
 * Listă minimală dacă lipsește fișierul .txt.
 *
 * @return string[]
 */
function my_plugin_get_cn_cities_fallback() {
	return array(
		'Beijing',
		'Guangzhou',
		'Shanghai',
		'Shenzhen',
	);
}
