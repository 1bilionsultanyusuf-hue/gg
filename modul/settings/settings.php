<div class="settings-wrapper">
    <!-- Header Section -->
    <div class="settings-header">
        <div class="d-flex align-items-center mb-3">
            <div class="settings-icon me-3">
                <i class="bi bi-gear"></i>
            </div>
            <div>
                <h2 class="mb-1">Pengaturan</h2>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="settings-tabs mb-4">
        <ul class="nav nav-pills nav-fill" id="settingsTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="pill" href="#general">
                    <i class="bi bi-sliders me-2"></i>Umum
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#account">
                    <i class="bi bi-person me-2"></i>Akun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#notifications">
                    <i class="bi bi-bell me-2"></i>Notifikasi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#security">
                    <i class="bi bi-shield-lock me-2"></i>Keamanan
                </a>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- General Settings Tab -->
        <div class="tab-pane fade show active" id="general">
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="bi bi-sliders me-2"></i>Pengaturan Umum
                </h4>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Nama Aplikasi</h6>
                        <p>Ubah nama aplikasi yang ditampilkan</p>
                    </div>
                    <div class="setting-control">
                        <input type="text" class="form-control" value="Aplikasi Sederhana" placeholder="Masukkan nama aplikasi">
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Bahasa</h6>
                        <p>Pilih bahasa untuk antarmuka aplikasi</p>
                    </div>
                    <div class="setting-control">
                        <select class="form-select">
                            <option value="id" selected>🇮🇩 Bahasa Indonesia</option>
                            <option value="en">🇺🇸 English</option>
                            <option value="ms">🇲🇾 Bahasa Malaysia</option>
                        </select>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Mode Gelap</h6>
                        <p>Aktifkan tema gelap untuk kenyamanan mata</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle">
                            <label class="form-check-label" for="darkModeToggle">
                                Aktifkan Mode Gelap
                            </label>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Jumlah Data per Halaman</h6>
                        <p>Tentukan berapa banyak data yang ditampilkan</p>
                    </div>
                    <div class="setting-control">
                        <select class="form-select">
                            <option value="10">10 item</option>
                            <option value="25" selected>25 item</option>
                            <option value="50">50 item</option>
                            <option value="100">100 item</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Settings Tab -->
        <div class="tab-pane fade" id="account">
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="bi bi-person me-2"></i>Pengaturan Akun
                </h4>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Informasi Profil</h6>
                        <p>Perbarui informasi personal Anda</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Profil
                        </button>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Foto Profil</h6>
                        <p>Ganti foto profil Anda</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-camera me-2"></i>Ubah Foto
                        </button>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Email</h6>
                        <p>Alamat email untuk login dan notifikasi</p>
                    </div>
                    <div class="setting-control">
                        <input type="email" class="form-control" value="user@example.com" readonly>
                        <small class="text-muted">Hubungi admin untuk mengubah email</small>
                    </div>
                </div>

                <div class="setting-item danger-zone">
                    <div class="setting-info">
                        <h6 class="text-danger">Hapus Akun</h6>
                        <p>Hapus akun Anda secara permanen</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>Hapus Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div class="tab-pane fade" id="notifications">
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="bi bi-bell me-2"></i>Pengaturan Notifikasi
                </h4>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Notifikasi Email</h6>
                        <p>Terima pemberitahuan melalui email</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                            <label class="form-check-label" for="emailNotif">
                                Aktifkan Email
                            </label>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Notifikasi Browser</h6>
                        <p>Tampilkan notifikasi di browser</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="browserNotif">
                            <label class="form-check-label" for="browserNotif">
                                Aktifkan Browser
                            </label>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Notifikasi SMS</h6>
                        <p>Terima SMS untuk update penting</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smsNotif">
                            <label class="form-check-label" for="smsNotif">
                                Aktifkan SMS
                            </label>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Suara Notifikasi</h6>
                        <p>Putar suara saat ada notifikasi</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="soundNotif" checked>
                            <label class="form-check-label" for="soundNotif">
                                Aktifkan Suara
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security">
            <div class="settings-section">
                <h4 class="section-title">
                    <i class="bi bi-shield-lock me-2"></i>Pengaturan Keamanan
                </h4>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Ganti Password</h6>
                        <p>Perbarui password untuk keamanan akun</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-primary">
                            <i class="bi bi-key me-2"></i>Ubah Password
                        </button>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Otentikasi 2 Faktor</h6>
                        <p>Tambahan lapisan keamanan untuk login</p>
                    </div>
                    <div class="setting-control">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                            <label class="form-check-label" for="twoFactorAuth">
                                Aktifkan 2FA
                            </label>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Timeout Sesi</h6>
                        <p>Waktu hingga logout otomatis (menit)</p>
                    </div>
                    <div class="setting-control">
                        <select class="form-select">
                            <option value="15">15 menit</option>
                            <option value="30" selected>30 menit</option>
                            <option value="60">1 jam</option>
                            <option value="120">2 jam</option>
                        </select>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h6>Riwayat Login</h6>
                        <p>Lihat aktivitas login terbaru</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-outline-info">
                            <i class="bi bi-clock-history me-2"></i>Lihat Riwayat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="settings-actions">
        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-outline-warning" onclick="resetSettings()">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset ke Default
            </button>
            <div class="action-buttons">
                <button class="btn btn-secondary me-2" onclick="cancelChanges()">
                    <i class="bi bi-x-circle me-2"></i>Batal
                </button>
                <button class="btn btn-success" onclick="saveSettings()">
                    <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="settingsMessage" class="alert alert-success d-none mt-3">
        <i class="bi bi-check-circle me-2"></i>
        Pengaturan berhasil disimpan!
    </div>
