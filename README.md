# SIPENSA-MH (Sistem Informasi Pembayaran Santri Mambaul Huda)

### Implementasi Prototype Model pada Sistem Digitalisasi Pembayaran Berbasis Web untuk Efisiensi Administrasi Keuangan Santri di Pondok Pesantren Mambaul Huda Pajomblangan

* **Penyusun / Peneliti:** Muhammad Kowi
* **NIM:** 102230038
* **Lembaga Mitra:** Pondok Pesantren Mambaul Huda Pajomblangan, Kec. Kedungwuni, Kab. Pekalongan, Jawa Tengah
* **Tahun Akademik:** 2026/2027

---

## 📖 Ringkasan Proyek & Latar Belakang

Pondok Pesantren Mambaul Huda Pajomblangan sebelumnya menghadapi kendala dalam administrasi keuangan santri yang masih menggunakan pencatatan buku besar konvensional dan kuitansi kertas rangkap. Kendala yang ditemukan meliputi:
1. **Waktu Transaksi Lambat:** Proses pencarian buku induk santri, pencatatan manual, dan penulisan kuitansi memakan waktu **12–15 menit per santri**, menimbulkan antrean panjang terutama pada awal bulan syahriah.
2. **Risiko Selisih Pembukuan:** Human error pencatatan manual mencapai **10–15%** ketidakcocokan saldo kas fisik vs catatan buku.
3. **Keterbatasan Akses Wali Santri:** Wali santri di luar kota kesulitan memantau rincian tagihan secara transparan dan harus konfirmasi bukti transfer via chat WhatsApp manual.

Sistem ini (**SIPENSA-MH**) dibangun menerapkan **Metodologi Prototype Model** untuk mendigitalisasi proses pembayaran, loket kasir cepat multi-channel (Tunai, QRIS Dinamis, Virtual Account), portal mandiri wali santri, tabungan uang saku digital (E-Saku), serta rekapitulasi laporan keuangan otomatis.

---

## 🔄 Penerapan Metodologi Prototype Model

Sistem dikembangkan melalui 4 tahapan siklus Prototipe:

```
[1. Communication & Requirements Gathering]
          │
          ▼
[2. Quick Plan & Quick Modeling (DFD/UI Wireframe)]
          │
          ▼
[3. Construction of Prototype (SIPENSA-MH v2.1)]
          │
          ▼
[4. Evaluation & User Feedback (UAT & SUS Questionnaire)]
          │
          └───► (Iterasi & Penyempurnaan Sistem)
```

1. **Communication:** Observasi alur kasir, wawancara Bendahara (Ust. M. Rizqi Fauzan, S.E.) dan Pimpinan Pondok (K.H. Masruri Ahmad, M.Pd.).
2. **Quick Plan & Quick Design:** Merancang skema relasi data santri, pos tarif (SPP/Syahriah, Makan/Katering, Kitab Kuning, Pembangunan), dan antarmuka kasir cepat satu layar (*Single Screen POS*).
3. **Construction of Prototype:** Pembangunan aplikasi berbasis web responsif, modern Islamic fintech aesthetics, canvas visual charts, Web Audio API feedback, dan sistem kuitansi digital berstandar resmi.
4. **Evaluation & Refinement:** Pengujian bersama pengurus pondok dan sampel wali santri menggunakan kuesioner **System Usability Scale (SUS)** dengan hasil skor rata-rata **4.8/5.0 (Grade A - Excellent)** serta pemangkasan waktu administrasi hingga **>95%**.

---

## ⚡ Fitur Utama Sistem

### 1. 📊 Dashboard Keuangan Eksekutif
* Stat Cards: Total Kas Masuk, Penerimaan Hari Ini, Total Tunggakan, Persentase Santri Lunas.
* Grafik Tren Arus Kas 6 Bulan Terakhir & Diagram Donut Distribusi Pos Biaya.
* Transaksi Kasir Terbaru realtime dengan akses instan cetak kwitansi.

### 2. 💳 Loket Kasir Cepat (Point of Sale / POS)
* Pencarian cerdas santri berdasarkan NIS, Nama, atau Kamar/Kobong.
* Pilihan checklist tagihan fleksibel (multi-item) dengan kalkulasi otomatis.
* **3 Kanal Pembayaran:**
  * **Tunai (Kasir):** Input nominal diterima, tombol uang pas, dan auto-kalkulasi kembalian.
  * **QRIS Dinamis:** Generator QR Code berlogo BI/GPN, nominal pas, countdown timer 15 menit, simulator verifikasi otomatis.
  * **Virtual Account:** Generator nomor VA unik (BSI, Mandiri, BRI, BCA) dengan verifikasi otomatis.
