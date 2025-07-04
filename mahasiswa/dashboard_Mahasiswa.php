<?php

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; 

$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// Ambil data statistik untuk mahasiswa
$praktikum_diikuti = 0;
$tugas_selesai = 0; // Laporan yang sudah diunggah DAN sudah dinilai
$tugas_menunggu = 0; // Laporan yang belum diunggah ATAU sudah diunggah tapi belum dinilai

// Query Praktikum Diikuti
$sql_praktikum_diikuti = "SELECT COUNT(*) AS total FROM pendaftaran_praktikum WHERE id_user = ?";
$stmt_praktikum_diikuti = $conn->prepare($sql_praktikum_diikuti);
$stmt_praktikum_diikuti->bind_param("i", $user_id);
$stmt_praktikum_diikuti->execute();
$result_praktikum_diikuti = $stmt_praktikum_diikuti->get_result();
if ($result_praktikum_diikuti && $row = $result_praktikum_diikuti->fetch_assoc()) {
    $praktikum_diikuti = $row['total'];
}
$stmt_praktikum_diikuti->close();

// Query Tugas Selesai (laporan yang sudah disubmit DAN sudah dinilai)
$sql_tugas_selesai = "SELECT COUNT(lt.id) AS total
                      FROM laporan_tugas lt
                      JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                      WHERE lt.id_user = ? AND nl.nilai IS NOT NULL";
$stmt_tugas_selesai = $conn->prepare($sql_tugas_selesai);
$stmt_tugas_selesai->bind_param("i", $user_id);
$stmt_tugas_selesai->execute();
$result_tugas_selesai = $stmt_tugas_selesai->get_result();
if ($result_tugas_selesai && $row = $result_tugas_selesai->fetch_assoc()) {
    $tugas_selesai = $row['total'];
}
$stmt_tugas_selesai->close();

// Query Tugas Menunggu (total modul di praktikum yang diikuti - tugas selesai - tugas belum dikumpulkan)
// Ini adalah estimasi, kita bisa menghitung modul yang belum dikumpulkan atau belum dinilai.
// Cara paling mudah: Total modul yang seharusnya dikerjakan MINUS (tugas selesai + tugas belum dinilai)
// Atau lebih akurat: Jumlah modul di praktikum yang diikuti, dikurangi yang sudah selesai dinilai.
// Mari kita hitung: Jumlah laporan yang sudah disubmit tapi belum dinilai.
$sql_tugas_menunggu_dinilai = "SELECT COUNT(lt.id) AS total
                                FROM laporan_tugas lt
                                LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                                WHERE lt.id_user = ? AND nl.nilai IS NULL";
$stmt_tugas_menunggu_dinilai = $conn->prepare($sql_tugas_menunggu_dinilai);
$stmt_tugas_menunggu_dinilai->bind_param("i", $user_id);
$stmt_tugas_menunggu_dinilai->execute();
$result_tugas_menunggu_dinilai = $stmt_tugas_menunggu_dinilai->get_result();
if ($result_tugas_menunggu_dinilai && $row = $result_tugas_menunggu_dinilai->fetch_assoc()) {
    $tugas_menunggu = $row['total']; // Ini hanya laporan yang sudah di-submit tapi belum dinilai
}
$stmt_tugas_menunggu_dinilai->close();

// Query Notifikasi Terbaru (misal 3 notifikasi terkait nilai atau batas waktu)
// Ini akan lebih kompleks karena melibatkan banyak kriteria. Untuk sementara, kita akan buat contoh sederhana.
// Notifikasi terkait nilai baru atau laporan yang akan jatuh tempo.
$notifications = [];
// Contoh: Notifikasi nilai baru
$sql_notifications_nilai = "SELECT 
                                nl.tanggal_dinilai, 
                                m.nama_modul, 
                                mp.nama_praktikum,
                                nl.nilai
                            FROM nilai_laporan nl
                            JOIN laporan_tugas lt ON nl.id_laporan = lt.id
                            JOIN modul m ON lt.id_modul = m.id
                            JOIN mata_praktikum mp ON m.id_praktikum = mp.id
                            WHERE lt.id_user = ?
                            ORDER BY nl.tanggal_dinilai DESC
                            LIMIT 3"; // Batasi notifikasi
$stmt_notifications_nilai = $conn->prepare($sql_notifications_nilai);
$stmt_notifications_nilai->bind_param("i", $user_id);
$stmt_notifications_nilai->execute();
$result_notifications_nilai = $stmt_notifications_nilai->get_result();
if ($result_notifications_nilai->num_rows > 0) {
    while ($row = $result_notifications_nilai->fetch_assoc()) {
        $notifications[] = [
            'type' => 'nilai',
            'message' => "Nilai untuk **" . htmlspecialchars($row['nama_modul']) . "** pada praktikum **" . htmlspecialchars($row['nama_praktikum']) . "** telah diberikan: **" . htmlspecialchars($row['nilai']) . "**",
            'date' => $row['tanggal_dinilai']
        ];
    }
}
$stmt_notifications_nilai->close();

// Contoh: Notifikasi praktikum baru yang didaftar (jika ada) - mungkin lebih cocok di sini
$sql_notifications_daftar = "SELECT 
                                pp.tanggal_daftar,
                                mp.nama_praktikum,
                                mp.kode_praktikum
                            FROM pendaftaran_praktikum pp
                            JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
                            WHERE pp.id_user = ?
                            ORDER BY pp.tanggal_daftar DESC
                            LIMIT 3"; // Batasi notifikasi
$stmt_notifications_daftar = $conn->prepare($sql_notifications_daftar);
$stmt_notifications_daftar->bind_param("i", $user_id);
$stmt_notifications_daftar->execute();
$result_notifications_daftar = $stmt_notifications_daftar->get_result();
if ($result_notifications_daftar->num_rows > 0) {
    while ($row = $result_notifications_daftar->fetch_assoc()) {
        $notifications[] = [
            'type' => 'daftar',
            'message' => "Anda berhasil mendaftar pada mata praktikum **" . htmlspecialchars($row['nama_praktikum']) . "**.",
            'date' => $row['tanggal_daftar']
        ];
    }
}
$stmt_notifications_daftar->close();

// Sort notifications by date (newest first)
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$notifications = array_slice($notifications, 0, 5); // Ambil 5 notifikasi terbaru saja


$conn->close();
?>


<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-blue-600"><?php echo $praktikum_diikuti; ?></div>
        <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?php echo $tugas_selesai; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Selesai (Dinilai)</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?php echo $tugas_menunggu; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Menunggu (Dinilai)</div>
    </div>
    
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <?php if (empty($notifications)): ?>
        <p class="text-gray-600">Tidak ada notifikasi terbaru.</p>
    <?php else: ?>
        <ul class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
                <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                    <span class="text-xl mr-4">
                        <?php 
                            if ($notification['type'] == 'nilai') echo '✅';
                            else if ($notification['type'] == 'daftar') echo '🎉';
                            else echo '🔔'; // Default icon
                        ?>
                    </span>
                    <div>
                        <p class="text-gray-800"><?php echo $notification['message']; ?></p>
                        <p class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($notification['date'])); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
?>