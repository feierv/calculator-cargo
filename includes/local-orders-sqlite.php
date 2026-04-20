<?php
/**
 * SQLite storage for local/admin order preview rows.
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

function my_plugin_sqlite_available() {
	return class_exists( 'PDO' ) && in_array( 'sqlite', \PDO::getAvailableDrivers(), true );
}

function my_plugin_local_orders_db_path() {
	return trailingslashit( MY_PLUGIN_PATH ) . 'data/local-orders.sqlite';
}

function my_plugin_local_orders_db() {
	if ( ! my_plugin_sqlite_available() ) {
		return new WP_Error( 'sqlite_unavailable', __( 'PDO SQLite nu este disponibil pe server.', 'my-plugin' ) );
	}
	$path = my_plugin_local_orders_db_path();
	$dir  = dirname( $path );
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'sqlite_dir', __( 'Nu pot crea directorul pentru baza locală.', 'my-plugin' ) );
	}
	try {
		$pdo = new PDO( 'sqlite:' . $path );
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$pdo->exec(
			'CREATE TABLE IF NOT EXISTS local_orders (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				created_at TEXT NOT NULL,
				local_status TEXT NOT NULL,
				remote_message TEXT NOT NULL,
				transport_type TEXT NOT NULL,
				transport_label TEXT NOT NULL,
				client_name TEXT NOT NULL,
				client_email TEXT NOT NULL,
				route_loading TEXT NOT NULL,
				route_delivery TEXT NOT NULL,
				total_eur TEXT NOT NULL,
				payload_json TEXT NOT NULL
			)'
		);
		return $pdo;
	} catch ( Exception $e ) {
		return new WP_Error( 'sqlite_open', $e->getMessage() );
	}
}

function my_plugin_local_orders_insert( array $payload, $status = 'queued_local', $message = '' ) {
	$db = my_plugin_local_orders_db();
	if ( is_wp_error( $db ) ) {
		return $db;
	}
	$transport_label = isset( $payload['transport_label'] ) ? sanitize_text_field( $payload['transport_label'] ) : '';
	$t               = strtolower( $transport_label );
	$transport_type  = 'Necunoscut';
	if ( false !== strpos( $t, 'feroviar' ) ) {
		$transport_type = 'Feroviar';
	} elseif ( false !== strpos( $t, 'maritim' ) ) {
		$transport_type = 'Maritim';
	} elseif ( false !== strpos( $t, 'aerian' ) ) {
		$transport_type = 'Aerian';
	} elseif ( false !== strpos( $t, 'rutier' ) ) {
		$transport_type = 'Rutier';
	}

	$created = gmdate( 'c' );
	$stmt    = $db->prepare(
		'INSERT INTO local_orders
		(created_at, local_status, remote_message, transport_type, transport_label, client_name, client_email, route_loading, route_delivery, total_eur, payload_json)
		VALUES (:created_at, :local_status, :remote_message, :transport_type, :transport_label, :client_name, :client_email, :route_loading, :route_delivery, :total_eur, :payload_json)'
	);
	$stmt->execute(
		array(
			':created_at'      => $created,
			':local_status'    => sanitize_text_field( $status ),
			':remote_message'  => sanitize_text_field( $message ),
			':transport_type'  => $transport_type,
			':transport_label' => $transport_label,
			':client_name'     => sanitize_text_field( isset( $payload['nume_prenume'] ) ? $payload['nume_prenume'] : '' ),
			':client_email'    => sanitize_email( isset( $payload['client_email'] ) ? $payload['client_email'] : '' ),
			':route_loading'   => sanitize_text_field( isset( $payload['route_loading'] ) ? $payload['route_loading'] : '' ),
			':route_delivery'  => sanitize_text_field( isset( $payload['route_delivery'] ) ? $payload['route_delivery'] : '' ),
			':total_eur'       => sanitize_text_field( isset( $payload['total_eur'] ) ? $payload['total_eur'] : '' ),
			':payload_json'    => wp_json_encode( $payload ),
		)
	);
	return (int) $db->lastInsertId();
}

function my_plugin_local_orders_update_status( $id, $status, $message = '' ) {
	$db = my_plugin_local_orders_db();
	if ( is_wp_error( $db ) ) {
		return $db;
	}
	$stmt = $db->prepare( 'UPDATE local_orders SET local_status = :s, remote_message = :m WHERE id = :id' );
	$stmt->execute(
		array(
			':s'  => sanitize_text_field( $status ),
			':m'  => sanitize_text_field( $message ),
			':id' => (int) $id,
		)
	);
	return true;
}

function my_plugin_local_orders_list( $transport_type = '', $limit = 300 ) {
	$db = my_plugin_local_orders_db();
	if ( is_wp_error( $db ) ) {
		return $db;
	}
	$limit = max( 1, min( 2000, (int) $limit ) );
	if ( $transport_type !== '' ) {
		$stmt = $db->prepare( 'SELECT * FROM local_orders WHERE transport_type = :tt ORDER BY id DESC LIMIT :lim' );
		$stmt->bindValue( ':tt', sanitize_text_field( $transport_type ), PDO::PARAM_STR );
		$stmt->bindValue( ':lim', $limit, PDO::PARAM_INT );
		$stmt->execute();
	} else {
		$stmt = $db->prepare( 'SELECT * FROM local_orders ORDER BY id DESC LIMIT :lim' );
		$stmt->bindValue( ':lim', $limit, PDO::PARAM_INT );
		$stmt->execute();
	}
	$rows = $stmt->fetchAll( PDO::FETCH_ASSOC );
	return is_array( $rows ) ? $rows : array();
}

function my_plugin_local_orders_clear() {
	$db = my_plugin_local_orders_db();
	if ( is_wp_error( $db ) ) {
		return $db;
	}
	$db->exec( 'DELETE FROM local_orders' );
	return true;
}

