# SPMT IT Asset Management

Oleh : Putri Rossa Ananta - Pemagang (UDINUS 2026)

Sistem Informasi SPMT IT Asset Management adalah aplikasi berbasis web untuk membantu pengelolaan data inventaris IT, monitoring aset, pencatatan log barang, pengelolaan keluhan inventaris, routine monitoring, laporan, serta pengelolaan user dan divisi/database.

Aplikasi ini dibuat dengan arsitektur MVC menggunakan PHP, MySQL, HTML, CSS, dan JavaScript.

---

## Fitur Utama

### Dashboard Inventaris

Dashboard menampilkan ringkasan kondisi inventaris IT secara visual dan informatif.

Fitur dashboard meliputi:

- Ringkasan spesifikasi utama perangkat.
- Statistik keluhan inventaris berdasarkan bulan dan tahun berjalan.
- Statistik arus inventaris barang masuk dan barang keluar.
- Grafik jumlah CCTV lapangan.
- Notifikasi tiket IT Support baru.
- Informasi visual berbasis chart.
- Overlay rekap ketika card dashboard diklik.
- Rekap detail untuk:
  - System OS.
  - Microsoft Office.
  - Processor.
  - RAM / Harddisk.

Pada card tertentu seperti **MS Office**, sistem dapat menampilkan rekap:

- Total data.
- Licensed.
- Unlicensed.
- Data kosong/lainnya.
- Rekap nilai terbanyak.
- Rekap per divisi.

---

### Inventaris Baru

Modul Inventaris Baru digunakan untuk input data PC atau perangkat inventaris baru.

Fitur utama:

- Input data PC baru.
- Input perangkat lain.
- Upload foto/file aset.
- Preview foto menyesuaikan rasio gambar asli.
- Foto portrait dan landscape tidak dipaksa crop.
- Form responsive mengikuti ukuran device.
- Layout form tetap nyaman digunakan pada desktop, tablet, mobile, dan layar minimize.
- Data inventaris baru otomatis masuk ke halaman paling belakang pada Detail Inventaris.

Data yang dapat diinput meliputi:

- Nama user.
- Email user.
- Computer name.
- Processor.
- RAM.
- Harddisk.
- IP address.
- Sistem operasi.
- Lisensi OS.
- Microsoft Office.
- Lisensi Office.
- Foto aset.
- Perangkat tambahan.

---

### Data Inventaris

Modul Data Inventaris digunakan untuk menampilkan dan mengelola data aset IT berdasarkan divisi atau unit kerja.

Fitur utama:

- Menampilkan daftar aset berdasarkan divisi/database.
- Pencarian data inventaris.
- Pagination data inventaris.
- Detail data inventaris.
- Edit data inventaris PC.
- Tambah PC.
- Tambah perangkat lain.
- Upload dan preview foto aset.
- Sinkronisasi perubahan nama user.
- Sinkronisasi perpindahan divisi.
- Pindah 1 set PC dan perangkat terkait berdasarkan user yang sama.
- Data baru tampil di halaman paling belakang.
- Ketika menu Data Inventaris dibuka atau halaman direfresh, sistem kembali ke halaman pertama.
- Pengecualian: setelah tambah PC/perangkat baru, sistem diarahkan ke halaman paling belakang.

Informasi yang ditampilkan dapat mencakup:

- Nama pengguna perangkat.
- Email pengguna.
- Nama komputer.
- Processor.
- RAM.
- Harddisk.
- IP address.
- Sistem operasi.
- Lisensi OS.
- Microsoft Office.
- Lisensi Office.
- Perangkat tambahan lain.
- Foto aset.

---

### Detail Inventaris

Halaman Detail Inventaris menampilkan informasi aset secara lebih lengkap berdasarkan pengguna, divisi, atau perangkat yang dipilih.

Fitur ini membantu:

- Pengecekan kondisi aset.
- Validasi data perangkat.
- Pelacakan kepemilikan aset IT.
- Edit data PC.
- Tambah perangkat lain.
- Upload foto/file aset.
- Menampilkan foto sesuai rasio asli.
- Sinkronisasi user pada perangkat lain yang memiliki user sama.
- Pindah divisi untuk PC dan perangkat terkait berdasarkan user yang sama.

---

### Data Keluhan Inventaris / IT Support Issue

