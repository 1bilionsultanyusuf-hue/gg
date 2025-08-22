<?php
$logoutMessage = '';
if(isset($_GET['logout'])){
    $logoutMessage = "Anda berhasil logout!";
}
?>

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
            <a href="index.php?page=settings&logout=true"
               onclick="return confirm('Yakin mau logout?')"
               class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
               Logout
            </a>

            <?php if($logoutMessage): ?>
                <div class="mt-4 bg-blue-100 text-blue-800 p-3 rounded shadow">
                    <?= $logoutMessage ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>