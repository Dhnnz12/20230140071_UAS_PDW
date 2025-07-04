<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php'; 

$message = ''; // Untuk pesan sukses atau error

// Logika Tambah/Edit Pengguna (CREATE & UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']); // Password bisa kosong jika edit tanpa ganti password
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($role)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Nama, email, dan peran harus diisi.</span></div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Format email tidak valid.</span></div>';
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Peran tidak valid.</span></div>';
    } else {
        // Cek apakah email sudah terdaftar (kecuali untuk user_id yang sedang diedit)
        $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Email sudah terdaftar. Gunakan email lain.</span></div>';
        } else {
            if ($user_id > 0) {
                // Update Pengguna
                $sql = "UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?";
                $params = [$nama, $email, $role, $user_id];
                $types = "sssi";

                if (!empty($password)) { // Jika password diisi, update juga passwordnya
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?";
                    $params = [$nama, $email, $hashed_password, $role, $user_id];
                    $types = "ssssi";
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna berhasil diperbarui.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui pengguna: ' . $stmt->error . '</span></div>';
                }
            } else {
                // Tambah Pengguna Baru
                if (empty($password)) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Password harus diisi untuk pengguna baru.</span></div>';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna baru berhasil ditambahkan.</span></div>';
                    } else {
                        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menambahkan pengguna: ' . $stmt->error . '</span></div>';
                    }
                }
            }
            if (isset($stmt)) $stmt->close();
        }
        $stmt_check_email->close();
    }
}

// Logika Hapus Pengguna (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);

    // Mencegah asisten menghapus akunnya sendiri
    if ($id_to_delete == $_SESSION['user_id']) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Anda tidak dapat menghapus akun Anda sendiri.</span></div>';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna berhasil dihapus.</span></div>';
            // Redirect untuk menghilangkan parameter GET setelah hapus
            header("Location: users.php");
            exit();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menghapus pengguna: ' . $stmt->error . '</span></div>';
        }
        $stmt->close();
    }
}

// Logika Ambil Data untuk Edit (READ for UPDATE form)
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT id, nama, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Ambil Semua Data Pengguna (READ for LIST)
$users = [];
$sql = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $edit_user ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h2>
    
    <?php echo $message; ?>

    <form action="users.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['id'] ?? ''); ?>">
        
        <div class="mb-4">
            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($edit_user['nama'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password (Kosongkan jika tidak ingin mengubah):</label>
            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" <?php echo $edit_user ? '' : 'required'; ?>>
            <?php if ($edit_user): ?>
                <p class="text-xs text-gray-500 mt-1">Isi hanya jika Anda ingin mengubah password pengguna ini.</p>
            <?php endif; ?>
        </div>

        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Peran:</label>
            <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="mahasiswa" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <?php echo $edit_user ? 'Perbarui Pengguna' : 'Tambah Pengguna'; ?>
            </button>
            <?php if ($edit_user): ?>
                <a href="users.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    Batal Edit
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Pengguna</h2>
    <?php if (empty($users)): ?>
        <p class="text-gray-600">Belum ada pengguna terdaftar.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Peran
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Terdaftar Sejak
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 capitalize"><?php echo htmlspecialchars($user['role']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): // Mencegah asisten menghapus akunnya sendiri ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')" class="text-red-600 hover:text-red-900">Hapus</a>
                                <?php else: ?>
                                    <span class="text-gray-400"> (Anda)</span>
                                <?php endif; ?>
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