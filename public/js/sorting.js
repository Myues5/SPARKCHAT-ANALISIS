// Fungsi sorting untuk tabel agent dengan AJAX (no reload)
function sortTable(column) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSortBy = urlParams.get('sort_by');
    const currentSortOrder = urlParams.get('sort_order') || 'asc';
    
    let newSortOrder = 'asc';
    if (currentSortBy === column) {
        newSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    }
    
    // Build URL dengan parameter sorting
    const url = new URL(window.location.origin + window.location.pathname);
    url.searchParams.set('section', 'agents');
    url.searchParams.set('sort_by', column);
    url.searchParams.set('sort_order', newSortOrder);
    url.searchParams.set('csat_page', '1');
    
    // Preserve filter
    const search = document.getElementById('csatSearch')?.value;
    const startDate = document.getElementById('csatStartDate')?.value;
    const endDate = document.getElementById('csatEndDate')?.value;
    const perPage = document.getElementById('csatPerPage')?.value;
    const csatCsId = document.getElementById('csatCsSelect')?.value;
    
    if (search) url.searchParams.set('csat_search', search);
    if (startDate) url.searchParams.set('csat_start_date', startDate);
    if (endDate) url.searchParams.set('csat_end_date', endDate);
    if (perPage) url.searchParams.set('csat_per_page', perPage);
    if (csatCsId) url.searchParams.set('csat_cs_id', csatCsId);
    
    // Fetch dengan AJAX
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
    })
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update tabel
        const newTable = doc.querySelector('#agentsSection table tbody');
        const currentTable = document.querySelector('#agentsSection table tbody');
        if (newTable && currentTable) {
            currentTable.innerHTML = newTable.innerHTML;
        }
        
        // Update pagination
        const newPagination = doc.querySelector('#agentsSection .mt-5');
        const currentPagination = document.querySelector('#agentsSection .mt-5');
        if (newPagination && currentPagination) {
            currentPagination.innerHTML = newPagination.innerHTML;
        }
        
        // Update URL tanpa reload
        window.history.pushState({}, '', url.toString());
    })
    .catch(error => {
        console.error('Sort error:', error);
    });
}

window.sortTable = sortTable;

// Fungsi filter untuk tabel agent dengan AJAX (no reload)
function filterCSATTable() {
    const url = new URL(window.location.origin + window.location.pathname);
    url.searchParams.set('section', 'agents');
    url.searchParams.set('csat_page', '1');
    
    const search = document.getElementById('csatSearch')?.value;
    const startDate = document.getElementById('csatStartDate')?.value;
    const endDate = document.getElementById('csatEndDate')?.value;
    const perPage = document.getElementById('csatPerPage')?.value;
    const csatCsId = document.getElementById('csatCsSelect')?.value;
    
    if (search) url.searchParams.set('csat_search', search);
    if (startDate) url.searchParams.set('csat_start_date', startDate);
    if (endDate) url.searchParams.set('csat_end_date', endDate);
    if (perPage) url.searchParams.set('csat_per_page', perPage);
    if (csatCsId) url.searchParams.set('csat_cs_id', csatCsId);
    
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.text();
    })
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const newTable = doc.querySelector('#agentsSection table tbody');
        const currentTable = document.querySelector('#agentsSection table tbody');
        if (newTable && currentTable) {
            currentTable.innerHTML = newTable.innerHTML;
        }
        
        const newPagination = doc.querySelector('#agentsSection .mt-5');
        const currentPagination = document.querySelector('#agentsSection .mt-5');
        if (newPagination && currentPagination) {
            currentPagination.innerHTML = newPagination.innerHTML;
        }
        
        window.history.pushState({}, '', url.toString());
    })
    .catch(error => {
        console.error('Filter error:', error);
    });
}

window.filterCSATTable = filterCSATTable;

// Fungsi change per page dengan AJAX (no reload)
function changeCSATPerPage() {
    filterCSATTable();
}

window.changeCSATPerPage = changeCSATPerPage;
