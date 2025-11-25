<?php
// index.php - Without client role
session_start();

// Handle logout
if(isset($_GET['logout'])){
    session_unset();
    session_destroy();
    header('Location: modul/auth/logout.php');
    exit;
}

// ==========================
// Pastikan user login
// ==========================
if(!isset($_SESSION['user_id'])){
    header('Location: modul/auth/login.php');
    exit;
}

// ==========================
// Include config database
// ==========================
require_once 'config.php';

// ============================================================
// HANDLE POST REDIRECTS - HARUS SEBELUM OUTPUT HTML APAPUN!
// ============================================================

// Handle Complete Taken - Redirect to taken_selesai
if (isset($_POST['complete_taken']) && isset($_POST['taken_id'])) {
    $id = (int)$_POST['taken_id'];
    $current_user_id = $_SESSION['user_id'];
    
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ?");
    $check_owner->bind_param("i", $id);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows > 0) {
        $owner = $owner_result->fetch_assoc();
        if ($owner['user_id'] == $current_user_id) {
            // PERBAIKAN: Redirect lewat routing index.php, BUKAN ke file langsung
            header("Location: index.php?page=taken_selesai&id=" . $id);
            exit();
        } else {
            $_SESSION['error_message'] = "Anda tidak memiliki akses untuk mengedit taken ini!";
            header("Location: index.php?page=taken");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Taken tidak ditemukan!";
        header("Location: index.php?page=taken");
        exit();
    }
}

// Handle Complete Taken Form Submission
if (isset($_POST['complete_taken_submit']) && isset($_GET['id'])) {
    $taken_id = (int)$_GET['id'];
    $catatan = trim($_POST['catatan']);
    $status = 'done';
    $date = date('Y-m-d');
    $image_path = null;
    $error = '';
    
    // Validate ownership
    $check_owner = $koneksi->prepare("SELECT user_id FROM taken WHERE id = ? AND user_id = ?");
    $check_owner->bind_param("ii", $taken_id, $_SESSION['user_id']);
    $check_owner->execute();
    $owner_result = $check_owner->get_result();
    
    if ($owner_result->num_rows == 0) {
        $_SESSION['error_message'] = "Taken tidak ditemukan atau Anda tidak memiliki akses!";
        header("Location: index.php?page=taken");
        exit();
    }
    
    // Validate catatan
    if (empty($catatan)) {
        $_SESSION['form_error'] = "Catatan tidak boleh kosong!";
        header("Location: index.php?page=taken_selesai&id=" . $taken_id);
        exit();
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['form_error'] = "Tipe file tidak diizinkan! Hanya JPG, PNG, atau GIF.";
            header("Location: index.php?page=taken_selesai&id=" . $taken_id);
            exit();
        } elseif ($file_size > $max_size) {
            $_SESSION['form_error'] = "Ukuran file terlalu besar! Maksimal 5MB.";
            header("Location: index.php?page=taken_selesai&id=" . $taken_id);
            exit();
        } else {
            // Create upload directory if not exists
            $upload_dir = 'uploads/taken_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'taken_' . $taken_id . '_' . time() . '.' . $file_extension;
            $image_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $_SESSION['form_error'] = "Gagal mengupload gambar!";
                header("Location: index.php?page=taken_selesai&id=" . $taken_id);
                exit();
            }
        }
    }
    
    // Update database
    $update_stmt = $koneksi->prepare("UPDATE taken SET status = ?, catatan = ?, image = ?, date = ? WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("ssssii", $status, $catatan, $image_path, $date, $taken_id, $_SESSION['user_id']);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Todo berhasil diselesaikan!";
        
        // Redirect ke dashboard sesuai role
        $redirect_page = 'taken';
        switch($_SESSION['user_role']) {
            case 'programmer':
                $redirect_page = 'dashboard_pr';
                break;
            case 'support':
                $redirect_page = 'dashboard_support';
                break;
            case 'admin':
                $redirect_page = 'dashboard';
                break;
        }
        
        header("Location: index.php?page=" . $redirect_page);
        exit();
    } else {
        // Delete uploaded image if database update fails
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $_SESSION['form_error'] = "Gagal menyelesaikan todo!";
        header("Location: index.php?page=taken_selesai&id=" . $taken_id);
        exit();
    }
}

// ============================================================
// END HANDLE POST REDIRECTS
// ============================================================

// ==========================
// Tentukan page dan role-based access
// ==========================
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Role-based page access control - WITHOUT CLIENT
$role_access = [
    'dashboard' => ['admin'],           // Dashboard khusus admin
    'dashboard_pr' => ['programmer'],   // Dashboard khusus programmer
    'dashboard_support' => ['support'], // Dashboard khusus support
    'apps' => ['admin', 'programmer', 'support'],
    'users' => ['admin'],
    'todos' => ['admin', 'programmer', 'support'],
    'profile' => ['admin', 'programmer', 'support'],
    'taken' => ['admin', 'programmer'],
    'reports' => ['admin', 'programmer', 'support'],
    'logout' => ['admin', 'programmer', 'support'],
    'detail_todos' =>['admin', 'programmer', 'support'],
    'detail_taken' =>['admin', 'programmer'],
    'detail_apps' =>['admin', 'support'],
    'tambah_apps' =>['admin', 'support'],
    'tambah_users' =>['admin'],
    'edit_todos' =>['admin', 'programmer', 'support'],
    'taken_selesai' =>['admin','programmer'],
];

// Get user role and determine default dashboard
$user_role = $_SESSION['user_role'];

