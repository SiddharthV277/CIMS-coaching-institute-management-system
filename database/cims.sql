-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 02:15 PM
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
-- Database: `cims`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','staff') DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'SidV', '$2y$10$bh6rwHxZR.bJEzdYXgkRdOlP8GAOisHKHXo3bWO/Ioi6y.4jhTdaC', 'superadmin', 'active', '2026-02-20 02:51:52'),
(2, 'staff1', '$2y$10$KlIQ1y2/NzbIyD1IP10wzeH2ks7Jq3a3BVl9kOC13IWokn02OKyPO', 'staff', 'active', '2026-02-20 03:02:11'),
(3, 'Staff2', '$2y$10$TB5IRfhqhiXurP4.mp8XIuY8uZfAPqwhksfjZwB38JsUjTpmZ/DTG', 'superadmin', 'inactive', '2026-02-20 04:05:11'),
(7, 'staff3', '$2y$10$AVWPbT3sHg54AuRQ3wTvpedtYmdj/O70TFnt7l2JDfJKgd/0c1DBa', 'staff', 'active', '2026-02-26 08:14:47');

-- --------------------------------------------------------

--
-- Table structure for table `admission_requests`
--

CREATE TABLE `admission_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `course_duration` varchar(50) DEFAULT NULL,
  `total_fees` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Shortlisted','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remark` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `medium` varchar(50) DEFAULT NULL,
  `institution_name` varchar(150) DEFAULT NULL,
  `institution_address` text DEFAULT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `percentage` varchar(10) DEFAULT NULL,
  `main_subjects` varchar(150) DEFAULT NULL,
  `passing_year` varchar(10) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_structure` enum('Full','Monthly','Quarterly','Custom') DEFAULT NULL,
  `payment_mode` enum('Cash','Online') DEFAULT NULL,
  `payment_date` varchar(50) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `duplicate_flag` enum('No','Might Be Duplicate') DEFAULT 'No',
  `heard_about` text DEFAULT NULL,
  `referred_student_name` varchar(150) DEFAULT NULL,
  `referred_student_phone` varchar(20) DEFAULT NULL,
  `heard_other_text` varchar(255) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_requests`
--

INSERT INTO `admission_requests` (`id`, `full_name`, `dob`, `gender`, `phone`, `email`, `photo`, `father_name`, `mother_name`, `guardian_phone`, `address`, `city`, `state`, `pincode`, `course`, `batch`, `admission_date`, `course_duration`, `total_fees`, `status`, `created_at`, `remark`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `medium`, `institution_name`, `institution_address`, `degree`, `percentage`, `main_subjects`, `passing_year`, `payment_amount`, `payment_structure`, `payment_mode`, `payment_date`, `receipt_image`, `duplicate_flag`, `heard_about`, `referred_student_name`, `referred_student_phone`, `heard_other_text`, `discount_type`, `discount_percent`, `discount_amount`, `final_total`) VALUES
(25, 'Menan Surin ', '2007-05-10', 'Male', '9142554525', 'surinmenan32@gmail.com', NULL, 'Kunwar Surin ', 'Aashish surin', '7762007788', 'Kochatoli namkum ranchi ', 'Ranchi ', 'Jharkhand ', '834010', 'Advanced Diploma in Computer Application', 'B', '2026-03-07', '12 Months', 8500.00, 'Approved', '2026-03-07 03:46:26', '', NULL, 1, '2026-03-07 09:27:12', '12th ', 'Vigyaan ', 'Kochatoli namkum ranchi ', '12th pass', '60%', 'Science ', '2025', 4000.00, 'Quarterly', 'Online', '03/07/2026', '1772855832_receipt.jpg', 'No', 'Student', 'Menan Surin ', '9142554525', '', '', 0.00, 0.00, 7280.00);

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `batch_name` char(1) NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 10,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `batch_name`, `time_slot`, `capacity`, `status`) VALUES
(1, 'A', '6:30AM TO 8:00AM', 10, 'Active'),
(2, 'B', '8:00AM TO 9:30AM', 10, 'Active'),
(3, 'C', '9:30AM TO 11:00AM', 10, 'Active'),
(4, 'D', '11:00AM TO 12:30PM', 10, 'Active'),
(5, 'E', '12:30PM TO 2:00PM', 10, 'Active'),
(6, 'F', '2:00PM TO 3:30PM', 10, 'Active'),
(7, 'G', '3:30PM TO 5:00PM', 10, 'Active'),
(8, 'H', '5:00PM TO 6:30PM', 10, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `fees` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `duration_months`, `fees`) VALUES
(1, 'Diploma in Financial Accounting', 4, 5500.00),
(2, 'Diploma in Graphics & DTP', 4, 8500.00),
(3, 'Advanced Diploma in Computer Application', 12, 8500.00),
(4, 'Web Design & Programming', 8, 8500.00),
(5, 'Advanced Diploma in Financial Accounting', 8, 8500.00);

-- --------------------------------------------------------

--
-- Table structure for table `fee_alerts`
--

