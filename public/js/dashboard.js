// ==== Theme Initialization ====
const savedTheme = localStorage.getItem("theme") || "light";
localStorage.setItem("theme", savedTheme);
document.documentElement.classList.toggle("dark", savedTheme === "dark");

// ==== State Flags ====
let isDropdownOpen = false;
let isSidebarOpen = false;
let isReloading = false;
let _isNavigating = false;

// ==== Chart Instances ====
let _chatChartInstance = null;
let _customerChartInstance = null;
let _adsChartInstance = null;
// Abort controllers for in-flight analytics requests
let _chatAbort = null;
let _custAbort = null;
let _adsAbort = null;

document.addEventListener("DOMContentLoaded", () => {
    animateCards();
    initializeSectionFromURL();
    attachPaginationListeners();
    handleChartDateFilter();
    handleCustomerDateFilter();
    handleAdsDateFilter();
    initSessionManagement();

    // Initialize auto-refresh date system
    initAutoRefreshDates();

    loadFilterFromLocal("chart_start_date", 'input[name="chart_start_date"]');
    loadFilterFromLocal("chart_end_date", 'input[name="chart_end_date"]');
    loadFilterFromLocal("second_start_date", 'input[name="second_start_date"]');
    loadFilterFromLocal("second_end_date", 'input[name="second_end_date"]');

    // Initialize analytics section if visible
    const analyticsSection = document.getElementById("analyticsSection");
    if (analyticsSection && !analyticsSection.classList.contains("hidden")) {
        initializeAnalyticsCharts();
    }

    // First response time is already loaded from server, no need to fetch via AJAX
});

document
    .getElementById("export-button")
    .addEventListener("click", function (e) {
        e.preventDefault();
        const exportType = document.querySelector(
            'select[name="export_type"]'
        ).value;
        const dateFrom = document.querySelector(
            'input[name="date_from"]'
        ).value;
        const dateTo = document.querySelector('input[name="date_to"]').value;

        let url = "";
        if (exportType === "message_log") {
            url =
                this.dataset.exportMessageLog +
                "?date_from=" +
                dateFrom +
                "&date_to=" +
                dateTo;
        } else {
            url =
                this.dataset.exportReviewLog +
                "?date_from=" +
                dateFrom +
                "&date_to=" +
                dateTo;
        }

        window.location.href = url;
    });

function switchSection(sectionName) {
    document
        .querySelectorAll(".content-section")
        .forEach((sec) => sec.classList.add("hidden"));
    const show = document.getElementById(sectionName + "Section");
    if (show) show.classList.remove("hidden");

    document.querySelectorAll(".nav-item").forEach((n) => {
        n.classList.toggle(
            "active",
            n.getAttribute("data-target") === sectionName + "Section"
        );
    });

    localStorage.setItem("current_section", sectionName);

    const url = new URL(window.location.href);
    url.searchParams.set("section", sectionName);
    history.pushState({ section: sectionName }, "", url.toString());

    // Initialize analytics charts when switching to analytics
    if (sectionName === "analytics") {
        setTimeout(() => {
            const needFetch =
                !window.chartLabels || window.chartLabels.length === 0;
            if (!needFetch) {
                initializeAnalyticsCharts();
                return;
            }

            // Prefer lightweight endpoints over heavy bundle
            try {
                const chartStart =
                    localStorage.getItem("chart_start_date") ||
                    document.querySelector('input[name="chart_start_date"]')
                        ?.value ||
                    "";
                const chartEnd =
                    localStorage.getItem("chart_end_date") ||
                    document.querySelector('input[name="chart_end_date"]')
                        ?.value ||
                    "";
                const secondStart =
                    localStorage.getItem("second_start_date") ||
                    document.querySelector('input[name="second_start_date"]')
                        ?.value ||
                    "";
                const secondEnd =
                    localStorage.getItem("second_end_date") ||
                    document.querySelector('input[name="second_end_date"]')
                        ?.value ||
                    "";

                document
                    .getElementById("chartLoading")
                    ?.classList.remove("hidden");
                document
                    .getElementById("customerChartLoading")
                    ?.classList.remove("hidden");
                isReloading = true;

                // Fire both in parallel, they'll hide their own loaders in finally
                fetchChatTrend(chartStart, chartEnd);
                fetchCustomerReportData(secondStart, secondEnd);
            } catch (err) {
                console.warn("Analytics init fetch failed", err);
                initializeAnalyticsCharts();
            }
        }, 100);
    }

    // First response time is already loaded from server when switching to agents
}

function initializeAnalyticsCharts() {
    // Check if data exists from server-side rendering
    if (
        window.chartLabels &&
        Array.isArray(window.chartLabels) &&
        window.chartLabels.length > 0
    ) {
        initChatTrendChart(
            window.chartLabels,
            window.customerData || [],
            window.csData || []
        );
    }

    if (
        window.customerChartDates &&
        Array.isArray(window.customerChartDates) &&
        window.customerChartDates.length > 0
    ) {
        initCustomerChart(
            window.customerChartDates,
            window.customerChartNewCustomers || [],
            window.customerChartExistingCustomers || []
        );
    }

    if (
        window.fromAdsChartDates &&
        Array.isArray(window.fromAdsChartDates) &&
        window.fromAdsChartDates.length > 0
    ) {
        initAdsChart(
            window.fromAdsChartDates,
            window.fromAdsChartData || [],
            window.nonAdsChartData || []
        );
    }
}

function initChatTrendChart(labels = [], customerData = [], csData = []) {
    const canvas = document.getElementById("chatTrendChart");
    const loading = document.getElementById("chartLoading");

    if (!canvas) return;

    try {
        const ctx = canvas.getContext("2d");

        // Destroy existing chart
        if (_chatChartInstance) {
            _chatChartInstance.destroy();
            _chatChartInstance = null;
        }

        // Check for valid data
        const hasValidData =
            labels.length > 0 && (customerData.length > 0 || csData.length > 0);

        if (!hasValidData) {
            showChartNoData("chatTrendChart", "chartLoading");
            return;
        }

        // Hide loading, show canvas
        canvas.style.display = "block";
        if (loading) loading.classList.add("hidden");

        // Create chart
        _chatChartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Customer Messages",
                        data: customerData,
                        fill: true,
                        backgroundColor: "rgba(59, 130, 246, 0.1)",
                        borderColor: "rgba(59, 130, 246, 1)",
                        tension: 0.4,
                        pointBackgroundColor: "rgba(59, 130, 246, 1)",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: "CS Responses",
                        data: csData,
                        fill: true,
                        backgroundColor: "rgba(34, 197, 94, 0.1)",
                        borderColor: "rgba(34, 197, 94, 1)",
                        tension: 0.4,
                        pointBackgroundColor: "rgba(34, 197, 94, 1)",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: "index" },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: "#9CA3AF" },
                        grid: { color: "rgba(156, 163, 175, 0.1)" },
                        title: {
                            display: true,
                            text: "Number of Messages",
                            color: "#6B7280",
                        },
                    },
                    x: {
                        ticks: { color: "#9CA3AF", maxRotation: 0 },
                        grid: { color: "rgba(156, 163, 175, 0.05)" },
                        title: {
                            display: true,
                            text: "Date",
                            color: "#6B7280",
                        },
                    },
                },
                plugins: {
                    legend: {
                        labels: { color: "#6B7280" },
                        position: "bottom",
                    },
                    tooltip: {
                        backgroundColor: "#1f2937",
                        titleColor: "#ffffff",
                        bodyColor: "#d1d5db",
                        cornerRadius: 8,
                        displayColors: false,
                    },
                },
            },
        });

        console.log("✅ Chat trend chart initialized");
    } catch (error) {
        console.error("❌ Chart initialization failed:", error);
        showChartNoData("chatTrendChart", "chartLoading");
    }
}