// Auto-redirect ke dashboard sesuai role jika mengakses halaman umum 'dashboard'
if($page == 'dashboard') {
    switch($user_role) {
        case 'admin':
            $page = 'dashboard';
            break;
        case 'programmer':
            $page = 'dashboard_pr';
            break;
        case 'support':
            $page = 'dashboard_support';
            break;
        default:
            $page = 'dashboard';
    }
}

// Get allowed pages for current user role
$allowed_pages = array_keys(array_filter($role_access, function($roles) use ($user_role) {
    return in_array($user_role, $roles);
}));

// Check if user has access to requested page
if (isset($role_access[$page]) && !in_array($user_role, $role_access[$page])) {
    // Redirect to appropriate dashboard with access denied message
    $_SESSION['access_error'] = "Anda tidak memiliki akses ke halaman tersebut.";
    
    // Redirect ke dashboard sesuai role
    switch($user_role) {
        case 'admin':
            header('Location: index.php?page=dashboard');
            break;
        case 'programmer':
            header('Location: index.php?page=dashboard_pr');
            break;
        case 'support':
            header('Location: index.php?page=dashboard_support');
            break;
    }
    exit;
}

// Validate page exists in allowed pages
if(!in_array($page, $allowed_pages)) {
    // Default ke dashboard sesuai role
    switch($user_role) {
        case 'admin':
            $page = 'dashboard';
            break;
        case 'programmer':
            $page = 'dashboard_pr';
            break;
        case 'support':
            $page = 'dashboard_support';
            break;
        default:
            $page = 'dashboard';
    }
}

// ==========================
// Include header
// ==========================
include 'modul/layouts/header.php';
?>

<!-- Topbar -->
<?php include 'modul/layouts/topbar.php'; ?>

<!-- Main Wrapper -->
<div class="content-wrapper">
    <!-- Sidebar -->
    <?php include 'modul/layouts/sidebar.php'; ?>

    <!-- Content Area -->
    <main class="main-content">
        <?php
        // Display access error message if exists
        if(isset($_SESSION['access_error'])):
        ?>
        <div class="access-error-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $_SESSION['access_error'] ?>
            <button onclick="this.parentElement.style.display='none';">×</button>
        </div>
        <?php 
            unset($_SESSION['access_error']);
        endif;
        
        // Display error message from POST operations
        if(isset($_SESSION['error_message'])):
        ?>
        <div class="access-error-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $_SESSION['error_message'] ?>
            <button onclick="this.parentElement.style.display='none';">×</button>
        </div>
        <?php 
            unset($_SESSION['error_message']);
        endif;
        
        // Display success message
        if(isset($_SESSION['success_message'])):
        ?>
        <div class="access-success-alert">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success_message'] ?>
            <button onclick="this.parentElement.style.display='none';">×</button>
        </div>
        <?php 
            unset($_SESSION['success_message']);
        endif;
        
        // Include appropriate page based on role and access
        switch($page){
            case 'apps': 
                include "modul/data/apps.php"; 
                break;
            case 'users': 
                include "modul/data/users.php"; 
                break;
            case 'todos': 
                include "modul/todos/todos.php"; 
                break;
            case 'profile': 
                include "modul/profile/profile.php"; 
                break;
            case 'taken':
                include "modul/taken/taken.php";
                break;
            case 'reports':
                include "modul/reports/reports.php";
                break;
            case 'logout':
                include "modul/auth/logout.php";
                break;
            case 'detail_todos':
                include "modul/todos/detail_todos.php";
                break;
            case 'detail_taken':
                include "modul/taken/detail_taken.php";
                break;
            case 'detail_apps':
                include "modul/data/detail_apps.php";
                break;
            case 'tambah_apps':
                include "modul/data/tambah_apps.php";
                break;
            case 'tambah_users':
                include "modul/data/tambah_users.php";
                break;
            case 'edit_todos':
                include "modul/todos/edit_todos.php";
                break;
            case 'taken_selesai':
                include "modul/taken/taken_selesai.php";
                break;
            case 'dashboard':
                include "modul/dashboard/dashboard.php"; // Dashboard khusus admin
                break;
            case 'dashboard_pr':
                include "modul/dashboard/dashboard_pr.php"; // Dashboard khusus programmer
                break;
            case 'dashboard_support':
                include "modul/dashboard/dashboard_sp.php"; // Dashboard khusus support
                break;
            default:
                // Default dashboard based on role
                switch($user_role) {
                    case 'admin':
                        include "modul/dashboard/dashboard.php";
                        break;
                    case 'programmer':
                        include "modul/dashboard/dashboard_pr.php";
                        break;
                    case 'support':
                        include "modul/dashboard/dashboard_sp.php";
                        break;
                    default:
                        include "modul/dashboard/dashboard.php";
                }
        }
        ?>
    </main>

    <!-- Footer -->
    <?php include 'modul/layouts/footer.php'; ?>

    <!-- Overlay for Mobile -->
    <div id="overlay" class="overlay"></div>
</div>

<!-- Scripts -->
<script src="style/js/main.js"></script>

<!-- Access Error Alert Styles -->
<style>
.access-error-alert {
    background: linear-gradient(90deg, #fee2e2, #fecaca);
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #fca5a5;
    animation: slideDown 0.3s ease;
    position: relative;
}

.access-success-alert {
    background: linear-gradient(90deg, #d1fae5, #a7f3d0);
    color: #065f46;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #6ee7b7;
    animation: slideDown 0.3s ease;
    position: relative;
}

.access-error-alert button,
.access-success-alert button {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.access-error-alert button {
    color: #dc2626;
}

.access-success-alert button {
    color: #065f46;
}

.access-error-alert button:hover {
    background: rgba(220, 38, 38, 0.1);
}

.access-success-alert button:hover {
    background: rgba(6, 95, 70, 0.1);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

</body>
</html>