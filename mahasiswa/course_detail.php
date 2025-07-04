<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses'; // Tetap aktifkan 'Praktikum Saya' di navigasi

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; 

$message = ''; // Untuk pesan sukses atau error
$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login
$upload_dir_materi = '../uploads/materi/'; // Direktori materi asisten
$upload_dir_laporan = '../uploads/laporan/'; // Direktori laporan mahasiswa

$praktikum_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect jika ID praktikum tidak valid
if ($praktikum_id === 0) {
    header("Location: my_courses.php");
    exit();
}

// === Validasi: Pastikan mahasiswa terdaftar di praktikum ini ===
$is_enrolled = false;
$sql_check_enrollment = "SELECT COUNT(*) AS count FROM pendaftaran_praktikum WHERE id_user = ? AND id_praktikum = ?";
$stmt_check_enrollment = $conn->prepare($sql_check_enrollment);
$stmt_check_enrollment->bind_param("ii", $user_id, $praktikum_id);
$stmt_check_enrollment->execute();
$result_enrollment = $stmt_check_enrollment->get_result();
$row_enrollment = $result_enrollment->fetch_assoc();
if ($row_enrollment['count'] > 0) {
    $is_enrolled = true;
}
$stmt_check_enrollment->close();

if (!$is_enrolled) {
    // Jika tidak terdaftar, redirect atau tampilkan pesan error
    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Akses Ditolak!</strong><span class="block sm:inline"> Anda tidak terdaftar di praktikum ini.</span></div>';
    // Anda bisa memilih untuk redirect ke my_courses.php
    // header("Location: my_courses.php");
    // exit();
}


// === Logika Mengumpulkan Laporan (CREATE/UPDATE Laporan) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit_laporan') {
    $id_modul_submit = isset($_POST['id_modul']) ? intval($_POST['id_modul']) : 0;
    
    if ($id_modul_submit === 0) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Modul tidak valid.</span></div>';
    } elseif (!$is_enrolled) {
         $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Anda tidak terdaftar di praktikum ini.</span></div>';
    } else {
        $file_laporan = null;
        // Cek apakah ada file laporan yang diunggah
        if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
            $file_name = basename($_FILES['file_laporan']['name']);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx']; // Hanya izinkan PDF dan DOC/X untuk laporan

            if (in_array($file_extension, $allowed_extensions)) {
                $new_file_name = 'laporan_' . $user_id . '_' . $id_modul_submit . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir_laporan . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    $file_laporan = $new_file_name;

                    // Cek apakah mahasiswa sudah pernah mengumpulkan laporan untuk modul ini
                    $sql_check_laporan = "SELECT id, file_laporan FROM laporan_tugas WHERE id_user = ? AND id_modul = ?";
                    $stmt_check_laporan = $conn->prepare($sql_check_laporan);
                    $stmt_check_laporan->bind_param("ii", $user_id, $id_modul_submit);
                    $stmt_check_laporan->execute();
                    $result_check_laporan = $stmt_check_laporan->get_result();

                    if ($result_check_laporan->num_rows > 0) {
                        // Laporan sudah ada, lakukan UPDATE
                        $existing_laporan = $result_check_laporan->fetch_assoc();
                        $old_laporan_file = $existing_laporan['file_laporan'];
                        $laporan_id_to_update = $existing_laporan['id'];

                        $sql_update = "UPDATE laporan_tugas SET file_laporan = ?, tanggal_submit = CURRENT_TIMESTAMP WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("si", $file_laporan, $laporan_id_to_update);
                        
                        if ($stmt_update->execute()) {
                            // Hapus file laporan lama
                            if (!empty($old_laporan_file) && file_exists($upload_dir_laporan . $old_laporan_file)) {
                                unlink($upload_dir_laporan . $old_laporan_file);
                            }
                            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Laporan berhasil diperbarui.</span></div>';
                        } else {
                            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui laporan: ' . $stmt_update->error . '</span></div>';
                        }
                        $stmt_update->close();
                    } else {
                        // Laporan belum ada, lakukan INSERT
                        $sql_insert = "INSERT INTO laporan_tugas (id_modul, id_user, file_laporan) VALUES (?, ?, ?)";
                        $stmt_insert = $conn->prepare($sql_insert);
                        $stmt_insert->bind_param("iis", $id_modul_submit, $user_id, $file_laporan);

                        if ($stmt_insert->execute()) {
                            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Laporan berhasil dikumpulkan.</span></div>';
                        } else {
                            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mengumpulkan laporan: ' . $stmt_insert->error . '</span></div>';
                        }
                        $stmt_insert->close();
                    }
                    $stmt_check_laporan->close();

                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mengunggah file laporan.</span></div>';
                }
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Jenis file tidak diizinkan. Hanya PDF, DOC, DOCX.</span></div>';
            }
        } else {
             $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Mohon pilih file laporan untuk diunggah.</span></div>';
        }
    }
}

