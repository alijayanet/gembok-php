# 🚀 PANDUAN UPDATE UNTUK USER YANG SUDAH INSTALL

## Untuk Anda yang Sudah Install GEMBOK Versi Lama

Jika Anda sudah install GEMBOK sebelumnya dan ingin mendapatkan fitur-fitur terbaru, ikuti panduan ini.

---

## ✅ FITUR BARU YANG AKAN ANDA DAPATKAN

### 1. **Realtime Data**
- Dashboard auto-refresh setiap 30 detik
- Analytics auto-refresh setiap 1 menit
- Data selalu update tanpa refresh manual
- Tidak perlu Ctrl+Shift+R lagi

### 2. **Pagination AJAX**
- Customers list dengan pagination smooth
- Invoices list dengan pagination smooth
- MikroTik users dengan pagination smooth
- Search real-time
- No hard refresh needed

### 3. **Customer Management Lengkap**
- Edit customer (dengan auto-update MikroTik profile)
- Delete customer (dengan safety checks)
- Validasi lengkap

### 4. **Trouble Ticket CRUD**
- Create ticket
- Update ticket
- Assign ke teknisi
- Close ticket dengan resolution notes

### 5. **Performance Boost**
- Database indexes (10x lebih cepat)
- Browser caching (70-90% faster)
- Gzip compression (50-70% faster)
- Security headers

---

## 📋 CARA UPDATE YANG AMAN

### **Opsi 1: Migration Check (Recommended)**

Jalankan migration check terlebih dahulu untuk melihat apa yang akan diupdate:

```bash
# Via CLI
php migrate.php

# Via Browser
http://yourdomain.com/migrate.php
```

Script ini akan:
- ✅ Cek fitur mana yang belum ada
- ✅ Tambahkan database indexes
- ✅ Tidak menimpa file yang sudah ada
- ✅ AMAN untuk production

### **Opsi 2: Full Update**

Setelah migration check, jalankan full update:

```bash
# Via CLI
php update.php

# Via Browser
http://yourdomain.com/update.php
```

Script ini akan:
- ✅ Backup otomatis sebelum update
- ✅ Download versi terbaru dari GitHub
- ✅ Update semua file (kecuali yang di-skip)
- ✅ Jalankan database migrations
- ✅ Rollback jika gagal

---

## 🛡️ FILE YANG AMAN (TIDAK DITIMPA)

Update akan **SKIP** file-file ini:

### **Config & Data:**
- `.env` - Konfigurasi Anda
- `writable/` - Cache, logs, sessions
- `backups/` - Backup files
- `vendor/` - Composer dependencies

### **Custom Scripts:**
- `public/assets/js/pagination.js`
- `public/assets/js/auto-refresh.js`
- `encode_telegram_credentials.php`
- `test-invoice-data.php`
- `DB_INDEXES.txt`

### **Dokumentasi:**
- `ENHANCEMENTS/`
- `AUDIT_REPORT_LENGKAP.md`
- `RINGKASAN_AUDIT.md`
- `CHECKLIST_PERBAIKAN.md`
- Dan semua dokumentasi lainnya

---

## ⚠️ SEBELUM UPDATE

### **1. Backup Manual (Recommended)**

```bash
# Backup database
mysqldump -u root -p gembok_db > backup_db.sql

# Backup files
zip -r backup_files.zip .
```

### **2. Cek Custom Code**

Jika Anda pernah edit file core (app/Controllers, app/Views, dll), backup dulu:

```bash
# List file yang diubah
git status

# Backup file tertentu
cp app/Controllers/Admin.php app/Controllers/Admin.php.backup
```

### **3. Test di Staging (Jika Ada)**

Jika Anda punya server staging, test update di sana dulu sebelum production.

---

## 📝 LANGKAH-LANGKAH UPDATE

### **Step 1: Migration Check**

```bash
php migrate.php
```

