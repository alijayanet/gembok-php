# 🔒 GEMBOK APP - ISP Management System

**Gembok App** adalah aplikasi manajemen ISP (Internet Service Provider) berbasis web yang dibangun dengan **PHP (CodeIgniter 4)**. Aplikasi ini dirancang untuk memudahkan pengelolaan pelanggan PPPoE/Hotspot, tagihan otomatis, integrasi pembayaran online, dan monitoring perangkat via MikroTik & GenieACS.

🔗 **Repository:** [https://github.com/alijayanet/gembok-php](https://github.com/alijayanet/gembok-php)

---

## ✨ Fitur Utama
*   **Billing Otomatis:** Generate invoice bulanan & notifikasi WhatsApp otomatis.
*   **Integrasi MikroTik:** Isolir otomatis (tunggakan), sinkronisasi profil PPPoE & Hotspot.
*   **Payment Gateway:** Integrasi **Tripay** untuk pembayaran online (Virtual Account, QRIS, Alfamart).
*   **Customer Portal:** Halaman khusus pelanggan untuk cek tagihan, bayar, dan ganti password WiFi mandiri.
*   **Integrasi GenieACS:** Remote modem/ONU (Reboot, Ganti SSID/Pass) langsung dari admin.
*   **Manajemen ODP & Maps:** Pemetaan lokasi pelanggan dan ODP via Google Maps.

---

## 🌐 Link Akses Default
Setelah instalasi berhasil, berikut adalah link akses default:

| Halaman | URL Path | Login Default |
| :--- | :--- | :--- |
| **Admin Panel** | `/admin/login` | User: `admin` <br> Pass: `admin123` |
| **Portal Pelanggan** | `/login` | Login menggunakan No. HP Pelanggan |

> **Catatan:** Ganti `admin123` segera setelah login pertama!

---

## 🚀 Panduan Instalasi (Shared Hosting / VPS)

### 1. Persiapan File
1.  Download source code atau clone dari GitHub.
2.  Pastikan struktur folder siap upload. Folder utama aplikasi (`gembok/`) sebaiknya berada di luar `public_html` untuk keamanan, namun aplikasi ini sudah dikonfigurasi agar fleksibel.

### 2. Upload ke Hosting
1.  Buka File Manager di cPanel.
2.  Upload seluruh isi folder `gembok-production` ke `public_html`.
3.  Pastikan file `index.php` dan `.htaccess` berada tepat di dalam `public_html` (atau folder sub-domain Anda).
4.  Folder inti aplikasi (`gembok/`) berisi `app`, `vendor`, `writable`, `.env`.

### 3. Setup Database Otomatis
1.  Buat Database baru di cPanel (MySQL Database & User).
2.  Buka browser dan akses: `http://domain-anda.com/gembok/install.php`
3.  Isi form jika diminta, atau script akan mencoba koneksi `.env` default.
    *   *Jika error koneksi, lanjut ke langkah 4 dulu untuk edit .env, lalu refresh install.php.*
4.  Jika sukses, akan muncul pesan **"Installation Complete"** dan user admin dibuat.
5.  **PENTING:** Hapus file `gembok/install.php` setelah selesai demi keamanan!

### 4. Konfigurasi Environment (.env)
Edit file `gembok/.env` dan sesuaikan pengaturan berikut:

```ini
# --- DATABASE ---
database.default.hostname = localhost
database.default.database = nama_database_anda
database.default.username = user_database_anda
database.default.password = password_database_anda

# --- MIKROTIK ---
MIKROTIK_HOST = 192.168.x.x  (Gunakan IP Public atau VPN jika hosting di luar jaringan)
MIKROTIK_USER = admin
MIKROTIK_PASS = password_mikrotik

# --- TRIPAY (Pembayaran Online) ---
TRIPAY_API_KEY       = ambil_di_member_area_tripay
TRIPAY_PRIVATE_KEY   = ambil_di_member_area_tripay
TRIPAY_MERCHANT_CODE = kode_merchant_anda
TRIPAY_MODE          = production (atau sandbox untuk test)

# --- WHATSAPP (Opsional) ---
WHATSAPP_API_URL = https://api.wa-gateway-anda.com
WHATSAPP_TOKEN   = token_anda

# --- GENIEACS (Opsional) ---
GENIEACS_URL = http://ip_public_acs:7557
```

---

## ⏰ Setup Cron Job (Otomatisasi)
Agar fitur **Isolir Otomatis** dan **Generate Invoice** berjalan, Anda wajib memasang Cron Job di cPanel.

1.  Masuk ke cPanel > **Cron Jobs**.
2.  Set frekuensi: **Once per day** (00:00) atau (01:00).
3.  Isi Command:
    ```bash
    /usr/local/bin/php /home/username/public_html/gembok/cron_scheduler.php
    ```
    *(Sesuaikan path `/home/username/...` dengan path hosting Anda)*

---

## ❓ Troubleshooting
*   **Error 500 / Blank:** Cek file `index.php`, pastikan baris `ROOTPATH` sudah benar menunjuk ke folder `gembok`.
*   **MikroTik Tidak Konek:** Pastikan hosting Anda bisa memanggil IP MikroTik. Jika hosting cloud, gunakan IP Public / VPN / Tunnel.
*   **Tripay Callback Gagal:** Pastikan URL Callback di dashboard Tripay diisi: `https://domain-anda.com/webhook/payment`.

---
**Developed by Alijaya Network**
GitHub: [alijayanet/gembok-php](https://github.com/alijayanet/gembok-php)
