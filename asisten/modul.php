<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php'; 

$message = ''; // Untuk pesan sukses atau error

// Direktori untuk menyimpan file materi
$upload_dir = '../uploads/materi/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true); // Buat direktori jika belum ada
}

// Logika Tambah/Edit Modul (CREATE & UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_praktikum = isset($_POST['id_praktikum']) ? intval($_POST['id_praktikum']) : 0;
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $modul_id = isset($_POST['modul_id']) ? intval($_POST['modul_id']) : 0;
    $old_file = isset($_POST['old_file']) ? trim($_POST['old_file']) : ''; // Untuk menghapus file lama saat update

    if (empty($nama_modul) || $id_praktikum == 0) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Nama modul dan praktikum harus diisi.</span></div>';
    } else {
        $file_materi = $old_file; // Default: gunakan file lama jika tidak ada upload baru

        // Handle file upload
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_materi']['tmp_name'];
            $file_name = basename($_FILES['file_materi']['name']);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_file_name = uniqid('materi_', true) . '.' . $file_extension;
                $target_file = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    $file_materi = $new_file_name;
                    // Hapus file lama jika ada dan ini adalah update
                    if ($modul_id > 0 && !empty($old_file) && file_exists($upload_dir . $old_file)) {
                        unlink($upload_dir . $old_file);
                    }
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mengunggah file materi.</span></div>';
                    $file_materi = $old_file; // Tetap gunakan file lama jika upload gagal
                }
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Jenis file tidak diizinkan. Hanya PDF, DOC, DOCX, PPT, PPTX.</span></div>';
                $file_materi = $old_file; // Tetap gunakan file lama jika jenis file tidak diizinkan
            }
        }

        if (empty($message)) { // Lanjutkan jika tidak ada error dari upload file
            if ($modul_id > 0) {
                // Update Modul
                $sql = "UPDATE modul SET id_praktikum = ?, nama_modul = ?, deskripsi = ?, file_materi = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", $id_praktikum, $nama_modul, $deskripsi, $file_materi, $modul_id);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Modul berhasil diperbarui.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui modul: ' . $stmt->error . '</span></div>';
                }
            } else {
                // Tambah Modul Baru
                $sql = "INSERT INTO modul (id_praktikum, nama_modul, deskripsi, file_materi) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id_praktikum, $nama_modul, $deskripsi, $file_materi);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Modul baru berhasil ditambahkan.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menambahkan modul: ' . $stmt->error . '</span></div>';
                }
            }
            $stmt->close();
        }
    }
}

// Logika Hapus Modul (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);

    // Ambil nama file materi sebelum menghapus record
    $sql_get_file = "SELECT file_materi FROM modul WHERE id = ?";
    $stmt_get_file = $conn->prepare($sql_get_file);
    $stmt_get_file->bind_param("i", $id_to_delete);
    $stmt_get_file->execute();
    $result_file = $stmt_get_file->get_result();
    $file_to_delete = null;
    if ($result_file->num_rows === 1) {
        $row = $result_file->fetch_assoc();
        $file_to_delete = $row['file_materi'];
    }
    $stmt_get_file->close();

    // Hapus record dari database
    $sql = "DELETE FROM modul WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        // Hapus file fisik jika ada
        if (!empty($file_to_delete) && file_exists($upload_dir . $file_to_delete)) {
            unlink($upload_dir . $file_to_delete);
        }
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Modul berhasil dihapus.</span></div>';
        // Redirect untuk menghilangkan parameter GET setelah hapus
        header("Location: modul.php");
        exit();
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menghapus modul: ' . $stmt->error . '</span></div>';
    }
    $stmt->close();
}

// Logika Ambil Data untuk Edit (READ for UPDATE form)
$edit_modul = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT id, id_praktikum, nama_modul, deskripsi, file_materi FROM modul WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_modul = $result->fetch_assoc();
    }
    $stmt->close();
}

// Ambil Semua Praktikum untuk Dropdown
$praktikums_dropdown = [];
$sql_praktikum = "SELECT id, nama_praktikum, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result_praktikum = $conn->query($sql_praktikum);
if ($result_praktikum->num_rows > 0) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikums_dropdown[] = $row;
    }
}

// Ambil Semua Data Modul (READ for LIST)
$moduls = [];
// Filter berdasarkan praktikum jika dipilih
$filter_praktikum_id = isset($_GET['filter_praktikum']) ? intval($_GET['filter_praktikum']) : 0;

