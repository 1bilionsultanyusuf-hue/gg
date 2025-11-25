<?php
session_start();

// Cara 1: Naik 2 level dari direktori saat ini
require_once(__DIR__ . '/../../config.php');

// Cek login
if (!isset($_SESSION['user_id'])) {
    die('Anda harus login terlebih dahulu!');
}

// Get parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'bulanan';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01');
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d');
$auto_print = isset($_GET['auto_print']) ? $_GET['auto_print'] : '0';

// Validasi kategori
if ($category !== 'laporan_tugas') {
    die('Kategori laporan tidak valid!');
}

// Get current user info
$current_user_id = $_SESSION['user_id'];
$user_query = $koneksi->prepare("SELECT name, role FROM users WHERE id = ?");
$user_query->bind_param('i', $current_user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows === 0) {
    die('User tidak ditemukan!');
}

$current_user = $user_result->fetch_assoc();
$user_name = $current_user['name'];
$user_role = $current_user['role'];

// Get laporan data dari taken
$query = "
    SELECT tk.id, tk.date, tk.status, tk.catatan, tk.image,
           td.title as todo_title,
           td.description as todo_description,
           td.priority,
           a.name as app_name,
           u.name as user_name,
           creator.name as creator_name
    FROM taken tk
    LEFT JOIN todos td ON tk.id_todos = td.id
    LEFT JOIN apps a ON td.app_id = a.id
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN users creator ON td.user_id = creator.id
    WHERE tk.user_id = ? AND tk.date BETWEEN ? AND ?
    ORDER BY tk.date ASC
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param('iss', $current_user_id, $date_start, $date_end);
$stmt->execute();
$result = $stmt->get_result();

// Format periode untuk display
$periode_display = date('d/m/Y', strtotime($date_start)) . ' - ' . date('d/m/Y', strtotime($date_end));
$bulan_display = date('F Y', strtotime($date_start));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            padding: 20px;
            background: white;
            position: relative;
        }

        body::before {
            content: '';
            display: block;
            height: 0;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 0 20px;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #000;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header p {
            font-size: 14px;
            margin: 2px 0;
        }

        /* Biodata Section */
        .biodata {
            margin-bottom: 25px;
        }

        .biodata-info {
            width: 100%;
        }

        .biodata-info table {
            width: 100%;
            font-size: 13px;
        }

        .biodata-info td {
            padding: 3px 0;
        }

        .biodata-info td:first-child {
            width: 80px;
            vertical-align: top;
        }

        .biodata-info td:nth-child(2) {
            width: 20px;
            vertical-align: top;
        }

        .biodata-info td:nth-child(3) {
            vertical-align: top;
        }

        /* Jurnal Table */
        .jurnal-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            font-size: 11px;
        }

        .jurnal-table th,
        .jurnal-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
        }

        .jurnal-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .jurnal-table td:first-child {
            text-align: center;
            width: 50px;
            font-weight: bold;
        }

        .jurnal-table td:nth-child(2) {
            text-align: center;
            width: 120px;
            font-weight: bold;
        }

        .jurnal-table td:nth-child(3) {
            width: auto;
            line-height: 1.5;
            padding-left: 12px;
        }

        .jurnal-table td:nth-child(4) {
            text-align: center;
            width: 150px;
            padding: 8px;
            vertical-align: middle;
            font-size: 10px;
            color: #0066cc;
            text-decoration: underline;
            cursor: pointer;
        }

        .jurnal-table td:nth-child(5) {
            text-align: center;
            width: 100px;
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .container {
                max-width: 100%;
            }

            @page {
                margin: 2cm;
                size: A4;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            * {
                box-shadow: none !important;
                text-shadow: none !important;
            }
            
            /* Hilangkan header/footer browser */
            @page {
                margin-top: 1cm;
                margin-bottom: 1cm;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Laporan Tugas</h1>
            <p>Periode: <?= $periode_display ?></p>
            <p><strong>Bulan: <?= $bulan_display ?></strong></p>
        </div>

        <!-- Biodata -->
        <div class="biodata">
            <div class="biodata-info">
                <table>
                    <tr>
                        <td>Nama</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($user_name) ?></td>
                    </tr>
                    <tr>
                        <td>Role</td>
                        <td>:</td>
                        <td><?= ucfirst($user_role) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Laporan Table -->
        <table class="jurnal-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari/Tanggal</th>
                    <th>Uraian Kegiatan/Uraian Materi</th>
                    <th>Image</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Simpan data ke array
                $data_rows = [];
                while($row = $result->fetch_assoc()) {
                    $data_rows[] = $row;
                }
                
                // Tampilkan minimal 10 baris
                $max_rows = 10;
                for($i = 0; $i < $max_rows; $i++):
                    if(isset($data_rows[$i])):
                        $row = $data_rows[$i];
                        // Format tanggal
                        $tanggal = date('d-m-Y', strtotime($row['date']));
                        
                        // Format status
                        $status_display = '';
                        switch($row['status']) {
                            case 'done':
                                $status_display = 'Selesai';
                                break;
                            case 'in_progress':
                                $status_display = 'Progress';
                                break;
                            case 'pending':
                                $status_display = 'Pending';
                                break;
                            default:
                                $status_display = ucfirst($row['status']);
                        }
                        
                        // Uraian kegiatan (gabungan judul + catatan jika ada)
                        $uraian = htmlspecialchars($row['todo_title']);
                        if (!empty($row['catatan'])) {
                            $uraian .= ' - ' . htmlspecialchars($row['catatan']);
                        }
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $tanggal ?></td>
                    <td><?= $uraian ?></td>
                    <td>
                        <?php if (!empty($row['image']) && file_exists('../../' . $row['image'])): ?>
                            Link Image
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= $status_display ?></td>
                </tr>
                <?php 
                    else:
                        // Baris kosong
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php 
                    endif;
                endfor;
                ?>
            </tbody>
        </table>
    </div>

    <script>
        // AUTO PRINT FUNCTIONALITY
        const urlParams = new URLSearchParams(window.location.search);
        const shouldAutoPrint = urlParams.get('auto_print') === '1';

        if (shouldAutoPrint) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$koneksi->close();
?>