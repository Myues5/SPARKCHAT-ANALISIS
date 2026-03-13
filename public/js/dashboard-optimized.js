/**
 * Optimized Dashboard Navigation & Filtering
 * Mengatasi loading lambat pada sidebar dan filter agent
 */

// Cache untuk menyimpan data yang sudah dimuat
const sectionCache = new Map();
const filterCache = new Map();

// State management
let currentSection = 'dashboard';
let isLoading = false;

// Optimized section switching tanpa reload
function switchSectionOptimized(sectionName) {
    if (isLoading) return;
    
    // Jika section sudah aktif, skip
    if (currentSection === sectionName) return;
    
    isLoading = true;
    currentSection = sectionName;
    
    // Update UI immediately untuk responsiveness
    updateSidebarUI(sectionName);
    showSectionContent(sectionName);
    
    // Load data in background jika belum ada di cache
    loadSectionDataOptimized(sectionName).finally(() => {
        isLoading = false;
    });
}

// Update sidebar UI tanpa delay
function updateSidebarUI(sectionName) {
    document.querySelectorAll('.nav-item').forEach(item => {
        const isActive = item.getAttribute('data-target') === sectionName + 'Section';
        item.classList.toggle('active', isActive);
    });
    
    // Update URL tanpa reload
    const url = new URL(window.location.href);
    url.searchParams.set('section', sectionName);
    history.replaceState({ section: sectionName }, '', url.toString());
    
    localStorage.setItem('current_section', sectionName);
}

// Show section content immediately
function showSectionContent(sectionName) {
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('hidden');
    });
    
    const targetSection = document.getElementById(sectionName + 'Section');
    if (targetSection) {
        targetSection.classList.remove('hidden');
        
        // Add loading skeleton jika data belum ada
        if (!sectionCache.has(sectionName)) {
            showLoadingSkeleton(targetSection);
        }
    }
}

// Loading skeleton untuk UX yang lebih baik
function showLoadingSkeleton(container) {
    const skeleton = document.createElement('div');
    skeleton.className = 'loading-skeleton';
    skeleton.innerHTML = `
        <div class="animate-pulse space-y-4 p-4">
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
            <div class="h-32 bg-gray-300 dark:bg-gray-600 rounded"></div>
        </div>
    `;
    
    // Insert skeleton setelah header
    const firstChild = container.firstElementChild;
    if (firstChild) {
        firstChild.after(skeleton);
    }
}

// Optimized data loading dengan caching
async function loadSectionDataOptimized(sectionName) {
    // Check cache first
    if (sectionCache.has(sectionName)) {
        const cachedData = sectionCache.get(sectionName);
        // Check if cache is still fresh (5 minutes)
        if (Date.now() - cachedData.timestamp < 300000) {
            updateSectionWithData(sectionName, cachedData.data);
            return;
        }
    }
    
    try {
        let endpoint = '';
        let params = new URLSearchParams();
        
        switch (sectionName) {
            case 'agents':
                endpoint = '/admin/dashboard/agents-data';
                params.set('rt_days', '30');
                break;
            case 'analytics':
                endpoint = '/admin/dashboard/analytics-bundle';
                break;
            case 'customer':
                endpoint = '/admin/dashboard/section-data';
                params.set('section', 'customer');
                break;
            default:
                endpoint = '/admin/dashboard/summary';
        }
        
        const response = await fetch(`${endpoint}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        if (!response.ok) throw new Error('Network error');
        
        const data = await response.json();
        
        // Cache the data
        sectionCache.set(sectionName, {
            data: data,
            timestamp: Date.now()
        });
        
        updateSectionWithData(sectionName, data);
        
    } catch (error) {
        console.error(`Failed to load ${sectionName} data:`, error);
        showErrorMessage(sectionName);
    } finally {
        // Remove loading skeleton
        document.querySelectorAll('.loading-skeleton').forEach(el => el.remove());
    }
}

// Update section dengan data baru
function updateSectionWithData(sectionName, data) {
    const section = document.getElementById(sectionName + 'Section');
    if (!section) return;
    
    // Remove loading skeleton
    section.querySelectorAll('.loading-skeleton').forEach(el => el.remove());
    
    switch (sectionName) {
        case 'agents':
            updateAgentsSection(data);
            break;
        case 'analytics':
            updateAnalyticsSection(data);
            break;
        case 'customer':
            updateCustomerSection(data);
            break;
        case 'dashboard':
            updateDashboardSection(data);
            break;
    }
}

// Optimized agent filter dengan debouncing
let agentFilterTimeout;
function optimizedAgentFilter() {
    clearTimeout(agentFilterTimeout);
    
    agentFilterTimeout = setTimeout(async () => {
        const formData = new FormData(document.getElementById('agentDateFilter'));
        const params = new URLSearchParams(formData);
        
        // Check filter cache
        const cacheKey = params.toString();
        if (filterCache.has(cacheKey)) {
            const cached = filterCache.get(cacheKey);
            if (Date.now() - cached.timestamp < 60000) { // 1 minute cache
                updateAgentsTable(cached.data);
                return;
            }
        }
        
        try {
            showFilterLoading();
            
            const response = await fetch(`/admin/dashboard/agents-data?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            
            if (!response.ok) throw new Error('Filter failed');
            
            const data = await response.json();
            
            // Cache filter result
            filterCache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            updateAgentsTable(data);
            
        } catch (error) {
            console.error('Agent filter error:', error);
            showFilterError();
        } finally {
            hideFilterLoading();
        }
    }, 300); // 300ms debounce
}

