<?php
// create_event.php - Criar / Editar evento com busca Nominatim + rota via Leaflet Routing Machine
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

if (!$me || $me['role'] !== 'organizador') {
    echo "<p>Acesso negado. Apenas organizadores.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
if (!$me['plan_paid']) {
    echo "<p>Você precisa de um plano ativo para criar eventos. <a href='plan.php'>Assinar plano</a></p>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [];
$editing = false;
$event = [
  'id' => null,
  'name' => '',
  'city' => '',
  'date_event' => date('Y-m-d'),
  'price' => '0.00',
  'distance_km' => '',
  'lat' => '',
  'lng' => '',
  'end_lat' => '',
  'end_lng' => '',
  'route_geojson' => null,
];

if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$id, $me['id']]);
    $ev = $stmt->fetch();
    if ($ev) {
        $editing = true;
        $event = $ev;
    } else {
        echo "<p>Evento não encontrado ou sem permissão.</p>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $date_event = $_POST['date_event'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $distance_km = floatval($_POST['distance_km'] ?? 0);
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
    $end_lat = isset($_POST['end_lat']) ? floatval($_POST['end_lat']) : null;
    $end_lng = isset($_POST['end_lng']) ? floatval($_POST['end_lng']) : null;
    $route_geojson = !empty($_POST['route_geojson']) ? $_POST['route_geojson'] : null;

    if (!$name || !$date_event || !$lat || !$lng) {
        $errors[] = "Preencha nome, data e defina ponto inicial no mapa.";
    }

    if (empty($errors)) {
        if (!empty($_POST['event_id'])) {
            // Update
            $eid = intval($_POST['event_id']);
            $upd = $pdo->prepare("UPDATE events SET name=?,city=?,date_event=?,lat=?,lng=?,end_lat=?,end_lng=?,distance_km=?,price=?,route_geojson=? WHERE id = ? AND organizer_id = ?");
            $upd->execute([$name,$city,$date_event,$lat,$lng,$end_lat,$end_lng,$distance_km,$price,$route_geojson,$eid,$me['id']]);
            header('Location: my_events.php'); exit;
        } else {
            // Insert
            $ins = $pdo->prepare("INSERT INTO events (organizer_id,name,city,date_event,lat,lng,end_lat,end_lng,distance_km,price,route_geojson) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->execute([$me['id'],$name,$city,$date_event,$lat,$lng,$end_lat,$end_lng,$distance_km,$price,$route_geojson]);
            header('Location: my_events.php'); exit;
        }
    } else {
        // keep posted values in form
        $event = [
          'id' => $_POST['event_id'] ?? null,
          'name' => $name,
          'city' => $city,
          'date_event' => $date_event,
          'price' => $price,
          'distance_km' => $distance_km,
          'lat' => $lat,
          'lng' => $lng,
          'end_lat' => $end_lat,
          'end_lng' => $end_lng,
          'route_geojson' => $route_geojson,
        ];
    }
}
?>

<h2><?php echo $editing ? 'Editar evento' : 'Criar evento'; ?></h2>

<div class="card">
  <?php if (!empty($errors)): ?>
    <div style="margin-bottom:8px;">
      <?php foreach($errors as $e): ?>
        <div class="small" style="color:#ffb5b5; margin-bottom:6px;"><?php echo esc($e); ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" id="eventForm">
    <input type="hidden" name="event_id" value="<?php echo esc($event['id']); ?>">

    <div class="form-row">
      <label>Nome da corrida</label>
      <input type="text" name="name" value="<?php echo esc($event['name']); ?>" required>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <div style="flex:1;min-width:200px;">
        <div class="form-row">
          <label>Cidade</label>
          <input type="text" name="city" value="<?php echo esc($event['city']); ?>">
        </div>
      </div>

      <div style="width:220px;">
        <div class="form-row">
          <label>Data</label>
          <input type="date" name="date_event" value="<?php echo esc($event['date_event']); ?>" required>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <div style="flex:1;min-width:180px;">
        <div class="form-row">
          <label>Preço (R$)</label>
          <input type="number" step="0.01" name="price" value="<?php echo esc($event['price']); ?>">
        </div>
      </div>

      <div style="width:200px;">
        <div class="form-row">
          <label>Distância (km)</label>
          <input type="text" name="distance_km" id="distance_km" value="<?php echo esc($event['distance_km']); ?>" readonly placeholder="—">
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:10px;">
      <label>Buscar endereço / ponto inicial</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input id="geosearch" type="search" placeholder="Ex: Av. Afonso Pena, Belo Horizonte" style="flex:1">
        <button type="button" id="doSearch" class="btn-ghost">Buscar</button>
        <button type="button" id="clearRoute" class="btn-ghost">Limpar rota</button>
      </div>

      <ul id="suggestions" style="list-style:none;padding:8px 0;max-height:160px;overflow:auto;margin:6px 0 0 0;"></ul>

      <div id="eventMap" class="map" style="margin-top:10px;height:420px;"></div>
      <div class="small" style="margin-top:8px;color:var(--muted);">Clique no mapa para definir largada e chegada. Ajuste a rota arrastando os waypoints.</div>
    </div>

    <!-- campos enviados -->
    <input type="hidden" name="lat" id="lat" value="<?php echo esc($event['lat']); ?>">
    <input type="hidden" name="lng" id="lng" value="<?php echo esc($event['lng']); ?>">
    <input type="hidden" name="end_lat" id="end_lat" value="<?php echo esc($event['end_lat']); ?>">
    <input type="hidden" name="end_lng" id="end_lng" value="<?php echo esc($event['end_lng']); ?>">
    <input type="hidden" name="route_geojson" id="route_geojson" value='<?php echo esc($event['route_geojson']); ?>'>

    <div style="margin-top:12px;">
      <button class="btn" type="submit"><?php echo $editing ? 'Salvar alterações' : 'Salvar evento'; ?></button>
      <a href="my_events.php" class="btn-ghost" style="margin-left:8px;padding:0.58rem 0.9rem;border-radius:10px;">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  // Map init
  const map = L.map('eventMap', { zoomControl:true }).setView([-19.9167, -43.9345], 10);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{ attribution:'&copy; OSM & CartoDB', maxZoom:20 }).addTo(map);

  let routingControl = null;
  let startMarker = null;
  let endMarker = null;

  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const endLatInput = document.getElementById('end_lat');
  const endLngInput = document.getElementById('end_lng');
  const distanceInput = document.getElementById('distance_km');
  const routeGeoInput = document.getElementById('route_geojson');

  const existingRoute = routeGeoInput.value ? (function(){ try { return JSON.parse(routeGeoInput.value); } catch(e){ return null; } })() : null;
  const existingStart = (latInput.value && lngInput.value) ? [parseFloat(latInput.value), parseFloat(lngInput.value)] : null;
  const existingEnd = (endLatInput.value && endLngInput.value) ? [parseFloat(endLatInput.value), parseFloat(endLngInput.value)] : null;

  function setStart(lat,lng){
    if (startMarker) startMarker.remove();
    startMarker = L.marker([lat,lng], { draggable:true }).addTo(map).bindPopup('Largada').openPopup();
    startMarker.on('dragend', computeRouteFromMarkers);
    latInput.value = lat;
    lngInput.value = lng;
  }
  function setEnd(lat,lng){
    if (endMarker) endMarker.remove();
    endMarker = L.marker([lat,lng], { draggable:true }).addTo(map).bindPopup('Chegada').openPopup();
    endMarker.on('dragend', computeRouteFromMarkers);
    endLatInput.value = lat;
    endLngInput.value = lng;
  }

  // Draw existing GeoJSON
  if (existingRoute && existingRoute.type === 'FeatureCollection') {
    L.geoJSON(existingRoute, { style: { color: '#e31b23', weight: 5, opacity: 0.95 } }).addTo(map);
    try {
      const coords = existingRoute.features[0].geometry.coordinates;
      const latlngs = coords.map(c => [c[1], c[0]]);
      map.fitBounds(latlngs, { padding: [40,40] });
      setStart(latlngs[0][0], latlngs[0][1]);
      setEnd(latlngs[latlngs.length-1][0], latlngs[latlngs.length-1][1]);
      if (existingRoute.features[0].properties && existingRoute.features[0].properties.distance_km) {
        distanceInput.value = existingRoute.features[0].properties.distance_km;
      }
    } catch(e){}
  } else {
    if (existingStart) setStart(existingStart[0], existingStart[1]);
    if (existingEnd) setEnd(existingEnd[0], existingEnd[1]);
    if (!existingStart && !existingEnd) map.setView([-19.9167,-43.9345], 10);
  }

  map.on('click', function(e){
    const { lat, lng } = e.latlng;
    if (!startMarker) {
      setStart(lat,lng);
    } else {
      setEnd(lat,lng);
    }
    computeRouteFromMarkers();
  });

  function computeRouteFromMarkers(){
    if (routingControl) { map.removeControl(routingControl); routingControl = null; }

    if (startMarker && endMarker) {
      routingControl = L.Routing.control({
        waypoints: [startMarker.getLatLng(), endMarker.getLatLng()],
        router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
        draggableWaypoints: true,
        addWaypoints: true,
        show: false,
        lineOptions: { styles: [{ color: '#e31b23', weight: 5, opacity: 0.95 }] }
      }).addTo(map);

      routingControl.on('routesfound', function(e){
        const r = e.routes[0];
        const distKm = (r.summary.totalDistance / 1000).toFixed(2);
        distanceInput.value = distKm;

        if (r.coordinates && r.coordinates.length) {
          const coords = r.coordinates.map(c => [c.lng || c[1], c.lat || c[0]]);
          const gj = {
            type: "FeatureCollection",
            features: [{
              type: "Feature",
              properties: { distance_km: distKm },
              geometry: { type: "LineString", coordinates: coords }
            }]
          };
          routeGeoInput.value = JSON.stringify(gj);
        } else if (r.geometry) {
          routeGeoInput.value = JSON.stringify({ polyline: r.geometry, distance_km: distKm });
        }

        const wp = routingControl.getWaypoints();
        if (wp && wp.length >= 2) {
          latInput.value = wp[0].lat.toFixed(6);
          lngInput.value = wp[0].lng.toFixed(6);
          endLatInput.value = wp[wp.length-1].lat.toFixed(6);
          endLngInput.value = wp[wp.length-1].lng.toFixed(6);
        }
      });
    } else {
      distanceInput.value = '';
      routeGeoInput.value = '';
    }
  }

  document.getElementById('clearRoute').addEventListener('click', function(){
    if (routingControl) { map.removeControl(routingControl); routingControl = null; }
    if (startMarker) { startMarker.remove(); startMarker = null; }
    if (endMarker) { endMarker.remove(); endMarker = null; }
    distanceInput.value = '';
    routeGeoInput.value = '';
    latInput.value = '';
    lngInput.value = '';
    endLatInput.value = '';
    endLngInput.value = '';
  });

  // Nominatim search
  const geosearch = document.getElementById('geosearch');
  const doSearchBtn = document.getElementById('doSearch');
  const suggestions = document.getElementById('suggestions');
  let searchTimeout = null;

  function showSuggestions(list) {
    suggestions.innerHTML = '';
    list.forEach(item => {
      const li = document.createElement('li');
      li.style.padding = '8px';
      li.style.cursor = 'pointer';
      li.style.borderRadius = '6px';
      li.style.transition = 'background .12s';
      li.onmouseover = ()=> li.style.background = 'rgba(255,255,255,0.02)';
      li.onmouseout = ()=> li.style.background = 'transparent';
      li.innerHTML = `<div style="font-weight:600;color:#fff">${item.display_name.split(',')[0]}</div><div class="small" style="color:${getComputedStyle(document.documentElement).getPropertyValue('--muted')};font-size:0.85rem;margin-top:3px">${item.display_name}</div>`;
      li.onclick = ()=> {
        const lat = parseFloat(item.lat);
        const lon = parseFloat(item.lon);
        map.setView([lat, lon], 15);
        setStart(lat, lon);
        suggestions.innerHTML = '';
      };
      suggestions.appendChild(li);
    });
  }

  function nominatimSearch(q) {
    if (!q) { suggestions.innerHTML = ''; return; }
    fetch('https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6&q=' + encodeURIComponent(q), { headers: { 'Accept-Language': 'pt-BR' } })
      .then(res => res.json())
      .then(data => { showSuggestions(data || []); })
      .catch(err => { console.error('Nominatim error', err); });
  }

  geosearch.addEventListener('input', function(){
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    searchTimeout = setTimeout(()=> nominatimSearch(q), 350);
  });

  doSearchBtn.addEventListener('click', function(){ nominatimSearch(geosearch.value.trim()); });

  geosearch.addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
      e.preventDefault();
      const first = suggestions.querySelector('li');
      if (first) first.click();
      else nominatimSearch(geosearch.value.trim());
    }
  });

  if (startMarker && endMarker) computeRouteFromMarkers();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>