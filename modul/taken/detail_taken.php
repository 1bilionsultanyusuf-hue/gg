<?php
// Get taken ID from URL
$taken_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taken_id <= 0) {
    header("Location: ?page=taken");
    exit;
}

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// Get taken detail with all related information
$detail_query = "
    SELECT tk.*, 
           td.title as todo_title,
           td.description as todo_description,
           td.priority as todo_priority,
           a.name as app_name,
           u.name as taker_name,
           u.id as taker_id,
           u.profile_photo as taker_image,
           todo_creator.name as todo_creator_name,
           todo_creator.id as todo_creator_id,
           todo_creator.profile_photo as todo_creator_image
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN users todo_creator ON td.user_id = todo_creator.id
    WHERE tk.id = ?
";

$stmt = $koneksi->prepare($detail_query);
$stmt->bind_param("i", $taken_id);
$stmt->execute();
$result = $stmt->get_result();
$taken = $result->fetch_assoc();

if (!$taken) {
    header("Location: ?page=taken");
    exit;
}

// Check if current user is the owner
$is_owner = ($taken['taker_id'] == $current_user_id);

function getPriorityBadgeDetailTaken($priority) {
    $badges = [
        'high' => '<span class="priority-badge-dt badge-high-dt">High</span>',
        'medium' => '<span class="priority-badge-dt badge-medium-dt">Medium</span>',
        'low' => '<span class="priority-badge-dt badge-low-dt">Low</span>'
    ];
    return $badges[$priority] ?? '<span class="priority-badge-dt">-</span>';
}

function getStatusBadgeDetailTaken($status) {
    $badges = [
        'done' => '<span class="status-badge-dt badge-done-dt">Completed</span>',
        'pending' => '<span class="status-badge-dt badge-pending-dt">Pending</span>',
        'in_progress' => '<span class="status-badge-dt badge-progress-dt">In Progress</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge-dt">-</span>';
}

// Helper function to get profile photo
function getProfilePhotoTaken($image_path, $name) {
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

.detail-taken-container-main {
    max-width: 100%;
    margin: 0;
    padding: 30px 40px;
    background: #f5f6fa;
}

.detail-taken-content-box {
    background: #ffffff;
    padding: 40px 50px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    min-height: 500px;
}

/* Page Header */
.detail-taken-page-header {
    margin-bottom: 30px;
}

/* Judul halaman */
.page-title {
    font-size: 32px;
    font-weight: 600;
    color: #0066cc;
    margin-bottom: 30px;
}

.detail-taken-page-title {
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

.btn-back-detail-taken {
    background:#2563EB;
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

.btn-back-detail-taken:hover {
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
.detail-taken-card-wrapper {
    max-width: 100%;
    margin: 0;
}

/* User Info in Detail */
.detail-taken-user-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #f5f5f5;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.detail-taken-user-circle {
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

.detail-taken-user-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.detail-taken-user-info {
    flex: 1;
}

.detail-taken-username {
    font-weight: 600;
    color: #333;
    margin-bottom: 3px;
}

.detail-taken-userrole {
    font-size: 0.85rem;
    color: #999;
}

.detail-taken-empty-text {
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

.badge-progress-dt {
    background: #fff4e6;
    color: #f39c12;
}

.badge-done-dt {
    background: #e8f5e9;
    color: #27ae60;
}

.badge-pending-dt {
    background: #e3f2fd;
    color: #2196f3;
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
    .detail-taken-container-main {
        padding: 15px;
    }
    
    .detail-taken-content-box {
        padding: 25px;
    }
    
    .detail-taken-page-title {
        font-size: 24px;
    }
    
    .btn-back-detail-taken {
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
<div class="detail-taken-container-main">
    <div class="detail-taken-page-header">
        <h1 class="detail-taken-page-title">Detail Tugas taken saya</h1>
    </div>

    <div class="detail-taken-content-box">
        <!-- Form Display -->
        <div class="detail-taken-card-wrapper">
            <div class="form-group">
                <label class="label">Judul Todo</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($taken['todo_title']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="label">Aplikasi</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($taken['app_name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label class="label">Deskripsi Todo</label>
                <textarea class="input-field" readonly><?= htmlspecialchars($taken['todo_description'] ?: 'Tidak ada deskripsi untuk todo ini.') ?></textarea>
            </div>

            <div class="form-group">
                <label class="label">Dibuat Oleh</label>
                <?php if ($taken['todo_creator_name']): ?>
                    <div class="detail-taken-user-box">
                        <div class="detail-taken-user-circle">
                            <img src="<?= getProfilePhotoTaken($taken['todo_creator_image'], $taken['todo_creator_name']) ?>" 
                                 alt="<?= htmlspecialchars($taken['todo_creator_name']) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($taken['todo_creator_name']) ?>&background=f093fb&color=fff&size=80'">
                        </div>
                        <div class="detail-taken-user-info">
                            <div class="detail-taken-username"><?= htmlspecialchars($taken['todo_creator_name']) ?></div>
                            <div class="detail-taken-userrole">Pembuat Todo</div>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="detail-taken-empty-text">Tidak ada informasi pembuat</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="label">Diambil Oleh</label>
                <?php if ($taken['taker_name']): ?>
                    <div class="detail-taken-user-box">
                        <div class="detail-taken-user-circle">
                            <img src="<?= getProfilePhotoTaken($taken['taker_image'], $taken['taker_name']) ?>" 
                                 alt="<?= htmlspecialchars($taken['taker_name']) ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($taken['taker_name']) ?>&background=667eea&color=fff&size=80'">
                        </div>
                        <div class="detail-taken-user-info">
                            <div class="detail-taken-username"><?= htmlspecialchars($taken['taker_name']) ?></div>
                            <div class="detail-taken-userrole">
                                <?php echo $is_owner ? 'Anda' : 'User Lain'; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="detail-taken-empty-text">Tidak ada informasi</span>
                <?php endif; ?>
            </div>

            <!-- Priority and Status Side by Side -->
            <div class="badge-row">
                <div class="badge-item">
                    <label class="label">Prioritas</label>
                    <div style="padding: 8px 0;">
                        <?= getPriorityBadgeDetailTaken($taken['todo_priority']) ?>
                    </div>
                </div>
                <div class="badge-item">
                    <label class="label">Status</label>
                    <div style="padding: 8px 0;">
                        <?= getStatusBadgeDetailTaken($taken['status']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix">
            <a href="?page=taken" class="btn-back-detail-taken">
                KEMBALI
            </a>
        </div>
    </div>
</div>