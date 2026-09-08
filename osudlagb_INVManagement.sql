-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 29, 2026 at 03:56 AM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `osudlagb_INVManagement`
--

-- --------------------------------------------------------

--
-- Table structure for table `advance_profit_adjustment`
--

CREATE TABLE `advance_profit_adjustment` (
  `id` int(11) NOT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `amount` float(20,2) NOT NULL DEFAULT '0.00',
  `transaction_date` date DEFAULT NULL,
  `month` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `adv_profit_adjusting_fund_type_A`
--

CREATE TABLE `adv_profit_adjusting_fund_type_A` (
  `date` date DEFAULT NULL,
  `id` int(11) NOT NULL,
  `amount` float(20,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `adv_profit_adjusting_fund_type_B`
--

CREATE TABLE `adv_profit_adjusting_fund_type_B` (
  `date` date DEFAULT NULL,
  `id` int(11) NOT NULL,
  `amount` float(20,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `directors`
--

CREATE TABLE `directors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `directors`
--

INSERT INTO `directors` (`id`, `name`, `mobile`, `created_at`, `updated_at`) VALUES
(1, 'Mohammad', '1756321023', '2025-08-28 00:00:00', NULL),
(2, 'Ushan', '1756321294', '2025-08-28 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `director_due_ledger`
--

CREATE TABLE `director_due_ledger` (
  `director_id` int(11) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `director_due_ledger`
--

INSERT INTO `director_due_ledger` (`director_id`, `due`) VALUES
(1, 136162);

-- --------------------------------------------------------

--
-- Table structure for table `director_monthly_due`
--

CREATE TABLE `director_monthly_due` (
  `director_id` int(11) NOT NULL,
  `due_month` varchar(27) NOT NULL,
  `due` float(20,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `director_monthly_due`
--

INSERT INTO `director_monthly_due` (`director_id`, `due_month`, `due`) VALUES
(1, '2025-09', 50000.00),
(1, '2025-10', 57000.00),
(1, '2025-11', 14538.00),
(1, '2025-12', 14624.00);

-- --------------------------------------------------------

--
-- Table structure for table `director_transactions`
--

CREATE TABLE `director_transactions` (
  `id` int(11) NOT NULL,
  `director_id` int(11) NOT NULL,
  `amount` float(20,2) NOT NULL,
  `transaction_month` varchar(12) DEFAULT NULL,
  `remarks` text,
  `transaction_date` date NOT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `designation` text,
  `phone_number` text,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_date` text,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `designation`, `phone_number`, `status`, `created_date`, `created_by`) VALUES
(1, 'Mohammad', 'SENIOR IT EXECUTIVE', '8801911599014', 'Active', '2025-05-23 12:18:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `investment_transactions`
--

CREATE TABLE `investment_transactions` (
  `id` int(11) NOT NULL,
  `investor_id` int(11) NOT NULL,
  `amount` float(20,2) NOT NULL,
  `transaction_month` varchar(12) DEFAULT NULL,
  `type` enum('add','withdraw') NOT NULL,
  `remarks` text,
  `transaction_date` date NOT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investment_transactions`
--

INSERT INTO `investment_transactions` (`id`, `investor_id`, `amount`, `transaction_month`, `type`, `remarks`, `transaction_date`, `created_at`) VALUES
(1, 167, 300000.00, '2025-09', 'add', 'N/A', '2025-09-16', '2026-01-14'),
(2, 168, 150000.00, '2025-09', 'add', 'N/A', '2025-09-16', '2026-01-14'),
(3, 169, 550000.00, '2025-09', 'add', 'N/A', '2025-09-16', '2026-01-14'),
(4, 167, 200000.00, '2025-10', 'add', 'N/A', '2025-10-02', '2026-01-14'),
(5, 168, 50000.00, '2025-10', 'add', 'N/A', '2025-10-03', '2026-01-14'),
(6, 169, 20000.00, '2025-10', 'withdraw', 'N/A', '2025-10-04', '2026-01-14'),
(7, 167, 100000.00, '2025-11', 'withdraw', 'N/A', '2025-11-01', '2026-01-14'),
(8, 168, 100000.00, '2025-11', 'add', 'N/A', '2025-11-02', '2026-01-14'),
(9, 169, 70000.00, '2025-11', 'add', 'N/A', '2025-11-02', '2026-01-14'),
(10, 170, 100000.00, '2025-12', 'add', 'N/A', '2025-12-01', '2026-01-14'),
(11, 171, 100000.00, '2025-12', 'add', 'N/A', '2025-12-04', '2026-01-14'),
(12, 172, 100000.00, '2025-09', 'add', 'N/A', '2025-09-25', '2026-01-14'),
(13, 172, 100000.00, '2025-09', 'withdraw', 'N/A', '2025-09-25', '2026-01-14'),
(14, 172, 100000.00, '2025-12', 'add', 'N/A', '2025-12-02', '2026-01-14');

-- --------------------------------------------------------

--
-- Table structure for table `investors`
--

CREATE TABLE `investors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `reference` text,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text,
  `profit` float(20,2) NOT NULL DEFAULT '0.00',
  `start_profit_month` varchar(27) DEFAULT NULL,
  `end_profit_month` varchar(27) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investors`
--

INSERT INTO `investors` (`id`, `name`, `reference`, `mobile`, `address`, `profit`, `start_profit_month`, `end_profit_month`, `created_at`, `updated_at`) VALUES
(167, 'Kazi Afzal Noor', 'MD', '1', '', 100.00, '2025-11', '2030-11', '2025-12-05 00:00:00', '2026-01-14 10:01:35'),
(168, 'Anwar Noor Topu', 'German', '2', '', 80.00, '2025-11', '2030-11', '2025-12-05 00:00:00', '2026-01-14 10:02:04'),
(169, 'Papun', 'MD', '3', '', 60.00, '2025-11', '2030-11', '2025-12-05 00:00:00', '2026-01-14 10:02:38'),
(170, 'Afzal 2', '', '', '', 100.00, '2025-12', '2030-12', '2026-01-14 00:00:00', NULL),
(171, 'Anwar 2', '', '8', '', 80.00, '2025-12', '2030-12', '2026-01-14 00:00:00', NULL),
(172, 'Papun 2', '', '223', '', 60.00, '2025-12', '2030-12', '2026-01-14 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `investor_advance_profit_adjustment`
--

CREATE TABLE `investor_advance_profit_adjustment` (
  `id` int(11) NOT NULL,
  `investor_id` int(11) DEFAULT NULL,
  `amount` float(20,2) NOT NULL DEFAULT '0.00',
  `transaction_date` date DEFAULT NULL,
  `month` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `investor_due_ledger`
--

CREATE TABLE `investor_due_ledger` (
  `investor_id` int(11) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investor_due_ledger`
--

INSERT INTO `investor_due_ledger` (`investor_id`, `due`) VALUES
(167, 400000),
(168, 300000),
(169, 600000),
(170, 100000),
(171, 100000),
(172, 100000);

-- --------------------------------------------------------

--
-- Table structure for table `investor_monthly_due`
--

CREATE TABLE `investor_monthly_due` (
  `investor_id` int(11) NOT NULL,
  `due_month` varchar(27) NOT NULL,
  `due` float(20,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investor_monthly_due`
--

INSERT INTO `investor_monthly_due` (`investor_id`, `due_month`, `due`) VALUES
(167, '2025-09', 300000.00),
(167, '2025-10', 200000.00),
(167, '2025-11', -100000.00),
(168, '2025-09', 150000.00),
(168, '2025-10', 50000.00),
(168, '2025-11', 100000.00),
(169, '2025-09', 550000.00),
(169, '2025-10', -20000.00),
(169, '2025-11', 70000.00),
(170, '2025-12', 100000.00),
(171, '2025-12', 100000.00),
(172, '2025-09', 0.00),
(172, '2025-12', 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `investor_monthly_profit_details`
--

CREATE TABLE `investor_monthly_profit_details` (
  `id` int(11) NOT NULL,
  `month` varchar(7) NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `investor_id` int(11) NOT NULL,
  `investment` float(20,2) DEFAULT '0.00' COMMENT 'Inv (c)',
  `investment_ratio` float(10,6) DEFAULT '0.000000' COMMENT 'Inv Ratio (d)',
  `estimated_profit` float(20,2) DEFAULT '0.00' COMMENT 'Disbursement Profit (e = a × d)	',
  `actual_profit_before_deed` float(20,2) DEFAULT '0.00' COMMENT 'Actual Profit (f = b × d)	',
  `deed_ratio` float(5,2) DEFAULT '0.00' COMMENT 'Deed Ratio % (g)	',
  `final_profit` float(20,2) DEFAULT '0.00' COMMENT 'Profit (h = f × g ÷ 100)	',
  `advance_paid` float(20,2) DEFAULT '0.00' COMMENT 'Advance Paid (i = e − h)\r\n',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investor_monthly_profit_details`
--

INSERT INTO `investor_monthly_profit_details` (`id`, `month`, `transaction_date`, `investor_id`, `investment`, `investment_ratio`, `estimated_profit`, `actual_profit_before_deed`, `deed_ratio`, `final_profit`, `advance_paid`, `created_at`) VALUES
(1, '2025-11', '2025-11-14', 167, 400000.00, 0.307692, 18153.85, 19384.62, 100.00, 19385.00, -1231.00, '2026-01-14 10:22:41'),
(2, '2025-11', '2025-11-14', 168, 300000.00, 0.230769, 13615.38, 14538.46, 80.00, 11631.00, 1984.00, '2026-01-14 10:22:41'),
(3, '2025-11', '2025-11-14', 169, 600000.00, 0.461538, 27230.77, 29076.92, 60.00, 17446.00, 9785.00, '2026-01-14 10:22:41'),
(4, '2025-12', '2025-12-14', 167, 400000.00, 0.250000, 17500.00, 16250.00, 100.00, 16250.00, 1250.00, '2026-01-14 10:23:27'),
(5, '2025-12', '2025-12-14', 168, 300000.00, 0.187500, 13125.00, 12187.50, 80.00, 9750.00, 3375.00, '2026-01-14 10:23:27'),
(6, '2025-12', '2025-12-14', 169, 600000.00, 0.375000, 26250.00, 24375.00, 60.00, 14625.00, 11625.00, '2026-01-14 10:23:27'),
(7, '2025-12', '2025-12-14', 170, 100000.00, 0.062500, 4375.00, 4062.50, 100.00, 4063.00, 312.00, '2026-01-14 10:23:27'),
(8, '2025-12', '2025-12-14', 171, 100000.00, 0.062500, 4375.00, 4062.50, 80.00, 3250.00, 1125.00, '2026-01-14 10:23:27'),
(9, '2025-12', '2025-12-14', 172, 100000.00, 0.062500, 4375.00, 4062.50, 60.00, 2438.00, 1937.00, '2026-01-14 10:23:27');

-- --------------------------------------------------------

--
-- Table structure for table `investor_profit_due_ledger`
--

CREATE TABLE `investor_profit_due_ledger` (
  `investor_id` int(11) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investor_profit_due_ledger`
--

INSERT INTO `investor_profit_due_ledger` (`investor_id`, `due`) VALUES
(167, 19),
(168, 5359),
(169, 21410),
(170, 312),
(171, 1125),
(172, 1937);

-- --------------------------------------------------------

--
-- Table structure for table `investor_profit_monthly_due`
--

CREATE TABLE `investor_profit_monthly_due` (
  `investor_id` int(11) NOT NULL,
  `due_month` varchar(27) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `investor_profit_monthly_due`
--

INSERT INTO `investor_profit_monthly_due` (`investor_id`, `due_month`, `due`) VALUES
(167, '2025-11', -1231),
(167, '2025-12', 1250),
(168, '2025-11', 1984),
(168, '2025-12', 3375),
(169, '2025-11', 9785),
(169, '2025-12', 11625),
(170, '2025-12', 312),
(171, '2025-12', 1125),
(172, '2025-12', 1937);

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `menu_name` varchar(255) NOT NULL,
  `menu_link` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT '0',
  `is_a_parent_id` varchar(10) NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `parent_id`, `menu_name`, `menu_link`, `icon`, `sort_order`, `is_a_parent_id`) VALUES
(1, NULL, 'Dashboard', 'dynamic-page.php?page=Home', 'fas fa-tachometer-alt', 1, 'No'),
(2, NULL, 'Investors', NULL, 'fas fa-cash-register', 4, 'Yes'),
(3, NULL, 'Sector', NULL, 'fas fa-shopping-cart', 3, 'Yes'),
(4, NULL, 'Investment', NULL, 'fas fa-copy', 5, 'Yes'),
(5, 2, 'New Investor', 'dynamic-page.php?page=New-Investor', 'far fa-circle', 1, 'No'),
(10, 2, 'All Investor', 'dynamic-page.php?page=List-Investor', 'far fa-circle', 2, 'No'),
(11, 3, 'New Sector', 'dynamic-page.php?page=New-Sector', 'far fa-circle', 1, 'No'),
(12, 3, 'All Sector', 'dynamic-page.php?page=List-Sector', 'far fa-circle', 2, 'No'),
(13, 4, 'New/Return', 'dynamic-page.php?page=Investments', 'far fa-circle', 1, 'No'),
(18, 19, 'Sector Profit', 'dynamic-page.php?page=Sector-Profit', 'far fa-circle', 3, 'No'),
(19, NULL, 'Profit', NULL, 'fas fa-copy', 7, 'Yes'),
(20, 19, 'Investor Profit', 'dynamic-page.php?page=Investor-Profit', 'far fa-circle', 3, 'No'),
(21, NULL, 'Reports ', NULL, 'fas fa-copy', 9, 'Yes'),
(22, 21, 'Sector Ledger', 'dynamic-page.php?page=Sector-Ledger', 'fas fa-copy', 2, 'No'),
(24, 21, 'Investor Ledger ', 'dynamic-page.php?page=Investor-Ledger', 'fas fa-copy', 1, 'No'),
(25, 21, 'MY Ledger', 'dynamic-page.php?page=MY-Ledger', 'far fa-circle', 3, 'No'),
(26, 38, 'Type A ', 'dynamic-page.php?page=Advance-Profit-Adjustment-Type-A', 'far fa-circle', 1, 'No'),
(27, 4, 'Sector Wise', 'dynamic-page.php?page=Sector-Wise', 'far fa-circle', 2, 'No'),
(28, 21, 'Investment', 'dynamic-page.php?page=Investment-Profit', 'far fa-circle', 3, 'No'),
(29, NULL, 'M/Y', NULL, 'fas fa-copy', 2, 'Yes'),
(30, 29, 'New Director', 'dynamic-page.php?page=New-Director', 'fas fa-copy', 2, 'No'),
(31, 29, 'Director List', 'dynamic-page.php?page=List-Director', 'fas fa-copy', 3, 'No'),
(32, 29, 'Withdraw', 'dynamic-page.php?page=MY-Withdraw', 'fas fa-copy', 1, 'No'),
(33, 21, 'Profit Adjustment', 'dynamic-page.php?page=Advance-Profit-Adjustment-Report', 'far fa-circle', 3, 'No'),
(34, NULL, 'Opening', NULL, 'fas fa-copy', 6, 'Yes'),
(35, 34, 'M/Y', 'dynamic-page.php?page=Opening-Amount-MY', 'far fa-circle', 1, 'No'),
(36, 34, 'Investor Advance', 'dynamic-page.php?page=Opening-Investor-Advance', 'far fa-circle', 2, 'No'),
(37, 34, 'Sector Advance', 'dynamic-page.php?page=Opening-Sector-Advance', 'far fa-circle', 3, 'No'),
(38, NULL, 'Adv Profit Adjust', NULL, 'fas fa-copy', 8, 'Yes'),
(39, 38, 'Type B', 'dynamic-page.php?page=Advance-Profit-Adjustment-Type-B', 'far fa-circle', 2, 'No'),
(40, 38, 'Type C', 'dynamic-page.php?page=Advance-Profit-Adjustment', 'far fa-circle', 3, 'No');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_profit_summary`
--

CREATE TABLE `monthly_profit_summary` (
  `month` varchar(7) NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `total_estimated_profit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_actual_profit_before_deed` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_final_profit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_advance_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `my_amount` float(20,2) NOT NULL DEFAULT '0.00',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monthly_profit_summary`
--

INSERT INTO `monthly_profit_summary` (`month`, `transaction_date`, `total_estimated_profit`, `total_actual_profit_before_deed`, `total_final_profit`, `total_advance_paid`, `my_amount`, `updated_at`) VALUES
('2025-09', '2025-09-14', 60000.00, 0.00, 0.00, 0.00, 50000.00, '2026-01-14 10:19:33'),
('2025-10', '2025-10-14', 61000.00, 0.00, 0.00, 0.00, 57000.00, '2026-01-14 10:21:26'),
('2025-11', '2025-11-14', 59000.00, 63000.00, 48462.00, 10538.00, 14538.00, '2026-01-14 10:22:41'),
('2025-12', '2025-12-14', 70000.00, 65000.00, 50376.00, 19624.00, 14624.00, '2026-01-14 10:23:27');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_sector_profit`
--

CREATE TABLE `monthly_sector_profit` (
  `id` int(11) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `month` text NOT NULL COMMENT 'e.g. 2025-08-01',
  `transaction_date` date DEFAULT NULL,
  `estimated_profit` decimal(12,2) DEFAULT NULL,
  `actual_profit` decimal(12,2) DEFAULT NULL,
  `profit_adjustment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_estimate` tinyint(1) DEFAULT '0',
  `created_at` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monthly_sector_profit`
--

INSERT INTO `monthly_sector_profit` (`id`, `sector_id`, `month`, `transaction_date`, `estimated_profit`, `actual_profit`, `profit_adjustment`, `is_estimate`, `created_at`, `updated_at`) VALUES
(1, 25, '2025-09', '2025-09-14', 10000.00, 5000.00, 0.00, 0, '2026-01-14', NULL),
(2, 26, '2025-09', '2025-09-14', 10000.00, 5000.00, 0.00, 0, '2026-01-14', NULL),
(3, 27, '2025-09', '2025-09-14', 20000.00, 20000.00, 0.00, 0, '2026-01-14', NULL),
(4, 28, '2025-09', '2025-09-14', 20000.00, 20000.00, 0.00, 0, '2026-01-14', NULL),
(5, 25, '2025-10', '2025-10-14', 12000.00, 10000.00, 0.00, 0, '2026-01-14', NULL),
(6, 26, '2025-10', '2025-10-14', 12000.00, 10000.00, 0.00, 0, '2026-01-14', NULL),
(7, 27, '2025-10', '2025-10-14', 15000.00, 15000.00, 0.00, 0, '2026-01-14', NULL),
(8, 28, '2025-10', '2025-10-14', 22000.00, 22000.00, 0.00, 0, '2026-01-14', NULL),
(9, 25, '2025-11', '2025-11-14', 12000.00, 10000.00, 0.00, 0, '2026-01-14', NULL),
(10, 26, '2025-11', '2025-11-14', 10000.00, 16000.00, 0.00, 0, '2026-01-14', NULL),
(11, 27, '2025-11', '2025-11-14', 15000.00, 15000.00, 0.00, 0, '2026-01-14', NULL),
(12, 28, '2025-11', '2025-11-14', 22000.00, 22000.00, 0.00, 0, '2026-01-14', NULL),
(13, 25, '2025-12', '2025-12-14', 15000.00, 15000.00, 0.00, 0, '2026-01-14', NULL),
(14, 26, '2025-12', '2025-12-14', 25000.00, 20000.00, 0.00, 0, '2026-01-14', NULL),
(15, 27, '2025-12', '2025-12-14', 10000.00, 10000.00, 0.00, 0, '2026-01-14', NULL),
(16, 28, '2025-12', '2025-12-14', 20000.00, 20000.00, 0.00, 0, '2026-01-14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `opening_director_due`
--

CREATE TABLE `opening_director_due` (
  `id` int(11) NOT NULL,
  `director_id` int(11) DEFAULT NULL,
  `amount` float(20,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `month` varchar(12) DEFAULT NULL,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `opening_investor_profit_due`
--

CREATE TABLE `opening_investor_profit_due` (
  `id` int(11) NOT NULL,
  `investor_id` int(11) DEFAULT NULL,
  `amount` float(20,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `month` varchar(12) DEFAULT NULL,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `opening_sector_profit_due`
--

CREATE TABLE `opening_sector_profit_due` (
  `id` int(11) NOT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `amount` float(20,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `month` varchar(12) DEFAULT NULL,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `name`, `mobile`, `address`, `created_at`, `updated_at`) VALUES
(25, 'Moto Craft', '1764921045', 'n/a', '2025-12-05 00:00:00', '2026-01-14 09:59:46'),
(26, 'Bike X', '1768363187', 'n/a', '2026-01-14 00:00:00', NULL),
(27, 'China House BD', '1768363206', 'n/a', '2026-01-14 00:00:00', NULL),
(28, 'Jersey Freak BD Mirpur', '1768363214', 'n/a', '2026-01-14 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sector_due_ledger`
--

CREATE TABLE `sector_due_ledger` (
  `sector_id` int(11) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sector_due_ledger`
--

INSERT INTO `sector_due_ledger` (`sector_id`, `due`) VALUES
(25, 200000),
(26, 720000),
(27, 280000),
(28, 400000);

-- --------------------------------------------------------

--
-- Table structure for table `sector_investments`
--

CREATE TABLE `sector_investments` (
  `id` int(11) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('add','withdraw') NOT NULL,
  `transaction_date` date NOT NULL,
  `remarks` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sector_investments`
--

INSERT INTO `sector_investments` (`id`, `sector_id`, `amount`, `type`, `transaction_date`, `remarks`, `created_at`) VALUES
(1, 25, 100000.00, 'add', '2025-09-16', 'N/A', '2026-01-14 00:00:00'),
(2, 26, 200000.00, 'add', '2025-09-16', 'N/A', '2026-01-14 00:00:00'),
(3, 27, 300000.00, 'add', '2025-09-16', 'N/A', '2026-01-14 00:00:00'),
(4, 28, 400000.00, 'add', '2025-09-16', 'N/A', '2026-01-14 00:00:00'),
(5, 25, 100000.00, 'add', '2025-10-05', 'N/A', '2026-01-14 00:00:00'),
(6, 26, 150000.00, 'add', '2025-10-05', 'N/A', '2026-01-14 00:00:00'),
(7, 27, 20000.00, 'withdraw', '2025-10-05', 'N/A', '2026-01-14 00:00:00'),
(8, 26, 70000.00, 'add', '2025-09-16', 'N/A', '2026-01-14 00:00:00'),
(9, 26, 300000.00, 'add', '2025-12-06', 'N/A', '2026-01-14 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `sector_monthly_due`
--

CREATE TABLE `sector_monthly_due` (
  `sector_id` int(11) NOT NULL,
  `due_month` varchar(27) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sector_monthly_due`
--

INSERT INTO `sector_monthly_due` (`sector_id`, `due_month`, `due`) VALUES
(25, '2025-09', 100000),
(25, '2025-10', 100000),
(26, '2025-09', 270000),
(26, '2025-10', 150000),
(26, '2025-12', 300000),
(27, '2025-09', 300000),
(27, '2025-10', -20000),
(28, '2025-09', 400000);

-- --------------------------------------------------------

--
-- Table structure for table `sector_profit_due_ledger`
--

CREATE TABLE `sector_profit_due_ledger` (
  `sector_id` int(11) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sector_profit_due_ledger`
--

INSERT INTO `sector_profit_due_ledger` (`sector_id`, `due`) VALUES
(25, 9000),
(26, 6000),
(27, 0),
(28, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sector_profit_monthly_due`
--

CREATE TABLE `sector_profit_monthly_due` (
  `sector_id` int(11) NOT NULL,
  `due_month` varchar(27) NOT NULL,
  `due` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sector_profit_monthly_due`
--

INSERT INTO `sector_profit_monthly_due` (`sector_id`, `due_month`, `due`) VALUES
(25, '2025-09', 5000),
(25, '2025-10', 2000),
(25, '2025-11', 2000),
(25, '2025-12', 0),
(26, '2025-09', 5000),
(26, '2025-10', 2000),
(26, '2025-11', -6000),
(26, '2025-12', 5000),
(27, '2025-09', 0),
(27, '2025-10', 0),
(27, '2025-11', 0),
(27, '2025-12', 0),
(28, '2025-09', 0),
(28, '2025-10', 0),
(28, '2025-11', 0),
(28, '2025-12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_name` text,
  `employee_id` int(11) DEFAULT NULL,
  `role` enum('user','admin','superadmin') NOT NULL,
  `hash_pass` text,
  `branch_id` int(11) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'Active',
  `login_start` time DEFAULT NULL,
  `login_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_name`, `employee_id`, `role`, `hash_pass`, `branch_id`, `status`, `login_start`, `login_end`) VALUES
(149, 'E0001', 1, 'superadmin', '$2y$10$LA2ni224JAWwSYq3sJkw6e3EE2RwOhPB3kZ3PwtVtwRcx7lSyGpjG', 1, 'Active', '09:00:00', '20:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `can_view` int(11) NOT NULL,
  `can_backdate` tinyint(1) DEFAULT '0',
  `can_edit` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `employee_id`, `menu_id`, `can_view`, `can_backdate`, `can_edit`, `can_delete`) VALUES
(273, 149, 11, 1, 0, 0, 0),
(274, 149, 3, 1, 0, 0, 0),
(275, 149, 12, 1, 0, 0, 0),
(276, 149, 5, 1, 0, 0, 0),
(277, 149, 2, 1, 0, 0, 0),
(278, 149, 10, 1, 0, 0, 0),
(279, 149, 4, 1, 0, 0, 0),
(280, 149, 13, 1, 0, 0, 0),
(281, 149, 27, 1, 0, 0, 0),
(286, 149, 19, 1, 0, 0, 0),
(287, 149, 26, 1, 0, 0, 0),
(288, 149, 18, 1, 0, 0, 0),
(289, 149, 20, 1, 0, 0, 0),
(290, 149, 21, 1, 0, 0, 0),
(291, 149, 22, 1, 0, 0, 0),
(292, 149, 24, 1, 0, 0, 0),
(293, 149, 25, 1, 0, 0, 0),
(309, 149, 32, 1, 0, 0, 0),
(310, 149, 29, 1, 0, 0, 0),
(312, 149, 33, 1, 0, 0, 0),
(317, 149, 34, 1, 0, 0, 0),
(318, 149, 35, 1, 0, 0, 0),
(319, 149, 36, 1, 0, 0, 0),
(320, 149, 37, 1, 0, 0, 0),
(321, 149, 38, 1, 0, 0, 0),
(322, 149, 39, 1, 0, 0, 0),
(323, 149, 40, 1, 0, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advance_profit_adjustment`
--
ALTER TABLE `advance_profit_adjustment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adv_profit_adjusting_fund_type_A`
--
ALTER TABLE `adv_profit_adjusting_fund_type_A`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `adv_profit_adjusting_fund_type_B`
--
ALTER TABLE `adv_profit_adjusting_fund_type_B`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `directors`
--
ALTER TABLE `directors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `director_due_ledger`
--
ALTER TABLE `director_due_ledger`
  ADD PRIMARY KEY (`director_id`) USING BTREE;

--
-- Indexes for table `director_monthly_due`
--
ALTER TABLE `director_monthly_due`
  ADD UNIQUE KEY `sector_id` (`director_id`,`due_month`) USING BTREE;

--
-- Indexes for table `director_transactions`
--
ALTER TABLE `director_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investor_id` (`director_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investment_transactions`
--
ALTER TABLE `investment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investor_id` (`investor_id`);

--
-- Indexes for table `investors`
--
ALTER TABLE `investors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investor_advance_profit_adjustment`
--
ALTER TABLE `investor_advance_profit_adjustment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investor_due_ledger`
--
ALTER TABLE `investor_due_ledger`
  ADD PRIMARY KEY (`investor_id`) USING BTREE;

--
-- Indexes for table `investor_monthly_due`
--
ALTER TABLE `investor_monthly_due`
  ADD UNIQUE KEY `sector_id` (`investor_id`,`due_month`) USING BTREE;

--
-- Indexes for table `investor_monthly_profit_details`
--
ALTER TABLE `investor_monthly_profit_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_month_investor` (`month`,`investor_id`);

--
-- Indexes for table `investor_profit_due_ledger`
--
ALTER TABLE `investor_profit_due_ledger`
  ADD PRIMARY KEY (`investor_id`) USING BTREE;

--
-- Indexes for table `investor_profit_monthly_due`
--
ALTER TABLE `investor_profit_monthly_due`
  ADD UNIQUE KEY `sector_id` (`investor_id`,`due_month`) USING BTREE;

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_profit_summary`
--
ALTER TABLE `monthly_profit_summary`
  ADD PRIMARY KEY (`month`);

--
-- Indexes for table `monthly_sector_profit`
--
ALTER TABLE `monthly_sector_profit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `opening_director_due`
--
ALTER TABLE `opening_director_due`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `opening_investor_profit_due`
--
ALTER TABLE `opening_investor_profit_due`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `opening_sector_profit_due`
--
ALTER TABLE `opening_sector_profit_due`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sector_due_ledger`
--
ALTER TABLE `sector_due_ledger`
  ADD PRIMARY KEY (`sector_id`) USING BTREE;

--
-- Indexes for table `sector_investments`
--
ALTER TABLE `sector_investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `sector_monthly_due`
--
ALTER TABLE `sector_monthly_due`
  ADD UNIQUE KEY `sector_id` (`sector_id`,`due_month`) USING BTREE;

--
-- Indexes for table `sector_profit_due_ledger`
--
ALTER TABLE `sector_profit_due_ledger`
  ADD PRIMARY KEY (`sector_id`) USING BTREE;

--
-- Indexes for table `sector_profit_monthly_due`
--
ALTER TABLE `sector_profit_monthly_due`
  ADD UNIQUE KEY `sector_id` (`sector_id`,`due_month`) USING BTREE;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advance_profit_adjustment`
--
ALTER TABLE `advance_profit_adjustment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adv_profit_adjusting_fund_type_A`
--
ALTER TABLE `adv_profit_adjusting_fund_type_A`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adv_profit_adjusting_fund_type_B`
--
ALTER TABLE `adv_profit_adjusting_fund_type_B`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `directors`
--
ALTER TABLE `directors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `director_transactions`
--
ALTER TABLE `director_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `investment_transactions`
--
ALTER TABLE `investment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `investors`
--
ALTER TABLE `investors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `investor_advance_profit_adjustment`
--
ALTER TABLE `investor_advance_profit_adjustment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investor_monthly_profit_details`
--
ALTER TABLE `investor_monthly_profit_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `monthly_sector_profit`
--
ALTER TABLE `monthly_sector_profit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `opening_director_due`
--
ALTER TABLE `opening_director_due`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `opening_investor_profit_due`
--
ALTER TABLE `opening_investor_profit_due`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `opening_sector_profit_due`
--
ALTER TABLE `opening_sector_profit_due`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `sector_investments`
--
ALTER TABLE `sector_investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `investment_transactions`
--
ALTER TABLE `investment_transactions`
  ADD CONSTRAINT `investment_transactions_ibfk_1` FOREIGN KEY (`investor_id`) REFERENCES `investors` (`id`);

--
-- Constraints for table `monthly_sector_profit`
--
ALTER TABLE `monthly_sector_profit`
  ADD CONSTRAINT `monthly_sector_profit_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`);

--
-- Constraints for table `sector_investments`
--
ALTER TABLE `sector_investments`
  ADD CONSTRAINT `sector_investments_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
