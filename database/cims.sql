-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 04:49 AM
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

INSERT INTO `admins` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES (1, 'botadmin', 'y$jVG33ZX6liQmmOc2W6zaNOuF86Jjmj/Jm63fv8t5y5.1FEhRe.hei', 'superadmin', 'active', current_timestamp());



--
-- Dumping data for table `admins`
--



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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

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