// Update agents table tanpa reload
function updateAgentsTable(data) {
    const tableBody = document.querySelector('#agentsSection tbody');
    if (!tableBody || !data.agents) return;
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    // Add new rows
    data.agents.forEach(agent => {
        const row = createAgentRow(agent);
        tableBody.appendChild(row);
    });
    
    // Update pagination if exists
    updatePagination(data.pagination);
}

// Create agent row element
function createAgentRow(agent) {
    const row = document.createElement('tr');
    row.className = 'border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors';
    
    row.innerHTML = `
        <td class="py-2 px-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-semibold">
                    ${agent.name.charAt(0)}
                </span>
                <span class="truncate">${agent.name}</span>
            </div>
        </td>
        <td class="py-2 px-3 truncate">${agent.contact || '-'}</td>
        <td class="py-2 px-3 text-center">
            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(agent.status)}">
                ${agent.status.charAt(0).toUpperCase() + agent.status.slice(1)}
            </span>
        </td>
        <td class="py-2 px-3 text-center">${agent.feedback || 0}</td>
        <td class="py-2 px-3 text-center">${agent.avg_fast ? agent.avg_fast + 'm' : '0m'}</td>
        <td class="py-2 px-3 text-center">${agent.avg_slow ? agent.avg_slow + 'm' : '0m'}</td>
        <td class="py-2 px-3 text-center">${agent.online_time || '-'}</td>
        <td class="py-2 px-3 text-center">${agent.total_handle_chat || 0}</td>
        <td class="py-2 px-3 text-center">
            <span class="font-medium">${agent.satisfaction || 0}%</span>
            <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mt-1">
                <div class="h-1.5 rounded-full ${getSatisfactionColor(agent.satisfaction)}" 
                     style="width: ${agent.satisfaction || 0}%"></div>
            </div>
        </td>
        <td class="py-2 px-3 text-xs">
            ${formatStatusDurations(agent.status_durations)}
        </td>
    `;
    
    return row;
}

// Helper functions
function getStatusClass(status) {
    const classes = {
        online: 'bg-green-100 text-green-700',
        busy: 'bg-yellow-100 text-yellow-700',
        offline: 'bg-gray-100 text-gray-600'
    };
    return classes[status] || classes.offline;
}

function getSatisfactionColor(satisfaction) {
    if (satisfaction >= 90) return 'bg-green-600';
    if (satisfaction >= 75) return 'bg-yellow-500';
    return 'bg-red-500';
}