Modul Data Keluhan atau IT Support Issue digunakan untuk mencatat, memantau, dan memproses keluhan terkait perangkat inventaris IT.

Fitur utama:

- Menampilkan daftar keluhan.
- Filter berdasarkan status, divisi, tanggal, dan pencarian.
- Update status keluhan.
- Riwayat perubahan status.
- Penanggung jawab penanganan keluhan.
- Validasi proses penanganan.
- Export data keluhan.
- Notifikasi untuk tiket baru.
- Pengiriman email ke pelapor ketika sudah ada penanganan.
- Validasi email pelapor sebelum email dikirim.
- Status proses email ditampilkan di sistem.

Status keluhan yang digunakan:

- `NOT YET`
- `ON PROGRESS`
- `DONE`

Email penanganan dapat berisi:

- Nomor tiket.
- Status terbaru.
- Aset terkait.
- Lokasi.
- PIC.
- Catatan penanganan.

Jika server belum mendukung fungsi email, proses simpan tiket tetap berjalan dan status email dicatat oleh sistem.

---

### Notifikasi IT Support

Sistem notifikasi digunakan untuk menampilkan tiket IT Support yang masih baru atau belum dibaca.

Fitur notifikasi:

- Badge jumlah notifikasi aktif.
- Badge otomatis hilang jika jumlah notifikasi 0.
- Klik notifikasi membuka tiket terkait.
- Setelah notifikasi dibuka, jumlah notifikasi otomatis berkurang.
- Data notifikasi diperbarui secara berkala.

---

### Log Barang

Modul Log Barang digunakan untuk mencatat arus barang inventaris, baik barang masuk maupun barang keluar.

Fitur utama:

- Tambah log barang.
- Edit log barang.
- Hapus log barang.
- Filter berdasarkan tahun, bulan, tanggal, status, urutan, dan pencarian.
- Upload file PDF surat pemesanan.
- Export PDF.
- Export Excel.
- Statistik barang masuk dan barang keluar.
- Statistik default mengikuti bulan dan tahun berjalan.
- Tampilan form overlay responsive.

Status log barang:

- `MASUK`
- `KELUAR`

---

### Routine Monitoring

Modul Routine Monitoring digunakan untuk monitoring rutin perangkat atau area tertentu seperti GATE, CCTV, dan SERVER.

Fitur utama:

- Menu sidebar Routine Monitoring.
- Checklist monitoring berbasis kategori.
- List monitoring tampil dalam bentuk tabel matrix.
- Baris vertikal berisi list monitoring.
- Kolom horizontal berisi tanggal 1 sampai akhir bulan.
- Filter bulan dan tahun.
- Search live untuk mencari list monitoring.
- Tombol reset ke periode bulan dan tahun berjalan.
- Checklist kondisi per tanggal:
  - Baik
  - Kurang Baik
  - Buruk
- Tabel maksimal menampilkan beberapa row dan dapat scroll atas-bawah jika item banyak.
- Header tanggal tetap sticky/statis saat scroll.
- Simpan checklist per kategori.
- Rekap per hari.
- Rekap per minggu khusus PDF.
- Rekap per bulan khusus PDF.
- Download PDF rekap.
- Kelola list checking khusus admin.
- Tambah, edit, hapus kategori checking.
- Tambah, edit, hapus item checking.
- Data tersimpan ke database.
- Tampilan responsive untuk desktop, tablet, mobile, dan layar minimize.

---

### Kelola User

Fitur Kelola User digunakan oleh admin untuk melihat dan mengelola user yang terdaftar.

Fitur utama:

- Menampilkan user yang sudah terdaftar.
- Menampilkan role setiap user.
- Live search.
- Filter user.
- Tabel user dapat discroll.
- Tampilan responsive.
- Fitur hanya tersedia untuk admin tertentu.

Role yang digunakan:

- `admin.spmt`
- `operator`
- `user`

Hak akses role:

#### Role User

User hanya dapat mengakses:

- Inventaris Baru.
- Data Inventaris.
- Log Barang.

#### Role Operator

Operator dapat mengakses:

- Inventaris Baru.
- Data Inventaris.
- Log Barang.
- IT Support Request.

#### Role Admin SPMT

Admin SPMT dapat mengakses fitur pengelolaan penuh, termasuk:

