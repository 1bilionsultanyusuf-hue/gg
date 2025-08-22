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
                <input type="date" value="2000-01-01" class="border rounded p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <textarea placeholder="Catatan tambahan..." class="w-full border rounded p-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

        <button class="bg-blue-500 text-white w-full p-3 rounded hover:bg-blue-600 transition duration-300" onclick="alert('Profil disimpan (dummy)')">Simpan</button>
        <a href="index.php?page=settings"
   class="mt-4 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-white shadow-md transition-all duration-500"
   style="background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet); 
          background-size: 400% 400%; 
          animation: rainbowHover 3s linear infinite;">
   <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
   </svg>
   Kembali ke Setting
</a>

<style>
@keyframes rainbowHover {
  0%{background-position:0% 50%}
  50%{background-position:100% 50%}
  100%{background-position:0% 50%}
}
</style>
    </div>
</div>