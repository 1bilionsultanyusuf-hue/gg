<?php
// ============================================================================
// PROSES FORM - Jika form di-submit
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_taken_submit'])) {
    
    $taken_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $catatan = trim($_POST['catatan']);
    $user_id = $_SESSION['user_id'];
    
    $errors = [];
    
    // Validasi
    if ($taken_id == 0) {
        $errors[] = "ID Taken tidak valid!";
    }
    
    if (empty($catatan)) {
        $errors[] = "Catatan penyelesaian harus diisi!";
    } elseif (strlen($catatan) < 10) {
        $errors[] = "Catatan terlalu pendek! Minimal 10 karakter.";
    }
    
    // Cek taken
    if (empty($errors)) {
        $check_query = "SELECT id, status FROM taken WHERE id = ? AND user_id = ?";
        $check_stmt = $koneksi->prepare($check_query);
        $check_stmt->bind_param("ii", $taken_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $errors[] = "Tugas tidak ditemukan!";
        } else {
            $taken_data = $check_result->fetch_assoc();
            if ($taken_data['status'] == 'done') {
                $errors[] = "Tugas sudah diselesaikan sebelumnya!";
            }
        }
        $check_stmt->close();
    }
    
    // Upload gambar (opsional)
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Tipe file tidak diizinkan!";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Ukuran file terlalu besar! Maksimal 5MB.";
        } else {
            $upload_dir = "uploads/taken_images/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = "taken_" . $taken_id . "_" . time() . "." . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            } else {
                $errors[] = "Gagal mengupload gambar!";
            }
        }
    }
    
    // Jika ada error
    if (!empty($errors)) {
        $_SESSION['form_error'] = implode("<br>", $errors);
        header("Location: index.php?page=taken_selesai&id=" . $taken_id);
        exit();
    }
    
    // Update database - Set status = done dan waktu NOW()
    $update_query = "UPDATE taken SET 
                     status = 'done', 
                     catatan = ?, 
                     image = ?,
                     updated_at = NOW(),
                     date = NOW()
                     WHERE id = ? AND user_id = ?";
    
    $update_stmt = $koneksi->prepare($update_query);
    $update_stmt->bind_param("ssii", $catatan, $image_path, $taken_id, $user_id);
    
    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        // Set success message
        $_SESSION['success_message'] = "✅ Tugas berhasil diselesaikan!";
        $update_stmt->close();
        
        // Redirect langsung ke halaman taken dengan JavaScript
        echo "<script>
            window.location.href='index.php?page=taken';
        </script>";
        exit();
    } else {
        // Set error message
        $_SESSION['error_message'] = "❌ Gagal menyelesaikan tugas!";
        
        // Hapus gambar jika upload gagal
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        
        echo "<script>
            window.location.href='index.php?page=taken_selesai&id=" . $taken_id . "';
        </script>";
        exit();
    }
    
    $update_stmt->close();
}

// ============================================================================
// TAMPILAN FORM - Di bawah ini
// ============================================================================

$taken_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taken_id == 0) {
    $_SESSION['error_message'] = "❌ ID Taken tidak valid!";
    echo "<script>window.location.href='index.php?page=taken';</script>";
    exit();
}

// Get taken data
$query = "
    SELECT tk.*, 
           td.title as todo_title,
           td.description as todo_description,
           td.priority as todo_priority,
           a.name as app_name,
           u.name as user_name
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    WHERE tk.id = ? AND tk.user_id = ?
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("ii", $taken_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "❌ Taken tidak ditemukan atau Anda tidak memiliki akses!";
    echo "<script>window.location.href='index.php?page=taken';</script>";
    exit();
}

$taken = $result->fetch_assoc();

// Ambil error dari session jika ada
$error = isset($_SESSION['form_error']) ? $_SESSION['form_error'] : '';
unset($_SESSION['form_error']);
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f6fa;
    color: #2c3e50;
}

.container-selesai {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px 30px;
}

.page-header-selesai {
    margin-bottom: 24px;
    padding: 8px 0;
}

.page-title-selesai {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 8px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #6b7280;
}

.breadcrumb a {
    color: #0d8af5;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: #0b7ad6;
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.alert-selesai {
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    animation: slideDown 0.3s ease;
}

.alert-selesai.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}

