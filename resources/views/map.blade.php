@extends('layouts.app')

@section('title', 'Map - CoffeePahoman (Dark)')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #map {
    height: 70vh;
  }

  .leaflet-control,
  .leaflet-popup-content-wrapper {
    background: #1a1a1a !important;
    color: #fef3c7 !important;
    border: 1px solid #78350f !important;
  }

  .map-icon {
    color: #b45309;
    font-size: 22px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
  }

  .map-icon.active {
    color: #fbbf24;
    filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.8));
  }

  .list-item.active {
    border-color: #fbbf24;
    background: rgba(120, 53, 15, 0.35);
  }
</style>
@endpush

@section('content')
<div class="flex justify-between items-center mb-6" data-aos="fade-down">
  <h1 class="text-2xl font-semibold text-amber-50">Map / WebGIS</h1>
  <div class="flex gap-3">
    <button id="downloadBtn" class="px-3 py-2 bg-amber-600 text-black rounded text-sm">Download GeoJSON</button>
    <input id="searchInput" class="px-3 py-2 rounded text-sm bg-[#1a1a1a] border border-amber-800 text-amber-100" placeholder="Cari nama...">
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
  <section class="lg:col-span-3" data-aos="fade-right">
    <div id="map" class="rounded-lg shadow border border-amber-900/50"></div>
    <div class="mt-3 flex gap-3">
      <button id="locateBtn" class="px-3 py-2 bg-amber-600 text-black rounded">Locate Me</button>
      <button id="addMarkerBtn" class="px-3 py-2 bg-amber-500 text-black rounded">Add Marker</button>
    </div>
  </section>

  <!-- Add marker modal -->
  <div id="addMarkerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-[#0f0f0f] p-4 rounded border border-amber-800 w-96 text-amber-100">
      <h4 class="font-semibold mb-2">Tambah Coffee Shop</h4>
      <form id="addMarkerForm" class="space-y-2">
        <input id="am_name" name="name" class="w-full px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Nama" required>
        <div class="flex gap-2">
          <input id="am_open" name="open_time" class="flex-1 px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Open">
          <input id="am_close" name="close_time" class="flex-1 px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Close">
        </div>
        <div class="flex gap-2">
          <input id="am_price" name="avg_price" class="flex-1 px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Avg price">
          <input id="am_rating" name="rating" class="flex-1 px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Rating">
        </div>
        <input id="am_address" name="address" class="w-full px-2 py-1 bg-[#111] border border-amber-800 rounded text-amber-100" placeholder="Alamat">
        <input type="hidden" id="am_lat" name="lat">
        <input type="hidden" id="am_lng" name="lng">
        <input type="hidden" id="am_id" name="id">
        <div class="flex gap-2 justify-end">
          <button type="button" id="am_cancel" class="px-3 py-1 bg-gray-700 rounded">Batal</button>
          <button type="submit" id="am_submit" class="px-3 py-1 bg-amber-600 text-black rounded">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <aside class="card p-4 rounded" data-aos="fade-left" data-aos-delay="100">
    <h3 class="font-semibold text-amber-100">Listing Coffee Shop</h3>
    <div id="list" class="mt-3 space-y-2 text-sm"></div>
  </aside>
</div>
@endsection

