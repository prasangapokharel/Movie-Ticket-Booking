-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 05:01 AM
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
-- Database: `movie_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `show_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `show_id`, `total_price`, `booking_status`, `created_at`, `payment_status`, `payment_method`, `payment_id`) VALUES
(57, 3, 434, 620.00, 'Confirmed', '2025-03-22 15:57:46', 'paid', 'khalti', 'ZRHGfrTHmcdWPvbi2rPxsV'),
(58, 3, 434, 820.00, 'Confirmed', '2025-03-22 16:00:36', 'paid', 'khalti', 'EekJsEJhapWF7JFWbqaSqa'),
(59, 3, 434, 1020.00, 'Confirmed', '2025-03-22 16:43:35', 'paid', 'khalti', 'UweHuCiTHV8kYvyr7oWjgd'),
(60, 3, 434, 1020.00, 'Confirmed', '2025-03-22 17:03:13', 'paid', 'khalti', 'cMqvP9ZdGRXzb3otXswMMc'),
(61, 4, 435, 620.00, 'Cancelled', '2025-06-23 16:40:20', 'pending', NULL, NULL),
(62, 4, 435, 420.00, 'Cancelled', '2025-06-23 17:01:20', 'pending', NULL, NULL),
(72, 4, 436, 320.00, 'Pending', '2025-06-25 16:29:51', 'pending', NULL, NULL),
(73, 4, 436, 320.00, 'Pending', '2025-06-25 16:33:47', 'pending', NULL, NULL),
(76, 2, 436, 320.00, 'Pending', '2025-06-25 16:41:50', 'pending', NULL, NULL),
(77, 2, 436, 320.00, 'Pending', '2025-06-25 16:41:50', 'pending', NULL, NULL),
(78, 2, 437, 470.00, 'Pending', '2025-07-06 15:35:35', 'pending', NULL, NULL),
(79, 2, 437, 470.00, 'Pending', '2025-07-06 15:35:36', 'pending', NULL, NULL),
(80, 2, 437, 620.00, '', '2025-07-06 15:51:12', 'failed', NULL, NULL),
(81, 2, 437, 620.00, 'Pending', '2025-07-06 15:52:18', 'pending', NULL, NULL),
(82, 2, 437, 620.00, 'Confirmed', '2025-07-06 15:52:18', 'paid', 'khalti', 'CINE82_1751817582'),
(83, 2, 437, 320.00, 'Pending', '2025-07-06 16:02:11', 'pending', NULL, NULL),
(84, 2, 437, 320.00, 'Pending', '2025-07-06 16:02:11', 'pending', NULL, NULL),
(85, 2, 437, 620.00, 'Pending', '2025-07-06 16:11:05', 'pending', NULL, NULL),
(86, 2, 437, 620.00, 'Confirmed', '2025-07-06 16:11:05', 'paid', 'khalti', 'CINE86_1751818270'),
(87, 2, 437, 770.00, 'Confirmed', '2025-07-06 16:17:28', 'paid', 'khalti', 'CINE87_1751818674'),
(88, 2, 437, 770.00, 'Pending', '2025-07-06 16:17:28', 'pending', NULL, NULL),
(89, 2, 437, 320.00, 'Confirmed', '2025-07-06 16:26:48', 'paid', 'khalti', 'CINE89_1751819212'),
(90, 2, 437, 320.00, 'Pending', '2025-07-06 16:26:48', 'pending', NULL, NULL),
(91, 2, 437, 620.00, 'Confirmed', '2025-07-06 16:28:00', 'paid', 'khalti', 'CINE91_1751819285'),
(92, 2, 437, 620.00, 'Pending', '2025-07-06 16:28:01', 'pending', NULL, NULL),
(93, 2, 437, 620.00, 'Pending', '2025-07-07 01:10:52', 'pending', NULL, NULL),
(94, 2, 437, 620.00, 'Pending', '2025-07-07 01:10:52', 'pending', NULL, NULL),
(95, 2, 437, 620.00, 'Confirmed', '2025-07-07 01:22:27', 'paid', 'khalti', 'CINE95_1751851416'),
(96, 2, 437, 620.00, 'Pending', '2025-07-07 01:22:27', 'pending', NULL, NULL),
(97, 2, 437, 620.00, 'Pending', '2025-07-07 01:58:57', 'pending', NULL, NULL),
(98, 2, 437, 620.00, 'Confirmed', '2025-07-07 01:58:57', 'paid', 'khalti', 'CINE98_1751853542'),
(99, 2, 437, 470.00, 'Pending', '2025-07-07 02:09:01', 'pending', NULL, NULL),
(100, 2, 437, 470.00, 'Confirmed', '2025-07-07 02:09:02', 'paid', 'khalti', 'CINE100_1751854145'),
(101, 2, 437, 320.00, 'Confirmed', '2025-07-07 02:34:11', 'paid', 'khalti', 'CINE101_1751855681'),
(102, 2, 437, 320.00, 'Pending', '2025-07-07 02:34:11', 'pending', NULL, NULL),
(103, 2, 437, 470.00, 'Confirmed', '2025-07-07 02:43:35', 'paid', 'khalti', 'CINE103_1751856223'),
(104, 2, 437, 470.00, 'Pending', '2025-07-07 02:43:35', 'pending', 'khalti', 'CINE104_1751856498'),
(105, 2, 437, 320.00, 'Confirmed', '2025-07-07 02:49:13', 'paid', 'khalti', 'CINE105_1751856567'),
(106, 2, 437, 320.00, 'Pending', '2025-07-07 02:49:13', 'pending', NULL, NULL),
(107, 2, 437, 320.00, 'Pending', '2025-07-07 02:57:12', 'pending', NULL, NULL),
(108, 2, 437, 320.00, 'Confirmed', '2025-07-07 02:57:12', 'paid', 'khalti', 'CINE108_1751857065');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `manager_name` varchar(255) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `branch_code`, `location`, `manager_name`, `contact_phone`, `email`, `username`, `password_hash`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Downtown Cinema', 'DC001', 'Downtown Area', 'John Manager', '9876543210', 'downtown@cinema.com', 'downtown_admin', '$2y$10$e0NRtQHChZz4H3MXqX0F9eSH8g/hLDBv2qvG4i6SBlNRJXpUFEli.', 'active', '2025-06-23 15:57:27', '2025-06-23 15:57:27'),
(2, 'Mall Cinema', 'MC001', 'Shopping Mall', 'Jane Manager', '9876543211', 'mall@cinema.com', 'mall_admin', '$2y$10$e0NRtQHChZz4H3MXqX0F9eSH8g/hLDBv2qvG4i6SBlNRJXpUFEli.', 'active', '2025-06-23 15:57:27', '2025-06-23 15:57:27'),
(3, 'RAMAN', 'RMN76', 'Inaruwa', 'Prasanga Raman Pokharel', '9765470926', 'prasangaramanpokharel@gmail.com', 'raman741', '$2y$10$DQgEKMBeml86Yv28.oYGf.q7MzvQCA8TFFVua1Hf5Bi7T1nXRRXz2', 'active', '2025-06-23 16:07:54', '2025-06-23 16:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `halls`
--

CREATE TABLE `halls` (
  `hall_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `hall_name` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL,
  `seats_per_row` int(11) NOT NULL,
  `total_capacity` int(11) NOT NULL,
  `hall_type` enum('standard','premium','imax') DEFAULT 'standard',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `halls`
--

INSERT INTO `halls` (`hall_id`, `branch_id`, `hall_name`, `total_rows`, `seats_per_row`, `total_capacity`, `hall_type`, `status`, `created_at`) VALUES
(1, 3, 'RAMAN', 10, 10, 100, 'standard', 'active', '2025-06-23 16:09:49');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `movie_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `director` varchar(255) DEFAULT NULL,
  `cast` text DEFAULT NULL,
  `certificate` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`movie_id`, `title`, `genre`, `duration`, `release_date`, `description`, `rating`, `language`, `poster_url`, `trailer_url`, `created_at`, `director`, `cast`, `certificate`, `status`) VALUES
(5, 'Iron Man  4', 'Action', 180, '2025-02-22', 'After surviving an unexpected attack in enemy territory, jet-setting industrialist Tony Stark builds a high-tech suit of armor and vows to protect the world as Iron Man. Straight from the pages of the legendary comic book, Iron Man is a hero who is built - not born - to be unlike any other.', 4.0, 'Hindi', 'https://images.moviesanywhere.com/45589cb573be13bb984b078ed3e1cf9e/a0652686-b625-4b41-bdf1-f32c3d9471a6.webp?h=375&resize=fit&w=250', 'https://www.youtube.com/watch?app=desktop&v=32YiPrKh7AQ&t=36s', '2025-03-08 10:43:04', 'Jon Favreau', 'Robert Downey Jr., Terrence Howard, Jeff Bridges, Gwyneth Paltrow, Leslie Bibb, Shaun Toub, Faran Tahir, Clark Gregg, Bill Smitrovich, Sayed Badreya, Paul Bettany, Jon Favreau, Peter Billingsley, Tim Guinee, Will Lyman, Tom Morello, Marco Khan, Daston Kalili, Ido Ezra, Kevin Foster, Garrett Noel, Eileen Weisinger, Ahmed Ahmed, Fahim Fazli, Gerard Sanders, Tim Rigby, Russell Richardson, Nazanin Boniadi, Thomas Craig Plumer, Robert Berkman, Stacy Stas, Lauren Scyphers, Dr. Frank Nyi, Marvin Jordan, Jim Cramer, Donna Evans Merlo, Reid Harper, Summer Kylie Remington, Ava Rose Williams, Vladimir Kubr, Callie Marie Croughwell, Javan Tahir, Sahar Bibiyan, Patrick O\'Connell, Adam Harrington, Meera Simhan, Ben Newmark, Ricki Noel Lander, Jeannine Kaspar, Sarah Cahill, Stan Lee, Justin Rex, Zorianna Kit, Lana Kinnear, Nicole Lindeblad, Masha Lund, Gabrielle Tuite, Tim Griffin, Joshua Harto, Micah Hauptman, James Bethea', 'U', 'active'),
(6, 'Iron Man  4', 'Action', 180, '2025-02-22', 'After surviving an unexpected attack in enemy territory, jet-setting industrialist Tony Stark builds a high-tech suit of armor and vows to protect the world as Iron Man. Straight from the pages of the legendary comic book, Iron Man is a hero who is built - not born - to be unlike any other.', 4.0, 'Hindi', 'https://images.moviesanywhere.com/45589cb573be13bb984b078ed3e1cf9e/a0652686-b625-4b41-bdf1-f32c3d9471a6.webp?h=375&resize=fit&w=250', 'https://www.youtube.com/watch?app=desktop&v=32YiPrKh7AQ&t=36s', '2025-03-08 11:16:01', 'Jon Favreau', 'Robert Downey Jr., Terrence Howard, Jeff Bridges, Gwyneth Paltrow, Leslie Bibb, Shaun Toub, Faran Tahir, Clark Gregg, Bill Smitrovich, Sayed Badreya, Paul Bettany, Jon Favreau, Peter Billingsley, Tim Guinee, Will Lyman, Tom Morello, Marco Khan, Daston Kalili, Ido Ezra, Kevin Foster, Garrett Noel, Eileen Weisinger, Ahmed Ahmed, Fahim Fazli, Gerard Sanders, Tim Rigby, Russell Richardson, Nazanin Boniadi, Thomas Craig Plumer, Robert Berkman, Stacy Stas, Lauren Scyphers, Dr. Frank Nyi, Marvin Jordan, Jim Cramer, Donna Evans Merlo, Reid Harper, Summer Kylie Remington, Ava Rose Williams, Vladimir Kubr, Callie Marie Croughwell, Javan Tahir, Sahar Bibiyan, Patrick O\'Connell, Adam Harrington, Meera Simhan, Ben Newmark, Ricki Noel Lander, Jeannine Kaspar, Sarah Cahill, Stan Lee, Justin Rex, Zorianna Kit, Lana Kinnear, Nicole Lindeblad, Masha Lund, Gabrielle Tuite, Tim Griffin, Joshua Harto, Micah Hauptman, James Bethea', 'U', 'active'),
(7, 'K.G.F: Chapter 1', 'Action', 140, '2025-03-07', 'KGF Chapter 1 is a film based on the gold mines that represents absolute power. The film is based on power struggle to rule these fields which eventually becomes one man’s destiny and his final destination.', 4.8, 'Hindi', 'https://w0.peakpx.com/wallpaper/707/32/HD-wallpaper-kgf-legend-superstar-yash.jpg', 'https://www.youtube.com/watch?v=-KfsY-qwBS0', '2025-03-08 15:12:15', 'Prashanth Neel', 'Vijendra Ingalgi, Son of Anand Ingalgi continues the story of KGF and Rocky in Chapter 2. Rocky survives the attack by Vanaram’s guards after killing Garuda. He is a hero and a saviour to the people of Narachi. While trying to fulfil his promise to his mother, he must face many obstacles in the form of Adheera, Inayat Khalil and Ramika sen.', 'U', 'active'),
(8, 'Hostel 3', 'Romance, Drama', 160, '2025-03-09', 'Hostel 3 is the third installment from the movie franchise Hostel starring Paras Bam Thakuri, Ryhaan Giri, Padam Tamang & others, directed by Sashan Kandel.', 4.0, 'Nepali', 'https://m.media-amazon.com/images/M/MV5BNDkxYmRlYWYtOGQyYy00Mzk1LTg2OTctYzkxMDIyYjRhYzVkXkEyXkFqcGc@._V1_QL75_UY562_CR21,0,380,562_.jpg', 'https://youtu.be/9WTiSuyNq1Y', '2025-03-09 04:50:56', 'Sashan Kandel', 'Paras Bam Thakuri, Ryhaan Giri, Padam Tamang, Prabin Khatiwoda', 'U', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Esewa','Khalti','Bank Transfer') NOT NULL,
  `status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `user_id`, `booking_id`, `show_id`, `amount`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES
(7, 3, 57, 434, 620.00, 'Khalti', 'Paid', '2025-03-22 15:58:25', '2025-03-22 15:58:25'),
(8, 3, 58, 434, 820.00, 'Khalti', 'Paid', '2025-03-22 16:01:21', '2025-03-22 16:01:21'),
(9, 3, 59, 434, 1020.00, 'Khalti', 'Paid', '2025-03-22 16:44:14', '2025-03-22 16:44:14'),
(10, 3, 59, 434, 1020.00, 'Khalti', 'Paid', '2025-03-22 17:00:47', '2025-03-22 17:00:47'),
(11, 3, 60, 434, 1020.00, 'Khalti', 'Paid', '2025-03-22 17:03:49', '2025-03-22 17:03:49'),
(12, 3, 60, 434, 1020.00, 'Khalti', 'Paid', '2025-03-22 17:03:50', '2025-03-22 17:03:50'),
(13, 2, 82, 437, 620.00, 'Khalti', 'Paid', '2025-07-06 16:00:09', '2025-07-06 16:00:09'),
(14, 2, 86, 437, 620.00, 'Khalti', 'Paid', '2025-07-06 16:11:32', '2025-07-06 16:11:32'),
(15, 2, 87, 437, 770.00, 'Khalti', 'Paid', '2025-07-06 16:18:16', '2025-07-06 16:18:16'),
(16, 2, 89, 437, 320.00, 'Khalti', 'Paid', '2025-07-06 16:27:09', '2025-07-06 16:27:09'),
(17, 2, 91, 437, 620.00, 'Khalti', 'Paid', '2025-07-06 16:28:26', '2025-07-06 16:28:26'),
(18, 2, 95, 437, 620.00, 'Khalti', 'Paid', '2025-07-07 01:24:28', '2025-07-07 01:24:28'),
(19, 2, 98, 437, 620.00, 'Khalti', 'Paid', '2025-07-07 01:59:29', '2025-07-07 01:59:29'),
(20, 2, 100, 437, 470.00, 'Khalti', 'Paid', '2025-07-07 02:09:28', '2025-07-07 02:09:28'),
(21, 2, 101, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:35:16', '2025-07-07 02:35:16'),
(22, 2, 101, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:35:23', '2025-07-07 02:35:23'),
(23, 2, 103, 437, 470.00, 'Khalti', 'Paid', '2025-07-07 02:44:19', '2025-07-07 02:44:19'),
(24, 2, 103, 437, 470.00, 'Khalti', 'Paid', '2025-07-07 02:44:23', '2025-07-07 02:44:23'),
(25, 2, 105, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:50:06', '2025-07-07 02:50:06'),
(26, 2, 105, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:50:11', '2025-07-07 02:50:11'),
(27, 2, 108, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:58:12', '2025-07-07 02:58:12'),
(28, 2, 108, 437, 320.00, 'Khalti', 'Paid', '2025-07-07 02:58:16', '2025-07-07 02:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `log_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `response_data` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_logs`
--

INSERT INTO `payment_logs` (`log_id`, `booking_id`, `user_id`, `amount`, `payment_method`, `payment_id`, `response_data`, `created_at`) VALUES
(24, 57, 3, 620.00, 'khalti', 'CINE57_1742659072', '{\"pidx\":\"ZRHGfrTHmcdWPvbi2rPxsV\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=ZRHGfrTHmcdWPvbi2rPxsV\",\"expires_at\":\"2025-03-22T22:12:51.699768+05:45\",\"expires_in\":1800}', '2025-03-22 21:42:53'),
(25, 57, 3, 620.00, 'khalti', 'ZRHGfrTHmcdWPvbi2rPxsV', '{\"pidx\":\"ZRHGfrTHmcdWPvbi2rPxsV\",\"status\":\"Completed\"}', '2025-03-22 21:43:25'),
(26, 58, 3, 820.00, 'khalti', 'CINE58_1742659243', '{\"pidx\":\"EekJsEJhapWF7JFWbqaSqa\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=EekJsEJhapWF7JFWbqaSqa\",\"expires_at\":\"2025-03-22T22:15:42.151051+05:45\",\"expires_in\":1800}', '2025-03-22 21:45:43'),
(27, 58, 3, 820.00, 'khalti', 'EekJsEJhapWF7JFWbqaSqa', '{\"pidx\":\"EekJsEJhapWF7JFWbqaSqa\",\"status\":\"Completed\"}', '2025-03-22 21:46:21'),
(28, 59, 3, 1020.00, 'khalti', 'CINE59_1742661825', '{\"pidx\":\"UweHuCiTHV8kYvyr7oWjgd\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=UweHuCiTHV8kYvyr7oWjgd\",\"expires_at\":\"2025-03-22T22:58:44.090172+05:45\",\"expires_in\":1800}', '2025-03-22 22:28:45'),
(29, 59, 3, 1020.00, 'khalti', 'UweHuCiTHV8kYvyr7oWjgd', '{\"pidx\":\"UweHuCiTHV8kYvyr7oWjgd\",\"status\":\"Completed\"}', '2025-03-22 22:29:14'),
(30, 59, 3, 1020.00, 'khalti', 'UweHuCiTHV8kYvyr7oWjgd', '{\"pidx\":\"UweHuCiTHV8kYvyr7oWjgd\",\"total_amount\":102000,\"status\":\"Completed\",\"transaction_id\":\"chWo8ZKjJok8Q8KV8BaVwc\",\"fee\":0,\"refunded\":false}', '2025-03-22 22:45:47'),
(31, 60, 3, 1020.00, 'khalti', 'CINE60_1742663004', '{\"pidx\":\"cMqvP9ZdGRXzb3otXswMMc\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=cMqvP9ZdGRXzb3otXswMMc\",\"expires_at\":\"2025-03-22T23:18:22.737214+05:45\",\"expires_in\":1800}', '2025-03-22 22:48:24'),
(32, 60, 3, 1020.00, 'khalti', 'cMqvP9ZdGRXzb3otXswMMc', '{\"pidx\":\"cMqvP9ZdGRXzb3otXswMMc\",\"status\":\"Completed\"}', '2025-03-22 22:48:49'),
(33, 60, 3, 1020.00, 'khalti', 'cMqvP9ZdGRXzb3otXswMMc', '{\"pidx\":\"cMqvP9ZdGRXzb3otXswMMc\",\"total_amount\":102000,\"status\":\"Completed\",\"transaction_id\":\"dKvrrBbxuMMdr2jVx4XweG\",\"fee\":0,\"refunded\":false}', '2025-03-22 22:48:50'),
(34, 82, 2, 620.00, 'khalti', 'CINE82_1751817582', '{\"pidx\":\"PSWjDzHYsWmZakPPQVuEjY\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=PSWjDzHYsWmZakPPQVuEjY\",\"expires_at\":\"2025-07-06T22:14:42.762290+05:45\",\"expires_in\":1800}', '2025-07-06 21:44:42'),
(35, 82, 2, 620.00, 'khalti', 'PSWjDzHYsWmZakPPQVuEjY', '{\"pidx\":\"PSWjDzHYsWmZakPPQVuEjY\",\"total_amount\":62000,\"status\":\"Completed\",\"transaction_id\":\"coicrCxJvvLxganLX2VXfj\",\"fee\":0,\"refunded\":false}', '2025-07-06 21:45:09'),
(36, 86, 2, 620.00, 'khalti', 'CINE86_1751818270', '{\"pidx\":\"8qzWrZWdK3wW5jfNmgZspm\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=8qzWrZWdK3wW5jfNmgZspm\",\"expires_at\":\"2025-07-06T22:26:11.643401+05:45\",\"expires_in\":1800}', '2025-07-06 21:56:11'),
(37, 86, 2, 620.00, 'khalti', '8qzWrZWdK3wW5jfNmgZspm', '{\"pidx\":\"8qzWrZWdK3wW5jfNmgZspm\",\"total_amount\":62000,\"status\":\"Completed\",\"transaction_id\":\"jCa7GvMA26vogswSReMjAm\",\"fee\":0,\"refunded\":false}', '2025-07-06 21:56:32'),
(38, 87, 2, 770.00, 'khalti', 'CINE87_1751818674', '{\"pidx\":\"yHeVxV3LCnBksb8u9dcThc\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=yHeVxV3LCnBksb8u9dcThc\",\"expires_at\":\"2025-07-06T22:32:55.118867+05:45\",\"expires_in\":1800}', '2025-07-06 22:02:55'),
(39, 87, 2, 770.00, 'khalti', 'yHeVxV3LCnBksb8u9dcThc', '{\"pidx\":\"yHeVxV3LCnBksb8u9dcThc\",\"total_amount\":77000,\"status\":\"Completed\",\"transaction_id\":\"CBzvre8m53PzntFho77B8k\",\"fee\":0,\"refunded\":false}', '2025-07-06 22:03:16'),
(40, 89, 2, 320.00, 'khalti', 'CINE89_1751819212', '{\"pidx\":\"EasHJjY3bZCgwsiR6GK7MK\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=EasHJjY3bZCgwsiR6GK7MK\",\"expires_at\":\"2025-07-06T22:41:53.044155+05:45\",\"expires_in\":1800}', '2025-07-06 22:11:53'),
(41, 89, 2, 320.00, 'khalti', 'EasHJjY3bZCgwsiR6GK7MK', '{\"pidx\":\"EasHJjY3bZCgwsiR6GK7MK\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"oAbCpJ8hQm7RjTSjtNFrhD\",\"fee\":0,\"refunded\":false}', '2025-07-06 22:12:09'),
(42, 91, 2, 620.00, 'khalti', 'CINE91_1751819285', '{\"pidx\":\"catA3iGXb5PcdzZg5M57gk\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=catA3iGXb5PcdzZg5M57gk\",\"expires_at\":\"2025-07-06T22:43:05.784041+05:45\",\"expires_in\":1800}', '2025-07-06 22:13:06'),
(43, 91, 2, 620.00, 'khalti', 'catA3iGXb5PcdzZg5M57gk', '{\"pidx\":\"catA3iGXb5PcdzZg5M57gk\",\"total_amount\":62000,\"status\":\"Completed\",\"transaction_id\":\"xoqMFrgmm8KE9dWANiFz8E\",\"fee\":0,\"refunded\":false}', '2025-07-06 22:13:26'),
(44, 95, 2, 620.00, 'khalti', 'CINE95_1751851416', '{\"pidx\":\"cryFiaLSfhibaHTGhz6FVM\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=cryFiaLSfhibaHTGhz6FVM\",\"expires_at\":\"2025-07-07T07:38:37.975403+05:45\",\"expires_in\":1800}', '2025-07-07 07:08:41'),
(45, 95, 2, 620.00, 'khalti', 'cryFiaLSfhibaHTGhz6FVM', '{\"pidx\":\"cryFiaLSfhibaHTGhz6FVM\",\"total_amount\":62000,\"status\":\"Completed\",\"transaction_id\":\"DkrtCUtNE4Bjk4RXbaGhGW\",\"fee\":0,\"refunded\":false}', '2025-07-07 07:09:28'),
(46, 98, 2, 620.00, 'khalti', 'CINE98_1751853542', '{\"pidx\":\"KT9NXkQsbSDkyJ25pXmotT\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=KT9NXkQsbSDkyJ25pXmotT\",\"expires_at\":\"2025-07-07T08:14:03.092591+05:45\",\"expires_in\":1800}', '2025-07-07 07:44:03'),
(47, 98, 2, 620.00, 'khalti', 'KT9NXkQsbSDkyJ25pXmotT', '{\"pidx\":\"KT9NXkQsbSDkyJ25pXmotT\",\"total_amount\":62000,\"status\":\"Completed\",\"transaction_id\":\"9d2cdf3LMHar4TuXGgidqC\",\"fee\":0,\"refunded\":false}', '2025-07-07 07:44:29'),
(48, 100, 2, 470.00, 'khalti', 'CINE100_1751854145', '{\"pidx\":\"og3htM2UbGPhZUtHAzCoGG\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=og3htM2UbGPhZUtHAzCoGG\",\"expires_at\":\"2025-07-07T08:24:07.039720+05:45\",\"expires_in\":1800}', '2025-07-07 07:54:07'),
(49, 100, 2, 470.00, 'khalti', 'og3htM2UbGPhZUtHAzCoGG', '{\"pidx\":\"og3htM2UbGPhZUtHAzCoGG\",\"total_amount\":47000,\"status\":\"Completed\",\"transaction_id\":\"o2MFPZ4RWczJUeAL7kAcGF\",\"fee\":0,\"refunded\":false}', '2025-07-07 07:54:28'),
(50, 101, 2, 320.00, 'khalti', 'CINE101_1751855681', '{\"pidx\":\"iHknT3hWJJGM3xh7EtpNDb\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=iHknT3hWJJGM3xh7EtpNDb\",\"expires_at\":\"2025-07-07T08:49:45.650180+05:45\",\"expires_in\":1800}', '2025-07-07 08:19:45'),
(51, 101, 2, 320.00, 'khalti', 'iHknT3hWJJGM3xh7EtpNDb', '{\"pidx\":\"iHknT3hWJJGM3xh7EtpNDb\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"K66Sru2pHNqGQGHu4QnQJR\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:20:16'),
(52, 101, 2, 320.00, 'khalti', 'iHknT3hWJJGM3xh7EtpNDb', '{\"pidx\":\"iHknT3hWJJGM3xh7EtpNDb\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"K66Sru2pHNqGQGHu4QnQJR\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:20:23'),
(53, 103, 2, 470.00, 'khalti', 'CINE103_1751856223', '{\"pidx\":\"ShmfY35aA7QpF5i9dVqaPZ\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=ShmfY35aA7QpF5i9dVqaPZ\",\"expires_at\":\"2025-07-07T08:58:47.630454+05:45\",\"expires_in\":1800}', '2025-07-07 08:28:47'),
(54, 103, 2, 470.00, 'khalti', 'ShmfY35aA7QpF5i9dVqaPZ', '{\"pidx\":\"ShmfY35aA7QpF5i9dVqaPZ\",\"total_amount\":47000,\"status\":\"Completed\",\"transaction_id\":\"cSZoLFfZDFKpFA2up3pr7h\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:29:19'),
(55, 103, 2, 470.00, 'khalti', 'ShmfY35aA7QpF5i9dVqaPZ', '{\"pidx\":\"ShmfY35aA7QpF5i9dVqaPZ\",\"total_amount\":47000,\"status\":\"Completed\",\"transaction_id\":\"cSZoLFfZDFKpFA2up3pr7h\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:29:23'),
(56, 104, 2, 470.00, 'khalti', 'CINE104_1751856498', '{\"pidx\":\"hVGSgAB2mZhtB6pLjXccGd\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=hVGSgAB2mZhtB6pLjXccGd\",\"expires_at\":\"2025-07-07T09:03:21.564916+05:45\",\"expires_in\":1800}', '2025-07-07 08:33:21'),
(57, 105, 2, 320.00, 'khalti', 'CINE105_1751856567', '{\"pidx\":\"NRHd95HatwwfgUvTeJQRUZ\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=NRHd95HatwwfgUvTeJQRUZ\",\"expires_at\":\"2025-07-07T09:04:31.693052+05:45\",\"expires_in\":1800}', '2025-07-07 08:34:31'),
(58, 105, 2, 320.00, 'khalti', 'NRHd95HatwwfgUvTeJQRUZ', '{\"pidx\":\"NRHd95HatwwfgUvTeJQRUZ\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"tt744BoPnzgzBHTgata6ad\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:35:06'),
(59, 105, 2, 320.00, 'khalti', 'NRHd95HatwwfgUvTeJQRUZ', '{\"pidx\":\"NRHd95HatwwfgUvTeJQRUZ\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"tt744BoPnzgzBHTgata6ad\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:35:11'),
(60, 108, 2, 320.00, 'khalti', 'CINE108_1751857041', '{\"pidx\":\"THN2sxjqmJLxGCkqNY3iAX\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=THN2sxjqmJLxGCkqNY3iAX\",\"expires_at\":\"2025-07-07T09:12:24.651186+05:45\",\"expires_in\":1800}', '2025-07-07 08:42:24'),
(61, 108, 2, 320.00, 'khalti', 'CINE108_1751857065', '{\"pidx\":\"sjuAfZHpXcY2Diy2kKMtW4\",\"payment_url\":\"https://test-pay.khalti.com/?pidx=sjuAfZHpXcY2Diy2kKMtW4\",\"expires_at\":\"2025-07-07T09:12:48.162788+05:45\",\"expires_in\":1800}', '2025-07-07 08:42:48'),
(62, 108, 2, 320.00, 'khalti', 'sjuAfZHpXcY2Diy2kKMtW4', '{\"pidx\":\"sjuAfZHpXcY2Diy2kKMtW4\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"GzmBgrpDw5KS7pYWES3AVR\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:43:12'),
(63, 108, 2, 320.00, 'khalti', 'sjuAfZHpXcY2Diy2kKMtW4', '{\"pidx\":\"sjuAfZHpXcY2Diy2kKMtW4\",\"total_amount\":32000,\"status\":\"Completed\",\"transaction_id\":\"GzmBgrpDw5KS7pYWES3AVR\",\"fee\":0,\"refunded\":false}', '2025-07-07 08:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `seat_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `status` enum('available','booked','reserved','holding') DEFAULT 'available',
  `booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`seat_id`, `show_id`, `seat_number`, `status`, `booking_id`, `created_at`, `updated_at`) VALUES
(344, 423, 'B7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(345, 423, 'C7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(348, 423, 'B3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(349, 423, 'B4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(350, 426, 'A1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(351, 426, 'B2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(352, 426, 'B3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(353, 426, 'A4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(354, 426, 'A5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(355, 426, 'A6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(356, 426, 'A7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(357, 426, 'A9', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(358, 426, 'A10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(359, 426, 'B1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(360, 426, 'B4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(361, 426, 'B6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(362, 426, 'B8', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(363, 426, 'B10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(364, 426, 'C1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(365, 426, 'C2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(366, 426, 'C3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(367, 426, 'C4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(368, 426, 'C5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(369, 426, 'C6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(370, 426, 'C7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(371, 426, 'C8', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(372, 426, 'C9', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(373, 426, 'C10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(374, 426, 'D1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(375, 426, 'D2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(376, 426, 'D3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(377, 426, 'D4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(378, 426, 'D5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(379, 426, 'D6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(380, 426, 'D7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(381, 426, 'D8', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(382, 426, 'D9', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(383, 426, 'D10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(384, 426, 'E1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(385, 426, 'E2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(386, 426, 'E3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(387, 426, 'E4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(388, 426, 'E5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(389, 426, 'E6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(390, 426, 'E7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(391, 426, 'E8', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(392, 426, 'E9', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(393, 426, 'E10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(394, 426, 'F1', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(395, 426, 'F2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(396, 426, 'F3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(397, 426, 'F4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(398, 426, 'F5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(399, 426, 'F6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(400, 426, 'F7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(401, 426, 'F8', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(402, 426, 'F9', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(403, 426, 'F10', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(404, 426, 'G5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(405, 426, 'G6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(406, 426, 'G7', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(407, 426, 'A2', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(408, 426, 'A3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(409, 426, 'B5', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(410, 427, 'E4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(411, 427, 'E5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(412, 427, 'E6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(413, 427, 'E7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(414, 427, 'E8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(415, 427, 'E9', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(416, 427, 'E10', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(417, 427, 'F5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(418, 427, 'F6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(419, 427, 'F7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(420, 427, 'F8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(421, 423, 'B5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(422, 423, 'B6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(423, 423, 'C5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(424, 423, 'C6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(425, 423, 'D5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(426, 427, 'G4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(427, 427, 'G5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(428, 427, 'G6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(429, 427, 'G7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(430, 427, 'G8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(431, 427, 'C2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(432, 427, 'C3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(433, 427, 'C5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(434, 427, 'C6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(435, 427, 'B2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(436, 427, 'B3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(437, 427, 'B4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(438, 427, 'B5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(439, 427, 'F3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(440, 427, 'F4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(441, 427, 'C7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(442, 427, 'C8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(443, 423, 'C3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(444, 423, 'C4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(445, 423, 'D3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(446, 423, 'D4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(447, 423, 'A1', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(448, 423, 'A2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(449, 423, 'B9', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(450, 423, 'C1', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(451, 423, 'C8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(452, 423, 'C9', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(453, 427, 'H4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(454, 427, 'H5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(455, 429, 'D5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(456, 429, 'D6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(457, 430, 'F1', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(458, 430, 'F2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(459, 423, 'D6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(460, 423, 'D7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(461, 423, 'A4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(462, 423, 'A5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(463, 429, 'C5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(464, 429, 'C6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(465, 429, 'B5', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(466, 429, 'B6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(467, 429, 'C2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(468, 429, 'C3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(469, 429, 'C4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(470, 431, 'A3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(471, 431, 'A4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(472, 431, 'F3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(473, 431, 'F4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(474, 433, 'A2', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(475, 433, 'A3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(476, 433, 'A4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(477, 433, 'B6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(478, 433, 'B7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(479, 433, 'H3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(480, 433, 'G3', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(481, 433, 'G4', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(482, 433, 'G6', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(483, 433, 'G7', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(484, 433, 'G8', 'booked', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(488, 434, 'B6', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(491, 434, 'E3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(492, 434, 'E4', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(493, 434, 'C3', 'available', NULL, '2025-03-22 15:32:30', '2025-06-25 16:28:38'),
(496, 434, 'F2', 'available', NULL, '2025-03-22 15:34:18', '2025-06-25 16:28:38'),
(497, 434, 'F3', 'available', NULL, '2025-03-22 15:34:18', '2025-06-25 16:28:38'),
(506, 434, 'B3', 'booked', 57, '2025-03-22 15:57:46', '2025-06-25 16:28:38'),
(507, 434, 'B4', 'booked', 57, '2025-03-22 15:57:46', '2025-06-25 16:28:38'),
(508, 434, 'B5', 'booked', 57, '2025-03-22 15:57:46', '2025-06-25 16:28:38'),
(509, 434, 'G2', 'booked', 58, '2025-03-22 16:00:36', '2025-06-25 16:28:38'),
(510, 434, 'G3', 'booked', 58, '2025-03-22 16:00:36', '2025-06-25 16:28:38'),
(511, 434, 'H2', 'booked', 58, '2025-03-22 16:00:36', '2025-06-25 16:28:38'),
(512, 434, 'H4', 'booked', 58, '2025-03-22 16:00:36', '2025-06-25 16:28:38'),
(513, 434, 'C6', 'booked', 59, '2025-03-22 16:43:35', '2025-06-25 16:28:38'),
(514, 434, 'C7', 'booked', 59, '2025-03-22 16:43:35', '2025-06-25 16:28:38'),
(515, 434, 'C8', 'booked', 59, '2025-03-22 16:43:35', '2025-06-25 16:28:38'),
(516, 434, 'C9', 'booked', 59, '2025-03-22 16:43:35', '2025-06-25 16:28:38'),
(517, 434, 'C10', 'booked', 59, '2025-03-22 16:43:35', '2025-06-25 16:28:38'),
(518, 434, 'C4', 'booked', 60, '2025-03-22 17:03:13', '2025-06-25 16:28:38'),
(519, 434, 'C5', 'booked', 60, '2025-03-22 17:03:13', '2025-06-25 16:28:38'),
(520, 434, 'D3', 'booked', 60, '2025-03-22 17:03:13', '2025-06-25 16:28:38'),
(521, 434, 'D4', 'booked', 60, '2025-03-22 17:03:13', '2025-06-25 16:28:38'),
(522, 434, 'D5', 'booked', 60, '2025-03-22 17:03:13', '2025-06-25 16:28:38'),
(526, 435, 'A4', 'available', NULL, '2025-06-23 16:37:45', '2025-06-25 16:28:38'),
(527, 435, 'A5', 'available', NULL, '2025-06-23 16:37:45', '2025-06-25 16:28:38'),
(528, 435, 'A6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(529, 435, 'A7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(530, 435, 'A8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(531, 435, 'A9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(532, 435, 'A10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(533, 435, 'B1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(534, 435, 'B2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(535, 435, 'B3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(536, 435, 'B4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(537, 435, 'B5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(538, 435, 'B6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(539, 435, 'B7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(540, 435, 'B8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(541, 435, 'B9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(542, 435, 'B10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(543, 435, 'C1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(544, 435, 'C2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(547, 435, 'C5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(548, 435, 'C6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(549, 435, 'C7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(550, 435, 'C8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(551, 435, 'C9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(552, 435, 'C10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(553, 435, 'D1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(554, 435, 'D2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(555, 435, 'D3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(556, 435, 'D4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(557, 435, 'D5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(558, 435, 'D6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(559, 435, 'D7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(560, 435, 'D8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(561, 435, 'D9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(562, 435, 'D10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(563, 435, 'E1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(564, 435, 'E2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(565, 435, 'E3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(566, 435, 'E4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(567, 435, 'E5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(568, 435, 'E6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(569, 435, 'E7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(570, 435, 'E8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(571, 435, 'E9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(572, 435, 'E10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(573, 435, 'F1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(574, 435, 'F2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(575, 435, 'F3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(576, 435, 'F4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(577, 435, 'F5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(578, 435, 'F6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(579, 435, 'F7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(580, 435, 'F8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(581, 435, 'F9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(582, 435, 'F10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(583, 435, 'G1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(584, 435, 'G2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(585, 435, 'G3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(586, 435, 'G4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(587, 435, 'G5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(588, 435, 'G6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(589, 435, 'G7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(590, 435, 'G8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(591, 435, 'G9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(592, 435, 'G10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(593, 435, 'H1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(594, 435, 'H2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(595, 435, 'H3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(596, 435, 'H4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(597, 435, 'H5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(598, 435, 'H6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(599, 435, 'H7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(600, 435, 'H8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(601, 435, 'H9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(602, 435, 'H10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(603, 435, 'I1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(604, 435, 'I2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(605, 435, 'I3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(606, 435, 'I4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(607, 435, 'I5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(608, 435, 'I6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(609, 435, 'I7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(610, 435, 'I8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(611, 435, 'I9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(612, 435, 'I10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(613, 435, 'J1', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(614, 435, 'J2', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(615, 435, 'J3', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(616, 435, 'J4', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(617, 435, 'J5', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(618, 435, 'J6', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(619, 435, 'J7', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(620, 435, 'J8', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(621, 435, 'J9', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(622, 435, 'J10', 'available', NULL, '2025-06-23 16:37:46', '2025-06-25 16:28:38'),
(623, 435, 'A1', 'available', NULL, '2025-06-23 16:40:20', '2025-06-25 16:28:38'),
(624, 435, 'A2', 'available', NULL, '2025-06-23 16:40:20', '2025-06-25 16:28:38'),
(625, 435, 'A3', 'available', NULL, '2025-06-23 16:40:20', '2025-06-25 16:28:38'),
(626, 435, 'C3', 'available', 62, '2025-06-23 17:01:20', '2025-06-25 16:28:38'),
(627, 435, 'C4', 'available', 62, '2025-06-23 17:01:20', '2025-06-25 16:28:38'),
(628, 436, 'A1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(629, 436, 'A2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(632, 436, 'A5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(633, 436, 'A6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(634, 436, 'A7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(635, 436, 'A8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(636, 436, 'A9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(637, 436, 'A10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(638, 436, 'B1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(639, 436, 'B2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(640, 436, 'B3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(641, 436, 'B4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(642, 436, 'B5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(643, 436, 'B6', 'reserved', 73, '2025-06-25 15:56:01', '2025-06-25 16:33:47'),
(644, 436, 'B7', 'reserved', 73, '2025-06-25 15:56:01', '2025-06-25 16:33:47'),
(645, 436, 'B8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(646, 436, 'B9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(647, 436, 'B10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(648, 436, 'C1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(649, 436, 'C2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(650, 436, 'C3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(651, 436, 'C4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(652, 436, 'C5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(653, 436, 'C6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(654, 436, 'C7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(655, 436, 'C8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(656, 436, 'C9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(657, 436, 'C10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(658, 436, 'D1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(659, 436, 'D2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(660, 436, 'D3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(661, 436, 'D4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(662, 436, 'D5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(663, 436, 'D6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(664, 436, 'D7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(665, 436, 'D8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(666, 436, 'D9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(667, 436, 'D10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(668, 436, 'E1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(669, 436, 'E2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(670, 436, 'E3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(671, 436, 'E4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(672, 436, 'E5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(673, 436, 'E6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(674, 436, 'E7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(675, 436, 'E8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(676, 436, 'E9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(677, 436, 'E10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(678, 436, 'F1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(679, 436, 'F2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(680, 436, 'F3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(681, 436, 'F4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(682, 436, 'F5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(683, 436, 'F6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(684, 436, 'F7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(685, 436, 'F8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(686, 436, 'F9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(687, 436, 'F10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(688, 436, 'G1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(689, 436, 'G2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(690, 436, 'G3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(691, 436, 'G4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(692, 436, 'G5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(693, 436, 'G6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(694, 436, 'G7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(695, 436, 'G8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(696, 436, 'G9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(697, 436, 'G10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(698, 436, 'H1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(699, 436, 'H2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(700, 436, 'H3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(701, 436, 'H4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(702, 436, 'H5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(703, 436, 'H6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(704, 436, 'H7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(705, 436, 'H8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(706, 436, 'H9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(707, 436, 'H10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(708, 436, 'I1', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(709, 436, 'I2', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(710, 436, 'I3', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(711, 436, 'I4', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(712, 436, 'I5', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(713, 436, 'I6', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(714, 436, 'I7', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(715, 436, 'I8', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(716, 436, 'I9', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(717, 436, 'I10', 'available', NULL, '2025-06-25 15:56:01', '2025-06-25 16:28:38'),
(718, 436, 'J1', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(719, 436, 'J2', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(720, 436, 'J3', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(721, 436, 'J4', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(722, 436, 'J5', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(723, 436, 'J6', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(724, 436, 'J7', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(725, 436, 'J8', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(726, 436, 'J9', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(727, 436, 'J10', 'available', NULL, '2025-06-25 15:56:02', '2025-06-25 16:28:38'),
(728, 436, 'A3', 'reserved', 72, '2025-06-25 16:29:51', '2025-06-25 16:29:51'),
(729, 436, 'A4', 'reserved', 72, '2025-06-25 16:29:51', '2025-06-25 16:29:51'),
(730, 437, 'A1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(731, 437, 'A2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(732, 437, 'A3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(733, 437, 'A4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(734, 437, 'A5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(735, 437, 'A6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(736, 437, 'A7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(737, 437, 'A8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(738, 437, 'A9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(739, 437, 'A10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(740, 437, 'B1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(741, 437, 'B2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(742, 437, 'B3', 'booked', 108, '2025-07-06 15:35:03', '2025-07-07 02:58:17'),
(743, 437, 'B4', 'booked', 108, '2025-07-06 15:35:03', '2025-07-07 02:58:17'),
(744, 437, 'B5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(745, 437, 'B6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(746, 437, 'B7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(747, 437, 'B8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(748, 437, 'B9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(749, 437, 'B10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(750, 437, 'C1', 'booked', 103, '2025-07-06 15:35:03', '2025-07-07 02:44:23'),
(751, 437, 'C2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(752, 437, 'C3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(753, 437, 'C4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(754, 437, 'C5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(755, 437, 'C6', 'booked', 105, '2025-07-06 15:35:03', '2025-07-07 02:50:11'),
(756, 437, 'C7', 'booked', 105, '2025-07-06 15:35:03', '2025-07-07 02:50:11'),
(757, 437, 'C8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(758, 437, 'C9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(759, 437, 'C10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(760, 437, 'D1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(761, 437, 'D2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(762, 437, 'D3', 'booked', 103, '2025-07-06 15:35:03', '2025-07-07 02:44:23'),
(763, 437, 'D4', 'booked', 103, '2025-07-06 15:35:03', '2025-07-07 02:44:23'),
(764, 437, 'D5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(765, 437, 'D6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(766, 437, 'D7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(767, 437, 'D8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(768, 437, 'D9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(769, 437, 'D10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(770, 437, 'E1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(771, 437, 'E2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(772, 437, 'E3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(773, 437, 'E4', 'booked', 101, '2025-07-06 15:35:03', '2025-07-07 02:35:23'),
(774, 437, 'E5', 'booked', 101, '2025-07-06 15:35:03', '2025-07-07 02:35:23'),
(775, 437, 'E6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(776, 437, 'E7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(777, 437, 'E8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(778, 437, 'E9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(779, 437, 'E10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(780, 437, 'F1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(781, 437, 'F2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(782, 437, 'F3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(783, 437, 'F4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(784, 437, 'F5', 'booked', 100, '2025-07-06 15:35:03', '2025-07-07 02:09:30'),
(785, 437, 'F6', 'booked', 100, '2025-07-06 15:35:03', '2025-07-07 02:09:30'),
(786, 437, 'F7', 'booked', 100, '2025-07-06 15:35:03', '2025-07-07 02:09:30'),
(787, 437, 'F8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(788, 437, 'F9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(789, 437, 'F10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(790, 437, 'G1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(791, 437, 'G2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(792, 437, 'G3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(793, 437, 'G4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(794, 437, 'G5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(795, 437, 'G6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(796, 437, 'G7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(797, 437, 'G8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(798, 437, 'G9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(799, 437, 'G10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(800, 437, 'H1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(801, 437, 'H2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(802, 437, 'H3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(803, 437, 'H4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(804, 437, 'H5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(805, 437, 'H6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(806, 437, 'H7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(807, 437, 'H8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(808, 437, 'H9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(809, 437, 'H10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(810, 437, 'I1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(811, 437, 'I2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(812, 437, 'I3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(813, 437, 'I4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(814, 437, 'I5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(815, 437, 'I6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(816, 437, 'I7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(817, 437, 'I8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(818, 437, 'I9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(819, 437, 'I10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(820, 437, 'J1', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(821, 437, 'J2', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(822, 437, 'J3', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(823, 437, 'J4', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(824, 437, 'J5', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(825, 437, 'J6', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(826, 437, 'J7', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(827, 437, 'J8', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(828, 437, 'J9', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03'),
(829, 437, 'J10', 'available', NULL, '2025-07-06 15:35:03', '2025-07-06 15:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `shows`
--

CREATE TABLE `shows` (
  `show_id` int(11) NOT NULL,
  `movie_id` int(11) DEFAULT NULL,
  `theater_id` int(11) NOT NULL,
  `show_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hall_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shows`
--

INSERT INTO `shows` (`show_id`, `movie_id`, `theater_id`, `show_time`, `price`, `created_at`, `hall_id`) VALUES
(423, 5, 1, '2025-03-09 18:00:00', 150.00, '2025-03-08 10:52:55', NULL),
(425, 7, 1, '2025-03-08 15:00:00', 150.00, '2025-03-08 15:12:58', NULL),
(426, 7, 1, '2025-03-08 18:00:00', 200.00, '2025-03-08 15:14:43', NULL),
(427, 7, 1, '2025-03-09 21:00:00', 200.00, '2025-03-08 15:51:59', NULL),
(428, 7, 1, '2025-03-08 22:00:00', 230.00, '2025-03-08 16:09:50', NULL),
(429, 8, 1, '2025-03-10 21:00:00', 120.00, '2025-03-09 04:54:06', NULL),
(430, 8, 7, '2025-03-09 15:00:00', 200.00, '2025-03-09 06:56:56', NULL),
(431, 7, 1, '2025-03-11 18:00:00', 120.00, '2025-03-10 15:46:05', NULL),
(432, 7, 7, '2025-03-12 18:00:00', 1000.00, '2025-03-10 15:55:49', NULL),
(433, 5, 1, '2025-03-22 12:00:00', 200.00, '2025-03-21 14:38:41', NULL),
(434, 7, 1, '2025-03-28 21:00:00', 200.00, '2025-03-22 14:04:15', NULL),
(435, 8, 2, '2025-06-25 15:00:00', 200.00, '2025-06-23 16:37:45', 1),
(436, 5, 2, '2025-06-26 09:00:00', 150.00, '2025-06-25 15:56:01', 1),
(437, 5, 2, '2025-07-17 15:00:00', 150.00, '2025-07-06 15:35:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `support_categories`
--

CREATE TABLE `support_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_categories`
--

INSERT INTO `support_categories` (`category_id`, `name`, `description`) VALUES
(1, 'Booking Issues', 'Problems with booking tickets or reservations'),
(2, 'Payment Problems', 'Issues related to payments or refunds'),
(3, 'Account Access', 'Login problems or account recovery'),
(4, 'Website Errors', 'Technical issues with the website'),
(5, 'Movie Information', 'Questions about movies or showtimes'),
(6, 'General Inquiry', 'Other general questions');

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `message_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`message_id`, `ticket_id`, `sender_id`, `is_admin`, `message`, `attachment_url`, `created_at`, `is_read`) VALUES
(1, 1, 3, 0, 'Issue i already paid but not booked fixed this and refund \r\nTransaction id \r\n#EERY345H3', NULL, '2025-03-08 17:02:12', 1),
(2, 1, 2, 1, 'ok which payee method did you used?', NULL, '2025-03-08 17:17:21', 1),
(3, 1, 3, 0, 'khalti', NULL, '2025-03-08 17:17:51', 1),
(4, 2, 2, 0, 'hhh', NULL, '2025-03-08 17:22:10', 0),
(5, 3, 3, 0, 'ss', NULL, '2025-03-08 17:24:26', 0),
(6, 2, 2, 0, 'opp', NULL, '2025-03-09 01:10:03', 0);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`ticket_id`, `user_id`, `subject`, `status`, `priority`, `created_at`, `updated_at`, `category_id`) VALUES
(1, 3, 'Rufund', 'Resolved', 'Medium', '2025-03-08 17:02:12', '2025-03-08 17:18:46', 2),
(2, 2, 'Payment', 'Open', 'Medium', '2025-03-08 17:22:10', '2025-03-09 01:10:03', 6),
(3, 3, 'rere', 'Open', 'High', '2025-03-08 17:24:26', '2025-03-08 17:24:26', 1);

