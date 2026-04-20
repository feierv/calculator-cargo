# LCL Rail — tarife SMILE (referință + completare pentru lista din plugin)

**Unitate:** USD/CBM  
**Exemplu valabilitate (din grilă furnizor):** ETD 01–28 feb, via Chengdu  
**Notă prag densitate (SMILE):** &lt;300 kg/CBM vs &gt;300 kg/CBM — diferență **+7 USD/CBM** între cele două coloane (unde există două valori). **Chengdu:** același tarif pentru ambele categorii.

Provinciile au **estimare agregată** (tier reprezentativ); nu este nevoie de un oraș concret din foaia de tarife.

Acest fișier este generat de `scripts/build_lcl_rail_rates_xlsx.py` (aceeași logică ca Excel-ul).

---

## Ghid — legătura cu estimatorul din plugin

### Ce este acest tabel

- **Secțiunea 1** reproduce grupele din documentul furnizor **SMILE** (Varșovia, BHD, București) în **USD/CBM**.
- **Secțiunea 2** aliniază fiecare intrare din `assets/data/cn-cities.json` la un **grup tarifar** (Chengdu / Galben / Portocaliu / Albastru sau estimare Urumqi), ca referință pentru ofertare.

### Cum calculează pluginul prețul feroviar (rezumat)

Implementare: `assets/js/calculator.js` — `computeRailTransportPriceEur` (metodă „pe hârtie”). Detaliu: **`RAIL_LCL_CALCULATION.md`**.

| Element | Regulă în cod (estimativ) |
|--------|---------------------------|
| CBM taxabil | `max(m³, kg ÷ 300)` — **fără** coeficient 333 kg/m³. |
| Rail (USD) | `CBM taxabil ×` tarif București (ușor/dens după densitate vs 300 kg/m³); mapare `RAIL_BUCHAREST_USD_PER_CBM`. |
| Pick-up China (USD) | `CBM taxabil ×` tarif USD/CBM; default **30**; `RAIL_PICKUP_USD_PER_CBM`. |
| Local (USD) | `CBM taxabil × 10 + 50` (`RAIL_LOCAL_*`). |
| Extra (USD) | `CBM taxabil × 10` (`RAIL_EXTRA_USD_PER_CBM`) — ca în PASUL 6. |
| Total EUR | `(rail + pickup + local + extra) USD × RAIL_USD_TO_EUR` (0,92). |
| Rutier România | **Nu** este inclus la feroviar; la **aer** da. |


---

## 1. Grile de referință (document SMILE)

| Grup | Orașe în document | Varșovia &lt;300 | Varșovia &gt;300 | BHD &lt;300 | BHD &gt;300 | București &lt;300 | București &gt;300 |
|------|-------------------|------------------|------------------|-------------|------------|-------------------|------------------|
| **Chengdu** | Chengdu | 105 | 105 | 120 | 120 | 165 | 165 |
| **Galben** | Shenzhen, Guangzhou, Chongqing, Ningbo, Shanghai, Changsha, Xi’an, Zhengzhou, Wuhan, Yiwu | 125 | 132 | 140 | 147 | 185 | 192 |
| **Portocaliu** | Changzhou, Qingdao, Tianjin, Wenzhou, Nanjing, Yangzhou, Nantong, Suzhou, Hangzhou, Wuxi, Hefei, Fuzhou | 130 | 137 | 145 | 152 | 190 | 197 |
| **Albastru** | Shantou, Xiamen, Beijing | 132 | 139 | 147 | 154 | 192 | 199 |

*BHD = Budapesta / Hamburg / Duisburg.*

---

## 2. Toate intrările din `cn-cities.json` (plugin)

| Oraș | Sursă | Grup tarifar | W &lt;300 | W &gt;300 | BHD &lt;300 | BHD &gt;300 | Buc &lt;300 | Buc &gt;300 | Observații |
|------|-------|--------------|-----------|-----------|-------------|------------|-------------|------------|------------|
| Anhui | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Baoding | Estimativ | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Albastru — grilă SMILE — hub apropiat / coridor feroviar. |
| Beijing | Grilă SMILE | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | — |
| Changsha | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Changzhou | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Chengdu | Grilă SMILE | Chengdu (grilă) | 105 | 105 | 120 | 120 | 165 | 165 | — |
| Chongqing | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Dongchong | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Dongguan | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Foshan | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Fujian | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Fuzhou | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Gansu | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Guangdong | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Guangxi | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Guangzhou | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Guizhou | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Haikou | Estimativ | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Albastru — grilă SMILE — hub apropiat / coridor feroviar. |
| Hainan | Estimativ (provincie) | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Hangzhou | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Hebei | Estimativ (provincie) | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Heilongjiang | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Henan | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Hong Kong | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Hubei | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Huizhou | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Hunan | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Inner Mongolia | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Jiangsu | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Jiangxi | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Jilin | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Jinhua | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Jining | Estimativ | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Portocaliu — grilă SMILE — hub apropiat / coridor feroviar. |
| Kunming | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Liaoning | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Macau | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Nanjing | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Nantong | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Ningbo | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Ningxia | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Qingdao | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Qinghai | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Qingxi | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Sanya | Estimativ | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Albastru — grilă SMILE — hub apropiat / coridor feroviar. |
| Shaanxi | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Shandong | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Shanghai | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Shenzhen | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Shijiazhuang | Estimativ | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | Albastru — grilă SMILE — hub apropiat / coridor feroviar. |
| Sichuan | Estimativ (provincie) | Chengdu (grilă) | 105 | 105 | 120 | 120 | 165 | 165 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Taiwan | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimativ; flux/documentație pot diferi de coridorul China–EU standard. |
| Tianjin | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Tibet | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Urumqi | Estimativ | Estimat (vest CN — nu e în grilă SMILE) | 175 | 182 | 190 | 197 | 235 | 242 | Estimat vest CN; nu e în grila SMILE. |
| Wenzhou | Grilă SMILE | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | — |
| Wuhan | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Xiamen | Grilă SMILE | Albastru — grilă SMILE | 132 | 139 | 147 | 154 | 192 | 199 | — |
| Xian | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Xiangqiao | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Xingfu | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Xingyang | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Xinjiang | Estimativ (provincie) | Estimat (vest CN — nu e în grilă SMILE) | 175 | 182 | 190 | 197 | 235 | 242 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Yiwu | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Yongkang | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |
| Yunnan | Estimativ (provincie) | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Zhejiang | Estimativ (provincie) | Portocaliu — grilă SMILE | 130 | 137 | 145 | 152 | 190 | 197 | Estimare agregată pentru provincie (fără depozit anume în grilă). |
| Zhengzhou | Grilă SMILE | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | — |
| Zhongshan | Estimativ | Galben — grilă SMILE | 125 | 132 | 140 | 147 | 185 | 192 | Galben — grilă SMILE — hub apropiat / coridor feroviar. |

---

## Fișiere înrudite

| Fișier | Rol |
|--------|-----|
| `assets/data/LCL_RAIL_RATES_SMILE_COMPLETE.xlsx` | Aceleași date în Excel |
| `scripts/build_lcl_rail_rates_xlsx.py` | Regenerare xlsx + acest fișier `.md` |
| `assets/js/calculator.js` | `RAIL_BUCHAREST_USD_PER_CBM`, `computeRailTransportPriceEur` |
| `RAIL_LCL_CALCULATION.md` | Formule estimator feroviar (plugin) |