$sql_modul = "SELECT m.id, m.nama_modul, m.deskripsi, m.file_materi, mp.nama_praktikum, mp.kode_praktikum 
              FROM modul m 
              JOIN mata_praktikum mp ON m.id_praktikum = mp.id";
if ($filter_praktikum_id > 0) {
    $sql_modul .= " WHERE m.id_praktikum = ?";
}
$sql_modul .= " ORDER BY mp.nama_praktikum ASC, m.nama_modul ASC";

$stmt_modul = $conn->prepare($sql_modul);
if ($filter_praktikum_id > 0) {
    $stmt_modul->bind_param("i", $filter_praktikum_id);
}
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();

if ($result_modul->num_rows > 0) {
    while ($row = $result_modul->fetch_assoc()) {
        $moduls[] = $row;
    }
}
$stmt_modul->close();

$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $edit_modul ? 'Edit Modul' : 'Tambah Modul Baru'; ?></h2>
    
    <?php echo $message; ?>

    <form action="modul.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($edit_modul['id'] ?? ''); ?>">
        <input type="hidden" name="old_file" value="<?php echo htmlspecialchars($edit_modul['file_materi'] ?? ''); ?>">
        
        <div class="mb-4">
            <label for="id_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Mata Praktikum:</label>
            <select id="id_praktikum" name="id_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">-- Pilih Praktikum --</option>
                <?php foreach ($praktikums_dropdown as $praktikum_opt): ?>
                    <option value="<?php echo htmlspecialchars($praktikum_opt['id']); ?>" 
                        <?php echo (isset($edit_modul['id_praktikum']) && $edit_modul['id_praktikum'] == $praktikum_opt['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum_opt['nama_praktikum'] . ' (' . $praktikum_opt['kode_praktikum'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="nama_modul" class="block text-gray-700 text-sm font-bold mb-2">Nama Modul:</label>
            <input type="text" id="nama_modul" name="nama_modul" value="<?php echo htmlspecialchars($edit_modul['nama_modul'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul:</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($edit_modul['deskripsi'] ?? ''); ?></textarea>
        </div>

        <div class="mb-6">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX/PPTX):</label>
            <input type="file" id="file_materi" name="file_materi" class="block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-blue-50 file:text-blue-700
                hover:file:bg-blue-100">
            <?php if (isset($edit_modul['file_materi']) && !empty($edit_modul['file_materi'])): ?>
                <p class="text-xs text-gray-500 mt-1">File saat ini: <a href="<?php echo $upload_dir . htmlspecialchars($edit_modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($edit_modul['file_materi']); ?></a> (akan diganti jika mengunggah file baru)</p>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <?php echo $edit_modul ? 'Perbarui Modul' : 'Tambah Modul'; ?>
            </button>
            <?php if ($edit_modul): ?>
                <a href="modul.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    Batal Edit
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul</h2>

    <div class="mb-4">
        <form action="modul.php" method="GET" class="flex items-center space-x-2">
            <label for="filter_praktikum" class="block text-gray-700 text-sm font-bold">Filter berdasarkan Praktikum:</label>
            <select id="filter_praktikum" name="filter_praktikum" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="0">-- Semua Praktikum --</option>
                <?php foreach ($praktikums_dropdown as $praktikum_opt): ?>
                    <option value="<?php echo htmlspecialchars($praktikum_opt['id']); ?>" 
                        <?php echo ($filter_praktikum_id == $praktikum_opt['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum_opt['nama_praktikum'] . ' (' . $praktikum_opt['kode_praktikum'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Filter</button>
        </form>
    </div>

    <?php if (empty($moduls)): ?>
        <p class="text-gray-600">Belum ada modul yang ditambahkan untuk praktikum ini.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mata Praktikum
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Modul
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Deskripsi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            File Materi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($moduls as $modul): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($modul['kode_praktikum']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($modul['nama_modul']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($modul['deskripsi']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="<?php echo $upload_dir . htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline">Unduh</a>
                                <?php else: ?>
                                    <span class="text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="modul.php?action=edit&id=<?php echo $modul['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="modul.php?action=delete&id=<?php echo $modul['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini? File materi terkait akan ikut terhapus.')" class="text-red-600 hover:text-red-900">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer.php';
?>