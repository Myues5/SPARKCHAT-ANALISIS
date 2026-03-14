// Enhanced CSAT filtering using JSON API for better reliability
function filterCSATTable() {
    console.log('🚀 filterCSATTable called - starting enhanced filtering');

    const search = document.getElementById('csatSearch')?.value || '';
    const startDate = document.getElementById('csatStartDate')?.value || '';
    const endDate = document.getElementById('csatEndDate')?.value || '';
    const perPage = document.getElementById('csatPerPage')?.value || '10';
    const csatCsId = document.getElementById('csatCsSelect')?.value || '';

    console.log('🔍 Filter parameters:', { search, startDate, endDate, perPage, csatCsId });

    // Build API URL for JSON data
    const apiUrl = new URL(window.location.origin + '/admin/dashboard/csat-data');
    apiUrl.searchParams.set('csat_page', '1');
    if (search) apiUrl.searchParams.set('csat_search', search);
    if (startDate) apiUrl.searchParams.set('csat_start_date', startDate);
    if (endDate) apiUrl.searchParams.set('csat_end_date', endDate);
    if (perPage) apiUrl.searchParams.set('csat_per_page', perPage);
    if (csatCsId) apiUrl.searchParams.set('csat_cs_id', csatCsId);

    // Show loading indicator
    window.LoadingAlerts?.show('Memuat Data...', 'Sedang memuat data berdasarkan filter...');

    console.log('🚀 Starting CSAT filter request to:', apiUrl.toString());

    // Fetch CSAT data and analytics data in parallel
    const csatPromise = fetch(apiUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    // Build analytics bundle URL with CSAT date filters for dashboard stats
    const analyticsUrl = new URL(window.location.origin + '/admin/dashboard/analytics-bundle');
    if (startDate) analyticsUrl.searchParams.set('chart_start_date', startDate);
    if (endDate) analyticsUrl.searchParams.set('chart_end_date', endDate);
    // Pass CSAT filters to analytics bundle so it can filter dashboard stats accordingly
    if (search) analyticsUrl.searchParams.set('csat_search', search);
    if (csatCsId) analyticsUrl.searchParams.set('csat_cs_id', csatCsId);

    const analyticsPromise = fetch(analyticsUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

   Promise.all([csatPromise, analyticsPromise])
.then(async ([csatResponse, analyticsResponse]) => {

    console.log('📡 CSAT response status:', csatResponse.status);
    console.log('📡 Analytics response status:', analyticsResponse.status);

    if (!csatResponse.ok) {
        throw new Error(`CSAT HTTP ${csatResponse.status}: ${csatResponse.statusText}`);
    }

    if (!analyticsResponse.ok) {
        throw new Error(`Analytics HTTP ${analyticsResponse.status}: ${analyticsResponse.statusText}`);
    }

    let csatData = await csatResponse.json();
    let analyticsData = await analyticsResponse.json();

    console.log('📄 Received CSAT data:', csatData);
    console.log('📄 Received analytics data:', analyticsData);

    // Update CSAT table
    updateCSATTable(csatData.csatResponsesPaginated.data || []);

    // Update pagination
    updateCSATPagination(csatData.csatResponsesPaginated);

    // Update stats cards
    updateCSATStats(csatData);

    // Update dashboard statistics
    if (analyticsData && analyticsData.data) {
        updateDashboardStats(analyticsData.data);
    }

    // Update URL without reload
    const url = new URL(window.location.origin + window.location.pathname);
    url.searchParams.set('section', 'agents');
    url.searchParams.set('csat_page', '1');

    if (search) url.searchParams.set('csat_search', search);
    if (startDate) url.searchParams.set('csat_start_date', startDate);
    if (endDate) url.searchParams.set('csat_end_date', endDate);
    if (perPage) url.searchParams.set('csat_per_page', perPage);
    if (csatCsId) url.searchParams.set('csat_cs_id', csatCsId);

    window.history.pushState({}, '', url.toString());

    console.log('✅ Filter completed successfully');

    window.LoadingAlerts?.hide();
    window.LoadingAlerts?.showSuccess('Berhasil!', 'Data berhasil dimuat.');

})
.catch(error => {

    console.error('❌ Filter error:', error);

    window.LoadingAlerts?.hide();

    if (window.LoadingAlerts?.show) {
        window.LoadingAlerts.show(
            'Error',
            'Terjadi kesalahan saat memuat data. Silakan coba lagi.',
            { icon: 'error' }
        );
    }

});

// Update CSAT table body with new data
function updateCSATTable(responses) {
    const tbody = document.getElementById('csatTableBody');
    if (!tbody) {
        console.warn('CSAT table body not found');
        return;
    }

    if (!responses || responses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="py-6 px-3 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-3 block"></i>No CSAT data available
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = responses.map(response => `
        <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.customer_name || ''}</td>
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.agent_name || ''}</td>
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.date || ''}</td>
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.first_response_time || '-'}</td>
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.average_response_time || '00:00'}</td>
            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${response.resolved_time || '-'}</td>
        </tr>
    `).join('');
}

// Update CSAT pagination
function updateCSATPagination(paginator) {
    const paginationEl = document.getElementById('csatPagination');
    if (!paginationEl) {
        console.warn('CSAT pagination element not found');
        return;
    }

    // Update summary texts
    const summaryTop = document.getElementById('csatSummaryTop');
    const summaryBottom = document.getElementById('csatSummaryBottom');

    const firstItem = paginator.firstItem || 0;
    const lastItem = paginator.lastItem || 0;
    const total = paginator.total || 0;
    const summaryText = `Showing ${firstItem} to ${lastItem} of ${total} entries`;

    if (summaryTop) summaryTop.textContent = summaryText;
    if (summaryBottom) summaryBottom.textContent = summaryText;

    // Generate pagination HTML (simplified version)
    let paginationHtml = '<div class="flex items-center gap-2">';

    if (paginator.prevPageUrl) {
        paginationHtml += `<a href="#" onclick="changeCSATPage(${paginator.currentPage - 1})" class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Previous</a>`;
    } else {
        paginationHtml += `<span class="px-2 py-1 text-xs text-gray-400 border rounded cursor-not-allowed">Previous</span>`;
    }

    // Page numbers (simplified)
    const current = paginator.currentPage;
    const last = paginator.lastPage;

    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        if (i === current) {
            paginationHtml += `<span class="px-2 py-1 text-xs bg-blue-500 text-white border border-blue-500 rounded">${i}</span>`;
        } else {
            paginationHtml += `<a href="#" onclick="changeCSATPage(${i})" class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">${i}</a>`;
        }
    }

    if (paginator.nextPageUrl) {
        paginationHtml += `<a href="#" onclick="changeCSATPage(${paginator.currentPage + 1})" class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Next</a>`;
    } else {
        paginationHtml += `<span class="px-2 py-1 text-xs text-gray-400 border rounded cursor-not-allowed">Next</span>`;
    }

    paginationHtml += '</div>';
    paginationEl.innerHTML = paginationHtml;
}

// Update dashboard statistics cards
function updateDashboardStats(data) {
    console.log('📊 Updating dashboard stats with data:', data);
    console.log('📊 Data keys:', Object.keys(data || {}));

    // Update Total Messages
    const totalMessagesEl = document.getElementById('totalMessagesValue');
    if (totalMessagesEl && data.totalAllMessages !== undefined) {
        const formattedValue = new Intl.NumberFormat().format(data.totalAllMessages);
        totalMessagesEl.textContent = formattedValue;
        console.log('✅ Updated Total Messages:', data.totalAllMessages, '->', formattedValue);
    } else {
        console.warn('❌ Could not update Total Messages - element:', !!totalMessagesEl, 'data:', data?.totalAllMessages);
    }

    // Update Positive & Negative Feedback
    const positiveEl = document.getElementById('positivePercentageValue');
    const negativeEl = document.getElementById('negativePercentageValue');

    if (positiveEl && data.positivePercentage !== undefined) {
        positiveEl.textContent = data.positivePercentage;
        console.log('✅ Updated Positive Percentage:', data.positivePercentage);
    } else {
        console.warn('❌ Could not update Positive Percentage - element:', !!positiveEl, 'data:', data?.positivePercentage);
    }

    if (negativeEl && data.negativePercentage !== undefined) {
        negativeEl.textContent = data.negativePercentage;
        console.log('✅ Updated Negative Percentage:', data.negativePercentage);
    } else {
        console.warn('❌ Could not update Negative Percentage - element:', !!negativeEl, 'data:', data?.negativePercentage);
    }

    // Update From Ads (always fetch separately since it's not in analytics bundle)
    fetchFromAdsData();
}

// Fetch and update From Ads data separately
function fetchFromAdsData() {
    const startDate = document.getElementById('csatStartDate')?.value || '';
    const endDate = document.getElementById('csatEndDate')?.value || '';

    const adsUrl = new URL(window.location.origin + '/admin/dashboard/from-ads-data');
    if (startDate) adsUrl.searchParams.set('start_date', startDate);
    if (endDate) adsUrl.searchParams.set('end_date', endDate);

    fetch(adsUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const fromAdsEl = document.getElementById('fromAdsValue');
        if (fromAdsEl && data.totalFromAds !== undefined) {
            fromAdsEl.textContent = new Intl.NumberFormat().format(data.totalFromAds);
            console.log('✅ Updated From Ads (separate fetch):', data.totalFromAds);
        }
    })
    .catch(error => {
        console.warn('❌ Failed to fetch From Ads data:', error);
    });
}

// Function to change CSAT page
function changeCSATPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('csat_page', page);
    window.location.href = url.toString();
}

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

    // Show loading indicator
    window.LoadingAlerts?.show('Memuat Data...', 'Sedang memuat data berdasarkan filter...');

    // Fetch dengan AJAX - use HTML parsing for sorting since it needs to update charts too
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('📡 Sort response status:', response.status);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        return response.text();
    })
    .then(html => {
        console.log('📄 Sort received HTML response, length:', html.length);

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Try to sync dashboard fragments
        syncDashboardFragments(doc);

        // Update URL tanpa reload
        window.history.pushState({}, '', url.toString());

        console.log('✅ Sort completed successfully');

        // Hide loading alert and show success toast
        window.LoadingAlerts?.hide();
        window.LoadingAlerts?.showSuccess('Berhasil!', 'Data berhasil dimuat.');
    })
    .catch(error => {
        console.error('❌ Sort error:', error);
        window.LoadingAlerts?.hide();
    });
}

