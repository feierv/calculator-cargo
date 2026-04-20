<?php
/**
 * Admin UI: menu, shortcodes, Excel upload.
 *
 * @package My_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class My_Plugin_Admin
 */
class My_Plugin_Admin {

	/**
	 * Option key for stored Excel file metadata.
	 */
	const EXCEL_OPTION = 'my_plugin_excel_upload';

	/**
	 * Register admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_output_quote_email_preview' ), 1 );
		add_action( 'admin_post_my_plugin_upload_excel', array( $this, 'handle_excel_upload' ) );
		add_action( 'admin_post_my_plugin_delete_excel', array( $this, 'handle_excel_delete' ) );
		add_action( 'admin_post_my_plugin_send_test_quote_email', array( $this, 'handle_send_test_quote_email' ) );
		add_action( 'admin_post_my_plugin_clear_local_orders_sqlite', array( $this, 'handle_clear_local_orders_sqlite' ) );
	}

	/**
	 * Load admin CSS on our settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_my-plugin' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'my-plugin-admin',
			MY_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			MY_PLUGIN_VERSION
		);
	}

	/**
	 * Add plugin menu and shortcodes page.
	 */
	public function add_menu_page() {
		add_options_page(
			__( 'My Plugin', 'my-plugin' ),
			__( 'My Plugin', 'my-plugin' ),
			'manage_options',
			'my-plugin',
			array( $this, 'render_shortcodes_page' )
		);
	}

	/**
	 * Allow Excel MIME types during upload.
	 *
	 * @param array $mimes MIME types.
	 * @return array
	 */
	public function filter_upload_mimes_for_excel( $mimes ) {
		$mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		$mimes['xls']  = 'application/vnd.ms-excel';
		return $mimes;
	}

