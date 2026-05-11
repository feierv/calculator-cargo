=== My Plugin ===

Contributors: yourname
Tags: plugin
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin.

== Description ==

Describe your plugin here.

== Installation ==

1. Zip the plugin folder (or upload the folder via FTP) to `wp-content/plugins/wordpress_plugin/` (folder name may vary).
2. Activate **My Plugin** under Plugins in WordPress admin.
3. Add the shortcode `[shipping_calculator]` to any page or post (or Elementor Shortcode widget).

See **WORDPRESS-SETUP.md** in the plugin folder for a full Romanian setup guide (email, SMTP, troubleshooting).

== Changelog ==

= 1.1.0 =
* Calculator: defalcare transparentă a costurilor pe fiecare linie (freight, pick-up CN, servicii locale CN, vamă export, ENS UE, T1, rutier RO).
* Toggle FOB/EXW: citire corectă din grupul incoterm și recalcul automat la schimbarea cursei sau a incotermului.
* Pick-up China (Untitled-3 LCL Local WH Pick-up fee, USD/CBM) aplicat la maritim, aerian și feroviar EXW.
* Tabelul FTL (Untitled-2: Constanța / Otopeni → 17 destinații) verificat și aplicat consecvent pe toate cele 3 moduri.
* Adăugat „Tranzit vamal T1" 150 € fix pentru toate modurile (FOB + EXW).
* Adăugat „ENS UE (ICS2 R3)" la maritim/feroviar și „Vamă export China" la EXW.
* Curs unic USD→EUR (0.92) și RON→EUR (0.20) pe toate calculele — EXW = FOB + servicii China, fără surprize.
* Total comandă: explicație scurtă sub fiecare linie pentru client.
* Email: defalcare detaliată (label + valoare + explicație) pentru fiecare cost.
* UI: linia statică „Servicii locale China INCLUS 417 €" înlocuită cu listă reală a serviciilor incluse.

= 1.0.0 =
* Initial release.
