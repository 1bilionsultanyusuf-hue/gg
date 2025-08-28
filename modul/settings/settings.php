<?php
// Mulai session hanya jika belum aktif
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua session
session_unset();
session_destroy();
?>

<main class="container mx-auto px-4 py-10">
    <div class="max-w-md mx-auto">
        <h1 class="text-3xl font-bold text-blue-500 mb-8 text-center">⚙️ Pengaturan</h1>

        <!-- Logout Card -->
        <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-semibold mb-2 text-red-600">Logout</h2>
                <p class="text-gray-600">Keluar dari akun Anda saat ini</p>
            </div>
            
            <button onclick="confirmLogout(event)"
                    class="w-full bg-red-500 hover:bg-red-600 text-white py-4 px-6 rounded-lg font-medium transition-colors text-lg">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </button>
        </div>
    </div>
</main>

<script>
function confirmLogout(e) {
    e.preventDefault();

    if(confirm('YANG BENER 😏?')) {
        const popup = document.createElement('div');
        popup.className = 'popup-logout';
        popup.innerHTML = `<div class="popup-circle"><div class="checkmark">✔</div></div><div class="popup-text">Anda berhasil logout!</div>`;
        document.body.appendChild(popup);

        const style = document.createElement('style');
        style.innerHTML = `
        .popup-logout {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            font-weight: bold;
            font-family: 'Segoe UI', sans-serif;
            color: #10b981;
            z-index: 9999;
            opacity: 1;
            transition: all 0.3s ease;
        }
        .popup-circle {
            width: 30px;
            height: 30px;
            border: 3px solid #10b981;
            border-radius: 50%;
            position: relative;
            animation: spin 0.5s ease forwards;
        }
        .checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 16px;
            color: #10b981;
            animation: scaleCheck 0.5s 0.5s forwards;
        }
        .popup-text { font-size: 14px; }
        @keyframes spin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
        @keyframes scaleCheck { 0%{transform:translate(-50%,-50%) scale(0);} 50%{transform:translate(-50%,-50%) scale(1.2);} 100%{transform:translate(-50%,-50%) scale(1);} }
        `;
        document.head.appendChild(style);

        setTimeout(() => { popup.style.opacity = '0'; }, 1800);
        setTimeout(() => { window.location.href='modul/auth/login.php'; }, 2000);
    }

    return false;
}
</script>
