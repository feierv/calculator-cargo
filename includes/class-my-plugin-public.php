<?php
/**
 * Frontend (public) logic: shortcodes, enqueue CSS/JS.
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class My_Plugin_Public
 */
class My_Plugin_Public {

	/**
	 * Register frontend hooks.
	 */
	public function __construct() {
		add_shortcode( 'adresa_incarcarii', array( $this, 'shortcode_adresa_incarcarii' ) );
		add_shortcode( 'shipping_calculator', array( $this, 'shortcode_shipping_calculator' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_nopriv_my_plugin_send_dvg_offer', array( $this, 'ajax_send_dvg_offer' ) );
		add_action( 'wp_ajax_my_plugin_send_dvg_offer', array( $this, 'ajax_send_dvg_offer' ) );
		add_action( 'wp_ajax_nopriv_my_plugin_confirm_order', array( $this, 'ajax_confirm_order' ) );
		add_action( 'wp_ajax_my_plugin_confirm_order', array( $this, 'ajax_confirm_order' ) );
		add_action( 'wp_ajax_nopriv_my_plugin_local_orders_list', array( $this, 'ajax_local_orders_list' ) );
		add_action( 'wp_ajax_my_plugin_local_orders_list', array( $this, 'ajax_local_orders_list' ) );
		add_action( 'wp_ajax_nopriv_my_plugin_local_orders_clear', array( $this, 'ajax_local_orders_clear' ) );
		add_action( 'wp_ajax_my_plugin_local_orders_clear', array( $this, 'ajax_local_orders_clear' ) );
	}

	/**
	 * Enqueue CSS and JS on the frontend only (not in admin).
	 * Calculator assets are enqueued here so they load before wp_head() and work
	 * in Elementor / other page builders that render shortcodes after the head.
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}
		$calculator_css_path = MY_PLUGIN_PATH . 'assets/css/calculator.css';
		$calculator_js_path  = MY_PLUGIN_PATH . 'assets/js/calculator.js';
		$calculator_css_ver  = file_exists( $calculator_css_path ) ? (string) filemtime( $calculator_css_path ) : MY_PLUGIN_VERSION;
		$calculator_js_ver   = file_exists( $calculator_js_path ) ? (string) filemtime( $calculator_js_path ) : MY_PLUGIN_VERSION;
		wp_enqueue_style(
			'my-plugin-public',
			MY_PLUGIN_URL . 'assets/css/public.css',
			array(),
			MY_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'my-plugin-public',
			MY_PLUGIN_URL . 'assets/js/public.js',
			array( 'jquery' ),
			MY_PLUGIN_VERSION,
			true
		);

		// Calculator: load on all frontend so shortcode works in Elementor etc.
		wp_enqueue_style(
			'my-plugin-calculator-font',
			'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'my-plugin-calculator',
			MY_PLUGIN_URL . 'assets/css/calculator.css',
			array( 'my-plugin-calculator-font' ),
			$calculator_css_ver
		);
		wp_enqueue_script(
			'my-plugin-calculator',
			MY_PLUGIN_URL . 'assets/js/calculator.js',
			array(),
			$calculator_js_ver,
			true
		);
		wp_localize_script(
			'my-plugin-calculator',
			'myPluginCalculatorL10n',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'my_plugin_dvg_offer' ),
				'action'          => 'my_plugin_send_dvg_offer',
				'successMessage'  => __( 'Solicitarea a fost trimisă cu succes. În cel mai scurt timp veți fi contactat de către un manager DVG-Cargo.', 'my-plugin' ),
				'errorGeneric'    => __( 'A apărut o eroare. Încercați din nou.', 'my-plugin' ),
				'errorValidation' => __( 'Completați Nume, Prenume, Telefon și acceptați termenii.', 'my-plugin' ),
				'sending'           => __( 'Se trimite…', 'my-plugin' ),
				'orderConfirmNonce' => wp_create_nonce( 'my_plugin_order_confirm' ),
				'orderConfirmAction' => 'my_plugin_confirm_order',
				'localOrdersListAction' => 'my_plugin_local_orders_list',
				'localOrdersClearAction' => 'my_plugin_local_orders_clear',
				'orderSuccessMessage' => __( 'Comanda a fost înregistrată și cotația a fost trimisă pe email. Veți fi contactat în curând.', 'my-plugin' ),
				'orderErrorGeneric' => __( 'Nu s-a putut trimite comanda. Încercați din nou.', 'my-plugin' ),
				'orderSending'      => __( 'Se trimite cotația…', 'my-plugin' ),
				'roCitiesJsonUrl'   => MY_PLUGIN_URL . 'assets/data/ro-cities.json',
				'cnCitiesJsonUrl'   => MY_PLUGIN_URL . 'assets/data/cn-cities.json',
			)
		);
	}

	/**
	 * AJAX: trimite solicitarea de ofertă către emailul DVG-Cargo.
	 */
	public function ajax_send_dvg_offer() {
		check_ajax_referer( 'my_plugin_dvg_offer', 'nonce' );

		$nume    = isset( $_POST['offer_nume'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_nume'] ) ) : '';
		$prenume = isset( $_POST['offer_prenume'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_prenume'] ) ) : '';
		$telefon = isset( $_POST['offer_telefon'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_telefon'] ) ) : '';
		$terms   = isset( $_POST['offer_terms'] ) && '1' === $_POST['offer_terms'];

		if ( '' === $nume || '' === $prenume || '' === $telefon || ! $terms ) {
			wp_send_json_error(
				array( 'message' => __( 'Completați Nume, Prenume, Telefon și acceptați termenii.', 'my-plugin' ) ),
				400
			);
		}

		$transport     = isset( $_POST['offer_transport'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_transport'] ) ) : '';
		$transport_price = isset( $_POST['offer_transport_price'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_transport_price'] ) ) : '';
		$vol_tax       = isset( $_POST['offer_vol_tax'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_vol_tax'] ) ) : '';
		$greutate      = isset( $_POST['offer_greutate'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_greutate'] ) ) : '';
		$incoterm      = isset( $_POST['offer_incoterm'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_incoterm'] ) ) : '';
		$load_addr     = isset( $_POST['offer_load_addr'] ) ? sanitize_textarea_field( wp_unslash( $_POST['offer_load_addr'] ) ) : '';
		$del_addr      = isset( $_POST['offer_del_addr'] ) ? sanitize_textarea_field( wp_unslash( $_POST['offer_del_addr'] ) ) : '';
		$page_url      = isset( $_POST['offer_page_url'] ) ? esc_url_raw( wp_unslash( $_POST['offer_page_url'] ) ) : '';

		$to = my_plugin_get_dvg_offer_recipient_email();
		if ( ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Configurare email invalidă. Contactați administratorul.', 'my-plugin' ) ), 500 );
		}

		$subject = sprintf(
			/* translators: 1: first name, 2: last name */
			__( '[DVG-Cargo] Solicitare ofertă – %1$s %2$s', 'my-plugin' ),
			$nume,
			$prenume
		);

		$body  = __( 'Nouă solicitare de ofertă (calculator site)', 'my-plugin' ) . "\n\n";
		$body .= __( 'Date contact', 'my-plugin' ) . "\n";
		$body .= __( 'Nume:', 'my-plugin' ) . ' ' . $nume . "\n";
		$body .= __( 'Prenume:', 'my-plugin' ) . ' ' . $prenume . "\n";
		$body .= __( 'Telefon:', 'my-plugin' ) . ' ' . $telefon . "\n\n";
		$body .= __( 'Detalii ofertă', 'my-plugin' ) . "\n";
		$body .= __( 'Transport selectat:', 'my-plugin' ) . ' ' . $transport . "\n";
		$body .= __( 'Preț afișat (EUR):', 'my-plugin' ) . ' ' . $transport_price . "\n";
		$body .= __( 'Volum taxabil (m³):', 'my-plugin' ) . ' ' . $vol_tax . "\n";
		$body .= __( 'Greutate (kg):', 'my-plugin' ) . ' ' . $greutate . "\n";
		$body .= __( 'INCOTERMS:', 'my-plugin' ) . ' ' . $incoterm . "\n";
		$body .= __( 'Adresă încărcare:', 'my-plugin' ) . ' ' . $load_addr . "\n";
		$body .= __( 'Adresă livrare:', 'my-plugin' ) . ' ' . $del_addr . "\n";
		if ( $page_url ) {
			$body .= "\n" . __( 'Pagină:', 'my-plugin' ) . ' ' . $page_url . "\n";
		}
		$body .= "\n—\n" . sprintf(
			/* translators: %s: blog name */
			__( 'Trimis de pe: %s', 'my-plugin' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $to, $subject, $body, $headers );

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'Emailul nu a putut fi trimis. Încercați mai târziu.', 'my-plugin' ) ), 500 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Solicitarea a fost trimisă cu succes. În cel mai scurt timp veți fi contactat de către un manager DVG-Cargo.', 'my-plugin' ),
			)
		);
	}

	/**
	 * AJAX: confirmare comandă pasul 3 – trimite email HTML cu cotația către DVG-Cargo.
	 */
	public function ajax_confirm_order() {
		if ( ! function_exists( 'my_plugin_process_order_confirmation_from_post' ) ) {
			wp_send_json_error( array( 'message' => __( 'Eroare internă.', 'my-plugin' ) ), 500 );
		}

		$result = my_plugin_process_order_confirmation_from_post( wp_unslash( $_POST ), array( 'skip_nonce' => false ) );

		if ( is_wp_error( $result ) ) {
			$status_map = array(
				'nonce'         => 403,
				'invalid_email' => 400,
				'config'        => 500,
				'send_failed'   => 500,
			);
			$code   = $result->get_error_code();
			$status = isset( $status_map[ $code ] ) ? $status_map[ $code ] : 400;
			wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: listă comenzi locale din SQLite (preview admin).
	 */
	public function ajax_local_orders_list() {
		$type = isset( $_POST['transport_type'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_type'] ) ) : '';
		$rows = my_plugin_local_orders_list( $type, 500 );
		if ( is_wp_error( $rows ) ) {
			wp_send_json_error( array( 'message' => $rows->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'rows' => $rows ) );
	}

	/**
	 * AJAX: șterge comenzile locale din SQLite (preview admin).
	 */
	public function ajax_local_orders_clear() {
		$ok = my_plugin_local_orders_clear();
		if ( is_wp_error( $ok ) ) {
			wp_send_json_error( array( 'message' => $ok->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'message' => __( 'Comenzile locale au fost șterse.', 'my-plugin' ) ) );
	}

	/**
	 * Shortcode [adresa_incarcarii] – "Adresa încărcării" section (frontend only).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_adresa_incarcarii( $atts ) {
		$atts = shortcode_atts( array(
			'country' => my_plugin_config( 'adresa_incarcarii_default_country', 'China' ),
		), $atts, 'adresa_incarcarii' );

		ob_start();
		?>
		<section class="my-plugin-adresa-incarcarii">
			<h2 class="my-plugin-adresa-incarcarii__title">Adresa încărcării</h2>
			<div class="my-plugin-adresa-incarcarii__field">
				<label for="my-plugin-country" class="my-plugin-adresa-incarcarii__label">Alegeți țara</label>
				<input
					type="text"
					id="my-plugin-country"
					class="my-plugin-adresa-incarcarii__input"
					value="<?php echo esc_attr( $atts['country'] ); ?>"
					placeholder="Alegeți țara"
					aria-label="<?php esc_attr_e( 'Alegeți țara', 'my-plugin' ); ?>"
				/>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode [shipping_calculator] – full calculator form (grupaj China).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_shipping_calculator( $atts ) {
		$atts = shortcode_atts( array(
			'loading_country'  => my_plugin_config( 'shipping_calculator_loading_country', 'China' ),
			'delivery_country' => my_plugin_config( 'shipping_calculator_delivery_country', 'Romania' ),
		), $atts, 'shipping_calculator' );
		$uid             = 'mpc-' . substr( uniqid( '', true ), -8 );
		$admin_excel_url = current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=my-plugin' ) : '';
		ob_start();
		?>
		<div class="my-plugin-calculator" id="<?php echo esc_attr( $uid ); ?>">
			<span class="mpc-splash mpc-splash-1" aria-hidden="true"></span>
			<span class="mpc-splash mpc-splash-2" aria-hidden="true"></span>
			<span class="mpc-splash mpc-splash-3" aria-hidden="true"></span>
			<div class="mpc-layout">
			<div class="mpc-inner">
				<nav class="mpc-progress" aria-label="<?php esc_attr_e( 'Pași formular', 'my-plugin' ); ?>">
					<div class="mpc-step mpc-step--active" data-step="1"><span class="mpc-step__circle">1</span><span class="mpc-step__label"><?php esc_html_e( 'Calculator', 'my-plugin' ); ?></span></div>
					<div class="mpc-step" data-step="2"><span class="mpc-step__circle">2</span><span class="mpc-step__label"><?php esc_html_e( 'Detalii', 'my-plugin' ); ?></span></div>
					<div class="mpc-step" data-step="3"><span class="mpc-step__circle">3</span><span class="mpc-step__label"><?php esc_html_e( 'Confirmare', 'my-plugin' ); ?></span></div>
				</nav>
				<div class="mpc-step1-panel">
				<header class="mpc-header">
					<h2 class="mpc-header__title"><?php esc_html_e( 'Calculați prețul transportului din China', 'my-plugin' ); ?></h2>
					<p><?php esc_html_e( 'Livrăm marfa începând de la 200 kg sau 1 m³', 'my-plugin' ); ?></p>
				</header>
				<div class="mpc-accent-strip"></div>
				<div class="mpc-address-row">
					<section class="mpc-section" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title-loading">
						<h3 id="<?php echo esc_attr( $uid ); ?>-title-loading" class="mpc-section-title"><?php esc_html_e( 'Adresa preluare', 'my-plugin' ); ?></h3>
						<div class="mpc-field">
							<label for="<?php echo esc_attr( $uid ); ?>-loading-country"><?php esc_html_e( 'Alegeți țara', 'my-plugin' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $uid ); ?>-loading-country" class="mpc-input" value="<?php echo esc_attr( $atts['loading_country'] ); ?>" placeholder="<?php esc_attr_e( 'Alegeți țara', 'my-plugin' ); ?>" />
						</div>
						<div class="mpc-field">
							<label for="<?php echo esc_attr( $uid ); ?>-loading-city"><?php esc_html_e( 'Alegeți oraș', 'my-plugin' ); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-loading-city" class="mpc-input mpc-input--neutral mpc-loading-city" autocomplete="off">
								<option value=""><?php esc_html_e( 'Selectați orașul', 'my-plugin' ); ?></option>
								<?php if ( function_exists( 'my_plugin_get_cn_cities' ) ) : ?>
									<?php foreach ( my_plugin_get_cn_cities() as $cn_city ) : ?>
										<option value="<?php echo esc_attr( $cn_city ); ?>"><?php echo esc_html( $cn_city ); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</section>
					<section class="mpc-section" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title-delivery">
						<h3 id="<?php echo esc_attr( $uid ); ?>-title-delivery" class="mpc-section-title"><?php esc_html_e( 'Adresa livrare', 'my-plugin' ); ?></h3>
						<div class="mpc-field">
							<label for="<?php echo esc_attr( $uid ); ?>-delivery-country"><?php esc_html_e( 'Alegeți țara', 'my-plugin' ); ?></label>
							<input type="text" id="<?php echo esc_attr( $uid ); ?>-delivery-country" class="mpc-input" value="<?php echo esc_attr( $atts['delivery_country'] ); ?>" placeholder="<?php esc_attr_e( 'Alegeți țara', 'my-plugin' ); ?>" />
						</div>
						<div class="mpc-field">
							<label for="<?php echo esc_attr( $uid ); ?>-delivery-city"><?php esc_html_e( 'Alegeți oraș', 'my-plugin' ); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-delivery-city" class="mpc-input mpc-input--neutral mpc-delivery-city" autocomplete="off">
								<option value=""><?php esc_html_e( 'Selectați orașul', 'my-plugin' ); ?></option>
								<?php if ( function_exists( 'my_plugin_get_ro_cities' ) ) : ?>
									<?php foreach ( my_plugin_get_ro_cities() as $ro_city ) : ?>
										<option value="<?php echo esc_attr( $ro_city ); ?>"><?php echo esc_html( $ro_city ); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</section>
				</div>
				<section class="mpc-section mpc-section--cargo" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title-cargo">
					<h3 id="<?php echo esc_attr( $uid ); ?>-title-cargo" class="mpc-section-title"><?php esc_html_e( 'Introduceți volumul total și greutatea', 'my-plugin' ); ?></h3>
					<div id="<?php echo esc_attr( $uid ); ?>-cargo-volume" class="mpc-cargo-panel mpc-cargo-by-volume">
						<div class="mpc-params-row">
							<div class="mpc-field">
								<label for="<?php echo esc_attr( $uid ); ?>-volume"><?php esc_html_e( 'Volum total (mc3)', 'my-plugin' ); ?></label>
								<input type="text" id="<?php echo esc_attr( $uid ); ?>-volume" class="mpc-input mpc-input--neutral mpc-input-volume" placeholder="ex: 4.00" inputmode="decimal" />
							</div>
							<div class="mpc-field">
								<label for="<?php echo esc_attr( $uid ); ?>-weight"><?php esc_html_e( 'Greutate (kg)', 'my-plugin' ); ?></label>
								<input type="text" id="<?php echo esc_attr( $uid ); ?>-weight" class="mpc-input mpc-input--neutral mpc-input-weight" placeholder="ex: 200" inputmode="numeric" />
							</div>
						</div>
					</div>
				</section>
				<section class="mpc-section" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title-incoterms">
					<div class="mpc-section-header">
						<h3 id="<?php echo esc_attr( $uid ); ?>-title-incoterms" class="mpc-section-title"><?php esc_html_e( 'Alegeți condiții INCOTERMS', 'my-plugin' ); ?></h3>
						<span class="mpc-help-icon" title="<?php esc_attr_e( 'Condiții de livrare internaționale', 'my-plugin' ); ?>">?</span>
					</div>
					<div class="mpc-toggle-group mpc-toggle-group--incoterms" role="group" aria-label="<?php esc_attr_e( 'INCOTERMS', 'my-plugin' ); ?>">
						<button type="button" class="mpc-toggle mpc-incoterm" data-mode="fob"><?php esc_html_e( 'FOB (Free On Board)', 'my-plugin' ); ?></button>
						<button type="button" class="mpc-toggle mpc-incoterm mpc-active" data-mode="exw"><?php esc_html_e( 'EXW (Ex Works)', 'my-plugin' ); ?></button>
					</div>
				</section>
				<div class="mpc-accent-strip"></div>
				<div class="mpc-submit-wrap">
					<button type="button" class="mpc-btn-calculate"><?php esc_html_e( 'Calculați', 'my-plugin' ); ?></button>
					<div class="mpc-warnings mpc-hidden" role="alert" aria-live="polite">
						<ul class="mpc-warnings-list"></ul>
					</div>
				</div>
				<!-- Rezultate (afișate după validare) – 4 opțiuni: Maritim, Feroviar, Aerian, Rutier -->
				<div class="mpc-results mpc-hidden">
					<div class="mpc-results-row">
						<div class="mpc-results-left">
							<!-- Vizualizare rezultate (volume + carduri + butoane) -->
							<div class="mpc-results-main">
								<div class="mpc-results-volume">
									<p class="mpc-results-vol-main"><?php esc_html_e( 'Volum taxabil:', 'my-plugin' ); ?> <strong class="mpc-results-vol-value">1.00</strong> m³</p>
									<p class="mpc-results-vol-details">
										<?php esc_html_e( 'Volum fizic:', 'my-plugin' ); ?> <span class="mpc-results-phys-value">1.00</span> m³<br>
										<?php esc_html_e( 'Volum în funcție de greutate:', 'my-plugin' ); ?> <span class="mpc-results-weight-vol-value">1.00</span> m³<br>
										<?php esc_html_e( 'Chargeable Weight (Aerian, IATA 1:6):', 'my-plugin' ); ?> <span class="mpc-results-air-weight-value">167</span> kg<br>
										<?php esc_html_e( 'Echivalent kg (300 kg/CBM, max V vs kg÷300):', 'my-plugin' ); ?> <span class="mpc-results-rail-weight-value">200</span> kg<br>
										<?php esc_html_e( 'CBM taxabil feroviar:', 'my-plugin' ); ?> <span class="mpc-results-rail-billable-cbm">1.00</span> m³
									</p>
								</div>
								<h3 class="mpc-results-transport-title">1. <?php esc_html_e( 'Transport Internațional (freight) China-România', 'my-plugin' ); ?></h3>
								<div class="mpc-results-cards">
									<div class="mpc-result-card mpc-result-card--air" data-transport-name="<?php echo esc_attr( __( 'Aerian', 'my-plugin' ) ); ?>" data-transport-label="<?php echo esc_attr( __( 'Transport Internațional (freight) China-România (Aerian)', 'my-plugin' ) ); ?>" data-transport-price="1500">
										<span class="mpc-result-card-icon" aria-hidden="true">✈️</span>
										<p class="mpc-result-card-name"><?php esc_html_e( 'Aerian', 'my-plugin' ); ?></p>
										<p class="mpc-result-card-price"><span class="mpc-result-card-amount">1500</span> €</p>
										<p class="mpc-result-card-delivery"><?php esc_html_e( 'Termen de livrare 7-12 de zile', 'my-plugin' ); ?></p>
										<button type="button" class="mpc-btn-choose"><?php esc_html_e( 'Alegeți', 'my-plugin' ); ?></button>
									</div>
									<div class="mpc-result-card mpc-result-card--rail" data-transport-name="<?php echo esc_attr( __( 'Feroviar', 'my-plugin' ) ); ?>" data-transport-label="<?php echo esc_attr( __( 'Transport Internațional (freight) China-România (Feroviar)', 'my-plugin' ) ); ?>" data-transport-price="365">
										<span class="mpc-result-card-icon" aria-hidden="true">🚂</span>
										<p class="mpc-result-card-name"><?php esc_html_e( 'Feroviar', 'my-plugin' ); ?></p>
										<p class="mpc-result-card-price"><span class="mpc-result-card-amount">365</span> €</p>
										<p class="mpc-result-card-delivery"><?php esc_html_e( 'Termen de livrare 25-35 de zile', 'my-plugin' ); ?></p>
										<button type="button" class="mpc-btn-choose"><?php esc_html_e( 'Alegeți', 'my-plugin' ); ?></button>
									</div>
									<div class="mpc-result-card mpc-result-card--sea" data-transport-name="<?php echo esc_attr( __( 'Maritim', 'my-plugin' ) ); ?>" data-transport-label="<?php echo esc_attr( __( 'Transport Internațional (freight) China-România (Maritim)', 'my-plugin' ) ); ?>" data-transport-price="158">
										<span class="mpc-result-card-icon" aria-hidden="true">🚢</span>
										<p class="mpc-result-card-name"><?php esc_html_e( 'Maritim', 'my-plugin' ); ?></p>
										<p class="mpc-result-card-price"><span class="mpc-result-card-amount">158</span> €</p>
										<p class="mpc-result-card-delivery"><?php esc_html_e( 'Termen de livrare 50-70 de zile', 'my-plugin' ); ?></p>
										<button type="button" class="mpc-btn-choose"><?php esc_html_e( 'Alegeți', 'my-plugin' ); ?></button>
									</div>
								</div>
								<div class="mpc-results-extras">
									<div class="mpc-results-section mpc-results-china">
										<h3 class="mpc-results-section-title">2. <?php esc_html_e( 'Servicii locale China (pentru EXW)', 'my-plugin' ); ?> <span class="mpc-help-icon" title="<?php esc_attr_e( 'Informații', 'my-plugin' ); ?>">?</span></h3>
										<div class="mpc-service-item mpc-service-item--included">
											<span class="mpc-service-name"><?php esc_html_e( 'Servicii locale China', 'my-plugin' ); ?> <span class="mpc-service-check" aria-hidden="true">✓</span></span>
											<span class="mpc-service-price mpc-service-price--included">417 € <?php esc_html_e( 'INCLUS', 'my-plugin' ); ?></span>
										</div>
									</div>
									<div class="mpc-results-section mpc-results-local">
										<h3 class="mpc-results-section-title"><span class="mpc-num-fob">2.</span><span class="mpc-num-exw">3.</span> <?php esc_html_e( 'Servicii locale România', 'my-plugin' ); ?> <span class="mpc-results-section-note">(<?php esc_html_e( 'TVA nu este inclus în preț', 'my-plugin' ); ?>)</span> <span class="mpc-help-icon" title="<?php esc_attr_e( 'Informații', 'my-plugin' ); ?>">?</span></h3>
										<div class="mpc-service-item mpc-service-item--toggle" data-service-id="door" data-service-price="76" data-service-label="<?php echo esc_attr( __( 'Livrare door to door', 'my-plugin' ) ); ?>">
											<span class="mpc-service-name"><?php esc_html_e( 'Livrare door to door', 'my-plugin' ); ?></span>
											<span class="mpc-service-price">76 €</span>
											<button type="button" class="mpc-btn-add-service"><?php esc_html_e( 'Adăugați', 'my-plugin' ); ?></button>
										</div>
									</div>
									<div class="mpc-results-section mpc-results-optional mpc-hidden" data-mpc-rail-sea-extras="1">
										<h3 class="mpc-results-section-title"><span class="mpc-num-fob">3.</span><span class="mpc-num-exw">4.</span> <?php esc_html_e( 'Servicii aditionale', 'my-plugin' ); ?></h3>
										<div class="mpc-service-item mpc-service-item--toggle mpc-service-item--disabled" data-service-id="portuare" data-service-price="700" data-service-label="<?php echo esc_attr( __( 'Prestatii portuare', 'my-plugin' ) ); ?>">
											<span class="mpc-service-name"><?php esc_html_e( 'Prestatii portuare', 'my-plugin' ); ?></span>
											<span class="mpc-service-price">700 €</span>
											<button type="button" class="mpc-btn-add-service" disabled><?php esc_html_e( 'Adăugați', 'my-plugin' ); ?></button>
										</div>
										<div class="mpc-service-item mpc-service-item--toggle" data-service-id="import" data-service-price="100" data-service-label="<?php echo esc_attr( __( 'Declarația de import', 'my-plugin' ) ); ?>" data-hs-package-size="2" data-hs-package-price="100">
											<span class="mpc-service-name"><?php esc_html_e( 'Declarația de import', 'my-plugin' ); ?></span>
											<span class="mpc-service-price">100 € / 2 coduri HS</span>
											<div class="mpc-import-hs-wrap">
												<label class="mpc-import-hs-label" for="<?php echo esc_attr( $uid ); ?>-import-hs"><?php esc_html_e( 'Număr coduri HS', 'my-plugin' ); ?></label>
												<input type="number" id="<?php echo esc_attr( $uid ); ?>-import-hs" class="mpc-import-hs-count" min="2" step="2" value="2" inputmode="numeric" />
											</div>
											<button type="button" class="mpc-btn-add-service"><?php esc_html_e( 'Adăugați', 'my-plugin' ); ?></button>
										</div>
									</div>
								</div>
								<div class="mpc-results-actions mpc-results-actions--continue-only">
									<button type="button" class="mpc-btn-continue"><?php esc_html_e( 'Continuați comanda', 'my-plugin' ); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				</div>
				<div class="mpc-step2-panel mpc-hidden">
					<button type="button" class="mpc-step2-back" aria-label="<?php esc_attr_e( 'Înapoi', 'my-plugin' ); ?>">← <?php esc_html_e( 'Înapoi', 'my-plugin' ); ?></button>
					<h2 class="mpc-step2-title"><?php esc_html_e( 'Detalii ofertă', 'my-plugin' ); ?></h2>
					<div class="mpc-step2-summary">
						<h3 class="mpc-step2-summary-title"><?php esc_html_e( 'Detalii ofertă', 'my-plugin' ); ?></h3>
						<dl class="mpc-summary-list">
							<dt><?php esc_html_e( 'Tip serviciu:', 'my-plugin' ); ?></dt><dd class="mpc-summary-tip-serviciu">—</dd>
							<dt><?php esc_html_e( 'Volum total:', 'my-plugin' ); ?></dt><dd class="mpc-summary-vol-total">—</dd>
							<dt><?php esc_html_e( 'Volum taxabil:', 'my-plugin' ); ?></dt><dd class="mpc-summary-vol-taxabil">—</dd>
							<dt><?php esc_html_e( 'Greutate totală:', 'my-plugin' ); ?></dt><dd class="mpc-summary-greutate">—</dd>
							<dt><?php esc_html_e( 'Condiții INCOTERMS:', 'my-plugin' ); ?></dt><dd class="mpc-summary-incoterms">—</dd>
							<dt><?php esc_html_e( 'Adresa încărcării:', 'my-plugin' ); ?></dt><dd class="mpc-summary-loading">—</dd>
							<dt><?php esc_html_e( 'Adresa livrării:', 'my-plugin' ); ?></dt><dd class="mpc-summary-delivery">—</dd>
						</dl>
					</div>
					<div class="mpc-step2-section">
						<h3 class="mpc-step2-section-title"><?php esc_html_e( 'Introduceți datele de contact', 'my-plugin' ); ?></h3>
						<div class="mpc-step2-fields">
							<div class="mpc-field"><label><?php esc_html_e( 'Nume', 'my-plugin' ); ?> *</label><input type="text" class="mpc-input mpc-input--neutral mpc-contact-nume" required /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Prenume', 'my-plugin' ); ?> *</label><input type="text" class="mpc-input mpc-input--neutral mpc-contact-prenume" required /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Telefon', 'my-plugin' ); ?> *</label><input type="tel" class="mpc-input mpc-input--neutral mpc-contact-telefon" required /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Email', 'my-plugin' ); ?> *</label><input type="email" class="mpc-input mpc-input--neutral mpc-contact-email" required /></div>
						</div>
					</div>
					<div class="mpc-step2-section">
						<h3 class="mpc-step2-section-title"><?php esc_html_e( 'Introduceți adresa de livrare și datele companiei', 'my-plugin' ); ?></h3>
						<div class="mpc-delivery-panel mpc-delivery-panel--fizica mpc-hidden">
							<div class="mpc-step2-fields mpc-step2-fields--two-cols">
								<div class="mpc-field"><label><?php esc_html_e( 'Județ', 'my-plugin' ); ?></label><select class="mpc-input mpc-input--neutral mpc-delivery-judet-fizica"><option value=""><?php esc_html_e( 'Selectați județul', 'my-plugin' ); ?></option></select></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Localitate', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-localitate-fizica" placeholder="<?php esc_attr_e( 'Localitate', 'my-plugin' ); ?>" /></div>
								<div class="mpc-field mpc-field--full"><label><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-adresa-fizica" placeholder="<?php esc_attr_e( 'Adresa', 'my-plugin' ); ?>" /></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Cod poștal', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-codpostal-fizica" placeholder="<?php esc_attr_e( 'Cod poștal', 'my-plugin' ); ?>" /></div>
							</div>
						</div>
						<div class="mpc-delivery-panel mpc-delivery-panel--juridica">
							<div class="mpc-step2-fields mpc-step2-fields--two-cols">
								<div class="mpc-field mpc-field--cui"><label><?php esc_html_e( 'CUI', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-cui" placeholder="<?php esc_attr_e( 'Introduceți CUI...', 'my-plugin' ); ?>" /><button type="button" class="mpc-btn-cui-fetch" aria-label="<?php esc_attr_e( 'Preia date', 'my-plugin' ); ?>">↓</button></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Companie', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-companie" placeholder="<?php esc_attr_e( 'Companie', 'my-plugin' ); ?>" /></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Județ', 'my-plugin' ); ?></label><select class="mpc-input mpc-input--neutral mpc-delivery-judet"><option value=""><?php esc_html_e( 'Selectați județul', 'my-plugin' ); ?></option></select></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Localitate', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-localitate" placeholder="<?php esc_attr_e( 'Localitate', 'my-plugin' ); ?>" /></div>
								<div class="mpc-field mpc-field--full"><label><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-adresa" placeholder="<?php esc_attr_e( 'Adresa', 'my-plugin' ); ?>" /></div>
								<div class="mpc-field"><label><?php esc_html_e( 'Cod poștal', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-delivery-codpostal" placeholder="<?php esc_attr_e( 'Cod poștal', 'my-plugin' ); ?>" /></div>
							</div>
						</div>
						<div class="mpc-field mpc-field--checkbox">
							<label><input type="checkbox" class="mpc-delivery-facturare-diferita" /> <?php esc_html_e( 'Doresc adresă de facturare diferită', 'my-plugin' ); ?></label>
						</div>
						<div class="mpc-billing-wrap mpc-hidden">
							<div class="mpc-billing-inner">
								<h3 class="mpc-step2-section-title"><?php esc_html_e( 'Introduceți adresa de facturare', 'my-plugin' ); ?></h3>
								<div class="mpc-delivery-panel mpc-billing-panel--fizica mpc-hidden">
									<div class="mpc-step2-fields mpc-step2-fields--two-cols">
										<div class="mpc-field"><label><?php esc_html_e( 'Județ', 'my-plugin' ); ?></label><select class="mpc-input mpc-input--neutral mpc-billing-judet-fizica"><option value=""><?php esc_html_e( 'Selectați județul', 'my-plugin' ); ?></option></select></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Localitate', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-localitate-fizica" placeholder="<?php esc_attr_e( 'Localitate', 'my-plugin' ); ?>" /></div>
										<div class="mpc-field mpc-field--full"><label><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-adresa-fizica" placeholder="<?php esc_attr_e( 'Adresa', 'my-plugin' ); ?>" /></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Cod poștal', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-codpostal-fizica" placeholder="<?php esc_attr_e( 'Cod poștal', 'my-plugin' ); ?>" /></div>
									</div>
								</div>
								<div class="mpc-delivery-panel mpc-billing-panel--juridica">
									<div class="mpc-step2-fields mpc-step2-fields--two-cols">
										<div class="mpc-field mpc-field--cui"><label><?php esc_html_e( 'CUI', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-cui" placeholder="<?php esc_attr_e( 'Introduceți CUI...', 'my-plugin' ); ?>" /><button type="button" class="mpc-btn-cui-fetch mpc-billing-cui-fetch" aria-label="<?php esc_attr_e( 'Preia date', 'my-plugin' ); ?>">↓</button></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Companie', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-companie" placeholder="<?php esc_attr_e( 'Companie', 'my-plugin' ); ?>" /></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Județ', 'my-plugin' ); ?></label><select class="mpc-input mpc-input--neutral mpc-billing-judet"><option value=""><?php esc_html_e( 'Selectați județul', 'my-plugin' ); ?></option></select></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Localitate', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-localitate" placeholder="<?php esc_attr_e( 'Localitate', 'my-plugin' ); ?>" /></div>
										<div class="mpc-field mpc-field--full"><label><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-adresa" placeholder="<?php esc_attr_e( 'Adresa', 'my-plugin' ); ?>" /></div>
										<div class="mpc-field"><label><?php esc_html_e( 'Cod poștal', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-billing-codpostal" placeholder="<?php esc_attr_e( 'Cod poștal', 'my-plugin' ); ?>" /></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="mpc-step2-section mpc-step2-supplier">
						<h3 class="mpc-step2-section-title"><?php esc_html_e( 'Introduceți adresa și datele de contact a furnizorului', 'my-plugin' ); ?></h3>
						<div class="mpc-step2-fields mpc-step2-fields--two-cols">
							<div class="mpc-field"><label><?php esc_html_e( 'Provincie', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-supplier-provincie" placeholder="<?php esc_attr_e( 'Provincie', 'my-plugin' ); ?>" /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Oraș', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-supplier-oras" placeholder="<?php esc_attr_e( 'Oraș', 'my-plugin' ); ?>" /></div>
							<div class="mpc-field mpc-field--full"><label><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-supplier-adresa" placeholder="<?php esc_attr_e( 'Adresa', 'my-plugin' ); ?>" /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Cod poștal', 'my-plugin' ); ?></label><input type="text" class="mpc-input mpc-input--neutral mpc-supplier-codpostal" placeholder="<?php esc_attr_e( 'Cod poștal', 'my-plugin' ); ?>" /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Numărul persoanei de contact', 'my-plugin' ); ?></label><input type="tel" class="mpc-input mpc-input--neutral mpc-supplier-telefon" placeholder="<?php esc_attr_e( 'Telefon', 'my-plugin' ); ?>" /></div>
							<div class="mpc-field"><label><?php esc_html_e( 'Email persoana de contact', 'my-plugin' ); ?></label><input type="email" class="mpc-input mpc-input--neutral mpc-supplier-email" placeholder="Email" /></div>
						</div>
						<div class="mpc-field mpc-field--observatii">
							<label><?php esc_html_e( 'Observații', 'my-plugin' ); ?></label>
							<textarea class="mpc-input mpc-input--neutral mpc-supplier-observatii" rows="4" placeholder="<?php esc_attr_e( 'Adăugați orice observații sau instrucțiuni speciale...', 'my-plugin' ); ?>"></textarea>
						</div>
					</div>
					<div class="mpc-step2-actions">
						<button type="button" class="mpc-btn-step2-continue"><?php esc_html_e( 'Continuați', 'my-plugin' ); ?></button>
					</div>
					<div class="mpc-step2-validation mpc-hidden" role="alert"></div>
				</div>
				<div class="mpc-step3-panel mpc-hidden">
					<button type="button" class="mpc-step3-back" aria-label="<?php esc_attr_e( 'Înapoi', 'my-plugin' ); ?>">← <?php esc_html_e( 'Înapoi', 'my-plugin' ); ?></button>
					<h2 class="mpc-step3-transport-name"></h2>
					<div class="mpc-step3-cards">
						<div class="mpc-step3-card">
							<h3 class="mpc-step3-card-title"><?php esc_html_e( 'Datele clientului', 'my-plugin' ); ?></h3>
							<dl class="mpc-step3-dl">
								<dt><?php esc_html_e( 'Nume', 'my-plugin' ); ?></dt><dd class="mpc-step3-client-nume">—</dd>
								<dt><?php esc_html_e( 'Prenume', 'my-plugin' ); ?></dt><dd class="mpc-step3-client-prenume">—</dd>
								<dt><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></dt><dd class="mpc-step3-client-adresa">—</dd>
								<dt><?php esc_html_e( 'Telefon', 'my-plugin' ); ?></dt><dd class="mpc-step3-client-telefon">—</dd>
								<dt><?php esc_html_e( 'E-mail', 'my-plugin' ); ?></dt><dd class="mpc-step3-client-email">—</dd>
							</dl>
						</div>
						<div class="mpc-step3-card">
							<h3 class="mpc-step3-card-title"><?php esc_html_e( 'Datele furnizorului', 'my-plugin' ); ?></h3>
							<dl class="mpc-step3-dl">
								<dt><?php esc_html_e( 'Provincie', 'my-plugin' ); ?></dt><dd class="mpc-step3-supplier-provincie">—</dd>
								<dt><?php esc_html_e( 'Oraș', 'my-plugin' ); ?></dt><dd class="mpc-step3-supplier-oras">—</dd>
								<dt><?php esc_html_e( 'Adresa', 'my-plugin' ); ?></dt><dd class="mpc-step3-supplier-adresa">—</dd>
								<dt><?php esc_html_e( 'Numărul persoanei de contact', 'my-plugin' ); ?></dt><dd class="mpc-step3-supplier-telefon">—</dd>
								<dt><?php esc_html_e( 'Email persoana de contact', 'my-plugin' ); ?></dt><dd class="mpc-step3-supplier-email">—</dd>
							</dl>
						</div>
						<div class="mpc-step3-card">
							<h3 class="mpc-step3-card-title"><?php esc_html_e( 'Marfă', 'my-plugin' ); ?></h3>
							<dl class="mpc-step3-dl">
								<dt><?php esc_html_e( 'Tip de marfă', 'my-plugin' ); ?></dt><dd class="mpc-step3-cargo-tip"><?php esc_html_e( 'Generală', 'my-plugin' ); ?></dd>
								<dt><?php esc_html_e( 'Volum', 'my-plugin' ); ?></dt><dd class="mpc-step3-cargo-volum">—</dd>
								<dt><?php esc_html_e( 'Greutate', 'my-plugin' ); ?></dt><dd class="mpc-step3-cargo-greutate">—</dd>
								<dt><?php esc_html_e( 'Volum tarifabil', 'my-plugin' ); ?></dt><dd class="mpc-step3-cargo-volum-taxabil">—</dd>
							</dl>
						</div>
						<div class="mpc-step3-card">
							<h3 class="mpc-step3-card-title"><?php esc_html_e( 'Traseu', 'my-plugin' ); ?></h3>
							<dl class="mpc-step3-dl">
								<dt><?php esc_html_e( 'Condiții de livrare INCOTERMS', 'my-plugin' ); ?></dt><dd class="mpc-step3-route-incoterms">—</dd>
								<dt><?php esc_html_e( 'Punctul de încărcare', 'my-plugin' ); ?></dt><dd class="mpc-step3-route-loading">—</dd>
								<dt><?php esc_html_e( 'Punctul de descărcare', 'my-plugin' ); ?></dt><dd class="mpc-step3-route-delivery">—</dd>
							</dl>
						</div>
					</div>
					<div class="mpc-step3-dvg-actions">
						<button type="button" class="mpc-btn-offer mpc-btn-offer--send">
							<?php esc_html_e( 'Trimite solicitarea actuală către DVG-Cargo', 'my-plugin' ); ?>
						</button>
						<p class="mpc-step3-dvg-note"><?php esc_html_e( 'Trimiteți oferta calculată către echipa DVG-Cargo', 'my-plugin' ); ?></p>
					</div>
				</div>
			</div>
			<div class="mpc-sidebar-column">
				<aside class="mpc-total-box">
					<header class="mpc-total-box-header"><?php esc_html_e( 'Total comandă', 'my-plugin' ); ?></header>
					<div class="mpc-total-box-body">
						<p class="mpc-total-box-empty mpc-total-box-hint"><?php esc_html_e( 'Alegeți opțiunea de transport pentru a vedea totalurile', 'my-plugin' ); ?></p>
						<div class="mpc-total-box-details mpc-hidden">
							<p class="mpc-total-box-section"><?php esc_html_e( 'TRANSPORT', 'my-plugin' ); ?></p>
							<p class="mpc-total-box-line mpc-total-box-line--transport">
								<span class="mpc-total-box-label"></span>
								<strong class="mpc-total-box-price">€ 0</strong>
							</p>
							<div class="mpc-total-box-more" aria-live="polite"></div>
							<hr class="mpc-total-box-sep" />
							<p class="mpc-total-box-total-line">
								<strong><?php esc_html_e( 'Total:', 'my-plugin' ); ?></strong>
								<strong class="mpc-total-box-total-amount">€ 0</strong>
							</p>
						</div>
					</div>
				</aside>
				<?php if ( function_exists( 'my_plugin_should_show_local_excel_panel' ) && my_plugin_should_show_local_excel_panel() ) : ?>
				<section class="mpc-excel-rates-panel mpc-excel-rates-panel--local-only" aria-label="<?php esc_attr_e( 'Tarife Excel (preview admin)', 'my-plugin' ); ?>"<?php echo $admin_excel_url ? ' data-admin-excel-url="' . esc_url( $admin_excel_url ) . '"' : ''; ?>>
					<p class="mpc-excel-rates-badge"><?php esc_html_e( 'Admin', 'my-plugin' ); ?></p>
					<h3 class="mpc-excel-rates-title"><?php esc_html_e( 'Tarife Excel (coloana maritim)', 'my-plugin' ); ?></h3>
					<p class="mpc-excel-rates-desc">
						<?php esc_html_e( 'Aceeași interfață ca în Setări → My Plugin: încărcați .xlsx/.xls cu coloana „maritim”. Pe site, încărcarea reală se face din panoul WordPress.', 'my-plugin' ); ?>
					</p>
					<div class="mpc-excel-rates-field">
						<label class="mpc-excel-rates-label" for="<?php echo esc_attr( $uid ); ?>-excel-file"><?php esc_html_e( 'Fișier Excel', 'my-plugin' ); ?></label>
						<input type="file" id="<?php echo esc_attr( $uid ); ?>-excel-file" class="mpc-excel-rates-file" name="mpc_excel_preview" accept=".xlsx,.xls,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
					</div>
					<div class="mpc-excel-rates-actions">
						<button type="button" class="mpc-btn mpc-excel-rates-btn-primary mpc-excel-rates-simulate-upload"><?php esc_html_e( 'Încarcă Excel (test)', 'my-plugin' ); ?></button>
						<button type="button" class="mpc-btn mpc-excel-rates-btn-secondary" disabled><?php esc_html_e( 'Simulare (dezactivat)', 'my-plugin' ); ?></button>
					</div>
					<p class="mpc-excel-rates-filename mpc-hidden" role="status" aria-live="polite"></p>
					<?php if ( $admin_excel_url ) : ?>
						<p class="mpc-excel-rates-admin-link">
							<a class="mpc-excel-rates-link" href="<?php echo esc_url( $admin_excel_url ); ?>"><?php esc_html_e( 'Deschide încărcarea în WordPress Admin →', 'my-plugin' ); ?></a>
						</p>
					<?php else : ?>
						<p class="mpc-excel-rates-note"><?php esc_html_e( 'Pentru încărcare reală, autentificați-vă ca administrator.', 'my-plugin' ); ?></p>
					<?php endif; ?>
				</section>
				<section class="mpc-local-orders-panel mpc-excel-rates-panel--local-only" aria-label="<?php esc_attr_e( 'Comenzi locale (preview admin)', 'my-plugin' ); ?>">
					<p class="mpc-excel-rates-badge"><?php esc_html_e( 'Admin', 'my-plugin' ); ?></p>
					<h3 class="mpc-excel-rates-title"><?php esc_html_e( 'Comenzi (admin)', 'my-plugin' ); ?></h3>
					<p class="mpc-excel-rates-desc">
						<?php esc_html_e( 'Comenzile trimise se salvează în SQLite (server). Afișați tabelul și filtrați după tipul de transport.', 'my-plugin' ); ?>
					</p>
					<div class="mpc-excel-rates-actions">
						<button type="button" class="mpc-btn mpc-excel-rates-btn-primary mpc-local-orders-toggle"><?php esc_html_e( 'Afișează comenzi', 'my-plugin' ); ?></button>
					</div>
					<div class="mpc-local-orders-body mpc-hidden">
						<div class="mpc-local-orders-toolbar">
							<label class="mpc-excel-rates-label" for="<?php echo esc_attr( $uid ); ?>-local-orders-filter"><?php esc_html_e( 'Filtru transport', 'my-plugin' ); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-local-orders-filter" class="mpc-input mpc-local-orders-filter">
								<option value=""><?php esc_html_e( 'Toate', 'my-plugin' ); ?></option>
								<option value="Aerian"><?php esc_html_e( 'Aerian', 'my-plugin' ); ?></option>
								<option value="Feroviar"><?php esc_html_e( 'Feroviar', 'my-plugin' ); ?></option>
								<option value="Maritim"><?php esc_html_e( 'Maritim', 'my-plugin' ); ?></option>
								<option value="Rutier"><?php esc_html_e( 'Rutier', 'my-plugin' ); ?></option>
							</select>
							<button type="button" class="mpc-btn mpc-local-orders-clear"><?php esc_html_e( 'Șterge', 'my-plugin' ); ?></button>
						</div>
						<div class="mpc-local-orders-table-wrap">
							<table class="mpc-local-orders-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Data', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Transport', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Client', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Email', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Rută', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Total', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Status', 'my-plugin' ); ?></th>
										<th><?php esc_html_e( 'Detaliu', 'my-plugin' ); ?></th>
									</tr>
								</thead>
								<tbody class="mpc-local-orders-tbody"></tbody>
							</table>
						</div>
					</div>
				</section>
				<?php endif; ?>
			</div>
			</div>
		</div>
		<script>
		(function(){
			var tries=0;
			function go(){ if(window.MyPluginCalculatorInit){ window.MyPluginCalculatorInit(); return; } tries++; if(tries<250) setTimeout(go,20); }
			if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",go);
			else go();
		})();
		</script>
		<?php
		return ob_get_clean();
	}
}