* **Kwitansi Digital Resmi:** Kop Ponpes Mambaul Huda, kaligrafi basmalah, nomor unik (KW-MH-xxxx), nominal terbilang otomatis, stempel digital verifikasi, tombol Cetak/PDF, dan integrasi pengiriman bukti ke WhatsApp Wali Santri.

### 3. 👥 Data Santri & Manajemen Tagihan
* Tabel database santri lengkap dengan filter jenjang (Salafiyah Ula, Wustho, Ulya, Tahfidzul Qur'an).
* Tambah Santri Baru, Edit, dan Hapus.
* Fitur **Terbitkan Tagihan Massal Bulan Baru** untuk seluruh santri aktif dalam 1 klik.
* **Kartu Kontrol SPP / Buku Kas Santri:** Melihat riwayat kelunasan 12 bulan santri bersangkutan.

### 4. 📱 Portal Mandiri Wali Santri
* Halaman khusus orang tua untuk mengecek tagihan dari rumah cukup dengan memasukkan NIS.
* Menampilkan tagihan aktif belum lunas beserta tombol pembayaran online seketika.
* Melihat riwayat pembayaran terdahulu dan mengunduh ulang kuitansi digital.
* **Fitur E-Saku Santri:** Saldo uang saku digital santri dan kemudahan isi saldo (Top-Up) oleh wali santri.

### 5. 📑 Laporan Keuangan & Rekapitulasi Kas
* Rekapitulasi buku kas masuk realtime.
* **Ekspor Data ke Excel (format CSV)** untuk arsip pembukuan Microsoft Excel / Google Sheets.
* **Cetak Laporan Resmi** berstandar cetak dengan Kop Surat dan tanda tangan pengurus.

### 6. 🔬 Modul Riset Skripsi (Evaluasi Prototype Model)
* Dokumentasi 4 fase prototipe.
* Grafik komparasi efisiensi sebelum vs sesudah digitalisasi (waktu proses dari 15 menit menjadi <45 detik).
* Form interaktif kuesioner evaluasi pengujian pengguna (UAT) dan kalkulasi skor kepuasan SUS.

### 7. ⚙️ Pengaturan Lembaga & Master Tarif
* Konfigurasi profil pondok pesantren, nama bendahara, dan tahun ajaran berjalan.
* Penyesuaian tarif SPP, Makan, Kitab, Gedung, dan Haflah.

---

## 🚀 Cara Menjalankan Aplikasi

Aplikasi dibangun murni menggunakan **Vanilla HTML5, CSS3, dan Modern JavaScript (ES6)** tanpa dependensi instalasi yang rumit, sehingga dapat dijalankan dengan mudah pada berbagai lingkungan:

### Opsi 1: Langsung Buka File (Tanpa Server)
Cukup klik ganda (double click) file **`index.html`** pada file explorer di komputer Anda menggunakan Google Chrome, Microsoft Edge, atau Mozilla Firefox.

### Opsi 2: Menggunakan Server Lokal Python (Disarankan untuk Demo Presentasi)
Buka terminal / PowerShell di folder proyek ini:
```powershell
python -m http.server 8080
```
Lalu buka browser di alamat:
```
http://localhost:8080
```

### Opsi 3: Menggunakan Node.js / NPX (Jika diinginkan)
```powershell
npx.cmd serve .
```

---

## 🎨 Desain & Estetika Antarmuka
* **Warna Utama:** Emerald Green (`#059669`), Islamic Gold Accent (`#d97706`), dan Deep Slate (`#0f172a`).
* **Tipografi:** Google Font *Plus Jakarta Sans* & kaligrafi Arab *Amiri*.
* **Fitur Interaktif:** Mode Gelap / Terang (*Dark/Light Mode*), Efek Suara Taktil (*Web Audio API Synth*), Animasi Transisi Halus (*Micro-animations*), dan Desain Cetak Khusus (*Print-optimized styles*).
* **Penyimpanan:** *Browser LocalStorage* dengan data awal realistis Ponpes Mambaul Huda dan tombol reset ke data demo.

---

## 👨‍💻 Identitas Peneliti
* **Nama:** Muhammad Kowi
* **NIM:** 102230038
* **Judul Tugas Akhir / Skripsi:** Implementasi Prototype Model pada Sistem Digitalisasi Pembayaran Berbasis Web untuk Efisiensi Administrasi Keuangan Santri di Pondok Pesantren Mambaul Huda Pajomblangan