-- --------------------------------------------------------

--
-- Table structure for table `temp_seat_selections`
--

CREATE TABLE `temp_seat_selections` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theaters`
--

CREATE TABLE `theaters` (
  `theater_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `screens` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theater_image` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theaters`
--

INSERT INTO `theaters` (`theater_id`, `name`, `location`, `capacity`, `address`, `city`, `state`, `screens`, `created_at`, `theater_image`, `branch_id`) VALUES
(1, 'QFX', 'Itahari', 105, '', '', '', '', '2025-03-08 11:01:22', 'https://res.cloudinary.com/digddaiwf/images/w_720,h_480,c_scale/v1701580282/QFX-Cinemas-Nepal/QFX-Cinemas-Nepal.jpg?_i=AA', NULL),
(2, 'Screen1', 'Citimall', 90, 'Inaruwa-1, Sunsari', 'Inaruwa', 'Inaruwa', '1', '2025-06-23 16:24:28', NULL, 3),
(7, 'FCUB', 'Morang', 120, 'Inaruwa-1, purwatole\r\nDuhabi road, 445H PK colony', 'Itahari', 'Morang', '1', '2025-03-09 06:56:26', 'uploads/theaters/1741503386_download.jpeg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(0, 'Umesh Pokharel', 'umesh@gmail.com', '9811388848', '$2y$10$hilzvSFzf0WfFUlcPlLMEeEjG8vgMQrpYK3Edac8UdYsE57wCCGK.', 'user', '2025-07-07 02:16:55'),
(1, 'Admin', 'admin@example.com', '9800000000', '$2y$10$e0NRtQHChZz4H3MXqX0F9eSH8g/hLDBv2qvG4i6SBlNRJXpUFEli.', 'admin', '2025-03-02 17:13:44'),
(2, 'Prasanga Pokharels', 'prasanga@gmail.com', '9765470926', '$2y$10$y.eBTA5amXz0AujGvZlcTuMzN5GtX.f4tYUZ2fiwnUxbN2wvUGrQC', 'user', '2025-03-02 18:05:46'),
(3, 'Jolie Potts', 'movie@gmail.com', '9765470927', '$2y$10$w/hA7ahu4O/34TTSU/cHOOarOC.FslZsIKaT3V37qUD9T.nzmbmvi', 'user', '2025-03-08 06:49:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `show_id` (`show_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `halls`
--
ALTER TABLE `halls`
  ADD PRIMARY KEY (`hall_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`movie_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `show_id` (`show_id`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`seat_id`),
  ADD KEY `show_id` (`show_id`),
  ADD KEY `idx_seats_booking_show` (`booking_id`,`show_id`),
  ADD KEY `idx_seats_show_status` (`show_id`,`status`);

--
-- Indexes for table `shows`
--
ALTER TABLE `shows`
  ADD PRIMARY KEY (`show_id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `idx_shows_theater_movie` (`theater_id`,`movie_id`),
  ADD KEY `idx_shows_datetime` (`show_time`),
  ADD KEY `fk_show_hall` (`hall_id`);

--
-- Indexes for table `support_categories`
--
ALTER TABLE `support_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `temp_seat_selections`
--
ALTER TABLE `temp_seat_selections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seat_selection` (`show_id`,`seat_number`),
  ADD KEY `idx_show_timestamp` (`show_id`,`timestamp`),
  ADD KEY `idx_user_show` (`user_id`,`show_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `theaters`
--
ALTER TABLE `theaters`
  ADD PRIMARY KEY (`theater_id`),
  ADD KEY `idx_theater_location` (`location`),
  ADD KEY `fk_theater_branch` (`branch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `halls`
--
ALTER TABLE `halls`
  MODIFY `hall_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `movie_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `seat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=830;

--
-- AUTO_INCREMENT for table `shows`
--
ALTER TABLE `shows`
  MODIFY `show_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=438;

--
-- AUTO_INCREMENT for table `support_categories`
--
ALTER TABLE `support_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `temp_seat_selections`
--
ALTER TABLE `temp_seat_selections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `halls`
--
ALTER TABLE `halls`
  ADD CONSTRAINT `halls_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `shows`
--
ALTER TABLE `shows`
  ADD CONSTRAINT `fk_show_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`hall_id`) ON DELETE SET NULL;

--
-- Constraints for table `temp_seat_selections`
--
ALTER TABLE `temp_seat_selections`
  ADD CONSTRAINT `temp_seat_selections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `temp_seat_selections_ibfk_2` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`) ON DELETE CASCADE;

--
-- Constraints for table `theaters`
--
ALTER TABLE `theaters`
  ADD CONSTRAINT `fk_theater_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