@push('scripts')
<script>
  const shopsUrl = "{{ url('/api/shops') }}";
  const boundaryUrl = "{{ route('api.geojson', ['type' => 'boundary'], false) }}";

  const map = L.map('map', {
    closePopupOnClick: false
  }).setView([-5.4295, 105.2625], 15);

  const darkTiles = L.tileLayer(
    "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
      attribution: '©OpenStreetMap, ©CARTO',
      maxZoom: 19
    }
  ).addTo(map);

  const satTiles = L.tileLayer(
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
      attribution: "Esri",
      maxZoom: 19
    }
  );

  const baseMaps = {
    "Map": darkTiles,
    "Satellite": satTiles
  };
  const shopsLayer = L.layerGroup().addTo(map);
  const boundaryLayer = L.geoJSON(null, {
    style: {
      color: "#f59e0b",
      weight: 2,
      fillOpacity: 0.05
    }
  }).addTo(map);

  const icon = L.divIcon({
    className: "",
    html: `<i class="fa-solid fa-mug-saucer map-icon"></i>`,
    iconSize: [22, 22],
    iconAnchor: [11, 22]
  });
  const iconActive = L.divIcon({
    className: "",
    html: `<i class="fa-solid fa-mug-saucer map-icon active"></i>`,
    iconSize: [26, 26],
    iconAnchor: [13, 26]
  });

  let shopsJSON = null;
  let allFeatures = [];
  const featureById = new Map();

  const listEl = document.getElementById("list");
  const searchInput = document.getElementById("searchInput");
  const markerByKey = new Map();
  const listItemByKey = new Map();
  let activeKey = null;

  function featureKey(f) {
    return `${f.properties?.NAMA || ''}|${(f.geometry?.coordinates || []).join(',')}`;
  }

  function setActive(key, {
    center = true,
    openPopup = true,
    moveToTop = false
  } = {}) {
    if (activeKey && markerByKey.has(activeKey)) {
      markerByKey.get(activeKey).setIcon(icon);
    }
    if (activeKey && listItemByKey.has(activeKey)) {
      listItemByKey.get(activeKey).classList.remove("active");
    }
    activeKey = key;
    if (!key) return;

    const marker = markerByKey.get(key);
    const item = listItemByKey.get(key);
    if (item) {
      item.classList.add("active");
      if (moveToTop && listEl.firstChild !== item) {
        listEl.prepend(item);
        if (typeof listEl.scrollTo === "function") listEl.scrollTo({
          top: 0,
          behavior: "smooth"
        });
      }
    }
    if (marker) {
      marker.setIcon(iconActive);
      if (center) {
        const ll = marker.getLatLng();
        map.setView(ll, 17);
      }
      if (openPopup) marker.openPopup();
    }
  }

  const markerOptions = {
    pointToLayer: (f, latlng) => {
      const key = featureKey(f);
      const marker = L.marker(latlng, {
        icon
      });
      markerByKey.set(key, marker);
      marker.on("click", ev => {
        ev.originalEvent?.stopPropagation?.();
        setActive(key, {
          center: false,
          openPopup: true,
          moveToTop: true
        });
      });
      return marker;
    },
    onEachFeature: (f, l) => {
      const p = f.properties || {};
      const detailUrl = "{{ route('detail') }}#" + encodeURIComponent(p.NAMA || '');
      const id = p.id || null;
      let controls = `<a href="${detailUrl}" class="text-amber-300">Lihat detail</a>`;
      if (id) {
        controls += ` <button class="popup-edit ml-2 text-amber-300" data-id="${id}">Edit</button>`;
        controls += ` <button class="popup-delete ml-2 text-red-400" data-id="${id}">Hapus</button>`;
      }
      l.bindPopup(
        `<div>
        <strong>${p.NAMA || 'Tanpa nama'}</strong><br>
        Buka: ${p.WAKTU_BUKA || '-'} - ${p.WKT_TUTUP || '-'}<br>
        Harga rata-rata: Rp ${p.HARGA || '-'}<br>
        Rating: ${p.RATING || '-'}<br>
        ${controls}
      </div>`
      );
    }
  };

  function renderMarkers(features) {
    shopsLayer.clearLayers();
    markerByKey.clear();
    if (!features.length) return;
    L.geoJSON({
      type: "FeatureCollection",
      features
    }, markerOptions).addTo(shopsLayer);
  }

  function renderList(features) {
    listEl.innerHTML = "";
    listItemByKey.clear();
    if (!features.length) {
      listEl.innerHTML = "<div class='text-amber-400 text-sm'>Tidak ada hasil.</div>";
      return;
    }
    features.forEach(ft => {
      const key = featureKey(ft);
      const div = document.createElement("div");
      div.className = "list-item p-2 rounded border border-amber-800 cursor-pointer hover:bg-amber-900/40";
      div.innerHTML = `<div>${ft.properties.NAMA || 'Tanpa nama'}</div><div class='text-xs text-amber-400'>Buka ${ft.properties.WAKTU_BUKA || '-'} - ${ft.properties.WKT_TUTUP || '-'}</div>`;
      div.onclick = () => {
        setActive(key, {
          center: true,
          openPopup: true
        });
      };
      listItemByKey.set(key, div);
      listEl.appendChild(div);
    });
  }

  function focusToFeatures(features) {
    if (!features.length) return;
    if (features.length === 1) {
      const c = features[0].geometry.coordinates;
      map.setView([c[1], c[0]], 17);
      return;
    }
    const bounds = L.latLngBounds(features.map(f => [f.geometry.coordinates[1], f.geometry.coordinates[0]]));
    map.fitBounds(bounds, {
      maxZoom: 17
    });
  }

  function handleSearch() {
    if (!shopsJSON) return;
    const q = searchInput.value.trim().toLowerCase();
    const filtered = q ? allFeatures.filter(f => (f.properties.NAMA || "").toLowerCase().includes(q)) : allFeatures;
    renderList(filtered);
    renderMarkers(filtered);
    focusToFeatures(filtered);
    setActive(filtered.length ? featureKey(filtered[0]) : null, {
      center: false,
      openPopup: true
    });
  }

  // Load shops: use /api/geojson?type=shops as primary (proven to work with DB)
  const geojsonShopsUrl = "{{ route('api.geojson', ['type' => 'shops'], false) }}";

  console.log('Loading shops from:', geojsonShopsUrl);
  fetch(geojsonShopsUrl)
    .then(r => {
      console.log('Response status:', r.status);
      if (!r.ok) throw r;
      return r.json();
    })
    .then(data => {
      console.log('Loaded shops data, features count:', (data && data.features) ? data.features.length : 0);
      shopsJSON = data;
      allFeatures = (data && data.features) ? data.features : [];
      // index features by id
      featureById.clear();
      allFeatures.forEach(f => {
        const id = f.properties?.id;
        if (id) featureById.set(Number(id), f);
      });
      renderList(allFeatures);
      renderMarkers(allFeatures);
      focusToFeatures(allFeatures);
      setActive(allFeatures.length ? featureKey(allFeatures[0]) : null, {
        center: false,
        openPopup: true
      });
    })
    .catch(err => {
      console.error('Failed to load shops:', err);
      renderList([]);
    });

  fetch(boundaryUrl)
    .then(r => r.json())
    .then(data => {
      boundaryLayer.addData(data);
    });

  L.control.layers(baseMaps, {
    "Coffee Shop": shopsLayer,
    "Batas Wilayah": boundaryLayer
  }).addTo(map);

  map.on('popupopen', (ev) => {
    const container = ev.popup.getElement();
    if (!container) return;
    const editBtn = container.querySelector('.popup-edit');
    const delBtn = container.querySelector('.popup-delete');

    if (editBtn) {
      editBtn.addEventListener('click', (e) => {
        const id = editBtn.getAttribute('data-id');
        const feature = featureById.get(Number(id));
        if (!feature) {
          alert('Data tidak tersedia untuk diedit');
          return;
        }
        // prefill modal for edit
        document.getElementById('am_id').value = id;
        document.getElementById('am_name').value = feature.properties?.NAMA || '';
        document.getElementById('am_open').value = feature.properties?.WAKTU_BUKA || '';
        document.getElementById('am_close').value = feature.properties?.WKT_TUTUP || '';
        document.getElementById('am_price').value = feature.properties?.HARGA || '';
        document.getElementById('am_rating').value = feature.properties?.RATING || '';
        document.getElementById('am_address').value = feature.properties?.ALAMAT || '';
        const coords = feature.geometry?.coordinates || [];
        if (coords.length >= 2) {
          document.getElementById('am_lat').value = coords[1];
          document.getElementById('am_lng').value = coords[0];
        }
        addMarkerModalEl.classList.remove('hidden');
      });
    }

    if (delBtn) {
      delBtn.addEventListener('click', (e) => {
        const id = delBtn.getAttribute('data-id');
        if (!confirm('Hapus coffeeshop ini?')) return;
        fetch(`${shopsUrl}/${id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          }
        }).then(r => {
          if (!r.ok) throw r;
          // remove from local arrays and re-render
          allFeatures = allFeatures.filter(f => String(f.properties?.id) !== String(id));
          if (shopsJSON && Array.isArray(shopsJSON.features)) {
            shopsJSON.features = shopsJSON.features.filter(f => String(f.properties?.id) !== String(id));
          }
          featureById.delete(Number(id));
          renderList(allFeatures);
          renderMarkers(allFeatures);
          ev.popup.remove();
          // reload data after 1s to sync with DB
          setTimeout(reloadShopsData, 1000);
        }).catch(async err => {
          let msg = 'Gagal menghapus';
          try {
            const j = await err.json();
            msg = j.message || msg;
          } catch (e) {}
          alert(msg);
        });
      });
    }
  });

  document.getElementById("downloadBtn").onclick = () => {
    if (!shopsJSON) return;
    const url = URL.createObjectURL(new Blob([JSON.stringify(shopsJSON)], {
      type: "application/json"
    }));
    const a = document.createElement("a");
    a.href = url;
    a.download = "coffeeshops.geojson";
    a.click();
  };

  document.getElementById("locateBtn").onclick = () => {
    map.locate({
      setView: true
    });
    map.on("locationfound", e => {
      L.circle(e.latlng, {
        radius: 20,
        color: "#fbbf24"
      }).addTo(map);
    });
  };

  searchInput.addEventListener("input", handleSearch);

  // Helper to reload shops from geojson endpoint
  const reloadShopsData = () => {
    console.log('Reloading shops data...');
    fetch(geojsonShopsUrl)
      .then(r => r.json())
      .then(data => {
        shopsJSON = data;
        allFeatures = (data && data.features) ? data.features : [];
        featureById.clear();
        allFeatures.forEach(f => {
          const id = f.properties?.id;
          if (id) featureById.set(Number(id), f);
        });
        renderList(allFeatures);
        renderMarkers(allFeatures);
        console.log('Reloaded features count:', allFeatures.length);
      })
      .catch(err => console.error('Failed to reload shops:', err));
  };

  // --- Add marker UI & client-side create ---
  const csrfToken = "{{ csrf_token() }}";
  const addMarkerBtnEl = document.getElementById('addMarkerBtn');
  const addMarkerModalEl = document.getElementById('addMarkerModal');
  const addMarkerFormEl = document.getElementById('addMarkerForm');
  const am_cancel_el = document.getElementById('am_cancel');
  let tempMarker = null;

  addMarkerBtnEl.onclick = () => {
    alert('Klik peta untuk menempatkan marker baru');
    map.once('click', e => {
      const lat = e.latlng.lat;
      const lng = e.latlng.lng;
      document.getElementById('am_id').value = '';
      document.getElementById('am_lat').value = lat;
      document.getElementById('am_lng').value = lng;
      addMarkerModalEl.classList.remove('hidden');
      document.getElementById('am_name').focus();
      if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
      }
      tempMarker = L.marker([lat, lng], {
        icon: iconActive
      }).addTo(map);
      map.setView([lat, lng], 17);
    });
  };

  am_cancel_el.onclick = () => {
    addMarkerModalEl.classList.add('hidden');
    if (tempMarker) {
      map.removeLayer(tempMarker);
      tempMarker = null;
    }
    document.getElementById('am_id').value = '';
  };

  addMarkerFormEl.addEventListener('submit', ev => {
    ev.preventDefault();
    const payload = {
      name: document.getElementById('am_name').value,
      open_time: document.getElementById('am_open').value,
      close_time: document.getElementById('am_close').value,
      avg_price: document.getElementById('am_price').value || null,
      rating: document.getElementById('am_rating').value || null,
      address: document.getElementById('am_address').value || null,
      lat: parseFloat(document.getElementById('am_lat').value),
      lng: parseFloat(document.getElementById('am_lng').value),
    };

    const editId = document.getElementById('am_id').value || null;
    if (editId) {
      // update existing
      fetch(`${shopsUrl}/${editId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
      }).then(r => {
        if (!r.ok) throw r;
        return r.json();
      }).then(feature => {
        addMarkerModalEl.classList.add('hidden');
        addMarkerFormEl.reset();
        document.getElementById('am_id').value = '';
        setTimeout(reloadShopsData, 1000);
      }).catch(async err => {
        let msg = 'Gagal menyimpan perubahan';
        try {
          const j = await err.json();
          msg = j.message || msg;
        } catch (e) {}
        alert(msg);
      });
    } else {
      // create new
      fetch(shopsUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify(payload),
        })
        .then(r => {
          if (!r.ok) throw r;
          return r.json();
        })
        .then(feature => {
          // prepend to feature list and re-render
          allFeatures.unshift(feature);
          if (!shopsJSON) shopsJSON = {
            type: 'FeatureCollection',
            features: []
          };
          shopsJSON.features.unshift(feature);
          if (feature.properties?.id) featureById.set(Number(feature.properties.id), feature);
          renderList(allFeatures);
          renderMarkers(allFeatures);
          setActive(featureKey(feature), {
            center: true,
            openPopup: true
          });
          addMarkerModalEl.classList.add('hidden');
          if (tempMarker) {
            map.removeLayer(tempMarker);
            tempMarker = null;
          }
          addMarkerFormEl.reset();
          document.getElementById('am_id').value = '';
          // reload data after 1s to sync with DB
          setTimeout(reloadShopsData, 1000);
        })
        .catch(async err => {
          let msg = 'Gagal menyimpan';
          try {
            const j = await err.json();
            msg = j.message || msg;
          } catch (e) {}
          alert(msg);
        });
    }
  });
</script>
@endpush