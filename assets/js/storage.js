/**
 * LocalStorage Manager & Business Logic
 * SIPENSA-MH - Pondok Pesantren Mambaul Huda Pajomblangan
 */

const STORAGE_KEYS = {
  INFO: 'sipensa_info_v1',
  TARIFFS: 'sipensa_tariffs_v1',
  SANTRI: 'sipensa_santri_v1',
  BILLS: 'sipensa_bills_v1',
  TRANSACTIONS: 'sipensa_transactions_v1',
  EVALUATIONS: 'sipensa_evaluations_v1',
  THEME: 'sipensa_theme_v1',
  ACTIVE_ROLE: 'sipensa_role_v1'
};

class StorageManager {
  constructor() {
    this.init();
  }

  init() {
    if (!localStorage.getItem(STORAGE_KEYS.INFO)) {
      localStorage.setItem(STORAGE_KEYS.INFO, JSON.stringify(DEFAULT_PESANTREN_INFO));
    }
    if (!localStorage.getItem(STORAGE_KEYS.TARIFFS)) {
      localStorage.setItem(STORAGE_KEYS.TARIFFS, JSON.stringify(DEFAULT_TARIFFS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.SANTRI)) {
      localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(DEFAULT_SANTRI));
    }
    if (!localStorage.getItem(STORAGE_KEYS.BILLS)) {
      localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(DEFAULT_BILLS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.TRANSACTIONS)) {
      localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(DEFAULT_TRANSACTIONS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.EVALUATIONS)) {
      localStorage.setItem(STORAGE_KEYS.EVALUATIONS, JSON.stringify(DEFAULT_EVALUATIONS));
    }
  }

