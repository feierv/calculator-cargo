# Deploy calculator pe site

Pentru ca modificările făcute în simulator să apară pe site, asigură-te că pe server sunt actualizate aceste fișiere:

## Fișiere obligatorii

| Fișier | Rol |
|--------|-----|
| `assets/css/calculator.css` | Stiluri calculator, pași, formulare, ofertă, facturare |
| `assets/js/calculator.js` | Logica calculator: pași, validare, trimitere ofertă |
| `includes/class-my-plugin-public.php` | HTML-ul shortcode-ului `[shipping_calculator]` |

## Ce face fiecare

- **calculator.css** și **calculator.js** sunt încărcate de plugin pe frontend (vezi `enqueue_assets()` în `class-my-plugin-public.php`). Simulatorul (`calculator-preview.html`) folosește aceleași fișiere din `assets/`, deci orice schimbare făcută acolo e deja și în plugin.
- **class-my-plugin-public.php** generează markup-ul pentru shortcode. Structura (clase, `data-step`, panouri step1/step2/step3, billing, offer form) trebuie să rămână aliniată cu preview-ul ca JS-ul și CSS-ul să funcționeze.

## Pași pentru deploy

1. Înlocuiește pe server (prin FTP/SFTP sau panou de fișiere) întregul folder al plugin-ului, **sau** doar:
   - `assets/css/calculator.css`
   - `assets/js/calculator.js`
   - `includes/class-my-plugin-public.php`
2. Dacă folosești cache (plugin sau CDN), golește cache-ul după upload.
3. Pe pagină folosește shortcode-ul: `[shipping_calculator]`.

După deploy, pe site vor apărea: pașii Calculator → Detalii → Confirmare (fără pas Plata), validări la pasul Detalii, emiterea cotației pe email la confirmare, formularul solicitare DVG-Cargo și restul comportamentului din simulator.

## Panoul „Tarife Excel (test local)” în sidebar

Pe **producție** acest bloc **nu se afișează** (doar mediu local / development sau hostname `localhost`, `127.0.0.1`, `*.local`, `*.test`).

- În `wp-config.php` puteți forța: `define( 'MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL', false );` (ascuns) sau `true` (vizibil, ex. staging).
- Sau: `add_filter( 'my_plugin_show_local_excel_panel', '__return_true' );` în tema child.

Încărcarea reală a Excel rămâne în **Setări → My Plugin** în admin.

---

## Comandă: fișier pentru traduceri (admin / l10n)

Pentru a genera fișierul **.pot** (template de traduceri) folosit în WordPress (admin, Poedit etc.), din rădăcina plugin-ului rulează (necesită [WP-CLI](https://wp-cli.org/) instalat):

```bash
wp i18n make-pot . my-plugin.pot --domain=my-plugin
```

Se creează `my-plugin.pot` în rădăcina plugin-ului. Poți pune fișierul în `languages/` sau îl folosești cu editori de traduceri.
