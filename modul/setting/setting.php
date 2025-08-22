 <main class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-blue-500 mb-8 text-center">⚙️ Pengaturan</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Profil -->
        <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h2 class="text-xl font-semibold mb-2">Profil</h2>
            <p>Ubah informasi akun Anda</p>
            <a href="index.php?page=profile" class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Edit Profil</a>
        </div>

        <!-- Logout -->
        <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h2 class="text-xl font-semibold mb-2 text-red-600">Logout</h2>
            <p>Keluar dari akun Anda saat ini</p>
            <a href="#"
               onclick="return confirmLogout(event)"
               class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
               Logout
            </a>
        </div>
    </div>
</main>

<script>
function confirmLogout(e){
    e.preventDefault();
    if(confirm('YANG BENER 😏?')){
        // Pop-up animasi
        const popup = document.createElement('div');
        popup.className = 'popup-logout';
        popup.innerHTML = `<div class="popup-circle"><div class="checkmark">✔</div></div><div class="popup-text">Anda berhasil logout!</div>`;
        document.body.appendChild(popup);

        // CSS animasi popup
        const style = document.createElement('style');
        style.innerHTML = `
        .popup-logout {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            font-weight: bold;
            font-family: 'Segoe UI', sans-serif;
            color: #16a34a;
            z-index: 9999;
            opacity: 1;
            transition: all 0.3s ease;
        }
        .popup-circle {
            width: 28px;
            height: 28px;
            border: 3px solid #16a34a;
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
            color: #16a34a;
            animation: scaleCheck 0.5s 0.5s forwards;
        }
        .popup-text { font-size: 14px; }
        @keyframes spin { 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
        @keyframes scaleCheck { 0%{transform:translate(-50%,-50%) scale(0);} 50%{transform:translate(-50%,-50%) scale(1.2);} 100%{transform:translate(-50%,-50%) scale(1);} }
        `;
        document.head.appendChild(style);

        // Fade out & redirect ke login sesuai path proyekmu
        setTimeout(() => { popup.style.opacity = '0'; }, 1800);
        setTimeout(() => { window.location.href='modul/auth/login.php'; }, 2000);
    }
    return false;
}
</script>