document.addEventListener("DOMContentLoaded", function () {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const overlay = document.getElementById('overlay');
    const submenuData = document.getElementById("dataSubmenu");
    let pendingMenuItem = null;

    toggleSidebar.addEventListener('click', function () {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        content.classList.toggle('ml-64');
        content.classList.toggle('ml-20');
        overlay.style.display = content.classList.contains('ml-20') ? 'block' : 'none';

        //  Kalau sidebar ditutup → submenu Data otomatis ikut collapse
        if (isCollapsed) {
            submenuData.classList.add("hidden");
            localStorage.setItem("dataMenuOpen", "false");
        }
    });

    overlay.addEventListener('click', function() {
        sidebar.classList.remove('collapsed');
        content.classList.remove('ml-20');
        content.classList.add('ml-64');
        overlay.style.display = 'none';
    });

    // ====== Perbaikan Klik Menu saat Sidebar Collapsed + Auto Focus ======
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function (e) {
            if (sidebar.classList.contains('collapsed')) {
                e.preventDefault();
                pendingMenuItem = this;
                sidebar.classList.remove('collapsed');
                content.classList.remove('ml-20');
                content.classList.add('ml-64');
                overlay.style.display = 'none';
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

    // Toggle submenu Data
    const toggleData = document.getElementById("toggleDataMenu");

    toggleData.addEventListener("click", function(e) {
        e.preventDefault();
        submenuData.classList.toggle("hidden");
        localStorage.setItem("dataMenuOpen", submenuData.classList.contains("hidden") ? "false" : "true");
    });

    if (localStorage.getItem("dataMenuOpen") === "true") {
        submenuData.classList.remove("hidden");
    }
});