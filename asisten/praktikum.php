<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Manajemen Praktikum';
$activePage = 'praktikum';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php'; 

$message = ''; // Untuk pesan sukses atau error

// Logika Tambah/Edit Praktikum (CREATE & UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $kode_praktikum = trim($_POST['kode_praktikum']);
    $praktikum_id = isset($_POST['praktikum_id']) ? intval($_POST['praktikum_id']) : 0;

    if (empty($nama_praktikum) || empty($kode_praktikum)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Nama praktikum dan kode praktikum harus diisi.</span></div>';
    } else {
        // Cek apakah kode_praktikum sudah ada (kecuali untuk praktikum_id yang sedang diedit)
        $sql_check_code = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ? AND id != ?";
        $stmt_check_code = $conn->prepare($sql_check_code);
        $stmt_check_code->bind_param("si", $kode_praktikum, $praktikum_id);
        $stmt_check_code->execute();
        $stmt_check_code->store_result();

        if ($stmt_check_code->num_rows > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Kode praktikum sudah ada. Gunakan kode lain.</span></div>';
        } else {
            if ($praktikum_id > 0) {
                // Update Praktikum
                $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ?, kode_praktikum = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nama_praktikum, $deskripsi, $kode_praktikum, $praktikum_id);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum berhasil diperbarui.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui praktikum: ' . $stmt->error . '</span></div>';
                }
            } else {
                // Tambah Praktikum Baru
                $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, kode_praktikum) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nama_praktikum, $deskripsi, $kode_praktikum);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum baru berhasil ditambahkan.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menambahkan praktikum: ' . $stmt->error . '</span></div>';
                }
            }
            $stmt->close();
        }
        $stmt_check_code->close();
    }
}

// Logika Hapus Praktikum (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $sql = "DELETE FROM mata_praktikum WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum berhasil dihapus.</span></div>';
        // Redirect untuk menghilangkan parameter GET setelah hapus
        header("Location: praktikum.php");
        exit();
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menghapus praktikum: ' . $stmt->error . '</span></div>';
    }
    $stmt->close();
}

// Logika Ambil Data untuk Edit (READ for UPDATE form)
$edit_praktikum = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_praktikum = $result->fetch_assoc();
    }
    $stmt->close();
}

// Ambil Semua Data Praktikum (READ for LIST)
$praktikums = [];
$sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $edit_praktikum ? 'Edit Praktikum' : 'Tambah Praktikum Baru'; ?></h2>
    
    <?php echo $message; ?>

    <form action="praktikum.php" method="POST">
        <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($edit_praktikum['id'] ?? ''); ?>">
        
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($edit_praktikum['nama_praktikum'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        
        <div class="mb-4">
            <label for="kode_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Kode Praktikum:</label>
            <input type="text" id="kode_praktikum" name="kode_praktikum" value="<?php echo htmlspecialchars($edit_praktikum['kode_praktikum'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-6">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($edit_praktikum['deskripsi'] ?? ''); ?></textarea>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <?php echo $edit_praktikum ? 'Perbarui Praktikum' : 'Tambah Praktikum'; ?>
            </button>
            <?php if ($edit_praktikum): ?>
                <a href="praktikum.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    Batal Edit
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Mata Praktikum</h2>
    <?php if (empty($praktikums)): ?>
        <p class="text-gray-600">Belum ada mata praktikum yang ditambahkan.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Praktikum
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kode
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Deskripsi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($praktikums as $praktikum): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="praktikum.php?action=edit&id=<?php echo $praktikum['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="praktikum.php?action=delete&id=<?php echo $praktikum['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Modul dan data terkait mungkin juga akan terhapus.')" class="text-red-600 hover:text-red-900">Hapus</a>
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