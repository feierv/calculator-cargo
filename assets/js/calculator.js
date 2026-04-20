/**
 * Shipping calculator – toggle groups, cargo mode panels, add box, live totals
 * Scoped to .my-plugin-calculator
 */
(function () {
	'use strict';

	var run = function () {
		var list = document.querySelectorAll && document.querySelectorAll('.my-plugin-calculator');
		if (!list || !list.length) {
			if (typeof console !== 'undefined' && console.log) console.log('[My Plugin Calculator] Niciun calculator găsit în pagină (.my-plugin-calculator).');
			return;
		}
		for (var i = 0; i < list.length; i++) {
			try {
				initCalculator(list[i]);
				if (typeof console !== 'undefined' && console.log) console.log('[My Plugin Calculator] Inițializat calculatorul #' + (i + 1) + ' – butoanele și formularul sunt active.');
			} catch (err) {
				if (typeof console !== 'undefined' && console.error) console.error('My Plugin Calculator init error:', err);
			}
		}
	};
	window.MyPluginCalculatorInit = run;
	if (typeof console !== 'undefined' && console.log) console.log('[My Plugin Calculator] Script încărcat. Apel run la DOM ready.');

	function parseNum(val) {
		var n = parseFloat(String(val).replace(',', '.'), 10);
		return isNaN(n) ? 0 : n;
	}

	/** Servicii locale China incluse la EXW (€) – aliniat cu UI */
	var PRICE_CHINA_EXW = 417;

	// --- Airfreight estimator (din tabelul tău Untitled-6) ---
	// Notă: tarifele din tabel sunt în USD (și RON pentru local).
	// Calculatorul afișează în EUR, deci folosim conversii estimative (le poți ajusta).
	var AIRFREIGHT_AIR_RATE_USD_PER_KG = 5.18;
	var AIRFREIGHT_TRUCK_MIN_USD = 85;
	var KG_PER_M3 = 167;
	/**
	 * Feroviar LCL — aceeași logică ca în screenshot-uri (hârtie):
	 * 1) CBM taxabil = max(m³, kg/300)
	 * 2) Rail = CBM × tarif din tabel (ușor/dens după kg/m³ vs 300)
	 * 3) Pick-up = CBM × tarif pick-up (default 30 USD/CBM)
	 * 4) Local = CBM × 10 + 50 USD
	 * 5) Extra = CBM × 10 USD/CBM (tabel cut-off etc., ca în exemplu)
	 * 6) TOTAL USD = sumă; 7) EUR = TOTAL × RAIL_USD_TO_EUR (0.92)
	 */
	var RAIL_SMILE_TIER_KG_PER_M3 = 300;
	var RAIL_PICKUP_USD_PER_CBM_DEFAULT = 30;
	var RAIL_PICKUP_USD_PER_CBM = {};
	/** LOCAL: 10 USD/CBM + 50 fix (screenshot PASUL 5). */
	var RAIL_LOCAL_USD_PER_CBM = 10;
	var RAIL_LOCAL_USD_FIXED = 50;
	/** EXTRA: 10 USD/CBM (exemplu: 10 CBM × 10 = 100 USD — PASUL 6). */
	var RAIL_EXTRA_USD_PER_CBM = 10;
	var RAIL_EXTRA_USD_FIXED = 0;
	/** Conversie finală (PASUL 8): 2300 × 0.92 = 2116 EUR. */
	var RAIL_USD_TO_EUR = 0.92;
	/** FOB feroviar (screenshot): USD×0.85, RON÷5.1 → EUR */
	var RAIL_FOB_USD_TO_EUR = 0.85;
	var RAIL_FOB_RON_PER_EUR = 5.1;
	var USD_TO_EUR = 0.92; // aer + același factor pentru consistență afișaj
	var RON_TO_EUR = 0.20; // 1 EUR ~ 5 RON
	var SEA_MIN_SPRINTER_KG = 300;
	var SEA_MIN_FULL_TRUCK_KG = 1200;

	/**
	 * Tarif principal RAIL (USD/CBM) pe origine — ușor / dens (dens ≈ ușor + 7 ca în SMILE).
	 * Beijing: 175 USD/CBM ușor ca în screenshot exemplu (10 CBM × 175 = 1750).
	 * Altele: coloana București din grilă SMILE unde e cazul.
	 */
	var RAIL_BUCHAREST_USD_PER_CBM = {
		Chengdu: [165, 165],
		Beijing: [175, 182],
		Xiamen: [192, 199],
		Haikou: [192, 199],
		Baoding: [192, 199],
		Shijiazhuang: [192, 199],
		Sanya: [192, 199],
		Changzhou: [190, 197],
		Qingdao: [190, 197],
		Tianjin: [190, 197],
		Wenzhou: [190, 197],
		Nanjing: [190, 197],
		Nantong: [190, 197],
		Hangzhou: [190, 197],
		Fuzhou: [190, 197],
		Jining: [190, 197],
		Urumqi: [235, 242]
	};
	var AIR_TRUCK_RATE_BY_CN_CITY = {
		'Anhui': 0.22,
		'Baoding': 0.36,
		'Beijing': 0.36,
		'Changsha': 0.36,
		'Changzhou': 0.22,
		'Chengdu': 0.43,
		'Chongqing': 0.43,
		'Dongchong': 0.43,
		'Dongguan': 0.29,
		'Foshan': 0.29,
		'Fujian': 0.29,
		'Fuzhou': 0.29,
		'Gansu': 0.36,
		'Guangdong': 0.29,
		'Guangxi': 0.36,
		'Guangzhou': 0.29,
		'Guizhou': 0.43,
		'Haikou': 0.43,
		'Hainan': 0.43,
		'Hangzhou': 0.22,
		'Hebei': 0.36,
		'Heilongjiang': 0.58,
		'Henan': 0.29,
		'Hubei': 0.29,
		'Huizhou': 0.22,
		'Hunan': 0.29,
		'Inner Mongolia': 0.58,
		'Jiangsu': 0.22,
		'Jiangxi': 0.29,
		'Jilin': 0.58,
		'Jinhua': 0.22,
		'Jining': 0.58,
		'Kunming': 0.58,
		'Liaoning': 0.58,
		'Nanjing': 0.22,
		'Nantong': 0.22,
		'Ningbo': 0.22,
		'Ningxia': 0.58,
		'Qingdao': 0.36,
		'Qinghai': 0.58,
		'Qingxi': 0.36,
		'Sanya': 0.43,
		'Shaanxi': 0.58,
		'Shandong': 0.36,
		'Shanghai': 0.14,
		'Shenzhen': 0.29,
		'Shijiazhuang': 0.29,
		'Sichuan': 0.43,
		'Tianjin': 0.29,
		'Tibet': 0.80,
		'Urumqi': 0.94,
		'Wenzhou': 0.22,
		'Wuhan': 0.29,
		'Xiamen': 0.29,
		'Xian': 0.58,
		'Xiangqiao': 0.43,
		'Xingfu': 0.43,
		'Xingyang': 0.43,
		'Xinjiang': 0.94,
		'Yiwu': 0.22,
		'Yongkang': 0.22,
		'Yunnan': 0.58,
		'Zhejiang': 0.29,
		'Zhengzhou': 0.29,
		'Zhongshan': 0.29
	};

	// Sea LCL rates (USD/WM) din fișierul Sea.xlsx (Untitled-6).
	var SEA_RATE_USD_PER_WM_BY_ORIGIN = {
		'anhui': 90,
		'baoding': 90,
		'beijing': 90,
		'changsha': 105,
		'changzhou': 85,
		'chengdu': 120,
		'chongqing': 125,
		'dongchong': 85,
		'dongguan': 85,
		'foshan': 88,
		'fujian': 115,
		'fuzhou': 117,
		'gansu': 130,
		'guangdong': 90,
		'guangxi': 105,
		'guangzhou': 95,
		'guizhou': 115,
		'haikou': 120,
		'hainan': 120,
		'hangzhou': 85,
		'hebei': 88,
		'heilongjiang': 115,
		'henan': 105,
		'hong kong': 87,
		'hubei': 100,
		'huizhou': 85,
		'hunan': 105,
		'inner mongolia': 120,
		'jiangsu': 85,
		'jiangxi': 95,
		'jilin': 115,
		'jinhua': 90,
		'jining': 90,
		'kunming': 130,
		'liaoning': 97,
		'macau': 90,
		'nanjing': 85,
		'nantong': 85,
		'ningbo': 82,
		'ningxia': 125,
		'qingdao': 82,
		'qinghai': 140,
		'qingxi': 85,
		'sanya': 125,
		'shaanxi': 110,
		'shandong': 85,
		'shanghai': 82,
		'shenzhen': 82,
		'shijiazhuang': 95,
		'sichuan': 120,
		'taiwan': 120,
		'tianjin': 82,
		'tibet': 150,
		'urumqi': 160,
		'wenzhou': 95,
		'wuhan': 100,
		'xiamen': 115,
		'xian': 110,
		'xiangqiao': 90,
		'xingang': 82,
		'xingfu': 95,
		'xingyang': 105,
		'xinjiang': 150,
		'yiwu': 95,
		'yongkang': 95,
		'yunnan': 125,
		'zhejiang': 90,
		'zhengzhou': 105,
		'zhongshan': 92,
		'nansha': 82,
		'dalian': 97
	};

	// Local road prices după tabelul: OTOPENI -> destinație (RON).
	// Dacă nu găsim destinația, folosim 0 RON.
	var LOCAL_OTOPENI_TO_RON_BY_DEST = {
		// Pentru "CO LOAD" (1-8 paleți) folosim valorile din tabel (unde există).
		// Dacă lipsește în tabel, lăsăm undefined.
		'bucuresti': { full: 1000, sprinter: 500, coload: null },
		'timisoara': { full: 3500, sprinter: 2000, coload: 2000 },
		'cluj napoca': { full: 3200, sprinter: 2500, coload: 1500 },
		'craiova': { full: 2000, sprinter: 1500, coload: 1500 },
		'iasi': { full: 2500, sprinter: 1800, coload: 1800 },
		'arad': { full: 4500, sprinter: 2800, coload: 2000 },
		'galati': { full: 2600, sprinter: 1800, coload: 1800 },
		'brasov': { full: 1800, sprinter: 1200, coload: 900 },
		'satu mare': { full: 4500, sprinter: 2400, coload: 2000 },
		'oradea': { full: 3600, sprinter: 2500, coload: 1600 },
		'odorheiu secuiesc': { full: 2500, sprinter: 1600, coload: 1500 },
		'tg mures': { full: 2500, sprinter: 1500, coload: 1000 },
		'slatina': { full: 1500, sprinter: 800, coload: 800 },
		'ribita': { full: 2500, sprinter: 2000, coload: 1500 },
		'sibiu': { full: 2000, sprinter: 1200, coload: 1200 },
		'deva': { full: 2500, sprinter: 1700, coload: 1500 },
		'alba iulia': { full: 2100, sprinter: 1700, coload: 1600 }
	};
	// Rutier RO din FTL.xlsx pentru maritim (plecare Constanța).
	var LOCAL_CONSTANTA_TO_RON_BY_DEST = {
		'bucuresti': { full: 2000, sprinter: 1200, coload: 800 },
		'timisoara': { full: 5000, sprinter: 3500, coload: 3000 },
		'cluj napoca': { full: 4500, sprinter: 3500, coload: 2600 },
		'craiova': { full: 3500, sprinter: 2000, coload: 1700 },
		'iasi': { full: 3000, sprinter: 1800, coload: 1500 },
		'arad': { full: 5500, sprinter: 3000, coload: 2800 },
		'brasov': { full: 3000, sprinter: 2000, coload: 2000 },
		'galati': { full: 1800, sprinter: 1500, coload: null },
		'satu mare': { full: 5800, sprinter: 3500, coload: 2500 },
		'oradea': { full: 5500, sprinter: 3500, coload: 2500 },
		'targu mursei': { full: 3600, sprinter: 2500, coload: 2000 },
		'targu mures': { full: 3600, sprinter: 2500, coload: 2000 },
		'tg mures': { full: 3600, sprinter: 2500, coload: 2000 },
		'slatina': { full: 2600, sprinter: 2000, coload: 1500 },
		'odorheiu secuiesc': { full: 3000, sprinter: 2000, coload: 1500 },
		'ribita': { full: 4200, sprinter: 2800, coload: 2800 },
		'sibiu': { full: 3500, sprinter: 2500, coload: 2000 },
		'deva': { full: 3700, sprinter: 2600, coload: 2000 },
		'alba iulia': { full: 3700, sprinter: 2500, coload: 2000 }
	};

	function normalizeRoCityName(s) {
		return (s || '')
			.toString()
			.trim()
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/\./g, ' ')
			// elimină cratime și orice separator (ex: "Cluj-Napoca" -> "cluj napoca")
			.replace(/[^a-z0-9]+/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function normalizeCnOriginName(s) {
		return (s || '')
			.toString()
			.trim()
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function getSeaUsdPerWm(originCity) {
		var key = normalizeCnOriginName(originCity);
		return Object.prototype.hasOwnProperty.call(SEA_RATE_USD_PER_WM_BY_ORIGIN, key)
			? SEA_RATE_USD_PER_WM_BY_ORIGIN[key]
			: null;
	}

	function getSeaRoadRon(weightRealKg, roDestCity) {
		var destKey = normalizeRoCityName(roDestCity);
		var road = LOCAL_CONSTANTA_TO_RON_BY_DEST[destKey];
		if (!road) return 0;
		if (weightRealKg >= SEA_MIN_FULL_TRUCK_KG) return road.full || 0;
		if (weightRealKg >= SEA_MIN_SPRINTER_KG) return road.sprinter || 0;
		if (road.coload != null) return road.coload;
		// Dacă CO LOAD nu există în tabel pentru destinația respectivă, folosim Sprinter ca fallback.
		return road.sprinter || 0;
	}

	function computeAirTransportPriceEur(chargeableKg, cnOriginCity, roDestCity) {
		var origin = (cnOriginCity || '').toString().trim();
		var truckRate = AIR_TRUCK_RATE_BY_CN_CITY[origin];
		if (!truckRate) return null;

		// China segment (oraș -> PVG): camion până la aeroport + aer.
		var truckUsd = Math.max(chargeableKg * truckRate, AIRFREIGHT_TRUCK_MIN_USD);
		var airUsd = chargeableKg * AIRFREIGHT_AIR_RATE_USD_PER_KG;
		var chinaEur = (truckUsd + airUsd) * USD_TO_EUR;

		// Local road segment: OTOPENI -> destinație (RON).
		var destKey = normalizeRoCityName(roDestCity);
		var road = LOCAL_OTOPENI_TO_RON_BY_DEST[destKey];
		var roadRon = 0;
		if (road) {
			// Heuristică simplă: "CO LOAD 1-8 PALETI" e pentru încărcări mici.
			// Dacă există valoarea, o folosim sub pragul de 600 kg.
			if (road.coload != null && chargeableKg <= 600) {
				roadRon = road.coload;
			} else {
				roadRon = chargeableKg <= 1200 ? road.sprinter : road.full;
			}
		}
		var roadEur = roadRon * RON_TO_EUR;

		return Math.round(chinaEur + roadEur);
	}

	/**
	 * Estimator feroviar LCL (metodă „pe hârtie”): CBM taxabil = max(V, kg/300); apoi Rail + Pick-up + Local + Extra (USD); EUR = sumă×USD_TO_EUR.
	 * Rutier RO exclus. Tarif rail: coloana București (ușor/dens). Nu folosește coeficient 333 kg/m³.
	 */
	function getRailBucharestUsdPerCbmPair(cnOriginCity) {
		var o = (cnOriginCity || '').toString().trim();
		var p = RAIL_BUCHAREST_USD_PER_CBM[o];
		if (p) return { light: p[0], heavy: p[1] };
		return { light: 185, heavy: 192 };
	}

	function getRailPickupUsdPerCbm(cnOriginCity) {
		var o = (cnOriginCity || '').toString().trim();
		if (Object.prototype.hasOwnProperty.call(RAIL_PICKUP_USD_PER_CBM, o)) {
			return RAIL_PICKUP_USD_PER_CBM[o];
		}
		return RAIL_PICKUP_USD_PER_CBM_DEFAULT;
	}

	function computeRailTransportPriceEur(weightRealKg, volumeM3, cnOriginCity, roDestCity, incotermMode) {
		var origin = (cnOriginCity || '').toString().trim();
		if (!origin) return null;

		var vol = Math.max(0, volumeM3 || 0);
		var w = Math.max(0, weightRealKg || 0);
		var taxableCbm = Math.max(vol, w / RAIL_SMILE_TIER_KG_PER_M3);
		if (taxableCbm <= 0) return null;

		var densityKgPerM3 = vol > 1e-9 ? w / vol : RAIL_SMILE_TIER_KG_PER_M3 + 1;
		var pair = getRailBucharestUsdPerCbmPair(origin);
		var usdRailPerCbm = densityKgPerM3 < RAIL_SMILE_TIER_KG_PER_M3 ? pair.light : pair.heavy;

		var railUsd = taxableCbm * usdRailPerCbm;
		var inc = (incotermMode || 'exw').toString().toLowerCase();
		if (inc === 'fob') {
			var roadRonFob = getSeaRoadRon(w, roDestCity);
			var railEurFob = railUsd * RAIL_FOB_USD_TO_EUR;
			var roadEurFob = roadRonFob / RAIL_FOB_RON_PER_EUR;
			return Math.round(railEurFob + roadEurFob);
		}

		var pickupUsd = taxableCbm * getRailPickupUsdPerCbm(origin);
		var localUsd = taxableCbm * RAIL_LOCAL_USD_PER_CBM + RAIL_LOCAL_USD_FIXED;
		var extraUsd = taxableCbm * RAIL_EXTRA_USD_PER_CBM + RAIL_EXTRA_USD_FIXED;

		var totalUsd = railUsd + pickupUsd + localUsd + extraUsd;
		return Math.round(totalUsd * RAIL_USD_TO_EUR);
	}

	/**
	 * Estimator maritim LCL:
	 * 1) WM taxabil = max(volume_m3, weight_kg / 1000)
	 * 2) cost maritim USD = WM * tarif USD/WM din Sea.xlsx
	 * 3) rutier RO (Constanța -> destinație) din FTL.xlsx, pe praguri de greutate
	 * 4) total EUR = maritim(USD->EUR) + rutier(RON->EUR)
	 */
	function computeSeaTransportPriceEur(weightRealKg, volumeM3, cnOriginCity, roDestCity) {
		var seaRateUsdPerWm = getSeaUsdPerWm(cnOriginCity);
		if (seaRateUsdPerWm == null) return null;

		var vol = Math.max(0, volumeM3 || 0);
		var weight = Math.max(0, weightRealKg || 0);
		var weightTon = weight / 1000;
		var wmTaxable = Math.max(vol, weightTon);
		if (wmTaxable <= 0) return null;

		var seaUsd = wmTaxable * seaRateUsdPerWm;
		var roadRon = getSeaRoadRon(weight, roDestCity);
		var seaEur = seaUsd * USD_TO_EUR;
		var roadEur = roadRon * RON_TO_EUR;
		return Math.round(seaEur + roadEur);
	}

	function getIncotermMode(container) {
		var r = container.querySelector('.mpc-results');
		if (r && !r.classList.contains('mpc-hidden') && r.getAttribute('data-incoterm')) {
			return r.getAttribute('data-incoterm');
		}
		var btn = container.querySelector('.mpc-incoterm.mpc-active');
		return (btn && btn.getAttribute('data-mode')) || 'exw';
	}

	/**
	 * Regula curentă:
	 * - EXW: doar Feroviar selectabil; Aerian + Maritim disabled.
	 * - FOB: Aerian + Maritim + Feroviar selectabile.
	 */
	function applyTransportAvailabilityByIncoterm(container) {
		var inc = getIncotermMode(container);
		var isExw = inc === 'exw';

		function setCardDisabled(card, disabled) {
			if (!card) return;
			var btn = card.querySelector('.mpc-btn-choose');
			card.classList.toggle('mpc-result-card--disabled', disabled);
			card.setAttribute('aria-disabled', disabled ? 'true' : 'false');
			if (btn) {
				btn.disabled = disabled;
				if (disabled) btn.textContent = 'Indisponibil';
				else if (!card.classList.contains('mpc-result-card--selected')) btn.textContent = 'Alegeți';
			}
			if (disabled) {
				card.classList.remove('mpc-result-card--selected');
			}
		}

		var railCard = container.querySelector('.mpc-result-card--rail');
		var airCard = container.querySelector('.mpc-result-card--air');
		var seaCard = container.querySelector('.mpc-result-card--sea');

		setCardDisabled(railCard, false);
		setCardDisabled(airCard, false);
		setCardDisabled(seaCard, isExw);

		// Dacă era selectat un transport devenit indisponibil, curățăm selecția și totalul.
		var selected = container.querySelector('.mpc-result-card--selected');
		if (selected && selected.classList.contains('mpc-result-card--disabled')) {
			resetTransportAndTotal(container);
		}
	}

	function resetServiceSelections(container) {
		container.querySelectorAll('.mpc-service-item--toggle').forEach(function (item) {
			item.classList.remove('mpc-service-item--added');
			var b = item.querySelector('.mpc-btn-add-service');
			if (b) b.textContent = 'Adăugați';
		});
	}

	/**
	 * Secțiunea „Prestatii portuare” + „Declarația de import”: vizibilă doar la Maritim.
	 */
	function updateRailSeaExtrasForContainer(container) {
		var optionalSection = container.querySelector('.mpc-results-optional[data-mpc-rail-sea-extras]');
		var portuareItem = container.querySelector('.mpc-service-item--toggle[data-service-id="portuare"]');
		var portuareBtn = portuareItem ? portuareItem.querySelector('.mpc-btn-add-service') : null;
		var importItem = container.querySelector('.mpc-service-item--toggle[data-service-id="import"]');
		var card = container.querySelector('.mpc-result-card--selected');
		var isSea = !!(card && card.classList.contains('mpc-result-card--sea'));

		if (optionalSection) {
			if (isSea) optionalSection.classList.remove('mpc-hidden');
			else optionalSection.classList.add('mpc-hidden');
		}

		if (!isSea) {
			if (portuareItem) {
				portuareItem.classList.remove('mpc-service-item--added');
				portuareItem.classList.add('mpc-service-item--disabled');
				if (portuareBtn) {
					portuareBtn.disabled = true;
					portuareBtn.textContent = 'Adăugați';
				}
			}
			if (importItem) {
				importItem.classList.remove('mpc-service-item--added');
				var ib = importItem.querySelector('.mpc-btn-add-service');
				if (ib) ib.textContent = 'Adăugați';
				var hsInp = importItem.querySelector('.mpc-import-hs-count');
				if (hsInp) hsInp.value = '2';
			}
		} else {
			if (portuareItem && portuareBtn) {
				portuareItem.classList.remove('mpc-service-item--disabled');
				portuareBtn.disabled = false;
			}
		}

		if (typeof container._mpcUpdateOrderTotal === 'function') {
			var details = container.querySelector('.mpc-total-box-details');
			if (details && !details.classList.contains('mpc-hidden')) {
				container._mpcUpdateOrderTotal();
			}
		}
	}

	function resetTransportAndTotal(container) {
		var btnChooseText = 'Alegeți';
		container.querySelectorAll('.mpc-result-card').forEach(function (c) {
			c.classList.remove('mpc-result-card--selected');
			var b = c.querySelector('.mpc-btn-choose');
			if (b) b.textContent = btnChooseText;
		});
		resetServiceSelections(container);
		updateRailSeaExtrasForContainer(container);
		var totalBox = container.querySelector('.mpc-total-box');
		if (!totalBox) return;
		var hint = totalBox.querySelector('.mpc-total-box-hint');
		var details = totalBox.querySelector('.mpc-total-box-details');
		var moreEl = totalBox.querySelector('.mpc-total-box-more');
		if (hint) hint.classList.remove('mpc-hidden');
		if (details) details.classList.add('mpc-hidden');
		if (moreEl) moreEl.innerHTML = '';
	}

	function initCargoPanels(container) {
		var cargoGroup = container.querySelector('.mpc-toggle-group--cargo');
		if (!cargoGroup) return;

		var panelDimensions = container.querySelector('.mpc-cargo-by-dimensions');
		var panelVolume = container.querySelector('.mpc-cargo-by-volume');
		var boxesContainer = container.querySelector('.mpc-boxes');
		var btnAddBox = container.querySelector('.mpc-btn-add-box');
		var firstBox = container.querySelector('.mpc-box');

		if (!panelDimensions || !panelVolume || !boxesContainer || !firstBox) return;

		// Toggle panels when cargo mode buttons are clicked
		cargoGroup.querySelectorAll('.mpc-cargo-mode').forEach(function (btn) {
			btn.addEventListener('click', function () {
				cargoGroup.querySelectorAll('.mpc-cargo-mode').forEach(function (b) {
					b.classList.remove('mpc-active');
				});
				btn.classList.add('mpc-active');
				var mode = btn.getAttribute('data-mode');
				if (mode === 'dimensions') {
					panelDimensions.classList.remove('mpc-hidden');
					panelVolume.classList.add('mpc-hidden');
				} else {
					panelDimensions.classList.add('mpc-hidden');
					panelVolume.classList.remove('mpc-hidden');
				}
			});
		});

		function updateBoxResult(box) {
			var lInp = box.querySelector('.mpc-box-l');
			var wInp = box.querySelector('.mpc-box-w');
			var hInp = box.querySelector('.mpc-box-h');
			var qtyInp = box.querySelector('.mpc-box-qty');
			var weightInp = box.querySelector('.mpc-box-weight');
			if (!lInp || !wInp || !hInp || !weightInp) return { vol: 0, weight: 0 };
			var l = parseNum(lInp.value);
			var w = parseNum(wInp.value);
			var h = parseNum(hInp.value);
			var qty = Math.max(0, Math.floor(parseNum(qtyInp ? qtyInp.value : 0)));
			var weightPer = parseNum(weightInp.value);
			var volM3 = (l * w * h / 1e6) * (qty > 0 ? qty : 1);
			var weightKg = weightPer * (qty > 0 ? qty : 1);
			var volEl = box.querySelector('.mpc-box-result .mpc-box-volume strong');
			var weightEl = box.querySelector('.mpc-box-result .mpc-box-weight-label strong');
			if (volEl) volEl.textContent = volM3.toFixed(2);
			if (weightEl) weightEl.textContent = weightKg.toFixed(2);
			return { vol: volM3, weight: weightKg };
		}

		function updateAllTotals() {
			var totalVol = 0;
			var totalWeight = 0;
			container.querySelectorAll('.mpc-box').forEach(function (box) {
				var r = updateBoxResult(box);
				totalVol += r.vol;
				totalWeight += r.weight;
			});
			var totalsEl = container.querySelector('.mpc-cargo-totals');
			if (totalsEl) {
				var volEl = totalsEl.querySelector('.mpc-total-volume strong');
				var weightEl = totalsEl.querySelector('.mpc-total-weight strong');
				if (volEl) volEl.textContent = totalVol.toFixed(2);
				if (weightEl) weightEl.textContent = totalWeight.toFixed(2);
			}
		}

		function bindBoxInputs(box) {
			box.querySelectorAll('.mpc-box-l, .mpc-box-w, .mpc-box-h, .mpc-box-qty, .mpc-box-weight').forEach(function (input) {
				input.addEventListener('input', updateAllTotals);
				input.addEventListener('change', updateAllTotals);
			});
		}

		// Delete box
		function setupDeleteBox(box) {
			var btn = box.querySelector('.mpc-btn-delete-box');
			if (!btn) return;
			btn.addEventListener('click', function () {
				var boxes = container.querySelectorAll('.mpc-box');
				if (boxes.length <= 1) return;
				box.remove();
				renumberBoxes();
				updateAllTotals();
			});
		}

		function renumberBoxes() {
			var boxes = container.querySelectorAll('.mpc-box');
			boxes.forEach(function (b, i) {
				b.setAttribute('data-box-index', String(i + 1));
				var title = b.querySelector('.mpc-box-title');
				if (title) title.textContent = 'Cutie ' + (i + 1);
				b.classList.toggle('mpc-box--single', boxes.length === 1);
			});
		}

		// Add another box
		if (btnAddBox) {
			btnAddBox.addEventListener('click', function () {
				var boxes = container.querySelectorAll('.mpc-box');
				var index = boxes.length + 1;
				var clone = firstBox.cloneNode(true);
				clone.classList.remove('mpc-box-template');
				clone.setAttribute('data-box-index', String(index));
				clone.querySelector('.mpc-box-title').textContent = 'Cutie ' + index;
				clone.querySelectorAll('input').forEach(function (inp) {
					inp.value = '';
					inp.removeAttribute('id');
				});
				clone.querySelector('.mpc-box-result .mpc-box-volume strong').textContent = '0.00';
				clone.querySelector('.mpc-box-result .mpc-box-weight-label strong').textContent = '0.00';
				boxesContainer.appendChild(clone);
				bindBoxInputs(clone);
				setupDeleteBox(clone);
				renumberBoxes();
				updateAllTotals();
			});
		}

		setupDeleteBox(firstBox);
		bindBoxInputs(firstBox);
		renumberBoxes();
		updateAllTotals();
	}

	function setProgressStep(container, stepNum) {
		var steps = container.querySelectorAll('.mpc-step');
		steps.forEach(function (s) {
			var n = parseInt(s.getAttribute('data-step'), 10);
			s.classList.remove('mpc-step--active', 'mpc-step--done');
			if (n === stepNum) s.classList.add('mpc-step--active');
			else if (n < stepNum) s.classList.add('mpc-step--done');
		});
	}

	function fillStep2Summary(container) {
		var summary = container.querySelector('.mpc-step2-panel');
		if (!summary) return;
		var tipServiciu = summary.querySelector('.mpc-summary-tip-serviciu');
		var volTotal = summary.querySelector('.mpc-summary-vol-total');
		var volTaxabil = summary.querySelector('.mpc-summary-vol-taxabil');
		var greutate = summary.querySelector('.mpc-summary-greutate');
		var incoterms = summary.querySelector('.mpc-summary-incoterms');
		var loading = summary.querySelector('.mpc-summary-loading');
		var delivery = summary.querySelector('.mpc-summary-delivery');

		var card = container.querySelector('.mpc-result-card--selected');
		var tipText = card ? (card.getAttribute('data-transport-label') || (card.querySelector('.mpc-result-card-name') && card.querySelector('.mpc-result-card-name').textContent) || '—') : '—';
		if (tipServiciu) tipServiciu.textContent = tipText.trim() || '—';

		var resultsEl = container.querySelector('.mpc-results');
		var physVal = resultsEl ? (resultsEl.querySelector('.mpc-results-phys-value') && resultsEl.querySelector('.mpc-results-phys-value').textContent) : null;
		var volVal = resultsEl ? (resultsEl.querySelector('.mpc-results-vol-value') && resultsEl.querySelector('.mpc-results-vol-value').textContent) : null;
		var airW = resultsEl ? (resultsEl.querySelector('.mpc-results-air-weight-value') && resultsEl.querySelector('.mpc-results-air-weight-value').textContent) : null;
		if (volTotal) volTotal.textContent = (physVal || '—') + (physVal ? ' m³' : '');
		if (volTaxabil) volTaxabil.textContent = (volVal || '—') + (volVal ? ' m³' : '');
		if (greutate) greutate.textContent = (airW || '—') + (airW ? ' kg' : '');

		var incotermBtn = container.querySelector('.mpc-incoterm.mpc-active');
		var incotermLabel = incotermBtn ? (incotermBtn.getAttribute('data-mode') === 'exw' ? 'EXW (Ex Works)' : 'FOB (Free On Board)') : '—';
		if (incoterms) incoterms.textContent = incotermLabel;

		var addrRow = container.querySelector('.mpc-address-row');
		var loadCountry = '';
		var loadCity = '';
		var delCountry = '';
		var delCity = '';
		if (addrRow) {
			var sections = addrRow.querySelectorAll('.mpc-section');
			if (sections[0]) {
				var inputs0 = sections[0].querySelectorAll('input[type="text"]');
				if (inputs0[0]) loadCountry = (inputs0[0].value || '').trim();
				var city0 = sections[0].querySelector('.mpc-loading-city');
				if (city0) loadCity = (city0.value || '').trim();
			}
			if (sections[1]) {
				var inputs1 = sections[1].querySelectorAll('input[type="text"]');
				if (inputs1[0]) delCountry = (inputs1[0].value || '').trim();
				var city1 = sections[1].querySelector('.mpc-delivery-city');
				if (city1) delCity = (city1.value || '').trim();
			}
		}
		if (loading) loading.textContent = [loadCountry, loadCity].filter(Boolean).join(', ') || '—';
		if (delivery) delivery.textContent = [delCountry, delCity].filter(Boolean).join(', ') || '—';
	}

	function validateStep2(container) {
		var step2 = container.querySelector('.mpc-step2-panel');
		if (!step2) return { valid: true, errors: [] };
		var errors = [];
		step2.querySelectorAll('.mpc-input--error').forEach(function (el) { el.classList.remove('mpc-input--error'); });
		var validationBox = step2.querySelector('.mpc-step2-validation');
		if (validationBox) validationBox.classList.add('mpc-hidden');

		function addErr(input, msg) {
			if (input) input.classList.add('mpc-input--error');
			if (msg && errors.indexOf(msg) === -1) errors.push(msg);
		}
		function trimVal(el) { return (el && el.value && el.value.trim()) || ''; }
		function digitCount(s) { return (s.match(/\d/g) || []).length; }
		function onlyDigits(s) { return (s || '').replace(/\D/g, ''); }
		var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		// —— Contact ——
		var nume = step2.querySelector('.mpc-contact-nume');
		var prenume = step2.querySelector('.mpc-contact-prenume');
		var telefon = step2.querySelector('.mpc-contact-telefon');
		var email = step2.querySelector('.mpc-contact-email');
		var vNume = trimVal(nume);
		var vPrenume = trimVal(prenume);
		var vTelefon = trimVal(telefon);
		var vEmail = trimVal(email);

		if (!vNume) addErr(nume, 'Completați numele.');
		else if (vNume.length < 2) addErr(nume, 'Numele trebuie să aibă cel puțin 2 caractere.');
		else if (vNume.length > 80) addErr(nume, 'Numele este prea lung.');
		if (!vPrenume) addErr(prenume, 'Completați prenumele.');
		else if (vPrenume.length < 2) addErr(prenume, 'Prenumele trebuie să aibă cel puțin 2 caractere.');
		else if (vPrenume.length > 80) addErr(prenume, 'Prenumele este prea lung.');
		if (!vTelefon) addErr(telefon, 'Completați numărul de telefon.');
		else {
			var digits = digitCount(vTelefon);
			if (digits < 8) addErr(telefon, 'Numărul de telefon trebuie să conțină cel puțin 8 cifre.');
			else if (digits > 15) addErr(telefon, 'Numărul de telefon nu poate avea mai mult de 15 cifre.');
			else if (!/^[0-9+\s\-().]{8,}$/.test(vTelefon)) addErr(telefon, 'Introduceți doar cifre, +, spații sau liniuțe în telefon.');
		}
		if (!vEmail) addErr(email, 'Completați adresa de email.');
		else if (!emailRegex.test(vEmail)) addErr(email, 'Introduceți o adresă de email validă.');
		else if (vEmail.length > 254) addErr(email, 'Adresa de email este prea lungă.');

		// —— Observații (opțional, dar limitat) ——
		var obs = step2.querySelector('.mpc-supplier-observatii');
		if (obs && obs.value && obs.value.length > 2000) addErr(obs, 'Observațiile nu pot depăși 2000 de caractere.');

		return { valid: errors.length === 0, errors: errors };
	}

	function fillStep3Summary(container) {
		var step2 = container.querySelector('.mpc-step2-panel');
		var step3 = container.querySelector('.mpc-step3-panel');
		if (!step2 || !step3) return;
		var card = container.querySelector('.mpc-result-card--selected');
		var transportName = card ? (card.getAttribute('data-transport-label') || (card.querySelector('.mpc-result-card-name') && card.querySelector('.mpc-result-card-name').textContent) || '') : '';
		var titleEl = step3.querySelector('.mpc-step3-transport-name');
		if (titleEl) titleEl.textContent = transportName.trim() || '—';

		function val(el) { return (el && el.value && el.value.trim()) || '—'; }

		step3.querySelector('.mpc-step3-client-nume').textContent = val(step2.querySelector('.mpc-contact-nume'));
		step3.querySelector('.mpc-step3-client-prenume').textContent = val(step2.querySelector('.mpc-contact-prenume'));
		var isFizica = step2.querySelector('.mpc-person-type[data-type="fizica"]') && step2.querySelector('.mpc-person-type[data-type="fizica"]').classList.contains('mpc-active');
		var addrParts = [];
		if (isFizica) {
			var jf = step2.querySelector('.mpc-delivery-judet-fizica');
			addrParts.push(jf && jf.value ? jf.value : '');
			addrParts.push(val(step2.querySelector('.mpc-delivery-localitate-fizica')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-adresa-fizica')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-codpostal-fizica')));
		} else {
			var j = step2.querySelector('.mpc-delivery-judet');
			addrParts.push(j && j.value ? j.value : '');
			addrParts.push(val(step2.querySelector('.mpc-delivery-localitate')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-adresa')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-codpostal')));
		}
		step3.querySelector('.mpc-step3-client-adresa').textContent = addrParts.filter(Boolean).join(', ') || '—';
		step3.querySelector('.mpc-step3-client-telefon').textContent = val(step2.querySelector('.mpc-contact-telefon'));
		step3.querySelector('.mpc-step3-client-email').textContent = val(step2.querySelector('.mpc-contact-email'));

		step3.querySelector('.mpc-step3-supplier-provincie').textContent = val(step2.querySelector('.mpc-supplier-provincie'));
		step3.querySelector('.mpc-step3-supplier-oras').textContent = val(step2.querySelector('.mpc-supplier-oras'));
		step3.querySelector('.mpc-step3-supplier-adresa').textContent = val(step2.querySelector('.mpc-supplier-adresa'));
		step3.querySelector('.mpc-step3-supplier-telefon').textContent = val(step2.querySelector('.mpc-supplier-telefon'));
		step3.querySelector('.mpc-step3-supplier-email').textContent = val(step2.querySelector('.mpc-supplier-email'));

		var resultsEl = container.querySelector('.mpc-results');
		var volVal = resultsEl && resultsEl.querySelector('.mpc-results-vol-value') ? resultsEl.querySelector('.mpc-results-vol-value').textContent : '—';
		var airW = resultsEl && resultsEl.querySelector('.mpc-results-air-weight-value') ? resultsEl.querySelector('.mpc-results-air-weight-value').textContent : '—';
		step3.querySelector('.mpc-step3-cargo-volum').textContent = volVal !== '—' ? volVal + ' m³' : '—';
		step3.querySelector('.mpc-step3-cargo-greutate').textContent = airW !== '—' ? airW + ' kg' : '—';
		step3.querySelector('.mpc-step3-cargo-volum-taxabil').textContent = volVal !== '—' ? volVal + ' m³' : '—';

		var incotermBtn = container.querySelector('.mpc-incoterm.mpc-active');
		step3.querySelector('.mpc-step3-route-incoterms').textContent = incotermBtn ? (incotermBtn.getAttribute('data-mode') === 'exw' ? 'EXW (Ex Works)' : 'FOB (Free On Board)') : '—';
		var addrRow = container.querySelector('.mpc-address-row');
		var loadStr = '—';
		var delStr = '—';
		if (addrRow) {
			var sec0 = addrRow.querySelectorAll('.mpc-section')[0];
			var sec1 = addrRow.querySelectorAll('.mpc-section')[1];
			if (sec0) loadStr = [(sec0.querySelector('input[type="text"]') && sec0.querySelector('input[type="text"]').value) || '', (sec0.querySelector('.mpc-loading-city') && sec0.querySelector('.mpc-loading-city').value) || ''].filter(Boolean).join(', ') || '—';
			if (sec1) delStr = [(sec1.querySelector('input[type="text"]') && sec1.querySelector('input[type="text"]').value) || '', (sec1.querySelector('.mpc-delivery-city') && sec1.querySelector('.mpc-delivery-city').value) || ''].filter(Boolean).join(', ') || '—';
		}
		step3.querySelector('.mpc-step3-route-loading').textContent = loadStr;
		step3.querySelector('.mpc-step3-route-delivery').textContent = delStr;
	}

	/**
	 * Colectează datele pentru email-ul de cotație (pas 3).
	 */
	function collectOrderConfirmationPayload(container) {
		var step2 = container.querySelector('.mpc-step2-panel');
		var step3 = container.querySelector('.mpc-step3-panel');

		function val(el) {
			return (el && el.value && el.value.trim()) || '';
		}
		function valText(el) {
			return (el && el.textContent && el.textContent.trim()) || '';
		}

		var isFizica =
			step2 &&
			step2.querySelector('.mpc-person-type[data-type="fizica"]') &&
			step2.querySelector('.mpc-person-type[data-type="fizica"]').classList.contains('mpc-active');
		var tipPersoana = isFizica ? 'Persoană fizică' : 'Persoană juridică';
		var nume = val(step2.querySelector('.mpc-contact-nume'));
		var prenume = val(step2.querySelector('.mpc-contact-prenume'));
		var numePrenume = [nume, prenume].filter(Boolean).join(' ') || '—';
		var companie = !isFizica
			? (val(step2.querySelector('.mpc-delivery-companie')) || val(step2.querySelector('.mpc-billing-companie')))
			: '';

		var addrParts = [];
		if (isFizica) {
			var jf = step2.querySelector('.mpc-delivery-judet-fizica');
			addrParts.push(jf && jf.value ? jf.value : '');
			addrParts.push(val(step2.querySelector('.mpc-delivery-localitate-fizica')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-adresa-fizica')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-codpostal-fizica')));
		} else {
			var j = step2.querySelector('.mpc-delivery-judet');
			addrParts.push(j && j.value ? j.value : '');
			addrParts.push(val(step2.querySelector('.mpc-delivery-localitate')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-adresa')));
			addrParts.push(val(step2.querySelector('.mpc-delivery-codpostal')));
		}
		var clientAdresa = addrParts.filter(Boolean).join(', ');

		var card = container.querySelector('.mpc-result-card--selected');
		var transportLong = card ? card.getAttribute('data-transport-label') || '' : '';
		var transportShort = card ? card.getAttribute('data-transport-name') || '' : '';
		if (!transportShort && card && card.querySelector('.mpc-result-card-name')) {
			transportShort = card.querySelector('.mpc-result-card-name').textContent.trim();
		}
		var transportEurStr = card ? card.getAttribute('data-transport-price') || '0' : '0';
		var tEur = parseFloat(String(transportEurStr).replace(',', '.'), 10) || 0;
		var inc = getIncotermMode(container);
		var logisticCn = inc === 'exw' ? PRICE_CHINA_EXW : 0;
		var logisticRo = 0;
		var portuareEur = 0;
		var importEur = 0;
		var importHsCount = 2;
		container.querySelectorAll('.mpc-service-item--toggle.mpc-service-item--added').forEach(function (item) {
			var sid = item.getAttribute('data-service-id') || '';
			var pr = parseFloat(item.getAttribute('data-service-price') || '0', 10) || 0;
			if (sid === 'door') logisticRo = pr;
			else if (sid === 'portuare') portuareEur = pr;
			else if (sid === 'import') {
				var hsInp = item.querySelector('.mpc-import-hs-count');
				importHsCount = hsInp ? Math.max(2, Math.floor(parseNum(hsInp.value))) : 2;
				if (importHsCount % 2 !== 0) importHsCount += 1;
				if (hsInp) hsInp.value = String(importHsCount);
				var packageSize = parseInt(item.getAttribute('data-hs-package-size') || '2', 10) || 2;
				var packagePrice = parseFloat(item.getAttribute('data-hs-package-price') || String(pr), 10) || pr;
				importEur = (importHsCount / packageSize) * packagePrice;
			}
		});
		var totalNum = tEur + logisticCn + logisticRo + portuareEur + importEur;

		var tarifeLines = [
			{ label: 'Transport Internațional (freight) China-România', eur: tEur + ' Euro' },
			{ label: 'Servicii logistice locale CN (EXW)', eur: logisticCn + ' Euro' },
			{ label: 'Servicii logistice locale RO — Livrare door to door (TVA nu este inclus în preț)', eur: logisticRo + ' Euro' },
			{ label: 'Prestatii portuare (la cerere, TVA nu este inclus în preț)', eur: portuareEur + ' Euro' },
			{
				label: 'Perfectare declarație de import (100 Euro / 2 coduri HS). Coduri HS: ' + importHsCount,
				eur: importEur + ' Euro'
			}
		];

		var totalDisplay = totalNum + ' Euro';
		var totalBox = container.querySelector('.mpc-total-box');
		if (totalBox) {
			var ta = totalBox.querySelector('.mpc-total-box-total-amount');
			if (ta && ta.textContent) {
				totalDisplay = ta.textContent.replace(/\s+/g, ' ').trim();
			}
		}

		var transportLabel = transportLong || transportShort || '—';
		var resultsForDecl = container.querySelector('.mpc-results');
		var realKgAttr = resultsForDecl && resultsForDecl.getAttribute('data-mpc-real-weight-kg');
		var cargoGreutateDeclarata = '';
		if (realKgAttr !== null && realKgAttr !== '') {
			var realN = parseFloat(realKgAttr, 10);
			if (!isNaN(realN)) cargoGreutateDeclarata = Math.round(realN) + ' kg';
		}

		var cargoTip = step3 ? valText(step3.querySelector('.mpc-step3-cargo-tip')) : 'Generală';
		var cargoVolum = step3 ? valText(step3.querySelector('.mpc-step3-cargo-volum')) : '—';
		var cargoGreutate = step3 ? valText(step3.querySelector('.mpc-step3-cargo-greutate')) : '—';
		var cargoVolTax = step3 ? valText(step3.querySelector('.mpc-step3-cargo-volum-taxabil')) : '—';
		var routeInc = step3 ? valText(step3.querySelector('.mpc-step3-route-incoterms')) : '—';
		var routeLoad = step3 ? valText(step3.querySelector('.mpc-step3-route-loading')) : '—';
		var routeDel = step3 ? valText(step3.querySelector('.mpc-step3-route-delivery')) : '—';

		return {
			transport_label: transportLabel,
			tip_persoana: tipPersoana,
			client_nume: nume,
			client_prenume: prenume,
			nume_prenume: numePrenume,
			companie: companie,
			client_adresa: clientAdresa,
			client_telefon: val(step2.querySelector('.mpc-contact-telefon')),
			client_email: val(step2.querySelector('.mpc-contact-email')),
			supplier_provincie: val(step2.querySelector('.mpc-supplier-provincie')),
			supplier_oras: val(step2.querySelector('.mpc-supplier-oras')),
			supplier_adresa: val(step2.querySelector('.mpc-supplier-adresa')),
			supplier_telefon: val(step2.querySelector('.mpc-supplier-telefon')),
			supplier_email: val(step2.querySelector('.mpc-supplier-email')),
			supplier_observatii: val(step2.querySelector('.mpc-supplier-observatii')),
			cargo_tip: cargoTip,
			cargo_volum: cargoVolum,
			cargo_volum_taxabil: cargoVolTax,
			cargo_greutate_declarata: cargoGreutateDeclarata,
			cargo_greutate: cargoGreutate,
			route_incoterms: routeInc,
			route_loading: routeLoad,
			route_delivery: routeDel,
			tarife_lines: JSON.stringify(tarifeLines),
			total_eur: totalDisplay,
			page_url: typeof window !== 'undefined' && window.location ? window.location.href : ''
		};
	}

	var LOCAL_ORDERS_STORAGE_KEY = 'mpc_local_admin_orders_v1';

	function parseTransportType(transportLabel) {
		var t = (transportLabel || '').toString().toLowerCase();
		if (t.indexOf('feroviar') !== -1) return 'Feroviar';
		if (t.indexOf('maritim') !== -1) return 'Maritim';
		if (t.indexOf('aerian') !== -1) return 'Aerian';
		if (t.indexOf('rutier') !== -1) return 'Rutier';
		return 'Necunoscut';
	}

	function readLocalOrders() {
		try {
			var raw = window.localStorage.getItem(LOCAL_ORDERS_STORAGE_KEY);
			if (!raw) return [];
			var list = JSON.parse(raw);
			return Array.isArray(list) ? list : [];
		} catch (e) {
			return [];
		}
	}

	function writeLocalOrders(list) {
		try {
			window.localStorage.setItem(LOCAL_ORDERS_STORAGE_KEY, JSON.stringify(list || []));
		} catch (e) {}
	}

	function saveOrderToLocalStorage(payload, meta) {
		if (!payload) return;
		var list = readLocalOrders();
		var now = new Date();
		var extra = meta || {};
		var entryId = String(now.getTime()) + '-' + Math.floor(Math.random() * 1000);
		list.unshift({
			id: entryId,
			created_at: now.toISOString(),
			transport_type: parseTransportType(payload.transport_label),
			transport_label: payload.transport_label || '—',
			client_name: payload.nume_prenume || payload.client_nume || '—',
			client_email: payload.client_email || '—',
			route_loading: payload.route_loading || '—',
			route_delivery: payload.route_delivery || '—',
			total_eur: payload.total_eur || '—',
			// Păstrăm payload-ul complet pentru preview admin local.
			payload_full: payload,
			local_status: extra.local_status || 'saved',
			remote_message: extra.remote_message || ''
		});
		// Păstrăm ultimele 500 pentru preview local.
		if (list.length > 500) list = list.slice(0, 500);
		writeLocalOrders(list);
		try {
			document.dispatchEvent(new CustomEvent('mpc:local-order-saved'));
		} catch (e) {}
		return entryId;
	}

	function updateLocalOrderStatus(entryId, patch) {
		if (!entryId) return;
		var list = readLocalOrders();
		var changed = false;
		for (var i = 0; i < list.length; i++) {
			if (list[i] && list[i].id === entryId) {
				list[i].local_status = patch && patch.local_status ? patch.local_status : list[i].local_status;
				list[i].remote_message = patch && patch.remote_message ? patch.remote_message : list[i].remote_message;
				changed = true;
				break;
			}
		}
		if (changed) {
			writeLocalOrders(list);
			try {
				document.dispatchEvent(new CustomEvent('mpc:local-order-saved'));
			} catch (e) {}
		}
	}

	function initLocalOrdersAdminPreview(container) {
		var panel = container.querySelector('.mpc-local-orders-panel');
		if (!panel) return;
		var btnToggle = panel.querySelector('.mpc-local-orders-toggle');
		var body = panel.querySelector('.mpc-local-orders-body');
		var filter = panel.querySelector('.mpc-local-orders-filter');
		var tbody = panel.querySelector('.mpc-local-orders-tbody');
		var btnClear = panel.querySelector('.mpc-local-orders-clear');

		if (!btnToggle || !body || !filter || !tbody) return;

		function fmtDate(iso) {
			try {
				return new Date(iso).toLocaleString();
			} catch (e) {
				return iso || '—';
			}
		}

		function renderRows(rows) {
			tbody.innerHTML = '';
			if (!rows.length) {
				var tr0 = document.createElement('tr');
				var td0 = document.createElement('td');
				td0.colSpan = 8;
				td0.textContent = 'Nu există comenzi salvate.';
				tr0.appendChild(td0);
				tbody.appendChild(tr0);
				return;
			}
			function prettyStatus(s) {
				var v = (s || '').toString();
				if (v === 'queued_local') return 'În așteptare';
				if (v === 'remote_success') return 'Trimis';
				if (v === 'remote_error') return 'Eroare';
				if (v === 'remote_network_error') return 'Eroare rețea';
				return v || '—';
			}
			rows.forEach(function (o) {
				var tr = document.createElement('tr');
				var cells = [
					fmtDate(o.created_at),
					o.transport_type || '—',
					o.client_name || '—',
					o.client_email || '—',
					[o.route_loading || '—', o.route_delivery || '—'].join(' → '),
					o.total_eur || '—',
					prettyStatus(o.local_status),
					o.transport_label || '—'
				];
				cells.forEach(function (c) {
					var td = document.createElement('td');
					td.textContent = c;
					tr.appendChild(td);
				});
				tbody.appendChild(tr);
			});
		}

		function render() {
			var l10n = typeof myPluginCalculatorL10n !== 'undefined' ? myPluginCalculatorL10n : null;
			var v = (filter.value || '').trim();
			if (!l10n || !l10n.ajaxUrl || !l10n.localOrdersListAction) {
				var all = readLocalOrders();
				var rowsFallback = all.filter(function (o) {
					if (!v) return true;
					return (o.transport_type || '') === v;
				});
				renderRows(rowsFallback);
				return;
			}
			var fd = new FormData();
			fd.append('action', l10n.localOrdersListAction);
			if (v) fd.append('transport_type', v);
			fetch(l10n.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (json) {
					if (json && json.success && json.data && Array.isArray(json.data.rows)) {
						renderRows(json.data.rows);
						return;
					}
					renderRows([]);
				})
				.catch(function () {
					renderRows([]);
				});
		}

		btnToggle.addEventListener('click', function () {
			var hidden = body.classList.contains('mpc-hidden');
			body.classList.toggle('mpc-hidden');
			btnToggle.textContent = hidden ? 'Ascunde comenzi' : 'Afișează comenzi';
			if (hidden) render();
		});
		filter.addEventListener('change', render);
		if (btnClear) {
			btnClear.addEventListener('click', function () {
				var l10n = typeof myPluginCalculatorL10n !== 'undefined' ? myPluginCalculatorL10n : null;
				if (!l10n || !l10n.ajaxUrl || !l10n.localOrdersClearAction) {
					writeLocalOrders([]);
					render();
					return;
				}
				var fd = new FormData();
				fd.append('action', l10n.localOrdersClearAction);
				fetch(l10n.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function () {
						render();
					})
					.catch(function () {
						render();
					});
			});
		}
		document.addEventListener('mpc:local-order-saved', render);
	}

	function initStep3SendOffer(container) {
		var step3 = container.querySelector('.mpc-step3-panel');
		if (!step3) return;
		var btn = step3.querySelector('.mpc-btn-offer--send');
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var l10n = typeof myPluginCalculatorL10n !== 'undefined' ? myPluginCalculatorL10n : null;
			if (!l10n || !l10n.ajaxUrl || !l10n.orderConfirmAction) {
				window.alert(
					'Trimiterea nu merge din această pagină: lipsește configurația (ajaxUrl).\n\n' +
						'Pe WordPress: folosește shortcode [shipping_calculator].\n' +
						'Pe localhost: pornește „php -S localhost:8080” în folderul pluginului și deschide calculator-preview.html prin http (nu file://).'
				);
				return;
			}
			if (!l10n.orderConfirmNonce && !l10n.devLocalSkipNonce) {
				window.alert(
					'Lipsește nonce-ul de securitate WordPress. Reîncarcă pagina sau folosește preview-ul local (devLocalSkipNonce).'
				);
				return;
			}
			var payload = collectOrderConfirmationPayload(container);
			if (!payload.client_email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.client_email)) {
				window.alert('Completați un email valid la pasul 2 înainte de trimitere.');
				return;
			}
			// Salvează local entry complet la click pe butonul din Step 3 (preview admin local).
			var localOrderId = saveOrderToLocalStorage(payload, { local_status: 'queued_local' });
			var fd = new FormData();
			fd.append('action', l10n.orderConfirmAction);
			if (l10n.orderConfirmNonce) {
				fd.append('nonce', l10n.orderConfirmNonce);
			}
			Object.keys(payload).forEach(function (k) {
				fd.append(k, payload[k]);
			});
			var origText = btn.textContent;
			var sending = l10n.orderSending || 'Se trimite…';
			btn.disabled = true;
			btn.textContent = sending;
			fetch(l10n.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (json) {
					if (json && json.success && json.data) {
						updateLocalOrderStatus(localOrderId, {
							local_status: 'remote_success',
							remote_message: json.data.message || ''
						});
						window.alert(json.data.message || l10n.orderSuccessMessage || 'OK');
					} else {
						var err =
							json && json.data && json.data.message
								? json.data.message
								: l10n.orderErrorGeneric || 'Eroare';
						updateLocalOrderStatus(localOrderId, {
							local_status: 'remote_error',
							remote_message: err
						});
						window.alert(err);
					}
				})
				.catch(function () {
					updateLocalOrderStatus(localOrderId, {
						local_status: 'remote_network_error',
						remote_message: l10n.orderErrorGeneric || 'Eroare de rețea.'
					});
					window.alert(l10n.orderErrorGeneric || 'Eroare de rețea.');
				})
				.finally(function () {
					btn.disabled = false;
					btn.textContent = origText;
				});
		});
	}

	function initStep2(container) {
		var step1 = container.querySelector('.mpc-step1-panel');
		var step2 = container.querySelector('.mpc-step2-panel');
		var step3 = container.querySelector('.mpc-step3-panel');
		var btnContinue = container.querySelector('.mpc-btn-continue');
		var btnBack = step2 ? step2.querySelector('.mpc-step2-back') : null;
		var msgNoTransport = 'Selectați o opțiune de transport (Alegeți Maritim, Feroviar, Aerian sau Rutier) înainte de a continua.';
		var warningsBox = container.querySelector('.mpc-warnings');
		var warningsList = container.querySelector('.mpc-warnings-list');

		if (!step1 || !step2) return;

		function goToStep1() {
			step1.classList.remove('mpc-hidden');
			step2.classList.add('mpc-hidden');
			if (step3) step3.classList.add('mpc-hidden');
			setProgressStep(container, 1);
		}
		function goToStep2() {
			var hasTransport = container.querySelector('.mpc-result-card--selected');
			if (!hasTransport) {
				if (warningsBox && warningsList) {
					warningsList.innerHTML = '';
					var li = document.createElement('li');
					li.textContent = msgNoTransport;
					warningsList.appendChild(li);
					warningsBox.classList.remove('mpc-hidden');
					warningsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
				return;
			}
			if (warningsBox) warningsBox.classList.add('mpc-hidden');
			fillStep2Summary(container);
			step1.classList.add('mpc-hidden');
			step2.classList.remove('mpc-hidden');
			setProgressStep(container, 2);
			step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
		function goToStep3() {
			fillStep3Summary(container);
			step2.classList.add('mpc-hidden');
			if (step3) step3.classList.remove('mpc-hidden');
			setProgressStep(container, 3);
			if (step3) step3.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
		function goToStep2FromStep3() {
			if (step3) {
				step3.classList.add('mpc-hidden');
			}
			step2.classList.remove('mpc-hidden');
			setProgressStep(container, 2);
			step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		if (btnContinue) btnContinue.addEventListener('click', goToStep2);
		if (btnBack) btnBack.addEventListener('click', goToStep1);

		// Person type toggle (Fizică / Juridică) – schimbă panoul vizibil
		var panelFizica = step2.querySelector('.mpc-delivery-panel--fizica');
		var panelJuridica = step2.querySelector('.mpc-delivery-panel--juridica');
		var personTypeGroup = step2.querySelector('.mpc-step2-person-type');
		if (personTypeGroup) {
			personTypeGroup.querySelectorAll('.mpc-person-type').forEach(function (btn) {
				btn.addEventListener('click', function () {
					personTypeGroup.querySelectorAll('.mpc-person-type').forEach(function (b) { b.classList.remove('mpc-active'); });
					btn.classList.add('mpc-active');
					var type = btn.getAttribute('data-type');
					if (type === 'fizica') {
						if (panelFizica) panelFizica.classList.remove('mpc-hidden');
						if (panelJuridica) {
							panelJuridica.classList.add('mpc-hidden');
							panelJuridica.querySelectorAll('.mpc-input--error').forEach(function (e) { e.classList.remove('mpc-input--error'); });
						}
					} else {
						if (panelFizica) {
							panelFizica.classList.add('mpc-hidden');
							panelFizica.querySelectorAll('.mpc-input--error').forEach(function (e) { e.classList.remove('mpc-input--error'); });
						}
						if (panelJuridica) panelJuridica.classList.remove('mpc-hidden');
					}
				});
			});
		}

		// Județ dropdown: populate with Romanian counties (pentru ambele panouri)
		var judete = ['Alba', 'Arad', 'Argeș', 'Bacău', 'Bihor', 'Bistrița-Năsăud', 'Botoșani', 'Brăila', 'Brașov', 'București', 'Buzău', 'Călărași', 'Caraș-Severin', 'Cluj', 'Constanța', 'Covasna', 'Dâmbovița', 'Dolj', 'Galați', 'Giurgiu', 'Gorj', 'Harghita', 'Hunedoara', 'Ialomița', 'Iași', 'Ilfov', 'Maramureș', 'Mehedinți', 'Mureș', 'Neamț', 'Olt', 'Prahova', 'Sălaj', 'Satu Mare', 'Sibiu', 'Suceava', 'Teleorman', 'Timiș', 'Tulcea', 'Vâlcea', 'Vaslui', 'Vrancea'];
		function fillJudetSelect(select) {
			if (!select || select.options.length > 1) return;
			judete.forEach(function (j) {
				var opt = document.createElement('option');
				opt.value = j;
				opt.textContent = j;
				select.appendChild(opt);
			});
		}
		fillJudetSelect(step2.querySelector('.mpc-delivery-judet'));
		fillJudetSelect(step2.querySelector('.mpc-delivery-judet-fizica'));
		fillJudetSelect(step2.querySelector('.mpc-billing-judet-fizica'));
		fillJudetSelect(step2.querySelector('.mpc-billing-judet'));

		// Checkbox „Doresc adresă de facturare diferită” – afișează/ascunde blocul de adresă facturare
		var billingWrap = step2.querySelector('.mpc-billing-wrap');
		var billingCheck = step2.querySelector('.mpc-delivery-facturare-diferita');
		if (billingCheck && billingWrap) {
			billingCheck.addEventListener('change', function () {
				if (billingCheck.checked) billingWrap.classList.remove('mpc-hidden');
				else billingWrap.classList.add('mpc-hidden');
			});
		}

		// Toggle Fizică/Juridică pentru adresa de facturare
		var billingPanelFizica = step2.querySelector('.mpc-billing-panel--fizica');
		var billingPanelJuridica = step2.querySelector('.mpc-billing-panel--juridica');
		var billingTypeGroup = step2.querySelector('.mpc-billing-person-type');
		if (billingTypeGroup) {
			billingTypeGroup.querySelectorAll('.mpc-billing-type').forEach(function (btn) {
				btn.addEventListener('click', function () {
					billingTypeGroup.querySelectorAll('.mpc-billing-type').forEach(function (b) { b.classList.remove('mpc-active'); });
					btn.classList.add('mpc-active');
					var type = btn.getAttribute('data-type');
					if (type === 'fizica') {
						if (billingPanelFizica) billingPanelFizica.classList.remove('mpc-hidden');
						if (billingPanelJuridica) {
							billingPanelJuridica.classList.add('mpc-hidden');
							billingPanelJuridica.querySelectorAll('.mpc-input--error').forEach(function (e) { e.classList.remove('mpc-input--error'); });
						}
					} else {
						if (billingPanelFizica) {
							billingPanelFizica.classList.add('mpc-hidden');
							billingPanelFizica.querySelectorAll('.mpc-input--error').forEach(function (e) { e.classList.remove('mpc-input--error'); });
						}
						if (billingPanelJuridica) billingPanelJuridica.classList.remove('mpc-hidden');
					}
				});
			});
		}

		// La tastare în orice câmp din pasul 2, scoate starea de eroare doar de pe acel câmp (feedback imediat)
		step2.querySelectorAll('input, select, textarea').forEach(function (el) {
			function clearThisError() {
				el.classList.remove('mpc-input--error');
			}
			el.addEventListener('input', clearThisError);
			el.addEventListener('change', clearThisError);
		});

		// Step 2 "Continuați" – validare apoi trecere la Step 3
		var btnStep2Continue = step2.querySelector('.mpc-btn-step2-continue');
		var validationBox = step2.querySelector('.mpc-step2-validation');
		if (btnStep2Continue) {
			btnStep2Continue.addEventListener('click', function () {
				var result = validateStep2(container);
				if (!result.valid) {
					if (validationBox) {
						validationBox.textContent = result.errors.join(' ');
						validationBox.classList.remove('mpc-hidden');
						validationBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
					var firstErr = step2.querySelector('.mpc-input--error');
					if (firstErr) firstErr.focus();
					return;
				}
				if (validationBox) validationBox.classList.add('mpc-hidden');
				goToStep3();
			});
		}

		// Step 3: doar Înapoi (emiterea cotației = formularul DVG, buton „Trimite”)
		if (step3) {
			var btnStep3Back = step3.querySelector('.mpc-step3-back');
			if (btnStep3Back) btnStep3Back.addEventListener('click', goToStep2FromStep3);
		}
	}

	function initExcelRatesPanel(container) {
		var panel = container.querySelector('.mpc-excel-rates-panel');
		if (!panel) return;
		var fileInput = panel.querySelector('.mpc-excel-rates-file');
		var btnSim = panel.querySelector('.mpc-excel-rates-simulate-upload');
		var statusEl = panel.querySelector('.mpc-excel-rates-filename');
		var msgReady = 'Fișier selectat (preview local): ';
		var msgSent = 'Simulare încărcare: doar vizual pe această pagină. Pentru salvare pe server folosiți Setări → My Plugin în WordPress.';

		function showStatus(text, isError) {
			if (!statusEl) return;
			statusEl.textContent = text;
			statusEl.classList.remove('mpc-hidden');
			statusEl.classList.toggle('mpc-excel-rates-filename--error', !!isError);
		}

		if (fileInput) {
			fileInput.addEventListener('change', function () {
				var f = fileInput.files && fileInput.files[0];
				if (f) {
					showStatus(msgReady + f.name, false);
				} else if (statusEl) {
					statusEl.classList.add('mpc-hidden');
				}
			});
		}
		if (btnSim) {
			btnSim.addEventListener('click', function () {
				var f = fileInput && fileInput.files && fileInput.files[0];
				if (!f) {
					if (fileInput) fileInput.click();
					return;
				}
				showStatus(msgSent, false);
			});
		}
	}

	function initTotalBox(container) {
		var totalBox = container.querySelector('.mpc-total-box');
		if (!totalBox) return;
		var hint = totalBox.querySelector('.mpc-total-box-hint');
		var details = totalBox.querySelector('.mpc-total-box-details');
		var transportLine = totalBox.querySelector('.mpc-total-box-line--transport');
		if (!transportLine) transportLine = totalBox.querySelector('.mpc-total-box-details .mpc-total-box-line');
		var labelEl = transportLine ? transportLine.querySelector('.mpc-total-box-label') : totalBox.querySelector('.mpc-total-box-label');
		var priceEl = transportLine ? transportLine.querySelector('.mpc-total-box-price') : totalBox.querySelector('.mpc-total-box-price');
		var totalAmountEl = totalBox.querySelector('.mpc-total-box-total-amount');
		var moreEl = totalBox.querySelector('.mpc-total-box-more');
		var btnChooseText = 'Alegeți';
		var btnSelectedText = 'Selectat';
		var importItem = container.querySelector('.mpc-service-item--toggle[data-service-id="import"]');

		function normalizeHsCount(rawVal) {
			// Cerință: creștere din 2 în 2 (2,4,6,...)
			var n = Math.floor(parseNum(rawVal) || 2);
			if (n < 2) n = 2;
			if (n % 2 !== 0) n += 1;
			return n;
		}

		function updateOrderTotal() {
			var card = container.querySelector('.mpc-result-card--selected');
			if (!card || !details || details.classList.contains('mpc-hidden')) return;
			var transportPrice = parseFloat(card.getAttribute('data-transport-price') || '0', 10) || 0;
			var inc = getIncotermMode(container);
			var china = inc === 'exw' ? PRICE_CHINA_EXW : 0;
			var extrasSum = 0;
			if (moreEl) moreEl.innerHTML = '';
			if (inc === 'exw' && china > 0 && moreEl) {
				var pCn = document.createElement('p');
				pCn.className = 'mpc-total-box-line mpc-total-box-line--extra';
				pCn.innerHTML = '<span class="mpc-total-box-label"></span><strong class="mpc-total-box-price">€ ' + china + '</strong>';
				pCn.querySelector('.mpc-total-box-label').textContent = 'Servicii locale China (EXW)';
				moreEl.appendChild(pCn);
			}
			container.querySelectorAll('.mpc-service-item--toggle.mpc-service-item--added').forEach(function (item) {
				var price = parseFloat(item.getAttribute('data-service-price') || '0', 10) || 0;
				var label = item.getAttribute('data-service-label') || '';
				var sid = item.getAttribute('data-service-id') || '';
				if (sid === 'import') {
					var hsInp = item.querySelector('.mpc-import-hs-count');
					var hsCount = hsInp ? normalizeHsCount(hsInp.value) : 2;
					if (hsInp) hsInp.value = String(hsCount);
					var packageSize = parseInt(item.getAttribute('data-hs-package-size') || '2', 10) || 2;
					var packagePrice = parseFloat(item.getAttribute('data-hs-package-price') || String(price), 10) || price;
					price = (hsCount / packageSize) * packagePrice;
					label = 'Declarația de import (' + hsCount + ' coduri HS)';
				}
				extrasSum += price;
				if (moreEl) {
					var p = document.createElement('p');
					p.className = 'mpc-total-box-line mpc-total-box-line--extra';
					p.innerHTML = '<span class="mpc-total-box-label"></span><strong class="mpc-total-box-price">€ ' + price + '</strong>';
					p.querySelector('.mpc-total-box-label').textContent = label;
					moreEl.appendChild(p);
				}
			});
			var total = transportPrice + china + extrasSum;
			var label = card.getAttribute('data-transport-label') || card.querySelector('.mpc-result-card-name');
			if (label && label.nodeName) label = label.textContent;
			if (labelEl) labelEl.textContent = label || '';
			if (priceEl) priceEl.textContent = '€ ' + transportPrice;
			if (totalAmountEl) totalAmountEl.textContent = '€ ' + total;
		}

		try {
			container._mpcUpdateOrderTotal = updateOrderTotal;
		} catch (e) {}

		container.querySelectorAll('.mpc-result-card').forEach(function (card) {
			var btn = card.querySelector('.mpc-btn-choose');
			if (!btn) return;
			btn.addEventListener('click', function () {
				if (btn.disabled || card.classList.contains('mpc-result-card--disabled')) return;
				container.querySelectorAll('.mpc-result-card').forEach(function (c) {
					c.classList.remove('mpc-result-card--selected');
					var b = c.querySelector('.mpc-btn-choose');
					if (b) b.textContent = btnChooseText;
				});
				card.classList.add('mpc-result-card--selected');
				btn.textContent = btnSelectedText;
				var price = card.getAttribute('data-transport-price') || '0';
				if (hint) hint.classList.add('mpc-hidden');
				if (details) {
					details.classList.remove('mpc-hidden');
					var label = card.getAttribute('data-transport-label') || card.querySelector('.mpc-result-card-name');
					if (label && label.nodeName) label = label.textContent;
					if (labelEl) labelEl.textContent = label || '';
					if (priceEl) priceEl.textContent = '€ ' + price;
					updateOrderTotal();
					updateRailSeaExtrasForContainer(container);
				}
			});
		});

		updateRailSeaExtrasForContainer(container);

		container.addEventListener('click', function (e) {
			var btn = e.target.closest && e.target.closest('.mpc-btn-add-service');
			if (!btn || !container.contains(btn)) return;
			var item = btn.closest('.mpc-service-item');
			if (!item || !item.classList.contains('mpc-service-item--toggle')) return;
			if (btn.disabled || item.classList.contains('mpc-service-item--disabled')) return;
			e.preventDefault();
			var added = item.classList.toggle('mpc-service-item--added');
			btn.textContent = added ? 'Scoateți' : 'Adăugați';
			if (container.querySelector('.mpc-result-card--selected') && details && !details.classList.contains('mpc-hidden')) {
				updateOrderTotal();
			}
		});

		// Recalculează totalul dacă se schimbă numărul de coduri HS.
		if (importItem) {
			var hsInput = importItem.querySelector('.mpc-import-hs-count');
			if (hsInput) {
				var recalcOnHsChange = function () {
					var v = normalizeHsCount(hsInput.value);
					hsInput.value = String(v);
					if (
						importItem.classList.contains('mpc-service-item--added') &&
						container.querySelector('.mpc-result-card--selected') &&
						details &&
						!details.classList.contains('mpc-hidden')
					) {
						updateOrderTotal();
					}
				};
				hsInput.addEventListener('input', recalcOnHsChange);
				hsInput.addEventListener('change', recalcOnHsChange);
			}
		}
	}

	/**
	 * Completează <datalist> gol pentru oraș livrare RO (preview static / fallback).
	 */
	function initRoCitiesDatalist(container) {
		var el = container.querySelector('.mpc-delivery-city');
		if (!el) return;
		var url = null;
		if (typeof myPluginCalculatorL10n !== 'undefined' && myPluginCalculatorL10n.roCitiesJsonUrl) {
			url = myPluginCalculatorL10n.roCitiesJsonUrl;
		} else if (typeof window.myPluginRoCitiesJsonUrl === 'string') {
			url = window.myPluginRoCitiesJsonUrl;
		}
		if (!url || typeof fetch !== 'function') return;
		// <select>: umple opțiunile din JSON când există doar placeholder (preview static).
		if (el.nodeName === 'SELECT') {
			if (el.options.length > 1) return;
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (cities) {
					if (!Array.isArray(cities) || !el || el.nodeName !== 'SELECT') return;
					cities.forEach(function (c) {
						var o = document.createElement('option');
						o.value = c;
						o.textContent = c;
						el.appendChild(o);
					});
				})
				.catch(function () {});
			return;
		}
		// <input list="..."> + datalist gol (fallback vechi).
		var listId = el.getAttribute('list');
		if (!listId) return;
		var dl = document.getElementById(listId);
		if (!dl || dl.querySelectorAll('option').length > 0) return;
		fetch(url, { credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (cities) {
				if (!Array.isArray(cities) || !dl) return;
				cities.forEach(function (c) {
					var o = document.createElement('option');
					o.value = c;
					dl.appendChild(o);
				});
			})
			.catch(function () {});
	}

	/**
	 * Completează <datalist> gol pentru oraș preluare China (preview static / fallback).
	 */
	function initCnCitiesDatalist(container) {
		var el = container.querySelector('.mpc-loading-city');
		if (!el) return;

		var url = null;
		if (typeof myPluginCalculatorL10n !== 'undefined' && myPluginCalculatorL10n.cnCitiesJsonUrl) {
			url = myPluginCalculatorL10n.cnCitiesJsonUrl;
		} else if (typeof window.myPluginCnCitiesJsonUrl === 'string') {
			url = window.myPluginCnCitiesJsonUrl;
		}
		if (!url || typeof fetch !== 'function') return;

		// <select>: umple opțiunile din JSON când există doar placeholder (preview static).
		if (el.nodeName === 'SELECT') {
			if (el.options.length > 1) return;
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (cities) {
					if (!Array.isArray(cities) || !el || el.nodeName !== 'SELECT') return;
					cities.forEach(function (c) {
						var o = document.createElement('option');
						o.value = c;
						o.textContent = c;
						el.appendChild(o);
					});
				})
				.catch(function () {});
			return;
		}

		// <input list="..."> + datalist gol (fallback vechi).
		var listId = el.getAttribute('list');
		if (!listId) return;
		var dl = document.getElementById(listId);
		if (!dl || dl.querySelectorAll('option').length > 0) return;

		fetch(url, { credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (cities) {
				if (!Array.isArray(cities) || !dl) return;
				cities.forEach(function (c) {
					var o = document.createElement('option');
					o.value = c;
					dl.appendChild(o);
				});
			})
			.catch(function () {});
	}

	function initCalculator(container) {
		if (!container || container.dataset.mpcInited === '1') return;
		try {
			container.dataset.mpcInited = '1';
		} catch (e) {
			return;
		}

		initRoCitiesDatalist(container);
		initCnCitiesDatalist(container);

		// Cargo mode: delegated click so buttons always work (e.g. if initCargoPanels bails out)
		container.addEventListener('click', function (e) {
			var target = e.target;
			var btn = null;
			if (target && typeof target.closest === 'function') {
				btn = target.closest('.mpc-toggle-group--cargo .mpc-cargo-mode');
			}
			if (!btn) return;
			var cargoGroup = container.querySelector('.mpc-toggle-group--cargo');
			var panelDimensions = container.querySelector('.mpc-cargo-by-dimensions');
			var panelVolume = container.querySelector('.mpc-cargo-by-volume');
			if (!cargoGroup || !panelDimensions || !panelVolume) return;
			cargoGroup.querySelectorAll('.mpc-cargo-mode').forEach(function (b) {
				b.classList.remove('mpc-active');
			});
			btn.classList.add('mpc-active');
			var mode = btn.getAttribute('data-mode');
			if (mode === 'dimensions') {
				panelDimensions.classList.remove('mpc-hidden');
				panelVolume.classList.add('mpc-hidden');
			} else {
				panelDimensions.classList.add('mpc-hidden');
				panelVolume.classList.remove('mpc-hidden');
			}
		});

		// Generic toggle groups (e.g. INCOTERMS)
		container.querySelectorAll('.mpc-toggle-group:not(.mpc-toggle-group--cargo)').forEach(function (group) {
			group.querySelectorAll('button').forEach(function (btn) {
				btn.addEventListener('click', function () {
					group.querySelectorAll('button').forEach(function (b) {
						b.classList.remove('mpc-active');
					});
					btn.classList.add('mpc-active');
					// La click pe INCOTERMS (FOB/EXW): revine sus și ascunde rezultatele (nu se afișează nimic mai jos)
					var incotermGroup = container.querySelector('.mpc-toggle-group--incoterms');
					if (group === incotermGroup && incotermGroup) {
						applyTransportAvailabilityByIncoterm(container);
						var resultsEl = container.querySelector('.mpc-results');
						if (resultsEl) {
							var wasVisible = !resultsEl.classList.contains('mpc-hidden');
							resultsEl.classList.add('mpc-hidden');
							if (wasVisible) resetTransportAndTotal(container);
						}
						incotermGroup.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				});
			});
		});

		// Cargo mode panels + add box + totals (live totals, add box, etc.)
		initCargoPanels(container);
		applyTransportAvailabilityByIncoterm(container);

		// Total comandă: la click pe Alegeți actualizează caseta din dreapta
		initTotalBox(container);

		// Panou Excel sub Total comandă (preview interfață admin – fără upload real pe frontend)
		initExcelRatesPanel(container);
		// Preview local comenzi (tip admin): tabel ascuns + filtru pe transport
		initLocalOrdersAdminPreview(container);

		// Pas 3: trimite cotația (HTML) prin Resend / wp_mail.
		initStep3SendOffer(container);

		// Pasul 2: Continuați → Detalii ofertă; Înapoi → rezultate
		initStep2(container);

		// Validation and warnings on Calculați click
		var warningsBox = container.querySelector('.mpc-warnings');
		var warningsList = container.querySelector('.mpc-warnings-list');
		var msgOriginCity = 'Vă rugăm să selectați orașul de origine.';
		var msgDestinationCity = 'Vă rugăm să selectați orașul de destinație.';
		var msgDimensions = 'Vă rugăm să introduceți dimensiuni valide pentru cel puțin o cutie.';
		var msgWeight = 'Vă rugăm să introduceți o greutate validă pentru cel puțin o cutie.';
		var msgMinWeight = 'Greutatea minimă este de 200 kg.';
		var msgVolumeTotal = 'Vă rugăm să introduceți volumul și greutatea.';

		container.querySelectorAll('.mpc-btn-calculate').forEach(function (btn) {
			btn.addEventListener('click', function () {
				// Clear previous state
				container.querySelectorAll('.mpc-input--error').forEach(function (el) {
					el.classList.remove('mpc-input--error');
				});
				if (warningsBox) {
					warningsBox.classList.add('mpc-hidden');
					if (warningsList) warningsList.innerHTML = '';
				}

				var errors = [];
				var loadingCity = container.querySelector('.mpc-loading-city');
				var deliveryCity = container.querySelector('.mpc-delivery-city');
				var panelDimensions = container.querySelector('.mpc-cargo-by-dimensions');
				var panelVolume = container.querySelector('.mpc-cargo-by-volume');
				var isDimensionsMode = panelDimensions && !panelDimensions.classList.contains('mpc-hidden');

				if (loadingCity) {
					var originVal = (loadingCity.value || '').trim();
					if (!originVal) {
						errors.push(msgOriginCity);
						loadingCity.classList.add('mpc-input--error');
					}
				}
				if (deliveryCity) {
					var destVal = (deliveryCity.value || '').trim();
					if (!destVal) {
						errors.push(msgDestinationCity);
						deliveryCity.classList.add('mpc-input--error');
					}
				}

				if (isDimensionsMode) {
					var hasValidBox = false;
					container.querySelectorAll('.mpc-box').forEach(function (box) {
						var lInp = box.querySelector('.mpc-box-l');
						var wInp = box.querySelector('.mpc-box-w');
						var hInp = box.querySelector('.mpc-box-h');
						var weightInp = box.querySelector('.mpc-box-weight');
						if (!lInp || !wInp || !hInp || !weightInp) return;
						var l = parseNum(lInp.value);
						var w = parseNum(wInp.value);
						var h = parseNum(hInp.value);
						var weightPer = parseNum(weightInp.value);
						if (l > 0 && w > 0 && h > 0 && weightPer > 0) hasValidBox = true;
						else {
							if (l > 0 || w > 0 || h > 0) {
								box.querySelectorAll('.mpc-box-l, .mpc-box-w, .mpc-box-h').forEach(function (inp) {
									if (parseNum(inp.value) <= 0) inp.classList.add('mpc-input--error');
								});
							}
							if (weightPer <= 0 && (l > 0 || w > 0 || h > 0)) {
								weightInp.classList.add('mpc-input--error');
							}
						}
					});
					if (!hasValidBox) {
						errors.push(msgDimensions);
						errors.push(msgWeight);
						container.querySelectorAll('.mpc-box').forEach(function (box) {
							box.querySelectorAll('.mpc-box-l, .mpc-box-w, .mpc-box-h, .mpc-box-weight').forEach(function (inp) {
								inp.classList.add('mpc-input--error');
							});
						});
					} else {
						// Constraint: utilizatorul nu poate introduce mai puțin de 200 kg (total).
						var totalWeight = 0;
						container.querySelectorAll('.mpc-box').forEach(function (box) {
							var weightInp = box.querySelector('.mpc-box-weight');
							var qtyInp = box.querySelector('.mpc-box-qty');
							if (!weightInp) return;
							var weightPer = parseNum(weightInp.value);
							var qty = Math.max(0, Math.floor(parseNum(qtyInp ? qtyInp.value : 0)));
							var weightKg = weightPer * (qty > 0 ? qty : 1);
							totalWeight += weightKg;
						});
						if (totalWeight < 200) {
							errors.push(msgMinWeight);
							container.querySelectorAll('.mpc-box').forEach(function (box) {
								var weightInp = box.querySelector('.mpc-box-weight');
								if (weightInp) weightInp.classList.add('mpc-input--error');
							});
						}
					}
				} else {
					var volInput = container.querySelector('.mpc-input-volume');
					var weightInput = container.querySelector('.mpc-input-weight');
					if (volInput && weightInput) {
						var v = parseNum(volInput.value);
						var w = parseNum(weightInput.value);
						if (v <= 0 || w <= 0) {
							errors.push(msgVolumeTotal);
							if (v <= 0) volInput.classList.add('mpc-input--error');
							if (w <= 0) weightInput.classList.add('mpc-input--error');
						} else if (w < 200) {
							// Constraint: utilizatorul nu poate introduce mai puțin de 200 kg (total).
							errors.push(msgMinWeight);
							weightInput.classList.add('mpc-input--error');
						}
					}
				}

				if (errors.length > 0 && warningsBox && warningsList) {
					errors.forEach(function (text) {
						var li = document.createElement('li');
						li.textContent = text;
						warningsList.appendChild(li);
					});
					warningsBox.classList.remove('mpc-hidden');
					warningsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					// Ascunde doar rezultatele dacă sunt erori (caseta Total rămâne vizibilă)
					var resultsEl = container.querySelector('.mpc-results');
					if (resultsEl) resultsEl.classList.add('mpc-hidden');
				} else if (errors.length === 0) {
					var resultsEl = container.querySelector('.mpc-results');
					if (resultsEl) {
						var incotermBtn = container.querySelector('.mpc-incoterm.mpc-active');
						var incoterm = (incotermBtn && incotermBtn.getAttribute('data-mode')) || 'exw';
						resultsEl.setAttribute('data-incoterm', incoterm);
						// Placeholder: pune volumul/greutatea din formular în rezultate (până la logica reală)
						var volVal = '1.00';
						var weightVal = '167';
						var chargeKg = 167;
						var volNum = 1.0;
						var weightRealNum = 0;
						var panelDimensions = container.querySelector('.mpc-cargo-by-dimensions');
						var isDimensionsMode = panelDimensions && !panelDimensions.classList.contains('mpc-hidden');
						if (isDimensionsMode) {
							var totalVol = 0;
							var totalWeight = 0;
							container.querySelectorAll('.mpc-box').forEach(function (box) {
								var lInp = box.querySelector('.mpc-box-l');
								var wInp = box.querySelector('.mpc-box-w');
								var hInp = box.querySelector('.mpc-box-h');
								var qtyInp = box.querySelector('.mpc-box-qty');
								var weightInp = box.querySelector('.mpc-box-weight');
								if (!lInp || !wInp || !hInp || !weightInp) return;
								var l = parseNum(lInp.value);
								var w = parseNum(wInp.value);
								var h = parseNum(hInp.value);
								var qty = Math.max(0, Math.floor(parseNum(qtyInp ? qtyInp.value : 0)));
								var weightPer = parseNum(weightInp.value);
								totalVol += (l * w * h / 1e6) * (qty || 1);
								totalWeight += weightPer * (qty || 1);
							});
							volNum = totalVol;
							weightRealNum = totalWeight;
							chargeKg = Math.max(weightRealNum, volNum * KG_PER_M3, 200);
							volVal = volNum.toFixed(2);
							weightVal = Math.round(chargeKg).toString();
						} else {
							var volInput = container.querySelector('.mpc-input-volume');
							var weightInput = container.querySelector('.mpc-input-weight');
							if (volInput) volNum = parseNum(volInput.value) || 1.0;
							if (weightInput) weightRealNum = parseNum(weightInput.value) || 0;
							chargeKg = Math.max(weightRealNum, volNum * KG_PER_M3, 200);
							if (volInput) volVal = volNum.toFixed(2) || '1.00';
							weightVal = Math.round(chargeKg).toString() || '167';
						}
						try {
							resultsEl.setAttribute('data-mpc-real-weight-kg', String(Math.round(weightRealNum)));
						} catch (eRk) {}
						var volEl = resultsEl.querySelector('.mpc-results-vol-value');
						if (volEl) volEl.textContent = volVal;
						resultsEl.querySelectorAll('.mpc-results-phys-value, .mpc-results-weight-vol-value').forEach(function (el) { el.textContent = volVal; });
						var airW = resultsEl.querySelector('.mpc-results-air-weight-value');
						if (airW) airW.textContent = weightVal;
						var railTaxableCbm = Math.max(volNum, weightRealNum / RAIL_SMILE_TIER_KG_PER_M3);
						var railEquivKg300 = railTaxableCbm * RAIL_SMILE_TIER_KG_PER_M3;
						var railWEl = resultsEl.querySelector('.mpc-results-rail-weight-value');
						if (railWEl) railWEl.textContent = String(Math.round(railEquivKg300));
						var railCbmEl = resultsEl.querySelector('.mpc-results-rail-billable-cbm');
						if (railCbmEl) railCbmEl.textContent = railTaxableCbm.toFixed(2);
						try {
							resultsEl.setAttribute('data-mpc-chargeable-rail-kg', String(Math.round(railEquivKg300)));
							resultsEl.setAttribute('data-mpc-rail-billable-cbm', railTaxableCbm.toFixed(4));
						} catch (eRail) {}

						// Calculează estimativ prețul pentru transport aerian pe baza tabelului (Airfreight + OTOPENI->dest).
						var airCard = resultsEl.querySelector('.mpc-result-card--air');
						var originCityForCalc = loadingCity && loadingCity.value ? loadingCity.value.trim() : '';
						var destCityForCalc = deliveryCity && deliveryCity.value ? deliveryCity.value.trim() : '';
						if (airCard && typeof computeAirTransportPriceEur === 'function') {
							var eurPrice = computeAirTransportPriceEur(chargeKg, originCityForCalc, destCityForCalc);
							if (eurPrice !== null && !isNaN(eurPrice)) {
								airCard.setAttribute('data-transport-price', eurPrice);
								var priceEl = airCard.querySelector('.mpc-result-card-price');
								if (priceEl) priceEl.textContent = eurPrice + ' €';
							}
						}

						var railCard = resultsEl.querySelector('.mpc-result-card--rail');
						if (railCard && typeof computeRailTransportPriceEur === 'function') {
							var eurRail = computeRailTransportPriceEur(
								weightRealNum,
								volNum,
								originCityForCalc,
								destCityForCalc,
								incoterm
							);
							if (eurRail !== null && !isNaN(eurRail)) {
								railCard.setAttribute('data-transport-price', eurRail);
								var railPriceEl = railCard.querySelector('.mpc-result-card-price');
								if (railPriceEl) railPriceEl.textContent = eurRail + ' €';
							}
						}
						applyTransportAvailabilityByIncoterm(container);

						var seaCard = resultsEl.querySelector('.mpc-result-card--sea');
						if (seaCard && typeof computeSeaTransportPriceEur === 'function') {
							var eurSea = computeSeaTransportPriceEur(
								weightRealNum,
								volNum,
								originCityForCalc,
								destCityForCalc
							);
							if (eurSea !== null && !isNaN(eurSea)) {
								seaCard.setAttribute('data-transport-price', eurSea);
								var seaPriceEl = seaCard.querySelector('.mpc-result-card-price');
								if (seaPriceEl) seaPriceEl.textContent = eurSea + ' €';
							}
						}

						resetServiceSelections(container);
						updateRailSeaExtrasForContainer(container);
						resultsEl.classList.remove('mpc-hidden');
						resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
						if (typeof container._mpcUpdateOrderTotal === 'function' && container.querySelector('.mpc-result-card--selected')) {
							container._mpcUpdateOrderTotal();
						}
					}
				}
			});
		});
	}

	// Run on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}

	// Re-run when new calculators are added (e.g. shortcode injected by Elementor/AJAX)
	try {
		if (typeof MutationObserver !== 'undefined') {
			var observer = new MutationObserver(function (mutations) {
				var needRun = false;
				for (var m = 0; m < mutations.length; m++) {
					var added = mutations[m].addedNodes;
					if (!added || !added.length) continue;
					for (var n = 0; n < added.length; n++) {
						var node = added[n];
						if (node.nodeType === 1) {
							if (node.classList && node.classList.contains('my-plugin-calculator')) needRun = true;
							else if (node.querySelector && node.querySelector('.my-plugin-calculator')) needRun = true;
						}
					}
				}
				if (needRun) run();
			});
			var body = document.body;
			if (body) observer.observe(body, { childList: true, subtree: true });
			else document.addEventListener('DOMContentLoaded', function () { observer.observe(document.body, { childList: true, subtree: true }); });
		}
	} catch (e) {}
})();
