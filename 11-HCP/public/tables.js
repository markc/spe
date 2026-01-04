/**
 * tables.js - Lightweight table sorting, filtering, and pagination
 * No dependencies, vanilla JS only (~60 lines)
 * Copyright (C) 2025 Mark Constable <mc@netserva.org> (MIT License)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Sortable tables
    document.querySelectorAll('table.sortable').forEach(table => {
        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('th[data-sort]');

        headers.forEach((th, colIndex) => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const key = th.dataset.sort;
                const asc = th.classList.toggle('sort-asc');
                th.classList.toggle('sort-desc', !asc);

                // Remove sort classes from other headers
                headers.forEach(h => h !== th && h.classList.remove('sort-asc', 'sort-desc'));

                // Sort rows
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort((a, b) => {
                    const aVal = a.cells[colIndex]?.textContent.trim().toLowerCase() || '';
                    const bVal = b.cells[colIndex]?.textContent.trim().toLowerCase() || '';

                    // Try numeric sort first
                    const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
                    const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return asc ? aNum - bNum : bNum - aNum;
                    }

                    return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                });

                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });

    // Table search/filter
    document.querySelectorAll('#table-search').forEach(input => {
        const table = input.closest('.card')?.querySelector('table') || document.querySelector('table');
        if (!table) return;

        const tbody = table.querySelector('tbody');

        input.addEventListener('input', () => {
            const query = input.value.toLowerCase();
            tbody.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // Simple pagination (show/hide rows)
    document.querySelectorAll('[data-perpage]').forEach(select => {
        const table = select.closest('.card')?.querySelector('table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        let currentPage = 1;

        const paginate = () => {
            const perPage = parseInt(select.value) || 10;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const start = (currentPage - 1) * perPage;

            rows.forEach((row, i) => {
                row.style.display = (i >= start && i < start + perPage) ? '' : 'none';
            });
        };

        select.addEventListener('change', () => { currentPage = 1; paginate(); });
        paginate();
    });
});
