-- Dummy Data untuk tabel apps
INSERT INTO apps (name, description) VALUES
('Aplikasi Keuangan', 'Digunakan untuk mencatat arus kas dan laporan keuangan.'),
('Aplikasi Inventaris', 'Mencatat stok barang dan pengeluaran inventaris kantor.'),
('Aplikasi CRM', 'Mengelola interaksi dan hubungan dengan pelanggan.'),
('Aplikasi HRIS', 'Mengatur data kepegawaian dan absensi.');

-- Dummy Data untuk tabel users
INSERT INTO users (name, email, role, password) VALUES
('Budi Santoso', 'budi@example.com', 'programmer', 'hashed_pw1'),
('Siti Aminah', 'siti@example.com', 'support', 'hashed_pw2'),
('Joko Widodo', 'joko@example.com', 'admin', 'hashed_pw3'),
('Dewi Lestari', 'dewi@example.com', 'programmer', 'hashed_pw4');

-- Dummy Data untuk tabel todos
INSERT INTO todos (app_id, title, description, priority, user_id) VALUES
(1, 'Perbaiki fitur export PDF', 'Export PDF gagal jika data terlalu besar', 'high', 1),
(1, 'Tambahkan grafik arus kas', 'Menampilkan grafik bulanan untuk arus kas masuk dan keluar', 'medium', 1),
(2, 'Perbaikan filter stok', 'Filter tidak bisa memproses nama barang dengan karakter khusus', 'low', 4),
(3, 'Integrasi dengan email client', 'Menambahkan fitur notifikasi otomatis ke email pelanggan', 'high', 2),
(4, 'Bug absensi shift malam', 'Absensi shift malam tidak masuk ke laporan bulanan', 'medium', 4);

-- Dummy Data untuk tabel taken
INSERT INTO taken (id_todos, date, status, user_id) VALUES
(1, '2025-08-01', 'in_progress', 1),
(2, '2025-08-02', 'done', 1),
(3, '2025-08-03', 'in_progress', 4),
(4, '2025-08-01', 'done', 2),
(5, '2025-08-02', 'in_progress', 4);