// === Ambil Detail Praktikum ===
$praktikum_detail = null;
$sql_praktikum_detail = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
$stmt_praktikum_detail = $conn->prepare($sql_praktikum_detail);
$stmt_praktikum_detail->bind_param("i", $praktikum_id);
$stmt_praktikum_detail->execute();
$result_praktikum_detail = $stmt_praktikum_detail->get_result();
if ($result_praktikum_detail->num_rows === 1) {
    $praktikum_detail = $result_praktikum_detail->fetch_assoc();
}
$stmt_praktikum_detail->close();

// Jika praktikum tidak ditemukan (walaupun ID valid tapi tidak ada di DB)
if (!$praktikum_detail) {
    header("Location: my_courses.php");
    exit();
}


// === Ambil Modul, Laporan Mahasiswa, dan Nilai untuk Praktikum Ini ===
$moduls_with_data = [];
$sql_modul_data = "SELECT 
                        m.id AS modul_id, 
                        m.nama_modul, 
                        m.deskripsi AS modul_deskripsi, 
                        m.file_materi,
                        lt.id AS laporan_id, 
                        lt.file_laporan, 
                        lt.tanggal_submit,
                        nl.nilai, 
                        nl.feedback
                    FROM modul m
                    LEFT JOIN laporan_tugas lt ON m.id = lt.id_modul AND lt.id_user = ?
                    LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                    WHERE m.id_praktikum = ?
                    ORDER BY m.nama_modul ASC"; // Urutkan berdasarkan nama modul

$stmt_modul_data = $conn->prepare($sql_modul_data);
$stmt_modul_data->bind_param("ii", $user_id, $praktikum_id);
$stmt_modul_data->execute();
$result_modul_data = $stmt_modul_data->get_result();

if ($result_modul_data->num_rows > 0) {
    while ($row = $result_modul_data->fetch_assoc()) {
        $moduls_with_data[] = $row;
    }
}
$stmt_modul_data->close();
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum_detail['nama_praktikum']); ?></h2>
    <p class="text-gray-600 text-lg mb-4">Kode: <?php echo htmlspecialchars($praktikum_detail['kode_praktikum']); ?></p>
    <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($praktikum_detail['deskripsi'])); ?></p>
    
    <?php echo $message; ?>

    <?php if (!$is_enrolled): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">Akses Ditolak!</p>
            <p>Anda tidak terdaftar di praktikum ini. Silakan kembali ke <a href="my_courses.php" class="font-semibold underline">Daftar Praktikum Saya</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_enrolled): // Tampilkan konten hanya jika terdaftar ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul & Tugas</h3>
        
        <?php if (empty($moduls_with_data)): ?>
            <p class="text-gray-600">Belum ada modul yang tersedia untuk praktikum ini.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($moduls_with_data as $modul): ?>
                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">Modul: <?php echo htmlspecialchars($modul['nama_modul']); ?></h4>
                        <p class="text-gray-700 text-sm mb-3"><?php echo nl2br(htmlspecialchars($modul['modul_deskripsi'])); ?></p>

                        <div class="mb-4">
                            <p class="text-md font-medium text-gray-700">Materi Modul:</p>
                            <?php if (!empty($modul['file_materi'])): ?>
                                <a href="<?php echo $upload_dir_materi . htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:underline">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Unduh Materi (<?php echo htmlspecialchars($modul['file_materi']); ?>)
                                </a>
                            <?php else: ?>
                                <p class="text-gray-500 text-sm">Tidak ada materi tersedia untuk modul ini.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4 p-4 border border-blue-200 rounded-md bg-blue-50">
                            <p class="text-md font-medium text-gray-700 mb-2">Status Laporan Anda:</p>
                            <?php if ($modul['laporan_id']): ?>
                                <p class="text-sm text-gray-800">Sudah mengumpulkan laporan pada: **<?php echo date('d M Y H:i', strtotime($modul['tanggal_submit'])); ?>**</p>
                                <a href="<?php echo $upload_dir_laporan . htmlspecialchars($modul['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline text-sm inline-block mt-1">Unduh Laporan Anda</a>
                            <?php else: ?>
                                <p class="text-sm text-yellow-700">Belum mengumpulkan laporan.</p>
                            <?php endif; ?>
                            
                            <form action="course_detail.php?id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="action" value="submit_laporan">
                                <input type="hidden" name="id_modul" value="<?php echo $modul['modul_id']; ?>">
                                <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Unggah/Perbarui Laporan (PDF/DOCX):</label>
                                <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100" required>
                                <button type="submit" class="mt-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline">
                                    <?php echo $modul['laporan_id'] ? 'Perbarui Laporan' : 'Kumpulkan Laporan'; ?>
                                </button>
                            </form>
                        </div>

                        <div class="p-4 border border-green-200 rounded-md bg-green-50">
                            <p class="text-md font-medium text-gray-700 mb-2">Nilai Laporan Anda:</p>
                            <?php if ($modul['nilai'] !== null): ?>
                                <p class="text-2xl font-bold text-green-700 mb-2"><?php echo htmlspecialchars($modul['nilai']); ?></p>
                                <p class="text-sm text-gray-800">Feedback: <?php echo nl2br(htmlspecialchars($modul['feedback'])); ?></p>
                            <?php else: ?>
                                <p class="text-sm text-gray-700">Laporan belum dinilai.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; // End if ($is_enrolled) ?>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
?>