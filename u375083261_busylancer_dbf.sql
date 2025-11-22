-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 21, 2025 at 10:01 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u375083261_busylancer_dbf`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `cover_message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_profiles`
--

CREATE TABLE `business_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `cr_number` varchar(50) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_size` enum('1-10','11-50','51-200','201-500','500+') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verification_date` date DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_jobs_posted` int(11) DEFAULT 0,
  `payment_punctuality_score` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('job','meeting','reminder','deadline') DEFAULT 'job',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#174F84',
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `education`
--

CREATE TABLE `education` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `period` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `education`
--

INSERT INTO `education` (`id`, `user_id`, `degree`, `institution`, `period`, `description`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 11, 'Bachelor', 'Imam Mohammed bin Saud', '2012-2017', '', NULL, NULL, '2025-11-18 18:09:32', '2025-11-18 18:09:32'),
(2, 11, 'Bachelor', 'Imam Mohammed bin Saud', '2012-2017', '', NULL, NULL, '2025-11-18 18:09:32', '2025-11-18 18:09:32'),
(3, 11, 'Health Care Security', 'Ministry of Health', '2021-2023', '', NULL, NULL, '2025-11-18 18:11:41', '2025-11-18 18:11:41'),
(4, 11, 'Nebosh Igc', 'NEBOSH', '2025', '', NULL, NULL, '2025-11-18 18:12:23', '2025-11-18 18:12:23'),
(5, 11, 'Nebosh Igc', 'NEBOSH', '2025', '', NULL, NULL, '2025-11-18 18:12:23', '2025-11-18 18:12:23');

-- --------------------------------------------------------

--
-- Table structure for table `experience`
--

CREATE TABLE `experience` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `period` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `current_job` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_applications`
--

CREATE TABLE `freelancer_applications` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `proposed_rate` decimal(10,2) DEFAULT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_availability`
--

CREATE TABLE `freelancer_availability` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0-6 (Sunday-Saturday)',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `timezone` varchar(50) DEFAULT 'UTC',
  `is_recurring` tinyint(1) DEFAULT 1,
  `specific_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_education`
--

CREATE TABLE `freelancer_education` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `field_of_study` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_employers`
--

CREATE TABLE `freelancer_employers` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freelancer_employers`
--

INSERT INTO `freelancer_employers` (`id`, `freelancer_id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `industry`, `website`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 'Test Client Inc.', 'John Doe', '', '', '', '', '', '', 'active', '2025-11-07 03:42:31', '2025-11-07 03:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_experience`
--

CREATE TABLE `freelancer_experience` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `company` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `current` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_jobs`
--

CREATE TABLE `freelancer_jobs` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `job_title` varchar(255) NOT NULL,
  `employer_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `job_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('in_progress','completed','cancelled','disputed') DEFAULT 'in_progress',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `client_rating` tinyint(4) DEFAULT NULL,
  `client_feedback` text DEFAULT NULL,
  `freelancer_rating` tinyint(4) DEFAULT NULL,
  `freelancer_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `source` varchar(50) NOT NULL DEFAULT 'platform'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freelancer_jobs`
--

INSERT INTO `freelancer_jobs` (`id`, `freelancer_id`, `job_id`, `job_title`, `employer_name`, `description`, `amount`, `job_date`, `due_date`, `status`, `started_at`, `completed_at`, `client_rating`, `client_feedback`, `freelancer_rating`, `freelancer_feedback`, `created_at`, `updated_at`, `source`) VALUES
(6, 5, NULL, 'تسويق', NULL, '', 400.00, '2025-11-08', '2025-11-08', 'in_progress', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 04:06:58', '2025-11-07 04:06:58', 'manual'),
(7, 5, NULL, 'jddd', NULL, '', 444.00, '2025-11-08', '2025-11-08', 'in_progress', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 04:12:11', '2025-11-07 04:12:11', 'manual'),
(8, 5, NULL, 'tgds', NULL, '', 444.00, '2025-11-08', '2025-11-08', 'in_progress', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 04:15:05', '2025-11-07 04:15:05', 'manual'),
(9, 5, NULL, 'htthfhdt', NULL, '', 600.00, '2025-11-09', '2025-11-10', 'in_progress', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 22:44:31', '2025-11-07 22:44:31', 'manual');

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_profiles`
--

