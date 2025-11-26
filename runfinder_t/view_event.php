<?php
// view_event.php - exibe evento com rota para corredores (usa route_geojson se houver)
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo "<p>Evento não encontrado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }

$stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name FROM events e JOIN users u ON u.id = e.organizer_id WHERE e.id = ?");
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { echo "<p>Evento não encontrado.</p>"; require_once __DIR__ . '/includes/footer.php'; exit; }

// Registrations for organizer
$registrations = [];
if ($me && $me['role'] === 'organizador' && $me['id'] == $ev['organizer_id']) {
    $r = $pdo->prepare("SELECT r.*, u.name as runner_name, u.email FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ?");
    $r->execute([$id]);
    $registrations = $r->fetchAll();
}
?>

<h2><?php echo esc($ev['name']); ?></h2>

<div class="card">
  <div style="display:flex;gap:1rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:260px;">
      <p><strong>Cidade:</strong> <?php echo esc($ev['city']); ?></p>
      <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($ev['date_event'])); ?></p>
      <p><strong>Preço:</strong> R$ <?php echo number_format($ev['price'],2,',','.'); ?></p>
      <p><strong>Status:</strong> <?php echo esc($ev['status']); ?></p>
      <p><strong>Distância:</strong> <span id="eventDistance"><?php echo $ev['distance_km'] ? esc($ev['distance_km']) . ' km' : '—'; ?></span></p>

      <?php if ($me && $me['role'] === 'corredor'): ?>
        <?php $s = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?"); $s->execute([$me['id'],$id]); $already = $s->fetch(); ?>
        <?php if ($ev['status'] === 'finalizado'): ?>
          <p class="small">Evento finalizado.</p>
        <?php else: ?>
          <?php if ($already): ?>
            <p class="small">Você já está inscrito.</p>
          <?php else: ?>
            <form method="post" action="register_event.php">
              <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
              <button class="btn">Inscrever-se</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div style="flex:1;min-width:320px;">
      <div id="eventMap" class="map"></div>
      <div id="distanceBox" class="small" style="margin-top:8px;color:var(--muted)">Distância da rota: <strong id="distanceLabel"><?php echo $ev['distance_km'] ? esc($ev['distance_km']).' km' : '—'; ?></strong></div>
    </div>
  </div>

  <?php if ($registrations): ?>
    <h4 style="margin-top:12px;">Inscritos</h4>
    <table class="table">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Código</th><th>Pago</th><th>Data</th></tr></thead>
      <tbody>
      <?php foreach($registrations as $r): ?>
        <tr>
          <td><?php echo esc($r['runner_name']); ?></td>
          <td><?php echo esc($r['email']); ?></td>
          <td style="font-family:monospace"><?php echo esc($r['code']); ?></td>
          <td><?php echo $r['paid'] ? 'Sim' : 'Não'; ?></td>
          <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
(function(){
  const map = L.map('eventMap', { zoomControl:true }).setView([<?php echo ($ev['lat']? $ev['lat'] : -19.9167); ?>, <?php echo ($ev['lng']? $ev['lng'] : -43.9345); ?>], 13);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution:'&copy; OSM & CartoDB', maxZoom:20 }).addTo(map);

  const routeGeo = <?php echo $ev['route_geojson'] ? $ev['route_geojson'] : 'null'; ?>;
  const start = <?php echo ($ev['lat'] && $ev['lng']) ? json_encode([floatval($ev['lat']), floatval($ev['lng'])]) : 'null'; ?>;
  const end = <?php echo ($ev['end_lat'] && $ev['end_lng']) ? json_encode([floatval($ev['end_lat']), floatval($ev['end_lng'])]) : 'null'; ?>;

  const startIcon = L.icon({ iconUrl: 'https://cdn.jsdelivr.net/gh/pointhi/leaflet-color-markers@master/img/marker-icon-green.png', iconSize:[25,41], iconAnchor:[12,41]});
  const endIcon = L.icon({ iconUrl: 'https://cdn.jsdelivr.net/gh/pointhi/leaflet-color-markers@master/img/marker-icon-red.png', iconSize:[25,41], iconAnchor:[12,41]});

  if (routeGeo && routeGeo.type === 'FeatureCollection') {
    // draw saved GeoJSON route
    const gj = L.geoJSON(routeGeo, { style: { color: '#e31b23', weight: 5, opacity: 0.95 } }).addTo(map);
    try {
      const coords = routeGeo.features[0].geometry.coordinates;
      const latlngs = coords.map(c => [c[1], c[0]]);
      map.fitBounds(latlngs, { padding:[40,40] });
      if (!start && latlngs.length) L.marker(latlngs[0], {icon: startIcon}).addTo(map).bindPopup('Largada');
      if (!end && latlngs.length) L.marker(latlngs[latlngs.length-1], {icon: endIcon}).addTo(map).bindPopup('Chegada');
      // show distance if present in properties
      const dist = routeGeo.features[0].properties && routeGeo.features[0].properties.distance_km ? routeGeo.features[0].properties.distance_km : '<?php echo $ev['distance_km'] ? esc($ev['distance_km']) : '';?>';
      if (dist) {
        document.getElementById('distanceLabel').textContent = dist + ' km';
        document.getElementById('eventDistance').textContent = dist + ' km';
      }
    } catch(e){}
  } else if (start && end) {
    // fallback: request route on the fly and display
    const control = L.Routing.control({
      waypoints: [L.latLng(start[0], start[1]), L.latLng(end[0], end[1])],
      router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
      addWaypoints: false,
      show: false,
      lineOptions: { styles: [{ color:'#e31b23', weight:5 }] }
    }).addTo(map);
    control.on('routesfound', function(e){
      const r = e.routes[0];
      const dist = (r.summary.totalDistance/1000).toFixed(2);
      document.getElementById('distanceLabel').textContent = dist + ' km';
      document.getElementById('eventDistance').textContent = dist + ' km';
    });
    L.marker(start, {icon:startIcon}).addTo(map).bindPopup('Largada');
    L.marker(end, {icon:endIcon}).addTo(map).bindPopup('Chegada');
  } else if (start) {
    L.marker(start, {icon:startIcon}).addTo(map).bindPopup('Largada');
    map.setView(start, 13);
  } else {
    map.setView([-19.9167, -43.9345], 10);
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>