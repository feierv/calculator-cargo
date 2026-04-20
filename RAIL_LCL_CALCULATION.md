# Modul de calcul — feroviar LCL (identic cu screenshot-urile „pe hârtie”)

Pașii și constantele sunt aliniate la exemplul: **10 CBM**, **800 kg**, **Beijing** → **2300 USD** → **2116 EUR** (× **0,92**).

Implementare: `assets/js/calculator.js` — `computeRailTransportPriceEur`, `RAIL_USD_TO_EUR`, `RAIL_*` constante.

---

## Pasul 1 — kg → volum (1 CBM = 300 kg)

\[
V_{\text{kg}} = \frac{W}{300}
\]

Exemplu: \(800 / 300 = 2{,}67\) CBM.

## Pasul 2 — CBM taxabil

\[
V_{\text{tax}} = \max(V_{\text{m}^3},\, V_{\text{kg}})
\]

Exemplu: \(\max(10,\, 2{,}67) = 10\) CBM.

## Pasul 3 — RAIL (tabel RAIL RATE)

Densitate \(\rho = W/V\); dacă \(\rho < 300\) kg/m³ → tarif **ușor**, altfel **dens** (în cod: `+7` USD/CBM peste ușor, ca SMILE).

\[
\text{Rail USD} = V_{\text{tax}} \times \text{tarif USD/CBM}
\]

Exemplu Beijing: **175** USD/CBM ușor → \(10 \times 175 = 1750\) USD.

## Pasul 4 — PICK-UP

\[
\text{Pick-up USD} = V_{\text{tax}} \times 30
\]

Exemplu: \(10 \times 30 = 300\) USD.

## Pasul 5 — LOCAL

\[
\text{Local USD} = V_{\text{tax}} \times 10 + 50
\]

Exemplu: \(10 \times 10 + 50 = 150\) USD.

## Pasul 6 — EXTRA (tabel cut-off etc.)

\[
\text{Extra USD} = V_{\text{tax}} \times 10
\]

Exemplu: \(10 \times 10 = 100\) USD.

## Pasul 7 — Total USD

\[
\text{TOTAL} = \text{Rail} + \text{Pick-up} + \text{Local} + \text{Extra} = 2300 \text{ USD}
\]

## Pasul 8 — EUR

\[
\text{EUR} = \text{round}(\text{TOTAL} \times 0{,}92) = 2116
\]

În cod: `RAIL_USD_TO_EUR` (același **0,92** ca în screenshot).

---

## Note

- **Rutier România** nu intră în acest lanț (rămâne doar la aer).
- Orașe **Beijing** au tarif rail ușor **175** în cod (screenshot); alte orașe folosesc `RAIL_BUCHAREST_USD_PER_CBM` (SMILE București).
- Pick-up: implicit **30** USD/CBM pentru toate originile (tabel gol `RAIL_PICKUP_USD_PER_CBM`).

## Fișiere

| Fișier | Rol |
|--------|-----|
| `assets/js/calculator.js` | Logică + constante |
| `assets/data/LCL_RAIL_RATES_SMILE_COMPLETE.md` | Grile SMILE tarif rail (alte orașe) |
