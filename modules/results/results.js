/**
 * Results Module JavaScript
 * Filtri e ordinamento client-side per la tabella risultati
 */

document.addEventListener('DOMContentLoaded', function() {
  const categoryFilter = document.getElementById('category-filter');
  const table = document.getElementById('results-table');
  const sortableHeaders = document.querySelectorAll('.sortable');

  if (categoryFilter && table) {
    categoryFilter.addEventListener('change', function() {
      const selectedCategory = this.value;
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const category = row.getAttribute('data-category');
        row.style.display = (!selectedCategory || category === selectedCategory) ? '' : 'none';
      });
    });
  }

  if (sortableHeaders.length && table) {
    sortableHeaders.forEach(header => {
      header.addEventListener('click', function() {
        const column = this.getAttribute('data-sort');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const isAsc = !this.classList.contains('asc');
        sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
        this.classList.add(isAsc ? 'asc' : 'desc');

        rows.sort((a, b) => {
          const aVal = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
          const bVal = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';

          if (column === 'position' || column === 'time_result') {
            return isAsc ? aVal.localeCompare(bVal, undefined, { numeric: true })
                         : bVal.localeCompare(aVal, undefined, { numeric: true });
          }
          return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });

        rows.forEach(row => tbody.appendChild(row));
      });
    });
  }
});
