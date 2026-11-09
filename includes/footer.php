</div>
  <footer class="footer">© <?= date('Y') ?> Pondok Pesantren <?= e(app_school_name()) ?> · Sistem Administrasi Keuangan</footer>
</main>
</div>

<div class="premium-modal-backdrop" id="premiumConfirmModal" aria-hidden="true" role="presentation">
  <section class="premium-modal" role="dialog" aria-modal="true" aria-labelledby="premiumConfirmTitle" aria-describedby="premiumConfirmMessage">
    <button class="premium-modal-close" type="button" data-confirm-cancel aria-label="Tutup">×</button>
    <div class="premium-modal-icon warning" aria-hidden="true">!</div>
    <div class="premium-modal-kicker">KONFIRMASI TINDAKAN</div>
    <h2 id="premiumConfirmTitle">Apakah Anda yakin?</h2>
    <p id="premiumConfirmMessage">Tindakan ini akan diproses.</p>
    <div class="premium-modal-actions"><button class="btn btn-light" type="button" data-confirm-cancel>Batal</button><button class="btn btn-danger" type="button" id="premiumConfirmProceed">Ya, lanjutkan</button></div>
  </section>
</div>
<script>

(function () {
    var loader = document.getElementById('schoolLoadingScreen');
    if (!loader) return;

    function navigationIsReload() {
        try {
            var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
            return !!((nav && nav.type === 'reload') ||
                (performance.navigation && performance.navigation.type === 1));
        } catch (e) { return false; }
    }
    var afterLogin = false;
    try {
        afterLogin = sessionStorage.getItem('santriGoShowLoaderAfterLogin') === '1';
        if (afterLogin) sessionStorage.removeItem('santriGoShowLoaderAfterLogin');
    } catch (e) {}

    var shouldShow = navigationIsReload() || afterLogin;
    var startedAt = Date.now();
    var hideTimer = null;
    function hideSchoolLoader() {
        if (hideTimer) window.clearTimeout(hideTimer);
        var elapsed = Date.now() - startedAt;
        hideTimer = window.setTimeout(function () {
            loader.classList.add('is-hidden');
        }, Math.max(650, 1000 - elapsed));
    }
    if (shouldShow) {
        loader.classList.remove('is-hidden');
        if (document.readyState === 'complete') hideSchoolLoader();
        else {
            window.addEventListener('load', hideSchoolLoader, { once: true });
            window.setTimeout(hideSchoolLoader, 5000);
        }
    } else {
        loader.classList.add('is-hidden');
    }

    // Browser dapat memulihkan halaman dari back-forward cache tanpa reload penuh.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) loader.classList.add('is-hidden');
    });
})();

(function () {
    const flashModal = document.getElementById('premiumFlashModal');
    if (flashModal) {
        document.body.classList.add('premium-modal-open');
        flashModal.querySelectorAll('[data-popup-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                flashModal.classList.remove('is-open');
                document.body.classList.remove('premium-modal-open');
            });
        });
        flashModal.addEventListener('click', function (event) {
            if (event.target === flashModal) {
                flashModal.classList.remove('is-open');
                document.body.classList.remove('premium-modal-open');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && flashModal.classList.contains('is-open')) {
                flashModal.classList.remove('is-open');
                document.body.classList.remove('premium-modal-open');
            }
        });
        const closeFlash = flashModal.querySelector('[data-popup-close]');
        if (closeFlash) closeFlash.focus();
    }

    const confirmModal = document.getElementById('premiumConfirmModal');
    const proceedButton = document.getElementById('premiumConfirmProceed');
    let pendingForm = null;
    function closeConfirm() {
        if (!confirmModal) return;
        confirmModal.classList.remove('is-open');
        confirmModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('premium-modal-open');
        pendingForm = null;
    }
    document.querySelectorAll('form[onsubmit*="confirm("], form[data-confirm]').forEach(function (form) {
        const inline = form.getAttribute('onsubmit') || '';
        const match = inline.match(/confirm\(['"]([\s\S]*?)['"]\)/);
        const message = form.dataset.confirm || (match ? match[1] : 'Lanjutkan tindakan ini?');
        form.removeAttribute('onsubmit');
        form.dataset.confirm = message;
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === 'true') { form.dataset.confirmed = 'false'; return; }
            event.preventDefault();
            pendingForm = form;
            const messageNode = document.getElementById('premiumConfirmMessage');
            if (messageNode) messageNode.textContent = message;
            confirmModal.classList.add('is-open');
            confirmModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('premium-modal-open');
            if (proceedButton) proceedButton.focus();
        });
    });
    if (proceedButton) proceedButton.addEventListener('click', function () {
        if (!pendingForm) return;
        const form = pendingForm;
        form.dataset.confirmed = 'true';
        closeConfirm();
        if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
    });
    if (confirmModal) {
        confirmModal.querySelectorAll('[data-confirm-cancel]').forEach(function (button) { button.addEventListener('click', closeConfirm); });
        confirmModal.addEventListener('click', function (event) { if (event.target === confirmModal) closeConfirm(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && confirmModal.classList.contains('is-open')) closeConfirm(); });
    }
})();
(function () {
    const modal = document.getElementById('notificationModal');
    const openButton = document.getElementById('notificationOpen');
    if (!modal || !openButton) return;
    function openNotifications() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('premium-modal-open');
        const closeButton = modal.querySelector('[data-notification-close]');
        if (closeButton) closeButton.focus();
    }
    function closeNotifications() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('premium-modal-open');
        openButton.focus();
    }
    openButton.addEventListener('click', openNotifications);
    modal.querySelectorAll('[data-notification-close]').forEach(function (button) { button.addEventListener('click', closeNotifications); });
    modal.addEventListener('click', function (event) { if (event.target === modal) closeNotifications(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && modal.classList.contains('is-open')) closeNotifications(); });
})();
(function () {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButton = document.querySelector('[data-sidebar-close]');
    if (!sidebar || !overlay || !openButton) return;

    function openSidebar() {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.body.classList.add('sidebar-open');
        openButton.setAttribute('aria-expanded', 'true');
        overlay.setAttribute('aria-hidden', 'false');
        if (closeButton) closeButton.focus();
    }

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.body.classList.remove('sidebar-open');
        openButton.setAttribute('aria-expanded', 'false');
        overlay.setAttribute('aria-hidden', 'true');
    }

    openButton.addEventListener('click', openSidebar);
    if (closeButton) closeButton.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeSidebar();
    });
    sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 760) closeSidebar();
    });
})();
</script>
</body>
</html>
