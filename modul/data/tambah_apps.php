<?php
// File: tambah_apps.php
$message = '';
$error = '';
$mode = 'add_app'; // Default mode
$edit_app_data = null;
$todo_app_id = null;
$todo_app_name = '';

// Determine mode
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $mode = 'edit_app';
        $edit_id = intval($_GET['id']);
        $edit_query = $koneksi->prepare("SELECT * FROM apps WHERE id = ?");
        $edit_query->bind_param("i", $edit_id);
        $edit_query->execute();
        $edit_app_data = $edit_query->get_result()->fetch_assoc();
        
        if (!$edit_app_data) {
            echo "<script>window.location.href = '?page=apps';</script>";
            exit;
        }
    } elseif ($_GET['action'] == 'add_todo' && isset($_GET['app_id'])) {
        $mode = 'add_todo';
        $todo_app_id = intval($_GET['app_id']);
        $todo_app_name = isset($_GET['app_name']) ? $_GET['app_name'] : '';
        
        // Verify app exists
        $check_app = $koneksi->prepare("SELECT name FROM apps WHERE id = ?");
        $check_app->bind_param("i", $todo_app_id);
        $check_app->execute();
        $app_result = $check_app->get_result();
        
        if ($app_result->num_rows == 0) {
            echo "<script>window.location.href = '?page=apps';</script>";
            exit;
        }
        
        if (empty($todo_app_name)) {
            $todo_app_name = $app_result->fetch_assoc()['name'];
        }
    }
}

// CREATE - Add new app
if (isset($_POST['add_app'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $koneksi->prepare("INSERT INTO apps (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Aplikasi '$name' berhasil ditambahkan!";
            $message = "Data berhasil disimpan!";
            echo "<script>
                setTimeout(function() {
                    window.location.href = '?page=apps';
                }, 1500);
            </script>";
        } else {
            $error = "Gagal menambahkan aplikasi!";
        }
    } else {
        $error = "Nama aplikasi harus diisi!";
    }
}

// UPDATE - Edit app
if (isset($_POST['edit_app'])) {
    $id = $_POST['app_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $koneksi->prepare("UPDATE apps SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Aplikasi berhasil diperbarui!";
            $message = "Data berhasil diperbarui!";
            echo "<script>
                setTimeout(function() {
                    window.location.href = '?page=apps';
                }, 1500);
            </script>";
        } else {
            $error = "Gagal memperbarui aplikasi!";
            $mode = 'edit_app';
        }
    } else {
        $error = "Nama aplikasi harus diisi!";
        $mode = 'edit_app';
    }
}