function initCustomerChart(
    dates = [],
    newCustomers = [],
    existingCustomers = []
) {
    const canvas = document.getElementById("customerChart");
    const loading = document.getElementById("customerChartLoading");

    if (!canvas) return;

    try {
        const ctx = canvas.getContext("2d");

        // Destroy existing chart
        if (_customerChartInstance) {
            _customerChartInstance.destroy();
            _customerChartInstance = null;
        }

        // Check for valid data
        const hasValidData =
            dates.length > 0 &&
            (newCustomers.length > 0 || existingCustomers.length > 0);

        if (!hasValidData) {
            showChartNoData("customerChart", "customerChartLoading");
            return;
        }

        // Hide loading, show canvas
        canvas.style.display = "block";
        if (loading) loading.classList.add("hidden");

        // Create chart
        _customerChartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: dates,
                datasets: [
                    {
                        label: "New Customer",
                        data: newCustomers,
                        backgroundColor: "rgba(34, 197, 94, 0.8)",
                        borderColor: "rgba(34, 197, 94, 1)",
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: "Existing Customer",
                        data: existingCustomers,
                        backgroundColor: "rgba(59, 130, 246, 0.8)",
                        borderColor: "rgba(59, 130, 246, 1)",
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: "index" },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: "#9CA3AF" },
                        grid: { color: "rgba(156, 163, 175, 0.1)" },
                        title: {
                            display: true,
                            text: "Number of Customers",
                            color: "#6B7280",
                        },
                    },
                    x: {
                        ticks: { color: "#9CA3AF", maxRotation: 0 },
                        grid: { color: "rgba(156, 163, 175, 0.05)" },
                        title: {
                            display: true,
                            text: "Date",
                            color: "#6B7280",
                        },
                    },
                },
                plugins: {
                    legend: {
                        labels: { color: "#6B7280" },
                        position: "bottom",
                    },
                    tooltip: {
                        backgroundColor: "#1f2937",
                        titleColor: "#ffffff",
                        bodyColor: "#d1d5db",
                        cornerRadius: 8,
                        displayColors: false,
                    },
                },
            },
        });
        console.log("✅ Customer chart initialized");
    } catch (error) {
        console.error("❌ Customer chart initialization failed:", error);
        showChartNoData("customerChart", "customerChartLoading");
    }
}

function initAdsChart(dates = [], fromAdsData = [], nonAdsData = []) {
    const canvas = document.getElementById("adsChart");
    const loading = document.getElementById("adsChartLoading");

    if (!canvas) return;

    try {
        const ctx = canvas.getContext("2d");

        // Destroy existing chart
        if (_adsChartInstance) {
            _adsChartInstance.destroy();
            _adsChartInstance = null;
        }

        // Check for valid data
        const hasValidData = dates.length > 0 && (fromAdsData.length > 0 || nonAdsData.length > 0);

        if (!hasValidData) {
            showChartNoData("adsChart", "adsChartLoading");
            return;
        }

        // Hide loading, show canvas
        canvas.style.display = "block";
        if (loading) loading.classList.add("hidden");

        // Create chart with both datasets
        _adsChartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: dates,
                datasets: [
                    {
                        label: "From Ads",
                        data: fromAdsData,
                        fill: true,
                        backgroundColor: "rgba(249, 115, 22, 0.1)",
                        borderColor: "rgba(249, 115, 22, 1)",
                        tension: 0.4,
                        pointBackgroundColor: "rgba(249, 115, 22, 1)",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: "Non Ads",
                        data: nonAdsData,
                        fill: true,
                        backgroundColor: "rgba(107, 114, 128, 0.1)",
                        borderColor: "rgba(107, 114, 128, 1)",
                        tension: 0.4,
                        pointBackgroundColor: "rgba(107, 114, 128, 1)",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: "index" },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: "#9CA3AF" },
                        grid: { color: "rgba(156, 163, 175, 0.1)" },
                        title: {
                            display: true,
                            text: "Number of Customers",
                            color: "#6B7280",
                        },
                    },
                    x: {
                        ticks: { color: "#9CA3AF", maxRotation: 0 },
                        grid: { color: "rgba(156, 163, 175, 0.05)" },
                        title: {
                            display: true,
                            text: "Date",
                            color: "#6B7280",
                        },
                    },
                },
                plugins: {
                    legend: {
                        labels: { color: "#6B7280" },
                        position: "bottom",
                    },
                    tooltip: {
                        backgroundColor: "#1f2937",
                        titleColor: "#ffffff",
                        bodyColor: "#d1d5db",
                        cornerRadius: 8,
                        displayColors: false,
                    },
                },
            },
        });
        console.log("✅ From Ads chart initialized with both datasets");
    } catch (error) {
        console.error("❌ From Ads chart initialization failed:", error);
        showChartNoData("adsChart", "adsChartLoading");
    }
}