CREATE TABLE `freelancer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `hourly_rate_min` decimal(10,2) DEFAULT 0.00,
  `hourly_rate_max` decimal(10,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `availability_status` enum('available','busy','unavailable') DEFAULT 'available',
  `timezone` varchar(50) DEFAULT 'UTC',
  `profile_completion_pct` int(11) DEFAULT 0,
  `trust_score` decimal(5,2) DEFAULT 0.00,
  `total_verified_gigs` int(11) DEFAULT 0,
  `profile_views` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `response_time` int(11) DEFAULT NULL COMMENT 'Average response time in hours',
  `ontime_rate_pct` int(11) DEFAULT 0,
  `job_success_rate` int(11) DEFAULT 100,
  `profile_completed` tinyint(1) DEFAULT 0,
  `profile_completion_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profile_completion_steps`)),
  `total_hours_worked` int(11) DEFAULT 0,
  `portfolio_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`portfolio_images`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `skills_text` varchar(255) GENERATED ALWAYS AS (ifnull(json_unquote(`skills`),'')) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freelancer_profiles`
--

INSERT INTO `freelancer_profiles` (`id`, `user_id`, `headline`, `bio`, `skills`, `hourly_rate_min`, `hourly_rate_max`, `hourly_rate`, `availability_status`, `timezone`, `profile_completion_pct`, `trust_score`, `total_verified_gigs`, `profile_views`, `average_rating`, `response_time`, `ontime_rate_pct`, `job_success_rate`, `profile_completed`, `profile_completion_steps`, `total_hours_worked`, `portfolio_images`, `languages`, `certifications`, `created_at`, `updated_at`) VALUES
(2, 3, '', '', '[]', 0.00, 0.00, 0.00, 'available', 'Asia/Riyadh', 30, 0.00, 0, 0, 0.00, NULL, 0, 100, 0, '[\"basic_info\",\"email\",\"phone\"]', 0, NULL, NULL, NULL, '2025-11-06 06:00:43', '2025-11-06 06:00:43');

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_skills`
--

