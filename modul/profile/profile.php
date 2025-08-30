<div class="content flex justify-center items-start p-6 min-h-screen bg-gray-100">
    <div class="profile-card w-full max-w-lg bg-white p-6 rounded-lg shadow-lg">
        <img src="https://via.placeholder.com/120" alt="Profile Image" class="mx-auto mb-4 border-4 border-blue-500 rounded-full object-cover w-32 h-32">
        <h3 class="text-center text-lg font-semibold mb-4">Edit Profile (Dummy)</h3>

        <div class="flex gap-3 mb-4">
            <div class="flex-1 flex flex-col">
                <label class="font-bold text-gray-700 mb-1">Email</label>
                <input type="email" value="user@example.com" class="border rounded p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 flex flex-col">
                <label class="font-bold text-gray-700 mb-1">Username</label>
                <input type="text" value="Prototype" class="border rounded p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 flex flex-col">
                <label class="font-bold text-gray-700 mb-1">Tanggal Lahir</label>
                <input type="date" value="02-03-2008" class="border rounded p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <textarea placeholder="Catatan tambahan..." class="w-full border rounded p-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

    <button class="bg-blue-500 text-white w-full p-3 rounded hover:bg-blue-600 transition duration-300" onclick="alert('Profil disimpan (dummy)')">Edit</button>
    </div>
</div>
