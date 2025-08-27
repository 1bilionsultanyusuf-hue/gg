<main class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600 mb-2">📋 Pelaporan</h1>
            <p class="text-gray-600">Kelola dan pantau laporan masalah pengguna</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-yellow-500">1</div>
                <div class="text-gray-600">Pending</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-blue-500">1</div>
                <div class="text-gray-600">Accepted</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-green-500">1</div>
                <div class="text-gray-600">Done</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-gray-700">3</div>
                <div class="text-gray-600">Total</div>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Daftar Laporan</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">ID</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">User</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Masalah</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium">#001</td>
                            <td class="px-6 py-4 text-sm">y</td>
                            <td class="px-6 py-4 text-sm">Error saat login</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Pending</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium">#002</td>
                            <td class="px-6 py-4 text-sm">e</td>
                            <td class="px-6 py-4 text-sm">santai</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Done</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>
                                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium">#003</td>
                            <td class="px-6 py-4 text-sm">e</td>
                            <td class="px-6 py-4 text-sm">Crash</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Accepted</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button class="text-green-600 hover:text-green-800"><i class="fas fa-check"></i></button>
                                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function confirmLogout(e) {
    e.preventDefault();
    if(confirm('Yakin ingin logout?')) {
        const popup = document.createElement('div');
        popup.innerHTML = `<div class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-white p-4 rounded-lg shadow-lg z-50">
            <span class="text-green-600">✓ Logout berhasil!</span>
        </div>`;
        document.body.appendChild(popup);
        setTimeout(() => window.location.href='modul/auth/login.php', 1500);
    }
}
</script>