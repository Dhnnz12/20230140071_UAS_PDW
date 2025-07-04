<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; 

$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// Ambil semua praktikum yang diikuti oleh mahasiswa ini
$my_praktikums = [];
$sql = "SELECT 
            mp.id AS praktikum_id, 
            mp.nama_praktikum, 
            mp.deskripsi, 
            mp.kode_praktikum,
            pp.tanggal_daftar
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
        WHERE pp.id_user = ?
        ORDER BY pp.tanggal_daftar DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $my_praktikums[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Praktikum yang Saya Ikuti</h2>
    
    <?php if (empty($my_praktikums)): ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
            <p class="font-bold">Belum Ada Praktikum</p>
            <p>Anda belum mendaftar ke praktikum manapun. Silakan <a href="courses.php" class="font-semibold underline">cari praktikum</a> untuk memulai.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($my_praktikums as $praktikum): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                        <p class="text-sm text-gray-600 mb-3">Kode: <?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></p>
                        <p class="text-gray-700 text-sm mb-4 line-clamp-3"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-gray-500 mb-2">Terdaftar sejak: <?php echo date('d M Y', strtotime($praktikum['tanggal_daftar'])); ?></p>
                        <a href="course_detail.php?id=<?php echo $praktikum['praktikum_id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline w-full text-center block">
                            Lihat Detail Praktikum
                        </a>
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