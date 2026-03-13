## ResponiLy Chat & Analytics Dashboard

Dashboard Laravel 10 untuk monitoring chat, analitik response time, CSAT, dan integrasi ChatBot (n8n webhook). Ringkas, siap dikembangkan lanjut.

---

### Fitur Singkat

-   Auth (Login/Register + Google OAuth)
-   ChatBot (webhook + fallback analisa lokal sentimen)
-   Dashboard analitik (trend chat, response time, agent stats, sentiment/CSAT)
-   Export & Import laporan Agent CSAT (.xls HTML-compatible)

---

### Prasyarat

-   PHP 8.1+ & Composer
-   Node.js 18+ & npm
-   PostgrestSQL
-   (Opsional) URL webhook n8n untuk chatbot

---

### Variabel .env Penting

```
APP_NAME=ResponiLy
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=responily
DB_USERNAME=root
DB_PASSWORD=your_password

CHATBOT_WEBHOOK_URL=https://playground.bintang.ai/webhook-test/CS
CHATBOT_TIMEOUT=30
CHATBOT_RETRY_ATTEMPTS=3
CHATBOT_SSL_VERIFY=false

GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxx
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

### Langkah Cepat (Development)

Di PowerShell (Windows):

```powershell
cd dashboard
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
# Tambahkan/migrasikan tabel messages & satisfaction_ratings jika belum ada
php artisan migrate
php    # jalankan server backend (http://127.0.0.1:8000)
npm run dev         # di terminal lain untuk Vite (asset & HMR)
```

Akses: `http://localhost:8000` → halaman auth. Dashboard: `/admin/dashboard`.

---

### Build Production Asset

```powershell
npm run build
```

Letakkan hasil build (public/build) di environment produksi, gunakan web server (Nginx/Apache) menunjuk ke folder `public`.

---

### Endpoint API ChatBot (Ringkas)

Prefix: `/api/chatbot`

-   POST `/send-message` → kirim & dapat balasan
-   GET `/chat-history` → history (param: user_id, room_id)
-   GET `/test-connection` → cek DB + webhook
artisan serve-   POST `/test-n8n` → uji webhook n8n

Contoh kirim pesan:

```bash
curl -X POST http://localhost:8000/api/chatbot/send-message \
	-H "Content-Type: application/json" \
	-d '{"message":"Berapa sentimen positif hari ini?","user_id":"user_123","username":"Budi","room_id":"1"}'
```

---

### Export / Import CSAT

-   Export: GET `/admin/agent-csat/export`
-   Template: GET `/admin/agent-csat/template`
-   Import: POST `/admin/agent-csat/import` (form-data: `import_file` .xls/.xlsx/.csv)

---

### Troubleshooting Cepat

| Masalah              | Solusi Ringkas                                                     |
| -------------------- | ------------------------------------------------------------------ |
| Bot fallback terus   | Periksa `CHATBOT_WEBHOOK_URL`, coba `/api/chatbot/test-connection` |
| Import gagal         | Pastikan format tanggal `DD MMM YYYY` & waktu `HH:MM:SS`           |
| Google OAuth error   | Samakan redirect URL di Google Console & `.env`                    |
| Response time kosong | Pastikan role pesan CS = `customer_service` & ada timestamp        |

Log debugging: `storage/logs/laravel.log`.

---

### Pengembangan Lanjutan (Opsional)

-   Tambah indexing DB (role, timestamp, sender_id)
-   Integrasi broadcasting (live chat)
-   Ganti export/import ke PhpSpreadsheet
-   Tambah role-based authorization policy

---

Selamat menggunakan! 🎉
