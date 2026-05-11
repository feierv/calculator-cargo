# FOB Feroviar - pas cu pas

Formula folosita in cod pentru Feroviar la FOB.

## 1) Date de intrare
- volum (m3)
- greutate (kg)
- oras plecare China
- oras destinatie Romania

## 2) CBM taxabil
- `cbmTaxabil = max(volum, greutate / 300)`

## 3) Tarif rail (light/heavy)
- `densitate = greutate / volum`
- < 300 => light, >= 300 => heavy

## 4) Rail USD
- `railUsd = cbmTaxabil * tarifRail`

## 5) Conversie rail in EUR (FOB)
- `railEur = railUsd * 0.85`

## 6) Rutier Romania (Constanta -> destinatie)
Tarif pe praguri de greutate:
- `roadEur = roadRon / 5.1`

## Formula finala FOB Feroviar
- `totalFOB = round(railEur + roadEur)`

## Observatie
FOB poate parea apropiat de EXW in unele cazuri daca rutierul RO este mare.
