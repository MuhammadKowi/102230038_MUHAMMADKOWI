/**
 * Main Application Logic for SIPENSA-MH
 * Pondok Pesantren Mambaul Huda Pajomblangan
 * Penelitian Skripsi: Muhammad Kowi (NIM: 102230038)
 */

document.addEventListener('DOMContentLoaded', () => {
  // Global State
  const state = {
    currentTab: 'dashboard',
    currentRole: 'admin', // 'admin', 'wali', 'prototype'
    selectedPosSantri: null,
    selectedPosBills: [],
    selectedPaymentMethod: 'tunai', // 'tunai', 'qris', 'va'
    portalSantri: null,
    qrisTimerInterval: null
  };

  // DOM Elements
  const navItems = document.querySelectorAll('.nav-item');
  const pageSections = document.querySelectorAll('.page-section');
  const roleButtons = document.querySelectorAll('.role-btn');
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const soundToggleBtn = document.getElementById('soundToggleBtn');
  const resetDataBtn = document.getElementById('resetDataBtn');

  // Initialize
  initApp();

  function initApp() {
    // Load theme preference
    const savedTheme = localStorage.getItem('sipensa_theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark-theme');
      updateThemeIcon(true);
    }

    // Bind Navigation
    navItems.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const tab = item.dataset.tab;
        switchTab(tab);
        window.soundFx.playClick();
      });
    });

    // Bind Role Switcher
    roleButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const role = btn.dataset.role;
        switchRole(role);
        window.soundFx.playClick();
      });
    });

    // Theme Toggle
    if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-theme');
        const isDark = document.body.classList.contains('dark-theme');
        localStorage.setItem('sipensa_theme', isDark ? 'dark' : 'light');
        updateThemeIcon(isDark);
        window.soundFx.playClick();
        window.sipensaCharts.renderAll();
      });
    }

    // Sound Toggle
    if (soundToggleBtn) {
      soundToggleBtn.addEventListener('click', () => {
        window.soundFx.muted = !window.soundFx.muted;
        soundToggleBtn.innerHTML = window.soundFx.muted 
          ? '<i class="ph ph-speaker-slash"></i>' 
          : '<i class="ph ph-speaker-high"></i>';
        showToast(window.soundFx.muted ? 'Suara dinonaktifkan' : 'Suara diaktifkan', 'info');
      });
    }

    // Reset Data
    if (resetDataBtn) {
      resetDataBtn.addEventListener('click', () => {
        if (confirm('Kembalikan seluruh data ke pengaturan awal (Seed Data Ponpes Mambaul Huda)?')) {
          window.storageManager.resetAll();
          showToast('Data berhasil di-reset ke kondisi awal demo!', 'success');
          window.soundFx.playSuccess();
        }
      });
    }

    // Setup POS Cashier
    setupPosCashier();

    // Setup Santri Table & Modals
    setupSantriManagement();

    // Setup Portal Wali Santri
    setupPortalWali();

    // Setup Financial Reports & Export
    setupFinancialReports();

    // Setup Prototype Model Evaluation
    setupPrototypeEvaluation();

    // Setup Settings
    setupSettings();

    // Initial Data Render
    renderAllViews();

    // Listen for storage changes
    window.addEventListener('sipensa-data-changed', () => {
      renderAllViews();
    });

    // Render initial charts after short delay
    setTimeout(() => {
      window.sipensaCharts.renderAll();
    }, 150);
  }

  function updateThemeIcon(isDark) {
    if (!themeToggleBtn) return;
    themeToggleBtn.innerHTML = isDark 
      ? '<i class="ph ph-sun"></i>' 
      : '<i class="ph ph-moon"></i>';
  }

  function switchTab(tabId) {
    state.currentTab = tabId;

    navItems.forEach(item => {
      item.classList.toggle('active', item.dataset.tab === tabId);
    });

    pageSections.forEach(sec => {
      sec.classList.toggle('active', sec.id === `section-${tabId}`);
    });

    // Re-render charts if dashboard or prototype
    if (tabId === 'dashboard' || tabId === 'prototype') {
      setTimeout(() => {
        window.sipensaCharts.renderAll();
      }, 50);
    }
  }

  function switchRole(role) {
    state.currentRole = role;
    roleButtons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.role === role);
    });

    if (role === 'admin') {
      switchTab('dashboard');
      showToast('Beralih ke mode Bendahara / Admin Keuangan', 'info');
    } else if (role === 'wali') {
      switchTab('portal');
      showToast('Beralih ke Portal Mandiri Wali Santri', 'info');
      // Auto select first santri for instant demo convenience
      selectPortalSantri('20240101');
    } else if (role === 'prototype') {
      switchTab('prototype');
      showToast('Beralih ke Mode Riset Prototype Model (Skripsi)', 'info');
    }
  }

  function renderAllViews() {
    renderDashboardStats();
    renderSantriTable();
    renderTransactionsTable();
    renderLaporanKas();
    renderPrototypeEvaluations();
    renderSettingsView();
    if (state.selectedPosSantri) {
      renderPosBills(state.selectedPosSantri.nis);
    }
    if (state.portalSantri) {
      renderPortalSantriView(state.portalSantri.nis);
    }
  }

  // =========================================================================
  // 1. DASHBOARD LOGIC
  // =========================================================================
  function renderDashboardStats() {
    const summary = window.storageManager.getFinancialSummary();

    const elTotalPenerimaan = document.getElementById('statTotalPenerimaan');
    const elPenerimaanHariIni = document.getElementById('statPenerimaanHariIni');
    const elTotalTunggakan = document.getElementById('statTotalTunggakan');
    const elPersenLunas = document.getElementById('statPersenLunas');
    const elCountSantri = document.getElementById('statCountSantri');

    if (elTotalPenerimaan) elTotalPenerimaan.textContent = window.formatRupiah(summary.totalPenerimaan);
    if (elPenerimaanHariIni) elPenerimaanHariIni.textContent = window.formatRupiah(summary.penerimaanHariIni);
    if (elTotalTunggakan) elTotalTunggakan.textContent = window.formatRupiah(summary.totalTunggakan);
    if (elPersenLunas) elPersenLunas.textContent = `${summary.persenLunas}%`;
    if (elCountSantri) elCountSantri.textContent = `${summary.countLunas}/${summary.totalSantri} Santri`;

    // Render Recent Transactions on Dashboard
    const recentTxBody = document.getElementById('dashboardRecentTxBody');
    if (recentTxBody) {
      const transactions = window.storageManager.getTransactions().slice(0, 5);
      if (transactions.length === 0) {
        recentTxBody.innerHTML = `<tr><td colspan="5" class="text-center">Belum ada riwayat transaksi</td></tr>`;
      } else {
        recentTxBody.innerHTML = transactions.map(tx => `
          <tr>
            <td><strong>${tx.kode}</strong></td>
            <td>
              <div style="font-weight:700;">${tx.namaSantri}</div>
              <small class="text-muted">${tx.nis}</small>
            </td>
            <td>${tx.tanggal}</td>
            <td><span class="badge badge-secondary">${tx.metode}</span></td>
            <td style="font-weight:800; color:var(--primary);">${window.formatRupiah(tx.totalBayar)}</td>
            <td>
              <button class="btn btn-outline btn-sm" onclick="window.viewReceipt('${tx.kode}')">
                <i class="ph ph-receipt"></i> Kwitansi
              </button>
            </td>
          </tr>
        `).join('');
      }
    }
  }

  // =========================================================================
  // 2. POS / LOKET KASIR CEPAT LOGIC
  // =========================================================================
  function setupPosCashier() {
    const posSearchInput = document.getElementById('posSantriSearch');
    const posSantriDropdown = document.getElementById('posSantriSearchResults');
    const cashReceivedInput = document.getElementById('posCashReceived');
    const btnBayarPos = document.getElementById('btnSubmitPosPayment');
    const methodTabs = document.querySelectorAll('.method-tab-btn');

    // Search Santri in POS
    if (posSearchInput) {
      posSearchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        if (!query) {
          if (posSantriDropdown) posSantriDropdown.style.display = 'none';
          return;
        }

        const list = window.storageManager.getSantri();
        const filtered = list.filter(s => 
          s.nama.toLowerCase().includes(query) || 
          s.nis.includes(query) || 
          s.kamar.toLowerCase().includes(query)
        );

        if (posSantriDropdown) {
          if (filtered.length > 0) {
            posSantriDropdown.innerHTML = filtered.map(s => `
              <div class="pos-search-item" style="padding:10px 14px; cursor:pointer; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;" data-nis="${s.nis}">
                <div>
                  <strong>${s.nama}</strong> (${s.nis})
                  <div style="font-size:0.75rem; color:var(--text-muted);">${s.jenjang} - ${s.kamar}</div>
                </div>
                <span class="badge badge-success">Pilih</span>
              </div>
            `).join('');
            posSantriDropdown.style.display = 'block';

            // Click item
            posSantriDropdown.querySelectorAll('.pos-search-item').forEach(el => {
              el.addEventListener('click', () => {
                const nis = el.dataset.nis;
                selectPosSantri(nis);
                posSantriDropdown.style.display = 'none';
                posSearchInput.value = '';
                window.soundFx.playClick();
              });
            });
          } else {
            posSantriDropdown.innerHTML = `<div style="padding:12px; text-align:center; color:var(--text-muted);">Santri tidak ditemukan</div>`;
            posSantriDropdown.style.display = 'block';
          }
        }
      });
    }

    // Payment Method Tabs
    methodTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const method = tab.dataset.method;
        state.selectedPaymentMethod = method;

        methodTabs.forEach(t => t.classList.toggle('active', t.dataset.method === method));

        const boxTunai = document.getElementById('posBoxTunai');
        const boxQris = document.getElementById('posBoxQris');
        const boxVa = document.getElementById('posBoxVa');

        if (boxTunai) boxTunai.style.display = (method === 'tunai') ? 'block' : 'none';
        if (boxQris) boxQris.style.display = (method === 'qris') ? 'block' : 'block'; // handles inside
        if (boxVa) boxVa.style.display = (method === 'va') ? 'block' : 'none';

        if (method === 'qris') {
          startQrisSimulator();
        } else if (method === 'va') {
          generateVaNumber();
        }

        window.soundFx.playClick();
      });
    });

    // Cash Received Calculation
    if (cashReceivedInput) {
      cashReceivedInput.addEventListener('input', calculatePosChange);
    }

    // Quick cash buttons
    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const amount = Number(btn.dataset.amount);
        if (cashReceivedInput) {
          cashReceivedInput.value = amount;
          calculatePosChange();
          window.soundFx.playClick();
        }
      });
    });

    // Submit Payment
    if (btnBayarPos) {
      btnBayarPos.addEventListener('click', () => {
        executePosPayment();
      });
    }

    // Simulate buttons
    const btnSimulateQris = document.getElementById('btnSimulateQrisPay');
    if (btnSimulateQris) {
      btnSimulateQris.addEventListener('click', () => {
        executePosPayment('QRIS');
      });
    }

    const btnSimulateVa = document.getElementById('btnSimulateVaPay');
    if (btnSimulateVa) {
      btnSimulateVa.addEventListener('click', () => {
        const bankName = document.getElementById('posVaBankSelect')?.value || 'BSI';
        executePosPayment(`Virtual Account ${bankName}`);
      });
    }
  }

  function selectPosSantri(nis) {
    const santri = window.storageManager.getSantriByNis(nis);
    if (!santri) return;

    state.selectedPosSantri = santri;
    state.selectedPosBills = [];

    // Render Santri Info in POS
    const profileCard = document.getElementById('posSantriProfileCard');
    if (profileCard) {
      profileCard.style.display = 'flex';
      profileCard.innerHTML = `
        <img src="${santri.foto}" alt="${santri.nama}" class="santri-avatar" onerror="this.src='assets/images/ponpes_logo.jpg'">
        <div class="santri-meta" style="flex:1;">
          <h4>${santri.nama}</h4>
          <p>${santri.jenjang} &bull; ${santri.kamar}</p>
          <p>Wali: <strong>${santri.wali}</strong> (${santri.noHpWali})</p>
          <div style="display:flex; gap:8px; margin-top:6px; align-items:center;">
            <span class="nis-pill">NIS: ${santri.nis}</span>
            <span class="badge badge-success">Saldo E-Saku: ${window.formatRupiah(santri.saldoSaku || 0)}</span>
          </div>
        </div>
        <button class="btn btn-outline btn-sm" onclick="window.clearSelectedPosSantri()">Ganti</button>
      `;
    }

    renderPosBills(nis);
  }

  window.clearSelectedPosSantri = function() {
    state.selectedPosSantri = null;
    state.selectedPosBills = [];
    const profileCard = document.getElementById('posSantriProfileCard');
    if (profileCard) profileCard.style.display = 'none';
    const list = document.getElementById('posBillList');
    if (list) list.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted);">Silakan cari dan pilih santri terlebih dahulu</div>`;
    updatePosSummary();
  };

  function renderPosBills(nis) {
    const billListEl = document.getElementById('posBillList');
    if (!billListEl) return;

    const bills = window.storageManager.getSantriBills(nis);
    const unpaidBills = bills.filter(b => b.status !== 'Lunas');

    if (unpaidBills.length === 0) {
      billListEl.innerHTML = `
        <div style="padding:24px; text-align:center; background:var(--bg-body); border-radius:var(--radius-md);">
          <i class="ph ph-check-circle" style="font-size:2.5rem; color:var(--primary); margin-bottom:8px; display:inline-block;"></i>
          <h4 style="font-weight:700;">Seluruh Tagihan Lunas!</h4>
          <p style="font-size:0.8rem; color:var(--text-muted);">Santri ini tidak memiliki tagihan aktif yang tertunggak.</p>
        </div>
      `;
      state.selectedPosBills = [];
      updatePosSummary();
      return;
    }

    billListEl.innerHTML = unpaidBills.map(b => {
      const isSelected = state.selectedPosBills.some(item => item.id === b.id);
      return `
        <div class="bill-item-row ${isSelected ? 'selected' : ''}" data-bill-id="${b.id}">
          <label class="bill-checkbox-label" style="flex:1;">
            <input type="checkbox" class="bill-check-input" value="${b.id}" ${isSelected ? 'checked' : ''}>
            <div class="bill-title-sub">
              <h5>${b.namaTarif}</h5>
              <p>Periode: <strong>${b.periode}</strong> &bull; Jatuh tempo: ${b.jatuhTempo}</p>
            </div>
          </label>
          <div class="bill-amount-tag">
            ${window.formatRupiah(b.sisaBayar)}
          </div>
        </div>
      `;
    }).join('');

    // Checkbox click handlers
    billListEl.querySelectorAll('.bill-item-row').forEach(row => {
      row.addEventListener('click', (e) => {
        if (e.target.tagName !== 'INPUT') {
          const checkbox = row.querySelector('.bill-check-input');
          checkbox.checked = !checkbox.checked;
        }
        const billId = row.dataset.billId;
        const targetBill = unpaidBills.find(b => b.id === billId);
        const checkbox = row.querySelector('.bill-check-input');

        if (checkbox.checked) {
          if (!state.selectedPosBills.some(b => b.id === billId)) {
            state.selectedPosBills.push(targetBill);
          }
          row.classList.add('selected');
        } else {
          state.selectedPosBills = state.selectedPosBills.filter(b => b.id !== billId);
          row.classList.remove('selected');
        }

        window.soundFx.playClick();
        updatePosSummary();
      });
    });

    updatePosSummary();
  }

  function updatePosSummary() {
    const totalAmount = state.selectedPosBills.reduce((sum, b) => sum + b.sisaBayar, 0);
    const countItems = state.selectedPosBills.length;

    const elSubtotal = document.getElementById('posSubtotalDisplay');
    const elTotal = document.getElementById('posTotalBayarDisplay');
    const elItemCount = document.getElementById('posItemCountDisplay');
    const cashInput = document.getElementById('posCashReceived');

    if (elSubtotal) elSubtotal.textContent = window.formatRupiah(totalAmount);
    if (elTotal) elTotal.textContent = window.formatRupiah(totalAmount);
    if (elItemCount) elItemCount.textContent = `${countItems} Item Dipilih`;

    // Quick cash buttons auto value
    const btnExact = document.getElementById('quickCashExact');
    if (btnExact) {
      btnExact.dataset.amount = totalAmount;
      btnExact.textContent = `Uang Pas (${window.formatRupiah(totalAmount)})`;
    }

    if (cashInput && !cashInput.value) {
      cashInput.value = totalAmount > 0 ? totalAmount : '';
    }

    calculatePosChange();

    // Update QRIS & VA nominal display
    const qrisNominal = document.getElementById('qrisNominalDisplay');
    if (qrisNominal) qrisNominal.textContent = window.formatRupiah(totalAmount);
  }

  function calculatePosChange() {
    const totalAmount = state.selectedPosBills.reduce((sum, b) => sum + b.sisaBayar, 0);
    const cashInput = document.getElementById('posCashReceived');
    const changeDisplay = document.getElementById('posChangeDisplay');
    const btnSubmit = document.getElementById('btnSubmitPosPayment');

    const received = Number(cashInput?.value || 0);
    const change = received - totalAmount;

    if (changeDisplay) {
      if (change >= 0) {
        changeDisplay.textContent = window.formatRupiah(change);
        changeDisplay.style.color = 'var(--primary)';
        if (btnSubmit) btnSubmit.disabled = totalAmount <= 0;
      } else {
        changeDisplay.textContent = `Kurang ${window.formatRupiah(Math.abs(change))}`;
        changeDisplay.style.color = 'var(--danger)';
        if (btnSubmit) btnSubmit.disabled = true;
      }
    }
  }

  function startQrisSimulator() {
    const timerEl = document.getElementById('qrisTimerDisplay');
    if (!timerEl) return;

    if (state.qrisTimerInterval) clearInterval(state.qrisTimerInterval);

    let seconds = 15 * 60; // 15 mins
    state.qrisTimerInterval = setInterval(() => {
      seconds--;
      if (seconds <= 0) {
        clearInterval(state.qrisTimerInterval);
        timerEl.textContent = '00:00 (Kedaluwarsa)';
        return;
      }
      const m = String(Math.floor(seconds / 60)).padStart(2, '0');
      const s = String(seconds % 60).padStart(2, '0');
      timerEl.textContent = `${m}:${s}`;
    }, 1000);
  }

  function generateVaNumber() {
    const bank = document.getElementById('posVaBankSelect')?.value || 'BSI';
    const vaCodeMap = { 'BSI': '90088', 'Mandiri': '88701', 'BRI': '10292', 'BCA': '39012' };
    const prefix = vaCodeMap[bank] || '90088';
    const nis = state.selectedPosSantri ? state.selectedPosSantri.nis : '20240101';
    const vaNumber = `${prefix}${nis}${Math.floor(100 + Math.random() * 900)}`;

    const vaDisplay = document.getElementById('posVaNumberDisplay');
    if (vaDisplay) {
      vaDisplay.textContent = vaNumber;
    }
  }

  function executePosPayment(forcedMethod = null) {
    if (!state.selectedPosSantri) {
      showToast('Pilih santri terlebih dahulu!', 'error');
      return;
    }

    if (state.selectedPosBills.length === 0) {
      showToast('Pilih minimal satu tagihan untuk dibayarkan!', 'error');
      return;
    }

    const totalBayar = state.selectedPosBills.reduce((sum, b) => sum + b.sisaBayar, 0);
    const method = forcedMethod || (
      state.selectedPaymentMethod === 'tunai' ? 'Tunai (Kasir)' :
      state.selectedPaymentMethod === 'qris' ? 'QRIS Dinamis' :
      `Virtual Account ${document.getElementById('posVaBankSelect')?.value || 'BSI'}`
    );

    const items = state.selectedPosBills.map(b => ({
      nama: `${b.namaTarif} (${b.periode})`,
      nominal: b.sisaBayar
    }));

    const billIds = state.selectedPosBills.map(b => b.id);

    // Record in Storage
    const newTx = window.storageManager.recordTransaction({
      nis: state.selectedPosSantri.nis,
      namaSantri: state.selectedPosSantri.nama,
      totalBayar: totalBayar,
      metode: method,
      referensi: `${method.slice(0, 3).toUpperCase()}-${Date.now().toString().slice(-6)}`,
      keterangan: `Pembayaran ${items.length} item administrasi`,
      items: items,
      billIds: billIds
    });

    window.soundFx.playCashRegister();
    showToast(`Pembayaran ${window.formatRupiah(totalBayar)} berhasil diproses!`, 'success');

    // Open Digital Receipt
    window.viewReceipt(newTx.kode);

    // Reset POS selection
    window.clearSelectedPosSantri();
  }

  // =========================================================================
  // 3. KWITANSI DIGITAL & PRINT MODAL
  // =========================================================================
  window.viewReceipt = function(kodeKwitansi) {
    const transactions = window.storageManager.getTransactions();
    const tx = transactions.find(t => t.kode === kodeKwitansi);
    if (!tx) return;

    const santri = window.storageManager.getSantriByNis(tx.nis) || {
      nama: tx.namaSantri,
      nis: tx.nis,
      jenjang: 'Santri Ponpes Mambaul Huda',
      kamar: '-',
      wali: '-',
      noHpWali: '08123456789'
    };

    const info = window.storageManager.getInfo();

    const receiptModal = document.getElementById('receiptModal');
    const receiptContent = document.getElementById('receiptPaperContent');

    if (!receiptModal || !receiptContent) return;

    receiptContent.innerHTML = `
      <div class="receipt-paper">
        <div class="receipt-header-kop">
          <p class="arabic-bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
          <h3>${info.nama}</h3>
          <p class="kop-address">${info.alamat} &bull; Telp: ${info.telepon}</p>
          <div style="font-size:0.75rem; font-weight:700; color:#047857; margin-top:4px;">
            BUKTI PEMBAYARAN ADMINISTRASI SANTRI (KWITANSI DIGITAL RESMI)
          </div>
        </div>

        <div class="receipt-meta-grid">
          <div>
            <div><strong>No. Kwitansi:</strong> ${tx.kode}</div>
            <div><strong>Waktu:</strong> ${tx.tanggal}</div>
            <div><strong>Metode:</strong> ${tx.metode}</div>
          </div>
          <div>
            <div><strong>Nama Santri:</strong> ${santri.nama}</div>
            <div><strong>NIS:</strong> ${santri.nis} &bull; ${santri.jenjang}</div>
            <div><strong>Kamar:</strong> ${santri.kamar} &bull; Wali: ${santri.wali}</div>
          </div>
        </div>

        <table class="receipt-table">
          <thead>
            <tr>
              <th style="width:40px;">No</th>
              <th>Uraian Pembayaran</th>
              <th style="text-align:right;">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            ${(tx.items || []).map((it, idx) => `
              <tr>
                <td>${idx + 1}</td>
                <td><strong>${it.nama}</strong></td>
                <td style="text-align:right; font-weight:700;">${window.formatRupiah(it.nominal)}</td>
              </tr>
            `).join('')}
            <tr class="total-row">
              <td colspan="2" style="text-align:right;">TOTAL DITERIMA</td>
              <td style="text-align:right;">${window.formatRupiah(tx.totalBayar)}</td>
            </tr>
          </tbody>
        </table>

        <div style="font-size:0.75rem; font-style:italic; color:#475569; margin-top:8px;">
          Terbilang: <strong>${terbilangRupiah(tx.totalBayar)}</strong>
        </div>

        <div class="receipt-stamp-signature">
          <div class="receipt-stamp-box">
            <span>LUNAS<br>MH-PAJOMBLANGAN<br>TERVERIFIKASI</span>
          </div>
          <div class="receipt-sig-box">
            <div>Pajomblangan, ${tx.tanggal.slice(0, 10)}</div>
            <div style="font-size:0.72rem; color:#64748b;">Kasir / Bendahara Pondok</div>
            <div class="sig-line">${tx.kasir || info.bendahara}</div>
          </div>
        </div>
      </div>
    `;

    // Setup action buttons in modal
    const btnPrint = document.getElementById('btnPrintReceipt');
    if (btnPrint) {
      btnPrint.onclick = () => {
        window.print();
      };
    }

    const btnShareWa = document.getElementById('btnShareWaReceipt');
    if (btnShareWa) {
      btnShareWa.onclick = () => {
        const text = encodeURIComponent(
          `*BUKTI PEMBAYARAN RESMI PONPES MAMBAUL HUDA PAJOMBLANGAN*\n\n` +
          `No Kwitansi: ${tx.kode}\n` +
          `Santri: ${santri.nama} (NIS: ${santri.nis})\n` +
          `Total Bayar: ${window.formatRupiah(tx.totalBayar)}\n` +
          `Metode: ${tx.metode}\n` +
          `Waktu: ${tx.tanggal}\n` +
          `Status: LUNAS & TERVERIFIKASI SISTEM\n\n` +
          `Jazakumullah Khairan Katsiran.`
        );
        const cleanPhone = santri.noHpWali.replace(/[^0-9]/g, '');
        const targetPhone = cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone;
        window.open(`https://api.whatsapp.com/send?phone=${targetPhone}&text=${text}`, '_blank');
      };
    }

    receiptModal.classList.add('active');
  };

  window.closeModal = function(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('active');
  };

  // =========================================================================
  // 4. DATA SANTRI MANAGEMENT & BILLING
  // =========================================================================
  function setupSantriManagement() {
    const searchInput = document.getElementById('santriSearchInput');
    const filterJenjang = document.getElementById('filterJenjangSelect');
    const btnAddSantri = document.getElementById('btnAddSantriModal');
    const btnGenerateMassal = document.getElementById('btnGenerateMassal');
    const santriForm = document.getElementById('formAddSantri');

    if (searchInput) searchInput.addEventListener('input', renderSantriTable);
    if (filterJenjang) filterJenjang.addEventListener('change', renderSantriTable);

    if (btnAddSantri) {
      btnAddSantri.addEventListener('click', () => {
        const modal = document.getElementById('santriModal');
        if (modal) modal.classList.add('active');
      });
    }

    if (btnGenerateMassal) {
      btnGenerateMassal.addEventListener('click', () => {
        const nextMonth = prompt('Masukkan nama periode tagihan yang ingin di-generate massal untuk seluruh santri:', 'November 2026');
        if (nextMonth) {
          const count = window.storageManager.generateMonthlyBillingForAll(nextMonth);
          showToast(`Berhasil menerbitkan ${count} tagihan baru periode ${nextMonth} untuk seluruh santri!`, 'success');
          window.soundFx.playSuccess();
        }
      });
    }

    if (santriForm) {
      santriForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const nis = document.getElementById('formSantriNis').value.trim();
        const nama = document.getElementById('formSantriNama').value.trim();
        const gender = document.getElementById('formSantriGender').value;
        const jenjang = document.getElementById('formSantriJenjang').value;
        const kamar = document.getElementById('formSantriKamar').value.trim();
        const wali = document.getElementById('formSantriWali').value.trim();
        const hp = document.getElementById('formSantriHp').value.trim();

        if (!nis || !nama) {
          showToast('NIS dan Nama Santri wajib diisi!', 'error');
          return;
        }

        window.storageManager.saveSantri({
          nis,
          nama,
          gender,
          jenjang,
          kelas: jenjang,
          kamar: kamar || 'Kamar Al-Fath',
          wali: wali || 'Wali Santri',
          noHpWali: hp || '081234567890',
          saldoSaku: 100000,
          status: 'Aktif',
          foto: 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=150&auto=format&fit=crop&q=80'
        });

        santriForm.reset();
        window.closeModal('santriModal');
        showToast(`Data santri ${nama} berhasil ditambahkan!`, 'success');
        window.soundFx.playSuccess();
      });
    }
  }

  function renderSantriTable() {
    const tableBody = document.getElementById('santriTableBody');
    if (!tableBody) return;

    const query = document.getElementById('santriSearchInput')?.value.toLowerCase().trim() || '';
    const jenjangFilter = document.getElementById('filterJenjangSelect')?.value || 'all';

    let list = window.storageManager.getSantri();

    if (query) {
      list = list.filter(s => s.nama.toLowerCase().includes(query) || s.nis.includes(query) || s.kamar.toLowerCase().includes(query));
    }

    if (jenjangFilter !== 'all') {
      list = list.filter(s => s.jenjang === jenjangFilter);
    }

    if (list.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:var(--text-muted);">Data santri tidak ditemukan</td></tr>`;
      return;
    }

    tableBody.innerHTML = list.map((s, idx) => {
      const bills = window.storageManager.getSantriBills(s.nis);
      const unpaid = bills.filter(b => b.status !== 'Lunas');
      const isLunas = unpaid.length === 0;

      return `
        <tr>
          <td>${idx + 1}</td>
          <td>
            <div style="display:flex; align-items:center; gap:10px;">
              <img src="${s.foto}" style="width:34px; height:34px; border-radius:50%; object-fit:cover;" onerror="this.src='assets/images/ponpes_logo.jpg'">
              <div>
                <div style="font-weight:700;">${s.nama}</div>
                <small class="text-muted">${s.nis} &bull; ${s.gender}</small>
              </div>
            </div>
          </td>
          <td><strong>${s.jenjang}</strong><div style="font-size:0.75rem; color:var(--text-muted);">${s.kamar}</div></td>
          <td>
            <div>${s.wali}</div>
            <small class="text-muted">${s.noHpWali}</small>
          </td>
          <td>
            <span style="font-weight:700; color:var(--primary);">${window.formatRupiah(s.saldoSaku || 0)}</span>
          </td>
          <td>
            <span class="badge ${isLunas ? 'badge-success' : 'badge-danger'}">
              ${isLunas ? 'Lunas' : `${unpaid.length} Tagihan Tertunggak`}
            </span>
          </td>
          <td>
            <div style="display:flex; gap:6px;">
              <button class="btn btn-outline btn-sm" onclick="window.viewSantriLedger('${s.nis}')" title="Buku Kas / Kartu SPP">
                <i class="ph ph-notebook"></i> Kartu SPP
              </button>
              <button class="btn btn-primary btn-sm" onclick="window.quickPaySantri('${s.nis}')" title="Bayar Kasir">
                <i class="ph ph-cash-register"></i> Bayar
              </button>
              <button class="btn btn-outline btn-sm" onclick="window.deleteSantriConfirm('${s.nis}')" title="Hapus">
                <i class="ph ph-trash" style="color:var(--danger);"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  window.viewSantriLedger = function(nis) {
    const santri = window.storageManager.getSantriByNis(nis);
    if (!santri) return;

    const bills = window.storageManager.getSantriBills(nis);
    const transactions = window.storageManager.getTransactions().filter(t => t.nis === nis);

    const modal = document.getElementById('ledgerModal');
    const content = document.getElementById('ledgerModalContent');
    if (!modal || !content) return;

    content.innerHTML = `
      <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid var(--border-color);">
        <img src="${santri.foto}" style="width:60px; height:60px; border-radius:50%; object-fit:cover;" onerror="this.src='assets/images/ponpes_logo.jpg'">
        <div>
          <h3 style="font-size:1.15rem; font-weight:800;">${santri.nama} (${santri.nis})</h3>
          <p class="text-muted" style="font-size:0.82rem;">${santri.jenjang} &bull; ${santri.kamar} &bull; Wali: ${santri.wali}</p>
        </div>
      </div>

      <h4 style="font-size:0.95rem; font-weight:700; margin-bottom:10px;">Daftar Tagihan & Status Kelunasan</h4>
      <table class="sipensa-table" style="margin-bottom:20px;">
        <thead>
          <tr>
            <th>Pos Pembayaran</th>
            <th>Periode</th>
            <th>Nominal</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${bills.map(b => `
            <tr>
              <td><strong>${b.namaTarif}</strong></td>
              <td>${b.periode}</td>
              <td style="font-weight:700;">${window.formatRupiah(b.nominal)}</td>
              <td>
                <span class="badge ${b.status === 'Lunas' ? 'badge-success' : 'badge-danger'}">${b.status}</span>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <h4 style="font-size:0.95rem; font-weight:700; margin-bottom:10px;">Riwayat Pembayaran Santri</h4>
      <table class="sipensa-table">
        <thead>
          <tr>
            <th>No Kwitansi</th>
            <th>Tanggal</th>
            <th>Metode</th>
            <th>Jumlah</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          ${transactions.length === 0 ? `<tr><td colspan="5" style="text-align:center;">Belum ada pembayaran</td></tr>` : 
            transactions.map(t => `
              <tr>
                <td><strong>${t.kode}</strong></td>
                <td>${t.tanggal}</td>
                <td><span class="badge badge-secondary">${t.metode}</span></td>
                <td style="font-weight:700; color:var(--primary);">${window.formatRupiah(t.totalBayar)}</td>
                <td>
                  <button class="btn btn-outline btn-sm" onclick="window.viewReceipt('${t.kode}')">Kwitansi</button>
                </td>
              </tr>
            `).join('')}
        </tbody>
      </table>
    `;

    modal.classList.add('active');
  };

  window.quickPaySantri = function(nis) {
    switchTab('pos');
    selectPosSantri(nis);
  };

  window.deleteSantriConfirm = function(nis) {
    const santri = window.storageManager.getSantriByNis(nis);
    if (!santri) return;
    if (confirm(`Yakin ingin menghapus data santri ${santri.nama} (${nis})?`)) {
      window.storageManager.deleteSantri(nis);
      showToast(`Data santri ${santri.nama} berhasil dihapus`, 'info');
    }
  };

  // =========================================================================
  // 5. PORTAL WALI SANTRI MANDIRI
  // =========================================================================
  function setupPortalWali() {
    const portalInput = document.getElementById('portalNisInput');
    const portalBtn = document.getElementById('btnPortalCekNis');

    if (portalBtn && portalInput) {
      portalBtn.addEventListener('click', () => {
        const nis = portalInput.value.trim();
        if (nis) {
          selectPortalSantri(nis);
          window.soundFx.playClick();
        } else {
          showToast('Masukkan NIS santri terlebih dahulu!', 'warning');
        }
      });

      portalInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          portalBtn.click();
        }
      });
    }

    // Quick chips in portal hero
    document.querySelectorAll('.quick-chip-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const nis = btn.dataset.nis;
        if (portalInput) portalInput.value = nis;
        selectPortalSantri(nis);
        window.soundFx.playClick();
      });
    });

    // Top up e-saku button inside portal
    const btnTopUpSaku = document.getElementById('btnTopUpSaku');
    if (btnTopUpSaku) {
      btnTopUpSaku.addEventListener('click', () => {
        if (!state.portalSantri) return;
        const nominalStr = prompt(`Masukkan nominal isi saldo E-Saku untuk santri ${state.portalSantri.nama}:`, '100000');
        if (nominalStr) {
          const nominal = Number(nominalStr.replace(/[^0-9]/g, ''));
          if (nominal > 0) {
            window.storageManager.topUpSantriSaku(state.portalSantri.nis, nominal, "Top Up Online oleh Wali Santri");
            showToast(`Berhasil menambah saldo E-Saku sebesar ${window.formatRupiah(nominal)}!`, 'success');
            window.soundFx.playCashRegister();
          }
        }
      });
    }
  }

  function selectPortalSantri(nis) {
    const santri = window.storageManager.getSantriByNis(nis);
    if (!santri) {
      showToast(`Santri dengan NIS ${nis} tidak ditemukan! Cek kembali NIS.`, 'error');
      return;
    }

    state.portalSantri = santri;
    renderPortalSantriView(santri.nis);
  }

  function renderPortalSantriView(nis) {
    const santri = window.storageManager.getSantriByNis(nis);
    if (!santri) return;

    const portalResult = document.getElementById('portalResultSection');
    if (!portalResult) return;
    portalResult.style.display = 'block';

    // Santri Card
    const elNama = document.getElementById('portalSantriNama');
    const elNis = document.getElementById('portalSantriNisDisplay');
    const elKelas = document.getElementById('portalSantriKelas');
    const elKamar = document.getElementById('portalSantriKamar');
    const elSaldo = document.getElementById('portalSantriSaldo');

    if (elNama) elNama.textContent = santri.nama;
    if (elNis) elNis.textContent = `NIS: ${santri.nis}`;
    if (elKelas) elKelas.textContent = santri.jenjang;
    if (elKamar) elKamar.textContent = santri.kamar;
    if (elSaldo) elSaldo.textContent = window.formatRupiah(santri.saldoSaku || 0);

    // Render Portal Unpaid Bills
    const bills = window.storageManager.getSantriBills(nis);
    const unpaid = bills.filter(b => b.status !== 'Lunas');
    const portalBillsList = document.getElementById('portalUnpaidBillsList');

    if (portalBillsList) {
      if (unpaid.length === 0) {
        portalBillsList.innerHTML = `
          <div style="padding:20px; text-align:center; background:var(--primary-light); border-radius:var(--radius-md); color:var(--primary-dark);">
            <i class="ph ph-seal-check" style="font-size:2rem;"></i>
            <h4 style="font-weight:700; margin-top:6px;">Alhamdulillah, Semua Tagihan Lunas!</h4>
            <p style="font-size:0.8rem;">Terima kasih atas kedisiplinan Bapak/Ibu Wali Santri.</p>
          </div>
        `;
      } else {
        portalBillsList.innerHTML = unpaid.map(b => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-surface); margin-bottom:10px;">
            <div>
              <div style="font-weight:700;">${b.namaTarif}</div>
              <small class="text-muted">Periode: ${b.periode} &bull; Jatuh tempo: ${b.jatuhTempo}</small>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
              <span style="font-weight:800; color:var(--primary); font-size:1.05rem;">${window.formatRupiah(b.sisaBayar)}</span>
              <button class="btn btn-primary btn-sm" onclick="window.payFromPortal('${b.id}')">
                <i class="ph ph-qr-code"></i> Bayar Sekarang
              </button>
            </div>
          </div>
        `).join('');
      }
    }

    // Render Portal History
    const transactions = window.storageManager.getTransactions().filter(t => t.nis === nis);
    const portalHistoryList = document.getElementById('portalPaidHistoryList');

    if (portalHistoryList) {
      if (transactions.length === 0) {
        portalHistoryList.innerHTML = `<div style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada riwayat pembayaran online.</div>`;
      } else {
        portalHistoryList.innerHTML = transactions.map(t => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid var(--border-color);">
            <div>
              <div style="font-weight:700;">${t.kode}</div>
              <small class="text-muted">${t.tanggal} &bull; ${t.metode}</small>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
              <span style="font-weight:700; color:var(--primary);">${window.formatRupiah(t.totalBayar)}</span>
              <button class="btn btn-outline btn-sm" onclick="window.viewReceipt('${t.kode}')">
                <i class="ph ph-receipt"></i> Kwitansi
              </button>
            </div>
          </div>
        `).join('');
      }
    }
  }

  window.payFromPortal = function(billId) {
    if (!state.portalSantri) return;
    // Switch to POS with this santri & bill preselected
    switchTab('pos');
    selectPosSantri(state.portalSantri.nis);
    showToast(`Silakan selesaikan pembayaran via QRIS atau Virtual Account di loket virtual.`, 'info');
  };

  // =========================================================================
  // 6. LAPORAN KEUANGAN & EKSPOR
  // =========================================================================
  function setupFinancialReports() {
    const btnExportCsv = document.getElementById('btnExportCsv');
    const btnPrintLaporan = document.getElementById('btnPrintLaporan');

    if (btnExportCsv) {
      btnExportCsv.addEventListener('click', exportFinancialCsv);
    }

    if (btnPrintLaporan) {
      btnPrintLaporan.addEventListener('click', () => {
        window.print();
      });
    }
  }

  function renderLaporanKas() {
    const transactions = window.storageManager.getTransactions();
    const tableBody = document.getElementById('laporanTableBody');
    if (!tableBody) return;

    if (transactions.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px;">Belum ada catatan kas masuk</td></tr>`;
      return;
    }

    let runningTotal = 0;
    tableBody.innerHTML = transactions.map((t, idx) => {
      runningTotal += t.totalBayar;
      return `
        <tr>
          <td>${idx + 1}</td>
          <td><strong>${t.kode}</strong></td>
          <td>${t.tanggal}</td>
          <td>
            <div style="font-weight:700;">${t.namaSantri}</div>
            <small class="text-muted">NIS: ${t.nis}</small>
          </td>
          <td><span class="badge badge-secondary">${t.metode}</span></td>
          <td style="font-weight:700; color:var(--primary);">${window.formatRupiah(t.totalBayar)}</td>
          <td>${t.kasir || '-'}</td>
        </tr>
      `;
    }).join('');

    const elTotalRekap = document.getElementById('laporanTotalKasMasuk');
    if (elTotalRekap) {
      elTotalRekap.textContent = window.formatRupiah(runningTotal);
    }
  }

  function renderTransactionsTable() {
    renderLaporanKas();
  }

  function exportFinancialCsv() {
    const transactions = window.storageManager.getTransactions();
    if (transactions.length === 0) {
      showToast('Tidak ada data transaksi untuk diekspor!', 'warning');
      return;
    }

    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'No,Nomor Kwitansi,Tanggal,NIS,Nama Santri,Metode Pembayaran,Total Bayar,Kasir,Keterangan\r\n';

    transactions.forEach((t, i) => {
      const row = [
        i + 1,
        `"${t.kode}"`,
        `"${t.tanggal}"`,
        `"${t.nis}"`,
        `"${t.namaSantri}"`,
        `"${t.metode}"`,
        t.totalBayar,
        `"${t.kasir}"`,
        `"${t.keterangan || ''}"`
      ];
      csvContent += row.join(',') + '\r\n';
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `Laporan_Keuangan_Ponpes_Mambaul_Huda_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('Laporan Keuangan berhasil diekspor ke file CSV/Excel!', 'success');
  }

  // =========================================================================
  // 7. PROTOTYPE MODEL EVALUATION & METRICS (RISET SKRIPSI)
  // =========================================================================
  function setupPrototypeEvaluation() {
    const evalForm = document.getElementById('formUatEvaluation');
    if (evalForm) {
      evalForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const nama = document.getElementById('evalNama').value.trim();
        const peran = document.getElementById('evalPeran').value;
        const skorKemudahan = Number(document.getElementById('evalSkorKemudahan').value);
        const skorKecepatan = Number(document.getElementById('evalSkorKecepatan').value);
        const skorAkurasi = Number(document.getElementById('evalSkorAkurasi').value);
        const waktuLama = Number(document.getElementById('evalWaktuLama').value) || 15;
        const waktuBaru = Number(document.getElementById('evalWaktuBaru').value) || 40;
        const komentar = document.getElementById('evalKomentar').value.trim();
        const saran = document.getElementById('evalSaran').value.trim();

        if (!nama) {
          showToast('Nama responden wajib diisi!', 'error');
          return;
        }

        window.storageManager.addEvaluation({
          responden: nama,
          peran: peran,
          skorKemudahan,
          skorKecepatan,
          skorAkurasi,
          waktuLamaMenit: waktuLama,
          waktuBaruDetik: waktuBaru,
          komentar: komentar || 'Sistem prototipe sangat membantu efisiensi administrasi keuangan santri.',
          rekomendasiIterasi: saran || 'Dapat dikembangkan lebih lanjut untuk sistem absensi santri.'
        });

        evalForm.reset();
        showToast('Kuesioner evaluasi prototype berhasil disimpan!', 'success');
        window.soundFx.playSuccess();
      });
    }
  }

  function renderPrototypeEvaluations() {
    const evals = window.storageManager.getEvaluations();
    const listContainer = document.getElementById('evaluationsList');
    if (!listContainer) return;

    if (evals.length === 0) {
      listContainer.innerHTML = `<div style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data evaluasi.</div>`;
      return;
    }

    // Calculate Average SUS Score
    const avgScore = (evals.reduce((sum, ev) => sum + (ev.skorKemudahan + ev.skorKecepatan + ev.skorAkurasi) / 3, 0) / evals.length).toFixed(1);
    const elAvgScore = document.getElementById('metricAvgSusScore');
    if (elAvgScore) elAvgScore.textContent = `${avgScore} / 5.0`;

    listContainer.innerHTML = evals.map(ev => `
      <div style="background:var(--bg-body); border-radius:var(--radius-md); padding:16px; margin-bottom:12px; border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
          <div>
            <strong>${ev.responden}</strong> &bull; <span class="badge badge-secondary">${ev.peran}</span>
            <div style="font-size:0.75rem; color:var(--text-muted);">${ev.tanggal}</div>
          </div>
          <div style="text-align:right;">
            <span class="badge badge-success">Kemudahan: ${ev.skorKemudahan}/5</span>
            <span class="badge badge-success">Kecepatan: ${ev.skorKecepatan}/5</span>
          </div>
        </div>
        <p style="font-size:0.85rem; font-style:italic; margin-bottom:6px;">"${ev.komentar}"</p>
        <div style="font-size:0.78rem; color:var(--primary); font-weight:600;">
          Saran Iterasi: ${ev.rekomendasiIterasi}
        </div>
      </div>
    `).join('');
  }

  // =========================================================================
  // 8. SETTINGS & TARIFF VIEW
  // =========================================================================
  function setupSettings() {
    const btnSaveSettings = document.getElementById('btnSaveSettings');
    if (btnSaveSettings) {
      btnSaveSettings.addEventListener('click', () => {
        const info = window.storageManager.getInfo();
        info.nama = document.getElementById('settingNamaPonpes')?.value || info.nama;
        info.alamat = document.getElementById('settingAlamatPonpes')?.value || info.alamat;
        info.telepon = document.getElementById('settingTeleponPonpes')?.value || info.telepon;
        info.bendahara = document.getElementById('settingBendaharaPonpes')?.value || info.bendahara;
        info.bulanBerjalan = document.getElementById('settingBulanBerjalan')?.value || info.bulanBerjalan;

        window.storageManager.saveInfo(info);
        showToast('Pengaturan lembaga berhasil disimpan!', 'success');
        window.soundFx.playSuccess();
      });
    }
  }

  function renderSettingsView() {
    const info = window.storageManager.getInfo();
    const tariffs = window.storageManager.getTariffs();

    const inputNama = document.getElementById('settingNamaPonpes');
    const inputAlamat = document.getElementById('settingAlamatPonpes');
    const inputTelepon = document.getElementById('settingTeleponPonpes');
    const inputBendahara = document.getElementById('settingBendaharaPonpes');
    const inputBulan = document.getElementById('settingBulanBerjalan');

    if (inputNama) inputNama.value = info.nama;
    if (inputAlamat) inputAlamat.value = info.alamat;
    if (inputTelepon) inputTelepon.value = info.telepon;
    if (inputBendahara) inputBendahara.value = info.bendahara;
    if (inputBulan) inputBulan.value = info.bulanBerjalan;

    const tariffTable = document.getElementById('tariffsTableBody');
    if (tariffTable) {
      tariffTable.innerHTML = tariffs.map(t => `
        <tr>
          <td><strong>${t.kode}</strong></td>
          <td>${t.nama}</td>
          <td><span class="badge badge-secondary">${t.tipe}</span></td>
          <td style="font-weight:700; color:var(--primary);">${window.formatRupiah(t.nominal)}</td>
          <td>${t.deskripsi}</td>
        </tr>
      `).join('');
    }
  }

  // =========================================================================
  // UTILITY HELPERS
  // =========================================================================
  function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const icon = type === 'success' ? 'ph-check-circle' : type === 'error' ? 'ph-x-circle' : 'ph-info';
    toast.innerHTML = `<i class="ph ${icon}" style="font-size:1.3rem;"></i> <span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function terbilangRupiah(angka) {
    const bil = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    angka = Number(angka);
    if (angka < 12) return bil[angka] + " Rupiah";
    if (angka < 20) return terbilangRupiah(angka - 10).replace(" Rupiah", "") + " Belas Rupiah";
    if (angka < 100) return bil[Math.floor(angka / 10)] + " Puluh " + (angka % 10 !== 0 ? bil[angka % 10] : "") + " Rupiah";
    if (angka < 200) return "Seratus " + terbilangRupiah(angka - 100);
    if (angka < 1000) return bil[Math.floor(angka / 100)] + " Ratus " + terbilangRupiah(angka % 100);
    if (angka < 2000) return "Seribu " + terbilangRupiah(angka - 1000);
    if (angka < 1000000) return terbilangRupiah(Math.floor(angka / 1000)).replace(" Rupiah", "") + " Ribu " + terbilangRupiah(angka % 1000);
    if (angka < 1000000000) return terbilangRupiah(Math.floor(angka / 1000000)).replace(" Rupiah", "") + " Juta " + terbilangRupiah(angka % 1000000);
    return angka.toString() + " Rupiah";
  }
});
