<?php
// Get app_id from URL
$app_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($app_id <= 0) {
    header("Location: ?page=apps");
    exit;
}

// Get app details
$app_query = "SELECT * FROM apps WHERE id = ?";
$stmt = $koneksi->prepare($app_query);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$app_result = $stmt->get_result();

if ($app_result->num_rows == 0) {
    header("Location: ?page=apps");
    exit;
}

$app = $app_result->fetch_assoc();
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

/* Container utama */
.content-box {
    background: #ffffff;
    padding: 40px 50px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-top: 20px;
    max-width: 100%;
    min-height: 500px;
}

.detail-container-main {
    max-width: 100%;
    margin: 0;
    padding: 30px 20px;
    background: #f5f6fa;
}

.detail-content-box {
    background: #ffffff;
    padding: 40px 50px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    min-height: 500px;
}

/* Page Header */
.detail-page-header {
    margin-bottom: 30px;
}

/* Judul halaman */
.page-title {
    font-size: 32px;
    font-weight: 600;
    color: #0066cc;
    margin-bottom: 30px;
}

.detail-page-title {
    font-size: 32px;
    font-weight: 600;
    color: #0066cc;
    margin-bottom: 30px;
}

/* Tombol kembali */
.btn-yellow {
    background: #2563EB;
    color: #fff;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    float: right;
}

.btn-yellow:hover {
    background: #2563EB;
}

.btn-back-detail {
    background: #2563EB;
    color: #fff;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    float: right;
}

.btn-back-detail:hover {
    background: #2563EB;
}

/* Form Group */
.form-group {
    margin-bottom: 30px;
}

/* Label */
.label {
    display: block;
    font-size: 15px;
    font-weight: 500;
    color: #666;
    margin-bottom: 10px;
}

/* Input Field */
.input-field {
    width: 100%;
    padding: 14px 16px;
    font-size: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f5f5f5;
    color: #333;
    transition: all 0.2s ease;
}

.input-field:focus {
    background: #ffffff;
    border-color: #0066cc;
    outline: none;
}

.input-field:disabled,
.input-field[readonly] {
    background: #f5f5f5;
    color: #666;
    cursor: not-allowed;
}

/* Textarea */
textarea.input-field {
    min-height: 150px;
    resize: vertical;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Text kosong */
.no-data {
    margin-top: 10px;
    font-size: 14px;
    color: #999;
    font-style: italic;
}

/* Detail Card Wrapper */
.detail-card-wrapper {
    max-width: 100%;
    margin: 0;
}

/* Clear float */
.clearfix::after {
    content: "";
    display: table;
    clear: both;
}

/* Responsive */
@media (max-width: 768px) {
    .detail-container-main {
        padding: 15px;
    }
    
    .detail-content-box {
        padding: 25px;
    }
    
    .detail-page-title {
        font-size: 24px;
    }
    
    .btn-back-detail {
        float: none;
        display: block;
        width: 100%;
        text-align: center;
    }
}
</style>

<!-- Page Header -->
<div class="detail-container-main">
    <div class="detail-page-header">
        <h1 class="detail-page-title">Detail apps</h1>
    </div>

    <div class="detail-content-box">
        <!-- Form Display -->
        <div class="detail-card-wrapper">
            <div class="form-group">
                <label class="label">Aplikasi</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($app['name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="label">Deskripsi</label>
                <textarea class="input-field" readonly><?= htmlspecialchars($app['description'] ?: 'Tidak ada deskripsi.') ?></textarea>
            </div>
        </div>

        <div class="clearfix">
            <a href="?page=apps" class="btn-back-detail">
                KEMBALI
            </a>
        </div>
    </div>
</div>