  resetAll() {
    localStorage.setItem(STORAGE_KEYS.INFO, JSON.stringify(DEFAULT_PESANTREN_INFO));
    localStorage.setItem(STORAGE_KEYS.TARIFFS, JSON.stringify(DEFAULT_TARIFFS));
    localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(DEFAULT_SANTRI));
    localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(DEFAULT_BILLS));
    localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(DEFAULT_TRANSACTIONS));
    localStorage.setItem(STORAGE_KEYS.EVALUATIONS, JSON.stringify(DEFAULT_EVALUATIONS));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
  }

  getInfo() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.INFO)) || DEFAULT_PESANTREN_INFO;
    } catch (e) {
      return DEFAULT_PESANTREN_INFO;
    }
  }

  saveInfo(info) {
    localStorage.setItem(STORAGE_KEYS.INFO, JSON.stringify(info));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
  }

  getTariffs() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.TARIFFS)) || DEFAULT_TARIFFS;
    } catch (e) {
      return DEFAULT_TARIFFS;
    }
  }

  saveTariffs(tariffs) {
    localStorage.setItem(STORAGE_KEYS.TARIFFS, JSON.stringify(tariffs));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
  }

  getSantri() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.SANTRI)) || DEFAULT_SANTRI;
    } catch (e) {
      return DEFAULT_SANTRI;
    }
  }

  getSantriByNis(nis) {
    const list = this.getSantri();
    return list.find(s => s.nis.trim() === String(nis).trim());
  }

  saveSantri(santri) {
    const list = this.getSantri();
    const index = list.findIndex(s => s.nis === santri.nis);
    if (index >= 0) {
      list[index] = { ...list[index], ...santri };
    } else {
      list.push(santri);
      // Auto-create initial October 2026 SPP and Makan bills for newly added santri
      this.createInitialBillsForSantri(santri.nis);
    }
    localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(list));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
  }

  deleteSantri(nis) {
    let list = this.getSantri();
    list = list.filter(s => s.nis !== nis);
    localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(list));
    // Also remove associated unpaid bills
    let bills = this.getBills().filter(b => b.nis !== nis || b.status === 'Lunas');
    localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(bills));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
  }

  createInitialBillsForSantri(nis) {
    const bills = this.getBills();
    const info = this.getInfo();
    bills.push({
      id: `BILL-${Date.now().toString().slice(-4)}1`,
      nis: nis,
      tarifId: "spp",
      periode: info.bulanBerjalan,
      nominal: 250000,
      terbayar: 0,
      status: "Belum Bayar",
      jatuhTempo: "2026-10-10"
    });
    bills.push({
      id: `BILL-${Date.now().toString().slice(-4)}2`,
      nis: nis,
      tarifId: "makan",
      periode: info.bulanBerjalan,
      nominal: 300000,
      terbayar: 0,
      status: "Belum Bayar",
      jatuhTempo: "2026-10-10"
    });
    localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(bills));
  }

  getBills() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.BILLS)) || DEFAULT_BILLS;
    } catch (e) {
      return DEFAULT_BILLS;
    }
  }

  getSantriBills(nis) {
    const bills = this.getBills();
    const tariffs = this.getTariffs();
    return bills.filter(b => b.nis === String(nis)).map(b => {
      const tariff = tariffs.find(t => t.id === b.tarifId) || { nama: b.tarifId, nominal: b.nominal };
      return {
        ...b,
        namaTarif: tariff.nama,
        sisaBayar: Math.max(0, b.nominal - (b.terbayar || 0))
      };
    });
  }

  getTransactions() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.TRANSACTIONS)) || DEFAULT_TRANSACTIONS;
    } catch (e) {
      return DEFAULT_TRANSACTIONS;
    }
  }

  recordTransaction(transactionData) {
    const transactions = this.getTransactions();
    const bills = this.getBills();
    const santriList = this.getSantri();

    // Generate unique code if not provided
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
    const randSeq = String(transactions.length + 1).padStart(3, '0');
    const kode = transactionData.kode || `KW-MH-${dateStr}-${randSeq}`;

    const newTx = {
      kode: kode,
      nis: transactionData.nis,
      namaSantri: transactionData.namaSantri,
      tanggal: transactionData.tanggal || new Date().toLocaleString('sv-SE').replace('T', ' '),
      totalBayar: transactionData.totalBayar,
      metode: transactionData.metode,
      referensi: transactionData.referensi || `TRX-${Date.now()}`,
      kasir: transactionData.kasir || this.getInfo().bendahara,
      keterangan: transactionData.keterangan || "Pembayaran Administrasi Santri",
      items: transactionData.items || []
    };

    // Update bill statuses if billIds provided
    if (transactionData.billIds && transactionData.billIds.length > 0) {
      transactionData.billIds.forEach(bId => {
        const b = bills.find(item => item.id === bId);
        if (b) {
          b.terbayar = b.nominal;
          b.status = "Lunas";
        }
      });
      localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(bills));
    }

    // Top up e-saku if payment is a top-up
    if (transactionData.isTopUp) {
      const targetSantri = santriList.find(s => s.nis === transactionData.nis);
      if (targetSantri) {
        targetSantri.saldoSaku = (targetSantri.saldoSaku || 0) + transactionData.totalBayar;
        localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(santriList));
      }
    }

    transactions.unshift(newTx);
    localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(transactions));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
    return newTx;
  }

  topUpSantriSaku(nis, nominal, catatan = "Top Up E-Saku") {
    const santriList = this.getSantri();
    const santri = santriList.find(s => s.nis === nis);
    if (!santri) return false;

    santri.saldoSaku = (santri.saldoSaku || 0) + Number(nominal);
    localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(santriList));

    // Also record transaction
    this.recordTransaction({
      nis: santri.nis,
      namaSantri: santri.nama,
      totalBayar: Number(nominal),
      metode: "Top Up E-Saku (Tunai/Transfer)",
      referensi: `SAKU-${Date.now()}`,
      keterangan: catatan,
      items: [{ nama: `Pengisian Saldo E-Saku Santri: ${catatan}`, nominal: Number(nominal) }]
    });

    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
    return true;
  }

  tarikSantriSaku(nis, nominal, catatan = "Penarikan Saku Santri") {
    const santriList = this.getSantri();
    const santri = santriList.find(s => s.nis === nis);
    if (!santri || (santri.saldoSaku || 0) < Number(nominal)) return false;

    santri.saldoSaku = (santri.saldoSaku || 0) - Number(nominal);
    localStorage.setItem(STORAGE_KEYS.SANTRI, JSON.stringify(santriList));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
    return true;
  }

  generateMonthlyBillingForAll(periodeName = "November 2026") {
    const santriList = this.getSantri();
    const bills = this.getBills();
    let addedCount = 0;

    santriList.forEach(santri => {
      // Check if SPP already exists for this period
      const existsSpp = bills.find(b => b.nis === santri.nis && b.tarifId === 'spp' && b.periode === periodeName);
      if (!existsSpp) {
        bills.push({
          id: `BILL-${Date.now().toString().slice(-4)}${Math.floor(Math.random() * 1000)}`,
          nis: santri.nis,
          tarifId: "spp",
          periode: periodeName,
          nominal: 250000,
          terbayar: 0,
          status: "Belum Bayar",
          jatuhTempo: "2026-11-10"
        });
        addedCount++;
      }

      // Check if Makan already exists for this period
      const existsMakan = bills.find(b => b.nis === santri.nis && b.tarifId === 'makan' && b.periode === periodeName);
      if (!existsMakan) {
        bills.push({
          id: `BILL-${Date.now().toString().slice(-4)}${Math.floor(Math.random() * 1000)}`,
          nis: santri.nis,
          tarifId: "makan",
          periode: periodeName,
          nominal: 300000,
          terbayar: 0,
          status: "Belum Bayar",
          jatuhTempo: "2026-11-10"
        });
        addedCount++;
      }
    });

    localStorage.setItem(STORAGE_KEYS.BILLS, JSON.stringify(bills));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
    return addedCount;
  }

  getEvaluations() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.EVALUATIONS)) || DEFAULT_EVALUATIONS;
    } catch (e) {
      return DEFAULT_EVALUATIONS;
    }
  }

  addEvaluation(evalData) {
    const evals = this.getEvaluations();
    evals.unshift({
      id: Date.now(),
      tanggal: new Date().toISOString().slice(0, 10),
      ...evalData
    });
    localStorage.setItem(STORAGE_KEYS.EVALUATIONS, JSON.stringify(evals));
    window.dispatchEvent(new CustomEvent('sipensa-data-changed'));
    return true;
  }

  getFinancialSummary() {
    const transactions = this.getTransactions();
    const bills = this.getBills();
    const santriList = this.getSantri();

    const totalPenerimaan = transactions.reduce((sum, t) => sum + (Number(t.totalBayar) || 0), 0);

    let totalTunggakan = 0;
    let countLunas = 0;
    let countBelumLunas = 0;

    santriList.forEach(s => {
      const santriBills = bills.filter(b => b.nis === s.nis);
      const unpaid = santriBills.filter(b => b.status !== 'Lunas');
      if (unpaid.length === 0 && santriBills.length > 0) {
        countLunas++;
      } else {
        countBelumLunas++;
      }
      unpaid.forEach(b => {
        totalTunggakan += (b.nominal - (b.terbayar || 0));
      });
    });

    // Today's revenue
    const today = new Date().toISOString().slice(0, 10);
    const penerimaanHariIni = transactions
      .filter(t => t.tanggal.startsWith(today))
      .reduce((sum, t) => sum + (Number(t.totalBayar) || 0), 0);

    // Percentage of completion
    const totalSantri = santriList.length || 1;
    const persentaseLunas = Math.round((countLunas / totalSantri) * 100);

    return {
      totalPenerimaan,
      penerimaanHariIni,
      totalTunggakan,
      totalSantri: santriList.length,
      countLunas,
      countBelumLunas,
      persentaseLunas,
      totalTransaksi: transactions.length
    };
  }
}

// Utility functions
window.formatRupiah = function(nominal) {
  const num = Number(nominal) || 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num);
};

window.storageManager = new StorageManager();
