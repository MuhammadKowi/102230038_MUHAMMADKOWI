# Sistem Digitalisasi Pembayaran SPP Santri

**Stack:** PHP 8+, MySQL/MariaDB, PDO, HTML/CSS/JavaScript.  
**Tema:** modern, putih dan hijau.

## Fitur

- Login dengan peran admin dan santri.
- Dashboard ringkasan.
- Admin: kelola data santri, nominal SPP bulanan, kategori jenjang dan nominalnya.
- Admin: melihat pembayaran, menyetujui/menolak pembayaran tunai atau transfer yang melampirkan bukti.
- Santri: melihat bulan tertunggak, memilih bulan SPP yang ingin dibayar, memilih kategori jenjang.
- Riwayat pembayaran dan status pembayaran.

## Persyaratan

- PHP 8.0+ dengan ekstensi `pdo_mysql`, `fileinfo`.
- MySQL 5.7+/MariaDB.
- XAMPP/WAMP/LAMP.

## Instalasi lokal (XAMPP)

1. Ekstrak folder `santri-go` ke `C:\xampp\htdocs\`.
2. Buat database bernama `pesantren_spp`.
3. Import `database.sql` melalui phpMyAdmin.
4. Buka `config.php`, sesuaikan `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Salin `uploads/.htaccess.example` menjadi `uploads/.htaccess` (Apache). Folder `uploads` harus dapat ditulis oleh PHP.
6. Buka `http://localhost/santri-go/`.
7. Akun awal:
   - Admin: `admin` / `password`
   - Santri demo: `santri001` / `password`
8. **Segera ganti password demo** sebelum digunakan. Akun demo hanya untuk pengujian.




## Alur pembayaran

- **Tunai di akademik:** santri memilih bulan, mengunggah bukti/nota yang diminta, status `Menunggu verifikasi`; admin memeriksa pembayaran dan menyetujui.
- **Transfer bank:** santri memilih bulan, mengunggah bukti transfer; status `Menunggu verifikasi`; admin memeriksa dan menyetujui.
- Bulan hanya dihitung lunas setelah pembayaran berstatus `paid`. Pembayaran manual yang menunggu verifikasi tidak menghapus tunggakan.

## Catatan keamanan sebelum produksi

- Ganti seluruh password demo, gunakan HTTPS, backup database, dan batasi akses admin.
- Sesuaikan kebijakan pembayaran sebagian, refund, biaya gateway, dan verifikasi bukti dengan kebijakan pesantren.
- Batasi ukuran/jenis berkas upload dan lindungi direktori upload.
- Lakukan audit keamanan, uji beban, dan pengujian penerimaan pengguna sebelum digunakan untuk data asli.

## Setting Tunnels HTTPS

- cd %USERPROFILE%\Downloads
- cloudflared-windows-amd64.exe tunnel --url http://localhost:80

## Pembaruan fitur admin
- Menu `Nominal Jenjang` mengelola nama dan nominal jenjang tanpa mengubah nominal SPP.
- Menu `Nominal SPP` mengelola tarif SPP bulanan secara terpisah untuk setiap jenjang.
- Menu `Kelola User` memungkinkan admin mencari semua akun, memperbarui nama/username/password, dan menambah/menghapus akun admin. Akun santri tetap dikelola melalui menu Data Santri agar relasi data dan pembayaran terjaga.
- Menu `Cari Santri & Tagihan` mencari santri dengan nama, username, nomor induk, atau jenjang, lalu menampilkan transaksi/tagihan yang sudah tercatat beserta nominal dan statusnya.

## Pembayaran SPP dan biaya jenjang secara terpisah

Pada database lama, jalankan `upgrade_pemisahan_tagihan.sql` satu kali melalui phpMyAdmin pada database `pesantren_spp`. Migrasi menambahkan kolom `payment_type` agar status SPP dan biaya jenjang untuk bulan yang sama dicatat terpisah. Untuk instalasi baru, impor `database.sql` terbaru sehingga kolom tersebut langsung tersedia.