	/**
	 * Handle Excel file upload (admin_post).
	 */
	public function handle_excel_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to upload files.', 'my-plugin' ) );
		}
		check_admin_referer( 'my_plugin_excel_upload', 'my_plugin_excel_nonce' );

		if ( empty( $_FILES['my_plugin_excel_file']['name'] ) ) {
			$this->redirect_with_notice( 'no_file' );
		}

		add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes_for_excel' ) );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = wp_handle_upload(
			$_FILES['my_plugin_excel_file'],
			array(
				'test_form' => false,
				'action'    => 'my_plugin_upload_excel',
			)
		);

		remove_filter( 'upload_mimes', array( $this, 'filter_upload_mimes_for_excel' ) );

		if ( isset( $file['error'] ) ) {
			$this->redirect_with_notice( 'upload_error', $file['error'] );
		}

		$ext = strtolower( pathinfo( $file['file'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'xlsx', 'xls' ), true ) ) {
			if ( file_exists( $file['file'] ) ) {
				wp_delete_file( $file['file'] );
			}
			$this->redirect_with_notice( 'invalid_type' );
		}

		$old = get_option( self::EXCEL_OPTION, array() );
		if ( ! empty( $old['path'] ) && $old['path'] !== $file['file'] && file_exists( $old['path'] ) ) {
			wp_delete_file( $old['path'] );
		}

		update_option(
			self::EXCEL_OPTION,
			array(
				'path'     => $file['file'],
				'url'      => $file['url'],
				'name'     => isset( $_FILES['my_plugin_excel_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['my_plugin_excel_file']['name'] ) ) : basename( $file['file'] ),
				'size'     => filesize( $file['file'] ),
				'uploaded' => time(),
			),
			false
		);

		$this->redirect_with_notice( 'success' );
	}

	/**
	 * Delete uploaded Excel file.
	 */
	public function handle_excel_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'my-plugin' ) );
		}
		check_admin_referer( 'my_plugin_delete_excel', 'my_plugin_delete_nonce' );

		$old = get_option( self::EXCEL_OPTION, array() );
		if ( ! empty( $old['path'] ) && file_exists( $old['path'] ) ) {
			wp_delete_file( $old['path'] );
		}
		delete_option( self::EXCEL_OPTION );

		$this->redirect_with_notice( 'deleted' );
	}

	/**
	 * Redirect back to settings with query arg.
	 *
	 * @param string $code Notice code.
	 * @param string $extra Optional extra message.
	 */
	private function redirect_with_notice( $code, $extra = '' ) {
		$url = add_query_arg(
			array(
				'page'                => 'my-plugin',
				'my_plugin_excel_msg' => rawurlencode( $code ),
			),
			admin_url( 'options-general.php' )
		);
		if ( $extra ) {
			$url = add_query_arg( 'my_plugin_excel_err', rawurlencode( $extra ), $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect back with mail test notice.
	 *
	 * @param string $code Notice code.
	 */
	private function redirect_mail_notice( $code ) {
		$url = add_query_arg(
			array(
				'page'               => 'my-plugin',
				'my_plugin_mail_msg' => rawurlencode( $code ),
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Output HTML preview of quote email (new tab) — no send.
	 */
	public function maybe_output_quote_email_preview() {
		if ( ! isset( $_GET['page'] ) || 'my-plugin' !== $_GET['page'] ) {
			return;
		}
		if ( empty( $_GET['my_plugin_preview_quote'] ) || '1' !== $_GET['my_plugin_preview_quote'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to preview this.', 'my-plugin' ) );
		}
		check_admin_referer( 'my_plugin_preview_quote' );
		if ( ! function_exists( 'my_plugin_get_demo_order_confirmation_data' ) ) {
			require_once MY_PLUGIN_PATH . 'includes/email-order-confirmation.php';
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- full HTML email template.
		echo my_plugin_build_order_confirmation_email_html( my_plugin_get_demo_order_confirmation_data() );
		exit;
	}

	/**
	 * Send test quote email to owner (demo data).
	 */
	public function handle_send_test_quote_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'my-plugin' ) );
		}
		check_admin_referer( 'my_plugin_send_test_quote_email', 'my_plugin_send_test_quote_nonce' );

		$to = isset( $_POST['test_quote_recipient'] ) ? sanitize_email( wp_unslash( $_POST['test_quote_recipient'] ) ) : '';
		if ( ! $to || ! is_email( $to ) ) {
			$to = my_plugin_get_dvg_offer_recipient_email();
		}
		if ( ! is_email( $to ) ) {
			$this->redirect_mail_notice( 'test_mail_bad_address' );
		}

		if ( ! function_exists( 'my_plugin_get_demo_order_confirmation_data' ) ) {
			require_once MY_PLUGIN_PATH . 'includes/email-order-confirmation.php';
		}
		$data = my_plugin_get_demo_order_confirmation_data();
		$html = my_plugin_build_order_confirmation_email_html( $data );

		$subject = sprintf(
			'[TEST] ' . __( '[DVG-Cargo] Cotație %1$s – %2$s', 'my-plugin' ),
			$data['quote_ref'],
			__( 'Demo client', 'my-plugin' )
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to, $subject, $html, $headers );

		$this->redirect_mail_notice( $sent ? 'test_mail_sent' : 'test_mail_fail' );
	}

	/**
	 * Clear local SQLite orders from admin page.
	 */
	public function handle_clear_local_orders_sqlite() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'my-plugin' ) );
		}
		check_admin_referer( 'my_plugin_clear_local_orders_sqlite', 'my_plugin_clear_local_orders_sqlite_nonce' );
		if ( function_exists( 'my_plugin_local_orders_clear' ) ) {
			my_plugin_local_orders_clear();
		}
		$url = add_query_arg(
			array(
				'page'               => 'my-plugin',
				'my_plugin_mail_msg' => rawurlencode( 'local_orders_cleared' ),
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the Shortcodes / info page in WP Admin.
	 */
	public function render_shortcodes_page() {
		$notice = '';
		if ( isset( $_GET['my_plugin_mail_msg'] ) ) {
			$notice = sanitize_text_field( wp_unslash( $_GET['my_plugin_mail_msg'] ) );
		} elseif ( isset( $_GET['my_plugin_excel_msg'] ) ) {
			$notice = sanitize_text_field( wp_unslash( $_GET['my_plugin_excel_msg'] ) );
		}
		$excel  = my_plugin_get_uploaded_excel_meta();
		$preview_quote_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'                 => 'my-plugin',
					'my_plugin_preview_quote' => '1',
				),
				admin_url( 'options-general.php' )
			),
			'my_plugin_preview_quote'
		);
		$default_recipient = my_plugin_get_dvg_offer_recipient_email();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'My Plugin', 'my-plugin' ); ?></h1>

			<?php $this->render_admin_notices( $notice ); ?>

			<div class="my-plugin-admin-layout">
				<section class="my-plugin-admin-section my-plugin-admin-section--email-test">
					<h2><?php esc_html_e( 'Test email cotație (owner)', 'my-plugin' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Previzualizare sau trimitere de probă a aceluiași HTML ca la confirmarea din calculator (date demo). Destinatarul real al ofertelor este setat în config / email admin.', 'my-plugin' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( $preview_quote_url ); ?>" class="button" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Deschide previzualizare HTML (tab nou)', 'my-plugin' ); ?></a>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="my-plugin-test-mail-form">
						<?php wp_nonce_field( 'my_plugin_send_test_quote_email', 'my_plugin_send_test_quote_nonce' ); ?>
						<input type="hidden" name="action" value="my_plugin_send_test_quote_email" />
						<p>
							<label for="test_quote_recipient"><strong><?php esc_html_e( 'Trimite test la:', 'my-plugin' ); ?></strong></label><br />
							<input type="email" class="regular-text" id="test_quote_recipient" name="test_quote_recipient" value="<?php echo esc_attr( $default_recipient ); ?>" placeholder="<?php esc_attr_e( 'email@exemplu.ro', 'my-plugin' ); ?>" />
						</p>
						<?php
						submit_button(
							__( 'Trimite email de test (date demo)', 'my-plugin' ),
							'secondary',
							'submit',
							false,
							array( 'id' => 'my-plugin-send-test-quote' )
						);
						?>
					</form>
					<p class="description">
						<?php esc_html_e( 'Subiectul începe cu [TEST]. Dacă nu primești nimic, configurează SMTP (ex. WP Mail SMTP) sau Mailtrap.', 'my-plugin' ); ?>
					</p>
				</section>

				<section class="my-plugin-admin-section">
					<h2><?php esc_html_e( 'Shortcodes', 'my-plugin' ); ?></h2>
					<p><?php esc_html_e( 'Use these shortcodes in posts or pages.', 'my-plugin' ); ?></p>
					<table class="widefat striped" style="max-width: 560px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Shortcode', 'my-plugin' ); ?></th>
								<th><?php esc_html_e( 'Description', 'my-plugin' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>[shipping_calculator]</code></td>
								<td><?php esc_html_e( 'Full shipping calculator (grupaj China): addresses, cargo params, INCOTERMS, Calculate button.', 'my-plugin' ); ?></td>
							</tr>
							<tr>
								<td><code>[shipping_calculator loading_country="China" delivery_country="Romania"]</code></td>
								<td><?php esc_html_e( 'Same, with default loading and delivery country.', 'my-plugin' ); ?></td>
							</tr>
							<tr>
								<td><code>[adresa_incarcarii]</code></td>
								<td><?php esc_html_e( 'Displays the "Adresa încărcării" section with country field.', 'my-plugin' ); ?></td>
							</tr>
							<tr>
								<td><code>[adresa_incarcarii country="China"]</code></td>
								<td><?php esc_html_e( 'Same, with default country attribute.', 'my-plugin' ); ?></td>
							</tr>
						</tbody>
					</table>
				</section>

				<section class="my-plugin-admin-section my-plugin-admin-section--upload">
					<div class="my-plugin-admin-preview-badge"><?php esc_html_e( 'ADMIN', 'my-plugin' ); ?></div>
					<h2><?php esc_html_e( 'Tarife Excel (coloana maritim)', 'my-plugin' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'UI preview în admin, identic cu partea publică. Momentan această zonă este doar pentru aspect (fără funcționalitate nouă).', 'my-plugin' ); ?>
					</p>
					<h3><?php esc_html_e( 'Fișiere Excel pe tip transport', 'my-plugin' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Previzualizare UI: selectați fișiere diferite pentru fiecare transport. Momentan nu se salvează în backend.', 'my-plugin' ); ?>
					</p>
					<div class="my-plugin-transport-files-wrap">
						<table class="widefat striped my-plugin-transport-files-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Transport', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Fișier Excel', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Fișier selectat', 'my-plugin' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array( 'Aerian', 'Feroviar', 'Maritim', 'Rutier' ) as $transport_type ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $transport_type ); ?></strong></td>
										<td>
											<input
												type="file"
												class="my-plugin-transport-file-input"
												accept=".xlsx,.xls,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
												data-transport="<?php echo esc_attr( $transport_type ); ?>"
											/>
										</td>
										<td>
											<span class="my-plugin-transport-file-name" data-transport="<?php echo esc_attr( $transport_type ); ?>">
												<?php esc_html_e( 'Niciun fișier selectat', 'my-plugin' ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
					$transport_filter = isset( $_GET['my_plugin_transport_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['my_plugin_transport_filter'] ) ) : '';
					$rows             = function_exists( 'my_plugin_local_orders_list' ) ? my_plugin_local_orders_list( $transport_filter, 500 ) : array();
					?>
					<hr />
					<h3><?php esc_html_e( 'Comenzi (SQLite)', 'my-plugin' ); ?></h3>
					<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" style="margin-bottom:8px;">
						<input type="hidden" name="page" value="my-plugin" />
						<label for="my_plugin_transport_filter"><strong><?php esc_html_e( 'Filtru transport', 'my-plugin' ); ?></strong></label>
						<select id="my_plugin_transport_filter" name="my_plugin_transport_filter">
							<option value=""><?php esc_html_e( 'Toate', 'my-plugin' ); ?></option>
							<?php foreach ( array( 'Aerian', 'Feroviar', 'Maritim' ) as $t ) : ?>
								<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $transport_filter, $t ); ?>><?php echo esc_html( $t ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php submit_button( __( 'Filtrează', 'my-plugin' ), 'secondary', 'submit', false ); ?>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:10px;">
						<?php wp_nonce_field( 'my_plugin_clear_local_orders_sqlite', 'my_plugin_clear_local_orders_sqlite_nonce' ); ?>
						<input type="hidden" name="action" value="my_plugin_clear_local_orders_sqlite" />
						<?php submit_button( __( 'Șterge toate comenzile', 'my-plugin' ), 'delete', 'submit', false ); ?>
					</form>
					<div style="max-height:320px; overflow:auto; border:1px solid #dcdcde; border-radius:4px;">
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Data', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Transport', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Client', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Email', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Rută', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Total', 'my-plugin' ); ?></th>
									<th><?php esc_html_e( 'Status', 'my-plugin' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( is_wp_error( $rows ) ) : ?>
									<tr><td colspan="7"><?php echo esc_html( $rows->get_error_message() ); ?></td></tr>
								<?php elseif ( empty( $rows ) ) : ?>
									<tr><td colspan="7"><?php esc_html_e( 'Nu există comenzi locale.', 'my-plugin' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $rows as $r ) : ?>
										<?php
										$created_raw     = isset( $r['created_at'] ) ? (string) $r['created_at'] : '';
										$created_ts      = $created_raw ? strtotime( $created_raw ) : false;
										$created_display = $created_ts ? wp_date( 'd.m.Y H:i', $created_ts ) : ( '' !== $created_raw ? $created_raw : '—' );
										?>
										<tr>
											<td><?php echo esc_html( $created_display ); ?></td>
											<td><?php echo esc_html( isset( $r['transport_type'] ) ? $r['transport_type'] : '—' ); ?></td>
											<td><?php echo esc_html( isset( $r['client_name'] ) ? $r['client_name'] : '—' ); ?></td>
											<td><?php echo esc_html( isset( $r['client_email'] ) ? $r['client_email'] : '—' ); ?></td>
											<td><?php echo esc_html( ( isset( $r['route_loading'] ) ? $r['route_loading'] : '—' ) . ' → ' . ( isset( $r['route_delivery'] ) ? $r['route_delivery'] : '—' ) ); ?></td>
											<td><?php echo esc_html( isset( $r['total_eur'] ) ? $r['total_eur'] : '—' ); ?></td>
											<td><?php echo esc_html( isset( $r['local_status'] ) ? $r['local_status'] : '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					<hr />
					<h2><?php esc_html_e( 'Excel file', 'my-plugin' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Upload an Excel file (.xlsx or .xls) for later use in this plugin (e.g. rates, reference data).', 'my-plugin' ); ?>
					</p>

					<form class="my-plugin-excel-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'my_plugin_excel_upload', 'my_plugin_excel_nonce' ); ?>
						<input type="hidden" name="action" value="my_plugin_upload_excel" />

						<p class="my-plugin-file-input">
							<label for="my_plugin_excel_file" class="screen-reader-text"><?php esc_html_e( 'Choose Excel file', 'my-plugin' ); ?></label>
							<input type="file" id="my_plugin_excel_file" name="my_plugin_excel_file" accept=".xlsx,.xls,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
						</p>
						<?php submit_button( __( 'Upload Excel file', 'my-plugin' ), 'primary', 'submit', false ); ?>
					</form>

					<?php if ( $excel ) : ?>
						<div class="my-plugin-excel-meta">
							<p><strong><?php esc_html_e( 'Current file:', 'my-plugin' ); ?></strong> <?php echo esc_html( $excel['name'] ); ?></p>
							<p>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: file size */
										__( 'Size: %s', 'my-plugin' ),
										size_format( isset( $excel['size'] ) ? (int) $excel['size'] : 0 )
									)
								);
								?>
								<?php if ( ! empty( $excel['uploaded'] ) ) : ?>
									&mdash;
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: formatted date */
											__( 'Uploaded: %s', 'my-plugin' ),
											wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $excel['uploaded'] )
										)
									);
									?>
								<?php endif; ?>
							</p>
							<?php if ( ! empty( $excel['url'] ) ) : ?>
								<p><a href="<?php echo esc_url( $excel['url'] ); ?>" class="button" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Download', 'my-plugin' ); ?></a></p>
							<?php endif; ?>
							<p><code><?php echo esc_html( $excel['path'] ); ?></code></p>
							<p class="description"><?php esc_html_e( 'Use my_plugin_get_uploaded_excel_path() in PHP to read the file.', 'my-plugin' ); ?></p>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this file?', 'my-plugin' ) ); ?>');">
								<?php wp_nonce_field( 'my_plugin_delete_excel', 'my_plugin_delete_nonce' ); ?>
								<input type="hidden" name="action" value="my_plugin_delete_excel" />
								<?php submit_button( __( 'Remove file', 'my-plugin' ), 'delete', 'submit', false ); ?>
							</form>
						</div>
					<?php endif; ?>
					<script>
					(function() {
						var scope = document.currentScript && document.currentScript.closest('.my-plugin-admin-section--upload');
						if (!scope) return;
						var inputs = scope.querySelectorAll('.my-plugin-transport-file-input');
						inputs.forEach(function(input) {
							input.addEventListener('change', function() {
								var transport = input.getAttribute('data-transport');
								var target = scope.querySelector('.my-plugin-transport-file-name[data-transport="' + transport + '"]');
								if (!target) return;
								var file = input.files && input.files[0] ? input.files[0].name : '';
								target.textContent = file || '<?php echo esc_js( __( 'Niciun fișier selectat', 'my-plugin' ) ); ?>';
							});
						});
					})();
					</script>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Admin notices after upload/delete.
	 *
	 * @param string $code Notice code.
	 */
	private function render_admin_notices( $code ) {
		if ( '' === $code ) {
			return;
		}
		$extra = isset( $_GET['my_plugin_excel_err'] ) ? sanitize_text_field( wp_unslash( $_GET['my_plugin_excel_err'] ) ) : '';

		$messages = array(
			'success'      => array( 'notice-success', __( 'File uploaded successfully.', 'my-plugin' ) ),
			'deleted'      => array( 'notice-success', __( 'File removed.', 'my-plugin' ) ),
			'no_file'      => array( 'notice-error', __( 'No file selected.', 'my-plugin' ) ),
			'invalid_type' => array( 'notice-error', __( 'Only .xlsx and .xls files are allowed.', 'my-plugin' ) ),
			'test_mail_sent' => array( 'notice-success', __( 'Email de test trimis. Verifică inbox-ul (și spam).', 'my-plugin' ) ),
			'test_mail_fail' => array( 'notice-error', __( 'Trimiterea emailului de test a eșuat. Verifică WP Mail SMTP / serverul de mail.', 'my-plugin' ) ),
			'test_mail_bad_address' => array( 'notice-error', __( 'Adresă de email destinatar invalidă. Setează emailul în Setări → General sau în config plugin.', 'my-plugin' ) ),
			'local_orders_cleared' => array( 'notice-success', __( 'Comenzile SQLite au fost șterse.', 'my-plugin' ) ),
		);

		if ( 'upload_error' === $code ) {
			$text = __( 'Upload failed.', 'my-plugin' );
			if ( $extra ) {
				$text .= ' ' . $extra;
			}
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $text )
			);
			return;
		}

		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}
		$class = $messages[ $code ][0];
		$text  = $messages[ $code ][1];
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $text )
		);
	}
}
