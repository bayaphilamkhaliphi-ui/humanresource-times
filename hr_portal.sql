-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2026 at 11:25 AM
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
-- Database: `hr_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `agreement_logs`
--

CREATE TABLE `agreement_logs` (
  `id` int(11) NOT NULL,
  `employee_document_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(6, 'Advertising Manager'),
(5, 'Circulation Manager'),
(4, 'Finance Manager'),
(2, 'HR'),
(1, 'Managing Editor'),
(3, 'News Editor');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('KPI','SOP','Policy','Policies','Other') NOT NULL DEFAULT 'Other',
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `category`, `description`, `filename`, `uploaded_by`, `upload_date`) VALUES
(1, '2026 Q3 Sales Department KPIs', 'KPI', 'Target metrics and quarterly KPIs for sales personnel.', 'kpi_sales_q3_2026.pdf', 1, '2026-09-17 08:03:30'),
(2, 'Standard Operating Procedure - IT Security', 'SOP', 'SOP for remote work access and password compliance.', 'sop_it_security_2026.pdf', 1, '2026-09-17 08:03:30'),
(3, 'Times of Eswatini Employee Code of Conduct', 'Policy', 'Organisation-wide workplace policy and ethics guidelines.', 'policy_code_of_conduct.pdf', 1, '2026-09-17 08:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `status` enum('pending','viewed','agreed') DEFAULT 'pending',
  `viewed_at` timestamp NULL DEFAULT NULL,
  `agreed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_documents`
--

INSERT INTO `employee_documents` (`id`, `employee_id`, `document_id`, `status`, `viewed_at`, `agreed_at`) VALUES
(1, 9, 1, 'agreed', '2026-09-17 08:03:30', '2026-09-17 08:03:30'),
(2, 9, 2, 'agreed', '2026-09-17 08:03:30', '2026-09-17 08:03:30'),
(3, 9, 3, 'agreed', '2026-09-17 08:03:30', '2026-09-17 08:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_forms`
--

CREATE TABLE `kpi_forms` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `status` enum('draft','assigned','submitted','reviewed') DEFAULT 'draft',
  `manager_overall_rating` decimal(5,2) DEFAULT NULL,
  `manager_overall_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_outputs`
--

CREATE TABLE `kpi_outputs` (
  `id` int(11) NOT NULL,
  `kpi_task_id` int(11) NOT NULL,
  `output_description` text DEFAULT NULL,
  `output_weight` decimal(5,2) DEFAULT 0.00,
  `employee_rating` decimal(5,2) DEFAULT NULL,
  `employee_comment` text DEFAULT NULL,
  `manager_rating` decimal(5,2) DEFAULT NULL,
  `manager_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_tasks`
--

CREATE TABLE `kpi_tasks` (
  `id` int(11) NOT NULL,
  `kpi_form_id` int(11) NOT NULL,
  `task_description` text DEFAULT NULL,
  `task_weight` decimal(5,2) DEFAULT 0.00,
  `task_output` text DEFAULT NULL,
  `output_weight` decimal(5,2) DEFAULT 0.00,
  `employee_rating` decimal(5,2) DEFAULT NULL,
  `employee_comment` text DEFAULT NULL,
  `manager_rating` decimal(5,2) DEFAULT NULL,
  `manager_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','linemanager','employee') NOT NULL DEFAULT 'employee',
  `department_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `role`, `department_id`, `manager_id`, `created_at`) VALUES
(1, 'System Administrator', 'admin@eswatinitimes.co.sz', 'admin', '$2y$10$1kT3mHAAl/T09cuxVh9qhOZ0lK8K0FSsg2IavUqCQGfAMkSj1ktg2', 'admin', NULL, NULL, '2026-09-17 08:03:12'),
(2, 'HR Administrator', 'hr@eswatinitimes.co.sz', 'hradmin', '$2y$10$JBUXbLf7qLEJxr8Lr0c65eiFSmGJE9mkYxcyINRDrAMph5Yu49eRi', 'admin', NULL, NULL, '2026-09-17 08:03:12'),
(3, 'Managing Editor', 'managingeditor@times.co.sz', 'managingeditor', '0', 'linemanager', 1, 1, '2026-09-17 08:03:12'),
(4, 'HR Manager', 'hrdept@times.co.sz', 'hrmanager', '0', 'linemanager', 2, 1, '2026-09-17 08:03:12'),
(5, 'News Editor', 'newseditor@times.co.sz', 'newseditor', '0', 'linemanager', 3, 1, '2026-09-17 08:03:12'),
(6, 'Finance Manager', 'finance@times.co.sz', 'financemanager', '0', 'linemanager', 4, 1, '2026-09-17 08:03:12'),
(7, 'Circulation Manager', 'circulation@times.co.sz', 'circulationmgr', '0', 'linemanager', 5, 1, '2026-09-17 08:03:12'),
(8, 'Advertising Manager', 'advertising@times.co.sz', 'advertisingmgr', '0', 'linemanager', 6, 1, '2026-09-17 08:03:12'),
(9, 'Staff Member', 'staff@example.com', 'staff', '$2y$10$YK5TbU3o.NVcuEd56duC.um.oNiFxZZQcH7YyG5oG0RSYxgqLYmCq', 'employee', NULL, 1, '2026-09-17 08:03:12'),
(10, 'Bayaphila Mkhaliphi', 'bayaphila@times.co.sz', 'bayaphila', '$2y$10$y/Esw5Kc3jnMZoXm1irJdu2xs7FUmWGx3HbKmpdI5tTz8bPg045vq', 'employee', NULL, 1, '2026-09-17 08:03:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agreement_logs`
--
ALTER TABLE `agreement_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kpi_forms`
--
ALTER TABLE `kpi_forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kpi_outputs`
--
ALTER TABLE `kpi_outputs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kpi_tasks`
--
ALTER TABLE `kpi_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agreement_logs`
--
ALTER TABLE `agreement_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kpi_forms`
--
ALTER TABLE `kpi_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_outputs`
--
ALTER TABLE `kpi_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_tasks`
--
ALTER TABLE `kpi_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