.info-section h3 {
    font-size: 1.15rem;
    color: #1f2937;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-section h3 i {
    color: #0d8af5;
}

.info-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 14px;
    margin-bottom: 14px;
}

.info-label {
    font-weight: 600;
    color: #6b7280;
    font-size: 0.95rem;
}

.info-value {
    color: #1f2937;
    font-size: 0.95rem;
}

.info-value strong {
    color: #0d8af5;
    font-size: 1.05rem;
}

.priority-badge {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 16px;
    font-size: 0.85rem;
    font-weight: 500;
}

.priority-badge.high {
    background: #fee;
    color: #e74c3c;
}

.priority-badge.medium {
    background: #fff4e6;
    color: #f39c12;
}

.priority-badge.low {
    background: #e8f5e9;
    color: #27ae60;
}

.form-section {
    margin-bottom: 24px;
}

.form-section h3 {
    font-size: 1.15rem;
    color: #1f2937;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h3 i {
    color: #27ae60;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.form-group label i {
    color: #0d8af5;
    margin-right: 6px;
}

.form-group label .required {
    color: #e74c3c;
    margin-left: 4px;
}

.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: vertical;
    min-height: 140px;
    transition: all 0.2s;
    line-height: 1.6;
}

.form-group textarea:focus {
    outline: none;
    border-color: #0d8af5;
    box-shadow: 0 0 0 3px rgba(13, 138, 245, 0.1);
}

.form-group textarea::placeholder {
    color: #9ca3af;
}

.image-upload-container {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    transition: all 0.2s;
    background: #f9fafb;
}

.image-upload-container:hover {
    border-color: #0d8af5;
    background: #f0f9ff;
}

.image-upload-container.has-file {
    border-color: #27ae60;
    background: #f0fdf4;
}

.upload-icon {
    font-size: 3rem;
    color: #9ca3af;
    margin-bottom: 12px;
}

.image-upload-container:hover .upload-icon {
    color: #0d8af5;
}

.image-upload-container.has-file .upload-icon {
    color: #27ae60;
}

.upload-text {
    margin-bottom: 12px;
}

.upload-text p {
    font-size: 0.95rem;
    color: #6b7280;
    margin-bottom: 4px;
}

.upload-text small {
    font-size: 0.85rem;
    color: #9ca3af;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    top: 0;
    left: 0;
}

