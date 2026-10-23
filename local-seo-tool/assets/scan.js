(function () {
  var S = window.SCAN, $ = function (id) { return document.getElementById(id); };
  function post(url, data) {
    var fd = new FormData(); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, headers: { 'X-CSRF': window.CSRF }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Something went wrong.'); return j; }); });
  }
  function cls(p, depth) { if (p.status !== 'done') return 'rp'; if (p.rank === null) return 'r4'; return p.rank <= 3 ? 'r1' : p.rank <= 10 ? 'r2' : 'r3'; }
  function label(p, depth) { return p.status !== 'done' ? '…' : p.rank === null ? depth + '+' : String(p.rank); }

  var map = null, layer = null;
  if (window.L) {
    map = L.map('map', { scrollWheelZoom: false }).setView([S.lat, S.lng], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    layer = L.layerGroup().addTo(map);
  } else { $('map').textContent = 'Map could not load. The grid view below shows the same results.'; }

  var fitted = false;
  function render(j) {
    var d = j.depth;
    if (layer) {
      layer.clearLayers();
      j.points.forEach(function (p) {
        var icon = L.divIcon({ className: '', html: '<div class="pin ' + cls(p, d) + '">' + label(p, d) + '</div>', iconSize: [34, 34], iconAnchor: [17, 17] });
        var m = L.marker([p.lat, p.lng], { icon: icon }).addTo(layer);
        var tip = document.createElement('div'); tip.innerHTML = '<b></b><br><small></small>';
        tip.querySelector('b').textContent = p.status !== 'done' ? 'Checking…' : p.rank === null ? 'Not in top ' + d : 'Rank ' + p.rank;
        tip.querySelector('small').textContent = p.top3 && p.top3.length ? 'Top 3 here: ' + p.top3.join(', ') : '';
        m.bindPopup(tip);
      });
      L.marker([S.lat, S.lng], { icon: L.divIcon({ className: '', html: '<div style="font-size:26px;color:#FCB814;text-shadow:0 0 3px #000">★</div>', iconSize: [26, 26], iconAnchor: [13, 13] }) }).addTo(layer).bindPopup(S.title);
      if (!fitted && j.points.length) { map.fitBounds(j.points.map(function (p) { return [p.lat, p.lng]; }), { padding: [24, 24] }); fitted = true; }
    }
    var g = $('grid'); g.textContent = ''; g.style.gridTemplateColumns = 'repeat(' + S.size + ', 44px)';
    j.points.forEach(function (p) { var c = document.createElement('div'); c.className = 'cell ' + cls(p, d); c.textContent = label(p, d); c.title = p.top3 && p.top3.length ? 'Top 3: ' + p.top3.join(', ') : ''; g.appendChild(c); });
    var st = j.stats;
    $('k-arp').textContent = st.arp === null ? '–' : st.arp;
    $('k-atrp').textContent = st.atrp === null ? '–' : st.atrp;
    $('k-solv').textContent = st.solv === null ? '–' : st.solv + '%';
    $('k-prog').textContent = Math.round(st.done / st.total * 100) + '%';
  }
  var fails = 0;
  function step() {
    post('api/scan_row.php', { scan_id: S.id }).then(function (j) {
      render(j); fails = 0;
      if (j.status === 'done') { $('msg').textContent = 'Scan complete. Click a point on the map to see who ranks there.'; return; }
      $('msg').textContent = j.error || 'Checking rankings… ' + j.stats.done + ' of ' + j.stats.total + ' points done.';
      setTimeout(step, j.error ? 3000 : 200);
    }, function (err) {
      fails++; $('msg').textContent = err.message + (fails < 5 ? ' Retrying…' : ' Reload the page to try again.');
      if (fails < 5) setTimeout(step, 3000);
    });
  }
  step();
})();