CREATE TABLE `freelancer_skills` (
  `freelancer_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `years_experience` int(11) DEFAULT 1,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gigs`
--

CREATE TABLE `gigs` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `venue` varchar(160) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `geofence_m` int(11) DEFAULT 150,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `rate_type` enum('hourly','fixed') DEFAULT 'hourly',
  `rate_value` decimal(10,2) DEFAULT 0.00,
  `supervisor_name` varchar(120) DEFAULT NULL,
  `supervisor_phone` varchar(30) DEFAULT NULL,
  `brief` text DEFAULT NULL,
  `status` enum('upcoming','active','awaiting_payment','paid','cancelled') DEFAULT 'upcoming',
  `payment_status` enum('pending','reminded','paid') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `gigs`
--

INSERT INTO `gigs` (`id`, `freelancer_id`, `title`, `venue`, `address`, `lat`, `lng`, `geofence_m`, `start_time`, `end_time`, `rate_type`, `rate_value`, `supervisor_name`, `supervisor_phone`, `brief`, `status`, `payment_status`, `payment_date`, `payment_method`, `created_at`, `updated_at`) VALUES
(1, 5, 'ببثصقب', '', 'العيلاني, المصيف, محافظة الرياض, منطقة الرياض, 11537, السعودية', 24.7692367, 46.6833012, 150, '2025-11-09 07:09:00', '2025-11-09 07:16:00', 'fixed', 300.00, 'فيصل أحمد', '0511345304', 'رثسبيسبثشب', 'upcoming', 'pending', NULL, NULL, '2025-11-09 04:06:12', '2025-11-09 04:06:12'),
(2, 5, 'haifa', '', 'الإمام عبدالله بن ثنيان آل سعود, الضباط, الرياض, محافظة الرياض, منطقة الرياض, 12811, السعودية', 24.6876304, 46.7233468, 150, '2025-11-09 02:59:00', '2025-11-10 15:53:00', 'hourly', 300.00, 'فيصل أحمد', '+966511345304', 'utruywrye5ytew5y', 'upcoming', 'pending', NULL, NULL, '2025-11-09 09:53:48', '2025-11-09 11:16:58'),
(3, 5, 'checkin test', '', 'ممر زهير بن علقمة, المصيف, محافظة الرياض, منطقة الرياض, 12465, السعودية', 24.7692659, 46.6832852, 150, '2025-11-09 14:48:00', '2025-11-09 15:45:00', 'hourly', 400.00, 'حسام', '+966511345304', '', 'upcoming', 'pending', NULL, NULL, '2025-11-09 11:32:38', '2025-11-09 11:46:40'),
(4, 5, 'معرض', '', 'Garage Entrance, الرائد, بلدية المعذر, محافظة الرياض, منطقة الرياض, 12371, السعودية', 24.7186395, 46.6454716, 150, '2025-11-09 18:28:00', '2025-11-09 19:26:00', 'fixed', 400.00, 'انا', '00966511345304', '', 'upcoming', 'pending', NULL, NULL, '2025-11-09 15:25:59', '2025-11-09 15:25:59'),
(5, 5, 'ثقغقيف', '', 'ممر زهير بن علقمة, المصيف, محافظة الرياض, منطقة الرياض, 12465, السعودية', 24.7692974, 46.6832639, 150, '2025-11-11 17:01:00', '2025-11-11 17:07:00', 'fixed', 200.00, 'حسام', '+966511345304', '', 'upcoming', 'pending', NULL, NULL, '2025-11-10 14:03:18', '2025-11-10 14:03:18'),
(6, 5, 'samy', '', 'ممر زهير بن علقمة, المصيف, محافظة الرياض, منطقة الرياض, 12465, السعودية', 24.7692080, 46.6832592, 150, '2025-11-11 04:31:00', '2025-11-11 07:31:00', 'fixed', 400.00, 'sami', '+966537062958', 'any thing', 'upcoming', 'pending', NULL, NULL, '2025-11-11 00:32:11', '2025-11-11 00:32:11'),
(7, 5, 'تجربه', '', 'العيلاني, المصيف, محافظة الرياض, منطقة الرياض, 11537, السعودية', 24.7692502, 46.6833061, 150, '2025-11-12 05:06:00', '2025-11-12 06:06:00', 'hourly', 800.00, 'حسام', '+966511345304', '', 'active', 'pending', NULL, NULL, '2025-11-12 02:07:13', '2025-11-12 02:08:59'),
(8, 5, 'نجم', '', 'العيلاني, المصيف, محافظة الرياض, منطقة الرياض, 11537, السعودية', 24.7692076, 46.6833015, 150, '2025-11-12 05:14:00', '2025-11-12 05:14:00', 'fixed', 400.00, 'حسام', '+966511345304', 'ى ةتاةالتاتغ', 'active', 'pending', NULL, NULL, '2025-11-12 02:15:01', '2025-11-12 02:15:59'),
(9, 5, 'مستقل', '', 'الأوسي, المصيف, محافظة الرياض, منطقة الرياض, 12465, السعودية', 24.7595830, 46.6812982, 150, '2025-11-12 14:34:00', '2025-11-12 15:34:00', 'fixed', 500.00, 'حسام', '+966511345304', '', 'upcoming', 'pending', NULL, NULL, '2025-11-12 10:34:42', '2025-11-12 10:34:42'),
(10, 6, 'test', '', 'طريق الملك خالد الفرعي, حطين, الدرعية, بلدية الشمال, محافظة الدرعية, منطقة الرياض, 13521, السعودية', 24.7552624, 46.5807866, 150, '2025-11-24 15:09:00', '2025-11-17 05:11:00', 'hourly', 600.00, 'jay', '96654556767', 'twsr', 'upcoming', 'pending', NULL, NULL, '2025-11-13 12:14:35', '2025-11-13 12:14:35'),
(11, 5, 'تجربع', '', 'ممر زهير بن علقمة, المصيف, محافظة الرياض, منطقة الرياض, 12465, السعودية', 24.7692377, 46.6832745, 150, '2025-11-15 01:24:00', '2025-11-15 01:27:00', 'hourly', 400.00, 'حسام', '+966554370040', 'ف7هف7عهبتغع', 'upcoming', 'pending', NULL, NULL, '2025-11-14 22:21:28', '2025-11-14 22:21:28');

-- --------------------------------------------------------

--
-- Table structure for table `gig_attendance`
--

CREATE TABLE `gig_attendance` (
  `id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `checkin_lat` decimal(10,7) DEFAULT NULL,
  `checkin_lng` decimal(10,7) DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `checkout_lat` decimal(10,7) DEFAULT NULL,
  `checkout_lng` decimal(10,7) DEFAULT NULL,
  `total_minutes` int(11) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `gig_attendance`
--

INSERT INTO `gig_attendance` (`id`, `gig_id`, `freelancer_id`, `checkin_time`, `checkin_lat`, `checkin_lng`, `checkout_time`, `checkout_lat`, `checkout_lng`, `total_minutes`, `late_minutes`, `created_at`) VALUES
(1, 7, 5, '2025-11-12 05:07:51', 24.7692772, 46.6831152, NULL, NULL, NULL, 0, 0, '2025-11-12 02:07:51'),
(2, 8, 5, '2025-11-12 05:15:59', 24.7693305, 46.6831494, NULL, NULL, NULL, 0, 0, '2025-11-12 02:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `gig_confirm_tokens`
--

CREATE TABLE `gig_confirm_tokens` (
  `id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `token` char(32) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `gig_confirm_tokens`
--

INSERT INTO `gig_confirm_tokens` (`id`, `gig_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 3, 'b5dd3a4f9439bedeb913b132efb48896', '2025-11-16 15:22:47', 0, '2025-11-09 12:22:47'),
(2, 3, '3f59c8b0ccf630fc6e9ff2f9e679abd8', '2025-11-16 15:26:23', 0, '2025-11-09 12:26:23'),
(3, 3, 'ea7f7256bccd4149f68fd8dc4a40c1af', '2025-11-16 15:29:03', 0, '2025-11-09 12:29:03'),
(4, 3, 'd4418bf70f52e599ac62ea457a976948', '2025-11-16 15:31:33', 0, '2025-11-09 12:31:33'),
(5, 3, '7cd2deb92d1434269c9fd46f693dbfa2', '2025-11-16 15:32:17', 0, '2025-11-09 12:32:17'),
(6, 3, '8b102c8c46f13f2ec95f89e1c2fdb9b7', '2025-11-16 15:40:57', 0, '2025-11-09 12:40:57'),
(7, 3, 'ac2902db610ee33b7b8146ee92bae651', '2025-11-16 15:42:57', 0, '2025-11-09 12:42:57'),
(8, 3, 'fc686899276c7df799e50bdaddd14652', '2025-11-16 15:46:00', 0, '2025-11-09 12:46:00'),
(9, 3, 'f0c45f36198c1f2102e3d25800c1aa9b', '2025-11-16 15:46:33', 0, '2025-11-09 12:46:33'),
(10, 3, '62e874f3565c114fdcc7c179a868fe73', '2025-11-16 15:48:09', 1, '2025-11-09 12:48:09'),
(11, 3, '6a3063ca126b98110990e3adf88d14bf', '2025-11-16 15:52:14', 0, '2025-11-09 12:52:14'),
(12, 3, '0be5a97cd8017dc54eb9a7ff2c138a00', '2025-11-16 15:53:02', 0, '2025-11-09 12:53:02'),
(13, 3, '2f46cdfe044ed59aead49a0a55784e5c', '2025-11-16 15:53:18', 1, '2025-11-09 12:53:18'),
(14, 3, '1820bde76a2c6bdb301cbaa5f123f145', '2025-11-16 15:59:38', 1, '2025-11-09 12:59:38'),
(15, 4, '2d52e89cfdccce4125ca819c614dc55f', '2025-11-16 21:01:29', 1, '2025-11-09 18:01:29'),
(16, 5, '64a1d1e3194b01373b46a7fa3cea4c79', '2025-11-17 17:03:38', 1, '2025-11-10 14:03:38'),
(17, 5, '3e8b1bba953cbffaa993f35da582e6b2', '2025-11-17 23:26:37', 0, '2025-11-10 20:26:37'),
(18, 5, '88da7850f267dc8a8a92840c25759888', '2025-11-18 03:33:04', 0, '2025-11-11 00:33:04'),
(19, 5, 'b2b55efd3c9990940b9935c264ff3f4f', '2025-11-18 03:33:41', 0, '2025-11-11 00:33:41'),
(20, 6, 'c018e57b425b2dda5ffbadb47d57a3db', '2025-11-18 03:34:32', 1, '2025-11-11 00:34:32'),
(21, 9, '108cc38560ce8521fa23c52e316e8ed8', '2025-11-19 13:35:00', 0, '2025-11-12 10:35:00'),
(22, 4, 'fc7741d41f49aa949f6f5f90195b1523', '2025-11-21 02:42:44', 0, '2025-11-13 23:42:44'),
(23, 4, 'f2e83091974a325d8848846db2fff936', '2025-11-21 02:46:45', 0, '2025-11-13 23:46:45'),
(24, 11, 'e21631313009723e5639abd106c7c1a9', '2025-11-22 01:22:20', 1, '2025-11-14 22:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `gig_ratings`
--

CREATE TABLE `gig_ratings` (
  `id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `stars` tinyint(4) NOT NULL CHECK (`stars` between 1 and 5),
  `comment` varchar(300) DEFAULT NULL,
  `rater_name` varchar(120) DEFAULT NULL,
  `rater_phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `gig_ratings`
--

INSERT INTO `gig_ratings` (`id`, `gig_id`, `freelancer_id`, `stars`, `comment`, `rater_name`, `rater_phone`, `created_at`) VALUES
(1, 3, 5, 5, '', 'حسام', NULL, '2025-11-09 12:51:35'),
(2, 3, 5, 5, '', 'حسام', NULL, '2025-11-09 12:53:44'),
(3, 3, 5, 1, 'مش نافع', 'حسام', NULL, '2025-11-09 13:00:10'),
(4, 4, 5, 3, 'اداء مميز', 'انا', NULL, '2025-11-09 18:04:28'),
(5, 5, 5, 3, 'سيلقيلقيلفق', 'حسام', NULL, '2025-11-10 14:04:13'),
(6, 6, 5, 4, '', 'sami', NULL, '2025-11-11 00:34:52'),
(7, 11, 5, 4, '', 'حسام', NULL, '2025-11-14 22:22:57');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `job_type` enum('full-time','part-time','contract','freelance') DEFAULT 'freelance',
  `location` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `fixed_price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `skills_required` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `job_date` date DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` enum('active','inactive','filled','cancelled') DEFAULT 'active',
  `views_count` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `business_id`, `title`, `description`, `category`, `job_type`, `location`, `hourly_rate`, `fixed_price`, `duration`, `skills_required`, `responsibilities`, `requirements`, `benefits`, `job_date`, `application_deadline`, `status`, `views_count`, `applications_count`, `created_at`, `updated_at`) VALUES
(1, 4, 'استقبال', 'استقبال ضيوف الحدث وتوجييهم الي اماكن جلوسهم', 'ضيافة', '', 'Riyadh, السعودية', 300.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 0, 0, '2025-11-07 01:59:31', '2025-11-07 01:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`id`, `name`, `description`, `icon`, `status`, `created_at`) VALUES
(1, 'الأمن والحراسة', 'وظائف الأمن والحراسة والحماية', 'fas fa-shield-alt', 'active', '2025-11-06 05:40:47'),
(2, 'الاستقبال', 'وظائف الاستقبال والاستعلامات', 'fas fa-concierge-bell', 'active', '2025-11-06 05:40:47'),
(3, 'النماذج والعارضين', 'وظائف العارضين والنماذج', 'fas fa-user-tie', 'active', '2025-11-06 05:40:47'),
(4, 'تنظيم الفعاليات', 'تنظيم المؤتمرات والحفلات والفعاليات', 'fas fa-calendar-alt', 'active', '2025-11-06 05:40:47'),
(5, 'خدمة العملاء', 'خدمة العملاء والعلاقات العامة', 'fas fa-headset', 'active', '2025-11-06 05:40:47'),
(6, 'المبيعات والتسويق', 'وظائف البيع والتسويق الميداني', 'fas fa-shopping-cart', 'active', '2025-11-06 05:40:47'),
(7, 'النقل والتوصيل', 'وظائف السائقين والتوصيل', 'fas fa-truck', 'active', '2025-11-06 05:40:47'),
(8, 'الصيانة والنظافة', 'وظائف الصيانة والنظافة', 'fas fa-tools', 'active', '2025-11-06 05:40:47'),
(9, 'المطاعم والضيافة', 'وظائف المطاعم والخدمات الغذائية', 'fas fa-utensils', 'active', '2025-11-06 05:40:47'),
(10, 'التجزئة', 'وظائف البيع في المتاجر', 'fas fa-store', 'active', '2025-11-06 05:40:47'),
(11, 'البناء والتشييد', 'وظائف البناء والعمالة الميدانية', 'fas fa-hard-hat', 'active', '2025-11-06 05:40:47'),
(12, 'الرعاية الصحية', 'مساعدي الرعاية الصحية', 'fas fa-briefcase-medical', 'active', '2025-11-06 05:40:47'),
(13, 'التعليم والتدريب', 'مساعدي التدريس والتدريب', 'fas fa-chalkboard-teacher', 'active', '2025-11-06 05:40:47'),
(14, 'وظائف أخرى', 'وظائف مؤقتة أخرى', 'fas fa-briefcase', 'active', '2025-11-06 05:40:47');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `skills_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills_required`)),
  `positions_available` int(11) DEFAULT 1,
  `pay_rate` decimal(10,2) DEFAULT NULL,
  `pay_type` enum('hourly','fixed') DEFAULT 'hourly',
  `application_deadline` datetime DEFAULT NULL,
  `status` enum('draft','open','closed','filled','cancelled') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger','alert') DEFAULT 'info',
  `related_type` enum('job','application','message','payment','system') DEFAULT 'system',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `job_alerts` tinyint(1) DEFAULT 1,
  `application_updates` tinyint(1) DEFAULT 1,
  `messages` tinyint(1) DEFAULT 1,
  `payments` tinyint(1) DEFAULT 1,
  `marketing_emails` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio`
--

CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_items`
--

CREATE TABLE `portfolio_items` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `project_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `skills_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills_used`)),
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `type` enum('freelancer_to_client','client_to_freelancer') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `name`, `category`, `description`, `created_at`) VALUES
(78, 'خدمة العملاء', 'المهارات الشخصية', 'التعامل مع العملاء والزوار', '2025-11-06 05:51:50'),
(79, 'الاتصال الفعال', 'المهارات الشخصية', 'مهارات التواصل الشفهي والجسدي', '2025-11-06 05:51:50'),
(80, 'العمل الجماعي', 'المهارات الشخصية', 'القدرة على العمل ضمن فريق', '2025-11-06 05:51:50'),
(81, 'المرونة', 'المهارات الشخصية', 'القدرة على التكيف مع الظروف المتغيرة', '2025-11-06 05:51:50'),
(82, 'الانضباط', 'المهارات الشخصية', 'الالتزام بالمواعيد والتعليمات', '2025-11-06 05:51:50'),
(83, 'اللياقة البدنية', 'المهارات البدنية', 'القدرة على الوقوف أو المشي لفترات طويلة', '2025-11-06 05:51:50'),
(84, 'التوجيه والقيادة', 'المهارات الشخصية', 'القدرة على توجيه الآخرين عند الحاجة', '2025-11-06 05:51:50'),
(85, 'حل المشكلات', 'المهارات الشخصية', 'التعامل مع المواقف الطارئة', '2025-11-06 05:51:50'),
(86, 'المراقبة', 'الأمن', 'القدرة على الملاحظة والمراقبة', '2025-11-06 05:51:50'),
(87, 'التفتيش', 'الأمن', 'فحص الحقائب والأشخاص', '2025-11-06 05:51:50'),
(88, 'الإسعافات الأولية', 'المهارات الطبية', 'معرفة أساسيات الإسعافات الأولية', '2025-11-06 05:51:50'),
(89, 'البيع والتسويق', 'المبيعات', 'القدرة على إقناع العملاء', '2025-11-06 05:51:50'),
(90, 'تنظيم الفعاليات', 'التنظيم', 'ترتيب وتنسيق الفعاليات', '2025-11-06 05:51:50'),
(91, 'استخدام الأجهزة', 'المهارات التقنية', 'تشغيل أجهزة بسيطة', '2025-11-06 05:51:50'),
(92, 'قيادة المركبات', 'النقل', 'القدرة على قيادة المركبات', '2025-11-06 05:51:50'),
(93, 'التغليف', 'المهارات العملية', 'تغليف المنتجات والبضائع', '2025-11-06 05:51:50'),
(94, 'الترتيب والتنسيق', 'المهارات العملية', 'ترتيب المنتجات والعروض', '2025-11-06 05:51:50'),
(95, 'اللغة الإنجليزية', 'اللغات', 'التحدث باللغة الإنجليزية', '2025-11-06 05:51:50'),
(96, 'اللغة الفرنسية', 'اللغات', 'التحدث باللغة الفرنسية', '2025-11-06 05:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('earning','withdrawal','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `role` enum('freelancer','business','admin') DEFAULT 'freelancer',
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `gender` enum('ذكر','أنثى') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `has_driving_license` enum('نعم','لا') DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `body_shape` enum('نحيف','رياضي','متوسط','ممتلئ') DEFAULT NULL,
  `hair_color` enum('أسود','بني','أشقر','أحمر','رمادي') DEFAULT NULL,
  `eye_color` enum('أسود','بني','أزرق','أخضر','عسلي') DEFAULT NULL,
  `clothing_type` enum('حجاب','نقاب','بدون','تغطية كاملة') DEFAULT NULL,
  `languages` varchar(500) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `specializations` text DEFAULT NULL,
  `bank_iban` varchar(50) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `language` enum('ar','en') DEFAULT 'en',
  `currency` varchar(3) DEFAULT 'SAR',
  `is_profile_public` tinyint(1) DEFAULT 1,
  `is_calendar_public` tinyint(1) DEFAULT 1,
  `status` enum('active','suspended','deleted') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `email`, `email_verified`, `password`, `role`, `phone`, `whatsapp`, `phone_verified`, `city`, `gender`, `age`, `nationality`, `id_number`, `has_driving_license`, `height`, `weight`, `body_shape`, `hair_color`, `eye_color`, `clothing_type`, `languages`, `profile_photo`, `bio`, `specializations`, `bank_iban`, `hourly_rate`, `language`, `currency`, `is_profile_public`, `is_calendar_public`, `status`, `created_at`, `last_login`, `updated_at`) VALUES