- Kelola User.
- Kelola Divisi / Database.
- Routine Monitoring.
- Laporan.
- Dashboard.
- Data Inventaris.
- IT Support Issue.
- Log Barang.

---

### Kelola Divisi / Database

Fitur Kelola Divisi / Database digunakan admin untuk mengelola divisi dan database inventaris.

Fitur utama:

- Tambah divisi/database.
- Edit nama divisi.
- Edit nama database.
- Simpan perubahan.
- Nonaktifkan divisi.
- Aktifkan kembali divisi.
- Hapus divisi.
- Hapus database divisi beserta isinya.
- Tombol aksi berurutan:
  - Simpan
  - Nonaktif / Aktifkan
  - Hapus
- Konfirmasi sebelum hapus divisi.
- Overlay form responsive.
- Field edit divisi dibuat besar dan mudah digunakan.
- Tampilan tetap aman pada device kecil.

Jika divisi dihapus, sistem dapat menghapus:

- Data divisi di `master_divisi`.
- Relasi user-divisi jika tabel tersedia.
- Default divisi user jika kolom tersedia.
- Database inventaris divisi beserta seluruh isinya.

---

### Statistik Arus Inventaris

Statistik arus inventaris menampilkan jumlah barang masuk dan barang keluar dalam bentuk bar chart.

Pada dashboard dan halaman log barang, statistik dapat menyesuaikan periode bulan dan tahun berjalan atau periode filter yang dipilih.

---

### Statistik Keluhan Inventaris

Statistik keluhan inventaris menampilkan jumlah keluhan berdasarkan divisi dalam periode bulan dan tahun berjalan.

Data divisualisasikan menggunakan bar chart agar lebih mudah dibaca dan dibandingkan antar divisi.

---

### Jumlah CCTV Lapangan

Card Jumlah CCTV Lapangan menampilkan data CCTV dalam bentuk pie chart.

Fitur CCTV:

- Menampilkan total CCTV.
- Menampilkan pembagian jumlah CCTV berdasarkan lokasi.
- Tooltip chart menampilkan keterangan lokasi dan jumlah.
- Data CCTV dapat dikelola melalui modal dashboard.
- Penambahan data CCTV bersifat fleksibel dan langsung menyesuaikan chart.

---

### Laporan

Modul laporan digunakan untuk kebutuhan rekapitulasi data dan export sesuai filter tertentu.

Laporan dapat digunakan untuk kebutuhan dokumentasi, evaluasi, dan pelaporan internal.

Jenis laporan yang tersedia:

- Laporan Data Inventaris.
- Laporan IT Support Issue.
- Laporan Log Barang.
- Laporan Routine Monitoring.
- Laporan User.

Fitur laporan:

- View laporan.
- Export PDF.
- Export Excel.
- Filter sesuai jenis laporan.
- Card laporan lama tetap menggunakan filter masing-masing.
- Card laporan Routine Monitoring dan User memiliki tombol:
  - Export PDF
  - Export Excel
  - View
- Export dari card Routine Monitoring dan User menggunakan seluruh data.
- Filter Routine Monitoring dan User tersedia pada halaman View.

Filter Laporan Routine Monitoring:

- Bulan.
- Tahun.
- Semua bulan dalam tahun tertentu.

Filter Laporan User:

- Role.
- Divisi.

---

## Responsive Design

Aplikasi sudah disesuaikan agar dapat digunakan pada berbagai ukuran layar.

Dukungan responsive meliputi:

- Desktop.
- Laptop.
- Tablet.
- Mobile.
- Layar minimize.
- Tabel scroll horizontal dan vertikal.
- Modal/overlay menyesuaikan tinggi layar.
- Form menyesuaikan kolom berdasarkan ukuran device.
- Foto aset menyesuaikan rasio asli.
- Card dashboard dan laporan tetap rapi pada layar kecil.

---

## Struktur Folder

```text
spmt_mvc_fixed/
├── app/
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── PageController.php
│   │   └── PublicItSupportController.php
│   ├── models/
│   │   ├── AuthModel.php
│   │   ├── Database.php
│   │   ├── ItSupportPublicModel.php
│   │   └── UiModel.php
│   └── views/
│       ├── layouts/
│       ├── pages/
│       ├── partials/
│       └── public/
├── database/
│   └── db_spmt_subreg.sql
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/
├── index.php
└── it-support.php