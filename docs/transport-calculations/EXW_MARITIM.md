# EXW Maritim - pas cu pas

Calculul maritim EXW (exact logica din codul actual).

## 1) Date de intrare
- volum (m3)
- greutate (kg)
- oras incarcare China
- oras destinatie Romania

## 2) WM taxabil
- `wmTaxabil = max(volum, greutate / 1000)`

## 3) Freight maritim (USD)
- `seaUsd = wmTaxabil * tarifUsdPerWm`

## 4) Pick-up China (USD)
Tarif pe praguri de volum:
- `pickupUsd = volum * tarifPickupPerCbm(volum)`

## 5) Local charges China (USD, flat)
- 150 USD daca volum < 40 si greutate < 12 tone
- 280 USD daca volum >= 40 sau greutate >= 12 tone

## 6) Total China (USD -> EUR)
- `totalChinaUsd = seaUsd + pickupUsd + localUsd`
- `chinaEur = totalChinaUsd * 0.85`

## 7) Rutier Romania (Constanta -> destinatie)
Din tabel local, dupa praguri de greutate:
- `roadEur = roadRon / 5.1`

## Formula finala EXW Maritim
- `totalEXW = round(chinaEur + roadEur)`