(1, 'yupp test', 'yupp', 'test', '1@y.com', 0, '$2y$10$VEuPzx5v5C5MI3iPt7wYJ.ok7gT011nOK01bEjy3DiHDsHZ7qGv.e', 'freelancer', '', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-10-28 22:10:21', NULL, '2025-11-06 05:44:18'),
(2, '', 'Hossam', 'Aboassi', '8@misk.com', 0, '$2y$10$8bQAnstwiEQ5ejZu/WBDOu5wDz6QRwqvnonlJCtZCgwcQkJrnCC0C', 'business', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-06 05:57:29', NULL, '2025-11-06 05:57:29'),
(3, 'Hossam Aboassi', 'Hossam', 'Aboassi', 'omar@h.com', 0, '$2y$10$f5LyTXw6BsGSBYZqDLuS8OyVKeRHX4bFdBHGWRRDLW5ervDlZUO.K', 'freelancer', '+966511345304', NULL, 0, 'Riyadh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, 0.00, 'ar', 'SAR', 1, 1, 'active', '2025-11-06 05:59:23', NULL, '2025-11-06 06:00:43'),
(4, '', 'صاحب', 'عمل', 's@a.com', 0, '$2y$10$a6CUiXhC5TgkKUe/lXUreuVEJgVUbzyanKajWeDbeD/HC239Cin0O', 'business', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-06 12:33:01', NULL, '2025-11-06 12:33:01'),
(5, '', 'Hossam', 'Aboassi', 'f@l.com', 0, '$2y$10$T/mJLsYLGrLqRNzGmpEWIelH1Ca2M6mkEYH7qaAkBYzVyFQR8WPOi', 'freelancer', '+966511345304', '', 0, 'Riyadh', 'ذكر', 48, 'اردني', '2047858929', '', 0, 0, '', '', '', '', 'الإنجليزية', 'uploads/avatars/5_1762476194_half.png', NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-06 18:04:21', NULL, '2025-11-07 12:42:48'),
(6, '', 'm', 'jayant', 'jayant@gmail.com', 0, '$2y$10$l0WSkyjuBgvfDdhHwg8kAOBWr1rzBCE4krWHWTdSuSNZYqG/Sg4Sa', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-13 12:06:49', NULL, '2025-11-13 12:06:49'),
(7, '', 'reem', 'mohamednur', 'rayoomali99@gmail.com', 0, '$2y$10$emJnZTW.Vbmf0/iNuaS2zuWxg7IRspFXOkoHpppKHdFJ6lN7.YZSe', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-17 17:14:23', NULL, '2025-11-17 17:14:23'),
(8, '', 'بشير', 'كمال', 'bashirkamal644@gmail.com', 0, '$2y$10$Vl2VALOxeavqrr3nV419KO/k6Zy4y4L12IV8aL4ZPWByfT4mrBlmi', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-17 17:51:57', NULL, '2025-11-17 17:51:57'),
(9, '', 'Anas', 'Tigani', 'anes12213@gmail.com', 0, '$2y$10$J4t8ZEBvPsueh9SA27JfR.PO7n5RgFQAbuIY5khim4GOllvHmRAs.', 'freelancer', '+966544007323', '+966544007323', 0, 'Riyadh', 'ذكر', 24, 'Sudan', '2217682566', 'لا', 183, 70, '', 'أسود', 'بني', '', 'الإنجليزية,أخرى', NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-18 14:14:43', NULL, '2025-11-18 14:16:29'),
(10, '', '‏Muath', 'Al Rushud', 'mouathabdulrahman@icloud.con', 0, '$2y$10$5q55myZp21CIFPY03PLBauTeS9PqMH5929sku9B6.qAfrMKWvCHNm', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-18 17:48:28', NULL, '2025-11-18 17:48:28'),
(11, '', 'معاذ', 'عبدالرحمن', 'mouathabdulrahman@icloud.com', 0, '$2y$10$T130wryWBWHln76tcxaPCusJuwzP08.niUepYkoxco/y/IJ5ZcoV6', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/11_1763489638_IMG_1140.jpeg', NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-18 17:52:36', NULL, '2025-11-18 18:13:58'),
(12, '', 'مستقل', '48', '48@BL.com', 0, '$2y$10$pRS1I2eAV21om5KQHn2ineQK29nbNm9GlyBJBb16bMY7ybAnHb1IS', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-18 22:05:13', NULL, '2025-11-18 22:05:13'),
(13, '', 'مستقل', '04', '04@BL.com', 0, '$2y$10$qkPENjuAf1CfQhKDJWcJg.bEE8MVw/a948423j.rkCFey6HZ6vjcq', 'freelancer', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 'en', 'SAR', 1, 1, 'active', '2025-11-18 22:51:15', NULL, '2025-11-18 22:51:15');

-- --------------------------------------------------------

--
-- Table structure for table `user_media`
--

CREATE TABLE `user_media` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`freelancer_id`),
  ADD KEY `idx_job` (`job_id`),
  ADD KEY `idx_freelancer` (`freelancer_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `education`
--
ALTER TABLE `education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `experience`
--
ALTER TABLE `experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `freelancer_applications`
--
ALTER TABLE `freelancer_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`freelancer_id`,`job_id`),
  ADD KEY `idx_freelancer_id` (`freelancer_id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_applied_at` (`applied_at`);

--
-- Indexes for table `freelancer_availability`
--
ALTER TABLE `freelancer_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `freelancer_education`
--
ALTER TABLE `freelancer_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `freelancer_employers`
--
ALTER TABLE `freelancer_employers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_freelancer_id` (`freelancer_id`),
  ADD KEY `idx_company_name` (`company_name`);

--
-- Indexes for table `freelancer_experience`
--
ALTER TABLE `freelancer_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `freelancer_jobs`
--
ALTER TABLE `freelancer_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_freelancer_id` (`freelancer_id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_job_date` (`job_date`);

--
-- Indexes for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_freelancer_skills` (`skills_text`(191));

--
-- Indexes for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD PRIMARY KEY (`freelancer_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `gigs`
--
ALTER TABLE `gigs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_freelancer` (`freelancer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_freelancer_status` (`freelancer_id`,`status`);

--
-- Indexes for table `gig_attendance`
--
ALTER TABLE `gig_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gig_freelancer` (`gig_id`,`freelancer_id`),
  ADD KEY `idx_gig` (`gig_id`),
  ADD KEY `idx_freelancer` (`freelancer_id`);

--
-- Indexes for table `gig_confirm_tokens`
--
ALTER TABLE `gig_confirm_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_gig` (`gig_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `gig_ratings`
--
ALTER TABLE `gig_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gig` (`gig_id`),
  ADD KEY `idx_freelancer` (`freelancer_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business_id` (`business_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_job_type` (`job_type`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employer` (`employer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `portfolio`
--
ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviewer_id` (`reviewer_id`),
  ADD KEY `idx_reviewee_id` (`reviewee_id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_city` (`city`);

--
-- Indexes for table `user_media`
--
ALTER TABLE `user_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_media_type` (`media_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_profiles`
--
ALTER TABLE `business_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `education`
--
ALTER TABLE `education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `experience`
--
ALTER TABLE `experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_applications`
--
ALTER TABLE `freelancer_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_availability`
--
ALTER TABLE `freelancer_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_education`
--
ALTER TABLE `freelancer_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_employers`
--
ALTER TABLE `freelancer_employers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `freelancer_experience`
--
ALTER TABLE `freelancer_experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_jobs`
--
ALTER TABLE `freelancer_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gigs`
--
ALTER TABLE `gigs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `gig_attendance`
--
ALTER TABLE `gig_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gig_confirm_tokens`
--
ALTER TABLE `gig_confirm_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `gig_ratings`
--
ALTER TABLE `gig_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio`
--
ALTER TABLE `portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_media`
--
ALTER TABLE `user_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD CONSTRAINT `business_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `education`
--
ALTER TABLE `education`
  ADD CONSTRAINT `education_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `experience`
--
ALTER TABLE `experience`
  ADD CONSTRAINT `experience_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_applications`
--
ALTER TABLE `freelancer_applications`
  ADD CONSTRAINT `freelancer_applications_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `freelancer_applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_availability`
--
ALTER TABLE `freelancer_availability`
  ADD CONSTRAINT `fk_availability_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_education`
--
ALTER TABLE `freelancer_education`
  ADD CONSTRAINT `fk_education_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_employers`
--
ALTER TABLE `freelancer_employers`
  ADD CONSTRAINT `freelancer_employers_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_experience`
--
ALTER TABLE `freelancer_experience`
  ADD CONSTRAINT `fk_experience_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_jobs`
--
ALTER TABLE `freelancer_jobs`
  ADD CONSTRAINT `freelancer_jobs_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `freelancer_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_profiles`
--
ALTER TABLE `freelancer_profiles`
  ADD CONSTRAINT `freelancer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD CONSTRAINT `fk_freelancer_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_freelancer_skill_user` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolio`
--
ALTER TABLE `portfolio`
  ADD CONSTRAINT `portfolio_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD CONSTRAINT `fk_portfolio_freelancer` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_media`
--
ALTER TABLE `user_media`
  ADD CONSTRAINT `user_media_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
