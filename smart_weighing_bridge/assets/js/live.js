/* Smart Weighing Bridge — polling status timbang real-time. */
(function () {
  'use strict';

  var ENDPOINT = '/iot_cihuy/api/live.php';

  var LABEL = {
    STANDBY:     'Siaga',
    MASUK:       'Kendaraan masuk',
    TIMBANG:     'Menimbang',
    TUNGGU_RFID: 'Baca RFID',
    KELUAR:      'Keluar'
  };

  var URUTAN = ['STANDBY', 'MASUK', 'TIMBANG', 'TUNGGU_RFID', 'KELUAR'];

  function ambil(callback) {
    fetch(ENDPOINT, { cache: 'no-store' })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) { callback(data); })
      .catch(function () {
        callback({ online: false, error: true, state: 'STANDBY', berat: 0 });
      });
  }

  window.LiveMonitor = {
    label: function (state) { return LABEL[state] || state || '—'; },
    urutan: URUTAN,

    start: function (callback, intervalMs) {
      var ms = intervalMs || 1500;
      ambil(callback);
      if (window.LiveMonitor._timer) clearInterval(window.LiveMonitor._timer);
      window.LiveMonitor._timer = setInterval(function () { ambil(callback); }, ms);
    }
  };
})();
