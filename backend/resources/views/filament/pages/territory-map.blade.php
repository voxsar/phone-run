<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Daily Growth Slider ────────────────────────────────── --}}
        <x-filament::section heading="Territory Growth Timeline">
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Select Day:
                    </label>
                    <input
                        id="daySlider"
                        type="range"
                        min="0"
                        value="0"
                        class="w-full accent-amber-500"
                        oninput="onSliderChange(this.value)"
                    />
                    <span id="sliderLabel" class="text-sm font-semibold text-amber-500 whitespace-nowrap w-24 text-right">All days</span>
                </div>
                <div id="sliderStats" class="text-sm text-gray-500 dark:text-gray-400"></div>
            </div>
        </x-filament::section>

        {{-- ── Google Map ─────────────────────────────────────────── --}}
        <x-filament::section heading="All Players' Territories">
            <div id="territory-map" style="height:600px;width:100%;border-radius:0.5rem;overflow:hidden;"></div>
        </x-filament::section>

    </div>

    @php
        $territories  = json_decode($territoriesJson, true) ?? [];
        $dailyTotals  = json_decode($dailyTotalsJson, true) ?? [];
    @endphp

    <script>
        const ALL_TERRITORIES = @json($territories);
        const DAILY_TOTALS    = @json($dailyTotals);

        let map;
        let polygons = [];

        // ── Build an array of unique sorted dates ───────────────────
        const DATES = [...new Set(ALL_TERRITORIES.map(t => t.date))].sort();

        // ── Initialise slider ────────────────────────────────────────
        window.addEventListener('DOMContentLoaded', () => {
            const slider = document.getElementById('daySlider');
            slider.max = DATES.length;          // 0 = all, 1…n = specific day
            onSliderChange(0);
        });

        function onSliderChange(value) {
            const label    = document.getElementById('sliderLabel');
            const stats    = document.getElementById('sliderStats');
            const idx      = parseInt(value);

            if (idx === 0) {
                label.textContent = 'All days';
                const total = ALL_TERRITORIES.reduce((s, t) => s + parseFloat(t.area || 0), 0);
                stats.textContent = `${ALL_TERRITORIES.length} territories · ${(total / 1e6).toFixed(4)} km² total`;
                renderTerritories(ALL_TERRITORIES);
            } else {
                const day   = DATES[idx - 1];
                label.textContent = day;
                const dayTerritories = ALL_TERRITORIES.filter(t => t.date <= day);
                const total = dayTerritories.reduce((s, t) => s + parseFloat(t.area || 0), 0);
                const todayCount = ALL_TERRITORIES.filter(t => t.date === day).length;
                stats.textContent = `${dayTerritories.length} cumulative · +${todayCount} on this day · ${(total / 1e6).toFixed(4)} km² total`;
                renderTerritories(dayTerritories);
            }
        }

        function renderTerritories(territories) {
            if (!map) return;
            polygons.forEach(p => p.setMap(null));
            polygons = [];

            territories.forEach(t => {
                if (!t.polygon || t.polygon.length < 3) return;
                const path   = t.polygon.map(pt => ({ lat: parseFloat(pt.lat), lng: parseFloat(pt.lng) }));
                const color  = t.color || '#3388FF';
                const poly   = new google.maps.Polygon({
                    paths:         path,
                    strokeColor:   color,
                    strokeOpacity: 0.9,
                    strokeWeight:  2,
                    fillColor:     color,
                    fillOpacity:   0.35,
                    map:           map,
                });
                const infoWindow = new google.maps.InfoWindow();
                poly.addListener('click', (e) => {
                    infoWindow.setContent(
                        `<strong>${t.userName}</strong><br>` +
                        `Area: ${(parseFloat(t.area) / 1e6).toFixed(6)} km²<br>` +
                        `Claimed: ${t.date}`
                    );
                    infoWindow.setPosition(e.latLng);
                    infoWindow.open(map);
                });
                polygons.push(poly);
            });
        }

        function initMap() {
            map = new google.maps.Map(document.getElementById('territory-map'), {
                zoom:   3,
                center: { lat: 20, lng: 0 },
                mapTypeId: 'roadmap',
            });

            // Auto-fit to all territories
            if (ALL_TERRITORIES.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                ALL_TERRITORIES.forEach(t => {
                    (t.polygon || []).forEach(pt => bounds.extend({ lat: parseFloat(pt.lat), lng: parseFloat(pt.lng) }));
                });
                if (!bounds.isEmpty()) map.fitBounds(bounds);
            }

            renderTerritories(ALL_TERRITORIES);
        }
    </script>

    @if($mapsApiKey)
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsApiKey }}&callback=initMap" async defer></script>
    @else
        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-300 dark:border-yellow-700">
            <p class="text-yellow-800 dark:text-yellow-300 text-sm font-medium">
                ⚠️ Google Maps API key not configured. Set <code>GOOGLE_MAPS_API_KEY</code> in your <code>.env</code> file to enable the map.
            </p>
        </div>
    @endif
</x-filament-panels::page>
