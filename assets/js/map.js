export let map = null;
let marker = null;

export function initMap(coords, show = true) {
  document.getElementById('map-notice').classList.add('d-none');
  const mapEl = document.getElementById('map');
  if (show) mapEl.parentElement.classList.remove('d-none');
  requestAnimationFrame(() => {
    if (!map) {
      map = L.map('map', { zoomControl: true, attributionControl: true });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
      }).addTo(map);
      marker = L.marker([coords.lat, coords.lon]).addTo(map);
      map.setView([coords.lat, coords.lon], 15);
    } else {
      marker.setLatLng([coords.lat, coords.lon]);
      map.setView([coords.lat, coords.lon], 15);
      if (show) map.invalidateSize();
    }
  });
}
