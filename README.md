# Sistem Digitalisasi Pembayaran SPP Santri

**Stack:** PHP 8+, MySQL/MariaDB, PDO, HTML/CSS/JavaScript.  
**Tema:** modern, putih dan hijau.

## Fitur

- Login dengan peran admin dan santri.
- Dashboard ringkasan.
- Admin: kelola data santri, nominal SPP bulanan, kategori jenjang dan nominalnya.
- Admin: melihat pembayaran, menyetujui/menolak pembayaran tunai atau transfer yang melampirkan bukti.
- Santri: melihat bulan tertunggak, memilih bulan SPP yang ingin dibayar, memilih kategori jenjang.
- Metode: tunai di bagian akademik (unggah bukti), transfer bank (unggah bukti), Midtrans (integrasi server-side dan notifikasi/webhook).
- Riwayat pembayaran dan status pembayaran.

## Persyaratan

- PHP 8.0+ dengan ekstensi `pdo_mysql`, `fileinfo`.
- MySQL 5.7+/MariaDB.
- XAMPP/WAMP/LAMP.

## Instalasi lokal (XAMPP)

1. Ekstrak folder `digitalisasi-spp-pesantren` ke `C:\xampp\htdocs\`.
2. Buat database bernama `pesantren_spp`.
3. Import `database.sql` melalui phpMyAdmin.
4. Buka `config.php`, sesuaikan `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Salin `uploads/.htaccess.example` menjadi `uploads/.htaccess` (Apache). Folder `uploads` harus dapat ditulis oleh PHP.
6. Buka `http://localhost/digitalisasi-spp-pesantren/`.
7. Akun awal:
   - Admin: `admin` / `password`
   - Santri demo: `santri001` / `password`
8. **Segera ganti password demo** sebelum digunakan. Akun demo hanya untuk pengujian.

## Konfigurasi Midtrans

1. Buat akun Midtrans Sandbox untuk pengujian.
2. Masukkan Server Key dan Client Key di bagian `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` pada `config.php` atau melalui environment variable.
3. Untuk integrasi aktif, pasang library resmi Midtrans PHP SDK melalui Composer (`midtrans/midtrans-php`) dan sesuaikan `midtrans_create.php` dengan dokumentasi resmi Midtrans yang berlaku.
4. Daftarkan URL notifikasi (webhook) ke `https://DOMAIN-PUBLIK/digitalisasi-spp-pesantren/midtrans_notification.php` pada dashboard Midtrans. URL harus dapat diakses publik melalui HTTPS. `localhost` tidak dapat menerima webhook dari Midtrans; untuk uji lokal gunakan tunnel HTTPS atau tombol Periksa status.
5. Jangan pernah meletakkan Server Key di kode frontend atau mengirimkannya ke browser.

**Penting:** koneksi Midtrans membutuhkan kredensial, SDK, URL publik HTTPS, dan konfigurasi akun. Tanpa itu, tombol Midtrans akan menjelaskan bahwa integrasi belum dikonfigurasi. Jangan gunakan mode produksi sebelum pengujian menyeluruh.

## Alur pembayaran

- **Tunai di akademik:** santri memilih bulan, mengunggah bukti/nota yang diminta, status `Menunggu verifikasi`; admin memeriksa pembayaran dan menyetujui.
- **Transfer bank:** santri memilih bulan, mengunggah bukti transfer; status `Menunggu verifikasi`; admin memeriksa dan menyetujui.
- **Midtrans:** pilih tepat satu bulan untuk setiap transaksi Midtrans. Aplikasi menyimpan Order ID, membuat Snap Token satu kali dan menyimpan token agar refresh tidak membuat Order ID duplikat. Setelah pembayaran, status `paid` hanya diberikan jika notifikasi tervalidasi atau Status API mengonfirmasi status `settlement`/`capture` yang diterima. Jangan menandai lunas hanya berdasarkan redirect browser.
- Setelah membuat transaksi baru, gunakan tombol **Periksa status** jika webhook belum diterima. Transaksi lama yang dibuat dengan versi kode sebelumnya dan tidak ada di Dashboard Midtrans tidak dapat otomatis diverifikasi; jangan tandai lunas manual.
- Bulan hanya dihitung lunas setelah pembayaran berstatus `paid`. Pembayaran manual yang menunggu verifikasi tidak menghapus tunggakan.

## Catatan keamanan sebelum produksi

- Ganti seluruh password demo, gunakan HTTPS, backup database, dan batasi akses admin.
- Sesuaikan kebijakan pembayaran sebagian, refund, biaya gateway, dan verifikasi bukti dengan kebijakan pesantren.
- Batasi ukuran/jenis berkas upload dan lindungi direktori upload.
- Webhook Midtrans wajib diverifikasi menggunakan signature resmi; kode contoh memverifikasi signature bila Server Key diisi.
- Lakukan audit keamanan, uji beban, dan pengujian penerimaan pengguna sebelum digunakan untuk data asli.

## Setting Tunnels HTTPS

- cd %USERPROFILE%\Downloads
- cloudflared-windows-amd64.exe tunnel --url http://localhost:80