CREATE TABLE `fee_alerts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `installment_id` int(11) NOT NULL,
  `alert_status` enum('active','snoozed') DEFAULT 'active',
  `snooze_until` date DEFAULT NULL,
  `last_alert_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_installments`
--

CREATE TABLE `fee_installments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `installment_no` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_date` date NOT NULL,
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_installments`
--

INSERT INTO `fee_installments` (`id`, `student_id`, `installment_no`, `amount`, `paid_amount`, `due_date`, `status`, `fine_amount`, `created_at`) VALUES
(1, 26, 1, 500.00, 0.00, '2026-11-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(2, 26, 2, 500.00, 0.00, '2026-12-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(3, 26, 3, 500.00, 0.00, '2027-01-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(4, 26, 4, 500.00, 0.00, '2027-02-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(5, 26, 5, 500.00, 0.00, '2027-03-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(6, 26, 6, 500.00, 0.00, '2027-04-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(7, 26, 7, 500.00, 0.00, '2027-05-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(8, 26, 8, 500.00, 0.00, '2027-06-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(9, 26, 9, 500.00, 0.00, '2027-07-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(10, 26, 10, 500.00, 0.00, '2027-08-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(11, 26, 11, 500.00, 0.00, '2027-09-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(12, 26, 12, 500.00, 0.00, '2027-10-04', 'pending', 0.00, '2026-03-03 03:57:03'),
(13, 27, 1, 2500.00, 0.00, '2026-03-03', 'paid', 0.00, '2026-03-03 04:08:23'),
(14, 27, 2, 1000.00, 0.00, '2026-04-03', 'pending', 0.00, '2026-03-03 04:08:23'),
(15, 27, 3, 1000.00, 0.00, '2026-05-03', 'pending', 0.00, '2026-03-03 04:08:23'),
(16, 27, 4, 1000.00, 0.00, '2026-06-03', 'pending', 0.00, '2026-03-03 04:08:23'),
(17, 27, 5, 1000.00, 0.00, '2026-07-03', 'pending', 0.00, '2026-03-03 04:08:23'),
(18, 27, 6, 1000.00, 0.00, '2026-08-03', 'pending', 0.00, '2026-03-03 04:08:23'),
(19, 34, 1, 2000.00, 0.00, '2026-03-03', 'paid', 0.00, '2026-03-03 23:44:58'),
(24, 34, 1, 2000.00, 0.00, '2026-03-03', 'paid', 0.00, '2026-03-03 23:48:10'),
(29, 34, 1, 2000.00, 0.00, '2026-03-03', 'paid', 0.00, '2026-03-03 23:48:22'),
(39, 34, 2, 1500.00, 0.00, '2026-04-03', 'pending', 0.00, '2026-03-03 23:55:18'),
(40, 34, 3, 1500.00, 0.00, '2026-05-03', 'pending', 0.00, '2026-03-03 23:55:18'),
(41, 34, 4, 1500.00, 0.00, '2026-06-03', 'pending', 0.00, '2026-03-03 23:55:18'),
(42, 35, 1, 2000.00, 0.00, '2026-03-04', 'paid', 0.00, '2026-03-07 03:03:35'),
(47, 35, 2, 1300.00, 0.00, '2026-04-04', 'pending', 0.00, '2026-03-07 03:03:41'),
(48, 35, 3, 1300.00, 0.00, '2026-05-04', 'pending', 0.00, '2026-03-07 03:03:41'),
(49, 35, 4, 1300.00, 0.00, '2026-06-04', 'pending', 0.00, '2026-03-07 03:03:41'),
(50, 35, 5, 1300.00, 0.00, '2026-07-04', 'pending', 0.00, '2026-03-07 03:03:41'),
(51, 35, 6, 1300.00, 0.00, '2026-08-04', 'pending', 0.00, '2026-03-07 03:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_structure` enum('Full','Monthly','Quarterly','Custom') NOT NULL,
  `payment_mode` enum('Cash','Online') NOT NULL,
  `payment_date` varchar(50) NOT NULL,
  `receipt_image` varchar(255) NOT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `course_duration` varchar(50) DEFAULT NULL,
  `total_fees` decimal(10,2) DEFAULT NULL,
  `fees_paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Completed','Dropped') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sequence_no` int(11) NOT NULL,
  `medium` varchar(50) DEFAULT NULL,
  `institution_name` varchar(150) DEFAULT NULL,
  `institution_address` text DEFAULT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `percentage` varchar(10) DEFAULT NULL,
  `main_subjects` varchar(150) DEFAULT NULL,
  `passing_year` varchar(10) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_total` decimal(10,2) DEFAULT 0.00,
  `payment_structure` varchar(20) DEFAULT NULL,
  `heard_about` text DEFAULT NULL,
  `referred_student_name` varchar(100) DEFAULT NULL,
  `referred_student_phone` varchar(20) DEFAULT NULL,
  `heard_other_text` varchar(255) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admission_requests`
--
ALTER TABLE `admission_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_name` (`batch_name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_alerts`
--
ALTER TABLE `fee_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_installments`
--
ALTER TABLE `fee_installments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`),
  ADD KEY `fk_batch` (`batch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admission_requests`
--
ALTER TABLE `admission_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fee_alerts`
--
ALTER TABLE `fee_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_installments`
--
ALTER TABLE `fee_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
