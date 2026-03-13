# Loading Alerts System

Sistem alert loading yang komprehensif untuk semua aksi filter di dashboard ResponiLy.

## Fitur

### ✅ Alert Loading Otomatis
- **Filter Tanggal**: Dashboard, Analytics, Customer
- **Navigasi Section**: Dashboard, Agent, Analytics, Customer  
- **Pagination**: Semua tabel dengan pagination
- **Export/Import**: Report CSAT, Message Log, Review Log
- **Per Page Selector**: Semua dropdown jumlah data per halaman
- **Search & Filter**: CSAT, Agent, Customer data

### ✅ Pesan Loading Spesifik
- Setiap aksi memiliki pesan loading yang sesuai konteks
- Mendukung tema dark/light mode
- Timeout otomatis 30 detik
- Auto-hide saat halaman selesai dimuat

### ✅ Integrasi Chatbot
- Loading saat mengirim pesan
- Loading saat test connection
- Loading saat clear chat history

## File yang Ditambahkan

### 1. `/public/js/loading-alerts.js`
Script utama sistem loading alerts dengan fungsi:
- `showLoadingAlert()` - Menampilkan alert loading
- `hideLoadingAlert()` - Menyembunyikan alert loading  
- `showSuccessAlert()` - Menampilkan alert sukses
- Auto-initialization untuk semua elemen filter

### 2. `/public/css/loading-alerts.css`
Styling untuk meningkatkan tampilan loading alerts:
- Animasi loading spinner
- Dark mode support
- Responsive design
- Accessibility improvements

## Implementasi

### Dashboard View
```html
<link rel="stylesheet" href="{{ asset('css/loading-alerts.css') }}">
<script src="{{ asset('js/loading-alerts.js') }}"></script>
```

### JavaScript Integration
```javascript
// Manual trigger
if (window.LoadingAlerts) {
    window.LoadingAlerts.show('Memuat Data...', 'Sedang memproses...');
}

// Auto-hide
if (window.LoadingAlerts) {
    window.LoadingAlerts.hide();
}
```

## Aksi yang Mendapat Loading Alert

### Dashboard Section
- ✅ Export Review Log / Message Log
- ✅ Filter tanggal review log
- ✅ Pagination review log
- ✅ Per page selector

### Agent Section  
- ✅ Filter tanggal status agent
- ✅ Search agent by name
- ✅ Pagination agent data
- ✅ Per page selector
- ✅ Filter CSAT table
- ✅ Export CSAT report
- ✅ Import CSAT data

### Analytics Section
- ✅ Filter tanggal chart activity
- ✅ Filter tanggal customer report
- ✅ Chart data loading
- ✅ Customer data loading

### Customer Section
- ✅ Filter tanggal conversation analysis
- ✅ Filter kategori & product
- ✅ Filter sentiment
- ✅ Export conversation analysis
- ✅ Pagination & per page

### Chatbot
- ✅ Send message loading
- ✅ Test connection loading  
- ✅ Clear chat loading

### Navigation  
- ✅ Section switching (Dashboard → Agent, Analytics, Customer)
- ✅ Pagination links
- ✅ AJAX navigation
- ✅ **CSAT conversion complete** - All loading optimized ✅

## Konfigurasi

### Timeout
Default: 30 detik (dapat diubah di `LOADING_CONFIG.timeout`)

### Posisi Alert
Default: center (dapat diubah di `LOADING_CONFIG.position`)

### Theme Support
Otomatis detect dark/light mode dari `document.documentElement.classList.contains('dark')`

## Browser Support
- ✅ Chrome 60+
- ✅ Firefox 55+  
- ✅ Safari 12+
- ✅ Edge 79+

## Accessibility
- ✅ Reduced motion support
- ✅ High contrast mode
- ✅ Screen reader friendly
- ✅ Keyboard navigation

## Performance
- Lightweight: ~8KB total (JS + CSS)
- No external dependencies (menggunakan SweetAlert2 yang sudah ada)
- Lazy loading untuk elemen yang tidak terpakai
- Memory efficient dengan cleanup otomatis