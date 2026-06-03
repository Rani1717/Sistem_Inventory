📦 Sistem Pengelolaan & Monitoring IT Asset berbasis web menggunakan PHP MVC — kelola inventaris, laporan, dan keluhan IT dalam satu platform.

# 📦 SPMT IT Asset Management
Sistem Pengelolaan & Monitoring IT Asset berbasis web yang dibangun menggunakan arsitektur **PHP MVC (Model-View-Controller)**. Aplikasi ini dirancang untuk memudahkan pengelolaan aset IT, monitoring perangkat, serta penanganan keluhan secara terpusat.

## ✨ Fitur Utama

- 🖥️ **Inventaris Perangkat** — Kelola data inventaris PC dan perangkat IT lainnya
- 📋 **Log Barang** — Riwayat pergerakan dan perubahan aset
- 🔧 **Routine Monitoring** — Pemantauan kondisi perangkat secara berkala
- 📝 **Data Keluhan** — Pencatatan dan penanganan keluhan IT
- 📊 **Laporan** — Generate laporan aset dan monitoring
- 👥 **User Management** — Manajemen akun dan hak akses pengguna
- 🌐 **Public IT Support** — Halaman publik untuk pengajuan keluhan

## 🛠️ Tech Stack

- **Backend**: PHP (MVC Pattern)
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Server**: Apache

## 📁 Struktur Proyek
spmt_it_asset_management/
├── app/
│   ├── config/        # Konfigurasi database
│   ├── controllers/   # Logic controller
│   ├── models/        # Model & query database
│   └── views/         # Tampilan halaman
├── database/          # File SQL database
└── public/            # Assets (CSS, JS, uploads)

## 🚀 Cara Instalasi
1. Clone repository ini
```bash
   git clone https://github.com/Rani1717/Sistem_Inventory.git
```
2. Import file `database/db_spmt_subreg.sql` ke MySQL
3. Sesuaikan konfigurasi database di `app/config/database.php`
4. Jalankan di local server (XAMPP/Laragon)
5. Akses melalui browser
