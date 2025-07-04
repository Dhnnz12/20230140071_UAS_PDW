<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Cari Praktikum';
$activePage = 'courses';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; 

$message = ''; // Untuk pesan sukses atau error
$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// Logika Mendaftar ke Praktikum (CREATE pendaftaran)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'daftar') {
    $id_praktikum_to_daftar = isset($_POST['id_praktikum']) ? intval($_POST['id_praktikum']) : 0;

    if ($id_praktikum_to_daftar > 0) {
        // Cek apakah mahasiswa sudah terdaftar di praktikum ini
        $sql_check_daftar = "SELECT id FROM pendaftaran_praktikum WHERE id_user = ? AND id_praktikum = ?";
        $stmt_check_daftar = $conn->prepare($sql_check_daftar);
        $stmt_check_daftar->bind_param("ii", $user_id, $id_praktikum_to_daftar);
        $stmt_check_daftar->execute();
        $stmt_check_daftar->store_result();

        if ($stmt_check_daftar->num_rows > 0) {
            $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Info!</strong><span class="block sm:inline"> Anda sudah terdaftar di praktikum ini.</span></div>';
        } else {
            // Lakukan pendaftaran
            $sql_insert_daftar = "INSERT INTO pendaftaran_praktikum (id_user, id_praktikum) VALUES (?, ?)";
            $stmt_insert_daftar = $conn->prepare($sql_insert_daftar);
            $stmt_insert_daftar->bind_param("ii", $user_id, $id_praktikum_to_daftar);

            if ($stmt_insert_daftar->execute()) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Berhasil mendaftar ke praktikum.</span></div>';
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mendaftar ke praktikum: ' . $stmt_insert_daftar->error . '</span></div>';
            }
            $stmt_insert_daftar->close();
        }
        $stmt_check_daftar->close();
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Praktikum tidak valid.</span></div>';
    }
}

// Logika Filter/Pencarian Praktikum
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil semua mata praktikum dan cek status pendaftaran untuk user yang login
$praktikums = [];
$sql_praktikum = "SELECT 
                    mp.id, 
                    mp.nama_praktikum, 
                    mp.deskripsi, 
                    mp.kode_praktikum,
                    pp.id AS pendaftaran_id -- Akan berisi NULL jika belum terdaftar
                  FROM mata_praktikum mp
                  LEFT JOIN pendaftaran_praktikum pp ON mp.id = pp.id_praktikum AND pp.id_user = ?";

$params = [$user_id];
$types = "i";

if (!empty($search_query)) {
    $sql_praktikum .= " WHERE mp.nama_praktikum LIKE ? OR mp.kode_praktikum LIKE ?";
    $params[] = "%" . $search_query . "%";
    $params[] = "%" . $search_query . "%";
    $types .= "ss";
}

$sql_praktikum .= " ORDER BY mp.nama_praktikum ASC";

$stmt_praktikum = $conn->prepare($sql_praktikum);
$stmt_praktikum->bind_param($types, ...$params);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();

if ($result_praktikum->num_rows > 0) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
$stmt_praktikum->close();
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Cari Mata Praktikum</h2>
    <?php echo $message; ?>

    <form action="courses.php" method="GET" class="mb-6 flex items-center space-x-2">
        <input type="text" name="search" placeholder="Cari berdasarkan nama atau kode praktikum..." 
               value="<?php echo htmlspecialchars($search_query); ?>"
               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            Cari
        </button>
        <?php if (!empty($search_query)): ?>
            <a href="courses.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($praktikums)): ?>
        <p class="text-gray-600">Tidak ada mata praktikum yang tersedia atau sesuai dengan pencarian Anda.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($praktikums as $praktikum): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                        <p class="text-sm text-gray-600 mb-3">Kode: <?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></p>
                        <p class="text-gray-700 text-sm mb-4"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                    </div>
                    <div>
                        <?php if ($praktikum['pendaftaran_id']): ?>
                            <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-3 py-1 rounded-full">Sudah Terdaftar</span>
                            <a href="my_courses.php" class="ml-2 text-blue-600 hover:underline text-sm">Lihat Praktikum Saya</a>
                        <?php else: ?>
                            <form action="courses.php" method="POST">
                                <input type="hidden" name="action" value="daftar">
                                <input type="hidden" name="id_praktikum" value="<?php echo $praktikum['id']; ?>">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline w-full">
                                    Daftar Praktikum Ini
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
?>