function formatStatusDurations(durations) {
    if (!durations || !Array.isArray(durations)) return '-';
    
    let html = '';
    let total = 0;
    
    durations.forEach(duration => {
        const hours = Math.floor(duration.durasi_jam);
        const minutes = Math.floor((duration.durasi_jam - hours) * 60);
        const formatted = `${hours}h ${minutes}m`;
        
        html += `<div class="flex justify-between mb-1 px-1">
            <span class="capitalize">${duration.status}</span>
            <span>${formatted}</span>
        </div>`;
        
        total += duration.durasi_jam;
    });
    
    const totalHours = Math.floor(total);
    const totalMinutes = Math.floor((total - totalHours) * 60);
    
    html += `<div class="flex justify-between font-medium bg-blue-50 dark:bg-blue-800 p-1.5 rounded mt-1">
        <span>Total</span>
        <span>${totalHours}h ${totalMinutes}m</span>
    </div>`;
    
    return html;
}

// Loading states
function showFilterLoading() {
    const button = document.querySelector('#agentDateFilter button[type="submit"]');
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Loading...';
        button.disabled = true;
    }
}

function hideFilterLoading() {
    const button = document.querySelector('#agentDateFilter button[type="submit"]');
    if (button) {
        button.innerHTML = 'Apply';
        button.disabled = false;
    }
}

function showFilterError() {
    // Show toast error
    if (window.Swal) {
        Swal.fire({
            title: 'Filter Error',
            text: 'Gagal memuat data agent. Silakan coba lagi.',
            icon: 'error',
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false
        });
    }
}

function showErrorMessage(sectionName) {
    const section = document.getElementById(sectionName + 'Section');
    if (section) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-center py-8 text-gray-500';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
            <p>Gagal memuat data ${sectionName}</p>
            <button onclick="loadSectionDataOptimized('${sectionName}')" 
                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Coba Lagi
            </button>
        `;
        section.appendChild(errorDiv);
    }
}

// Initialize optimized navigation
function initOptimizedNavigation() {
    // Replace existing nav item listeners
    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.dataset.external === 'true') return;
        
        // Remove existing listeners
        item.replaceWith(item.cloneNode(true));
        
        // Add optimized listener
        const newItem = document.querySelector(`[data-target="${item.dataset.target}"]`);
        if (newItem) {
            newItem.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionName = newItem.dataset.target.replace('Section', '');
                switchSectionOptimized(sectionName);
            });
        }
    });
    
    // Replace agent filter form
    const agentForm = document.getElementById('agentDateFilter');
    if (agentForm) {
        agentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            optimizedAgentFilter();
        });
    }
    
    console.log('✅ Optimized navigation initialized');
}

// Preload critical sections
function preloadCriticalSections() {
    // Preload dashboard summary
    setTimeout(() => {
        if (!sectionCache.has('dashboard')) {
            loadSectionDataOptimized('dashboard');
        }
    }, 1000);
    
    // Preload agents data after 3 seconds
    setTimeout(() => {
        if (!sectionCache.has('agents')) {
            loadSectionDataOptimized('agents');
        }
    }, 3000);
}

// Clear cache periodically
function setupCacheCleanup() {
    setInterval(() => {
        const now = Date.now();
        
        // Clear section cache older than 10 minutes
        for (const [key, value] of sectionCache.entries()) {
            if (now - value.timestamp > 600000) {
                sectionCache.delete(key);
            }
        }
        
        // Clear filter cache older than 5 minutes
        for (const [key, value] of filterCache.entries()) {
            if (now - value.timestamp > 300000) {
                filterCache.delete(key);
            }
        }
    }, 60000); // Check every minute
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initOptimizedNavigation();
    preloadCriticalSections();
    setupCacheCleanup();
});

// Export for global use
window.DashboardOptimized = {
    switchSection: switchSectionOptimized,
    filterAgents: optimizedAgentFilter,
    clearCache: () => {
        sectionCache.clear();
        filterCache.clear();
    }
};