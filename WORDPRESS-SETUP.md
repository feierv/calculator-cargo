# Cum rulezi pluginul pe WordPress

Ghid scurt: de la folderul din proiect până la calculator live pe site.

## Cerințe

- WordPress **5.8+**
- PHP **7.4+** (recomandat 8.x)
- HTTPS recomandat pe producție (pentru formulare și AJAX)

---

## 1. Instalare plugin

### Varianta A – ZIP (uzual)

1. Pe calculatorul tău, folderul proiectului este `wordpress_plugin` (sau cum îl numești).
2. Creează un **ZIP** care conține **conținutul** folderului plugin (fișierele `my-plugin.php`, `includes/`, `assets/` etc.), **nu** un folder părinte gol.
   - Corect: în ZIP există direct `my-plugin.php` la rădăcină.
   - Alternativ: zip-uiește tot folderul `wordpress_plugin`; în WordPress la instalare se va crea un folder cu același nume — e ok dacă structura e `wordpress_plugin/my-plugin.php`.
3. În WordPress: **Pluginuri → Adaugă plugin → Încarcă plugin** → alegi ZIP-ul → **Activează**.

### Varianta B – FTP / panou fișiere

1. Copiază **tot folderul** pluginului în:
   ```
   wp-content/plugins/wordpress_plugin/
   ```
   (sau redenumire `my-plugin` — numele folderului poate fi orice; important e că `my-plugin.php` e în interior.)
2. În admin: **Pluginuri** → găsești **My Plugin** → **Activează**.

---

## 2.1. Orașe România (autocomplete)

Lista de orașe pentru **„Adresa finală → Alegeți oraș”** vine din `includes/data/ro-cities.txt` (~230 de orașe: municipii și orașe importante). Poți edita fișierul (un oraș pe linie) sau folosi filtrul PHP `my_plugin_ro_city_list`.

După modificare, regenerează `assets/data/ro-cities.json` (pentru preview static) cu:

```bash
python3 -c "import json; p='includes/data/ro-cities.txt'; c=sorted(set(x.strip() for x in open(p,encoding='utf-8') if x.strip())); json.dump(c,open('assets/data/ro-cities.json','w'),ensure_ascii=False,indent=2); print(len(c))"
```

(rulat din folderul pluginului)

---

## 2. Afișare calculator pe site

1. **Pagini → Adaugă pagină nouă** (sau editezi una existentă).
2. În editor (Clasic sau Gutenberg), adaugă un bloc **Shortcode** și scrie:

   ```
   [shipping_calculator]
   ```

   Opțional, cu țări implicite:

   ```
   [shipping_calculator loading_country="China" delivery_country="Romania"]
   ```

3. **Publică** pagina și o deschizi în frontend — trebuie să vezi calculatorul (pași, formular, Total comandă).

> Dacă folosești **Elementor** sau alt page builder: adaugă widget **Shortcode** și pune același `[shipping_calculator]`.

---

## 3. Setări utile în WordPress

| Unde | Ce faci |
|------|--------|
| **Setări → My Plugin** | Shortcode-uri, încărcare Excel (tarife), **test email cotație** (previzualizare / trimitere demo). |
| **Setări → General** | **Adresă de email** a site-ului — folosită ca destinatar implicit pentru oferte DVG dacă nu setezi altceva în cod. |

### Email destinatar oferte (owner)

În `includes/config.php` poți seta (sau lasă gol pentru `admin_email`):

```php
'dvg_cargo_offer_recipient_email' => 'office@firma-ta.ro',
```

După modificare, reîncarcă fișierul pe server.

---

## 4. Emailuri care pleacă de pe site

Pluginul folosește **`wp_mail()`** (ofertă + confirmare comandă). Pe multe hosturi trebuie **SMTP** (ex. plugin **WP Mail SMTP** + Mailtrap pentru test sau inbox real).

- Test fără SMTP: **Setări → My Plugin** → previzualizare HTML sau fișier local `email-quote-preview.html`.

---

## 5. Panoul „Tarife Excel” lângă Total comandă

- Pe **producție** e ascuns implicit (doar mediu local/dev).
- Forțare în `wp-config.php`: `define( 'MY_PLUGIN_SHOW_LOCAL_EXCEL_PANEL', true );` pe staging dacă vrei să vezi blocul.

---

## 6. Probleme frecvente

| Simptom | Ce verifici |
|--------|-------------|
| Pagină goală / fără calculator | Shortcode-ul e exact `[shipping_calculator]`? Pagina e publicată? |
| Fără stiluri / fără butoane | Cache (plugin/CDN) golit; verifică că există `assets/css/calculator.css` pe server. |
| Eroare la trimitere ofertă / confirmare | Consola browser (F12); permalinks: încearcă **Setări → Permalinks → Salvează** (reîncarcă regulile). |
| Email nu ajunge | SMTP + inbox spam; test din **Setări → My Plugin**. |

---

## 7. Actualizare după ce modifici codul local

Înlocuiești pe server folderul pluginului (sau fișierele modificate), golești cache-ul, reîncarci pagina cu calculatorul.

Detalii suplimentare: vezi **DEPLOY.md**.
