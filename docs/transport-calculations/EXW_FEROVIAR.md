# EXW Feroviar - pas cu pas

Formula feroviara EXW din cod, explicata clar.

## 1) Date de intrare
- volum (m3)
- greutate (kg)
- oras plecare China

## 2) CBM taxabil
- `cbmTaxabil = max(volum, greutate / 300)`

## 3) Tarif rail (light/heavy)
Se calculeaza densitatea:
- `densitate = greutate / volum`

Se alege tarif:
- densitate < 300 -> light
- densitate >= 300 -> heavy

## 4) Costuri in USD
- `railUsd = cbmTaxabil * tarifRail`
- `pickupUsd = cbmTaxabil * tarifPickup` (default 30 USD/CBM)
- `localUsd = cbmTaxabil * 10 + 50`
- `extraUsd = cbmTaxabil * 10`

## 5) Total USD si conversie
- `totalUsd = railUsd + pickupUsd + localUsd + extraUsd`
- `totalEur = round(totalUsd * 0.92)`

## Formula finala EXW Feroviar
- `totalEXW = round((rail + pickup + local + extra) * 0.92)`
