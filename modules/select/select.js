/**
 * Select Module JS - inizializzazione Select2
 */
document.addEventListener('DOMContentLoaded', function() {
  const selects = document.querySelectorAll('[data-module="select"] select[data-enhance="select2"]');
  if (!selects.length) return;

  // Necessita di jQuery e Select2 (caricati come vendor)
  if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
    return;
  }

  selects.forEach(function(sel) {
    const placeholder = sel.getAttribute('data-placeholder') || 'Seleziona...';
    window.jQuery(sel).select2({
      width: 'resolve',
      placeholder: placeholder,
      allowClear: true
    });
  });
});


