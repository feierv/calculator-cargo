# EXW Aerian - pas cu pas

Acesta este calculul folosit in cod pentru Aerian la EXW, explicat simplu.

## 1) Date de intrare
- greutate taxabila (kg)
- oras incarcare China
- oras livrare Romania

## 2) Segment China (USD)
- camion China -> aeroport: `max(greutate * tarif_camion, 85)`
- transport aerian: `greutate * 5.18`
- total China USD = camion + aerian

## 3) Conversie China in EUR
- `chinaEur = totalChinaUsd * 0.92`

## 4) Segment rutier Romania
Din tabelul local (Otopeni -> oras destinatie), dupa praguri de greutate:
- co-load / sprinter / full
- `roadEur = roadRon * 0.20`

## 5) Total Aerian (baza)
- `aerianBazaEur = round(chinaEur + roadEur)`

## 6) Diferenta EXW
La EXW se adauga servicii locale China:
- `+ 417 EUR` (fix)

## Formula finala EXW Aerian
- `totalEXW = aerianBazaEur + 417`
