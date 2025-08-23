document.addEventListener("DOMContentLoaded", function () {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const overlay = document.getElementById('overlay');
    const submenuData = document.getElementById("dataSubmenu");
    const toggleData = document.getElementById("toggleDataMenu");
    let pendingMenuItem = null;

    // === Toggle Sidebar ===
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function () {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            content.classList.toggle('ml-64');
            content.classList.toggle('ml-20');

            if (overlay) {
                overlay.style.display = content.classList.contains('ml-20') ? 'block' : 'none';
            }

            // Kalau sidebar ditutup → submenu Data ikut collapse
            if (isCollapsed && submenuData) {
                submenuData.classList.add("hidden");
                localStorage.setItem("dataMenuOpen", "false");
            }
        });
    }

    // === Overlay Click ===
    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('collapsed');
            content.classList.remove('ml-20');
            content.classList.add('ml-64');
            overlay.style.display = 'none';
        });
    }

    // === Klik Menu saat Sidebar Collapsed ===
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function (e) {
            if (sidebar.classList.contains('collapsed')) {
                e.preventDefault();
                pendingMenuItem = this;
                sidebar.classList.remove('collapsed');
                content.classList.remove('ml-20');
                content.classList.add('ml-64');
                if (overlay) overlay.style.display = 'none';

                // highlight menu aktif
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            } else {
                if (pendingMenuItem === this) {
                    window.location.href = this.getAttribute('href');
                } else {
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                }
                pendingMenuItem = null;
            }
        });
    });

    // === Toggle submenu Data ===
    if (toggleData && submenuData) {
        toggleData.addEventListener("click", function (e) {
            e.preventDefault();
            submenuData.classList.toggle("hidden");
            localStorage.setItem("dataMenuOpen", submenuData.classList.contains("hidden") ? "false" : "true");
        });

        // Restore state dari localStorage
        if (localStorage.getItem("dataMenuOpen") === "true") {
            submenuData.classList.remove("hidden");
        }
    }
});