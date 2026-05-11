# FOB Maritim - pas cu pas

Explicatia calculului maritim FOB din codul actual.

## 1) Date de intrare
- volum (m3)
- greutate (kg)
- oras incarcare China
- oras destinatie Romania

## 2) WM taxabil
- `wmTaxabil = max(volum, greutate / 1000)`

## 3) Freight maritim (USD)
- `seaUsd = wmTaxabil * tarifUsdPerWm`

## 4) Conversie freight in EUR
- `seaEur = seaUsd * 0.92`

## 5) Rutier Romania (Constanta -> destinatie)
Tarif local dupa greutate:
- full / sprinter / co-load (cu fallback)
- `roadEur = roadRon * 0.20`

## Formula finala FOB Maritim
- `totalFOB = round(seaEur + roadEur)`

## Diferenta fata de EXW
La FOB nu se adauga in aceasta formula costurile locale China de tip pick-up + local charges EXW.
