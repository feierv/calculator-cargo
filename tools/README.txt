Test local — calculator / Resend
================================

0) Preview calculator-preview.html pe localhost:
   cd /cale/catre/wordpress_plugin
   php -S localhost:8080
   Deschide http://localhost:8080/calculator-preview.html (nu file://).
   Trimiterea folosește dev-send-quote.php (doar 127.0.0.1). WordPress: cale automată sau .wp-root-local.php sau MY_PLUGIN_WP_ROOT.

1) Email de cotație (template HTML demo) prin Resend — din folderul pluginului:

   php tools/test-resend-local.php /cale/către/wordpress

   WordPress trebuie să aibă pluginul activ. Cheie API: .resend-local.php (root) / includes/resend-local.php / MY_PLUGIN_RESEND_API_KEY.

   Destinatar implicit: updeveloplab@gmail.com (sau order_confirmation_recipient_email din config).
   Alt destinatar:
   MY_PLUGIN_TEST_TO=alt@email.com php tools/test-resend-local.php /cale/catre/wordpress

2) Expeditor „From” (Resend):
   - în wp-config.php: define( 'MY_PLUGIN_RESEND_FROM', 'DVG Cargo <adresa@domeniu-verificat.ro>' );
   - sau variabilă de mediu MY_PLUGIN_RESEND_FROM
   - sau în includes/config.php cheia resend_from_email
   Fără asta se folosește fallback onboarding@resend.dev (doar test Resend).

3) Test în browser: pagină WordPress cu [shipping_calculator], pasul 3 → „Trimite solicitarea…”.