// Sync useful dashboard fragments after AJAX updates
function syncDashboardFragments(doc) {
    console.log('🔄 Starting dashboard fragment sync...');

    const fragments = [
        { selector: '#csatStatsCards', name: 'CSAT Stats Cards' },
        { selector: '#csatSummaryTop', name: 'CSAT Summary Top' },
        { selector: '#csatSummaryBottom', name: 'CSAT Summary Bottom' },
        { selector: '#csatTableBody', name: 'CSAT Table Body' },
        { selector: '#csatPagination', name: 'CSAT Pagination' },
        { selector: '#analyticsSection', name: 'Analytics Section' },
        { selector: '#totalMessagesCard', name: 'Total Messages Card' },
        { selector: '#feedbackCard', name: 'Feedback Card' },
        { selector: '#fromAdsCard', name: 'From Ads Card' }
    ];

    let syncedCount = 0;

    fragments.forEach(({ selector, name }) => {
        const newEl = doc.querySelector(selector);
        const currentEl = document.querySelector(selector);

        if (newEl && currentEl) {
            console.log(`✅ Syncing ${name} (${selector}): ${newEl.innerHTML.length} chars`);
            currentEl.innerHTML = newEl.innerHTML;
            syncedCount++;
        } else {
            console.warn(`❌ Failed to sync ${name} (${selector}): newEl=${!!newEl}, currentEl=${!!currentEl}`);
        }
    });

    console.log(`📊 Sync complete: ${syncedCount}/${fragments.length} fragments updated`);

    // Force re-initialize any charts if analytics section was updated
    if (doc.querySelector('#analyticsSection') && window.initChatTrendChart) {
        console.log('🔄 Re-initializing charts after analytics sync...');
        setTimeout(() => {
            try {
                // Re-init charts if functions exist
                if (typeof initChatTrendChart === 'function') initChatTrendChart();
                if (typeof initCustomerChart === 'function') initCustomerChart();
                if (typeof initAdsChart === 'function') initAdsChart();
            } catch (e) {
                console.warn('Chart re-init failed:', e);
            }
        }, 100);
    }
}

// Fungsi change per page dengan AJAX (no reload)
function changeCSATPerPage() {
    filterCSATTable();
}

window.sortTable = sortTable;
window.filterCSATTable = filterCSATTable;
window.changeCSATPerPage = changeCSATPerPage;