</div>

<style>
/* Settings Wrapper */
.settings-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

/* Header Styling */
.settings-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid #dee2e6;
}

.settings-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.settings-header h2 {
    color: #495057;
    font-weight: 700;
    margin: 0;
}

/* Tabs Styling */
.settings-tabs .nav-pills .nav-link {
    background: transparent;
    border: 2px solid #dee2e6;
    color: #495057;
    font-weight: 500;
    border-radius: 10px;
    margin: 0 5px;
    transition: all 0.3s ease;
}

.settings-tabs .nav-pills .nav-link:hover {
    background: #f8f9fa;
    border-color: #007bff;
    transform: translateY(-2px);
}

.settings-tabs .nav-pills .nav-link.active {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-color: #007bff;
    color: white;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

/* Section Styling */
.settings-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

/* Setting Item Styling */
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.setting-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.setting-info {
    flex: 1;
    margin-right: 2rem;
}

.setting-info h6 {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: #343a40;
    font-size: 1rem;
}

.setting-info p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
}

.setting-control {
    min-width: 250px;
}

.setting-control .form-control,
.setting-control .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.setting-control .form-control:focus,
.setting-control .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Switch Styling */
.form-check-input {
    width: 3rem;
    height: 1.5rem;
    border-radius: 1rem;
    background-color: #dee2e6;
    border: none;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
}

.form-check-label {
    font-weight: 500;
    color: #495057;
    margin-left: 0.5rem;
}

/* Button Styling */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
    border-width: 2px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-color: #007bff;
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    border-color: #28a745;
}

.btn-outline-primary {
    color: #007bff;
    border-color: #007bff;
}

.btn-outline-primary:hover {
    background: #007bff;
    border-color: #007bff;
}

/* Danger Zone */
.danger-zone {
    background: #fff5f5;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #fed7d7;
    margin-top: 1rem;
}

.danger-zone .setting-info h6 {
    color: #e53e3e !important;
}

/* Actions Section */
.settings-actions {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .setting-item {
        flex-direction: column;
        align-items: stretch;
    }
    
    .setting-info {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .setting-control {
        min-width: auto;
    }
    
    .settings-actions .d-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .settings-tabs .nav-pills {
        flex-direction: column;
    }
    
    .settings-tabs .nav-pills .nav-link {
        margin: 5px 0;
        text-align: center;
    }
}

/* Alert Styling */
.alert {
    border-radius: 10px;
    border: none;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

/* Loading State */
.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: button-loading-spinner 1s ease infinite;
}

@keyframes button-loading-spinner {
    from { transform: rotate(0turn); }
    to { transform: rotate(1turn); }
}
</style>

<script>
// JavaScript Functions
function saveSettings() {
    const btn = document.querySelector('.btn-success');
    const message = document.getElementById('settingsMessage');
    
    // Show loading state
    btn.classList.add('loading');
    btn.disabled = true;
    
    // Simulate save process
    setTimeout(() => {
        btn.classList.remove('loading');
        btn.disabled = false;
        
        // Show success message
        message.classList.remove('d-none');
        setTimeout(() => {
            message.classList.add('d-none');
        }, 3000);
        
        console.log('Settings saved successfully!');
    }, 1500);
}

function cancelChanges() {
    if (confirm('Apakah Anda yakin ingin membatalkan perubahan?')) {
        location.reload();
    }
}

function resetSettings() {
    if (confirm('Apakah Anda yakin ingin mereset ke pengaturan default?')) {
        // Reset all form elements
        document.querySelectorAll('input[type="text"], input[type="email"]').forEach(input => {
            input.value = input.defaultValue;
        });
        
        document.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = checkbox.hasAttribute('checked');
        });
        
        console.log('Settings reset to default!');
    }
}

// Dark mode toggle functionality
document.getElementById('darkModeToggle').addEventListener('change', function() {
    if (this.checked) {
        document.body.classList.add('dark-mode');
        console.log('Dark mode enabled');
    } else {
        document.body.classList.remove('dark-mode');
        console.log('Dark mode disabled');
    }
});

// Notification permission for browser notifications
document.getElementById('browserNotif').addEventListener('change', function() {
    if (this.checked && 'Notification' in window) {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission !== 'granted') {
                    this.checked = false;
                    alert('Izin notifikasi browser diperlukan untuk fitur ini');
                }
            });
        } else if (Notification.permission === 'denied') {
            this.checked = false;
            alert('Notifikasi browser diblokir. Silakan aktifkan di pengaturan browser.');
        }
    }
});

// Tab switching with URL hash support
document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (e) {
        const targetHash = e.target.getAttribute('href').substring(1);
        history.pushState(null, null, `#${targetHash}`);
    });
});

// Load tab from URL hash on page load
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[href="${hash}"]`);
        if (tab) {
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
});
</script>