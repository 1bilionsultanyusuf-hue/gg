<?php
// ===== BAGIAN 1: LOGIKA PHP (HARUS DI PALING ATAS) =====
// Get todo ID from URL
$todo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($todo_id <= 0) {
    header("Location: ?page=todos");
    exit;
}

// Get todo data
$stmt = $koneksi->prepare("SELECT * FROM todos WHERE id = ?");
$stmt->bind_param("i", $todo_id);
$stmt->execute();
$result = $stmt->get_result();
$todo = $result->fetch_assoc();

if (!$todo) {
    $_SESSION['error_message'] = "Todo tidak ditemukan!";
    header("Location: ?page=todos");
    exit;
}

// Handle form submission
if (isset($_POST['update_todo'])) {
    $app_id = (int)$_POST['app_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = trim($_POST['priority']);
    
    if (!empty($title) && !empty($app_id)) {
        $stmt = $koneksi->prepare("UPDATE todos SET app_id = ?, title = ?, description = ?, priority = ? WHERE id = ?");
        $stmt->bind_param("isssi", $app_id, $title, $description, $priority, $todo_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Todo berhasil diperbarui!";
            header("Location: ?page=todos");
            exit;
        } else {
            $error = "Gagal memperbarui todo!";
        }
    } else {
        $error = "Judul dan aplikasi harus diisi!";
    }
}

// Get apps for dropdown
$apps_query = "SELECT id, name FROM apps ORDER BY name";
$apps_result = $koneksi->query($apps_query);
?>

<!-- ===== BAGIAN 2: HTML DAN CSS (SETELAH SEMUA LOGIKA PHP) ===== -->
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
}

.breadcrumb a:hover {
    text-decoration: underline;
}

/* Content Box */
.content-box {
    background: white;
    border-radius: 0;
    padding: 26px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 800px;
    margin: 0 auto;
}

/* Form */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 0.95rem;
}

.form-group label .required {
    color: #e74c3c;
    margin-left: 2px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.96rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0d8af5;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* Priority Radio Buttons */
.priority-options {
    display: flex;
    gap: 12px;
}

.priority-option {
    flex: 1;
}

.priority-option input[type="radio"] {
    display: none;
}

.priority-option label {
    display: block;
    padding: 12px;
    text-align: center;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
    margin-bottom: 0;
}

.priority-option input[type="radio"]:checked + label {
    border-color: #0d8af5;
    background: #e3f2fd;
    color: #0d8af5;
}

.priority-option label:hover {
    border-color: #0d8af5;
}

/* Buttons */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    border: none;
    border-radius: 6px;
    font-size: 0.96rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background: #0d8af5;
    color: white;
}

.btn-primary:hover {
    background: #0b7ad6;
}

.btn-secondary {
    background: white;
    color: #666;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #f5f5f5;
}

/* Alert */
.alert {
    padding: 11px 17px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Helper Text */
.helper-text {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .page-header {
        padding: 8px 15px;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .content-box {
        padding: 20px 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Edit Todo</h1>
</div>

<div class="container">
    <div class="content-box">
        <!-- Alert Error -->
        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="?page=edit_todos&id=<?= $todo_id ?>">
            <div class="form-group">
                <label for="app_id">
                    Aplikasi
                    <span class="required">*</span>
                </label>
                <select name="app_id" id="app_id" required>
                    <option value="">Pilih Aplikasi</option>
                    <?php while($app = $apps_result->fetch_assoc()): ?>
                    <option value="<?= $app['id'] ?>" <?= $app['id'] == $todo['app_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($app['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <div class="helper-text">Pilih aplikasi terkait dengan todo ini</div>
            </div>

            <div class="form-group">
                <label for="title">
                    Judul Todo
                    <span class="required">*</span>
                </label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       value="<?= htmlspecialchars($todo['title']) ?>"
                       placeholder="Masukkan judul todo" 
                       required>
                <div class="helper-text">Berikan judul yang jelas dan deskriptif</div>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea name="description" 
                          id="description" 
                          placeholder="Masukkan deskripsi detail todo (opsional)"><?= htmlspecialchars($todo['description']) ?></textarea>
                <div class="helper-text">Jelaskan detail todo yang perlu dikerjakan</div>
            </div>

            <div class="form-group">
                <label>
                    Prioritas
                    <span class="required">*</span>
                </label>
                <div class="priority-options">
                    <div class="priority-option">
                        <input type="radio" 
                               name="priority" 
                               value="low" 
                               id="priority_low"
                               <?= $todo['priority'] == 'low' ? 'checked' : '' ?>>
                        <label for="priority_low">
                            <i class="fas fa-arrow-down"></i> Low
                        </label>
                    </div>
                    <div class="priority-option">
                        <input type="radio" 
                               name="priority" 
                               value="medium" 
                               id="priority_medium"
                               <?= $todo['priority'] == 'medium' ? 'checked' : '' ?>>
                        <label for="priority_medium">
                            <i class="fas fa-minus"></i> Medium
                        </label>
                    </div>
                    <div class="priority-option">
                        <input type="radio" 
                               name="priority" 
                               value="high" 
                               id="priority_high"
                               <?= $todo['priority'] == 'high' ? 'checked' : '' ?>>
                        <label for="priority_high">
                            <i class="fas fa-arrow-up"></i> High
                        </label>
                    </div>
                </div>
                <div class="helper-text">Tentukan tingkat prioritas todo</div>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_todo" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Todo
                </button>
                <a href="?page=todos" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const appId = document.getElementById('app_id').value;
    
    if (!title) {
        e.preventDefault();
        alert('Judul todo harus diisi!');
        document.getElementById('title').focus();
        return false;
    }
    
    if (!appId) {
        e.preventDefault();
        alert('Aplikasi harus dipilih!');
        document.getElementById('app_id').focus();
        return false;
    }
});

// Auto-resize textarea
const textarea = document.getElementById('description');
if (textarea) {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Initial resize
    textarea.dispatchEvent(new Event('input'));
}
</script>