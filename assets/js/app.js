document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar && sidebar.classList.add('open');
        overlay && overlay.classList.add('open');
    }
    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        overlay && overlay.classList.remove('open');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) { closeSidebar(); } else { openSidebar(); }
        });
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                var instance = bootstrap.Alert.getOrCreateInstance(el);
                instance.close();
            } else {
                el.style.display = 'none';
            }
        }, 5000);
    });
});
