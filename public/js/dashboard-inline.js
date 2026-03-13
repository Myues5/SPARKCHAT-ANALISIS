// Initialize chart data from PHP variables
window.chartLabels = window.chartLabels || [];
window.customerData = window.customerData || [];
window.csData = window.csData || [];
window.customerChartDates = window.customerChartDates || [];
window.customerChartNewCustomers = window.customerChartNewCustomers || [];
window.customerChartExistingCustomers = window.customerChartExistingCustomers || [];

// Default agent image
const defaultAgentSrc = "/assets/img/default-agent.svg";
const logoutUrl = "/logout";

function formatDuration(hours) {
    const totalMinutes = Math.round(hours * 60);
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;

    if (h > 0 && m > 0) {
        return `${h}h ${m}m`;
    } else if (h > 0) {
        return `${h}h`;
    } else {
        return `${m}m`;
    }
}

// ✅ Fetch Chart Data (Dashboard)
async function loadChartData() {
    try {
        const res = await fetch("/admin/dashboard/chart-data");
        if (!res.ok) throw new Error("Gagal ambil chart data");

        const data = await res.json();

        // Simpan ke variabel global
        window.chartLabels = data.labels || [];
        window.customerData = data.customers || [];
        window.csData = data.csat || [];

        // Render ulang chart
        if (typeof renderCharts === "function") {
            renderCharts();
        }
    } catch (err) {
        console.error("Chart Data Error:", err);
    }
}

// ✅ Fetch Analytics Data (Reviews / Analytics)
async function loadAnalyticsData() {
    try {
        const res = await fetch("/admin/dashboard/analytics-bundle");
        if (!res.ok) throw new Error("Gagal ambil analytics data");

        const data = await res.json();

        // Simpan ke variabel global
        window.customerChartDates = data.customerChartDates || [];
        window.customerChartNewCustomers = data.newCustomers || [];
        window.customerChartExistingCustomers = data.existingCustomers || [];

        if (typeof renderAnalyticsCharts === "function") {
            renderAnalyticsCharts();
        }
    } catch (err) {
        console.error("Analytics Data Error:", err);
    }
}

// ✅ Section display name
function getSectionDisplayName(sectionName) {
    const displayNames = {
        "dashboard": "Dashboard",
        "agents": "Data Agen",
        "reviews": "Review & Rating",
        "analytics": "Analytics & Chart"
    };
    return displayNames[sectionName] || sectionName;
}

// 🔔 Unified SweetAlert loading helper (theme-aware, safe if Swal missing)
function showLoadingSwal(title = 'Loading...', text = 'Mohon tunggu, data sedang diproses...') {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            title,
            text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { Swal.showLoading(); },
            showConfirmButton: false,
            background: isDark ? '#111827' : '#ffffff',
            color: isDark ? '#e5e7eb' : '#111827'
        });
    }
}

// 📌 Hook forms and controls that trigger filtering to show loading state
document.addEventListener('DOMContentLoaded', () => {
    // Filter forms
    ['customerDateFilterForm', 'chartDateFilterForm', 'agentDateFilter'].forEach((id) => {
        const form = document.getElementById(id);
        if (form) {
            form.addEventListener('submit', () => {
                showLoadingSwal('Filtering data...', 'Mohon tunggu, sedang memuat data hasil filter.');
            }, { passive: true });
        }
    });

    // Per-page selectors that auto-submit
    document.querySelectorAll('select[name$="_per_page"]').forEach((sel) => {
        sel.addEventListener('change', () => {
            showLoadingSwal('Updating list...', 'Mohon tunggu, sedang memuat ulang data.');
        }, { passive: true });
    });

    // Pagination/Navigation links that trigger reload
    document.querySelectorAll('a.agent-nav, a[data-ajax]').forEach((a) => {
        a.addEventListener('click', () => {
            showLoadingSwal('Loading...', 'Mohon tunggu, halaman sedang dimuat.');
        }, { passive: true });
    });
});

// Alpine.js Multi-Select Filter Component
document.addEventListener('alpine:init', () => {
    Alpine.data('multiSelectFilter', (config) => ({
        open: false,
        name: config.name,
        options: config.options || [],
        selected: config.selected || [],
        label: config.label || 'Select',

        getLabel() {
            if (this.selected.length === 0) {
                return `All ${this.label}`;
            }
            if (this.selected.length === 1) {
                return this.selected[0];
            }
            return `${this.selected.length} ${this.label} dipilih`;
        },

        toggleOption(option) {
            const index = this.selected.indexOf(option);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(option);
            }
        },

        clearAll() {
            this.selected = [];
        },

        applyFilter() {
            // Update hidden input dengan nilai yang dipisahkan koma
            const hiddenInput = document.getElementById(`ca_${this.name}_hidden`);
            if (hiddenInput) {
                hiddenInput.value = this.selected.join(',');
            }

            this.open = false;

            // Submit form
            this.$nextTick(() => {
                const form = this.$el.closest('form');
                if (form) {
                    // Show loading alert before submit
                    if (window.LoadingAlerts) {
                        window.LoadingAlerts.show(
                            'Memfilter Data Customer...',
                            `Sedang memuat data dengan filter ${this.label}...`
                        );
                    }
                    form.submit();
                }
            });
        }
    }));
});

// Modal for message & reason (Customer Review Log)
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.show-msg-reason');
    if (!btn) return;
    const message = btn.getAttribute('data-message') || '-';
    const reason = btn.getAttribute('data-reason') || '-';

    if (window.Swal && typeof window.Swal.fire === 'function') {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            title: 'Detail Review',
            html: `<div style="text-align:left">
                        <div style="margin-bottom:8px"><strong>Pesan:</strong><br>${message.replaceAll('\n', '<br>')}</div>
                        <div><strong>Alasan:</strong><br>${reason.replaceAll('\n', '<br>')}</div>
                    </div>`,
            icon: 'info',
            confirmButtonText: 'Tutup',
            background: isDark ? '#111827' : '#ffffff',
            color: isDark ? '#e5e7eb' : '#111827'
        });
    } else {
        alert(`Pesan:\n${message}\n\nAlasan:\n${reason}`);
    }
});