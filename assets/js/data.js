/**
 * Default Seed Data for Pondok Pesantren Mambaul Huda Pajomblangan
 * Penelitian Skripsi: Muhammad Kowi (NIM: 102230038)
 */

const DEFAULT_PESANTREN_INFO = {
  nama: "Pondok Pesantren Mambaul Huda Pajomblangan",
  singkatan: "PONPES MAMBAUL HUDA",
  alamat: "Jl. Raya Pajomblangan No. 45, Kec. Kedungwuni, Kab. Pekalongan, Jawa Tengah 51173",
  telepon: "(0285) 789-2231 / 0857-4288-9900",
  email: "keuangan@mambaulhuda-pajomblangan.sch.id",
  website: "www.mambaulhuda-pajomblangan.sch.id",
  pimpinan: "K.H. Masruri Ahmad, M.Pd.",
  bendahara: "Ust. M. Rizqi Fauzan, S.E.",
  peneliti: "Muhammad Kowi (NIM: 102230038)",
  judulPenelitian: "Implementasi Prototype Model pada Sistem Digitalisasi Pembayaran Berbasis Web untuk Efisiensi Administrasi Keuangan Santri di Pondok Pesantren Mambaul Huda Pajomblangan",
  tahunAjaran: "2026/2027",
  bulanBerjalan: "Oktober 2026"
};

const DEFAULT_TARIFFS = [
  { id: "spp", kode: "SPP-01", nama: "Syahriah / SPP Bulanan", nominal: 250000, tipe: "bulanan", deskripsi: "Operasional pendidikan santri, listrik, air & asrama" },
  { id: "makan", kode: "MKN-02", nama: "Uang Makan & Katering", nominal: 300000, tipe: "bulanan", deskripsi: "Katering makan santri 3x sehari" },
  { id: "kitab", kode: "KTB-03", nama: "Uang Kitab Kuning Semester", nominal: 150000, tipe: "semester", deskripsi: "Pengadaan kitab kuning & modul pengajian" },
  { id: "gedung", kode: "GDG-04", nama: "Infaq Pembangunan / Sarpras", nominal: 1500000, tipe: "tahunan", deskripsi: "Pengembangan sarana prasarana asrama santri" },
  { id: "haflah", kode: "HFL-05", nama: "Haflah Akhirussanah & Khotmil Qur'an", nominal: 200000, tipe: "insidental", deskripsi: "Kegiatan wisuda & penutupan tahun ajaran" }
];

const DEFAULT_SANTRI = [
  {
    nis: "20240101",
    nama: "Ahmad Zainal Arifin",
    gender: "Laki-laki",
    jenjang: "Salafiyah Wustho",
    kelas: "Kelas 2 Wustho",
    kamar: "Kamar Al-Fath (A1)",
    wali: "H. Sukirno",
    noHpWali: "081234567890",
    saldoSaku: 185000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1544717305-2782549b5136?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240102",
    nama: "Muhammad Ilham Ramadhan",
    gender: "Laki-laki",
    jenjang: "Salafiyah Ulya",
    kelas: "Kelas 3 Ulya",
    kamar: "Kamar An-Nur (A2)",
    wali: "M. Sholihin, S.Pd.",
    noHpWali: "082198765432",
    saldoSaku: 320000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240103",
    nama: "Siti Fatimatuz Zahra",
    gender: "Perempuan",
    jenjang: "Tahfidzul Qur'an",
    kelas: "Juz 15-20",
    kamar: "Kamar Khadijah (P1)",
    wali: "Hj. Munawaroh",
    noHpWali: "085811223344",
    saldoSaku: 450000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240104",
    nama: "Fikri Maulana Yusuf",
    gender: "Laki-laki",
    jenjang: "Salafiyah Wustho",
    kelas: "Kelas 1 Wustho",
    kamar: "Kamar Al-Barokah (B1)",
    wali: "Supriyadi",
    noHpWali: "081399887766",
    saldoSaku: 75000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240105",
    nama: "Nur Aini Lathifah",
    gender: "Perempuan",
    jenjang: "Salafiyah Ulya",
    kelas: "Kelas 1 Ulya",
    kamar: "Kamar Fatimah (P2)",
    wali: "H. Nurhadi",
    noHpWali: "087766554433",
    saldoSaku: 210000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240106",
    nama: "M. Danu Rizqullah",
    gender: "Laki-laki",
    jenjang: "Tahfidzul Qur'an",
    kelas: "Juz 5-10",
    kamar: "Kamar Al-Ikhlas (B2)",
    wali: "Bambang Irawan",
    noHpWali: "085233445566",
    saldoSaku: 90000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240107",
    nama: "Naila Khusnul Khotimah",
    gender: "Perempuan",
    jenjang: "Salafiyah Wustho",
    kelas: "Kelas 3 Wustho",
    kamar: "Kamar Aisyah (P3)",
    wali: "Drs. H. Mulyono",
    noHpWali: "081223344556",
    saldoSaku: 500000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80"
  },
  {
    nis: "20240108",
    nama: "Bilal Habibi Mustofa",
    gender: "Laki-laki",
    jenjang: "Salafiyah Ula",
    kelas: "Kelas 1 Ula",
    kamar: "Kamar Al-Fath (A1)",
    wali: "Tarmizi",
    noHpWali: "087811992233",
    saldoSaku: 120000,
    status: "Aktif",
    foto: "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80"
  }
];

