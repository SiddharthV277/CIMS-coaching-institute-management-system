-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2026 at 02:50 AM
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
(2, 'staff1', '$2y$10$KlIQ1y2/NzbIyD1IP10wzeH2ks7Jq3a3BVl9kOC13IWokn02OKyPO', 'staff', 'active', '2026-02-20 03:02:11');

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
  `duplicate_flag` enum('No','Might Be Duplicate') DEFAULT 'No',
  `heard_about` text DEFAULT NULL,
  `referred_student_name` varchar(150) DEFAULT NULL,
  `referred_student_phone` varchar(20) DEFAULT NULL,
  `heard_other_text` varchar(255) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_total` decimal(10,2) DEFAULT 0.00,
  `receipt_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_requests`
--

INSERT INTO `admission_requests` (`id`, `full_name`, `dob`, `gender`, `phone`, `email`, `photo`, `father_name`, `mother_name`, `guardian_phone`, `address`, `city`, `state`, `pincode`, `course`, `batch`, `admission_date`, `course_duration`, `total_fees`, `status`, `created_at`, `remark`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `medium`, `institution_name`, `institution_address`, `degree`, `percentage`, `main_subjects`, `passing_year`, `payment_amount`, `payment_structure`, `payment_mode`, `payment_date`, `duplicate_flag`, `heard_about`, `referred_student_name`, `referred_student_phone`, `heard_other_text`, `discount_type`, `discount_percent`, `discount_amount`, `final_total`, `receipt_number`) VALUES
(31, 'TEJASWI KUMARI', '2007-02-08', 'Female', '8210342148', '', NULL, 'BINOD KUMAR MAHTO', 'SONAMANI DEVI', '9097416552', 'GURUTOLI, NAMKUM', 'RANCHI', 'Jharkhand', '834010', 'Advanced Diploma in Computer Application', 'B', '2026-03-09', '12 Months', 8500.00, 'Pending', '2026-03-09 04:43:05', NULL, NULL, NULL, NULL, 'JAC', 'URSLINE INTERMEDITATE COLLEGE', 'RAJAULATU', 'Class 12', '0', 'NILL', '2026', NULL, NULL, NULL, NULL, 'No', NULL, '', '', '', NULL, 0.00, 0.00, 0.00, NULL),
(37, 'Sakshi Kachhap', '2009-07-02', 'Female', '9709054297', 'sakshikachhap5429@gmali.com', '1773456746_1981.png', 'Nirmal Kachhap', 'Binita Kacchap', '9334946237', 'shirkha Toli Khijri', 'Ranchi', 'Jharkhand', '834010', 'Basics of Computer', 'C', '2026-03-13', '3 Months', 4500.00, 'Approved', '2026-03-14 02:52:26', 'Student has picked a custom course of Computer Basics( MS Office, Internet Basics, Getting to know about AI).', NULL, 1, '2026-03-14 08:28:23', 'CBSE', 'Jawahar Navodaya Vidyalaya', 'BIT Mesra Rd, Mesra', 'Class 10', '80', 'Maths', '2026', 1000.00, 'Monthly', 'Cash', '2026-03-13', 'No', 'Others', '', '', 'From Known', '', 0.00, 0.00, 4500.00, NULL),
(38, 'Manish Indwar ', '1992-11-13', 'Male', '8797208869', 'manishindwar@rediffmail.com', '1773636092_6433.jpg', 'Somra Indwar', 'Kunti Indwar', '8797208869', 'SirkhaToli, Namkum', 'Ranchi', 'Jharkhand', '834010', 'Typing Practice', 'C', '2026-03-16', '3 Months', 2250.00, 'Approved', '2026-03-16 04:41:32', '', NULL, 1, '2026-03-16 10:26:01', 'Post Graduate', 'IGNOU', 'Ashok Nagar', 'M.COM', '00', 'Commerce', '2022', 700.00, 'Monthly', 'Online', '2026-03-16', 'No', NULL, '', '', '', 'General', 14.56, 150.00, 2100.00, NULL),
(39, 'Anish Lohra', '2009-01-01', 'Male', '08789998728', 'anishiohra650@gamil.com', '1773724898_2565.jpg', 'Charo Lohra', 'Sarita Devi', '9801136651', 'Charna Beda, Namkum, Tetri', 'Ranchi', 'Jharkhand', '834010', 'Advanced Diploma in Computer Application', 'D', '2026-03-17', '12 Months', 8500.00, 'Approved', '2026-03-17 05:21:38', '', NULL, 1, '2026-03-17 11:03:19', 'JAC', 'Project High School', 'Bargawa, Namkum', '10', '00', 'NILL', '2026', 1000.00, 'Monthly', 'Cash', '2026-03-17', 'No', 'Student', 'Lakhiya Tirki', '9572101031', '', 'General', 27.47, 2000.00, 6500.00, NULL);

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
(52, 47, 1, 5000.00, 5000.00, '2026-03-13', 'paid', 0.00, '2026-03-13 13:08:10'),
(54, 47, 2, 1750.00, 0.00, '2026-04-13', 'pending', 0.00, '2026-03-13 13:08:15'),
(55, 47, 3, 1750.00, 0.00, '2026-05-13', 'pending', 0.00, '2026-03-13 13:08:15'),
(56, 48, 1, 1000.00, 1000.00, '2026-03-13', 'paid', 0.00, '2026-03-14 02:59:20'),
(57, 48, 2, 1750.00, 0.00, '2026-04-13', 'pending', 0.00, '2026-03-14 02:59:20'),
(58, 48, 3, 1750.00, 0.00, '2026-05-13', 'pending', 0.00, '2026-03-14 02:59:20'),
(59, 50, 1, 700.00, 700.00, '2026-03-16', 'paid', 0.00, '2026-03-16 04:56:51'),
(60, 50, 2, 700.00, 0.00, '2026-04-16', 'pending', 0.00, '2026-03-16 04:56:51'),
(61, 50, 3, 700.00, 0.00, '2026-05-16', 'pending', 0.00, '2026-03-16 04:56:51'),
(62, 49, 1, 700.00, 700.00, '2026-03-16', 'paid', 0.00, '2026-03-16 04:56:58'),
(63, 49, 2, 700.00, 0.00, '2026-04-16', 'pending', 0.00, '2026-03-16 04:56:58'),
(64, 49, 3, 700.00, 0.00, '2026-05-16', 'pending', 0.00, '2026-03-16 04:56:58'),
(65, 51, 1, 1000.00, 1000.00, '2026-03-17', 'paid', 0.00, '2026-03-17 05:34:59'),
(66, 51, 2, 2750.00, 0.00, '2026-04-17', 'pending', 0.00, '2026-03-17 05:34:59'),
(67, 51, 3, 2750.00, 0.00, '2026-05-17', 'pending', 0.00, '2026-03-17 05:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `passed_out_students`
--

CREATE TABLE `passed_out_students` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
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
  `status` enum('Active','Completed','Dropped') DEFAULT 'Completed',
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
  `batch_id` int(11) DEFAULT NULL,
  `result_summary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `received_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `amount`, `payment_structure`, `payment_mode`, `payment_date`, `received_by`, `created_at`, `receipt_number`) VALUES
(40, 48, 1000.00, 'Monthly', 'Cash', '2026-03-13', 1, '2026-03-14 02:58:51', NULL),
(41, 49, 700.00, 'Full', 'Cash', '2026-03-16', 1, '2026-03-16 04:49:01', NULL),
(42, 50, 700.00, 'Monthly', 'Online', '2026-03-16', 1, '2026-03-16 04:56:16', NULL),
(43, 51, 1000.00, 'Monthly', 'Cash', '2026-03-17', 1, '2026-03-17 05:33:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
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
  `course_duration` varchar(255) DEFAULT NULL,
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
  `batch_id` int(11) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `admission_no`, `registration_no`, `full_name`, `dob`, `gender`, `phone`, `email`, `photo`, `father_name`, `mother_name`, `guardian_phone`, `address`, `city`, `state`, `pincode`, `course`, `batch`, `admission_date`, `course_duration`, `total_fees`, `fees_paid`, `status`, `created_at`, `updated_at`, `sequence_no`, `medium`, `institution_name`, `institution_address`, `degree`, `percentage`, `main_subjects`, `passing_year`, `discount_type`, `discount_percent`, `discount_amount`, `final_total`, `payment_structure`, `heard_about`, `referred_student_name`, `referred_student_phone`, `heard_other_text`, `batch_id`, `receipt_number`) VALUES
(48, 'VIG2026-001', '1119', 'Sakshi Kachhap', '2009-07-02', 'Female', '9709054297', 'sakshikachhap5429@gmali.com', '1773456746_1981.png', 'Nirmal Kachhap', 'Binita Kacchap', '9334946237', 'shirkha Toli Khijri', 'Ranchi', 'Jharkhand', '834010', 'Basics of Computer', 'C', '2026-03-13', '3 Months', 4500.00, 1000.00, 'Active', '2026-03-14 02:58:51', '2026-03-19 01:47:53', 1, 'CBSE', 'Jawahar Navodaya Vidyalaya', 'BIT Mesra Rd, Mesra', 'Class 10', '80', 'Maths', '2026', '', 0.00, 0.00, 4500.00, 'Monthly', 'Others', '', '', 'From Known', 3, '1682'),
(49, 'VIG2026-002', '1120', 'Anshul Lakra', '1989-11-11', 'Male', '9304651342', 'anshulbolt02@gmail.com', 'VIG2026-002.jpg', 'Biokal Lakra', 'Lila Lakra', '9304651342', 'Khijri, Namkum', 'Ranchi', 'Jharkhand', '834010', 'Typing Course', 'C', '2026-03-16', '3 Months', 2250.00, 700.00, 'Active', '2026-03-16 04:49:01', '2026-03-19 01:48:28', 2, 'Post Graduate', 'Jain College', 'Dhurva', 'Nagpuri Litrature', '00', 'Nagpuri Litrature', '2021', 'Amount', 14.56, 150.00, 2100.00, 'Full', NULL, '', '', '', 3, '1683'),
(50, 'VIG2026-003', '1121', 'Manish Indwar ', '1992-11-13', 'Male', '8797208869', 'manishindwar@rediffmail.com', '1773636092_6433.jpg', 'Somra Indwar', 'Kunti Indwar', '8797208869', 'SirkhaToli, Namkum', 'Ranchi', 'Jharkhand', '834010', 'Typing Practice', 'C', '2026-03-16', '3 Months', 2250.00, 700.00, 'Active', '2026-03-16 04:56:16', '2026-03-19 01:48:55', 3, 'Post Graduate', 'IGNOU', 'Ashok Nagar', 'M.COM', '00', 'Commerce', '2022', 'General', 14.56, 150.00, 2100.00, 'Monthly', NULL, '', '', '', 3, '1684'),
(51, 'VIG2026-004', '1125', 'Anish Lohra', '2009-01-01', 'Male', '9801136651', 'anishiohra650@gamil.com', '1773724898_2565.jpg', 'Charo Lohra', 'Sarita Devi', '9801136651', 'Charna Beda, Namkum, Tetri', 'Ranchi', 'Jharkhand', '834010', 'Advanced Diploma in Computer Application', 'D', '2026-03-17', '6 Months', 8500.00, 1000.00, 'Active', '2026-03-17 05:33:42', '2026-03-19 01:49:55', 4, 'JAC', 'Project High School', 'Bargawa, Namkum', '10', '00', 'NILL', '2026', 'General', 27.47, 2000.00, 6500.00, 'Monthly', 'Student', 'Lakhiya Tirki', '9572101031', '', 4, '1688');

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
-- Indexes for table `passed_out_students`
--
ALTER TABLE `passed_out_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`);

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
  ADD UNIQUE KEY `registration_no` (`registration_no`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `passed_out_students`
--
ALTER TABLE `passed_out_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
