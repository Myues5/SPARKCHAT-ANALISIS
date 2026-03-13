/**
 * Loading Alerts System for Dashboard Filters
 * Provides consistent loading feedback for all filter actions
 */

// Loading alert configuration
const LOADING_CONFIG = {
    defaultTitle: 'Memuat Data...',
    defaultText: 'Mohon tunggu, data sedang diproses...',
    timeout: 30000, // 30 seconds timeout
    position: 'center',
    showConfirmButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false
};

// Loading alert instance tracker
let currentLoadingAlert = null;

/**
 * Show loading alert with theme awareness
 */
function showLoadingAlert(title = LOADING_CONFIG.defaultTitle, text = LOADING_CONFIG.defaultText, options = {}) {
    if (!window.Swal || typeof window.Swal.fire !== 'function') {
        console.warn('SweetAlert2 not available, skipping loading alert');
        return null;
    }

    // Close any existing loading alert
    if (currentLoadingAlert) {
        try {
            Swal.close();
            currentLoadingAlert = null;
        } catch (e) {
            console.warn('Error closing previous alert:', e);
        }
    }

    const isDark = document.documentElement.classList.contains('dark');

    // GANTI dengan loading sederhana seperti di gambar
    const config = {
        title: title,
        html: `<div style="color: ${isDark ? '#9ca3af' : '#6b7280'}; font-size: 14px; margin-top: 10px;">${text}</div>`,
        allowOutsideClick: LOADING_CONFIG.allowOutsideClick,
        allowEscapeKey: LOADING_CONFIG.allowEscapeKey,
        showConfirmButton: LOADING_CONFIG.showConfirmButton,
        background: isDark ? '#111827' : '#ffffff',
        color: isDark ? '#e5e7eb' : '#111827',
        didOpen: () => {
            Swal.showLoading();  // Gunakan loading spinner bawaan SweetAlert2
        },
        ...options
    };

    currentLoadingAlert = Swal.fire(config);

    // Auto-close after timeout
    setTimeout(() => {
        if (currentLoadingAlert && Swal.isVisible()) {
            hideLoadingAlert();
        }
    }, LOADING_CONFIG.timeout);

    return currentLoadingAlert;
}

/**
 * Hide current loading alert
 */
function hideLoadingAlert() {
    if (window.Swal) {
        try {
            if (Swal.isVisible()) {
                Swal.close();
            }
        } catch (e) {
            console.warn('Error hiding loading alert:', e);
        }
    }
    currentLoadingAlert = null;
}

/**
 * Show success alert after loading
 */
function showSuccessAlert(title = 'Berhasil!', text = 'Data berhasil dimuat', duration = 2000) {
    if (!window.Swal) return;

    const isDark = document.documentElement.classList.contains('dark');

    Swal.fire({
        title,
        text,
        icon: 'success',
        timer: duration,
        showConfirmButton: false,
        background: isDark ? '#1f2937' : '#ffffff',
        color: isDark ? '#f9fafb' : '#1f2937',
        toast: true,
        position: 'top-end'
    });
}

/**
 * Initialize loading alerts for all filter elements
 */
