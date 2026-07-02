export class MapManager {
  #map = null;
  #marker = null;
  #sessionCoords;
  #showVenue;
  #showLink;
  #showMap;

  constructor({ sessionCoords, showLocation }) {
    this.#sessionCoords = sessionCoords;
    this.#showVenue = showLocation !== false;
    this.#showLink  = showLocation === 'only_link' || showLocation === 'with_map';
    this.#showMap   = showLocation === 'with_map';
  }

  setup(sel, uid) {
    if (this.#showVenue) this.#bindLink(sel, uid);
    if (this.#showMap)   this.#syncMap(uid);
  }

  onSessionChange(sel, uid) {
    if (this.#showMap)   this.#syncMap(uid);
    if (this.#showVenue) this.#bindLink(sel, uid);
  }

  // Updates marker position on session change — never loads tiles without consent
  #syncMap(uid) {
    const coords    = this.#sessionCoords[uid];
    const container = document.getElementById('map').parentElement;
    if (!coords || coords.lat == null) { container.classList.add('d-none'); return; }
    if (!this.#map) return;
    const wasHidden = container.classList.contains('d-none');
    this.#updateView(coords, !wasHidden);
  }

  #bindLink(sel, uid) {
    const venue  = sel.options[sel.selectedIndex]?.dataset.location ?? '';
    const coords = this.#sessionCoords[uid];
    const el     = document.getElementById('session-location');
    if (!venue) { el.classList.add('d-none'); return; }

    document.getElementById('venue-name').textContent = '📍 ' + venue;
    el.classList.remove('d-none');
    el.onclick = null;
    el.style.cursor = '';

    if (this.#showMap) {
      const notice = document.getElementById('map-notice');
      if (coords?.lat != null) {
        el.onclick = e => { e.preventDefault(); this.#onUserClick(coords); };
        el.style.cursor = 'pointer';
        if (!this.#map) notice.classList.remove('d-none');
      } else {
        notice.classList.add('d-none');
      }
    } else if (this.#showLink) {
      if (coords?.lat != null) {
        el.href = `https://www.openstreetmap.org/?mlat=${coords.lat}&mlon=${coords.lon}#map=15/${coords.lat}/${coords.lon}`;
        el.target = '_blank';
        el.rel = 'noopener';
        el.tabIndex = 0;
      } else {
        el.removeAttribute('href');
        el.tabIndex = -1;
      }
    }
  }

  // Entry point for user consent — only place that may call #loadLeaflet
  #onUserClick(coords) {
    const container = document.getElementById('map').parentElement;
    if (!this.#map) {
      this.#loadLeaflet(coords);
    } else {
      container.classList.toggle('d-none');
      if (!container.classList.contains('d-none')) this.#map.invalidateSize();
    }
  }

  #loadLeaflet(coords) {
    document.getElementById('map-notice').classList.add('d-none');
    document.getElementById('map').parentElement.classList.remove('d-none');
    requestAnimationFrame(() => {
      this.#map = L.map('map', { zoomControl: true, attributionControl: true });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
      }).addTo(this.#map);
      this.#marker = L.marker([coords.lat, coords.lon]).addTo(this.#map);
      this.#map.setView([coords.lat, coords.lon], 15);
    });
  }

  #updateView(coords, invalidate = false) {
    this.#marker.setLatLng([coords.lat, coords.lon]);
    this.#map.setView([coords.lat, coords.lon], 15);
    if (invalidate) this.#map.invalidateSize();
  }
}