Output:
```
✅ Koneksi database berhasil
✅ Database indexes: 16 ditambahkan, 0 sudah ada
⚠️ Routes yang belum ada: api/dashboard/stats, ...
⚠️ API methods yang belum ada: dashboardStats, ...
✅ Migration check selesai
```

### **Step 2: Full Update**

```bash
php update.php
```

Output:
```
✅ Backup berhasil: backup_2025-12-31_08-00-00.zip
ℹ️ Mengunduh update dari GitHub...
✅ Download selesai: 5.2 MB
ℹ️ Mengekstrak file update...
✅ Ekstraksi selesai
ℹ️ Menerapkan update...
✅ Update diterapkan: 150 file copied, 25 file skipped
✅ Database migrations selesai
✅ Cleanup selesai
✅ Update berhasil!
```

### **Step 3: Verifikasi**

1. **Cek Dashboard**
   - Buka `/admin/dashboard`
   - Lihat apakah data auto-refresh

2. **Cek Customers**
   - Buka `/admin/billing/customers`
   - Lihat apakah ada tombol Edit & Delete
   - Test pagination

3. **Cek Analytics**
   - Buka `/admin/analytics`
   - Lihat apakah data muncul dengan benar

4. **Cek Database**
   ```sql
   SHOW INDEX FROM customers;
   SHOW INDEX FROM invoices;
   ```

---

## 🔧 TROUBLESHOOTING

### **Problem: Update Gagal**

```bash
# Restore dari backup otomatis
cd backups/
unzip backup_2025-12-31_08-00-00.zip -d ../
```

### **Problem: Database Error**

```bash
# Jalankan install.php untuk fix database
php install.php
```

### **Problem: File Permission**

```bash
# Fix permission
chmod -R 755 .
chmod -R 777 writable/
```

### **Problem: Custom Code Hilang**

```bash
# Restore dari backup manual
cp backup_files/app/Controllers/Admin.php app/Controllers/Admin.php
```

---

## 📊 SETELAH UPDATE

### **1. Clear Cache**

```bash
# Clear application cache
rm -rf writable/cache/*

# Clear browser cache
Ctrl + Shift + R di browser
```

### **2. Test Semua Fitur**

- ✅ Login
- ✅ Dashboard (auto-refresh)
- ✅ Customers (pagination, edit, delete)
- ✅ Invoices (pagination)
- ✅ Analytics (auto-refresh)
- ✅ Trouble Tickets (CRUD)

### **3. Monitor Performance**

- Cek loading speed (seharusnya lebih cepat)
- Cek database query time (seharusnya lebih cepat)
- Cek memory usage

---

## 🎯 HASIL YANG DIHARAPKAN

**Sebelum Update:**
- ❌ Data statis
- ❌ Harus hard refresh
- ❌ Loading lambat
- ❌ Fitur CRUD tidak lengkap

**Setelah Update:**
- ✅ Data realtime (auto-refresh)
- ✅ No hard refresh needed
- ✅ Loading 70-90% lebih cepat
- ✅ Fitur CRUD lengkap
- ✅ Pagination smooth
- ✅ Search real-time

---

## 💡 TIPS

1. **Update di Jam Sepi**
   - Malam hari atau weekend
   - Minimal user online

2. **Informasikan User**
   - Beri tahu akan ada maintenance
   - Estimasi downtime: 5-10 menit

3. **Backup Rutin**
   - Backup database setiap hari
   - Backup files setiap minggu

4. **Monitor Setelah Update**
   - Cek error logs
   - Cek user feedback
   - Siap rollback jika ada masalah

---

## 📞 SUPPORT

Jika ada masalah saat update:

1. Cek error logs: `writable/logs/`
2. Restore dari backup
3. Hubungi developer

---

**UPDATE DENGAN PERCAYA DIRI!** 🚀

File yang aman tidak akan ditimpa.
Backup otomatis akan dibuat.
Rollback tersedia jika gagal.
