-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 08:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sports_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_missions`
--

CREATE TABLE `daily_missions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `pushups` int(11) DEFAULT NULL,
  `situps` int(11) DEFAULT NULL,
  `squatjumps` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_missions`
--

INSERT INTO `daily_missions` (`id`, `user_id`, `date`, `pushups`, `situps`, `squatjumps`, `completed`) VALUES
(1, 5, '2025-05-16', 21, 10, 15, 0);

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `pushups` int(11) DEFAULT NULL,
  `situps` int(11) DEFAULT NULL,
  `squatjumps` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(256) NOT NULL,
  `iv` binary(16) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `level` int(11) NOT NULL,
  `min_pushups` int(11) NOT NULL,
  `min_situps` int(11) NOT NULL,
  `min_squatjumps` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `iv`, `name`, `level`, `min_pushups`, `min_situps`, `min_squatjumps`, `created_at`) VALUES
(5, 'ahmadfairuzzakiwidyatna@gmail.com', '$2y$10$R5vIszAK.wdXcSj1W66RdOxNKSXKG/M0zCRJQQuUO9GR8XPCp8Oeq', 0x00000000000000000000000000000000, 'Ahmad Fairuz Zaki Widyatna Zaki Widyatna', 1, 0, 0, 0, '2025-05-15 23:49:46'),
(11, 'SnRpczN4WkJGWDdUb1F0NVVGcVIwam5QandzRDd1QXhGSTRRdUgwQTBwND0=', '$2y$10$3f6nkOmXcSpH0gmfcFSgLe6EG8dmHAlpUtGq5KD6.nPWrXV5MBg2O', 0x1d3507fdfc26cf5932a3e857a386baac, 'Ahmad Fairuz SpareTechWeb Zaki Widyatna', 1, 0, 0, 0, '2025-05-16 03:39:57'),
(12, 'Ni9xOWJITlMyUWNyK1lRdDY2SkIxN3FpM2dLN3JTRHpKTFhYeWprTWdJcW1FREFFYTJ1V0lRY3lPZ21sZ0pXdg==', '$2y$10$5du4Dvi.jP02iwUsvdzjn.R5WDFxHj.a.sAPWK34bUa.K0v/ZFYey', 0xf7e7e73b3d13699eaf561db85b2d8665, 'Jacky', 1, 0, 0, 0, '2025-05-16 13:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `workout_logs`
--

CREATE TABLE `workout_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movement_type` varchar(50) NOT NULL,
  `reps` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_missions`
--
ALTER TABLE `daily_missions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workout_logs`
--
ALTER TABLE `workout_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_missions`
--
ALTER TABLE `daily_missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `workout_logs`
--
ALTER TABLE `workout_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_missions`
--
ALTER TABLE `daily_missions`
  ADD CONSTRAINT `daily_missions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `workout_logs`
--
ALTER TABLE `workout_logs`
  ADD CONSTRAINT `workout_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
