/* Smart Weighing Bridge — kecil, tanpa dependency. */
document.addEventListener('click', function (event) {
  var trigger = event.target.closest('[data-confirm]');
  if (!trigger) return;
  if (!window.confirm(trigger.getAttribute('data-confirm') || 'Yakin?')) {
    event.preventDefault();
  }
});
