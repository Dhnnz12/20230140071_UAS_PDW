<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php'; 

// Ambil data statistik dari database
$total_modul = 0;
$total_laporan_masuk = 0;
$laporan_belum_dinilai = 0;

// Query untuk Total Modul Diajarkan (semua modul yang ada di sistem)
$sql_total_modul = "SELECT COUNT(*) AS total FROM modul";
$result_total_modul = $conn->query($sql_total_modul);
if ($result_total_modul && $row = $result_total_modul->fetch_assoc()) {
    $total_modul = $row['total'];
}

// Query untuk Total Laporan Masuk (semua laporan yang diunggah mahasiswa)
$sql_total_laporan = "SELECT COUNT(*) AS total FROM laporan_tugas";
$result_total_laporan = $conn->query($sql_total_laporan);
if ($result_total_laporan && $row = $result_total_laporan->fetch_assoc()) {
    $total_laporan_masuk = $row['total'];
}

// Query untuk Laporan Belum Dinilai (laporan di laporan_tugas yang tidak ada di nilai_laporan)
$sql_belum_dinilai = "SELECT COUNT(lt.id) AS total 
                      FROM laporan_tugas lt
                      LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                      WHERE nl.id_laporan IS NULL";
$result_belum_dinilai = $conn->query($sql_belum_dinilai);
if ($result_belum_dinilai && $row = $result_belum_dinilai->fetch_assoc()) {
    $laporan_belum_dinilai = $row['total'];
}

// Ambil Aktivitas Laporan Terbaru (misal 5 laporan terakhir)
$latest_reports = [];
$sql_latest_reports = "SELECT 
                            lt.tanggal_submit, 
                            u.nama AS mahasiswa_nama, 
                            m.nama_modul,
                            mp.nama_praktikum
                        FROM laporan_tugas lt
                        JOIN users u ON lt.id_user = u.id
                        JOIN modul m ON lt.id_modul = m.id
                        JOIN mata_praktikum mp ON m.id_praktikum = mp.id
                        ORDER BY lt.tanggal_submit DESC
                        LIMIT 5"; // Ambil 5 laporan terbaru
$result_latest_reports = $conn->query($sql_latest_reports);
if ($result_latest_reports->num_rows > 0) {
    while ($row = $result_latest_reports->fetch_assoc()) {
        $latest_reports[] = $row;
    }
}

$conn->close();
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_modul; ?></p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_laporan_masuk; ?></p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $laporan_belum_dinilai; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <?php if (empty($latest_reports)): ?>
        <p class="text-gray-600">Belum ada aktivitas laporan terbaru.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($latest_reports as $report): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4 text-gray-500 font-bold text-sm">
                        <?php echo strtoupper(substr($report['mahasiswa_nama'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="text-gray-800"><strong><?php echo htmlspecialchars($report['mahasiswa_nama']); ?></strong> mengumpulkan laporan untuk <strong><?php echo htmlspecialchars($report['nama_modul']); ?></strong> (<?php echo htmlspecialchars($report['nama_praktikum']); ?>)</p>
                        <p class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($report['tanggal_submit'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// 3. Panggil Footer
require_once 'templates/footer.php';
?>