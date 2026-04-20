## Airfreight estimativ (China -> Romania) - pași

1. **Intrări user**

   - `mc` = volum (m³)
   - `kg_real` = greutate (kg)
   - folosești tarifele din tabel pe rândul pentru orașul de plecare (ex: `Anhui -> ROMANIA`).
2. **Volum convertit în kg (volumetric / IATA)**

   - `kg_vol = mc * 167`
   - IATA (International Air Transport Association) folosește o regulă standard numită **„greutate volumetrică”**: dacă un colet ocupă mult volum (deși cântărește puțin), companiile de transport îl taxează ca și cum ar „cântări” mai mult, pentru că spațiul în avion e limitat.
   - Practic, se aplică un coeficient de echivalare volum->kg; pentru aer se folosește des aproximația: **1 m³ ≈ 167 kg** (echivalentul unei reguli IATA uzuale, adesea raportată ca volum / 6000).
3. **Greutate taxabilă (billing/chargeable)**

   - nm `W = max(kg_real, kg_vol, 300)`
   - (minim: 300 kg).
4. **Camion (oraș -> PVG)**

   - `C_truck = max(W * truck_rate, 85)`
   - unde `truck_rate` = „Truck cost/KG, MIN USD 85” (ex: 0.22 pentru Anhui).
5. **Aer (PVG -> ROMANIA / tariful din tabel)**

   - `C_air = W * air_rate`
   - unde `air_rate` = „Airfreight Price/kg” (în rândul ex. 5.18).
   - dacă în Excel tarifele diferă pe praguri, alegi `air_rate` după pragul care include `W`.
6. **Total estimativ**

   - `C_total = C_truck + C_air`
   - (fără TVA/vamă/alte surcharges care pot lipsi din tabel).
7. **După ce ajunge în RO (Otopeni): adaugi transport rutier FTL / Sprinter / CO LOAD**

   - Fă aceeași regulă de „minim 300 kg” deja folosită mai sus pentru `W`.
   - Alegi tarif din tabelul rutier `OTOPENI -> destinație`:
     - `CO LOAD` pentru încărcări mici (în calculator: când `W <= 600 kg`, dacă există în tabel)
     - altfel `SPRINTER` până la ~1200 kg
     - apoi `FULL`.
   - `C_road = tarif_ron * (RON_TO_EUR (dacă afișezi în EUR))`
   - `C_total_road = C_total + C_road`
