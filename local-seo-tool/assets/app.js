(function () {
  function post(url, data) {
    var fd = new FormData(); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, headers: { 'X-CSRF': window.CSRF }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Something went wrong.'); return j; }); });
  }
  window.gridPost = post;
  var sf = document.getElementById('search-form');
  if (sf) sf.addEventListener('submit', function (e) {
    e.preventDefault();
    var box = document.getElementById('results'), btn = sf.querySelector('button');
    btn.disabled = true; box.textContent = 'Searching Google Maps…';
    post('api/search_business.php', { q: document.getElementById('q').value, country: document.getElementById('country').value })
      .then(function (j) {
        box.textContent = '';
        if (!j.results.length) { box.textContent = 'No businesses found. Try adding the city or area to the name.'; return; }
        j.results.forEach(function (r) {
          var row = document.createElement('div'), info = document.createElement('div'), t = document.createElement('b'), a = document.createElement('span'), b = document.createElement('button');
          row.className = 'result'; t.textContent = r.title;
          a.textContent = r.address + (r.rating ? ' · ' + r.rating + '★ (' + (r.reviews || 0) + ')' : '') + (r.category ? ' · ' + r.category : '');
          b.className = 'btn btn-gold btn-sm'; b.textContent = 'This is my business';
          b.addEventListener('click', function () { b.disabled = true; post('api/add_business.php', { i: r.i }).then(function () { location.reload(); }, function (err) { alert(err.message); b.disabled = false; }); });
          info.appendChild(t); info.appendChild(a); row.appendChild(info); row.appendChild(b); box.appendChild(row);
        });
      }, function (err) { box.textContent = err.message; })
      .then(function () { btn.disabled = false; });
  });
  document.querySelectorAll('[data-del]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.dataset.confirm !== '1') { b.dataset.confirm = '1'; b.textContent = 'Click again to remove (deletes its scans)'; return; }
      post('api/delete_business.php', { id: b.dataset.del }).then(function () { location.reload(); });
    });
  });
  var scf = document.getElementById('scan-form');
  if (scf) scf.addEventListener('submit', function (e) {
    e.preventDefault(); var btn = scf.querySelector('button'); btn.disabled = true;
    post('api/start_scan.php', { business_id: document.getElementById('biz').value, keyword: document.getElementById('kw').value, grid_size: document.getElementById('size').value, spacing: document.getElementById('spacing').value })
      .then(function (j) { location.href = 'scan.php?id=' + j.scan_id; }, function (err) { document.getElementById('scan-note').textContent = err.message; btn.disabled = false; });
  });
})();
