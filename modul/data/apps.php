<section class="text-center mb-10">
    <div class="flex items-center justify-center mb-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
        </div>
    </div>
    <h1 class="text-3xl font-bold text-blue-600 mb-2">About Application</h1>
    <p class="text-gray-600 text-lg">Tentang aplikasi dan pengembangnya</p>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="stat-card">
        <div class="flex items-center mb-4">
            <div class="stat-icon bg-blue-100 text-blue-600">
                <i class="fas fa-code"></i>
            </div>
            <h2 class="text-xl font-semibold text-blue-600 ml-3">Aplikasi Support & Coding</h2>
        </div>
        <p class="text-gray-700 text-sm leading-relaxed">
            Aplikasi ini dibuat untuk membantu divisi support & coding memantau dan mengelola permintaan user dengan mudah, cepat, dan rapi. Semua data diatur secara manual tanpa koneksi database.
        </p>
        <div class="mt-4 flex items-center text-sm text-gray-500">
            <i class="fas fa-calendar-alt mr-2"></i>
            <span>Dibuat tahun <?= date('Y') ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center mb-4">
            <div class="stat-icon bg-green-100 text-green-600">
                <i class="fas fa-users"></i>
            </div>
            <h2 class="text-xl font-semibold text-green-600 ml-3">Pengembang</h2>
        </div>
        <p class="text-gray-700 text-sm leading-relaxed">
            Dibuat oleh <strong>Boby</strong> dengan bantuan <strong>Connor</strong>. Tujuan pembuatan aplikasi ini adalah untuk belajar pemrograman PHP dan membuat aplikasi internal yang efektif.
        </p>
        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center text-sm text-gray-500">
                <i class="fas fa-user-tie mr-2"></i>
                <span>Tim Development</span>
            </div>
            <div class="flex space-x-2">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">B</div>
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-bold">C</div>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center mb-4">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <i class="fas fa-star"></i>
            </div>
            <h2 class="text-xl font-semibold text-purple-600 ml-3">Fitur Utama</h2>
        </div>
        <ul class="text-gray-700 text-sm space-y-2">
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Form input masalah user
            </li>
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Status: Pending, Accept, Done
            </li>
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Halaman admin dan user
            </li>
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Tampilan modern & responsif
            </li>
        </ul>
    </div>

    <!-- Technology Stack Card -->
    <div class="stat-card md:col-span-2 lg:col-span-2">
        <div class="flex items-center mb-4">
            <div class="stat-icon bg-orange-100 text-orange-600">
                <i class="fas fa-layer-group"></i>
            </div>
            <h2 class="text-xl font-semibold text-orange-600 ml-3">Technology Stack</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <i class="fab fa-php text-3xl text-purple-600 mb-2"></i>
                <p class="text-sm font-medium">PHP</p>
                <p class="text-xs text-gray-500">Backend</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <i class="fab fa-html5 text-3xl text-orange-600 mb-2"></i>
                <p class="text-sm font-medium">HTML5</p>
                <p class="text-xs text-gray-500">Structure</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <i class="fab fa-css3-alt text-3xl text-blue-600 mb-2"></i>
                <p class="text-sm font-medium">CSS3</p>
                <p class="text-xs text-gray-500">Styling</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <i class="fab fa-js-square text-3xl text-yellow-600 mb-2"></i>
                <p class="text-sm font-medium">JavaScript</p>
                <p class="text-xs text-gray-500">Interactive</p>
            </div>
        </div>
        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <p class="text-sm text-blue-800">
                    <strong>Framework:</strong> Tailwind CSS untuk styling modern dan responsif
                </p>
            </div>
        </div>
    </div>

    <!-- System Information Card -->
    <div class="stat-card">
        <div class="flex items-center mb-4">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <i class="fas fa-server"></i>
            </div>
            <h2 class="text-xl font-semibold text-indigo-600 ml-3">System Info</h2>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Version</span>
                <span class="text-sm font-medium">v1.0.0</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">PHP Version</span>
                <span class="text-sm font-medium"><?= phpversion() ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Last Update</span>
                <span class="text-sm font-medium"><?= date('M Y') ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-circle text-green-400 mr-1" style="font-size: 0.5rem;"></i>
                    Active
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Contact Section -->
<div class="mt-8 stat-card">
    <div class="text-center">
        <div class="flex items-center justify-center mb-4">
            <div class="stat-icon bg-gray-100 text-gray-600">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Need Support?</h3>
        <p class="text-gray-600 mb-4">
            Jika Anda memiliki pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi tim development.
        </p>
        <div class="flex justify-center space-x-4">
            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center" onclick="alert('Contact feature coming soon!')">
                <i class="fas fa-phone mr-2"></i>
                Contact Support
            </button>
            <button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center" onclick="alert('Documentation coming soon!')">
                <i class="fas fa-book mr-2"></i>
                Documentation
            </button>
        </div>
    </div>
</div>