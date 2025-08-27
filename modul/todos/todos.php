<main class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600 mb-2">📝 Todo List</h1>
            <p class="text-gray-600">Kelola tugas dan aktivitas harian</p>
        </div>

        <!-- Add Todo Form -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6">
            <form class="flex gap-3" method="POST">
                <input type="text" name="todo_name" 
                       placeholder="Tambah tugas baru..." 
                       class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       required>
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tambah
                </button>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-blue-500">2</div>
                <div class="text-gray-600">Active</div>
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

        <!-- Todo List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Daftar Tugas</h2>
            </div>
            
            <div class="p-6">
                <ul class="space-y-4">
                    <!-- Active Todo -->
                    <li class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 border-2 border-blue-500 rounded"></div>
                            <span class="text-gray-800 font-medium">Belajar PHP</span>
                        </div>
                        <div class="flex gap-2">
                            <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-check mr-1"></i>Done
                            </button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>

                    <!-- Completed Todo -->
                    <li class="flex items-center justify-between p-4 bg-green-50 rounded-lg border-l-4 border-green-500 opacity-75">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 bg-green-500 rounded flex items-center justify-center">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-gray-600 line-through">Mengerjakan tugas</span>
                        </div>
                        <div class="flex gap-2">
                            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-undo mr-1"></i>Undo
                            </button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>

                    <!-- Active Todo -->
                    <li class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 border-2 border-blue-500 rounded"></div>
                            <span class="text-gray-800 font-medium">Baca dokumentasi</span>
                        </div>
                        <div class="flex gap-2">
                            <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-check mr-1"></i>Done
                            </button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
// Simple todo interactions
document.addEventListener('click', function(e) {
    if (e.target.closest('.bg-green-500')) {
        const todoItem = e.target.closest('li');
        todoItem.classList.add('opacity-75');
        todoItem.querySelector('span').classList.add('line-through', 'text-gray-600');
        todoItem.querySelector('.w-4').innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
        todoItem.querySelector('.w-4').classList.add('bg-green-500');
    }
});
</script>