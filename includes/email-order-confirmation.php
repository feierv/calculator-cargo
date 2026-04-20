<?php
/**
 * HTML email template for order confirmation (pasul 3 – emitere pe mail).
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build HTML body for order confirmation email (layout similar to DVG cotație).
 *
 * @param array $data Sanitized associative array of field values.
 * @return string
 */
function my_plugin_build_order_confirmation_email_html( array $data ) {
	$quote_ref = isset( $data['quote_ref'] ) ? $data['quote_ref'] : '';
	$h         = static function ( $s ) {
		return esc_html( is_string( $s ) ? $s : '' );
	};

	$client_nume    = isset( $data['client_nume'] ) ? trim( (string) $data['client_nume'] ) : '';
	$client_prenume = isset( $data['client_prenume'] ) ? trim( (string) $data['client_prenume'] ) : '';

	$pink = '#D8005F';

	$section_header = static function ( $title ) use ( $pink, $h ) {
		return sprintf(
			'<tr><td colspan="2" style="background:%s;color:#ffffff;font-weight:700;font-size:14px;padding:10px 14px;font-family:Arial,Helvetica,sans-serif;">%s</td></tr>',
			esc_attr( $pink ),
			$h( $title )
		);
	};

	$row = static function ( $label, $value ) use ( $h ) {
		return sprintf(
			'<tr><td style="padding:8px 14px;border-bottom:1px solid #eeeeee;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#333333;width:42%%;">%s</td><td style="padding:8px 14px;border-bottom:1px solid #eeeeee;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#111111;font-weight:700;">%s</td></tr>',
			$h( $label ),
			$h( $value )
		);
	};

	$tip_serviciu = isset( $data['transport_label'] ) ? $data['transport_label'] : '';

	ob_start();
	?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f5f5f5;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f5f5f5;padding:24px 12px;">
	<tr>
		<td align="center">
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
				<tr>
					<td style="padding:24px 20px 8px;text-align:center;font-family:Arial,Helvetica,sans-serif;">
						<p style="margin:0 0 8px;font-size:18px;font-weight:700;color:#111111;"><?php echo $h( sprintf( /* translators: %s: quote reference */ __( 'Cotația nr. %s', 'my-plugin' ), $quote_ref ) ); ?></p>
						<p style="margin:0;font-size:13px;color:#555555;"><?php echo esc_html__( 'Tipul serviciului:', 'my-plugin' ); ?></p>
						<p style="margin:8px 0 0;font-size:16px;font-weight:700;color:#111111;"><?php echo $h( $tip_serviciu ); ?></p>
						<p style="margin:12px 0 0;font-size:12px;color:#666666;line-height:1.45;"><?php echo esc_html__( 'Rezumat din pasul Confirmare (datele introduse în calculator).', 'my-plugin' ); ?></p>
					</td>
				</tr>
				<tr><td style="padding:0 16px;"><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
					<?php echo $section_header( __( 'Date de contact (client)', 'my-plugin' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					echo $row( __( 'Tip persoană', 'my-plugin' ), isset( $data['tip_persoana'] ) ? $data['tip_persoana'] : '—' );
					if ( $client_nume !== '' || $client_prenume !== '' ) {
						echo $row( __( 'Nume', 'my-plugin' ), $client_nume !== '' ? $client_nume : '—' );
						echo $row( __( 'Prenume', 'my-plugin' ), $client_prenume !== '' ? $client_prenume : '—' );
					} elseif ( ! empty( $data['nume_prenume'] ) ) {
						echo $row( __( 'Nume și prenume', 'my-plugin' ), $data['nume_prenume'] );
					} else {
						echo $row( __( 'Nume', 'my-plugin' ), '—' );
						echo $row( __( 'Prenume', 'my-plugin' ), '—' );
					}
					if ( ! empty( $data['companie'] ) ) {
						echo $row( __( 'Companie', 'my-plugin' ), $data['companie'] );
					}
					echo $row( __( 'Telefon', 'my-plugin' ), isset( $data['client_telefon'] ) ? $data['client_telefon'] : '—' );
					echo $row( __( 'Email', 'my-plugin' ), isset( $data['client_email'] ) ? $data['client_email'] : '—' );
					echo $row( __( 'Adresă livrare', 'my-plugin' ), isset( $data['client_adresa'] ) && $data['client_adresa'] !== '' ? $data['client_adresa'] : '—' );
					?>
					<?php echo $section_header( __( 'Marfă — volum și greutate', 'my-plugin' ) ); ?>
					<?php
					$g_decl = isset( $data['cargo_greutate_declarata'] ) ? trim( (string) $data['cargo_greutate_declarata'] ) : '';
					echo $row( __( 'Tip de marfă', 'my-plugin' ), isset( $data['cargo_tip'] ) ? $data['cargo_tip'] : '—' );
					echo $row( __( 'Volum (declarat)', 'my-plugin' ), isset( $data['cargo_volum'] ) ? $data['cargo_volum'] : '—' );
					echo $row( __( 'Greutate declarată de client', 'my-plugin' ), $g_decl !== '' ? $g_decl : '—' );
					echo $row( __( 'Volum tarifabil', 'my-plugin' ), isset( $data['cargo_volum_taxabil'] ) ? $data['cargo_volum_taxabil'] : '—' );
					echo $row( __( 'Greutate tarifabilă (chargeable)', 'my-plugin' ), isset( $data['cargo_greutate'] ) ? $data['cargo_greutate'] : '—' );
					?>
					<?php echo $section_header( __( 'Traseu', 'my-plugin' ) ); ?>
					<?php
					echo $row( __( 'Condiții de livrare INCOTERMS', 'my-plugin' ), isset( $data['route_incoterms'] ) ? $data['route_incoterms'] : '—' );
					echo $row( __( 'Punctul de încărcare', 'my-plugin' ), isset( $data['route_loading'] ) ? $data['route_loading'] : '—' );
					echo $row( __( 'Punctul de descărcare', 'my-plugin' ), isset( $data['route_delivery'] ) ? $data['route_delivery'] : '—' );
					?>
					<?php echo $section_header( __( 'Date furnizor', 'my-plugin' ) ); ?>
					<?php
					echo $row( __( 'Provincie', 'my-plugin' ), isset( $data['supplier_provincie'] ) ? $data['supplier_provincie'] : '—' );
					echo $row( __( 'Oraș', 'my-plugin' ), isset( $data['supplier_oras'] ) ? $data['supplier_oras'] : '—' );
					echo $row( __( 'Adresa', 'my-plugin' ), isset( $data['supplier_adresa'] ) ? $data['supplier_adresa'] : '—' );
					echo $row( __( 'Numărul persoanei de contact', 'my-plugin' ), isset( $data['supplier_telefon'] ) ? $data['supplier_telefon'] : '—' );
					echo $row( __( 'Email persoană de contact', 'my-plugin' ), isset( $data['supplier_email'] ) ? $data['supplier_email'] : '—' );
					if ( ! empty( $data['supplier_observatii'] ) ) {
						echo $row( __( 'Observații', 'my-plugin' ), $data['supplier_observatii'] );
					}
					?>
					<?php echo $section_header( __( 'Tarife', 'my-plugin' ) ); ?>
					<tr>
						<td colspan="2" style="padding:12px 14px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#333333;line-height:1.7;">
							<?php
							$lines = isset( $data['tarife_lines'] ) && is_array( $data['tarife_lines'] ) ? $data['tarife_lines'] : array();
							$i     = 1;
							foreach ( $lines as $line ) {
								if ( ! is_array( $line ) ) {
									continue;
								}
								$lbl = isset( $line['label'] ) ? (string) $line['label'] : '';
								$eur = isset( $line['eur'] ) ? (string) $line['eur'] : '';
								if ( '' === $lbl && '' === $eur ) {
									continue;
								}
								echo '<p style="margin:0 0 6px;">' . esc_html( (string) $i ) . '. ' . esc_html( $lbl ) . ': <strong>' . esc_html( $eur ) . '</strong></p>';
								++$i;
							}
							if ( $i === 1 ) {
								echo '<p style="margin:0;">' . esc_html__( '—', 'my-plugin' ) . '</p>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="border-top:2px solid #dddddd;padding:14px;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:15px;">
							<strong><?php echo esc_html__( 'Cost total:', 'my-plugin' ); ?></strong>
							<strong style="color:<?php echo esc_attr( $pink ); ?>;"><?php echo $h( isset( $data['total_eur'] ) ? $data['total_eur'] : '—' ); ?></strong>
						</td>
					</tr>
				</table></td></tr>
				<tr>
					<td style="padding:16px 20px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:<?php echo esc_attr( $pink ); ?>;line-height:1.5;">
						<?php echo esc_html__( 'Termenii de livrare: Maritim 60-70 zile; Feroviar 25-30 zile; Aerian 8-12 zile.', 'my-plugin' ); ?>
					</td>
				</tr>
				<?php if ( ! empty( $data['page_url'] ) ) : ?>
				<tr>
					<td style="padding:0 20px 20px;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#888888;">
						<?php echo esc_html__( 'Pagină:', 'my-plugin' ); ?> <?php echo esc_html( $data['page_url'] ); ?>
					</td>
				</tr>
				<?php endif; ?>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
	<?php
	return (string) ob_get_clean();
}

/**
 * Sample data for testing the order confirmation HTML email (admin preview / test send).
 *
 * @return array
 */
function my_plugin_get_demo_order_confirmation_data() {
	$data = array(
		'quote_ref'           => 'TEST-' . gmdate( 'Ymd' ) . '-001',
		'transport_label'     => __( 'Transport Internațional (freight) China-România (Feroviar)', 'my-plugin' ),
		'tip_persoana'        => __( 'Persoană fizică', 'my-plugin' ),
		'client_nume'         => 'Ion',
		'client_prenume'      => 'Popescu',
		'nume_prenume'        => 'Ion Popescu',
		'companie'            => '—',
		'client_adresa'       => 'București, Str. Exemplu nr. 10, cod 010101',
		'client_telefon'      => '+40 722 000 000',
		'client_email'        => 'client@exemplu.ro',
		'supplier_provincie'  => 'Guangdong',
		'supplier_oras'       => 'Shenzhen',
		'supplier_adresa'     => 'Zone industriale – adresă demo',
		'supplier_telefon'    => '+86 138 0000 0000',
		'supplier_email'      => 'supplier@exemplu.cn',
		'supplier_observatii' => __( 'Observații demo pentru test.', 'my-plugin' ),
		'cargo_tip'           => __( 'Generală', 'my-plugin' ),
		'cargo_volum'         => '2.50 m³',
		'cargo_volum_taxabil' => '2.50 m³',
		'cargo_greutate_declarata' => '400 kg',
		'cargo_greutate'      => '450 kg',
		'route_incoterms'     => 'EXW (Ex Works)',
		'route_loading'       => 'China, Shenzhen',
		'route_delivery'      => 'România, București',
		'total_eur'           => '€ 968',
		'page_url'            => home_url( '/' ),
		'tarife_lines'        => array(
			array(
					'label' => __( 'Transport Internațional (freight) China-România', 'my-plugin' ),
				'eur'   => '365 Euro',
			),
			array(
				'label' => __( 'Servicii logistice locale CN (EXW)', 'my-plugin' ),
				'eur'   => '417 Euro',
			),
			array(
				'label' => __( 'Servicii logistice locale RO — Livrare door to door (TVA nu este inclus în preț)', 'my-plugin' ),
				'eur'   => '76 Euro',
			),
			array(
				'label' => __( 'Perfectare cod EORI (la cerere, TVA nu este inclus în preț)', 'my-plugin' ),
				'eur'   => '50 Euro',
			),
			array(
				'label' => __( 'Perfectare declarație de import (100 Euro / 2 coduri HS, TVA nu este inclus în preț)', 'my-plugin' ),
				'eur'   => '60 Euro',
			),
		),
	);

	return apply_filters( 'my_plugin_demo_order_confirmation_data', $data );
}