// Bill items mapped per santri
const DEFAULT_BILLS = [
  // Ahmad Zainal Arifin (Lunas sebagian, sisa makan & kitab)
  { id: "BILL-101", nis: "20240101", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 250000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-102", nis: "20240101", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },
  { id: "BILL-103", nis: "20240101", tarifId: "kitab", periode: "Semester Ganjil 2026", nominal: 150000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-25" },

  // Muhammad Ilham Ramadhan (Semua lunas)
  { id: "BILL-201", nis: "20240102", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 250000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-202", nis: "20240102", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 300000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-203", nis: "20240102", tarifId: "kitab", periode: "Semester Ganjil 2026", nominal: 150000, terbayar: 150000, status: "Lunas", jatuhTempo: "2026-10-25" },

  // Siti Fatimatuz Zahra (Belum bayar SPP & Makan)
  { id: "BILL-301", nis: "20240103", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },
  { id: "BILL-302", nis: "20240103", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },
  { id: "BILL-303", nis: "20240103", tarifId: "gedung", periode: "Cicilan 2 Sarpras", nominal: 500000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-30" },

  // Fikri Maulana Yusuf (Menunggak September & Oktober)
  { id: "BILL-401", nis: "20240104", tarifId: "spp", periode: "September 2026", nominal: 250000, terbayar: 0, status: "Menunggak", jatuhTempo: "2026-09-10" },
  { id: "BILL-402", nis: "20240104", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },
  { id: "BILL-403", nis: "20240104", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },

  // Nur Aini Lathifah (Lunas SPP, belum bayar gedung)
  { id: "BILL-501", nis: "20240105", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 250000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-502", nis: "20240105", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 300000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-503", nis: "20240105", tarifId: "gedung", periode: "Cicilan 1 Sarpras", nominal: 500000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-25" },

  // M. Danu Rizqullah (Belum bayar semua)
  { id: "BILL-601", nis: "20240106", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },
  { id: "BILL-602", nis: "20240106", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-10" },

  // Naila Khusnul Khotimah (Lunas)
  { id: "BILL-701", nis: "20240107", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 250000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-702", nis: "20240107", tarifId: "makan", periode: "Oktober 2026", nominal: 300000, terbayar: 300000, status: "Lunas", jatuhTempo: "2026-10-10" },

  // Bilal Habibi Mustofa (Santri baru, cicilan gedung + SPP)
  { id: "BILL-801", nis: "20240108", tarifId: "spp", periode: "Oktober 2026", nominal: 250000, terbayar: 250000, status: "Lunas", jatuhTempo: "2026-10-10" },
  { id: "BILL-802", nis: "20240108", tarifId: "kitab", periode: "Semester Ganjil 2026", nominal: 150000, terbayar: 0, status: "Belum Bayar", jatuhTempo: "2026-10-25" }
];

const DEFAULT_TRANSACTIONS = [
  {
    kode: "KW-MH-20261001-001",
    nis: "20240102",
    namaSantri: "Muhammad Ilham Ramadhan",
    tanggal: "2026-10-02 08:45:12",
    totalBayar: 700000,
    metode: "QRIS",
    referensi: "QRIS-ID-88291039",
    kasir: "Ust. M. Rizqi Fauzan, S.E.",
    keterangan: "Pelunasan SPP, Makan & Kitab Semester Ganjil",
    items: [
      { nama: "Syahriah / SPP Bulanan (Oktober 2026)", nominal: 250000 },
      { nama: "Uang Makan & Katering (Oktober 2026)", nominal: 300000 },
      { nama: "Uang Kitab Kuning Semester (Semester Ganjil 2026)", nominal: 150000 }
    ]
  },
  {
    kode: "KW-MH-20261003-002",
    nis: "20240107",
    namaSantri: "Naila Khusnul Khotimah",
    tanggal: "2026-10-03 09:12:40",
    totalBayar: 550000,
    metode: "Virtual Account BSI",
    referensi: "VA-BSI-99238471",
    kasir: "Sistem Otomatis (VA)",
    keterangan: "Pembayaran SPP dan Katering Oktober 2026",
    items: [
      { nama: "Syahriah / SPP Bulanan (Oktober 2026)", nominal: 250000 },
      { nama: "Uang Makan & Katering (Oktober 2026)", nominal: 300000 }
    ]
  },
  {
    kode: "KW-MH-20261005-003",
    nis: "20240101",
    namaSantri: "Ahmad Zainal Arifin",
    tanggal: "2026-10-05 10:30:15",
    totalBayar: 250000,
    metode: "Tunai (Kasir)",
    referensi: "CASH-LOKET-01",
    kasir: "Ust. M. Rizqi Fauzan, S.E.",
    keterangan: "Pembayaran Syahriah SPP Oktober 2026",
    items: [
      { nama: "Syahriah / SPP Bulanan (Oktober 2026)", nominal: 250000 }
    ]
  },
  {
    kode: "KW-MH-20261007-004",
    nis: "20240105",
    namaSantri: "Nur Aini Lathifah",
    tanggal: "2026-10-07 14:15:22",
    totalBayar: 550000,
    metode: "Transfer Mandiri",
    referensi: "TRF-MDR-443211",
    kasir: "Ust. M. Rizqi Fauzan, S.E.",
    keterangan: "Pembayaran SPP dan Uang Makan",
    items: [
      { nama: "Syahriah / SPP Bulanan (Oktober 2026)", nominal: 250000 },
      { nama: "Uang Makan & Katering (Oktober 2026)", nominal: 300000 }
    ]
  },
  {
    kode: "KW-MH-20261009-005",
    nis: "20240108",
    namaSantri: "Bilal Habibi Mustofa",
    tanggal: "2026-10-09 11:05:00",
    totalBayar: 250000,
    metode: "Tunai (Kasir)",
    referensi: "CASH-LOKET-02",
    kasir: "Ust. M. Rizqi Fauzan, S.E.",
    keterangan: "Pembayaran SPP Santri Baru Oktober 2026",
    items: [
      { nama: "Syahriah / SPP Bulanan (Oktober 2026)", nominal: 250000 }
    ]
  }
];

const DEFAULT_PROTOTYPE_STAGES = [
  {
    fase: 1,
    nama: "Communication & Requirements",
    status: "Selesai (Completed)",
    persentase: 100,
    tanggal: "Agustus 2026",
    deskripsi: "Wawancara pengurus & bendahara Ponpes Mambaul Huda Pajomblangan, observasi proses pencatatan buku besar manual, identifikasi kendala waktu antrean dan risiko rekapitulasi data tercecer.",
    temuan: "Rata-rata pelayanan pembayaran manual memerlukan 12-18 menit per santri. Risiko ketidakcocokan saldo buku kas fisik mencapai 10-15% per bulan."
  },
  {
    fase: 2,
    nama: "Quick Plan & Quick Design",
    status: "Selesai (Completed)",
    persentase: 100,
    tanggal: "September 2026",
    deskripsi: "Perancangan arsitektur sistem, Data Flow Diagram (DFD) Level 0-2, Entity Relationship Diagram (ERD), wireframe antarmuka kasir cepat, dan skema integrasi multichannel payment (Tunai, QRIS, VA).",
    temuan: "Disepakati antarmuka berbasis Single Page Application (SPA) responsif agar bisa diakses cepat lewat laptop kantor maupun smartphone wali santri."
  },
  {
    fase: 3,
    nama: "Construction of Prototype",
    status: "Aktif (Live System v2.1)",
    persentase: 100,
    tanggal: "Oktober 2026",
    deskripsi: "Pengembangan prototipe fungsional berbasis web: Dashboard keuangan realtime, Loket Kasir Cepat dengan hitung kembalian dan cetak kuitansi, Portal Mandiri Wali Santri, dan modul E-Saku santri.",
    temuan: "Prototipe berhasil memvalidasi otomatisasi kalkulasi tagihan dan penerbitan nomor kwitansi unik seketika saat pembayaran selesai."
  },
  {
    fase: 4,
    nama: "Evaluation & Refinement",
    status: "Pengujian Berjalan (UAT Iterasi 2)",
    persentase: 90,
    tanggal: "Oktober 2026",
    deskripsi: "Pengujian User Acceptance Test (UAT) dan evaluasi efisiensi administrasi bersama bendahara ponpes, pengurus madrasah, dan sampel wali santri menggunakan kuesioner System Usability Scale (SUS).",
    temuan: "Rata-rata durasi transaksi terpangkas dari 15 menit menjadi 42 detik (efisiensi 95.3%). Skor SUS mencapai 88.5 (Grade A - Sangat Baik / Excellent)."
  }
];

const DEFAULT_EVALUATIONS = [
  {
    id: 1,
    responden: "Ust. M. Rizqi Fauzan, S.E. (Bendahara Utama Ponpes)",
    peran: "Bendahara / Admin Kasir",
    tanggal: "2026-10-08",
    skorKemudahan: 5,
    skorKecepatan: 5,
    skorAkurasi: 5,
    waktuLamaMenit: 15.0,
    waktuBaruDetik: 38,
    komentar: "Sangat membantu tugas kasir. Fitur pencarian NIS instan dan cetak kwitansi 1-klik menghilangkan antrean panjang saat awal bulan santri bayar syahriah.",
    rekomendasiIterasi: "Integrasi WhatsApp gateway langsung terhubung ke nomor wali santri agar bukti bayar langsung masuk ke handphone orang tua."
  },
  {
    responden: "H. Sukirno (Wali Santri Ahmad Zainal)",
    peran: "Wali Santri",
    tanggal: "2026-10-09",
    skorKemudahan: 5,
    skorKecepatan: 4,
    skorAkurasi: 5,
    waktuLamaMenit: 30.0,
    waktuBaruDetik: 45,
    komentar: "Bagus sekali, kami yang tinggal di luar kota tidak perlu repot transfer manual terus konfirmasi foto struk ke pengurus. Cukup cek NIS langsung tahu tagihan.",
    rekomendasiIterasi: "Pertahankan QRIS dinamis karena sangat praktis scan dari mobile banking apa saja."
  },
  {
    responden: "K.H. Masruri Ahmad, M.Pd. (Pimpinan Pondok)",
    peran: "Pimpinan Lembaga",
    tanggal: "2026-10-09",
    skorKemudahan: 5,
    skorKecepatan: 5,
    skorAkurasi: 5,
    waktuLamaMenit: 180.0,
    waktuBaruDetik: 10,
    komentar: "Transparansi keuangan pondok pesantren meningkat drastis. Laporan kas harian dan bulanan bisa dilihat secara realtime tanpa menunggu rekap bulanan berhari-hari.",
    rekomendasiIterasi: "Prototipe sangat layak diimplementasikan permanen untuk seluruh unit madrasah."
  }
];