function showChartNoData(canvasId, loadingId) {
    const canvas = document.getElementById(canvasId);
    const loading = document.getElementById(loadingId);

    if (canvas) canvas.style.display = "none";
    if (loading) {
        loading.classList.remove("hidden");
        loading.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-chart-line text-4xl mb-4 opacity-30"></i>
                <p class="text-lg font-medium">No chart data available</p>
                <p class="text-sm">Try adjusting your date filters or check back later</p>
            </div>
        `;
    }
}

// Save and load filter from localStorage
function saveFilterToLocal(key, value) {
    localStorage.setItem(key, value);
}

function loadFilterFromLocal(key, inputSelector) {
    const value = localStorage.getItem(key);
    if (value) {
        const input = document.querySelector(inputSelector);
        if (input) input.value = value;
    }
}

// Auto-refresh date system
function initAutoRefreshDates() {
    // Set default date ranges if not already set
    setDefaultDateRanges();

    // Auto-update dates every hour
    setInterval(updateAllDateFilters, 60 * 60 * 1000); // 1 hour

    // Update dates when page becomes visible
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            updateAllDateFilters();
        }
    });
}

function setDefaultDateRanges() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    const todayStr = today.toISOString().split('T')[0];
    const thirtyDaysAgoStr = thirtyDaysAgo.toISOString().split('T')[0];

    // Chart date filters
    const chartStartInput = document.querySelector('input[name="chart_start_date"]');
    const chartEndInput = document.querySelector('input[name="chart_end_date"]');

    if (chartStartInput && !chartStartInput.value) {
        chartStartInput.value = thirtyDaysAgoStr;
        saveFilterToLocal('chart_start_date', thirtyDaysAgoStr);
    }
    if (chartEndInput && !chartEndInput.value) {
        chartEndInput.value = todayStr;
        saveFilterToLocal('chart_end_date', todayStr);
    }

    // Customer report date filters
    const secondStartInput = document.querySelector('input[name="second_start_date"]');
    const secondEndInput = document.querySelector('input[name="second_end_date"]');

    if (secondStartInput && !secondStartInput.value) {
        secondStartInput.value = thirtyDaysAgoStr;
        saveFilterToLocal('second_start_date', thirtyDaysAgoStr);
    }
    if (secondEndInput && !secondEndInput.value) {
        secondEndInput.value = todayStr;
        saveFilterToLocal('second_end_date', todayStr);
    }

    // CSAT date filters
    const csatStartInput = document.getElementById('csatStartDate');
    const csatEndInput = document.getElementById('csatEndDate');

    if (csatStartInput && !csatStartInput.value) {
        csatStartInput.value = thirtyDaysAgoStr;
        saveFilterToLocal('csat_start_date', thirtyDaysAgoStr);
    }
    if (csatEndInput && !csatEndInput.value) {
        csatEndInput.value = todayStr;
        saveFilterToLocal('csat_end_date', todayStr);
    }

    // Customer analysis date filters
    const caDateFromInput = document.querySelector('input[name="ca_date_from"]');
    const caDateToInput = document.querySelector('input[name="ca_date_to"]');

    if (caDateFromInput && !caDateFromInput.value) {
        caDateFromInput.value = thirtyDaysAgoStr;
    }
    if (caDateToInput && !caDateToInput.value) {
        caDateToInput.value = todayStr;
    }

    // Dashboard export date filters
    const dateFromInput = document.querySelector('input[name="date_from"]');
    const dateToInput = document.querySelector('input[name="date_to"]');

    if (dateFromInput && !dateFromInput.value) {
        dateFromInput.value = thirtyDaysAgoStr;
    }
    if (dateToInput && !dateToInput.value) {
        dateToInput.value = todayStr;
    }

    // Agent status date filter
    const statusDateInput = document.getElementById('status_date');
    if (statusDateInput && !statusDateInput.value) {
        statusDateInput.value = todayStr;
    }
}

function updateAllDateFilters() {
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    // Update end dates to today
    const endDateInputs = [
        document.querySelector('input[name="chart_end_date"]'),
        document.querySelector('input[name="second_end_date"]'),
        document.getElementById('csatEndDate'),
        document.querySelector('input[name="ca_date_to"]'),
        document.querySelector('input[name="date_to"]')
    ];

    endDateInputs.forEach(input => {
        if (input) {
            input.setAttribute('max', todayStr);
            // Only update value if it's in the future
            if (input.value && input.value > todayStr) {
                input.value = todayStr;
                // Save to localStorage if it has a corresponding key
                if (input.name === 'chart_end_date') saveFilterToLocal('chart_end_date', todayStr);
                if (input.name === 'second_end_date') saveFilterToLocal('second_end_date', todayStr);
                if (input.id === 'csatEndDate') saveFilterToLocal('csat_end_date', todayStr);
            }
        }
    });

    // Update status date to today if it's in the future
    const statusDateInput = document.getElementById('status_date');
    if (statusDateInput) {
        statusDateInput.setAttribute('max', todayStr);
        if (statusDateInput.value && statusDateInput.value > todayStr) {
            statusDateInput.value = todayStr;
        }
    }

    // Update all date inputs max attribute
    const allDateInputs = document.querySelectorAll('input[type="date"]');
    allDateInputs.forEach(input => {
        input.setAttribute('max', todayStr);
    });

    console.log('📅 Date filters updated to current date:', todayStr);
}

// Abortable, timeout-guarded fetch for Chat Activity Trends only
async function fetchChatTrend(startDate, endDate) {
    let timeoutId;
    try {
        try {
            _chatAbort?.abort();
        } catch (_) {}
        _chatAbort = new AbortController();

        const url = new URL(
            "/admin/dashboard/chart-data",
            window.location.origin
        );
        if (startDate) url.searchParams.set("chart_start_date", startDate);
        if (endDate) url.searchParams.set("chart_end_date", endDate);

        timeoutId = setTimeout(() => {
            try {
                _chatAbort.abort();
            } catch (_) {}
        }, 20000);

        const res = await fetch(url.toString(), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: _chatAbort.signal,
        });
        if (!res.ok) throw new Error(`Bad response: ${res.status}`);
        const data = await res.json();
        window.chartLabels = data.chartLabels || data.labels || [];
        window.customerData = data.customerData || data.customers || [];
        window.csData = data.csData || data.csat || [];
        initChatTrendChart(
            window.chartLabels,
            window.customerData,
            window.csData
        );
    } catch (e) {
        if (e?.name === "AbortError") {
            console.warn("fetchChatTrend aborted");
        } else {
            console.error("fetchChatTrend failed", e);
            showChartNoData("chatTrendChart", "chartLoading");
        }
    } finally {
        if (timeoutId) clearTimeout(timeoutId);
        isReloading = false;
        try {
            document.getElementById("chartLoading")?.classList.add("hidden");
            // KEMBALIKAN 3 BARIS INI
            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }
        } catch (_) {}
    }
}

async function fetchCustomerReportData(startDate, endDate) {
    let timeoutId;
    try {
        try {
            _custAbort?.abort();
        } catch (_) {}
        _custAbort = new AbortController();

        const url = new URL(
            "/admin/dashboard/customer-report-data",
            window.location.origin
        );
        url.searchParams.set("second_start_date", startDate);
        url.searchParams.set("second_end_date", endDate);

        timeoutId = setTimeout(() => {
            try {
                _custAbort.abort();
            } catch (_) {}
        }, 20000);

        const res = await fetch(url.toString(), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: _custAbort.signal,
        });
        if (!res.ok) throw new Error(`Bad response: ${res.status}`);
        const data = await res.json();

        window.customerChartDates = data.customerChartDates || [];
        window.customerChartNewCustomers = data.customerChartNewCustomers || [];
        window.customerChartExistingCustomers =
            data.customerChartExistingCustomers || [];

        const newEl = document.querySelector("[data-new-cust]");
        const existEl = document.querySelector("[data-existing-cust]");
        if (newEl && typeof data.newCustomer === "number")
            newEl.textContent = new Intl.NumberFormat().format(
                data.newCustomer
            );
        if (existEl && typeof data.existingCustomer === "number")
            existEl.textContent = new Intl.NumberFormat().format(
                data.existingCustomer
            );

        initCustomerChart(
            window.customerChartDates,
            window.customerChartNewCustomers,
            window.customerChartExistingCustomers
        );
    } catch (e) {
        if (e?.name === "AbortError") {
            console.warn("fetchCustomerReportData aborted");
        } else {
            console.error("Customer report load failed:", e);
            showChartNoData("customerChart", "customerChartLoading");
        }
    } finally {
        if (timeoutId) clearTimeout(timeoutId);
        isReloading = false;
        try {
            document
                .getElementById("customerChartLoading")
                ?.classList.add("hidden");
            // KEMBALIKAN 3 BARIS INI
            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }
        } catch (_) {}
    }
}

async function fetchAdsReportData(startDate, endDate) {
    let timeoutId;
    try {
        try {
            _adsAbort?.abort();
        } catch (_) {}
        _adsAbort = new AbortController();

        const url = new URL(
            "/admin/dashboard/from-ads-data",
            window.location.origin
        );
        url.searchParams.set("ads_start_date", startDate);
        url.searchParams.set("ads_end_date", endDate);

        timeoutId = setTimeout(() => {
            try {
                _adsAbort.abort();
            } catch (_) {}
        }, 20000);

        const res = await fetch(url.toString(), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: _adsAbort.signal,
        });
        if (!res.ok) throw new Error(`Bad response: ${res.status}`);
        const data = await res.json();

        window.fromAdsChartDates = data.fromAdsChartDates || [];
        window.fromAdsChartData = data.fromAdsChartData || [];
        window.nonAdsChartData = data.nonAdsChartData || [];

        const fromAdsEl = document.querySelector("[data-from-ads]");
        const nonAdsEl = document.querySelector("[data-non-ads]");
        if (fromAdsEl && typeof data.totalFromAds === "number")
            fromAdsEl.textContent = new Intl.NumberFormat().format(
                data.totalFromAds
            );
        if (nonAdsEl && typeof data.totalNonAds === "number")
            nonAdsEl.textContent = new Intl.NumberFormat().format(
                data.totalNonAds
            );

        initAdsChart(
            window.fromAdsChartDates,
            window.fromAdsChartData,
            window.nonAdsChartData
        );
    } catch (e) {
        if (e?.name === "AbortError") {
            console.warn("fetchAdsReportData aborted");
        } else {
            console.error("Ads report load failed:", e);
            showChartNoData("adsChart", "adsChartLoading");
        }
    } finally {
        if (timeoutId) clearTimeout(timeoutId);
        isReloading = false;
        try {
            document
                .getElementById("adsChartLoading")
                ?.classList.add("hidden");
            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }
        } catch (_) {}
    }
}

function handleChartDateFilter() {
    const chartFilterForm = document.getElementById("chartDateFilterForm");
    if (chartFilterForm) {
        chartFilterForm.addEventListener("submit", function (e) {  // HAPUS async
            e.preventDefault();

            if (isReloading) return;
            isReloading = true;

            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }

            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Memuat Chart...', 'Sedang memperbarui grafik berdasarkan filter tanggal...');
            }

            const startDate = this.querySelector(
                'input[name="chart_start_date"]'
            ).value;
            const endDate = this.querySelector(
                'input[name="chart_end_date"]'
            ).value;
            const today = new Date().toISOString().split("T")[0];

            if (!startDate || !endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "⚠️ Tanggal Diperlukan!",
                    "Silakan pilih tanggal mulai dan tanggal akhir."
                );
                return;
            }

            if (startDate > endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
                );
                return;
            }

            if (startDate > today || endDate > today) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal tidak boleh melebihi hari ini."
                );
                return;
            }

            saveFilterToLocal("chart_start_date", startDate);
            saveFilterToLocal("chart_end_date", endDate);

            const url = new URL(window.location.href);
            url.searchParams.set("section", "analytics");
            url.searchParams.set("chart_start_date", startDate);
            url.searchParams.set("chart_end_date", endDate);
            history.pushState({ section: "analytics" }, "", url.toString());

            const chartLoading = document.getElementById("chartLoading");
            if (chartLoading) chartLoading.classList.remove("hidden");

            fetchChatTrend(startDate, endDate);  // HAPUS await

            // HAPUS bagian manual hide ini
        });
    }
}

function handleCustomerDateFilter() {
    const customerFilterForm = document.getElementById(
        "customerDateFilterForm"
    );
    if (customerFilterForm) {
        customerFilterForm.addEventListener("submit", function (e) {  // HAPUS async
            e.preventDefault();

            if (isReloading) return;
            isReloading = true;

            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }

            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Memuat Data Customer...', 'Sedang memperbarui data customer berdasarkan filter...');
            }

            const startDate = this.querySelector(
                'input[name="second_start_date"]'
            ).value;
            const endDate = this.querySelector(
                'input[name="second_end_date"]'
            ).value;
            const today = new Date().toISOString().split("T")[0];

            if (!startDate || !endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "⚠️ Tanggal Diperlukan!",
                    "Silakan pilih tanggal mulai dan tanggal akhir."
                );
                return;
            }

            if (startDate > endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
                );
                return;
            }

            if (startDate > today || endDate > today) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal tidak boleh melebihi hari ini."
                );
                return;
            }

            saveFilterToLocal("second_start_date", startDate);
            saveFilterToLocal("second_end_date", endDate);

            const url = new URL(window.location.href);
            url.searchParams.set("section", "analytics");
            url.searchParams.set("second_start_date", startDate);
            url.searchParams.set("second_end_date", endDate);
            history.pushState({ section: "analytics" }, "", url.toString());

            const customerChartLoading = document.getElementById(
                "customerChartLoading"
            );
            if (customerChartLoading)
                customerChartLoading.classList.remove("hidden");

            fetchCustomerReportData(startDate, endDate);  // HAPUS await

            // HAPUS bagian manual hide ini
        });
    }
}

function handleAdsDateFilter() {
    const adsFilterForm = document.getElementById("adsDateFilterForm");
    if (adsFilterForm) {
        adsFilterForm.addEventListener("submit", function (e) {
            e.preventDefault();

            if (isReloading) return;
            isReloading = true;

            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }

            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Memuat Data From Ads...', 'Sedang memperbarui data from ads berdasarkan filter...');
            }

            const startDate = this.querySelector(
                'input[name="ads_start_date"]'
            ).value;
            const endDate = this.querySelector(
                'input[name="ads_end_date"]'
            ).value;
            const today = new Date().toISOString().split("T")[0];

            if (!startDate || !endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "⚠️ Tanggal Diperlukan!",
                    "Silakan pilih tanggal mulai dan tanggal akhir."
                );
                return;
            }

            if (startDate > endDate) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
                );
                return;
            }

            if (startDate > today || endDate > today) {
                isReloading = false;
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal tidak boleh melebihi hari ini."
                );
                return;
            }

            saveFilterToLocal("ads_start_date", startDate);
            saveFilterToLocal("ads_end_date", endDate);

            const url = new URL(window.location.href);
            url.searchParams.set("section", "analytics");
            url.searchParams.set("ads_start_date", startDate);
            url.searchParams.set("ads_end_date", endDate);
            history.pushState({ section: "analytics" }, "", url.toString());

            const adsChartLoading = document.getElementById("adsChartLoading");
            if (adsChartLoading) adsChartLoading.classList.remove("hidden");

            fetchAdsReportData(startDate, endDate);
        });
    }
}

// Add this helper to fetch analytics data on demand
async function loadAnalyticsData() {
    if (isReloading) return; // Avoid double-fetches
    isReloading = true;

    try {
        // Grab current filter values from localStorage or defaults
        const chartStart =
            localStorage.getItem("chart_start_date") ||
            document.querySelector('input[name="chart_start_date"]')?.value ||
            "";
        const chartEnd =
            localStorage.getItem("chart_end_date") ||
            document.querySelector('input[name="chart_end_date"]')?.value ||
            "";
        const secondStart =
            localStorage.getItem("second_start_date") ||
            document.querySelector('input[name="second_start_date"]')?.value ||
            "";
        const secondEnd =
            localStorage.getItem("second_end_date") ||
            document.querySelector('input[name="second_end_date"]')?.value ||
            "";
        const csatSearch =
            document.getElementById("csatSearch")?.value ||
            localStorage.getItem("csat_search") ||
            "";
        const csatStart =
            document.getElementById("csatStartDate")?.value ||
            localStorage.getItem("csat_start_date") ||
            "";
        const csatEnd =
            document.getElementById("csatEndDate")?.value ||
            localStorage.getItem("csat_end_date") ||
            "";
        const csatPerPage =
            document.getElementById("csatPerPage")?.value ||
            localStorage.getItem("csat_per_page") ||
            "";
        const csatCsId =
            document.getElementById("csatCsSelect")?.value ||
            localStorage.getItem("csat_cs_id") ||
            "";

        const url = new URL(
            "/admin/dashboard/analytics-bundle",
            window.location.origin
        );
        if (chartStart) url.searchParams.set("chart_start_date", chartStart);
        if (chartEnd) url.searchParams.set("chart_end_date", chartEnd);
        if (secondStart) url.searchParams.set("second_start_date", secondStart);
        if (secondEnd) url.searchParams.set("second_end_date", secondEnd);
        if (csatSearch) url.searchParams.set("csat_search", csatSearch);
        if (csatStart) url.searchParams.set("csat_start_date", csatStart);
        if (csatEnd) url.searchParams.set("csat_end_date", csatEnd);
        if (csatPerPage) url.searchParams.set("csat_per_page", csatPerPage);
        if (csatCsId) url.searchParams.set("csat_cs_id", csatCsId);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000); // 20s safety timeout
        const response = await fetch(url.toString(), {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
            },
            signal: controller.signal,
        });
        clearTimeout(timeoutId);

        if (!response.ok) throw new Error("Failed to load analytics");

        const data = await response.json();
        if (!data.success) throw new Error(data.message || "No data");

        // Expose to window for chart init (mimic server-side)
        Object.assign(window, {
            chartLabels: data.data.chartLabels || [],
            customerData: data.data.customerData || [],
            csData: data.data.csData || [],
            customerChartDates: data.data.customerChartDates || [],
            customerChartNewCustomers:
                data.data.customerChartNewCustomers || [],
            customerChartExistingCustomers:
                data.data.customerChartExistingCustomers || [],
        });

        // Update the cards with fresh numbers (avoid zeroing on partial data)
        const monthlyActiveEl = document.querySelector("[data-mau]");
        const newCustEl = document.querySelector("[data-new-cust]");
        const existingCustEl = document.querySelector("[data-existing-cust]");
        if (monthlyActiveEl && typeof data.data.monthlyActiveUser === "number")
            monthlyActiveEl.textContent = data.data.monthlyActiveUser;
        if (newCustEl && typeof data.data.newCustomer === "number")
            newCustEl.textContent = data.data.newCustomer;
        if (existingCustEl && typeof data.data.existingCustomer === "number")
            existingCustEl.textContent = data.data.existingCustomer;

        // Update analytics top summary cards if present
        const totalMsgEl = document.querySelector(
            "#analyticsSection .grid div:nth-child(1) p.text-2xl"
        );
        if (totalMsgEl && typeof data.data.totalAllMessages === "number") {
            totalMsgEl.textContent = new Intl.NumberFormat().format(
                data.data.totalAllMessages
            );
        }
        const posNegContainers = document.querySelectorAll(
            "#analyticsSection .grid div:nth-child(2) .flex p"
        );
        if (posNegContainers.length >= 2) {
            if (typeof data.data.positivePercentage === "number")
                posNegContainers[0].textContent = `Positive: ${data.data.positivePercentage}%`;
            if (typeof data.data.negativePercentage === "number")
                posNegContainers[1].textContent = `Negative: ${data.data.negativePercentage}%`;
        }

        // Re-init charts now that data's fresh
        initializeAnalyticsCharts();

        // Save filters back to localStorage
        if (chartStart) localStorage.setItem("chart_start_date", chartStart);
        if (chartEnd) localStorage.setItem("chart_end_date", chartEnd);
        if (secondStart) localStorage.setItem("second_start_date", secondStart);
        if (secondEnd) localStorage.setItem("second_end_date", secondEnd);
        if (csatSearch) localStorage.setItem("csat_search", csatSearch);
        if (csatStart) localStorage.setItem("csat_start_date", csatStart);
        if (csatEnd) localStorage.setItem("csat_end_date", csatEnd);
        if (csatPerPage) localStorage.setItem("csat_per_page", csatPerPage);
        if (csatCsId) localStorage.setItem("csat_cs_id", csatCsId);

        console.log("✅ Analytics data loaded via AJAX");
    } catch (error) {
        console.error("❌ Analytics load failed:", error);
        // Do not wipe existing charts on failure; show a toast instead
        Swal.fire({
            title: "⚠️ Gagal Memuat Analytics",
            text: "Terjadi gangguan jaringan. Data sebelumnya tetap ditampilkan.",
            icon: "warning",
            timer: 2500,
            showConfirmButton: false,
            background: document.documentElement.classList.contains("dark")
                ? "#1f2937"
                : "#ffffff",
            color: document.documentElement.classList.contains("dark")
                ? "#f9fafb"
                : "#1f2937",
            toast: true,
            position: "top-end",
        });
    } finally {
        isReloading = false;
        // Always hide inline loading overlays and close SweetAlert if open
        try {
            document.getElementById("chartLoading")?.classList.add("hidden");
            document
                .getElementById("customerChartLoading")
                ?.classList.add("hidden");
            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }
        } catch (_) {}
    }
}

// Handle Customer Date Filter
function handleCustomerDateFilter() {
    const customerFilterForm = document.getElementById(
        "customerDateFilterForm"
    );
    if (customerFilterForm) {
        customerFilterForm.addEventListener("submit", function (e) {
            e.preventDefault();

            if (isReloading) return;
            isReloading = true;

            // Force close any existing alerts first
            if (window.LoadingAlerts) {
                window.LoadingAlerts.hide();
            }

            // Show loading alert
            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Memuat Data Customer...', 'Sedang memperbarui data customer berdasarkan filter...');
            }

            const startDate = this.querySelector(
                'input[name="second_start_date"]'
            ).value;
            const endDate = this.querySelector(
                'input[name="second_end_date"]'
            ).value;
            const today = new Date().toISOString().split("T")[0];

            // Validation
            if (!startDate || !endDate) {
                isReloading = false;
                showValidationAlert(
                    "⚠️ Tanggal Diperlukan!",
                    "Silakan pilih tanggal mulai dan tanggal akhir."
                );
                return;
            }

            if (startDate > endDate) {
                isReloading = false;
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
                );
                return;
            }

            if (startDate > today || endDate > today) {
                isReloading = false;
                showValidationAlert(
                    "❌ Tanggal Tidak Valid!",
                    "Tanggal tidak boleh melebihi hari ini."
                );
                return;
            }

            // Save filters
            saveFilterToLocal("second_start_date", startDate);
            saveFilterToLocal("second_end_date", endDate);

            // Update URL without full reload, then fetch analytics via AJAX
            const url = new URL(window.location.href);
            url.searchParams.set("section", "analytics");
            url.searchParams.set("second_start_date", startDate);
            url.searchParams.set("second_end_date", endDate);
            history.pushState({ section: "analytics" }, "", url.toString());
            const customerChartLoading = document.getElementById(
                "customerChartLoading"
            );
            if (customerChartLoading)
                customerChartLoading.classList.remove("hidden");
            // Fetch just the customer report, not the full bundle
            fetchCustomerReportData(startDate, endDate);
        });
    }
}

// Helper functions
function showValidationAlert(title, text) {
    Swal.fire({
        title: title,
        text: text,
        icon: "warning",
        confirmButtonText: "OK",
        confirmButtonColor: "#3085d6",
        background: document.documentElement.classList.contains("dark")
            ? "#1f2937"
            : "#ffffff",
        color: document.documentElement.classList.contains("dark")
            ? "#f9fafb"
            : "#1f2937",
    });
}

// Initialize section from URL
function initializeSectionFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    let section =
        urlParams.get("section") ||
        localStorage.getItem("current_section") ||
        "dashboard";
    const validSections = [
        "dashboard",
        "agents",
        "reviews",
        "analytics",
        "customer",
    ];
    const targetSection = validSections.includes(section)
        ? `${section}Section`
        : "dashboardSection";

    document.querySelectorAll(".content-section").forEach((section) => {
        section.classList.add("hidden");
    });

    const activeSection = document.getElementById(targetSection);
    if (activeSection) {
        activeSection.classList.remove("hidden");
    }

    document.querySelectorAll(".nav-item").forEach((item) => {
        if (item.getAttribute("data-target") === targetSection) {
            item.classList.add("active");
        } else {
            item.classList.remove("active");
        }
    });

    localStorage.setItem("current_section", section);
}

// Attach pagination listeners
function attachPaginationListeners() {
    const paginationLinks = document.querySelectorAll(
        '#reviewsSection a[href*="page="], #reviewsSection a[href*="per_page="]'
    );
    paginationLinks.forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            isReloading = true;
            const url = new URL(this.href);
            url.searchParams.set("section", "reviews");
            this.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.classList.add("opacity-50", "cursor-wait");
            window.location.href = url.toString();
        });
    });
}

// Card animation
function animateCards() {
    const cards = document.querySelectorAll(".animate-fade-in-up");
    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(10px)";
        setTimeout(() => {
            card.style.transition = "opacity 0.4s ease, transform 0.4s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, index * 100);
    });
}

// Navigation handling - Force reload for all sections
document.querySelectorAll(".nav-item").forEach((item) => {
    item.addEventListener("click", (e) => {
        if (item.dataset.external === "true") {
            return;
        }

        e.preventDefault();
        const targetId = item.getAttribute("data-target");
        const sectionName = targetId.replace("Section", "").toLowerCase();

        const sectionNames = {
            dashboard: 'Dashboard',
            agents: 'Data Agent',
            analytics: 'Analytics',
            customer: 'Data Customer'
        };

        // Force reload for ALL sections
        _isNavigating = true;

        // Show loading alert for navigation
        if (window.LoadingAlerts) {
            window.LoadingAlerts.show(
                `Memuat ${sectionNames[sectionName] || sectionName}...`,
                'Sedang berpindah ke halaman yang dipilih...'
            );
        }

        const url = new URL(window.location.href);
        url.searchParams.set("section", sectionName);

        console.log("🔄 Dashboard: Reloading to", sectionName);

        window.location.href = url.toString();
    });
});

// Session Management - Simplified to work with auto-logout.js
function initSessionManagement() {
    console.log("🔧 Dashboard: Session management delegated to auto-logout");

    // Set session active (will be managed by auto-logout.js)
    sessionStorage.setItem("sessionActive", "true");
    _isNavigating = false;

    // Handle visibility changes for session extension
    document.addEventListener("visibilitychange", handleVisibilityChange);
}

// Simplified session extension - delegate to auto-logout
function extendSession() {
    if (
        window.AutoLogout &&
        typeof window.AutoLogout.extendSession === "function"
    ) {
        window.AutoLogout.extendSession();
    }
}

function handleVisibilityChange() {
    if (
        !document.hidden &&
        window.AutoLogoutState &&
        !window.AutoLogoutState.isLoggingOut
    ) {
        extendSession();
    }
}

// Enhanced navigation detection
window.addEventListener("keydown", function (e) {
    if (
        e.key === "F5" ||
        (e.ctrlKey && e.key === "r") ||
        (e.metaKey && e.key === "r")
    ) {
        isReloading = true;
        sessionStorage.setItem("isReloading", "true");
    }
});

// Enhanced popstate handling
window.addEventListener("popstate", function () {
    initializeSectionFromURL();
    const analyticsSection = document.getElementById("analyticsSection");
    if (analyticsSection && !analyticsSection.classList.contains("hidden")) {
        setTimeout(() => initializeAnalyticsCharts(), 100);
    }
});

// Global functions used by HTML
window.changePerPage = function (perPage) {
    isReloading = true;

    // Show loading alert
    if (window.LoadingAlerts) {
        window.LoadingAlerts.show('Memperbarui Halaman...', `Sedang memuat ${perPage} data per halaman...`);
    }

    const url = new URL(window.location.href);
    url.searchParams.set("per_page", perPage);
    url.searchParams.delete("page");
    url.searchParams.set("section", "reviews");
    window.location.href = url.toString();
};

window.changeCSATPerPage = function () {
    const perPage = document.getElementById("csatPerPage")?.value || "10";

    // Show loading alert
    if (window.LoadingAlerts) {
        window.LoadingAlerts.show('Memperbarui Halaman CSAT...', `Sedang memuat ${perPage} data CSAT per halaman...`);
    }

    const url = new URL(window.location.href);
    url.searchParams.set("section", "analytics");
    url.searchParams.set("csat_per_page", perPage);
    url.searchParams.delete("csat_page");
    window.location.href = url.toString();
};

// Profile dropdown handling
const profileToggle = document.getElementById("profileToggle");
const profileDropdown = document.getElementById("profileDropdown");
const chevronIcon = document.getElementById("chevronIcon");

function openDropdown() {
    isDropdownOpen = true;
    if (profileDropdown) profileDropdown.classList.add("show");
    if (profileToggle) profileToggle.classList.add("active");
    if (chevronIcon) chevronIcon.classList.add("chevron-rotate");
}

function closeDropdown() {
    isDropdownOpen = false;
    if (profileDropdown) profileDropdown.classList.remove("show");
    if (profileToggle) profileToggle.classList.remove("active");
    if (chevronIcon) chevronIcon.classList.remove("chevron-rotate");
}

if (profileToggle) {
    profileToggle.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        isDropdownOpen ? closeDropdown() : openDropdown();
    });
}

document.addEventListener("click", (e) => {
    if (
        profileToggle &&
        profileDropdown &&
        !profileToggle.contains(e.target) &&
        !profileDropdown.contains(e.target)
    ) {
        if (isDropdownOpen) closeDropdown();
    }
});

// Sidebar handling
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");

function openSidebar() {
    isSidebarOpen = true;
    if (sidebar) sidebar.classList.add("open");
    if (sidebarOverlay) sidebarOverlay.classList.add("show");
    document.body.classList.add("overflow-hidden");
}

function closeSidebar() {
    isSidebarOpen = false;
    if (sidebar) sidebar.classList.remove("open");
    if (sidebarOverlay) sidebarOverlay.classList.remove("show");
    document.body.classList.remove("overflow-hidden");
}

if (sidebarToggle) {
    sidebarToggle.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        isSidebarOpen ? closeSidebar() : openSidebar();
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
}

// Dark mode toggle
const darkToggle = document.getElementById("darkToggle");
if (darkToggle) {
    darkToggle.addEventListener("click", () => {
        document.documentElement.classList.toggle("dark");
        const isDark = document.documentElement.classList.contains("dark");
        localStorage.setItem("theme", isDark ? "dark" : "light");
        darkToggle.style.transform = "scale(0.95)";
        setTimeout(() => {
            darkToggle.style.transform = "scale(1)";
        }, 150);
    });
}

// Keyboard event handling
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        if (isDropdownOpen) closeDropdown();
        if (isSidebarOpen) closeSidebar();
    }
});

// CSAT functionality
function filterCSATTable() {
    const search = document.getElementById("csatSearch")?.value || "";
    const startDate = document.getElementById("csatStartDate")?.value || "";
    const endDate = document.getElementById("csatEndDate")?.value || "";
    const perPage = document.getElementById("csatPerPage")?.value || "10";
    const csatCsId = document.getElementById("csatCsSelect")?.value || "";
    
    // Validasi tanggal
    const today = new Date().toISOString().split('T')[0];
    
    if (startDate && endDate) {
        if (startDate > endDate) {
            showValidationAlert(
                "❌ Tanggal Tidak Valid!",
                "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
            );
            return;
        }
        
        if (startDate > today || endDate > today) {
            showValidationAlert(
                "❌ Tanggal Tidak Valid!",
                "Tanggal tidak boleh melebihi hari ini."
            );
            return;
        }
    }
    
    isReloading = true;

    // Show loading alert
    if (window.LoadingAlerts) {
        window.LoadingAlerts.show('Memfilter Data Agent...', 'Sedang memuat data agent berdasarkan filter yang dipilih...');
    }

    console.log("Filtering with:", {
        search,
        startDate,
        endDate,
        perPage,
        csatCsId,
    });

    const url = new URL(window.location.href);
    url.searchParams.set("section", "agents");
    if (search) url.searchParams.set("csat_search", search);
    if (startDate) url.searchParams.set("csat_start_date", startDate);
    if (endDate) url.searchParams.set("csat_end_date", endDate);
    url.searchParams.set("csat_per_page", perPage);
    if (csatCsId) url.searchParams.set("csat_cs_id", csatCsId);
    url.searchParams.delete("csat_page");
    window.location.href = url.toString();
}

// Report dropdown functions
window.toggleReportDropdown = function () {
    const menu = document.getElementById("reportDropdownMenu");
    if (menu?.classList.contains("hidden")) {
        openReportDropdown();
    } else {
        closeReportDropdown();
    }
};

function openReportDropdown() {
    const menu = document.getElementById("reportDropdownMenu");
    const icon = document.getElementById("reportDropdownIcon");

    if (menu) {
        menu.classList.remove("hidden");
        setTimeout(() => {
            menu.classList.remove("opacity-0", "scale-95");
            menu.classList.add("opacity-100", "scale-100");
        }, 10);
    }
    if (icon) {
        icon.classList.add("rotate-180");
    }
}

function closeReportDropdown() {
    const menu = document.getElementById("reportDropdownMenu");
    const icon = document.getElementById("reportDropdownIcon");

    if (menu) {
        menu.classList.remove("opacity-100", "scale-100");
        menu.classList.add("opacity-0", "scale-95");
    }
    if (icon) {
        icon.classList.remove("rotate-180");
    }

    setTimeout(() => {
        if (menu) menu.classList.add("hidden");
    }, 200);
}

window.exportFilteredReport = async function () {
    const startDate = document.getElementById("csatStartDate")?.value;
    const endDate = document.getElementById("csatEndDate")?.value;
    const search = document.getElementById("csatSearch")?.value || "";
    const formatSelect = document.getElementById("csatExportFormat");
    const requestedFormat = (formatSelect?.value || "xlsx").toLowerCase();
    const csatCsId = document.getElementById("csatCsSelect")?.value || "";

    if (!startDate || !endDate) {
        showValidationAlert(
            "⚠️ Filter Tanggal Diperlukan!",
            "Silakan pilih tanggal mulai dan tanggal akhir terlebih dahulu sebelum export."
        );
        closeReportDropdown();
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        showValidationAlert(
            "❌ Tanggal Tidak Valid!",
            "Tanggal mulai tidak boleh lebih besar dari tanggal akhir."
        );
        closeReportDropdown();
        return;
    }

    const url = new URL("/admin/agent-csat/export", window.location.origin);
    url.searchParams.set("search", search);
    url.searchParams.set("date_from", startDate);
    url.searchParams.set("date_to", endDate);
    if (csatCsId) {
        url.searchParams.set("csat_cs_id", csatCsId);
    }
    if (requestedFormat !== "xlsx") {
        url.searchParams.set("format", requestedFormat);
    }

    Swal.fire({
        title: "📊 Mengexport Report...",
        text: "Mohon tunggu sebentar...",
        icon: "info",
        allowOutsideClick: false,
        showConfirmButton: false,
        background: document.documentElement.classList.contains("dark") ? "#1f2937" : "#ffffff",
        color: document.documentElement.classList.contains("dark") ? "#f9fafb" : "#1f2937",
        didOpen: () => Swal.showLoading(),
    });

    try {
        const response = await fetch(url, {
            method: "GET",
            headers: {
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
            },
        });

        if (!response.ok) {
            throw new Error(
                `Server returned ${response.status}: ${await response.text()}`
            );
        }

        const blob = await response.blob();
        const contentType = response.headers.get("Content-Type") || "";
        const disposition = response.headers.get("Content-Disposition") || "";
        let serverFileName = (() => {
            const match = disposition.match(/filename="?([^";]+)"?/i);
            return match ? match[1] : "";
        })();

        // Tentukan ekstensi berdasarkan MIME jika serverFileName kosong
        if (!serverFileName) {
            const agentSuffix = csatCsId ? `_${csatCsId}` : "";
            if (contentType.includes("openxmlformats"))
                serverFileName = `Agent_CSAT_Report${agentSuffix}_${startDate}_${endDate}.xlsx`;
            else if (contentType.includes("text/csv"))
                serverFileName = `Agent_CSAT_Report${agentSuffix}_${startDate}_${endDate}.csv`;
            else
                serverFileName = `Agent_CSAT_Report${agentSuffix}_${startDate}_${endDate}.xls`;
        } else {
            // Jika pengguna minta xlsx tapi server kirim HTML .xls (fallback), jangan pakai .xlsx agar tidak error di Excel
            if (
                requestedFormat === "xlsx" &&
                contentType.includes("ms-excel") &&
                serverFileName.endsWith(".xlsx")
            ) {
                serverFileName = serverFileName.replace(/\.xlsx$/i, ".xls");
            }
        }

        const downloadUrl = window.URL.createObjectURL(blob);
        const tempLink = document.createElement("a");
        tempLink.href = downloadUrl;
        tempLink.download = serverFileName;
        document.body.appendChild(tempLink);
        tempLink.click();
        document.body.removeChild(tempLink);
        window.URL.revokeObjectURL(downloadUrl);

        Swal.fire({
            title: "✅ Export Berhasil!",
            text: `Report berhasil diunduh (${serverFileName}).`,
            icon: "success",
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: "top-end"
        });
    } catch (error) {
        console.error("Export failed:", error);
        Swal.fire({
            title: "❌ Export Gagal!",
            text: "Terjadi kesalahan saat mengexport report. Silakan coba lagi.",
            icon: "error",
            confirmButtonText: "OK",
            confirmButtonColor: "#d33"
        });
    } finally {
        closeReportDropdown();
    }
};

// Import function
window.importCSATData = function (input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const allowedTypes = [
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "text/csv",
    ];

    if (
        !allowedTypes.includes(file.type) &&
        !file.name.match(/\.(xlsx|xls|csv)$/i)
    ) {
        showValidationAlert(
            "❌ File Tidak Valid!",
            "Silakan pilih file Excel (.xlsx, .xls) atau CSV (.csv)"
        );
        input.value = "";
        closeReportDropdown();
        return;
    }

    Swal.fire({
        title: "📤 Mengimport Data...",
        text: "Mohon tunggu, sedang memproses file...",
        icon: "info",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    const formData = new FormData();
    formData.append("import_file", file);

    fetch("/admin/agent/csat/import", {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                Swal.fire({
                    title: "✅ Import Berhasil!",
                    text: `${data.records_count} record berhasil diimport.`,
                    icon: "success",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#10b981"
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: "❌ Import Gagal!",
                    text: data.message || "Terjadi kesalahan saat mengimport data.",
                    icon: "error",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#d33"
                });
            }
        })
        .catch((error) => {
            console.error("Import error:", error);
            Swal.fire({
                title: "❌ Error!",
                text: "Terjadi kesalahan sistem. Silakan coba lagi.",
                icon: "error",
                confirmButtonText: "OK",
                confirmButtonColor: "#d33"
            });
        })
        .finally(() => {
            input.value = "";
            closeReportDropdown();
        });
};

// Event listeners for CSAT functionality
document.addEventListener("DOMContentLoaded", function () {
    const csatSearchInput = document.getElementById("csatSearch");
    if (csatSearchInput) {
        csatSearchInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                filterCSATTable();
            }
        });
    }

    // Close report dropdown when clicking outside
    document.addEventListener("click", function (e) {
        const dropdown = document.getElementById("reportDropdownMenu");
        const button = e.target.closest('[onclick="toggleReportDropdown()"]');

        if (dropdown && !button && !dropdown.contains(e.target)) {
            closeReportDropdown();
        }
    });
});

// Card hover effects
document.querySelectorAll(".card-hover").forEach((card) => {
    card.addEventListener("mouseenter", function () {
        this.style.transform = "translateY(-8px) scale(1.02)";
    });
    card.addEventListener("mouseleave", function () {
        this.style.transform = "translateY(0) scale(1)";
    });
});

// Load first response time data for agents section
async function loadFirstResponseTime() {
    // Use the dedicated handler for consistency
    if (window.FirstResponseTimeHandler) {
        return await window.FirstResponseTimeHandler.loadFirstResponseTime();
    }

    // Fallback if handler not loaded
    console.warn('FirstResponseTimeHandler not available, using fallback');
    try {
        const response = await fetch('/admin/dashboard/first-response-time', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) throw new Error('Failed to load first response time');

        const data = await response.json();

        // Update the card with the fetched data
        const avgFirstResponseTimeEl = document.getElementById('avgFirstResponseTime');
        if (avgFirstResponseTimeEl) {
            if (data.success && data.simple_format) {
                avgFirstResponseTimeEl.textContent = data.simple_format;
                console.log('✅ First response time loaded:', data.simple_format);
            } else if (data.avg_formatted) {
                avgFirstResponseTimeEl.textContent = data.avg_formatted;
                console.log('✅ First response time loaded:', data.avg_formatted);
            } else {
                avgFirstResponseTimeEl.textContent = '0m 0s';
                console.log('⚠️ No first response time data available');
            }
        }
    } catch (error) {
        console.error('❌ Failed to load first response time:', error);
        const avgFirstResponseTimeEl = document.getElementById('avgFirstResponseTime');
        if (avgFirstResponseTimeEl) {
            avgFirstResponseTimeEl.textContent = '0m 0s';
        }
    }
}

// Cleanup function - Updated to work with auto-logout
window.cleanup = function () {
    // Clean up dashboard-specific resources only
    if (_chatChartInstance) {
        _chatChartInstance.destroy();
        _chatChartInstance = null;
    }

    if (_customerChartInstance) {
        _customerChartInstance.destroy();
        _customerChartInstance = null;
    }

    if (_adsChartInstance) {
        _adsChartInstance.destroy();
        _adsChartInstance = null;
    }

    console.log("Dashboard cleanup completed");
};
