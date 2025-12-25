# 🔐 GEMBOK - ISP Management System

![Version](https://img.shields.io/badge/version-1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.3.8-red.svg)
![License](https://img.shields.io/badge/license-Proprietary-orange.svg)

**GEMBOK** adalah aplikasi manajemen ISP (Internet Service Provider) berbasis web yang powerful dan user-friendly.

---

## ✨ **Fitur Utama**

### 🎛️ **Admin Panel**
- Dashboard dengan statistik real-time
- Manajemen pelanggan & paket internet
- Sistem billing & invoice otomatis
- Analytics & reports

### 📡 **Network Management**
- **MikroTik Integration** - PPPoE & Hotspot management (CRUD lengkap)
- **GenieACS Integration** - ONU/ONT monitoring & control
- **Map Visualization** - Lokasi pelanggan & perangkat

### 💳 **Payment & Billing**
- Auto-generate invoice bulanan
- Payment gateway integration (Tripay & Midtrans)
- Auto-isolasi pelanggan telat bayar
- Customer self-service portal

### 📱 **Notifications**
- WhatsApp notifications (Fonnte)
- Telegram bot integration
- Real-time alerts & reminders

### 🚀 **Auto-Update**
- 1-click update dari GitHub
- Backup otomatis sebelum update
- Rollback support jika gagal

---

## 📋 **Requirements**

- **PHP:** 8.0 atau lebih tinggi
- **Database:** MySQL 5.7+ atau MariaDB 10.3+
- **Web Server:** Apache dengan mod_rewrite
- **PHP Extensions:**
  - `mysqli`
  - `curl`
  - `zip`
  - `mbstring`
  - `json`

---

## 🔗 **Quick Links**

### **🌐 URL Akses Aplikasi**

| Halaman | URL | Keterangan |
|---------|-----|------------|
| **🏠 Homepage** | `http://yourdomain.com/` | Halaman utama |
| **👨‍💼 Admin Login** | `http://yourdomain.com/index.php/admin/login` | Login admin panel |
| **📊 Admin Dashboard** | `http://yourdomain.com/admin/dashboard` | Dashboard admin |
| **⚙️ Settings** | `http://yourdomain.com/admin/settings` | Pengaturan sistem |
| **👥 Customer Portal** | `http://yourdomain.com/login` | Login pelanggan |
| **🔧 Installer** | `http://yourdomain.com/gembok/install.php` | Setup database |
| **🔄 Auto-Update** | `http://yourdomain.com/gembok/update.php` | Update dari GitHub |

### **📡 Admin Panel Menu**

| Menu | URL | Fitur |
|------|-----|-------|
| **Dashboard** | `/admin/dashboard` | Statistik & overview |
| **Billing** | `/admin/billing` | Paket, pelanggan, invoice |
| **MikroTik** | `/admin/mikrotik` | PPPoE users management |
| **Hotspot** | `/admin/hotspot` | Hotspot users & voucher |
| **GenieACS** | `/admin/genieacs` | ONU/ONT monitoring |
| **Map** | `/admin/map` | Peta lokasi pelanggan |
| **Trouble Tickets** | `/admin/trouble` | Laporan gangguan |
| **Settings** | `/admin/settings` | Konfigurasi sistem |

### **🔐 Default Login**

**Admin Panel:**
```
URL: http://yourdomain.com/admin/login
Username: admin
Password: admin123

⚠️ WAJIB ganti password setelah login pertama!
```

**Customer Portal:**
```
URL: http://yourdomain.com/portal/login
Login: Nomor HP pelanggan
Password: Portal password (set oleh admin)
```

### **💝 Support & Donasi**

Jika aplikasi ini bermanfaat, dukung kami untuk terus berkembang:

- **📱 WhatsApp:** [0819-4721-5703](https://wa.me/6281947215703?text=Halo%2C%20saya%20ingin%20info%20tentang%20GEMBOK-PHP)
- **💰 Donasi:** [Chat via WhatsApp](https://wa.me/6281947215703?text=Halo%2C%20saya%20ingin%20donasi%20untuk%20GEMBOK-PHP)
- **🐛 Report Bug:** [GitHub Issues](https://github.com/alijayanet/gembok-php/issues)
- **⭐ Star Repo:** [GitHub](https://github.com/alijayanet/gembok-php)

---

## 🚀 **Instalasi**

### **1. Clone Repository**

```bash
git clone https://github.com/alijayanet/gembok-php.git
cd gembok-php
```

### **2. Install Dependencies**

```bash
composer install
```

### **3. Setup Environment**

```bash
# Copy .env example
cp env-contoh.txt .env

# Edit .env dengan kredensial Anda
nano .env
```

### **4. Setup Database**

Buka browser dan akses:
```
http://yourdomain.com/gembok/install.php
```

Atau via CLI:
```bash
php gembok/install.php
```

### **5. Login Admin**

```
URL: http://yourdomain.com/admin/login
Username: admin
Password: admin123
```

**⚠️ PENTING:** Ganti password default setelah login!

---

## 🔧 **Konfigurasi**

Edit file `.env` dengan kredensial Anda:

```env
# Database
DB_HOST=localhost
DB_DATABASE=gembok_db
DB_USERNAME=root
DB_PASSWORD=

# MikroTik
MIKROTIK_HOST=192.168.1.1
MIKROTIK_USER=admin
MIKROTIK_PASS=
MIKROTIK_PORT=8728

# GenieACS
GENIEACS_URL=http://localhost:7557

# Payment Gateway (Tripay)
TRIPAY_MERCHANT_CODE=
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=
TRIPAY_MODE=sandbox

# WhatsApp (Fonnte)
WHATSAPP_API_URL=
WHATSAPP_TOKEN=

# Telegram Bot
TELEGRAM_BOT_TOKEN=
TELEGRAM_ADMIN_CHAT_IDS=
```

**Atau edit via Web Admin:**
```
Menu: Settings → Edit semua konfigurasi via web interface
```

---

## 🔄 **Auto-Update**

Update aplikasi dengan mudah:

### **Via Browser:**
```
http://yourdomain.com/gembok/update.php
```

### **Via CLI:**
```bash
php gembok/update.php
```

**Fitur:**
- ✅ Backup otomatis sebelum update
- ✅ Download langsung dari GitHub
- ✅ Database migrations otomatis
- ✅ Rollback support

**Dokumentasi:** [AUTO_UPDATE_GUIDE.md](AUTO_UPDATE_GUIDE.md)

---

## 📚 **Dokumentasi**

| File | Deskripsi |
|------|-----------|
| [README.md](README.md) | Overview & quick start |
| [APLIKASI_OVERVIEW.md](APLIKASI_OVERVIEW.md) | Overview lengkap aplikasi |
| [TECHNICAL_SUMMARY.md](TECHNICAL_SUMMARY.md) | Dokumentasi teknis |
| [USER_GUIDE.md](USER_GUIDE.md) | Panduan pengguna |
| [AUTO_UPDATE_GUIDE.md](AUTO_UPDATE_GUIDE.md) | Panduan auto-update |

---

## 🎯 **Fitur Lengkap**

### **Admin Panel:**
- ✅ Dashboard dengan statistik real-time
- ✅ Manajemen pelanggan (CRUD + mapping)
- ✅ Manajemen paket internet
- ✅ Generate invoice bulanan
- ✅ Payment processing
- ✅ Auto-isolasi & unisolasi
- ✅ Analytics & reports
- ✅ Settings management (web-editable)

### **MikroTik Integration:**
- ✅ PPPoE Users (Add, Edit, Delete, Toggle)
- ✅ Hotspot Users (Add, Edit, Delete, Toggle)
- ✅ PPPoE Profiles (Add, Edit, Delete)
- ✅ Hotspot Profiles (Add, Edit, Delete)
- ✅ Voucher generator
- ✅ Active sessions monitoring

### **GenieACS Integration:**
- ✅ ONU/ONT device list
- ✅ Device details & monitoring
- ✅ Remote reboot
- ✅ WiFi SSID & password change
- ✅ Signal strength monitoring

### **Customer Portal:**
- ✅ Login dengan nomor HP
- ✅ Lihat tagihan & invoice
- ✅ Bayar online (Tripay)
- ✅ Ubah WiFi settings
- ✅ Ganti password portal
- ✅ Lapor gangguan (comming soon)

### **Payment Gateway:**
- ✅ Tripay integration
- ✅ Midtrans integration
- ✅ Webhook auto-processing
- ✅ Auto-unisolate setelah bayar

### **Notifications:**
- ✅ WhatsApp (invoice, payment, reminder)
- ✅ Telegram (admin notifications)
- ✅ Webhook logging

---

## 🛡️ **Keamanan**

- ✅ Password hashing (bcrypt)
- ✅ Input validation
- ✅ CSRF protection
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Webhook signature validation

---

## 📊 **Tech Stack**

- **Framework:** CodeIgniter 4.3.8
- **PHP:** 8.0+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML, CSS, JavaScript (Vanilla)
- **Map:** Leaflet.js
- **Dependencies:**
  - GuzzleHTTP (HTTP Client)
  - RouterOS API PHP (MikroTik)
  - Monolog (Logging)
  - PHP DotEnv (Environment)

---

## 🤝 **Contributing**

Kontribusi sangat diterima! Silakan:

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📄 **License**

Proprietary - All rights reserved.

---

## 📞 **Support & Contact**

### **💝 Donasi & Dukungan**

Jika aplikasi GEMBOK bermanfaat untuk bisnis Anda, dukung kami untuk terus berkembang:

**📱 WhatsApp: [0819-4721-5703](https://wa.me/6281947215703)**

- 💰 **Donasi:** [Klik untuk donasi via WhatsApp](https://wa.me/6281947215703?text=Halo%2C%20saya%20ingin%20donasi%20untuk%20GEMBOK-PHP)
- 💬 **Konsultasi:** [Chat untuk konsultasi](https://wa.me/6281947215703?text=Halo%2C%20saya%20ingin%20konsultasi%20tentang%20GEMBOK-PHP)
- 🐛 **Report Bug:** [Laporkan bug](https://wa.me/6281947215703?text=Halo%2C%20saya%20menemukan%20bug%20di%20GEMBOK-PHP)

### **🌐 Links**

- **GitHub Repository:** [https://github.com/alijayanet/gembok-php](https://github.com/alijayanet/gembok-php)
- **Issues & Bug Reports:** [https://github.com/alijayanet/gembok-php/issues](https://github.com/alijayanet/gembok-php/issues)
- **Documentation:** [README.md](README.md) | [User Guide](USER_GUIDE.md) | [Technical Docs](TECHNICAL_SUMMARY.md)

### **⭐ Star This Repo**

Jika aplikasi ini membantu Anda, jangan lupa untuk:
- ⭐ **Star** repository ini di GitHub
- 🍴 **Fork** untuk kontribusi
- 📢 **Share** ke teman-teman ISP lainnya

---

## 🎉 **Changelog**

### **Version 1.0.0** (2025-12-25)
- ✅ Initial release
- ✅ Full MikroTik integration (14 methods)
- ✅ GenieACS integration
- ✅ Payment gateway (Tripay & Midtrans)
- ✅ Customer portal
- ✅ Auto-update system
- ✅ Web-editable settings
- ✅ Comprehensive documentation

---

## 🙏 **Acknowledgments**

- **CodeIgniter Team** - Framework yang powerful
- **MikroTik** - RouterOS API
- **GenieACS** - TR-069 ACS
- **Tripay** - Payment gateway
- **Fonnte** - WhatsApp API
- **Leaflet** - Map visualization

---

## 📸 **Screenshots**

### Admin Dashboard
```
┌─────────────────────────────────────────┐
│  📊 Dashboard                           │
├─────────────────────────────────────────┤
│  💰 Revenue Today: Rp 1,500,000         │
│  📡 Total Devices: 245                  │
│  👥 PPPoE Online: 189/245               │
│  📶 Hotspot Online: 12                  │
│  🎫 Pending Tickets: 3                  │
└─────────────────────────────────────────┘
```

### Customer Portal
```
┌─────────────────────────────────────────┐
│  👤 Portal Pelanggan                    │
├─────────────────────────────────────────┤
│  📋 Tagihan: Rp 150,000                 │
│  📅 Jatuh Tempo: 20 Des 2025            │
│  [💳 Bayar Online]                      │
│                                         │
│  📶 WiFi: MyWiFi                        │
│  [✏️ Ubah WiFi]                         │
└─────────────────────────────────────────┘
```

---

**Made with ❤️ by Antigravity AI**

**⭐ Star this repo if you find it useful!**



