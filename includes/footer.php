</div>
  <footer class="footer">© <?= date('Y') ?> Pondok Pesantren Mambaul Huda · Sistem Administrasi Keuangan</footer>
</main>
</div>
<script>
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