function initializeLoadingAlerts() {
    // Dashboard date filters
    const dashboardDateFilters = document.querySelectorAll('input[name="date_from"], input[name="date_to"]');
    dashboardDateFilters.forEach(input => {
        input.addEventListener('change', () => {
            showLoadingAlert('Memfilter Data...', 'Sedang memuat data berdasarkan tanggal yang dipilih...');
        });
    });

    // Export buttons - DISABLED to prevent double loading alerts
    // const exportButtons = document.querySelectorAll('#export-button, [href*="export"]');
    // exportButtons.forEach(button => {
    //     button.addEventListener('click', () => {
    //         showLoadingAlert('Mengekspor Data...', 'Sedang menyiapkan file untuk diunduh...');
    //     });
    // });

    // Chart date filter forms - DISABLED to prevent stuck loading alerts
    // const chartFilterForm = document.getElementById('chartDateFilterForm');
    // if (chartFilterForm) {
    //     chartFilterForm.addEventListener('submit', () => {
    //         showLoadingAlert('Memuat Chart...', 'Sedang memperbarui grafik berdasarkan filter tanggal...');
    //     });
    // }

    // Customer date filter forms - DISABLED to prevent stuck loading alerts
    // const customerFilterForm = document.getElementById('customerDateFilterForm');
    // if (customerFilterForm) {
    //     customerFilterForm.addEventListener('submit', () => {
    //         showLoadingAlert('Memuat Data Customer...', 'Sedang memperbarui data customer berdasarkan filter...');
    //     });
    // }

    // Agent date filter
    const agentDateFilter = document.getElementById('agentDateFilter');
    if (agentDateFilter) {
        agentDateFilter.addEventListener('submit', () => {
            showLoadingAlert('Memuat Data Agent...', 'Sedang memperbarui status dan performa agent...');
        });
    }

    // CSAT filter button
    const csatFilterBtn = document.querySelector('button[onclick="filterCSATTable()"]');
    if (csatFilterBtn) {
        csatFilterBtn.addEventListener('click', () => {
            showLoadingAlert('Memfilter CSAT...', 'Sedang memuat data CSAT berdasarkan filter...');
        });
    }

    // Per page selectors
    const perPageSelectors = document.querySelectorAll('select[name$="_per_page"], select[onchange*="PerPage"]');
    perPageSelectors.forEach(select => {
        select.addEventListener('change', () => {
            showLoadingAlert('Memuat Halaman...', 'Sedang memperbarui jumlah data per halaman...');
        });
    });

    // Pagination links
    const paginationLinks = document.querySelectorAll('a[data-ajax], a.rating-nav, a.agent-nav');
    paginationLinks.forEach(link => {
        link.addEventListener('click', () => {
            showLoadingAlert('Memuat Halaman...', 'Sedang berpindah ke halaman yang dipilih...');
        });
    });

    // Navigation items (sections)
    const navItems = document.querySelectorAll('.nav-item:not([data-external="true"])');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetSection = item.getAttribute('data-target')?.replace('Section', '') || 'dashboard';
            const sectionNames = {
                dashboard: 'Dashboard',
                agents: 'Data Agent',
                analytics: 'Analytics',
                customer: 'Data Customer'
            };

            showLoadingAlert(
                `Memuat ${sectionNames[targetSection] || targetSection}...`,
                'Sedang berpindah ke halaman yang dipilih...'
            );
        });
    });

    // Customer section filters - DISABLED to prevent loading alerts on dropdown interactions
    // const customerSectionFilters = document.querySelectorAll('#customerSection form');
    // customerSectionFilters.forEach(form => {
    //     form.addEventListener('submit', (e) => {
    //         showLoadingAlert('Memfilter Data Customer...', 'Sedang memuat data berdasarkan filter yang dipilih...');
    //     });
    // });

    // Dropdown filters in customer section - DISABLED to prevent loading alerts on dropdown interactions
    // const dropdownButtons = document.querySelectorAll('#customerSection button[type="button"]');
    // dropdownButtons.forEach(button => {
    //     button.addEventListener('click', (e) => {
    //         if (e.target.closest('form')) {
    //             showLoadingAlert('Memfilter Data...', 'Sedang memperbarui data berdasarkan pilihan...');
    //         }
    //     });
    // });

    // Report dropdown actions - DISABLED to prevent double loading alerts
    // const reportActions = document.querySelectorAll('[onclick*="export"], [onclick*="import"]');
    // reportActions.forEach(action => {
    //     action.addEventListener('click', () => {
    //         if (action.getAttribute('onclick')?.includes('export')) {
    //             showLoadingAlert('Mengekspor Report...', 'Sedang menyiapkan file report untuk diunduh...');
    //         } else if (action.getAttribute('onclick')?.includes('import')) {
    //             showLoadingAlert('Mengimpor Data...', 'Sedang memproses file yang diupload...');
    //         }
    //     });
    // });

    console.log('✅ Loading alerts initialized for all filter actions');
}

/**
 * Override existing filter functions to include loading alerts
 */
function enhanceExistingFunctions() {
    // Enhance filterCSATTable function
    if (window.filterCSATTable) {
        const originalFilterCSAT = window.filterCSATTable;
        window.filterCSATTable = function() {
            showLoadingAlert('Memfilter Data CSAT...', 'Sedang memuat data CSAT berdasarkan filter yang dipilih...');
            return originalFilterCSAT.apply(this, arguments);
        };
    }

    // Enhance changePerPage function
    if (window.changePerPage) {
        const originalChangePerPage = window.changePerPage;
        window.changePerPage = function(perPage) {
            showLoadingAlert('Memperbarui Halaman...', `Sedang memuat ${perPage} data per halaman...`);
            return originalChangePerPage.apply(this, arguments);
        };
    }

    // Enhance changeCSATPerPage function
    if (window.changeCSATPerPage) {
        const originalChangeCSATPerPage = window.changeCSATPerPage;
        window.changeCSATPerPage = function() {
            const perPage = document.getElementById('csatPerPage')?.value || '10';
            showLoadingAlert('Memperbarui Halaman CSAT...', `Sedang memuat ${perPage} data CSAT per halaman...`);
            return originalChangeCSATPerPage.apply(this, arguments);
        };
    }

    // Enhance exportFilteredReport function - DISABLED to prevent double loading alerts
    // if (window.exportFilteredReport) {
    //     const originalExportReport = window.exportFilteredReport;
    //     window.exportFilteredReport = function() {
    //         showLoadingAlert('Menyiapkan Export...', 'Sedang memvalidasi filter dan menyiapkan data untuk export...');
    //         return originalExportReport.apply(this, arguments);
    //     };
    // }
}

/**
 * Auto-hide loading alerts on page load completion
 */
function setupAutoHide() {
    // Hide loading alert when page is fully loaded
    window.addEventListener('load', () => {
        setTimeout(() => {
            hideLoadingAlert();
            // Show success message if we were loading
            if (currentLoadingAlert) {
                showSuccessAlert('Data Berhasil Dimuat!', 'Halaman telah dimuat dengan lengkap');
            }
        }, 500);
    });

    // Hide loading alert on navigation completion
    window.addEventListener('pageshow', () => {
        setTimeout(hideLoadingAlert, 300);
    });

    // Hide loading alert if user navigates away
    window.addEventListener('beforeunload', () => {
        hideLoadingAlert();
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeLoadingAlerts();
    enhanceExistingFunctions();
    setupAutoHide();
});

// Export functions for global use
window.LoadingAlerts = {
    show: showLoadingAlert,
    hide: hideLoadingAlert,
    showSuccess: showSuccessAlert
};
