<?php
// Get todo ID from URL
$todo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($todo_id <= 0) {
    header("Location: ?page=todos");
    exit;
}

// Get todo detail with all related information
$detail_query = "
    SELECT t.*, 
           a.name as app_name,
           tk.status as taken_status,
           tk.date as taken_date,
           taker.name as taker_name,
           taker.id as taker_id,
           taker.profile_photo as taker_image,
           sender.name as sender_name,
           sender.id as sender_id,
           sender.profile_photo as sender_image
    FROM todos t
    LEFT JOIN apps a ON t.app_id = a.id
    LEFT JOIN taken tk ON t.id = tk.id_todos
    LEFT JOIN users taker ON tk.user_id = taker.id
    LEFT JOIN users sender ON t.user_id = sender.id
    WHERE t.id = ?
";

$stmt = $koneksi->prepare($detail_query);
$stmt->bind_param("i", $todo_id);
$stmt->execute();
$result = $stmt->get_result();
$todo = $result->fetch_assoc();

if (!$todo) {
    header("Location: ?page=todos");
    exit;
}

function getPriorityBadgeDetail($priority) {
    $badges = [
        'high' => '<span class="priority-badge-dt badge-high-dt">High</span>',
        'medium' => '<span class="priority-badge-dt badge-medium-dt">Medium</span>',
        'low' => '<span class="priority-badge-dt badge-low-dt">Low</span>'
    ];
    return $badges[$priority] ?? '<span class="priority-badge-dt">-</span>';
}

function getStatusBadgeDetail($taker_id, $status) {
    if (!$taker_id) {
        return '<span class="status-badge-dt badge-available-dt">Available</span>';
    }
    return $status == 'done' 
        ? '<span class="status-badge-dt badge-done-dt">Completed</span>' 
        : '<span class="status-badge-dt badge-progress-dt">In Progress</span>';
}

// Helper function to get profile photo
function getProfilePhotoDetail($image_path, $name) {
    // Check if user has profile_photo and file exists
    if (!empty($image_path) && file_exists($image_path)) {
        return $image_path . '?v=' . time();
    }
    
    // Default to UI Avatars with user's name
    $encoded_name = urlencode($name);
    return "https://ui-avatars.com/api/?name={$encoded_name}&background=667eea&color=fff&size=80";
}
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
    padding: 30px 40px;
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
    background:#2563EB;
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
    min-height: 120px;
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

/* User Info in Detail */
.detail-user-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #f5f5f5;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.detail-user-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 600;
    flex-shrink: 0;
    overflow: hidden;
    background: #e5e7eb;
}

.detail-user-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.detail-user-info {
    flex: 1;
}

.detail-username {
    font-weight: 600;
    color: #333;
    margin-bottom: 3px;
}

.detail-userrole {
    font-size: 0.85rem;
    color: #999;
}

.detail-empty-text {
    color: #999;
    font-style: italic;
    display: block;
    padding: 14px 16px;
    background: #f5f5f5;
    border-radius: 4px;
    border: 1px solid #ddd;
}

/* Badges */
.priority-badge-dt,
.status-badge-dt {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 20px;
    font-size: 16px;
    font-weight: 600;
}

.badge-high-dt {
    background: #fee;
    color: #e74c3c;
}

.badge-medium-dt {
    background: #fff4e6;
    color: #f39c12;
}

.badge-low-dt {
    background: #e8f5e9;
    color: #27ae60;
}

.badge-available-dt {
    background: #e3f2fd;
    color: #2196f3;
}

.badge-progress-dt {
    background: #fff4e6;
    color: #f39c12;
}

.badge-done-dt {
    background: #e8f5e9;
    color: #27ae60;
}

/* Badge Row - Side by Side */
.badge-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.badge-item {
    display: flex;
    flex-direction: column;
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
    
    .badge-row {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Page Header -->
<div class="detail-container-main">
    <div class="detail-page-header">
        <h1 class="detail-page-title">Detail tugas todos</h1>
    </div>

    <div class="detail-content-box">
        <!-- Form Display -->
        <div class="detail-card-wrapper">
            <div class="form-group">
                <label class="label">Judul</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($todo['title']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="label">Aplikasi</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($todo['app_name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="label">Deskripsi</label>
                <textarea class="input-field" readonly><?= htmlspecialchars($todo['description'] ?: 'Tidak ada deskripsi untuk todo ini.') ?></textarea>
            </div>

            <div class="form-group">
                <label class="label">Dikirim Oleh</label>
                <?php if ($todo['sender_name']): ?>
                    <div class="detail-user-box">
                        <div class="detail-user-circle">
                            <img src="<?= getProfilePhotoDetail($todo['sender_image'], $todo['sender_name']) ?>" 
                                 alt="<?= htmlspecialchars($todo['sender_name']) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($todo['sender_name']) ?>&background=667eea&color=fff&size=80'">
                        </div>
                        <div class="detail-user-info">
                            <div class="detail-username"><?= htmlspecialchars($todo['sender_name']) ?></div>
                            <div class="detail-userrole">Pengirim Todo</div>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="detail-empty-text">Tidak ada informasi pengirim</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="label">Diambil Oleh</label>
                <?php if ($todo['taker_name']): ?>
                    <div class="detail-user-box">
                        <div class="detail-user-circle">
                            <img src="<?= getProfilePhotoDetail($todo['taker_image'], $todo['taker_name']) ?>" 
                                 alt="<?= htmlspecialchars($todo['taker_name']) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($todo['taker_name']) ?>&background=f093fb&color=fff&size=80'">
                        </div>
                        <div class="detail-user-info">
                            <div class="detail-username"><?= htmlspecialchars($todo['taker_name']) ?></div>
                            <div class="detail-userrole">
                                <?php 
                                if ($todo['taken_date']) {
                                    $date = new DateTime($todo['taken_date']);
                                    echo $date->format('d F Y, H:i');
                                } else {
                                    echo 'Tanggal tidak tersedia';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="detail-empty-text">Belum ada yang mengambil todo ini</span>
                <?php endif; ?>
            </div>

            <!-- Priority and Status Side by Side -->
            <div class="badge-row">
                <div class="badge-item">
                    <label class="label">Prioritas</label>
                    <div style="padding: 8px 0;">
                        <?= getPriorityBadgeDetail($todo['priority']) ?>
                    </div>
                </div>
                <div class="badge-item">
                    <label class="label">Status</label>
                    <div style="padding: 8px 0;">
                        <?= getStatusBadgeDetail($todo['taker_id'], $todo['taken_status']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix">
            <a href="?page=todos" class="btn-back-detail">
                KEMBALI
            </a>
        </div>
    </div>
</div>