-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 04, 2025 at 12:59 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uaspdw`
--

-- --------------------------------------------------------

--
-- Table structure for table `laporan_tugas`
--

CREATE TABLE `laporan_tugas` (
  `id` int(11) NOT NULL,
  `id_modul` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `file_laporan` varchar(255) NOT NULL,
  `tanggal_submit` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_tugas`
--

INSERT INTO `laporan_tugas` (`id`, `id_modul`, `id_user`, `file_laporan`, `tanggal_submit`) VALUES
(1, 1, 3, 'laporan_3_1_68677552568f0.pdf', '2025-07-04 06:31:46'),
(2, 1, 2, 'laporan_2_1_686775fd383db.pdf', '2025-07-04 06:34:37');

-- --------------------------------------------------------

--
-- Table structure for table `mata_praktikum`
--

CREATE TABLE `mata_praktikum` (
  `id` int(11) NOT NULL,
  `nama_praktikum` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kode_praktikum` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_praktikum`
--

INSERT INTO `mata_praktikum` (`id`, `nama_praktikum`, `deskripsi`, `kode_praktikum`, `created_at`) VALUES
(1, 'Praktikum Keamanan Siber', 'Praktikum ini dilaksanakan setiap rabu pagi', 'TI001', '2025-07-04 06:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `modul`
--

CREATE TABLE `modul` (
  `id` int(11) NOT NULL,
  `id_praktikum` int(11) NOT NULL,
  `nama_modul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_materi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modul`
--

INSERT INTO `modul` (`id`, `id_praktikum`, `nama_modul`, `deskripsi`, `file_materi`, `created_at`) VALUES
(1, 1, 'Praktikum PICOCTF', 'Mengerjakan 10 Soal PicoCTF', 'materi_6867753c168294.27390671.pdf', '2025-07-04 06:31:24');

-- --------------------------------------------------------

--
-- Table structure for table `nilai_laporan`
--

CREATE TABLE `nilai_laporan` (
  `id` int(11) NOT NULL,
  `id_laporan` int(11) NOT NULL,
  `id_asisten` int(11) NOT NULL,
  `nilai` int(3) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `tanggal_dinilai` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai_laporan`
--

INSERT INTO `nilai_laporan` (`id`, `id_laporan`, `id_asisten`, `nilai`, `feedback`, `tanggal_dinilai`) VALUES
(1, 1, 3, 75, 'Semangat', '2025-07-04 06:32:19'),
(2, 2, 3, 100, 'Keren', '2025-07-04 06:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran_praktikum`
--

CREATE TABLE `pendaftaran_praktikum` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_praktikum` int(11) NOT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pendaftaran_praktikum`
--

INSERT INTO `pendaftaran_praktikum` (`id`, `id_user`, `id_praktikum`, `tanggal_daftar`) VALUES
(1, 3, 1, '2025-07-04 06:30:02'),
(2, 2, 1, '2025-07-04 06:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Dhonan', 'dhonanhibrizi123@gmail.com', '$2y$10$Cb2.qKNkD5PTCEkQ2qM3DOPR9gCb7zKNv4MVVX5eRJzY4FVLLVQQ6', 'mahasiswa', '2025-07-03 10:32:21'),
(2, 'Hibrizi', 'hibrizidhonan789@gmail.com', '$2y$10$V4ap1cyrlCqNNkz33XHDyOH0JNQ9ZD4kBXIJqWO.JbSQJ6fiPHSya', 'mahasiswa', '2025-07-03 10:32:51'),
(3, 'Fikri', 'fikri123@gmail.com', '$2y$10$La1FfbU/v0zRd64qupn5TusfOofHxfgWgVo8puAhu2zslPy5nAYj2', 'asisten', '2025-07-03 10:39:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `laporan_tugas`
--
ALTER TABLE `laporan_tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_modul` (`id_modul`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `mata_praktikum`
--
ALTER TABLE `mata_praktikum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_praktikum` (`kode_praktikum`);

--
-- Indexes for table `modul`
--
ALTER TABLE `modul`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_praktikum` (`id_praktikum`);

--
-- Indexes for table `nilai_laporan`
--
ALTER TABLE `nilai_laporan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_laporan_unique` (`id_laporan`),
  ADD KEY `id_asisten` (`id_asisten`);

--
-- Indexes for table `pendaftaran_praktikum`
--
ALTER TABLE `pendaftaran_praktikum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_praktikum_unique` (`id_user`,`id_praktikum`),
  ADD KEY `id_praktikum` (`id_praktikum`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `laporan_tugas`
--
ALTER TABLE `laporan_tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mata_praktikum`
--
ALTER TABLE `mata_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `modul`
--
ALTER TABLE `modul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nilai_laporan`
--
ALTER TABLE `nilai_laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pendaftaran_praktikum`
--
ALTER TABLE `pendaftaran_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan_tugas`
--
ALTER TABLE `laporan_tugas`
  ADD CONSTRAINT `laporan_tugas_ibfk_1` FOREIGN KEY (`id_modul`) REFERENCES `modul` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_tugas_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `modul`
--
ALTER TABLE `modul`
  ADD CONSTRAINT `modul_ibfk_1` FOREIGN KEY (`id_praktikum`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nilai_laporan`
--
ALTER TABLE `nilai_laporan`
  ADD CONSTRAINT `nilai_laporan_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_tugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_laporan_ibfk_2` FOREIGN KEY (`id_asisten`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pendaftaran_praktikum`
--
ALTER TABLE `pendaftaran_praktikum`
  ADD CONSTRAINT `pendaftaran_praktikum_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pendaftaran_praktikum_ibfk_2` FOREIGN KEY (`id_praktikum`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