.file-input-label {
    display: inline-block;
    padding: 10px 24px;
    background: #0d8af5;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}

.file-input-label:hover {
    background: #0b7ad6;
    transform: translateY(-1px);
}

.file-input-label i {
    margin-right: 8px;
}

.image-preview {
    margin-top: 16px;
    display: none;
}

.image-preview.show {
    display: block;
}

.preview-container {
    position: relative;
    display: inline-block;
    max-width: 100%;
}

.preview-image {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #e74c3c;
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.remove-image:hover {
    background: #c0392b;
    transform: scale(1.1);
}

.file-name {
    margin-top: 12px;
    padding: 8px 12px;
    background: #e5e7eb;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #374151;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.file-name i {
    color: #27ae60;
}

.button-group {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid #e5e7eb;
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn i {
    font-size: 1rem;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
    transform: translateY(-1px);
}

.btn-primary {
    background: #27ae60;
    color: white;
    box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
}

.btn-primary:hover {
    background: #229954;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .container-selesai {
        padding: 16px 20px;
    }
    
    .content-card {
        padding: 24px 20px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 6px;
    }
    
    .info-label {
        font-size: 0.85rem;
        font-weight: 700;
    }
    
    .info-value {
        margin-bottom: 12px;
    }
    
    .button-group {
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .image-upload-container {
        padding: 20px 16px;
    }
    
    .upload-icon {
        font-size: 2.5rem;
    }
}
</style>

<div class="container-selesai">
    <!-- Page Header -->
    <div class="page-header-selesai">
        <h1 class="page-title-selesai">Selesaikan Tugas</h1>
        <div class="breadcrumb">
            <a href="index.php?page=taken">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert-selesai alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Content Card -->
    <div class="content-card">
        <!-- Info Section -->
        <div class="info-section">
            <h3><i class="fas fa-info-circle"></i> Informasi Tugas</h3>
            
            <div class="info-grid">
                <div class="info-label">Judul Todo:</div>
                <div class="info-value"><strong><?= htmlspecialchars($taken['todo_title']) ?></strong></div>
            </div>
            
            <div class="info-grid">
                <div class="info-label">Aplikasi:</div>
                <div class="info-value"><?= htmlspecialchars($taken['app_name']) ?></div>
            </div>
            
            <div class="info-grid">
                <div class="info-label">Prioritas:</div>
                <div class="info-value">
                    <span class="priority-badge <?= strtolower($taken['todo_priority']) ?>">
                        <?= ucfirst($taken['todo_priority']) ?>
                    </span>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-label">Deskripsi:</div>
                <div class="info-value"><?= nl2br(htmlspecialchars($taken['todo_description'])) ?></div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <h3><i class="fas fa-edit"></i> Lengkapi Detail Penyelesaian</h3>
            
            <form method="POST" action="index.php?page=taken_selesai&id=<?= $taken_id ?>" enctype="multipart/form-data" id="completeForm">
                <!-- Catatan -->
                <div class="form-group">
                    <label for="catatan">
                        <i class="fas fa-sticky-note"></i> Catatan Penyelesaian<span class="required">*</span>
                    </label>
                    <textarea 
                        id="catatan" 
                        name="catatan" 
                        required
                        placeholder="Jelaskan detail pekerjaan yang telah diselesaikan, kendala yang dihadapi, hasil yang dicapai, atau catatan lainnya..."></textarea>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i> Upload Gambar <small>(Opsional)</small>
                    </label>
                    <div class="image-upload-container" id="uploadContainer">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <p><strong>Klik untuk memilih gambar</strong> atau drag & drop</p>
                            <small>Format: JPG, PNG, GIF • Maksimal 5MB</small>
                        </div>
                        <div class="file-input-wrapper">
                            <input 
                                type="file" 
                                id="imageInput" 
                                name="image" 
                                accept="image/jpeg,image/jpg,image/png,image/gif"
                                onchange="handleFileSelect(event)">
                            <label for="imageInput" class="file-input-label">
                                <i class="fas fa-folder-open"></i> Pilih Gambar
                            </label>
                        </div>
                        
                        <div class="image-preview" id="imagePreview">
                            <div class="preview-container">
                                <img id="previewImg" class="preview-image" src="" alt="Preview">
                                <button type="button" class="remove-image" onclick="removeImage()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="file-name" id="fileName"></div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <a href="index.php?page=taken" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" name="complete_taken_submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Selesaikan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleFileSelect(event) {
    const file = event.target.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal 5MB.');
            event.target.value = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipe file tidak diizinkan! Hanya JPG, PNG, atau GIF.');
            event.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.add('show');
            document.getElementById('uploadContainer').classList.add('has-file');
            document.getElementById('fileName').innerHTML = '<i class="fas fa-check-circle"></i> ' + file.name;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').classList.remove('show');
    document.getElementById('uploadContainer').classList.remove('has-file');
    document.getElementById('previewImg').src = '';
    document.getElementById('fileName').innerHTML = '';
}

const uploadContainer = document.getElementById('uploadContainer');

uploadContainer.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = '#0d8af5';
    this.style.background = '#f0f9ff';
});

uploadContainer.addEventListener('dragleave', function(e) {
    e.preventDefault();
    if (!this.classList.contains('has-file')) {
        this.style.borderColor = '#d1d5db';
        this.style.background = '#f9fafb';
    }
});

uploadContainer.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '#d1d5db';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('imageInput').files = files;
        handleFileSelect({ target: { files: files } });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-selesai');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Konfirmasi sebelum submit
document.getElementById('completeForm').addEventListener('submit', function(e) {
    const catatan = document.getElementById('catatan').value.trim();
    
    if (catatan.length < 10) {
        e.preventDefault();
        alert('Catatan terlalu pendek! Minimal 10 karakter.');
        document.getElementById('catatan').focus();
        return false;
    }
    
    // Konfirmasi tandai selesai
    if (!confirm('Tandai tugas ini sebagai selesai?')) {
        e.preventDefault();
        return false;
    }
});
</script>