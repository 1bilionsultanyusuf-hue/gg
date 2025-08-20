<div class="max-w-2xl mx-auto mt-8 p-4 bg-white rounded-lg shadow-lg">

    <!-- Form tambah todo -->
    <form class="flex gap-2 mb-6" method="POST" action="">
        <input type="text" name="todo_name"
            class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
            placeholder="Tambah todo baru..." required>
        <button type="submit"
            class="bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white font-bold px-4 py-2 rounded-md transition-all">
            Tambah
        </button>
    </form>

    <!-- Daftar todo -->
    <ul class="space-y-3">
        <li class="flex justify-between items-center bg-gray-50 rounded-md p-3 shadow-sm">
            <span>Belajar PHP</span>
            <div class="flex gap-2">
                <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md">Done</button>
                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md">Hapus</button>
            </div>
        </li>
        <li class="flex justify-between items-center bg-gray-100 rounded-md p-3 shadow-sm line-through text-gray-500">
            <span>Mengerjakan tugas</span>
            <div class="flex gap-2">
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md">Undo</button>
                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md">Hapus</button>
            </div>
        </li>
        <li class="flex justify-between items-center bg-gray-50 rounded-md p-3 shadow-sm">
            <span>Baca dokumentasi</span>
            <div class="flex gap-2">
                <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md">Done</button>
                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md">Hapus</button>
            </div>
        </li>
    </ul>

</div>