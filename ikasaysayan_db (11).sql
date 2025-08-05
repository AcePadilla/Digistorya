-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 09:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ikasaysayan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `question_type` varchar(50) DEFAULT NULL,
  `question_text` text DEFAULT NULL,
  `choices` text DEFAULT NULL,
  `correct_answer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `section_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `teacher_id`, `question_type`, `question_text`, `choices`, `correct_answer`, `created_at`, `section_id`, `title`) VALUES
(56, 0, 'truefalse', 'Test', NULL, 'True', '2025-06-04 15:49:01', 49, 'Biology'),
(57, 0, 'mcq', 'Test', '{\"A\":\"A\",\"B\":\"B\",\"C\":\"C\",\"D\":\"D\"}', 'A', '2025-06-04 15:49:01', 49, 'Biology');

-- --------------------------------------------------------

--
-- Table structure for table `activity_submissions`
--

CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `total_score` float DEFAULT NULL,
  `total_items` int(11) DEFAULT NULL,
  `percentage_score` float DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_submissions`
--

INSERT INTO `activity_submissions` (`id`, `user_id`, `section_id`, `activity_id`, `answer`, `is_correct`, `total_score`, `total_items`, `percentage_score`, `submitted_at`) VALUES
(37, 72, 49, 0, NULL, 0, 1, 2, 50, '2025-06-05 03:51:51'),
(38, 72, 0, 56, 'true', 1, NULL, NULL, NULL, '2025-06-05 03:51:51'),
(39, 72, 0, 57, 'B', 0, NULL, NULL, NULL, '2025-06-05 03:51:51'),
(40, 73, 49, 0, NULL, 0, 2, 2, 100, '2025-06-18 23:08:51'),
(41, 73, 0, 56, 'true', 1, NULL, NULL, NULL, '2025-06-18 23:08:51'),
(42, 73, 0, 57, 'A', 1, NULL, NULL, NULL, '2025-06-18 23:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(5, 'digistorya@gmail.com', 'Admin#123');

-- --------------------------------------------------------

--
-- Table structure for table `presentations`
--

CREATE TABLE `presentations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presentations`
--

INSERT INTO `presentations` (`id`, `title`, `image`, `file`, `uploaded_at`, `file_type`) VALUES
(48, 'rg', '../../assets/imageslogo.png', 'https://drive.google.com/file/d/1Mr3nXQCC2Rt7RUm0i0L7A6yxPWjafrrO/view?usp=drive_link', '2025-06-04 21:38:22', 'pdf');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `active_reflective` varchar(255) NOT NULL,
  `sensing_intuitive` varchar(255) NOT NULL,
  `visual_verbal` varchar(255) NOT NULL,
  `sequential_global` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `user_id`, `active_reflective`, `sensing_intuitive`, `visual_verbal`, `sequential_global`, `comments`, `created_at`) VALUES
(136, 73, '5|2', '3|4', '4|3', '2|5', 'Try', '2025-06-13 16:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_submissions`
--

CREATE TABLE `quiz_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `submission_date` date NOT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_submissions`
--

INSERT INTO `quiz_submissions` (`id`, `user_id`, `session_id`, `submission_date`, `score`) VALUES
(22, 66, 'v9t1se7i9m0nlsub3hdik7hjj2', '2025-06-01', 3),
(24, 69, 'v9t1se7i9m0nlsub3hdik7hjj2', '2025-06-01', 2),
(25, 70, 'v9t1se7i9m0nlsub3hdik7hjj2', '2025-06-01', 5),
(27, 66, 'v9t1se7i9m0nlsub3hdik7hjj2', '2025-06-02', 2),
(30, 72, '2u2bmp8lpsl994p33ndn6km5v3', '2025-06-04', 3);

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`id`, `section_name`) VALUES
(47, 'South-1'),
(48, 'South-2'),
(49, 'South-3'),
(50, 'South-4');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `otp` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `email`, `password`, `firstname`, `lastname`, `otp`) VALUES
(51, 'akosiacepadilla@gmail.com', '$2y$10$WLakjV5.m4Hc1wgKY3JZh.XiCMzgni0JSe73xaPju/O2XsUgN7ObO', 'Aira', 'Lucero', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_section`
--

CREATE TABLE `teacher_section` (
  `teacher_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_section`
--

INSERT INTO `teacher_section` (`teacher_id`, `section_id`) VALUES
(51, 47),
(51, 49);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `gender`, `email`, `password`, `section_id`, `otp`, `is_verified`) VALUES
(73, 'Ace', 'Padilla', 'Male', 'acco.padilla.au@phinmaed.com', '$2y$10$ViHDm2yGGQQvwPh4w9stg.s3FI2BWVSc9bnew4HMFur5GBKMV48M2', 49, '331023', 1),
(74, 'Fritz', 'Cornico', 'Female', 'akosiacepadilla@gmail.com', '$2y$10$KO/3yQCblTmr2disKYRfQutp6LAjxcn7FUFIkuw.EM39z9Ngc8iz2', 50, '449488', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`);

--
-- Indexes for table `presentations`
--
ALTER TABLE `presentations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_submission_per_month` (`user_id`,`submission_date`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_section`
--
ALTER TABLE `teacher_section`
  ADD PRIMARY KEY (`teacher_id`,`section_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD UNIQUE KEY `username_2` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `presentations`
--
ALTER TABLE `presentations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_section`
--
ALTER TABLE `teacher_section`
  ADD CONSTRAINT `teacher_section_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_section_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `section` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