// CREATE - Add todo to app
if (isset($_POST['add_todo_to_app'])) {
    $app_id = trim($_POST['app_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    $user_id = $_SESSION['user_id'];
    
    $image_path = null;
    
    // Handle image upload
    if (isset($_FILES['todo_image']) && $_FILES['todo_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['todo_image']['type'];
        $file_size = $_FILES['todo_image']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            // Create upload directory if not exists
            $upload_dir = 'uploads/todos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['todo_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('todo_') . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['todo_image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                $error = "Gagal mengupload gambar!";
            }
        } else {
            $error = "File tidak valid! Hanya JPG, JPEG, PNG, GIF dengan maksimal 5MB.";
        }
    }
    
    if (!empty($title) && !empty($app_id) && empty($error)) {
        $stmt = $koneksi->prepare("INSERT INTO todos (app_id, title, description, priority, image, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $app_id, $title, $description, $priority, $image_path, $user_id);
        
        if ($stmt->execute()) {
            $app_stmt = $koneksi->prepare("SELECT name FROM apps WHERE id = ?");
            $app_stmt->bind_param("i", $app_id);
            $app_stmt->execute();
            $app_name = $app_stmt->get_result()->fetch_assoc()['name'];
            
            $_SESSION['success_message'] = "Todo '$title' berhasil ditambahkan ke aplikasi '$app_name'!";
            $message = "Todo berhasil ditambahkan!";
            echo "<script>
                setTimeout(function() {
                    window.location.href = '?page=apps';
                }, 1500);
            </script>";
        } else {
            // Delete uploaded image if database insert fails
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            $error = "Gagal menambahkan todo!";
            $mode = 'add_todo';
        }
    } elseif (empty($error)) {
        $error = "Judul todo harus diisi!";
        $mode = 'add_todo';
    }
}

// Page titles
$page_titles = [
    'add_app' => 'Tambah Aplikasi Baru',
    'edit_app' => 'Edit Aplikasi',
    'add_todo' => 'Tambah Todo'
];
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

.container {
    max-width: 100%;
    margin: 0;
    padding: 20px 30px;
    background: #f5f6fa;
}

/* Alert Messages */
.alert {
    padding: 11px 17px;
    border-radius: 6px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Page Header */
.page-header {
    margin-bottom: 16px;
    padding: 8px 30px;
    background: #f5f6fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 2.1rem;
    font-weight: 600;
    color: #0d8af5;
    margin-bottom: 4px;
}

/* Form Box */
.form-box {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 10000px;
    margin: 0 auto;
}

.form-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.form-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
}

/* Form */
.form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 0.95rem;
}

.form-group label span {
    color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0d8af5;
    box-shadow: 0 0 0 3px rgba(13, 138, 245, 0.1);
}

.form-help {
    display: block;
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 5px;
}

/* Priority Options */
.priority-options {
    display: flex;
    gap: 12px;
}

.priority-option {
    flex: 1;
}

.priority-option input {
    display: none;
}

.priority-option label {
    display: block;
    padding: 12px;
    text-align: center;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.priority-option input:checked + label {
    border-color: #0d8af5;
    background: #e3f2fd;
    color: #0d8af5;
}

.priority-option label:hover {
    border-color: #0d8af5;
}

/* Image Upload */
.image-upload-container {
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #fafafa;
}

.image-upload-container:hover {
    border-color: #0d8af5;
    background: #f0f9ff;
}

.image-upload-container.dragover {
    border-color: #0d8af5;
    background: #e3f2fd;
}

.upload-icon {
    font-size: 3rem;
    color: #0d8af5;
    margin-bottom: 12px;
}

.upload-text {
    color: #374151;
    font-size: 1rem;
    margin-bottom: 6px;
    font-weight: 500;
}

.upload-hint {
    color: #9ca3af;
    font-size: 0.85rem;
}

#todoImageInput {
    display: none;
}

.image-preview-container {
    margin-top: 15px;
    display: none;
}

.image-preview-container.show {
    display: block;
}

.image-preview {
    position: relative;
    display: inline-block;
}

.preview-image {
    max-width: 100%;
    max-height: 250px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.remove-image {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    font-size: 1rem;
}

.remove-image:hover {
    background: #c0392b;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #e5e7eb;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    border: none;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #0d8af5 0%, #0b7ad6 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 138, 245, 0.3);
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

/* Info Box */
.info-box {
    background: #f0f9ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: start;
    gap: 12px;
}

.info-box i {
    color: #0d8af5;
    font-size: 1.2rem;
    margin-top: 2px;
}

.info-box p {
    color: #1e40af;
    font-size: 0.9rem;
    line-height: 1.6;
    margin: 0;
}

/* App Badge */
.selected-app-info {
    margin-bottom: 20px;
    padding: 16px;
    background: #f0f9ff;
    border: 2px solid #0ea5e9;
    border-radius: 8px;
}

.app-info-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #0ea5e9;
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .form-box {
        padding: 20px;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .priority-options {
        flex-direction: column;
    }
}
</style>

<!-- Alerts -->
<?php if ($message): ?>
<div class="container">
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
        <span style="margin-left: auto; font-size: 0.85rem;">Redirecting...</span>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="container">
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="header-content">
        <h1 class="page-title"><?= $page_titles[$mode] ?></h1>
    </div>
</div>

<div class="container">
    <div class="form-box">
        <!-- Form Header -->
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-<?= $mode == 'add_todo' ? 'tasks' : 'cube' ?>"></i>
            </div>
            <h2 class="form-title">
                <?= $page_titles[$mode] ?>
            </h2>
        </div>

        <!-- Info Box -->
        <?php if ($mode == 'add_app'): ?>
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>
                <strong>Catatan:</strong> Field yang ditandai dengan <span style="color: #ef4444;">*</span> wajib diisi. 
                Aplikasi yang Anda buat akan menjadi wadah untuk mengelompokkan todo-todo.
            </p>
        </div>
        <?php elseif ($mode == 'edit_app'): ?>
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>
                <strong>Catatan:</strong> Perubahan yang Anda lakukan akan mempengaruhi semua todo yang terkait dengan aplikasi ini.
            </p>
        </div>
        <?php endif; ?>

        <!-- Add Todo to Specific App -->
        <?php if ($mode == 'add_todo'): ?>
        <div class="selected-app-info">
            <div class="app-info-badge">
                <i class="fas fa-cube"></i>
                <span><?= htmlspecialchars($todo_app_name) ?></span>
            </div>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="app_id" value="<?= $todo_app_id ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="todoTitle">Judul Todo <span>*</span></label>
                    <input type="text" 
                           id="todoTitle" 
                           name="title" 
                           required 
                           placeholder="Masukkan judul todo"
                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="todoDescription">Deskripsi</label>
                    <textarea id="todoDescription" 
                              name="description"
                              placeholder="Deskripsi detail tentang todo"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    <small class="form-help">Opsional - Jelaskan detail tentang todo ini</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prioritas <span>*</span></label>
                    <div class="priority-options">
                        <div class="priority-option">
                            <input type="radio" name="priority" value="low" id="priorityLow" 
                                   <?= (!isset($_POST['priority']) || $_POST['priority'] == 'low') ? 'checked' : '' ?>>
                            <label for="priorityLow">
                                <i class="fas fa-arrow-down"></i> Low
                            </label>
                        </div>
                        <div class="priority-option">
                            <input type="radio" name="priority" value="medium" id="priorityMedium"
                                   <?= (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'checked' : '' ?>>
                            <label for="priorityMedium">
                                <i class="fas fa-minus"></i> Medium
                            </label>
                        </div>
                        <div class="priority-option">
                            <input type="radio" name="priority" value="high" id="priorityHigh"
                                   <?= (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'checked' : '' ?>>
                            <label for="priorityHigh">
                                <i class="fas fa-arrow-up"></i> High
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Gambar (Opsional)</label>
                    <div class="image-upload-container" id="imageUploadArea" onclick="document.getElementById('todoImageInput').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            Klik atau drag & drop gambar di sini
                        </div>
                        <div class="upload-hint">
                            JPG, JPEG, PNG, GIF - Maksimal 5MB
                        </div>
                    </div>
                    <input type="file" id="todoImageInput" name="todo_image" accept="image/jpeg,image/png,image/jpg,image/gif">
                    <div class="image-preview-container" id="imagePreviewContainer">
                        <div class="image-preview">
                            <img id="previewImage" class="preview-image" src="" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage(event)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="?page=apps" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
                <button type="submit" name="add_todo_to_app" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Todo
                </button>
            </div>
        </form>
        
        <!-- Add/Edit App Form -->
        <?php elseif ($mode == 'add_app' || $mode == 'edit_app'): ?>
        <form method="POST" action="">
            <?php if ($mode == 'edit_app' && $edit_app_data): ?>
                <input type="hidden" name="app_id" value="<?= $edit_app_data['id'] ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="appName">Nama Aplikasi <span>*</span></label>
                    <input type="text" 
                           id="appName" 
                           name="name" 
                           required 
                           placeholder="Masukkan nama aplikasi" 
                           value="<?= $mode == 'edit_app' && $edit_app_data ? htmlspecialchars($edit_app_data['name']) : '' ?>">
                    <small class="form-help">Nama unik untuk aplikasi Anda</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="appDescription">Deskripsi</label>
                    <textarea id="appDescription" 
                              name="description"
                              placeholder="Deskripsi singkat tentang aplikasi"><?= $mode == 'edit_app' && $edit_app_data ? htmlspecialchars($edit_app_data['description']) : '' ?></textarea>
                    <small class="form-help">Opsional - Jelaskan tujuan atau fungsi aplikasi ini</small>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="?page=apps" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
                <button type="submit" name="<?= $mode == 'edit_app' ? 'edit_app' : 'add_app' ?>" class="btn btn-primary">
                    <i class="fas fa-<?= $mode == 'edit_app' ? 'check' : 'save' ?>"></i>
                    <?= $mode == 'edit_app' ? 'Perbarui Data' : 'Simpan Data' ?>
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Image Upload Handling (only for add_todo mode)
<?php if ($mode == 'add_todo'): ?>
const imageUploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('todoImageInput');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const previewImage = document.getElementById('previewImage');

// Handle file input change
imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        handleImageFile(file);
    }
});

// Handle drag and drop
imageUploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    imageUploadArea.classList.add('dragover');
});

imageUploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    imageUploadArea.classList.remove('dragover');
});

imageUploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    imageUploadArea.classList.remove('dragover');
    
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        imageInput.files = e.dataTransfer.files;
        handleImageFile(file);
    } else {
        alert('Silakan upload file gambar (JPG, PNG, GIF)');
    }
});

function handleImageFile(file) {
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Tipe file tidak didukung! Gunakan JPG, PNG, atau GIF.');
        imageInput.value = '';
        return;
    }
    
    // Validate file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('Ukuran file terlalu besar! Maksimal 5MB.');
        imageInput.value = '';
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        previewImage.src = e.target.result;
        imagePreviewContainer.classList.add('show');
    };
    reader.readAsDataURL(file);
}

function removeImage(e) {
    e.preventDefault();
    e.stopPropagation();
    imageInput.value = '';
    imagePreviewContainer.classList.remove('show');
    previewImage.src = '';
}
<?php endif; ?>

// Form validation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Mohon lengkapi semua field yang wajib diisi!');
    }
});
</script>