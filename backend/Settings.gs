// ============================================================
// backend/Settings.gs — PENGATURAN APLIKASI (server-side)
// Pengaturan UI (tema, pageSize, dll) disimpan di localStorage.
// File ini untuk pengaturan yang perlu disimpan di server.
// ============================================================

function getKecamatan(cityCode) {
  if (!cityCode || String(cityCode).length < 4) return [];

  var cacheKey = 'kec_' + String(cityCode);

  try {
    var cache  = CacheService.getScriptCache();
    var cached = cache.get(cacheKey);
    if (cached) return JSON.parse(cached);

    var url      = 'https://raw.githubusercontent.com/ibnux/data-indonesia/master/kecamatan/' + cityCode + '.json';
    var response = UrlFetchApp.fetch(url, { muteHttpExceptions: true });
    if (response.getResponseCode() !== 200) return [];

    var result = JSON.parse(response.getContentText()).map(function(item) {
      return { id: String(item.id), nama: String(item.nama) };
    });

    cache.put(cacheKey, JSON.stringify(result), 21600); // 6 jam
    return result;
  } catch (e) {
    Logger.log('getKecamatan error for ' + cityCode + ': ' + e.toString());
    return [];
  }
}
