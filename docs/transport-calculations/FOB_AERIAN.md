# FOB Aerian - pas cu pas

Calculul Aerian pentru FOB, in varianta din cod.

## 1) Date de intrare
- greutate taxabila (kg)
- oras incarcare China
- oras livrare Romania

## 2) Segment China (USD)
- camion pana la aeroport: `max(greutate * tarif_camion, 85)`
- aerian: `greutate * 5.18`

## 3) Conversie in EUR
- `chinaEur = (camionUsd + aerianUsd) * 0.92`

## 4) Segment rutier Romania
Tarif local din tabel (Otopeni -> oras destinatie):
- co-load / sprinter / full
- `roadEur = roadRon * 0.20`

## Formula finala FOB Aerian
- `totalFOB = round(chinaEur + roadEur)`

## Diferenta fata de EXW
- la FOB NU se adauga cei 417 EUR de servicii locale China.
