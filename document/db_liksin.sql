-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th7 31, 2023 lúc 11:16 AM
-- Phiên bản máy phục vụ: 10.4.21-MariaDB
-- Phiên bản PHP: 7.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `db_liksin`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_menu`
--

CREATE TABLE `admin_menu` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_menu`
--

INSERT INTO `admin_menu` (`id`, `parent_id`, `order`, `title`, `icon`, `uri`, `permission`, `created_at`, `updated_at`) VALUES
(1, 0, 1, 'Trang chủ', 'fa-home', '/', NULL, NULL, '2023-07-17 04:29:37'),
(2, 0, 10, 'Admin', 'fa-tasks', '', NULL, NULL, '2023-07-20 08:19:26'),
(3, 2, 11, 'Users', 'fa-users', '/custom-users', NULL, NULL, '2023-07-20 08:19:26'),
(4, 2, 12, 'Roles', 'fa-user', 'auth/roles', NULL, NULL, '2023-07-20 08:19:26'),
(5, 2, 13, 'Permission', 'fa-ban', 'auth/permissions', NULL, NULL, '2023-07-20 08:19:26'),
(6, 2, 14, 'Menu', 'fa-bars', 'auth/menu', NULL, NULL, '2023-07-20 08:19:26'),
(7, 2, 15, 'Operation log', 'fa-history', 'auth/logs', NULL, NULL, '2023-07-20 08:19:26'),
(8, 0, 6, 'Quản lý  danh mục', 'fa-bars', NULL, NULL, '2023-07-17 04:21:06', '2023-07-20 08:19:26'),
(9, 8, 7, 'Danh sách lỗi', 'fa-align-justify', '/errors', NULL, '2023-07-17 04:21:53', '2023-07-20 08:19:26'),
(10, 8, 8, 'Danh sách chỉ tiêu kiểm tra', 'fa-bars', '/test_criteria', NULL, '2023-07-17 04:22:22', '2023-07-20 08:19:26'),
(11, 8, 9, 'Danh sách công đoạn', 'fa-bars', '/lines', NULL, '2023-07-17 04:23:40', '2023-07-20 08:19:26'),
(12, 0, 4, 'Quản lý máy móc thiết bị', 'fa-cogs', NULL, NULL, '2023-07-17 04:26:12', '2023-07-20 08:19:26'),
(13, 12, 5, 'Danh sách máy', 'fa-bars', '/machine', NULL, '2023-07-17 04:28:39', '2023-07-20 08:19:26'),
(14, 0, 2, 'Quản lý sản xuất', 'fa-bars', NULL, NULL, '2023-07-20 08:18:56', '2023-07-20 08:19:26'),
(15, 14, 3, 'Kế hoạch sản xuất', 'fa-bars', '/production_plan', NULL, '2023-07-20 08:19:12', '2023-07-20 08:19:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_operation_log`
--

CREATE TABLE `admin_operation_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_operation_log`
--

INSERT INTO `admin_operation_log` (`id`, `user_id`, `path`, `method`, `ip`, `input`, `created_at`, `updated_at`) VALUES
(1097, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-17 04:41:41', '2023-07-17 04:41:41'),
(1098, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-17 06:15:33', '2023-07-17 06:15:33'),
(1099, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:15:56', '2023-07-17 06:15:56'),
(1100, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:08', '2023-07-17 06:16:08'),
(1101, 1, 'admin/auth/menu/3/edit', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:30', '2023-07-17 06:16:30'),
(1102, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:39', '2023-07-17 06:16:39'),
(1103, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:40', '2023-07-17 06:16:40'),
(1104, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:43', '2023-07-17 06:16:43'),
(1105, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:43', '2023-07-17 06:16:43'),
(1106, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:44', '2023-07-17 06:16:44'),
(1107, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:45', '2023-07-17 06:16:45'),
(1108, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:46', '2023-07-17 06:16:46'),
(1109, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:16:46', '2023-07-17 06:16:46'),
(1110, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:17:00', '2023-07-17 06:17:00'),
(1111, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:07', '2023-07-17 06:22:07'),
(1112, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:07', '2023-07-17 06:22:07'),
(1113, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:08', '2023-07-17 06:22:08'),
(1114, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:09', '2023-07-17 06:22:09'),
(1115, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:10', '2023-07-17 06:22:10'),
(1116, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:14', '2023-07-17 06:22:14'),
(1117, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:14', '2023-07-17 06:22:14'),
(1118, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:22:15', '2023-07-17 06:22:15'),
(1119, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:45:32', '2023-07-17 06:45:32'),
(1120, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:45:33', '2023-07-17 06:45:33'),
(1121, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:45:34', '2023-07-17 06:45:34'),
(1122, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:45:35', '2023-07-17 06:45:35'),
(1123, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:45:36', '2023-07-17 06:45:36'),
(1124, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-17 06:46:23', '2023-07-17 06:46:23'),
(1125, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-17 06:46:48', '2023-07-17 06:46:48'),
(1126, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:46:58', '2023-07-17 06:46:58'),
(1127, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:01', '2023-07-17 06:47:01'),
(1128, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:03', '2023-07-17 06:47:03'),
(1129, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:04', '2023-07-17 06:47:04'),
(1130, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:06', '2023-07-17 06:47:06'),
(1131, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:27', '2023-07-17 06:47:27'),
(1132, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"4\"}', '2023-07-17 06:47:32', '2023-07-17 06:47:32'),
(1133, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-17 06:47:36', '2023-07-17 06:47:36'),
(1134, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-17 06:47:40', '2023-07-17 06:47:40'),
(1135, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-17 06:47:46', '2023-07-17 06:47:46'),
(1136, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-17 06:47:48', '2023-07-17 06:47:48'),
(1137, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"4\"}', '2023-07-17 06:47:49', '2023-07-17 06:47:49'),
(1138, 1, 'admin/custom-users/71', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:54', '2023-07-17 06:47:54'),
(1139, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:47:58', '2023-07-17 06:47:58'),
(1140, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:06', '2023-07-17 06:48:06'),
(1141, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:08', '2023-07-17 06:48:08'),
(1142, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:08', '2023-07-17 06:48:08'),
(1143, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:10', '2023-07-17 06:48:10'),
(1144, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:10', '2023-07-17 06:48:10'),
(1145, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:11', '2023-07-17 06:48:11'),
(1146, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:11', '2023-07-17 06:48:11'),
(1147, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:12', '2023-07-17 06:48:12'),
(1148, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:14', '2023-07-17 06:48:14'),
(1149, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:15', '2023-07-17 06:48:15'),
(1150, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:27', '2023-07-17 06:48:27'),
(1151, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:48:46', '2023-07-17 06:48:46'),
(1152, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:49:11', '2023-07-17 06:49:11'),
(1153, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:49:43', '2023-07-17 06:49:43'),
(1154, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:49:46', '2023-07-17 06:49:46'),
(1155, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:49:48', '2023-07-17 06:49:48'),
(1156, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:50:15', '2023-07-17 06:50:15'),
(1157, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:14', '2023-07-17 06:51:14'),
(1158, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:22', '2023-07-17 06:51:22'),
(1159, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:23', '2023-07-17 06:51:23'),
(1160, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:23', '2023-07-17 06:51:23'),
(1161, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:25', '2023-07-17 06:51:25'),
(1162, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:26', '2023-07-17 06:51:26'),
(1163, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:26', '2023-07-17 06:51:26'),
(1164, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:27', '2023-07-17 06:51:27'),
(1165, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-17 06:51:42', '2023-07-17 06:51:42'),
(1166, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-20 02:34:10', '2023-07-20 02:34:10'),
(1167, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:34:14', '2023-07-20 02:34:14'),
(1168, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 02:34:23', '2023-07-20 02:34:23'),
(1169, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 02:34:28', '2023-07-20 02:34:28'),
(1170, 1, 'admin/production_plan/create', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 02:34:36', '2023-07-20 02:34:36'),
(1171, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 02:34:55', '2023-07-20 02:34:55'),
(1172, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:40:20', '2023-07-20 02:40:20'),
(1173, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:40:52', '2023-07-20 02:40:52'),
(1174, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:44:12', '2023-07-20 02:44:12'),
(1175, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:51:53', '2023-07-20 02:51:53'),
(1176, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 02:51:56', '2023-07-20 02:51:56'),
(1177, 1, 'admin/production_plan/import', 'GET', '127.0.0.1', '[]', '2023-07-20 02:52:32', '2023-07-20 02:52:32'),
(1178, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:52:36', '2023-07-20 02:52:36'),
(1179, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:52:42', '2023-07-20 02:52:42'),
(1180, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 02:52:47', '2023-07-20 02:52:47'),
(1181, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:52:47', '2023-07-20 02:52:47'),
(1182, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 02:52:57', '2023-07-20 02:52:57'),
(1183, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:52:57', '2023-07-20 02:52:57'),
(1184, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:53:28', '2023-07-20 02:53:28'),
(1185, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 02:53:30', '2023-07-20 02:53:30'),
(1186, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 02:53:30', '2023-07-20 02:53:30'),
(1187, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 02:55:05', '2023-07-20 02:55:05'),
(1188, 1, 'admin/production_plan/import', 'GET', '127.0.0.1', '[]', '2023-07-20 03:07:28', '2023-07-20 03:07:28'),
(1189, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:07:30', '2023-07-20 03:07:30'),
(1190, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:07:39', '2023-07-20 03:07:39'),
(1191, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:08:13', '2023-07-20 03:08:13'),
(1192, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:08:17', '2023-07-20 03:08:17'),
(1193, 1, 'admin/production_plan/import', 'GET', '127.0.0.1', '[]', '2023-07-20 03:08:29', '2023-07-20 03:08:29'),
(1194, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:08:31', '2023-07-20 03:08:31'),
(1195, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:08:36', '2023-07-20 03:08:36'),
(1196, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:31:26', '2023-07-20 03:31:26'),
(1197, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:32:02', '2023-07-20 03:32:02'),
(1198, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:32:06', '2023-07-20 03:32:06'),
(1199, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:32:59', '2023-07-20 03:32:59'),
(1200, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:33:00', '2023-07-20 03:33:00'),
(1201, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:33:08', '2023-07-20 03:33:08'),
(1202, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:33:22', '2023-07-20 03:33:22'),
(1203, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:34:38', '2023-07-20 03:34:38'),
(1204, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:34:42', '2023-07-20 03:34:42'),
(1205, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:35:25', '2023-07-20 03:35:25'),
(1206, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:35:29', '2023-07-20 03:35:29'),
(1207, 1, 'admin/production_plan/1', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 03:36:34', '2023-07-20 03:36:34'),
(1208, 1, 'admin/production_plan/1', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:36:37', '2023-07-20 03:36:37'),
(1209, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 03:36:37', '2023-07-20 03:36:37'),
(1210, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:36:49', '2023-07-20 03:36:49'),
(1211, 1, 'admin/production_plan/import', 'GET', '127.0.0.1', '[]', '2023-07-20 03:37:29', '2023-07-20 03:37:29'),
(1212, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:37:30', '2023-07-20 03:37:30'),
(1213, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:37:40', '2023-07-20 03:37:40'),
(1214, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:37:40', '2023-07-20 03:37:40'),
(1215, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:38:16', '2023-07-20 03:38:16'),
(1216, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:38:55', '2023-07-20 03:38:55'),
(1217, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:39:00', '2023-07-20 03:39:00'),
(1218, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:39:00', '2023-07-20 03:39:00'),
(1219, 1, 'admin/production_plan/2,3,4,5', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:39:16', '2023-07-20 03:39:16'),
(1220, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 03:39:16', '2023-07-20 03:39:16'),
(1221, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:40:07', '2023-07-20 03:40:07'),
(1222, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:40:17', '2023-07-20 03:40:17'),
(1223, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:40:55', '2023-07-20 03:40:55'),
(1224, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:41:31', '2023-07-20 03:41:31'),
(1225, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:43:25', '2023-07-20 03:43:25'),
(1226, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 03:44:10', '2023-07-20 03:44:10'),
(1227, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:44:10', '2023-07-20 03:44:10'),
(1228, 1, 'admin/production_plan/6/edit', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 03:44:38', '2023-07-20 03:44:38'),
(1229, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 03:44:45', '2023-07-20 03:44:45'),
(1230, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:46:27', '2023-07-20 03:46:27'),
(1231, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 03:46:46', '2023-07-20 03:46:46'),
(1232, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:03:47', '2023-07-20 04:03:47'),
(1233, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 04:03:52', '2023-07-20 04:03:52'),
(1234, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 04:04:28', '2023-07-20 04:04:28'),
(1235, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:11:24', '2023-07-20 04:11:24'),
(1236, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 04:11:28', '2023-07-20 04:11:28'),
(1237, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:11:28', '2023-07-20 04:11:28'),
(1238, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:12:08', '2023-07-20 04:12:08'),
(1239, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"or6Lh1BYcObOc4sijtQEFqWmoYKCrZ3Gcd3vWDkI\"}', '2023-07-20 04:12:14', '2023-07-20 04:12:14'),
(1240, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:12:14', '2023-07-20 04:12:14'),
(1241, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 04:12:16', '2023-07-20 04:12:16'),
(1242, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-20 07:36:55', '2023-07-20 07:36:55'),
(1243, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-20 07:37:27', '2023-07-20 07:37:27'),
(1244, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 07:57:47', '2023-07-20 07:57:47'),
(1245, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:08:49', '2023-07-20 08:08:49'),
(1246, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:10:41', '2023-07-20 08:10:41'),
(1247, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:10:56', '2023-07-20 08:10:56'),
(1248, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:11:15', '2023-07-20 08:11:15'),
(1249, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:14:33', '2023-07-20 08:14:33'),
(1250, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:14:37', '2023-07-20 08:14:37'),
(1251, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:14:37', '2023-07-20 08:14:37'),
(1252, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:14:40', '2023-07-20 08:14:40'),
(1253, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:14:50', '2023-07-20 08:14:50'),
(1254, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:15:30', '2023-07-20 08:15:30'),
(1255, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:16:24', '2023-07-20 08:16:24'),
(1256, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:16:46', '2023-07-20 08:16:46'),
(1257, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:17:55', '2023-07-20 08:17:55'),
(1258, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:18:21', '2023-07-20 08:18:21'),
(1259, 1, 'admin/auth/menu', 'POST', '127.0.0.1', '{\"parent_id\":\"0\",\"title\":\"Qu\\u1ea3n l\\u00fd s\\u1ea3n xu\\u1ea5t\",\"icon\":\"fa-bars\",\"uri\":null,\"roles\":[null],\"permission\":null,\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:18:56', '2023-07-20 08:18:56'),
(1260, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '[]', '2023-07-20 08:18:56', '2023-07-20 08:18:56'),
(1261, 1, 'admin/auth/menu', 'POST', '127.0.0.1', '{\"parent_id\":\"14\",\"title\":\"K\\u1ebf ho\\u1ea1ch s\\u1ea3n xu\\u1ea5t\",\"icon\":\"fa-bars\",\"uri\":\"\\/production_plan\",\"roles\":[null],\"permission\":null,\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:19:12', '2023-07-20 08:19:12'),
(1262, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '[]', '2023-07-20 08:19:12', '2023-07-20 08:19:12'),
(1263, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '[]', '2023-07-20 08:19:15', '2023-07-20 08:19:15'),
(1264, 1, 'admin/auth/menu', 'POST', '127.0.0.1', '{\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\",\"_order\":\"[{\\\"id\\\":1},{\\\"id\\\":14,\\\"children\\\":[{\\\"id\\\":15}]},{\\\"id\\\":12,\\\"children\\\":[{\\\"id\\\":13}]},{\\\"id\\\":8,\\\"children\\\":[{\\\"id\\\":9},{\\\"id\\\":10},{\\\"id\\\":11}]},{\\\"id\\\":2,\\\"children\\\":[{\\\"id\\\":3},{\\\"id\\\":4},{\\\"id\\\":5},{\\\"id\\\":6},{\\\"id\\\":7}]}]\"}', '2023-07-20 08:19:26', '2023-07-20 08:19:26'),
(1265, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:19:26', '2023-07-20 08:19:26'),
(1266, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '[]', '2023-07-20 08:19:28', '2023-07-20 08:19:28'),
(1267, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:19:31', '2023-07-20 08:19:31'),
(1268, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:19:43', '2023-07-20 08:19:43'),
(1269, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:19:47', '2023-07-20 08:19:47'),
(1270, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:21:01', '2023-07-20 08:21:01'),
(1271, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:21:03', '2023-07-20 08:21:03'),
(1272, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:21:09', '2023-07-20 08:21:09'),
(1273, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:21:22', '2023-07-20 08:21:22'),
(1274, 1, 'admin/production_plan/12,13', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:25:26', '2023-07-20 08:25:26'),
(1275, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:25:26', '2023-07-20 08:25:26'),
(1276, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:25:35', '2023-07-20 08:25:35'),
(1277, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:26:09', '2023-07-20 08:26:09'),
(1278, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:26:19', '2023-07-20 08:26:19'),
(1279, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:26:19', '2023-07-20 08:26:19'),
(1280, 1, 'admin/production_plan/14,15', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:26:54', '2023-07-20 08:26:54'),
(1281, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-20 08:26:54', '2023-07-20 08:26:54'),
(1282, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"Tia4NDfp2vf6zFI5vFiBuPOj02UupIlA5GWo0uRX\"}', '2023-07-20 08:32:10', '2023-07-20 08:32:10'),
(1283, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-20 08:32:10', '2023-07-20 08:32:10'),
(1284, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-27 08:18:07', '2023-07-27 08:18:07'),
(1285, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:46', '2023-07-27 08:21:46'),
(1286, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:47', '2023-07-27 08:21:47'),
(1287, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:49', '2023-07-27 08:21:49'),
(1288, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:51', '2023-07-27 08:21:51'),
(1289, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:51', '2023-07-27 08:21:51'),
(1290, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:52', '2023-07-27 08:21:52'),
(1291, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:53', '2023-07-27 08:21:53'),
(1292, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:21:53', '2023-07-27 08:21:53'),
(1293, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:00', '2023-07-27 08:22:00'),
(1294, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:00', '2023-07-27 08:22:00'),
(1295, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:03', '2023-07-27 08:22:03'),
(1296, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:03', '2023-07-27 08:22:03'),
(1297, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:04', '2023-07-27 08:22:04'),
(1298, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:05', '2023-07-27 08:22:05'),
(1299, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:07', '2023-07-27 08:22:07'),
(1300, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:07', '2023-07-27 08:22:07'),
(1301, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:08', '2023-07-27 08:22:08'),
(1302, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:09', '2023-07-27 08:22:09'),
(1303, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:09', '2023-07-27 08:22:09'),
(1304, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:10', '2023-07-27 08:22:10'),
(1305, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:11', '2023-07-27 08:22:11'),
(1306, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:11', '2023-07-27 08:22:11'),
(1307, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:15', '2023-07-27 08:22:15'),
(1308, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:20', '2023-07-27 08:22:20'),
(1309, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 08:22:21', '2023-07-27 08:22:21'),
(1310, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-27 09:07:32', '2023-07-27 09:07:32'),
(1311, 1, 'admin/machine', 'GET', '127.0.0.1', '[]', '2023-07-28 02:06:50', '2023-07-28 02:06:50'),
(1312, 1, 'admin/time-sets', 'GET', '127.0.0.1', '[]', '2023-07-28 02:20:02', '2023-07-28 02:20:02'),
(1313, 1, 'admin/time-sets/create', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 02:20:30', '2023-07-28 02:20:30'),
(1314, 1, 'admin/time-sets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 02:20:36', '2023-07-28 02:20:36'),
(1315, 1, 'admin/time-sets', 'GET', '127.0.0.1', '[]', '2023-07-28 02:21:40', '2023-07-28 02:21:40'),
(1316, 1, 'admin/time-sets/create', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 02:21:44', '2023-07-28 02:21:44'),
(1317, 1, 'admin/time-sets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 02:21:47', '2023-07-28 02:21:47'),
(1318, 1, 'admin/time-sets', 'GET', '127.0.0.1', '[]', '2023-07-28 02:22:06', '2023-07-28 02:22:06'),
(1319, 1, 'admin/time-sets', 'GET', '127.0.0.1', '[]', '2023-07-28 02:33:45', '2023-07-28 02:33:45'),
(1320, 1, 'admin/time-sets/create', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 03:31:33', '2023-07-28 03:31:33'),
(1321, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 03:41:46', '2023-07-28 03:41:46'),
(1322, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 03:41:49', '2023-07-28 03:41:49'),
(1323, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 08:43:10', '2023-07-28 08:43:10'),
(1324, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 09:03:26', '2023-07-28 09:03:26'),
(1325, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 09:03:28', '2023-07-28 09:03:28'),
(1326, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-28 09:03:31', '2023-07-28 09:03:31'),
(1327, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 02:07:28', '2023-07-29 02:07:28'),
(1328, 1, 'admin/materials', 'GET', '127.0.0.1', '[]', '2023-07-29 03:36:31', '2023-07-29 03:36:31'),
(1329, 1, 'admin/materials', 'GET', '127.0.0.1', '[]', '2023-07-29 03:37:29', '2023-07-29 03:37:29'),
(1330, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-29 03:38:20', '2023-07-29 03:38:20'),
(1331, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:39:56', '2023-07-29 03:39:56'),
(1332, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:40:55', '2023-07-29 03:40:55'),
(1333, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:41:56', '2023-07-29 03:41:56'),
(1334, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:43:04', '2023-07-29 03:43:04'),
(1335, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:45:20', '2023-07-29 03:45:20'),
(1336, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 03:45:27', '2023-07-29 03:45:27'),
(1337, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 03:49:10', '2023-07-29 03:49:10'),
(1338, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 03:49:15', '2023-07-29 03:49:15'),
(1339, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 03:49:20', '2023-07-29 03:49:20'),
(1340, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:11:04', '2023-07-29 04:11:04'),
(1341, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:11:16', '2023-07-29 04:11:16'),
(1342, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:12:45', '2023-07-29 04:12:45'),
(1343, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:12:49', '2023-07-29 04:12:49'),
(1344, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:13:29', '2023-07-29 04:13:29'),
(1345, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:13:34', '2023-07-29 04:13:34'),
(1346, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:15:08', '2023-07-29 04:15:08'),
(1347, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:15:12', '2023-07-29 04:15:12'),
(1348, 1, 'admin/products/import', 'GET', '127.0.0.1', '[]', '2023-07-29 04:17:48', '2023-07-29 04:17:48'),
(1349, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:17:57', '2023-07-29 04:17:57'),
(1350, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:19:03', '2023-07-29 04:19:03'),
(1351, 1, 'admin/products/import', 'GET', '127.0.0.1', '[]', '2023-07-29 04:19:27', '2023-07-29 04:19:27'),
(1352, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:19:40', '2023-07-29 04:19:40'),
(1353, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:19:46', '2023-07-29 04:19:46'),
(1354, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:20:14', '2023-07-29 04:20:14'),
(1355, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:20:18', '2023-07-29 04:20:18'),
(1356, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:20:34', '2023-07-29 04:20:34'),
(1357, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"0\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 04:20:39', '2023-07-29 04:20:39'),
(1358, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"0\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 04:20:50', '2023-07-29 04:20:50'),
(1359, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:21:10', '2023-07-29 04:21:10'),
(1360, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:21:14', '2023-07-29 04:21:14'),
(1361, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:24:59', '2023-07-29 04:24:59'),
(1362, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:25:03', '2023-07-29 04:25:03'),
(1363, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"0\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 04:25:09', '2023-07-29 04:25:09'),
(1364, 1, 'admin/products/0', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:25:18', '2023-07-29 04:25:18'),
(1365, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 04:25:18', '2023-07-29 04:25:18'),
(1366, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:25:22', '2023-07-29 04:25:22'),
(1367, 1, 'admin/products/0', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:25:28', '2023-07-29 04:25:28'),
(1368, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 04:25:28', '2023-07-29 04:25:28'),
(1369, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:25:31', '2023-07-29 04:25:31'),
(1370, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"0\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 04:25:38', '2023-07-29 04:25:38'),
(1371, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:26:44', '2023-07-29 04:26:44'),
(1372, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"0\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 04:26:48', '2023-07-29 04:26:48'),
(1373, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:27:28', '2023-07-29 04:27:28'),
(1374, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:27:32', '2023-07-29 04:27:32'),
(1375, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:29:14', '2023-07-29 04:29:14'),
(1376, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:29:21', '2023-07-29 04:29:21'),
(1377, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:32:20', '2023-07-29 04:32:20'),
(1378, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:32:26', '2023-07-29 04:32:26'),
(1379, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:34:42', '2023-07-29 04:34:42'),
(1380, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:34:46', '2023-07-29 04:34:46'),
(1381, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:36:11', '2023-07-29 04:36:11'),
(1382, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:36:14', '2023-07-29 04:36:14'),
(1383, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:36:28', '2023-07-29 04:36:28'),
(1384, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:36:34', '2023-07-29 04:36:34'),
(1385, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:36:38', '2023-07-29 04:36:38'),
(1386, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:37:08', '2023-07-29 04:37:08'),
(1387, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:37:12', '2023-07-29 04:37:12'),
(1388, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:37:29', '2023-07-29 04:37:29'),
(1389, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:37:34', '2023-07-29 04:37:34'),
(1390, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:38:50', '2023-07-29 04:38:50'),
(1391, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:38:53', '2023-07-29 04:38:53'),
(1392, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:39:28', '2023-07-29 04:39:28'),
(1393, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:39:32', '2023-07-29 04:39:32'),
(1394, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:40:37', '2023-07-29 04:40:37'),
(1395, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:40:43', '2023-07-29 04:40:43'),
(1396, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:41:00', '2023-07-29 04:41:00'),
(1397, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:41:04', '2023-07-29 04:41:04'),
(1398, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:41:38', '2023-07-29 04:41:38'),
(1399, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:42:00', '2023-07-29 04:42:00'),
(1400, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:42:13', '2023-07-29 04:42:13'),
(1401, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:42:17', '2023-07-29 04:42:17'),
(1402, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:46:35', '2023-07-29 04:46:35'),
(1403, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:46:38', '2023-07-29 04:46:38'),
(1404, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 04:47:21', '2023-07-29 04:47:21'),
(1405, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 04:47:27', '2023-07-29 04:47:27'),
(1406, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:15:35', '2023-07-29 06:15:35'),
(1407, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 06:18:47', '2023-07-29 06:18:47'),
(1408, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:18:50', '2023-07-29 06:18:50'),
(1409, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 06:22:01', '2023-07-29 06:22:01'),
(1410, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:22:09', '2023-07-29 06:22:09'),
(1411, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-29 06:22:10', '2023-07-29 06:22:10'),
(1412, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 06:22:13', '2023-07-29 06:22:13'),
(1413, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:22:26', '2023-07-29 06:22:26'),
(1414, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:22:37', '2023-07-29 06:22:37'),
(1415, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:22:48', '2023-07-29 06:22:48'),
(1416, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:22:52', '2023-07-29 06:22:52'),
(1417, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"3\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:22:54', '2023-07-29 06:22:54'),
(1418, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-29 06:22:57', '2023-07-29 06:22:57'),
(1419, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 06:22:59', '2023-07-29 06:22:59'),
(1420, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:30:07', '2023-07-29 06:30:07'),
(1421, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:30:12', '2023-07-29 06:30:12'),
(1422, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:31:58', '2023-07-29 06:31:58'),
(1423, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"7\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:32:01', '2023-07-29 06:32:01'),
(1424, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 06:32:06', '2023-07-29 06:32:06'),
(1425, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"7\"}', '2023-07-29 06:32:07', '2023-07-29 06:32:07'),
(1426, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:32:10', '2023-07-29 06:32:10'),
(1427, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"7\"}', '2023-07-29 06:32:12', '2023-07-29 06:32:12'),
(1428, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"6\"}', '2023-07-29 06:32:15', '2023-07-29 06:32:15'),
(1429, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"5\"}', '2023-07-29 06:32:17', '2023-07-29 06:32:17'),
(1430, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"4\"}', '2023-07-29 06:32:18', '2023-07-29 06:32:18'),
(1431, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-29 06:32:20', '2023-07-29 06:32:20'),
(1432, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-29 06:32:21', '2023-07-29 06:32:21'),
(1433, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 06:32:22', '2023-07-29 06:32:22'),
(1434, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:33:43', '2023-07-29 06:33:43'),
(1435, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:35:55', '2023-07-29 06:35:55'),
(1436, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-29 06:36:58', '2023-07-29 06:36:58'),
(1437, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"3\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:02', '2023-07-29 06:38:02'),
(1438, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"7\"}', '2023-07-29 06:38:03', '2023-07-29 06:38:03'),
(1439, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"5\"}', '2023-07-29 06:38:04', '2023-07-29 06:38:04'),
(1440, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-29 06:38:05', '2023-07-29 06:38:05'),
(1441, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 06:38:06', '2023-07-29 06:38:06'),
(1442, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-29 06:38:19', '2023-07-29 06:38:19'),
(1443, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"4\"}', '2023-07-29 06:38:21', '2023-07-29 06:38:21'),
(1444, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 06:38:23', '2023-07-29 06:38:23'),
(1445, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:36', '2023-07-29 06:38:36'),
(1446, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:38', '2023-07-29 06:38:38'),
(1447, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:40', '2023-07-29 06:38:40'),
(1448, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:40', '2023-07-29 06:38:40'),
(1449, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:41', '2023-07-29 06:38:41'),
(1450, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:41', '2023-07-29 06:38:41'),
(1451, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:42', '2023-07-29 06:38:42'),
(1452, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:44', '2023-07-29 06:38:44'),
(1453, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:46', '2023-07-29 06:38:46'),
(1454, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:49', '2023-07-29 06:38:49'),
(1455, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:49', '2023-07-29 06:38:49'),
(1456, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:50', '2023-07-29 06:38:50'),
(1457, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:50', '2023-07-29 06:38:50'),
(1458, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:52', '2023-07-29 06:38:52'),
(1459, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:52', '2023-07-29 06:38:52'),
(1460, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:53', '2023-07-29 06:38:53'),
(1461, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:38:54', '2023-07-29 06:38:54'),
(1462, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:39:02', '2023-07-29 06:39:02'),
(1463, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:39:03', '2023-07-29 06:39:03'),
(1464, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:39:03', '2023-07-29 06:39:03'),
(1465, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:41:56', '2023-07-29 06:41:56'),
(1466, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:41:57', '2023-07-29 06:41:57'),
(1467, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:41:59', '2023-07-29 06:41:59'),
(1468, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:01', '2023-07-29 06:42:01'),
(1469, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:01', '2023-07-29 06:42:01'),
(1470, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:02', '2023-07-29 06:42:02'),
(1471, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:03', '2023-07-29 06:42:03'),
(1472, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:03', '2023-07-29 06:42:03'),
(1473, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:06', '2023-07-29 06:42:06');
INSERT INTO `admin_operation_log` (`id`, `user_id`, `path`, `method`, `ip`, `input`, `created_at`, `updated_at`) VALUES
(1474, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:07', '2023-07-29 06:42:07'),
(1475, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:08', '2023-07-29 06:42:08'),
(1476, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:08', '2023-07-29 06:42:08'),
(1477, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:09', '2023-07-29 06:42:09'),
(1478, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:09', '2023-07-29 06:42:09'),
(1479, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:10', '2023-07-29 06:42:10'),
(1480, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:10', '2023-07-29 06:42:10'),
(1481, 1, 'admin/auth/roles', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:11', '2023-07-29 06:42:11'),
(1482, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:15', '2023-07-29 06:42:15'),
(1483, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:16', '2023-07-29 06:42:16'),
(1484, 1, 'admin/auth/menu', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:17', '2023-07-29 06:42:17'),
(1485, 1, 'admin/auth/permissions', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:17', '2023-07-29 06:42:17'),
(1486, 1, 'admin/custom-users', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:17', '2023-07-29 06:42:17'),
(1487, 1, 'admin', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:20', '2023-07-29 06:42:20'),
(1488, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:20', '2023-07-29 06:42:20'),
(1489, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:23', '2023-07-29 06:42:23'),
(1490, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:24', '2023-07-29 06:42:24'),
(1491, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 06:42:25', '2023-07-29 06:42:25'),
(1492, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 07:24:05', '2023-07-29 07:24:05'),
(1493, 1, 'admin/_handle_action_', 'POST', '127.0.0.1', '{\"_key\":\"MHB077\",\"_model\":\"App_Models_Product\",\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\",\"_action\":\"Encore_Admin_Grid_Actions_Delete\",\"_input\":\"true\"}', '2023-07-29 07:24:10', '2023-07-29 07:24:10'),
(1494, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:10', '2023-07-29 07:24:10'),
(1495, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"IvtJMkr6CTL8jKMRCYQi6kfcsTWT8uz2fRNNAJ2f\"}', '2023-07-29 07:24:16', '2023-07-29 07:24:16'),
(1496, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 07:24:18', '2023-07-29 07:24:18'),
(1497, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"7\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:20', '2023-07-29 07:24:20'),
(1498, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 07:24:22', '2023-07-29 07:24:22'),
(1499, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"7\"}', '2023-07-29 07:24:23', '2023-07-29 07:24:23'),
(1500, 1, 'admin/products', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-29 07:24:25', '2023-07-29 07:24:25'),
(1501, 1, 'admin/products/MHB077', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:30', '2023-07-29 07:24:30'),
(1502, 1, 'admin/products', 'GET', '127.0.0.1', '{\"page\":\"1\",\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:33', '2023-07-29 07:24:33'),
(1503, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:40', '2023-07-29 07:24:40'),
(1504, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:41', '2023-07-29 07:24:41'),
(1505, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:42', '2023-07-29 07:24:42'),
(1506, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:43', '2023-07-29 07:24:43'),
(1507, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:44', '2023-07-29 07:24:44'),
(1508, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:45', '2023-07-29 07:24:45'),
(1509, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 07:24:45', '2023-07-29 07:24:45'),
(1510, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-29 07:24:54', '2023-07-29 07:24:54'),
(1511, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:22:59', '2023-07-29 08:22:59'),
(1512, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:01', '2023-07-29 08:23:01'),
(1513, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:02', '2023-07-29 08:23:02'),
(1514, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:04', '2023-07-29 08:23:04'),
(1515, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:17', '2023-07-29 08:23:17'),
(1516, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:20', '2023-07-29 08:23:20'),
(1517, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-29 08:23:53', '2023-07-29 08:23:53'),
(1518, 1, 'admin/custom-users', 'GET', '127.0.0.1', '[]', '2023-07-29 08:24:04', '2023-07-29 08:24:04'),
(1519, 1, 'admin/customers', 'GET', '127.0.0.1', '[]', '2023-07-29 08:24:10', '2023-07-29 08:24:10'),
(1520, 1, 'admin/customers/NTL383', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:24:14', '2023-07-29 08:24:14'),
(1521, 1, 'admin/customers', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:24:16', '2023-07-29 08:24:16'),
(1522, 1, 'admin/customers', 'GET', '127.0.0.1', '[]', '2023-07-29 08:25:44', '2023-07-29 08:25:44'),
(1523, 1, 'admin/customers', 'GET', '127.0.0.1', '[]', '2023-07-29 08:27:04', '2023-07-29 08:27:04'),
(1524, 1, 'admin/machine', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:27:06', '2023-07-29 08:27:06'),
(1525, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:27:08', '2023-07-29 08:27:08'),
(1526, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:27:09', '2023-07-29 08:27:09'),
(1527, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:27:09', '2023-07-29 08:27:09'),
(1528, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:27:10', '2023-07-29 08:27:10'),
(1529, 1, 'admin/lines', 'GET', '127.0.0.1', '[]', '2023-07-29 08:27:46', '2023-07-29 08:27:46'),
(1530, 1, 'admin/lines', 'GET', '127.0.0.1', '[]', '2023-07-29 08:28:06', '2023-07-29 08:28:06'),
(1531, 1, 'admin/lines', 'GET', '127.0.0.1', '[]', '2023-07-29 08:28:13', '2023-07-29 08:28:13'),
(1532, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:28:16', '2023-07-29 08:28:16'),
(1533, 1, 'admin/lines', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:28:19', '2023-07-29 08:28:19'),
(1534, 1, 'admin/test_criteria', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:28:19', '2023-07-29 08:28:19'),
(1535, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-29 08:28:20', '2023-07-29 08:28:20'),
(1536, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-31 01:17:19', '2023-07-31 01:17:19'),
(1537, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-31 01:17:26', '2023-07-31 01:17:26'),
(1538, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 01:35:46', '2023-07-31 01:35:46'),
(1539, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 01:43:19', '2023-07-31 01:43:19'),
(1540, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 01:44:33', '2023-07-31 01:44:33'),
(1541, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 01:45:37', '2023-07-31 01:45:37'),
(1542, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 01:45:53', '2023-07-31 01:45:53'),
(1543, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 01:45:57', '2023-07-31 01:45:57'),
(1544, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 01:47:35', '2023-07-31 01:47:35'),
(1545, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 01:47:38', '2023-07-31 01:47:38'),
(1546, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 01:48:23', '2023-07-31 01:48:23'),
(1547, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 01:59:59', '2023-07-31 01:59:59'),
(1548, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:00:04', '2023-07-31 02:00:04'),
(1549, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:00:08', '2023-07-31 02:00:08'),
(1550, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:00:18', '2023-07-31 02:00:18'),
(1551, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:04:28', '2023-07-31 02:04:28'),
(1552, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:04:32', '2023-07-31 02:04:32'),
(1553, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:04:37', '2023-07-31 02:04:37'),
(1554, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:05:30', '2023-07-31 02:05:30'),
(1555, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:05:32', '2023-07-31 02:05:32'),
(1556, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:05:38', '2023-07-31 02:05:38'),
(1557, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:06:37', '2023-07-31 02:06:37'),
(1558, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:06:43', '2023-07-31 02:06:43'),
(1559, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:06:45', '2023-07-31 02:06:45'),
(1560, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:07:45', '2023-07-31 02:07:45'),
(1561, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:11:14', '2023-07-31 02:11:14'),
(1562, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:11:24', '2023-07-31 02:11:24'),
(1563, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:11:32', '2023-07-31 02:11:32'),
(1564, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:12:18', '2023-07-31 02:12:18'),
(1565, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:12:19', '2023-07-31 02:12:19'),
(1566, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:17:58', '2023-07-31 02:17:58'),
(1567, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:18:09', '2023-07-31 02:18:09'),
(1568, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:18:22', '2023-07-31 02:18:22'),
(1569, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:19:10', '2023-07-31 02:19:10'),
(1570, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:19:37', '2023-07-31 02:19:37'),
(1571, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:19:54', '2023-07-31 02:19:54'),
(1572, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:19:58', '2023-07-31 02:19:58'),
(1573, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:20:00', '2023-07-31 02:20:00'),
(1574, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:20:04', '2023-07-31 02:20:04'),
(1575, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:20:07', '2023-07-31 02:20:07'),
(1576, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:20:17', '2023-07-31 02:20:17'),
(1577, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:20:20', '2023-07-31 02:20:20'),
(1578, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:20:31', '2023-07-31 02:20:31'),
(1579, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:20:32', '2023-07-31 02:20:32'),
(1580, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:20:40', '2023-07-31 02:20:40'),
(1581, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:20:56', '2023-07-31 02:20:56'),
(1582, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:20:59', '2023-07-31 02:20:59'),
(1583, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:22:30', '2023-07-31 02:22:30'),
(1584, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:22:33', '2023-07-31 02:22:33'),
(1585, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:22:44', '2023-07-31 02:22:44'),
(1586, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:22:48', '2023-07-31 02:22:48'),
(1587, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:24:06', '2023-07-31 02:24:06'),
(1588, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:24:11', '2023-07-31 02:24:11'),
(1589, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:24:39', '2023-07-31 02:24:39'),
(1590, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:24:52', '2023-07-31 02:24:52'),
(1591, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:25:06', '2023-07-31 02:25:06'),
(1592, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:25:07', '2023-07-31 02:25:07'),
(1593, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:25:10', '2023-07-31 02:25:10'),
(1594, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:27:17', '2023-07-31 02:27:17'),
(1595, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:27:20', '2023-07-31 02:27:20'),
(1596, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:28:06', '2023-07-31 02:28:06'),
(1597, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:28:08', '2023-07-31 02:28:08'),
(1598, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:28:11', '2023-07-31 02:28:11'),
(1599, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:28:33', '2023-07-31 02:28:33'),
(1600, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:28:34', '2023-07-31 02:28:34'),
(1601, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:28:37', '2023-07-31 02:28:37'),
(1602, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-31 02:29:36', '2023-07-31 02:29:36'),
(1603, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:34:08', '2023-07-31 02:34:08'),
(1604, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:34:13', '2023-07-31 02:34:13'),
(1605, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:34:31', '2023-07-31 02:34:31'),
(1606, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:34:32', '2023-07-31 02:34:32'),
(1607, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:34:32', '2023-07-31 02:34:32'),
(1608, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:34:39', '2023-07-31 02:34:39'),
(1609, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 02:35:38', '2023-07-31 02:35:38'),
(1610, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:35:40', '2023-07-31 02:35:40'),
(1611, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:35:44', '2023-07-31 02:35:44'),
(1612, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:36:34', '2023-07-31 02:36:34'),
(1613, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:36:39', '2023-07-31 02:36:39'),
(1614, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:36:50', '2023-07-31 02:36:50'),
(1615, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:36:54', '2023-07-31 02:36:54'),
(1616, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:38:15', '2023-07-31 02:38:15'),
(1617, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:38:18', '2023-07-31 02:38:18'),
(1618, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:38:43', '2023-07-31 02:38:43'),
(1619, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:38:46', '2023-07-31 02:38:46'),
(1620, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:39:18', '2023-07-31 02:39:18'),
(1621, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:39:21', '2023-07-31 02:39:21'),
(1622, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 02:40:10', '2023-07-31 02:40:10'),
(1623, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 02:40:14', '2023-07-31 02:40:14'),
(1624, 1, 'admin', 'GET', '127.0.0.1', '[]', '2023-07-31 02:53:11', '2023-07-31 02:53:11'),
(1625, 1, 'admin/errors', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-31 02:53:16', '2023-07-31 02:53:16'),
(1626, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-31 04:09:01', '2023-07-31 04:09:01'),
(1627, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 04:09:05', '2023-07-31 04:09:05'),
(1628, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-31 04:11:54', '2023-07-31 04:11:54'),
(1629, 1, 'admin/production_plan/16,17', 'DELETE', '127.0.0.1', '{\"_method\":\"delete\",\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 04:12:03', '2023-07-31 04:12:03'),
(1630, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-31 04:12:03', '2023-07-31 04:12:03'),
(1631, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 04:12:10', '2023-07-31 04:12:10'),
(1632, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-31 04:12:10', '2023-07-31 04:12:10'),
(1633, 1, 'admin/production_plan/import', 'POST', '127.0.0.1', '{\"_token\":\"fOGYp7lzWqNeDWnQ4iDYaD0AcSSwSTfhyZWjkCju\"}', '2023-07-31 04:13:00', '2023-07-31 04:13:00'),
(1634, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 06:55:54', '2023-07-31 06:55:54'),
(1635, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 06:55:55', '2023-07-31 06:55:55'),
(1636, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 06:57:25', '2023-07-31 06:57:25'),
(1637, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 06:58:04', '2023-07-31 06:58:04'),
(1638, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 06:58:06', '2023-07-31 06:58:06'),
(1639, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 06:58:11', '2023-07-31 06:58:11'),
(1640, 1, 'admin/check-sheets/import', 'GET', '127.0.0.1', '[]', '2023-07-31 07:15:19', '2023-07-31 07:15:19'),
(1641, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:15:20', '2023-07-31 07:15:20'),
(1642, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:15:21', '2023-07-31 07:15:21'),
(1643, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:15:29', '2023-07-31 07:15:29'),
(1644, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:17:42', '2023-07-31 07:17:42'),
(1645, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:17:50', '2023-07-31 07:17:50'),
(1646, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:19:53', '2023-07-31 07:19:53'),
(1647, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:21:40', '2023-07-31 07:21:40'),
(1648, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:21:46', '2023-07-31 07:21:46'),
(1649, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:21:47', '2023-07-31 07:21:47'),
(1650, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:21:50', '2023-07-31 07:21:50'),
(1651, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:21:53', '2023-07-31 07:21:53'),
(1652, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-31 07:22:03', '2023-07-31 07:22:03'),
(1653, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:22:12', '2023-07-31 07:22:12'),
(1654, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:26:19', '2023-07-31 07:26:19'),
(1655, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:26:24', '2023-07-31 07:26:24'),
(1656, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:26:29', '2023-07-31 07:26:29'),
(1657, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:28:43', '2023-07-31 07:28:43'),
(1658, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:28:45', '2023-07-31 07:28:45'),
(1659, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\"}', '2023-07-31 07:28:47', '2023-07-31 07:28:47'),
(1660, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\"}', '2023-07-31 07:29:28', '2023-07-31 07:29:28'),
(1661, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\"}', '2023-07-31 07:31:07', '2023-07-31 07:31:07'),
(1662, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:31:11', '2023-07-31 07:31:11'),
(1663, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:31:19', '2023-07-31 07:31:19'),
(1664, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:34:57', '2023-07-31 07:34:57'),
(1665, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:35:13', '2023-07-31 07:35:13'),
(1666, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:35:41', '2023-07-31 07:35:41'),
(1667, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:36:43', '2023-07-31 07:36:43'),
(1668, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:36:56', '2023-07-31 07:36:56'),
(1669, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:37:19', '2023-07-31 07:37:19'),
(1670, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:37:47', '2023-07-31 07:37:47'),
(1671, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:37:56', '2023-07-31 07:37:56'),
(1672, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:38:11', '2023-07-31 07:38:11'),
(1673, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:38:22', '2023-07-31 07:38:22'),
(1674, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:38:28', '2023-07-31 07:38:28'),
(1675, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:39:11', '2023-07-31 07:39:11'),
(1676, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '[]', '2023-07-31 07:41:05', '2023-07-31 07:41:05'),
(1677, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:41:07', '2023-07-31 07:41:07'),
(1678, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:41:14', '2023-07-31 07:41:14'),
(1679, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:49:05', '2023-07-31 07:49:05'),
(1680, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:49:06', '2023-07-31 07:49:06'),
(1681, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:49:09', '2023-07-31 07:49:09'),
(1682, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:49:11', '2023-07-31 07:49:11'),
(1683, 1, 'admin/check-sheets/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 07:49:35', '2023-07-31 07:49:35'),
(1684, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:49:35', '2023-07-31 07:49:35'),
(1685, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:49:38', '2023-07-31 07:49:38'),
(1686, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:49:39', '2023-07-31 07:49:39'),
(1687, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:49:42', '2023-07-31 07:49:42'),
(1688, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-31 07:49:56', '2023-07-31 07:49:56'),
(1689, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:49:58', '2023-07-31 07:49:58'),
(1690, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:50:00', '2023-07-31 07:50:00'),
(1691, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-31 07:54:05', '2023-07-31 07:54:05'),
(1692, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:54:06', '2023-07-31 07:54:06'),
(1693, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:54:09', '2023-07-31 07:54:09'),
(1694, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:54:25', '2023-07-31 07:54:25'),
(1695, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:54:27', '2023-07-31 07:54:27'),
(1696, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:54:29', '2023-07-31 07:54:29'),
(1697, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:54:31', '2023-07-31 07:54:31'),
(1698, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"1\"}', '2023-07-31 07:54:47', '2023-07-31 07:54:47'),
(1699, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"page\":\"2\",\"_pjax\":\"#pjax-container\"}', '2023-07-31 07:54:54', '2023-07-31 07:54:54'),
(1700, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:54:55', '2023-07-31 07:54:55'),
(1701, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:54:57', '2023-07-31 07:54:57'),
(1702, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-31 07:59:23', '2023-07-31 07:59:23'),
(1703, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:59:24', '2023-07-31 07:59:24'),
(1704, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:59:30', '2023-07-31 07:59:30'),
(1705, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"3\"}', '2023-07-31 07:59:32', '2023-07-31 07:59:32'),
(1706, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"2\"}', '2023-07-31 07:59:39', '2023-07-31 07:59:39'),
(1707, 1, 'admin/check-sheets', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\",\"page\":\"1\"}', '2023-07-31 07:59:41', '2023-07-31 07:59:41'),
(1708, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-31 08:55:32', '2023-07-31 08:55:32'),
(1709, 1, 'admin/production_plan', 'GET', '127.0.0.1', '[]', '2023-07-31 09:00:17', '2023-07-31 09:00:17'),
(1710, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-31 09:00:29', '2023-07-31 09:00:29'),
(1711, 1, 'admin/production_plan', 'GET', '127.0.0.1', '{\"_pjax\":\"#pjax-container\"}', '2023-07-31 09:01:42', '2023-07-31 09:01:42'),
(1712, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-31 09:05:27', '2023-07-31 09:05:27'),
(1713, 1, 'admin/products/import', 'POST', '127.0.0.1', '{\"_token\":\"9wD8yDfXeN6fVfzYzc9I1DYdVVN2EQ3cVFAfDZAW\"}', '2023-07-31 09:07:22', '2023-07-31 09:07:22'),
(1714, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
(1715, 1, 'admin/products', 'GET', '127.0.0.1', '[]', '2023-07-31 09:08:09', '2023-07-31 09:08:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `http_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_path` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_permissions`
--

INSERT INTO `admin_permissions` (`id`, `name`, `slug`, `http_method`, `http_path`, `created_at`, `updated_at`) VALUES
(1, 'All permission', '*', '', '*', NULL, NULL),
(2, 'Dashboard', 'dashboard', 'GET', '/', NULL, NULL),
(3, 'Login', 'auth.login', '', '/auth/login\r\n/auth/logout', NULL, NULL),
(4, 'User setting', 'auth.setting', 'GET,PUT', '/auth/setting', NULL, NULL),
(5, 'Auth management', 'auth.management', '', '/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs', NULL, NULL),
(6, 'In flexo', 'in-flexo', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(7, 'Thủ công', 'thu-cong', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(8, 'PQC', 'pqc', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(9, 'OQC', 'oqc', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(10, 'PCL', 'pcl', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(11, 'Lab', 'lab', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(12, 'Kho TP', 'kho-tp', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(13, 'KHSX', 'khsx', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(14, 'KHSX + Kho', 'khsx-kho', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(15, 'Cơ điện', 'co-dien', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(16, 'Kế toán', 'ke-toan', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(17, 'Ban Giám Đốc', 'ban-giam-doc', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(18, 'QLSX', 'qlsx', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(19, 'Trợ lý', 'tro-ly', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(20, 'Thư ký sx', 'thu-ky-sx', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'administrator', '2023-07-17 04:14:32', '2023-07-17 04:14:32'),
(2, 'Giấy', 'giay', '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(3, 'PCL', 'pcl', '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(4, 'Lab', 'lab', '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(5, 'Kho', 'kho', '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(6, 'KHSX', 'khsx', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(7, 'KHSX + Kho', 'khsx-kho', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(8, 'Cơ điện', 'co-dien', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(9, 'Kế toán', 'ke-toan', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(10, 'Ban Giám Đốc', 'ban-giam-doc', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(11, 'QLSX', 'qlsx', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(12, 'Trợ lý', 'tro-ly', '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(13, 'Thư ký', 'thu-ky', '2023-07-17 04:14:43', '2023-07-17 04:14:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_role_menu`
--

CREATE TABLE `admin_role_menu` (
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_role_menu`
--

INSERT INTO `admin_role_menu` (`role_id`, `menu_id`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, NULL),
(1, 2, NULL, NULL),
(1, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_role_permissions`
--

CREATE TABLE `admin_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_role_permissions`
--

INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL),
(1, 1, NULL, NULL),
(1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_role_users`
--

CREATE TABLE `admin_role_users` (
  `role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_role_users`
--

INSERT INTO `admin_role_users` (`role_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL),
(2, 2, NULL, NULL),
(2, 3, NULL, NULL),
(2, 4, NULL, NULL),
(2, 5, NULL, NULL),
(2, 6, NULL, NULL),
(2, 7, NULL, NULL),
(2, 8, NULL, NULL),
(2, 9, NULL, NULL),
(2, 10, NULL, NULL),
(2, 11, NULL, NULL),
(2, 12, NULL, NULL),
(2, 13, NULL, NULL),
(2, 14, NULL, NULL),
(2, 15, NULL, NULL),
(2, 16, NULL, NULL),
(2, 17, NULL, NULL),
(2, 18, NULL, NULL),
(2, 19, NULL, NULL),
(2, 20, NULL, NULL),
(2, 21, NULL, NULL),
(2, 22, NULL, NULL),
(2, 23, NULL, NULL),
(2, 24, NULL, NULL),
(2, 25, NULL, NULL),
(2, 26, NULL, NULL),
(2, 27, NULL, NULL),
(2, 28, NULL, NULL),
(2, 29, NULL, NULL),
(2, 30, NULL, NULL),
(2, 31, NULL, NULL),
(3, 32, NULL, NULL),
(3, 33, NULL, NULL),
(3, 34, NULL, NULL),
(3, 35, NULL, NULL),
(3, 36, NULL, NULL),
(3, 37, NULL, NULL),
(3, 38, NULL, NULL),
(3, 39, NULL, NULL),
(3, 40, NULL, NULL),
(3, 41, NULL, NULL),
(3, 42, NULL, NULL),
(3, 43, NULL, NULL),
(3, 44, NULL, NULL),
(3, 45, NULL, NULL),
(3, 46, NULL, NULL),
(3, 47, NULL, NULL),
(3, 48, NULL, NULL),
(3, 49, NULL, NULL),
(4, 50, NULL, NULL),
(4, 51, NULL, NULL),
(4, 52, NULL, NULL),
(4, 53, NULL, NULL),
(5, 54, NULL, NULL),
(5, 55, NULL, NULL),
(5, 56, NULL, NULL),
(5, 57, NULL, NULL),
(5, 58, NULL, NULL),
(5, 59, NULL, NULL),
(5, 60, NULL, NULL),
(5, 61, NULL, NULL),
(6, 62, NULL, NULL),
(7, 63, NULL, NULL),
(8, 64, NULL, NULL),
(8, 65, NULL, NULL),
(8, 66, NULL, NULL),
(8, 67, NULL, NULL),
(8, 68, NULL, NULL),
(8, 69, NULL, NULL),
(9, 70, NULL, NULL),
(9, 71, NULL, NULL),
(9, 72, NULL, NULL),
(9, 73, NULL, NULL),
(10, 74, NULL, NULL),
(11, 75, NULL, NULL),
(12, 76, NULL, NULL),
(13, 78, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `mnv` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_users`
--

INSERT INTO `admin_users` (`id`, `mnv`, `username`, `password`, `name`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', '$2y$10$Ex0nWhZmgZMReY3h58vhSeqWUZDmCa/f80MZy/zz.cdNgcNL91Rvi', 'Administrator', NULL, 'aDZ5ovfO4kx28rS03Dkz10Vrqgs2wfjbhLsJ2WKkWygpvCgoyipu0QoGxLtP', '2023-07-17 04:14:32', '2023-07-17 04:14:32'),
(2, 'V2005120718', 'tvhai', '$2y$10$rBsdLfzSio4qp1r.FsZOaumPRJxcnYh65n3cuB29.m9kBgsBRpzIu', 'Trần Văn Hải', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(3, 'V20200904780', 'vvngan', '$2y$10$u9v16FbAgmKMDTlJ8/l6eeXcuAaycI7WpOHt9fXPKLEbZcXkvNCae', 'Vi Văn Ngân', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(4, 'V20210115935', 'ptson', '$2y$10$YkgR1PiebUR6zhiI4Bb/FuEVOogjW8YgcMxaAO7S.1wl1mf01InhK', 'Phạm Trọng Sơn', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(5, 'V20180515443', 'tmthuan', '$2y$10$aed9C4Ok/XagwTGFI71iAu.M0OZsvQzayzLuIG1SZJuz3BPOyAXtm', 'Tạ Minh Thuận', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(6, 'V20220413444', 'dchoan', '$2y$10$t05XmBOutN94.eaF.yuyUeGi9BEXh0A2RVH7iOGfvkSB309XZa/Eq', 'Đàm Công Hoan', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(7, 'V20221212870', 'ltlong', '$2y$10$GFpcdaCvs7sdqum4HO9UhOgLy6XbsToOgRpX3Ihkga7b3bYiB1xo2', 'Lê Thành Long', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(8, 'V2002010104', 'nvquang', '$2y$10$NBuxxP7k2bpBN5aTGmK2/eDNIdAj75L2ISwkA9RD3K6azHXfcAFXO', 'Nguyễn Văn Quang', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(9, 'V2016030111342', 'dvhoa', '$2y$10$vf1AhveVp5Tb2ZTTGbiK3.qi6YESlcH.hRk1w/BnTD4vmsIUGezou', 'Đinh Văn Hòa', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(10, 'V20180919206', 'nddang', '$2y$10$nQvMWNRBvtCV0jHu8xUegeK2i7kY1uBt7hhpdFjR.wJR39m2G/WnS', 'Nguyễn Đình Đặng', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(11, 'V20211026096', 'dqhuy', '$2y$10$x4XisA/UjqWYBgFisqxNmuEdXzpBsqHJ.V81mLjauTONBvuRXXV82', 'Đặng Quang Huy', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(12, 'V20180919206', 'lvtoan', '$2y$10$6f5XM2PjYqpKVnIH15mZ5.6xyw8BIH2592XZXY0ETVtBTMx/r8LCS', 'Lê Văn Toàn', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(13, 'V20190223458', 'lvdinh', '$2y$10$MKJUFEVghvDnQphj0a9IXu.ncwSYQ4/f.JNNhs0gKatcmjd47BS/q', 'Lường Văn Định', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(14, ' V20200219114 ', 'pdvan', '$2y$10$KG8j.Uz0guXUZJ5T8BArDuB19pS13GxmxuWO8rWLfPMc80eo/jZW2', 'Phạm Đình Văn', NULL, NULL, '2023-07-17 04:14:39', '2023-07-17 04:14:39'),
(15, 'V20180808819', 'dnthiet', '$2y$10$81wYE9yRsmr4BOWzCjkWM.aWWQQIXLgiaPHrJ6HaaGq56sv1efd/u', 'Đặng Như Thiết', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(16, 'V20181115184', 'nctrong', '$2y$10$WW6dz8yrSRXhb.011zZyt.4kDGWFnK3H1VyvUSSW4zZK.eKXKHsV.', 'Nguyễn Công Trọng', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(17, 'V20190717918', 'mcdinh', '$2y$10$Len3eFSrNnZvmNRlp15zw.loKq0rophzPKuwf2LoI081CT1hcbNom', 'Ma Công Đỉnh', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(18, 'V20220212205', 'nvthai', '$2y$10$17DPnbXaGUaIyNROHUy6jezqojQ/tsixPyxAZxhSiLm1ubsb2MHd.', 'Nguyễn Văn Thái', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(19, 'V20180223005', 'lvthanh', '$2y$10$IuJulEZD4/O1TBhI97E9Q.elmm8y9EDEIecA0ec2rCuhnG.UPyYbm', 'Lù Văn Thảnh', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(20, 'V20181019094', 'dnkhoa', '$2y$10$dE/hgXB0M2SIdHxJC66Bbe55pGP71bqHOKRlItxrncEs.xXIo2ebW', 'Đặng Như Khoa', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(21, 'V20190716908', 'ntvu', '$2y$10$9W1cLgNRK10ebv9pqfiiKuM0BvL3ZJBAK0ntnxNbLvr/MZ1HBz6Ca', 'Nguyễn Tuấn Vũ', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(22, 'V20180302067', 'lvdung', '$2y$10$30Uzu/meMKZwp0BNSp6X4ecWU0F7rx9R7aCEwlAUPGBwPK9Xh7hSu', 'Lù Văn Dũng', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(23, 'V20190212366', 'lvban', '$2y$10$cTSSYXcQ5aix3J1qwU2fMOb4I8oaxW//TxGvf6kdc2xQnzjRGIei.', 'Lò Văn Băn', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(24, 'V20210529046', 'lvtrieu', '$2y$10$eicd8aMeEy1l8OmIBea5h.maJN0ej.jKKKg7UB.WerNWZ0UaScJXC', 'Lò Văn Triều', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(25, 'V20180803771', 'lvkim', '$2y$10$nSSReceSI3uER.DzlP13ZeUmh4cXhW44aKaEEmEuDpsdVL72K9wIe', 'Lý Văn Kim', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(26, 'V2007060127', 'ntnhi', '$2y$10$OF22DSu/OyQmd6OYowNGfOFWfNQGvwIUbAMmi2aYu6/53UV1oAKC.', 'Nguyễn Thị Nhị', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(27, 'V20180319225', 'ntdao', '$2y$10$HHZ/glJh9tyd3h0F2qqMwOUoIF77IOKnZj/eFHTTK1l3mM7CxTisy', 'Nguyễn Thị Đào', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(28, 'V20181117190', 'ntninh', '$2y$10$C32R.WU1IgAurOtQp8hbU.K.1f/FmwJTo5mr4PXXulb4qGznmmlSy', 'Nguyễn Thị Ninh Phương', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(29, 'V20230221030', 'qthoai', '$2y$10$jLUYdGJ5yLkcTByMVON8p.NYNQskAB1.PwyBXEGg5O3QQVGHNuWB6', 'Quàng Thị Hoài', NULL, NULL, '2023-07-17 04:14:40', '2023-07-17 04:14:40'),
(30, 'V20200818689', 'lvhiep', '$2y$10$A0S041BZvbl2PnXHHaKPd.NIQHZYsY9WUvx0inBNd9W1N/A3Dglri', 'Liểu Văn Hiệp', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(31, 'V20200715580', 'tvkhoa', '$2y$10$V59rTlG7tBQq8Pabi9kYxOTKuMUEx1Yxs8bOIxsL8q8T8sQPfE5pm', 'Triệu Văn Khóa', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(32, 'V2016090611921', 'hdtrong', '$2y$10$5b7XJsUxcesR7NbrH2eoeeXjoGoq/mKpMIQL8Mv.RuDTH277ZHr..', 'Hoàng Đình Trọng', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(33, 'V20190113353', 'nvthang', '$2y$10$zjuw6IRjw/fGDmYdBovv1.7CbwCsUIl2RZino5ZHgQ3VmNPA1Kp8m', 'Nguyễn Văn Thắng', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(34, 'V20180730747', 'cvkien', '$2y$10$g8zAO2bOv8kphgdcOwBK8ONneRmFeDa394GJOlLa1KrkzVaZFkMKi', 'Cà Văn Kiển', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(35, 'V201511161107', 'tvtuan', '$2y$10$jCOlQ8owLbrwXOhczKIvzuB2oDeocKGNW2OBb1880Zt1WUQB3oKby', 'Tăng Văn Tuấn', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(36, 'V20180527457', 'nvbac', '$2y$10$JjwsOQS8eL2RCeO/.RgtR.W7cMnOQXrTNDQg/Y3kArVTEfMwjxxBG', 'Nguyễn Văn Bắc', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(37, 'V20190213378', 'lvsang', '$2y$10$pC0chl/xXHPKCGa2WvVGD.62XvIj1pY04HFB7K5TPOxrHyfW0K1Te', 'Lò Văn Sang', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(38, 'V20220214218', 'nvson', '$2y$10$HXgBJSINoATKkhM1WJGopOrh72WSFPMD4R53yHkwThhWcz4.V/Y6i', 'Nguyễn Văn Sơn', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(39, 'V20170222168', 'ttbe', '$2y$10$bcPSbliOAVe8pcQIg8qPae93kFmAiI/YO.0SIGi0LyhQryQXPEun6', 'Tạ Thị Bé', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(40, 'V20180503414', 'ltben', '$2y$10$RZENWdzu1dcg.m/A36YOyO5TGENzwUg8RtOFCMB9h4tdWjj4JZoV6', 'Lương Thị Bền', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(41, 'V20180524455', 'ltha', '$2y$10$Vek63xwH2SLmgPXU9hh1bOs86OwSWvOopC0lZknMZkDlhe2vZpHCO', 'Lường Thị Hà', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(42, 'V201710201047', 'nvtien', '$2y$10$MxoDG1Nu1OMnADBkE5Shyu3NJWE4RbwiDxB./2orH6q0H9Q2GqQN2', 'Ngô Văn Tiến', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(43, 'V201711011080', 'nvhoang', '$2y$10$tvF2O0AO0LDhifYHYkx0UuAWk6tTaAmSLcjfkCI0S..Qfb25uiGhi', 'Ngô Văn Hoàng', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(44, 'V2016100412049', 'lvhung', '$2y$10$UGFygMvAyT0X5VVeO4qiIOWdfP/qVn5XT39ITQNyIWYVTvTq9OcnS', 'Lò Văn Hưng', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(45, 'V20220214217', 'ntquyet', '$2y$10$5RJuH7D7EbDnoIdXiIp.4.4knXDlfKGEFpgkgmpKVM.KIT29YfgjK', 'Nguyễn Tiến Quyết', NULL, NULL, '2023-07-17 04:14:41', '2023-07-17 04:14:41'),
(46, 'V2009051154', 'ttvinh', '$2y$10$sFDnzFQvjYfFoxcGDjXOuO6Wy2aBe78TPcPir/TgQK0GXD.FoGlg2', 'Trần Thị Vinh', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(47, 'V2005080107', 'nttho', '$2y$10$Nil4GoAEKpm10G4177DwXegr7DxjgegYYOJQb4AeUwilbpNgLbTE6', 'Nguyễn Thị Thơ', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(48, 'V20170509509', 'btoanh', '$2y$10$aCi43egn2OtjyzuDGWUNp.xUyUG/v9ttPoHBzezQFCW.1/nqOXPMC', 'Bùi Thị Oanh', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(49, 'V20180315209', 'bcdat', '$2y$10$mlUZj/Ga.Y6ZVbDTg8Q2eehDoSPZWyzjh8vcgc9fgaQZNhJXw/1NS', 'Bạc Cầm Đạt', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(50, 'V20170925903', 'lvdien', '$2y$10$6CCfFA0n68jml9RkVupKYOFr46SsSM5qWSIfqoxgot/R/3.O1MCsq', 'Lò Văn Diện', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(51, 'V20190405870\n', 'nttrang', '$2y$10$CpyRQiWAVi9ZCZAKWIragOBUJXE7k1v3MMk6K7.1vmjW6y8gTWbAS', 'Nguyễn Thị Trang', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(52, 'V20220808674', 'nltruong', '$2y$10$VduZzTxdIc4paGQj4FGH1eo15kVXOvrSu/ZINC64.JyjlXbdHYTwy', 'Nguyễn Lam Trường', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(53, 'V20200930832', 'nvluong', '$2y$10$U3pblhjIjwUszqhtkwd72uF.gn5o22TCD8sCHXNlKkGXX00QQVBAm', 'Nguyễn Văn Lượng', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(54, 'V20150426834', 'ktha', '$2y$10$nBbqBWpX6mxfivivQWyuVOZb3ntXmTEDgl707anyEbQz.qwecweoG', 'Khương Thị Hà', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(55, 'V2016070511566', 'dthien', '$2y$10$xAR8RvDaQB8a2gYXIW9B7.QdwZhQvOe2rfQGF3nS4nwfTWc3aM4KK', 'Đinh Thị Hiền', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(56, 'V20210225954', 'lvtrong', '$2y$10$u8SIoFfAJuVyu3fj/UoiF.TcvRAXAVmB8aKJpkRgaKy5lvbqqh.2G', 'Lò Văn Trọng', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(57, 'V2016060111482', 'lttam', '$2y$10$YPMEU3wsWQc8./r52L8.QekePI0ldnC2VuB0oxcb86xz8/Ev8usva', 'Lương Thị Tâm', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(58, 'V20161026120133', 'ntcong', '$2y$10$AJa7g/p8urIq9.LfV/F2MOyUDVNxwRj6Hjbpb130k8FwhmotO2IxG', 'Nguyễn Tiến Công', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(59, 'V20180505416', 'ntthuy', '$2y$10$iIXAqk.94wNBDWiHrEhUeu9RFGdxiE0wox2wAJIzxnrX.jRoS8BpO', 'Nguyễn Thị Thủy', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(60, 'V20190212370', 'bvtoan', '$2y$10$OQYBB8WpvqGZrrGMuJxSB.KCVT.xHBLoOS1iJZceL96N.SdDOheK2', 'Bùi Văn Toản', NULL, NULL, '2023-07-17 04:14:42', '2023-07-17 04:14:42'),
(61, 'V20220226111', 'ndtien', '$2y$10$PZDipspVdantu9yuZKGpoOchkBJTT.i9TTDOVvVJyAs.kdda9tQci', 'Nguyễn Đắc Tiến', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(62, 'V2009040950', 'dtthuy', '$2y$10$kxwwUKB1hufKVtvpa8Uhbu4gtGG8pOufmCMw2rDk01BtIWVXyfKmq', 'Đinh Thị Thủy', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(63, 'V2009042953', 'ntnet', '$2y$10$S1kJoLBpF3TgDIK0YhrfIuvjuMoJ32Oi3bU/vm7U08sC5JaHRuYKO', 'Ngô Thị Nết', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(64, 'V2009052755', 'nmkhoi', '$2y$10$nF56VQD3eTDA3uFLsCjHpOYpin9nPWxcnHrtquX7jiiSt5ZeKVdja', 'Nguyễn Mạnh Khởi', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(65, 'V20211126121', 'gmtien', '$2y$10$ps2GxW6K/5XgImjkLp1/qOmMUGMwWFi5O896soZyvNwl.K1A6t8tW', 'Giáp Mạnh Tiến', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(66, 'V20111101105', 'bxthuy', '$2y$10$y7d.GhwoElEnjhVx6CLDmugtqC.kZvHh7V2wQ18oXX.lofP9fLWj6', 'Bùi Xuân Thùy', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(67, 'V20141105631', 'nvhoc', '$2y$10$BzGB6z8..GR73Fz71LYHZ.WLUflZmTQ4A7fAaDOBrxhy91ywhu7fC', 'Nguyễn Văn Học', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(68, 'V20170703655', 'pdchinh', '$2y$10$vm.FH4DrbbDrj1OeGAKcWuPlUKj92osWZ7qwMUzPv/.YOdC7JHyGW', 'Phạm Đình Chinh', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(69, NULL, 'gvdo', '$2y$10$1Gagfx2zzHV8TqoPodk9euYJYqj2WBw/QpsThujaSv1dQHPDICTwa', 'Giáp Văn Đô', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(70, 'V2010042969', 'nttien', '$2y$10$8vPIpFPzvRd1XQvg.PywG.k3jYTeZZ2g77vxy5Ztc9kkSgAPNKYcW', ' Ngô Thị Tiên', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(71, 'V2011063096', 'ntnga', '$2y$10$ciiCXl1Zt/dgZde27LMtYOnqj5R8etT.kAp5T6jPE32GM3a29DjFO', ' Nguyễn Thị Nga ', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(72, 'V20170728738', 'nthoa', '$2y$10$ibDXSBBgpaFMrIlVBUMGxe29BujOlK/kyRsRq1YWe3MSLZM0bUpKe', ' Nguyễn Thị Hoa', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(73, 'V20200616490', 'ttlai', '$2y$10$dMoQOB1USohk6HoFqBs4euE45ALo4eAJjBw9SfFlaZgOrI6kSw6kW', ' Trần Thị Lài', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(74, 'V20221121857', 'nqha', '$2y$10$qdvOR5YtowwodIp6MhuTI.3WLRK8614JFLggrDpHEUZPMZGDem9JW', 'Nguyễn Quảng Hà', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(75, 'V2007091931', 'gvlam', '$2y$10$SKTgGAJkvym3hNZqG0QvgeJ9jhFmZcihrXTrlLoJhw6x1vroli2TO', 'Giáp Văn Lâm', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(76, 'V20150914973', 'btngoc', '$2y$10$eJ6AN9472wLujPImAKCI/OAN0SkeDfQc.l5Y4AfBd4QUFrb0lXpie', 'Bùi Tuấn Ngọc', NULL, NULL, '2023-07-17 04:14:43', '2023-07-17 04:14:43'),
(78, NULL, 'dttlua', '$2y$10$nufGNZAnukWPAO73CymuQeqN50sqKsGkVdD4HC0DvdKNsK6m67ToO', 'Đinh Thị Thuý Lụa', NULL, NULL, '2023-07-17 04:17:14', '2023-07-17 04:17:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_user_permissions`
--

CREATE TABLE `admin_user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_user_permissions`
--

INSERT INTO `admin_user_permissions` (`user_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(2, 6, NULL, NULL),
(3, 6, NULL, NULL),
(4, 6, NULL, NULL),
(5, 6, NULL, NULL),
(6, 6, NULL, NULL),
(7, 6, NULL, NULL),
(8, 6, NULL, NULL),
(9, 6, NULL, NULL),
(10, 6, NULL, NULL),
(11, 6, NULL, NULL),
(12, 6, NULL, NULL),
(13, 6, NULL, NULL),
(14, 6, NULL, NULL),
(15, 7, NULL, NULL),
(16, 7, NULL, NULL),
(17, 7, NULL, NULL),
(18, 7, NULL, NULL),
(19, 7, NULL, NULL),
(20, 7, NULL, NULL),
(21, 7, NULL, NULL),
(22, 7, NULL, NULL),
(23, 7, NULL, NULL),
(24, 7, NULL, NULL),
(25, 7, NULL, NULL),
(26, 7, NULL, NULL),
(27, 7, NULL, NULL),
(28, 7, NULL, NULL),
(29, 7, NULL, NULL),
(30, 7, NULL, NULL),
(31, 7, NULL, NULL),
(32, 8, NULL, NULL),
(33, 8, NULL, NULL),
(34, 8, NULL, NULL),
(35, 8, NULL, NULL),
(36, 8, NULL, NULL),
(37, 8, NULL, NULL),
(38, 8, NULL, NULL),
(39, 8, NULL, NULL),
(40, 8, NULL, NULL),
(41, 8, NULL, NULL),
(42, 9, NULL, NULL),
(43, 9, NULL, NULL),
(44, 9, NULL, NULL),
(45, 9, NULL, NULL),
(46, 10, NULL, NULL),
(47, 10, NULL, NULL),
(48, 10, NULL, NULL),
(49, 10, NULL, NULL),
(50, 11, NULL, NULL),
(51, 11, NULL, NULL),
(52, 11, NULL, NULL),
(53, 11, NULL, NULL),
(54, 12, NULL, NULL),
(55, 12, NULL, NULL),
(56, 12, NULL, NULL),
(57, 12, NULL, NULL),
(58, 12, NULL, NULL),
(59, 12, NULL, NULL),
(60, 12, NULL, NULL),
(61, 12, NULL, NULL),
(62, 13, NULL, NULL),
(63, 14, NULL, NULL),
(64, 15, NULL, NULL),
(65, 15, NULL, NULL),
(66, 15, NULL, NULL),
(67, 15, NULL, NULL),
(68, 15, NULL, NULL),
(69, 15, NULL, NULL),
(70, 16, NULL, NULL),
(71, 16, NULL, NULL),
(72, 16, NULL, NULL),
(73, 16, NULL, NULL),
(74, 17, NULL, NULL),
(75, 18, NULL, NULL),
(76, 19, NULL, NULL),
(78, 20, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cells`
--

CREATE TABLE `cells` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `col` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `note` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sheft_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cell_product`
--

CREATE TABLE `cell_product` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cell_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` double NOT NULL DEFAULT 0,
  `customer_id` int(11) NOT NULL,
  `inventory_volume` double NOT NULL,
  `losx_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inventory_date` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `num_of_bin` int(11) NOT NULL,
  `volume_of_bin` double NOT NULL,
  `info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `check_sheet`
--

CREATE TABLE `check_sheet` (
  `id` int(11) NOT NULL,
  `line_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `hang_muc` varchar(300) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `check_sheet`
--

INSERT INTO `check_sheet` (`id`, `line_id`, `type`, `hang_muc`, `active`, `created_at`, `updated_at`) VALUES
(1, 10, 0, 'Kiểm tra nhiệt độ', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(2, 10, 0, 'Kiểm tra xung quanh máy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(3, 10, 0, 'Kiểm tra bàn vào giấy, ra giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(4, 10, 0, 'Kiểm tra bề mặt và vòng bi băng tải', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(5, 10, 0, 'Kiểm tra ống kẽm', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(6, 10, 0, 'Kiểm tra Film lót ống kẽm', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(7, 10, 0, 'Kiểm tra ống cao su', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(8, 10, 0, 'Kiểm tra đồ nghề', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(9, 10, 0, 'Kiểm tra ống chuyền', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(10, 10, 0, 'Kiểm tra bề mặt lô nước', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(11, 10, 0, ' Kiểm tra mực nhớt chính', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(12, 10, 0, 'Kiểm tra trạng thái nước bồn lạnh', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(13, 10, 0, ' Kiểm tra máy nén xả nước, mức nhớt', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(14, 10, 0, 'Kiểm tra phun bột', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(15, 10, 0, 'Kiểm tra các âm thanh bất thường', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(16, 10, 0, 'Kiểm tra đồng hồ bơm hút, bơm thổi, bơm nhớt', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(17, 10, 0, 'Kiểm tra bàn phụ', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(18, 10, 0, 'Kiểm tra vòng bi nhíp', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(19, 10, 0, 'Kiểm tra các ống dẫn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(20, 10, 0, 'Kiểm tra thanh cản vật lạ', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(21, 10, 0, 'Kiểm tra lá thép đè giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(22, 10, 0, 'Kiểm tra lọc bơm', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(23, 10, 0, 'Kiểm tra mức mỡ bò', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(24, 10, 0, 'Kiểm tra bơm mỡ bò các vị trí', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(25, 12, 0, 'Kiểm tra 5s xung quanh máy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(26, 12, 0, 'Kiểm tra đầu ra giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(27, 12, 0, 'Kiểm tra đầu vào giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(28, 12, 0, 'Kiểm tra bàn bế', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(29, 12, 0, 'Giám sát an toàn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(30, 12, 0, 'Kiểm tra các thiết bị an toàn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(31, 12, 0, 'Kiểm tra áp lực bơm nhớt', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(32, 12, 0, 'Kiểm tra áp lực bế', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(33, 12, 0, 'Kiểm tra bề mặt lô dẫn, con lăn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(34, 12, 0, 'Kiểm tra mực nhớt, dầu chính', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(35, 11, 0, 'Vệ sinh 5s toàn khu vực máy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(36, 11, 0, 'Kiểm tra tình trạng dây điện (rò, đứt, hở…)', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(37, 11, 0, 'Kiểm tra hệ thống quạt gió, quạt làm mát tủ điện, động cơ', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(38, 11, 0, 'Bổ sung bơm dầu , tra mỡ các bộ phận.', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(39, 11, 0, 'Kiểm tra đầu vào giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(40, 11, 0, 'Kiểm tra bơm keo, áp xuất bơm', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(41, 11, 0, 'Kiểm tra vệ sinh lô cao su, lô sắt', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(42, 11, 0, 'Kiểm tra hệ thống băng tải', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(43, 13, 0, 'Kiểm tra nhiệt độ ', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(44, 13, 0, 'Kiểm tra xung quanh máy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(45, 13, 0, 'Kiểm tra bàn vào giấy, ra giấy', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(46, 13, 0, 'Kiểm tra bề mặt và vòng bi băng tải', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(47, 13, 0, 'Kiểm tra ống cao su', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(48, 13, 0, 'Kiểm tra đồ nghề', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(49, 13, 0, 'Kiểm tra bề mặt lô', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(50, 13, 0, 'Kiểm tra các âm thanh bất thường', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(51, 13, 0, 'Kiểm tra đồng hồ áp suất khí nén', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(52, 13, 0, 'Kiểm tra các ống dẫn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(53, 13, 0, 'Kiểm tra lô cao su, lô tỳ, lô con lăn', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(54, 13, 0, 'Kiểm tra bơm mỡ bò các vị trí', 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `check_sheet_works`
--

CREATE TABLE `check_sheet_works` (
  `id` int(11) NOT NULL,
  `cong_viec` varchar(300) NOT NULL,
  `check_sheet_id` int(11) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `type` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `check_sheet_works`
--

INSERT INTO `check_sheet_works` (`id`, `cong_viec`, `check_sheet_id`, `active`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Kiểm tra nhiệt độ môi trườngKiểm tra nhiệt độ môi trường', 1, 1, 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(2, 'Kiểm tra nhiệt độ nước', 1, 1, 1, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(3, 'Đảm bảo an toàn thiết bị, vệ sinh 5S, gọn gàng ngăn lắp', 2, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(4, 'Không có vật lạ, thao tác vận hành bình thường', 3, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(5, 'Đảm bảo bề mặt sạch, vòng bi không rơ lắc', 4, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(6, 'Đảm bảo không rò rỉ,', 5, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(7, 'Đảm bảo đúng vị trí không bị xô lệch', 6, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(8, 'Đảm bảo ống cao su không rò rỉ', 7, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(9, 'Sẵn sàng đủ đồ nghề tại vị trí sản xuất', 8, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(10, 'Đảm bảo ở trạng thái an toàn', 9, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(11, 'Đảm bảo sạch và không tì vết', 10, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(12, 'Đảm bảo trong giới hạn cho phép', 11, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(13, 'Đảm bảo không có bất thường', 12, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(14, 'Xả hết nước đọng, mực nhớ ở mức tiêu chuẩn', 13, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(15, 'Đảm bảo không phát sinh bất thường', 14, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(16, 'Nghe phát hiện âm thanh lạ để xử lý kịp thời', 15, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(17, 'Đảm bảo trạng thái hoạt động ở mức bình thường', 16, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(18, 'Đảm bảo bàn phụ sạch sẽ', 17, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(19, 'Bơm mỡ định kỳ', 18, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(20, 'Ống dẫn hơi, khí, nhớp, lọc và đảm đảm không rò rỉ', 19, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(21, 'Đảm bảo ở trạng thái an toàn', 20, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(22, 'Đảm bảo ở trạng thái an toàn, không có bất thường', 21, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(23, 'Đảm bảo không tắc nghẽn', 22, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(24, 'Mức mỡ bò ở trạng thái tiêu chuẩn', 23, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(25, 'Đảm bảo bơm đủ định lượng cho phép', 24, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(26, 'Vệ sinh 5s khu vực máy sản xuất', 25, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(27, 'Kiểm tra kệ hàng đầu ra', 26, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(28, 'Kiểm tra áp suất hút, kệ đầu vào', 27, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(29, 'Vệ sinh bàn bế, mặt bế', 28, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(30, 'Kiểm tra tiếng kêu bất thường máy', 29, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(31, 'Kiểm tra cửa, cover an toàn', 30, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(32, 'Kiểm tra áp lực dầu', 31, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(33, 'Đưa áp lực về 0', 32, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(34, 'Vệ sinh lô, con lăn', 33, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(35, 'Kiểm tra mức dâu, bổ sung', 34, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(36, 'Vệ sinh 5s khu vực máy sản xuất', 35, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(37, 'Kiểm tra nguồn điện, dây điện', 36, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(38, 'Kiểm tra hệ thống quạt thông gió tủ điện, máy', 37, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(39, 'Vệ sinh lô, con lăn', 38, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(40, 'Vệ sinh xịt bụi đầu vào giấy', 39, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(41, 'Tra dầu bơm mỡ vòng bi', 38, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(42, 'Tra dầu bơm mỡ nhông xích , bánh răng', 38, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(43, 'Kiểm tra thùng keo, ống dẫn, áp suất bơm', 40, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(44, 'Vệ sinh lô cao su, lô sắt', 41, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(45, 'Kiểm tra băng tải, con lăn', 42, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(46, 'Kiểm tra nhiệt độ thùng keo', 43, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(47, 'Kiểm tra nhiệt độ súng keo', 43, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(48, 'Đảm bảo an toàn thiết bị, vệ sinh 5S, gọn gàng ngăn lắp', 44, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(49, 'Không có vật lạ, thao tác vận hành bình thường', 45, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(50, 'Đảm bảo bề mặt sạch, vòng bi không rơ lắc', 46, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(51, 'Đảm bảo ống cao su không rò rỉ', 47, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(52, 'Sẵn sàng đủ đồ nghề tại vị trí sản xuất', 48, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(53, 'Đảm bảo sạch và không tì vết', 49, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(54, 'Nghe phát hiện âm thanh lạ để xử lý kịp thời', 50, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(55, 'Đảm bảo trạng thái hoạt động ở mức bình thường', 51, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(56, 'Ống dẫn hơi, khí, nhớp, lọc và đảm đảm không rò rỉ', 52, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(57, 'Đảm bảo không bị xước, mòn, rơ lắc', 53, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35'),
(58, 'Đảm bảo bơm đủ định lượng cho phép', 54, 1, 0, '2023-07-31 14:49:35', '2023-07-31 14:49:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `colors`
--

CREATE TABLE `colors` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `colors`
--

INSERT INTO `colors` (`id`, `name`, `created_at`, `updated_at`) VALUES
('066eb3b4-a9c5-40e6-8eae-b0eaf4b3a224', 'P284C', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('0794ee95-e9f0-4e6b-b78d-3cedca8f6ba8', 'WHITE', '2022-11-01 19:43:38', '2022-11-01 19:43:38'),
('08c634c4-0710-452c-bfdb-8391b4a49b26', 'P7743C', '2022-10-26 08:51:35', '2022-10-26 08:51:35'),
('0987d69c-a78c-49ae-8a1d-ff1094a3feed', 'LOGO', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('0a38fd54-d237-41bd-bdb1-17b59a0ea765', 'Đỏ Chữ', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('0b8d681d-a283-48bf-87a5-9b58e9f5ba6a', 'P2983C', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('0b90132f-8149-4e74-ae8a-55d8fabbc930', 'Hồng', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('10af35ec-557b-4e10-898f-078f367aa8f9', 'Đỏ đen', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('1187f5a9-4fb8-4487-9984-d7ec63bc4053', 'P485', '2022-10-26 08:51:37', '2022-10-26 08:51:37'),
('11d608b2-319a-40aa-82e5-3ba1de7e81e2', 'Xanh Tím', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('12fa4341-dd28-40f2-8e34-3ad5f30adf6e', 'CYAN', '2022-11-01 19:43:38', '2022-11-01 19:43:38'),
('18ae05ec-0d0d-412f-94fe-fc3d034f29bc', 'Đỏ Pha', '2022-10-26 08:51:35', '2022-10-26 08:51:35'),
('194d5f55-3ce6-4b4d-9560-030900dde835', 'Nền Red', '2022-10-26 08:51:36', '2022-10-26 08:51:36'),
('1a2a0ab6-9f7c-460a-903f-3ec17d316b4e', 'Nâu\nP476C', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('1c23130f-fc60-4f41-8f51-22810bacee5b', 'Cam Nền', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('1cea56d3-85c0-4ceb-a22b-a816cfb79cc6', 'xám nền', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('1eed3b8c-ed4c-4b9a-994f-6a2d162fbd6f', 'Trắng', '2022-11-02 03:37:57', '2022-11-02 03:37:57'),
('1f975925-5fd4-42ef-856b-b4333b51b5d1', 'Xanh P2175C', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('23984a4e-ece1-4072-9845-3b6bade3ede1', 'NÂU PHA', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('2857314e-57cc-4706-ab3d-59d97709671d', 'Whtie w962', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('286338cd-01ee-43d3-bffb-282783121ab1', 'xanh lá nhạt', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('2929cf01-dfc2-42cf-93b4-0f324ede5f33', 'NÂU BÁNH', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('2c3561f2-c35f-4a78-94b7-514265f302dc', 'xanh đậm', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('2ce3bdb2-6e50-4e46-8b2d-341ac70a2c1d', 'P2353C', '2022-10-26 08:51:34', '2022-10-26 08:51:34'),
('2dd3198d-d4dd-425b-b913-54278e32fdd2', 'P121C', '2022-10-26 08:51:44', '2022-10-26 08:51:44'),
('2eabfc04-2273-4330-abfc-767cc126a177', 'Xanh Tím Đậm', '2022-10-26 08:51:33', '2022-10-26 08:51:33'),
('2ef45d9e-396f-4fe5-9eff-b10608642362', 'ĐỎ LOGO', '2022-11-01 19:43:38', '2022-11-01 19:43:38'),
('2ef6d4c2-2224-4363-b450-81b3429efe00', 'tím đậm', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('31def3c9-d0f5-49cf-b65c-dda7bfb011b4', 'Nâu Warm', '2022-10-26 08:51:41', '2022-10-26 08:51:41'),
('3262a2e7-ea6e-498d-9106-e3e535611c28', 'Nền Nâu', '2022-10-26 08:51:27', '2022-10-26 08:51:27'),
('34d6fe77-39d9-4d27-b209-12bfec52587a', 'Đỏ cam', '2022-10-26 08:51:35', '2022-10-26 08:51:35'),
('3521c180-6ed8-47ad-926a-c5c11c015b38', 'Nền Hồng', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('38665e9b-e849-4d19-9106-0ce9c2b7df3d', 'Vàng Fa', '2022-10-26 08:51:36', '2022-10-26 08:51:36'),
('390dbb0a-080c-4bb0-acfa-dac7f4f7592d', 'P123C', '2022-10-26 08:51:36', '2022-10-26 08:51:36'),
('3b00dd2a-798b-47ec-8889-1179e8735a09', 'Cyan 800', '2022-11-01 19:42:03', '2022-11-01 19:42:03'),
('3be0ff08-090a-4a4a-a464-66cfe481b390', 'Red\np185c', '2022-10-26 08:51:37', '2022-10-26 08:51:37'),
('3dce55a7-3a8a-4c7a-99c9-9712be90cdc2', 'P7677C', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('3e338805-ecfd-438e-9915-6809f239cc02', 'Xanh Ngọc', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('4000888a-d778-4626-b3d8-e70f962dca6b', 'nền vàng', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('40b57080-c687-4947-9ff3-d6d13d262186', 'P576C', '2022-10-26 08:51:34', '2022-10-26 08:51:34'),
('4128d4d5-fd61-4fde-8b61-7ef7f89a58bf', 'xanh lá P361C', '2022-10-26 08:51:49', '2022-10-26 08:51:49'),
('415a3fc3-1a7d-4506-b0e6-381711838844', 'P326C', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('42f0d9de-d0fd-47b1-a4d9-362ffc866a4c', 'Red', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('445af769-4102-4c48-b4cc-691f99667447', 'Xám Chữ', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('44c614fd-7dd2-4e72-89c4-ebfea516fd96', 'P3262C', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('48fdacd0-482f-41b4-941d-e33bbabfa43b', 'P198C', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('4ca1bef4-6e34-44b0-a885-4098937f64c1', 'Giả Nhũ', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('4ce8db9a-d8d3-419a-b76f-d034f4bb54e1', 'P130C', '2022-10-26 08:51:38', '2022-10-26 08:51:38'),
('4d4f1226-ab40-4712-b673-57cc32012fca', 'đỏ da người', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('4e5d07fe-7c5e-487f-aec2-814076fa034c', 'Xanh lá nhạt\nP375C', '2022-10-26 08:51:38', '2022-10-26 08:51:38'),
('5eb4f9a3-48d6-49d1-8aa3-78ffc72f3f10', 'xanh lá đen', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('6095a7f7-2470-4018-a24b-6eb76be2c7ea', 'đỏ cờ', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('60daa0e3-68a9-4e49-ad77-f7dc141e4e17', 'P3025C', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('6549676d-1e73-4b68-92b8-61d243dc0074', 'Nâu P7617', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('6780781f-d9ce-4e63-9826-cc489eccfd63', 'P300C', '2022-10-26 08:51:43', '2022-10-26 08:51:43'),
('68a47016-f32f-420c-ab6d-dc18dc16fd6c', 'Xanh lá', '2022-10-26 08:51:27', '2022-10-26 08:51:27'),
('69d435da-03c2-4dca-921b-f2dfee071f55', 'P202C', '2022-10-26 08:51:41', '2022-10-26 08:51:41'),
('69f8787d-77e6-4b2e-8a93-d8c1d3759af6', 'Nâu P4975', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('6b858841-fc43-43ba-9ab2-98af08452572', 'Magenta', '2022-10-26 08:51:34', '2022-10-26 08:51:34'),
('6b8fa701-f4bf-41b8-9177-a67d910c825b', 'tím nền', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('71d044f1-252e-4bd2-99b5-4b36e676a62d', 'CYAN 301', '2022-11-01 19:43:37', '2022-11-01 19:43:37'),
('71da6626-dee5-422f-9bfb-22ee18bb07ec', 'Xanh tím\nP540C', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('73efca94-dcc2-42c9-b8b7-b069feb43c53', 'Yellow 2007', '2022-11-01 19:42:03', '2022-11-01 19:42:03'),
('758a9dee-e397-4feb-8667-f804ba544159', 'White w962', '2022-11-01 19:42:11', '2022-11-01 19:42:11'),
('78d87fda-ca80-4460-be8f-3dafc48143f7', 'Nền Xanh Pha', '2022-10-26 08:51:32', '2022-10-26 08:51:32'),
('7c3e69e9-9d09-409a-b66a-1e25ec100dca', 'Cyan 939', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('7c4dee91-6a87-420e-ac30-7de6c3af285a', 'PHỦ MỜ', '2022-11-01 19:43:37', '2022-11-01 19:43:37'),
('7c97b28b-4422-49ad-b728-88e99fda4f8c', 'Nâu P4625', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('7d217d7a-3442-4c4b-9462-caccef8ca8fb', 'P284', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('7d354425-9789-48e5-bbeb-50243afb42c6', 'Xanh Tím Đen', '2022-10-26 08:51:32', '2022-10-26 08:51:32'),
('7ff3a25c-e4b6-45ea-a6f4-6a081d9aed0b', 'Xám', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('8011b29e-3cde-4faf-9cbe-dffd43cbe4ca', 'Hồng Pha', '2022-10-26 08:51:33', '2022-10-26 08:51:33'),
('8080649b-22f2-4498-9923-aba6127405f8', 'Tím Nhạt', '2022-10-26 08:51:33', '2022-10-26 08:51:33'),
('836eb73f-d755-4a5b-b635-7743f3f72ebb', 'P7618C', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('83eaa53c-54bf-4428-93dd-5a3c927b4476', 'P2567C', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('84e460db-c2e1-46de-80c1-3686d780c72f', 'NỀN XAMH', '2022-11-01 19:43:37', '2022-11-01 19:43:37'),
('862b8435-84e0-45c1-8106-a63d45e2d8c2', 'P2162C', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('8a7819b9-b687-4653-83ac-68c87ed9833c', 'đỏ xanh', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('8b408fe0-0ca1-4dae-88b3-3b860da2eeb4', 'Hồng Nền', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('9292d76e-63e1-4d2c-9730-d50b197826dd', 'Xanh Pha', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('93eda162-5926-4073-9a04-9ffc63e2f25f', 'Vàng Pha', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('946529e0-9d50-49c5-bd76-a0ae77e627aa', 'Xanh lá\np575', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('98264fda-2b81-4d51-a55d-450d9515a68d', 'YELLOW', '2022-11-01 19:43:37', '2022-11-01 19:43:37'),
('9d912da1-8912-4411-837c-d90d93a475a6', 'xanh tím nhạt', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('a1c14a58-e599-442d-8711-381b67d85d85', 'P172C', '2022-10-26 08:51:27', '2022-10-26 08:51:27'),
('a5084e2c-7d86-48fd-9b30-cdcf4049db44', 'P288C', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('a8ce4591-2cdb-4213-b69f-bcaa064645cd', 'vàng', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('ac596edc-4766-4af6-8dca-48afd34129ab', 'Hồng P190C', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('ac6aa1a6-5c12-492a-a018-b040120df8d4', 'Xamh Tím', '2022-10-26 08:51:32', '2022-10-26 08:51:32'),
('ae972787-dce0-48d9-a46f-991c5473d027', 'Nâu P370C', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('b341e824-2d15-4385-ad9c-9ad3b1929309', 'Whtie 100', '2022-11-01 19:42:03', '2022-11-01 19:42:03'),
('b39a08ef-5ba8-4347-ab49-bade39806c38', 'VANISH', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('b6fcbfb7-a622-489e-84a1-d1761c4b0b42', 'ĐEN', '2022-11-01 19:42:02', '2022-11-01 19:42:02'),
('bed3dcaf-a7df-45b7-a034-62a873432a7a', 'Xanh Lá Nền', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('bf13b31c-58cb-4f59-8cd9-1d0256c91d8d', 'Đỏ', '2022-10-26 08:51:35', '2022-10-26 08:51:35'),
('c2c56266-c27f-4b11-ab15-5c78653f146e', 'P7617C', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('c9145a9f-9314-40e1-92a7-eceae99652cc', 'nền xanh đen', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('ca62bf34-5ca2-49de-b581-0ef6f04fee9a', 'Xám Đậm', '2022-10-26 08:51:40', '2022-10-26 08:51:40'),
('ca9bb7a6-5498-4c16-826c-f839fc643445', 'Magenta 300', '2022-11-01 19:42:03', '2022-11-01 19:42:03'),
('caa4f39a-cf82-42df-aded-ce6f5abb5604', 'Kem', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('cc1a4550-4dbf-452b-af5b-87836b5ff306', 'Nền Xanh', '2022-10-26 08:51:33', '2022-10-26 08:51:33'),
('cc448a86-f228-4896-8be2-5dbc512753c3', 'xanh', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('d4fafbaf-55ad-4566-bf11-ce3d345105ca', 'Nền Cam', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('d50dbb14-6f50-425c-ad5f-a1a72c99fba5', 'Red\n', '2022-10-26 08:51:37', '2022-10-26 08:51:37'),
('d6787d42-44aa-4e14-a778-9040c99ebc57', 'Đỏ Nhạt', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('d6dd13c9-57d8-4981-b07a-1a33372f9770', 'Nâu', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('d818f8aa-d417-4eec-ab52-a536545bb9e0', 'P293C', '2022-10-26 08:51:47', '2022-10-26 08:51:47'),
('d8b91480-a7e0-4c6e-9814-085871938c33', 'Nhũ Pha', '2022-11-01 19:42:03', '2022-11-01 19:42:03'),
('d9213b42-fea9-4cb1-82bd-1c73b4d3f71e', 'Xanh Nhạt', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('d944f804-b213-47d9-8174-031707d7dc62', 'Xanh Nền', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('d9753798-f562-4378-8dc5-84fb663e6859', 'Tím', '2022-10-26 08:51:29', '2022-10-26 08:51:29'),
('dd02f7bf-8b6c-4f84-ae46-a1d75feb9df9', 'White W001sl', '2022-11-01 19:43:37', '2022-11-01 19:43:37'),
('dfe9c5b3-315c-47c9-9b8d-0ba2b4659481', 'Xanh Lá P341', '2022-10-26 08:51:27', '2022-10-26 08:51:27'),
('dff45a16-bc2a-4494-924f-6b4cee773c9a', 'Nền', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('e040fb79-1479-4272-96bc-77ba523b50d6', 'P7401C', '2022-10-26 08:51:43', '2022-10-26 08:51:43'),
('e127fb19-4e8d-44f9-a70b-5d452b398194', 'Magenta 360', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('e24eafb4-d01d-491c-a8f4-6871ffdfd5e6', 'xanh lá chữ', '2022-10-26 08:51:48', '2022-10-26 08:51:48'),
('e2587e32-253a-42c1-af62-a66e5eb93642', 'Yellow 923', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('e36e9f1a-312c-4eab-9805-564179ed87a0', 'Xanh Đen', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('e6053870-30d1-4e91-9523-4bf8d4d3bbd4', 'Cam P485C', '2022-10-26 08:51:37', '2022-10-26 08:51:37'),
('e6be31c7-1f34-4a8f-9b94-3dc020ffb38d', 'Cam', '2022-10-26 08:51:26', '2022-10-26 08:51:26'),
('e8ac2406-22cb-4833-8fc6-98efa0f3f298', 'CAM\nP1565C', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('edc85699-f99d-4e05-977d-f956add04ee0', 'Xanh Lá Đậm', '2022-10-26 08:51:34', '2022-10-26 08:51:34'),
('f25e80e1-bf13-4df1-b235-81ab1167b494', 'P376C', '2022-10-26 08:51:28', '2022-10-26 08:51:28'),
('f528ea94-0247-4b49-8a95-fb24e8b22c02', 'Đỏ Nền', '2022-10-26 08:51:31', '2022-10-26 08:51:31'),
('f5c0a88d-5ff9-4350-bcc1-b113329386e1', 'WARN GRAY', '2022-11-01 19:42:10', '2022-11-01 19:42:10'),
('f68da8ea-95b0-4779-9f6f-6bd900fb6d4c', 'Vàng Nền', '2022-10-26 08:51:30', '2022-10-26 08:51:30'),
('f799735b-4730-459e-9ab1-fbcacf80a993', 'Cam Chữ', '2022-10-26 08:51:41', '2022-10-26 08:51:41'),
('f8d8fd48-5cb8-445d-a414-7bd32f672d67', 'red\nP186C', '2022-10-26 08:51:39', '2022-10-26 08:51:39'),
('fbc68b24-f954-4dfc-a149-397e215ee0f1', 'P356C', '2022-10-26 08:51:34', '2022-10-26 08:51:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `criterias_values`
--

CREATE TABLE `criterias_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer`
--

CREATE TABLE `customer` (
  `id` varchar(36) NOT NULL,
  `name` varchar(191) NOT NULL,
  `thong_tin` varchar(191) DEFAULT NULL,
  `created_at` varchar(191) DEFAULT current_timestamp(),
  `updated_at` varchar(191) DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `customer`
--

INSERT INTO `customer` (`id`, `name`, `thong_tin`, `created_at`, `updated_at`) VALUES
('NTL383', 'TL-CÔNG TY TNHH ALMUS VINA', NULL, '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('NTL121', 'CNTL-Công Ty  TNHH CRESYN Hà Nội', NULL, '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('NTL499', 'TL_CÔNG TY TNHH RFTECH THÁI NGUYÊN', NULL, '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('NTL202  ', 'TL-CÔNG TY TNHH HÀ NỘI SEOWONINTECH', NULL, '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('NTL464', 'CNTL- CÔNG TY TNHH HANBO ENC VINA', NULL, '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('NTL400', 'CNTL_Công ty TNHH UIL Việt Nam', NULL, '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('NTL170', 'TL-CÔNG TY TNHH GLONICS VIỆT NAM', NULL, '2023-07-29 13:32:07', '2023-07-29 13:32:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `descriptions`
--

CREATE TABLE `descriptions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `errors`
--

CREATE TABLE `errors` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `noi_dung` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `line_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nguyen_nhan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `khac_phuc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phong_ngua` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `errors`
--

INSERT INTO `errors` (`id`, `name`, `noi_dung`, `line_id`, `created_at`, `updated_at`, `nguyen_nhan`, `khac_phuc`, `phong_ngua`) VALUES
('BE1', '', 'Bế lệch', '12', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Tay kê kéo không đều', 'Chỉnh tay kê khi sản xuất', 'nhấn chỉnh tay kê đầu giấy'),
('BE2', '', 'Dính phôi', '12', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Vết nối dao mắt đục hơi hở công nhân chưa xử lý, quá trình làm sạch phôi đục chưa đảm bảo', 'thực hiện chọc phôi khi ép 2 kim vào 1', '- Tăng tần suất kiểm tra khuôn bế, kiểm tra hàng đang sản xuất 2h/lần'),
('BE3', '', 'Xơ, bavia', '12', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do chất lượng dao bế không tốt\n- Do dao cao dẫn đến xơ mép giấy', 'Theo dõi dao bế áp lực bế', '- Sử dụng dao titan để sản xuất \n- Kiểm tra chiều dao khuôn bế trước khi sản xuất '),
('BE4', '', 'Tổn thương', '12', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do dính phôi trong khuôn bế', 'kiểm tra chọc phôi không để sót phôi', 'Chọc phôi không tiếp xúc với 2 kim'),
('BE5', '', 'Vỡ giấy', '12', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do dao bế cong, lệch với đường hằn nên khi bế bị lệch gây ra nứt đường hằn', 'chỉnh đường hằn', 'Nắn lại dao bế, tăng tần suất kiểm tra 200 tờ/lần, xử lý khoanh vùng lỗi khi phát hiện bất thường'),
('BE6', '', '', '14', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '', '', ''),
('BO1', '', '', '9', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '', '', ''),
('GD1', '', 'Bong keo', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do khi sản xuất bị tắt keo dẫn đến bị bong keo\n- Do khi chạy máy đầu cấp ra không đều 2 mảnh bị chồng chéo dẫn đến bong keo', 'vệ sinh súng keo, căn chỉnh đầu cấp, và kiểm tra lực ép băng tải daauf đón', '- Tăng tần suất kiểm tra súng bắn keo, vệ sinh súng bắn keo\n- Căn chỉnh đầu cấp cho đều trước khi sản xuất hàng loạt.'),
('GD2', '', 'Bẩn keo', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do điểm bắn keo nhỏ đầu cấp ra không đều nên vị trí bắn keo bị trượt ra ngoài', 'kiểm tra băng tải có bị bẩn keo không, và sử lý làm sạch băng tải truocs khi sản xuất', '- Căn chỉnh bắn keo vào giữa đường keo, không bắn lệch, không bắn sát mép làm tràn keo ra ngoài'),
('GD3', '', 'Tràn keo', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Lệch vị trí bắn keo, do chéo phôi\nDo phôi đầu vào bị cong', 'kiểm tra và cài đặt vị trí bắn keo + lượng keo hợp láy', 'Căn chỉnh lại đầu cấp, để phôi không ra lệch\nNắn lại phôi trước khi vào đầu cấp'),
('GD4', '', 'Gấp lệch', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do đầu cấp ra chéo\nDo dưỡng gấp chưa chính xác', 'Căn chỉnh lại đầu cấp cho phôi ra đều\nCăn chỉnh lại dưỡng gấp', 'kiểm tra lại độ ổn định của đầu cấp và vị trí dưỡng gấp truocs khi sản xuất hàng loạt'),
('GD5', '', 'Tổn thương', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Phôi cài vào nhau\nCân chỉnh đầu đón chưa đều', 'phật phôi tơi, kiểm tra độ ổn định của đầu cấp trước khi sản xuất hàng loạt', 'Bẻ phôi trước khi vào đầu cấp\nCan chỉnh lại đầu đón'),
('GD6', '', 'Hằn', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do lực ép các lô tỳ trên máy mạnh\nDo lô lực ép đầu đón mạnh', 'kiểm tra áp lực lô tỳ và băng tải đầu đón trước khi chạy hàng loạt', 'Giảm lực ép lô\nGiảm lực ép đầu đón'),
('GD7', '', 'Gấp ngược', '13', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do phôi đầu vào ngược', 'kiểm tra phôi trước khi cho vào đầu cấp', 'Đào tạo người cấp phôi'),
('IN1', '', 'Lên mực', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do bản in bị bẩn', 'Lau lại bằng sữa ', '- Kiểm tra kỹ bản in trước khi nhận, kiểm tra liên tục 5pcs trước khi sản xuất hàng loạt'),
('IN2', '', 'Khác màu', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do giấy ( bề mặt giấy không đều, màu sắc giấy không đều, độ dày giấy không đều)\n- Do nước từng cụm in không đều.\n- Do giấy vào máy không ổn định\n- Do nhíp máy gắp giấy không ổn định\n- Do bản bị mất tram, làm không lên màu in\n- Do mặt cao su bị lỗi lõm', 'Đổi sang NVL giấy mới có bề mặt mịn\nCân lại lô mực, lô nước về đúng tiêu chuẩn\nThay lại bộ khác có đầy đủ tram\nThay cao su mới', ' Kiểm soát điều kiện sản xuất đặc biệt là máy móc, làm biên bản xác nhận OK trước khi chạy hàng loạt.\n- Khoanh vùng khi chỉnh máy, phát sinh sự cố theo hướng dẫn khoanh vùng số TL-QC-HD-TT15.'),
('IN3', '', 'Mờ hình in', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Do lỗi bản\nBản cao su bị lõm\nBay bản kẽm\nNhiều nước trên bản', 'Thay bản mới, cao su mới\nCăn lại lô nước, giảm nước trên máy', 'Ra lại bản kẽm\nThay bản cao su\nRa lại bản kẽm\nGiảm lượng nước trên bản'),
('IN4', '', 'Lệch hình in', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do giấy đầu vào kích thước không đều, không bắt được mắt phát sinh lỗi lệch hình in', 'Đổi giấy mới đúng kích thước', '- Trong quá trình kiểm tra kệ hàng yêu cầu công nhân xếp kệ hàng chuẩn mép tay kê bắt mắt để mắt bắt được chuẩn hơn'),
('IN5', '', 'Mất nét hình in', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do áp lực tại vị trí  in bị yếu', 'Kiểm tra lại áp lực in, lót cao su', '- Căn chỉnh lại áp lực trước khi sản xuất, khi sản xuất kiểm tra liên tục 20 mét đầu cuộn nếu không phát sinh lỗi mới đánh dấu lấy hàng đạt'),
('IN6', '', 'In ngược mặt', '10', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '- Do giấy đầu vào không xếp cùng chiều', 'Xếp lại giấy', '- Kiểm tra chiều giấy trước khi sản xuất '),
('PH1', '', 'Sọc', '11', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Lô in chưa vệ sinh sạch', 'Vệ sinh sạch lô in phủ', 'vệ sinh lô in khi hết ca sx '),
('PH2', '', 'Loang phủ', '11', '2023-07-14 07:03:01', '2023-07-14 07:03:01', '', '', ''),
('PH3', '', 'Lệch phủ', '11', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Tay kê kéo lệch', 'Chỉnh tay kê', 'Chỉnh tay kê'),
('PH4', '', 'Xước phủ', '11', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'thanh ép giấy bị bẩn', 'Vệ sinh sạch ép giấy', 'Vệ sinh máy trước khi sản xuất'),
('PH5', '', 'Không phủ', '11', '2023-07-14 07:03:01', '2023-07-14 07:03:01', 'Không bật áp lực', 'Bật áp lực khi sản xuất', 'Kiểm tra áp lực trước khi sản xuất');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lines`
--

CREATE TABLE `lines` (
  `id` int(9) NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lines`
--

INSERT INTO `lines` (`id`, `name`, `note`, `display`, `created_at`, `updated_at`) VALUES
(9, 'Kho bảo ôn', NULL, 1, '2023-07-14 07:03:01', '2023-07-14 07:03:01'),
(10, 'In', NULL, 1, '2023-07-14 07:03:01', '2023-07-14 07:03:01'),
(11, 'Phủ', NULL, 1, '2023-07-14 07:03:01', '2023-07-14 07:03:01'),
(12, 'Bế', NULL, 1, '2023-07-14 07:03:01', '2023-07-14 07:03:01'),
(13, 'Gấp dán', NULL, 1, '2023-07-14 07:03:01', '2023-07-14 07:03:01'),
(14, 'Bóc', NULL, 1, '2023-07-14 07:28:14', '2023-07-14 07:28:14'),
(15, 'Chọn', NULL, 1, NULL, NULL),
(16, 'Kiểm tra NVL', NULL, 0, NULL, NULL),
(19, 'Kho thành phẩm', NULL, 1, '2023-07-17 04:38:12', '2023-07-17 04:38:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lot`
--

CREATE TABLE `lot` (
  `id` varchar(36) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `lsx` varchar(36) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `finished` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `l_s_x_logs`
--

CREATE TABLE `l_s_x_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `info` text CHARACTER SET utf8 COLLATE utf8_vietnamese_ci DEFAULT NULL,
  `lsx` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `l_s_x_logs`
--

INSERT INTO `l_s_x_logs` (`id`, `info`, `lsx`, `created_at`, `updated_at`) VALUES
(1, '{\"mDauVao\":\"500\",\"mDauRa\":\"8000\",\"msc\":1667201174,\"lot\":0,\"reason\":\"KH\\u00c1C\"}', 'S22C00875', '2022-10-31 21:26:14', '2022-10-31 21:52:31'),
(2, '{\"mDauVao\":\"777\",\"mDauRa\":\"80\",\"msc\":1667202787,\"lot\":2}', 'S22A04384', '2022-10-31 21:53:07', '2022-10-31 21:53:12'),
(3, '{\"mDauVao\":\"200\",\"mDauRa\":\"190\",\"msc\":1667203952,\"lot\":2,\"machine_id\":\"FR200\"}', 'S22A03908', '2022-10-31 22:12:32', '2022-10-31 22:12:41'),
(4, '{\"mDauVao\":\"200\",\"mDauRa\":0,\"msc\":1667204400,\"lot\":1,\"machine_id\":\"FR200\"}', 'S22A04006', '2022-10-31 22:20:00', '2022-10-31 22:20:00'),
(5, '{\"mDauVao\":\"200\",\"mDauRa\":0,\"msc\":1667204592,\"lot\":2,\"machine_id\":\"FR200\"}', 'S22A04006', '2022-10-31 22:23:12', '2022-10-31 22:23:12'),
(6, '{\"mDauVao\":\"5\",\"mDauRa\":\"2000\",\"msc\":1667206689,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"KH\\u00c1C\"}', 'S22C00875', '2022-10-31 22:58:09', '2022-10-31 23:02:11'),
(7, '{\"mDauVao\":\"6000\",\"mDauRa\":0,\"msc\":1667208107,\"lot\":1,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:21:47', '2022-10-31 23:21:47'),
(8, '{\"mDauVao\":0,\"mDauRa\":0,\"msc\":1667208107,\"lot\":2,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:23:19', '2022-10-31 23:23:19'),
(9, '{\"mDauVao\":\"500\",\"mDauRa\":0,\"msc\":1667208321,\"lot\":3,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:25:21', '2022-10-31 23:25:21'),
(10, '{\"mDauVao\":\"6000\",\"mDauRa\":\"228\",\"msc\":1667208427,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u1ef0C\",\"test_criteria\":{\"bda73b96-44b6-48de-ae97-c975d31839ab\":\"\\u0110\\u00fang\",\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"\\u0110\\u1ea1t\",\"6c9f7201-e315-4c86-a0c4-8cbef06d3c4d\":null,\"8e874171-3796-4a4f-a2ad-48e0b4904bb6\":\"Trong\",\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"\\u0110\\u1ea1t\",\"4b932a9d-3de2-472a-bc7c-a263522c46db\":6,\"8a2d7350-b07d-42c8-8040-6e89d68b5fc1\":\"\\u0110\\u1ea1t\",\"a85ae79f-8e0b-4200-aee4-3add3c87e5dd\":\"CPP 30 mic\",\"f60bf15a-6528-446c-b31e-731fc8bced2a\":136190.5,\"ffbf7252-9792-48f3-8dd6-afcd06fe2e8a\":30,\"d66aca53-6132-4699-808d-7cb2955ae27f\":2.5,\"97d61e62-3978-42a8-a64f-d612019cc912\":\"Kh\\u00f4ng ki\\u1ec3m\",\"796014b4-dc29-4ab5-b221-b2f0f98eedce\":\"Kh\\u00f4ng ki\\u1ec3m\",\"68185041-0eeb-4fe4-bdac-5799c7974be4\":null},\"result\":\"\\u0110\\u1ea1t\"}', 'S22A04360', '2022-10-31 23:27:07', '2022-10-31 23:46:48'),
(11, '{\"mDauVao\":\"6000\",\"mDauRa\":\"500\",\"msc\":1667208918,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u1ef0C\"}', 'S22A04360', '2022-10-31 23:35:18', '2022-10-31 23:39:42'),
(12, '{\"mDauVao\":\"1000\",\"mDauRa\":0,\"msc\":1667209370,\"lot\":6,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:42:50', '2022-10-31 23:42:50'),
(13, '{\"mDauVao\":0,\"mDauRa\":0,\"msc\":1667209370,\"lot\":7,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:42:58', '2022-10-31 23:42:58'),
(14, '{\"mDauVao\":\"1000\",\"mDauRa\":0,\"msc\":1667209425,\"lot\":8,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:43:45', '2022-10-31 23:43:45'),
(15, '{\"mDauVao\":0,\"mDauRa\":0,\"msc\":1667209425,\"lot\":9,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:43:59', '2022-10-31 23:43:59'),
(16, '{\"mDauVao\":0,\"mDauRa\":0,\"msc\":1667209425,\"lot\":10,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:44:12', '2022-10-31 23:44:12'),
(17, '{\"mDauVao\":\"6000\",\"mDauRa\":\"2200\",\"msc\":1667209514,\"lot\":13,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-10-31 23:45:14', '2022-11-01 00:00:41'),
(18, '{\"mDauVao\":\"2200\",\"mDauRa\":0,\"msc\":1667210435,\"lot\":12,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-11-01 00:00:35', '2022-11-01 00:00:35'),
(19, '{\"mDauVao\":\"2200\",\"mDauRa\":\"2200\",\"msc\":1667210830,\"lot\":14,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-11-01 00:07:10', '2022-11-01 00:07:16'),
(20, '{\"mDauVao\":\"2200\",\"mDauRa\":\"2200\",\"msc\":1667211225,\"lot\":15,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-11-01 00:13:45', '2022-11-01 00:13:55'),
(21, '{\"mDauVao\":\"6000\",\"mDauRa\":0,\"msc\":1667214129,\"lot\":15,\"machine_id\":\"FR200\"}', 'S22A04360', '2022-11-01 01:02:09', '2022-11-01 01:02:09'),
(22, '{\"mDauVao\":\"6000\",\"mDauRa\":\"500\",\"msc\":1667214169,\"lot\":2,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 01:02:49', '2022-11-01 01:02:59'),
(23, '{\"mDauVao\":\"500\",\"mDauRa\":0,\"msc\":1667214169,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u1ef0C\"}', 'S22A04362', '2022-11-01 01:03:16', '2022-11-01 01:03:28'),
(24, '{\"mDauVao\":0,\"mDauRa\":0,\"msc\":1667214169,\"lot\":3,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 01:05:17', '2022-11-01 01:05:17'),
(25, '{\"mDauVao\":\"6000\",\"mDauRa\":\"1500\",\"msc\":1667214326,\"lot\":5,\"machine_id\":\"SH\"}', 'S22A03959', '2022-11-01 01:05:26', '2022-11-01 01:05:36'),
(26, '{\"mDauVao\":\"6000\",\"mDauRa\":\"500\",\"msc\":1667214506,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u1ef0C\"}', 'S22A04362', '2022-11-01 01:08:26', '2022-11-01 01:09:22'),
(27, '{\"mDauVao\":\"6000\",\"mDauRa\":0,\"msc\":1667215015,\"lot\":6,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 01:16:55', '2022-11-01 01:16:55'),
(28, '{\"mDauVao\":\"6000\",\"mDauRa\":\"400\",\"msc\":1667215905,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u1ef0C\"}', 'S22A04362', '2022-11-01 01:31:45', '2022-11-01 01:37:00'),
(29, '{\"mDauVao\":\"6000\",\"mDauRa\":\"2118\",\"msc\":1667216868,\"lot\":9,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 01:47:48', '2022-11-01 02:15:29'),
(30, '{\"mDauVao\":\"500\",\"mDauRa\":\"2000\",\"msc\":1667221768,\"lot\":0,\"machine_id\":\"FR200\",\"reason\":\"M\\u00c0NG\"}', 'S22A04006', '2022-11-01 03:09:28', '2022-11-01 03:10:26'),
(31, '{\"mDauVao\":\"4500\",\"mDauRa\":\"2500\",\"msc\":1667265468,\"lot\":2,\"machine_id\":\"SH\",\"test_criteria\":{\"1226379f-8a40-4297-b7c4-b7a502f662a3\":152,\"18172604-4f54-405f-96ed-12b2e2c680d1\":172,\"1de6343d-ae78-4865-9b53-9983c3268ab4\":\"\\u0110\\u00fang\",\"28e070e6-7159-4959-a96d-004263da8b23\":\"U bi\\u00ean\",\"2bb87c4a-ab61-4cce-acca-ea239303c194\":\"Ngo\\u00e0i\",\"498dd956-d213-44e2-8eb2-0b45d852f3ce\":11,\"58d9f598-0272-428d-a3d6-ca4593b3603e\":null,\"606931cc-50ca-4354-98e1-e545fb6f329d\":0,\"undefined\":[\"\\u0110\\u1ed1m m\\u1ef1c, b\\u1ecdt m\\u1ef1c\",\"In l\\u1ec7ch\",\"Tr\\u00f3c m\\u1ef1c\\/ blocking\"],\"74059044-0e6d-43a4-9a7b-1ffbb9d8a0c9\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"77a745a3-2077-4469-843f-ec087ac19feb\":null,\"79423db6-1fa5-4310-abb7-73a827fb1920\":111,\"84afef90-bf2a-4ee5-9af2-80ee894edc43\":\"Kh\\u00f4ng ki\\u1ec3m\",\"9111e159-281d-454d-a894-a9a42fb1c70b\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"9f11c82d-7c46-4672-ab4e-09cd1816bad8\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"b4deb179-d4aa-457d-9cb9-d516fd59c2b7\":\"Kh\\u00f4ng ki\\u1ec3m\",\"b64f47e5-f41a-45a7-9899-369706f2140e\":171,\"bb24c19b-9d56-4442-943d-94076684a80f\":9,\"ea77aea2-f58f-44b2-81b9-f593f937fb3b\":770,\"ecfb7424-5e50-467d-bad3-ddda2ec7ee88\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"eeea91cc-92ba-4d50-9f55-76ea65675abf\":\"Sai\",\"f350385a-a4d6-4fa9-a203-2cccd43826ce\":11,\"f43473ba-c62d-43e9-9c06-ca3c26b24f3b\":\"Kh\\u00f4ng ki\\u1ec3m\",\"f6238d7f-e694-4d81-b123-eea7e14a534f\":\"\\u0110\\u1ea1t\"},\"result\":\"Kh\\u00f4ng \\u0111\\u1ea1t\"}', 'S22A03974', '2022-11-01 15:17:48', '2022-11-01 16:35:11'),
(32, '{\"mDauVao\":\"1000\",\"mDauRa\":\"2000\",\"msc\":1667267106,\"lot\":10,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 15:45:06', '2022-11-01 15:45:19'),
(33, '{\"mDauVao\":\"2000\",\"mDauRa\":0,\"msc\":1667267106,\"lot\":10,\"machine_id\":\"FR200\"}', 'S22A04362', '2022-11-01 15:47:59', '2022-11-01 15:47:59'),
(34, '{\"mDauVao\":\"114\",\"mDauRa\":\"125\",\"msc\":1667268127,\"lot\":2,\"machine_id\":\"FR200\",\"test_criteria\":{\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"\\u0110\\u1ea1t\",\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"4b932a9d-3de2-472a-bc7c-a263522c46db\":11,\"68185041-0eeb-4fe4-bdac-5799c7974be4\":187,\"undefined\":[\"Sai n\\u1ed9i dung\"],\"796014b4-dc29-4ab5-b221-b2f0f98eedce\":\"Kh\\u00f4ng \\u0111\\u1ea1t\",\"8a2d7350-b07d-42c8-8040-6e89d68b5fc1\":\"Kh\\u00f4ng ki\\u1ec3m\",\"8e874171-3796-4a4f-a2ad-48e0b4904bb6\":\"Trong\",\"97d61e62-3978-42a8-a64f-d612019cc912\":\"Kh\\u00f4ng ki\\u1ec3m\",\"a85ae79f-8e0b-4200-aee4-3add3c87e5dd\":\"NYLON 12mic\",\"bda73b96-44b6-48de-ae97-c975d31839ab\":\"Sai\",\"d66aca53-6132-4699-808d-7cb2955ae27f\":null,\"f60bf15a-6528-446c-b31e-731fc8bced2a\":716,\"ffbf7252-9792-48f3-8dd6-afcd06fe2e8a\":1172},\"result\":\"Kh\\u00f4ng \\u0111\\u1ea1t\"}', 'S22A04393', '2022-11-01 16:02:07', '2022-11-01 16:04:29'),
(35, '{\"mDauVao\":\"8000\",\"mDauRa\":\"2500\",\"msc\":1667268921,\"lot\":2,\"machine_id\":\"FR200\",\"test_criteria\":{\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"Kh\\u00f4ng ki\\u1ec3m\",\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"\\u0110\\u1ea1t\",\"4b932a9d-3de2-472a-bc7c-a263522c46db\":7,\"6c9f7201-e315-4c86-a0c4-8cbef06d3c4d\":null,\"796014b4-dc29-4ab5-b221-b2f0f98eedce\":\"Kh\\u00f4ng ki\\u1ec3m\",\"8a2d7350-b07d-42c8-8040-6e89d68b5fc1\":\"\\u0110\\u1ea1t\",\"8e874171-3796-4a4f-a2ad-48e0b4904bb6\":\"Trong\",\"97d61e62-3978-42a8-a64f-d612019cc912\":\"\\u0110\\u1ea1t\",\"a85ae79f-8e0b-4200-aee4-3add3c87e5dd\":\"M\\u00e0ng th\\u1ed5i LLDPE\",\"bda73b96-44b6-48de-ae97-c975d31839ab\":\"Sai\",\"d66aca53-6132-4699-808d-7cb2955ae27f\":1.5,\"f60bf15a-6528-446c-b31e-731fc8bced2a\":165,\"ffbf7252-9792-48f3-8dd6-afcd06fe2e8a\":20,\"68185041-0eeb-4fe4-bdac-5799c7974be4\":null,\"1226379f-8a40-4297-b7c4-b7a502f662a3\":7,\"28e070e6-7159-4959-a96d-004263da8b23\":\"\\u0110\\u1ea1t\",\"58d9f598-0272-428d-a3d6-ca4593b3603e\":null,\"74059044-0e6d-43a4-9a7b-1ffbb9d8a0c9\":\"\\u0110\\u1ea1t\",\"84afef90-bf2a-4ee5-9af2-80ee894edc43\":\"\\u0110\\u1ea1t\",\"b4deb179-d4aa-457d-9cb9-d516fd59c2b7\":\"\\u0110\\u1ea1t\",\"ea77aea2-f58f-44b2-81b9-f593f937fb3b\":20,\"f350385a-a4d6-4fa9-a203-2cccd43826ce\":3,\"18172604-4f54-405f-96ed-12b2e2c680d1\":120,\"2bb87c4a-ab61-4cce-acca-ea239303c194\":\"Trong\",\"606931cc-50ca-4354-98e1-e545fb6f329d\":null,\"77a745a3-2077-4469-843f-ec087ac19feb\":null,\"9111e159-281d-454d-a894-a9a42fb1c70b\":\"\\u0110\\u1ea1t\",\"b64f47e5-f41a-45a7-9899-369706f2140e\":0.12,\"ecfb7424-5e50-467d-bad3-ddda2ec7ee88\":\"\\u0110\\u1ea1t\",\"f43473ba-c62d-43e9-9c06-ca3c26b24f3b\":\"Kh\\u00f4ng ki\\u1ec3m\",\"1de6343d-ae78-4865-9b53-9983c3268ab4\":\"\\u0110\\u00fang\",\"498dd956-d213-44e2-8eb2-0b45d852f3ce\":6,\"6cb36ac4-feac-4323-8abc-f423a914628f\":null,\"79423db6-1fa5-4310-abb7-73a827fb1920\":9,\"9f11c82d-7c46-4672-ab4e-09cd1816bad8\":\"\\u0110\\u1ea1t\",\"bb24c19b-9d56-4442-943d-94076684a80f\":8,\"eeea91cc-92ba-4d50-9f55-76ea65675abf\":\"\\u0110\\u00fang\",\"f6238d7f-e694-4d81-b123-eea7e14a534f\":\"Kh\\u00f4ng ki\\u1ec3m\"},\"result\":\"\\u0110\\u1ea1t\"}', 'S22A04398', '2022-11-01 16:15:21', '2022-11-01 20:00:20'),
(36, '{\"mDauVao\":\"8000\",\"mDauRa\":\"1000\",\"msc\":1667269166,\"lot\":3,\"machine_id\":\"FR200\"}', 'S22A04398', '2022-11-01 16:19:26', '2022-11-01 16:21:38'),
(37, '{\"mDauVao\":\"8000\",\"mDauRa\":\"2650\",\"msc\":1667269416,\"lot\":4,\"machine_id\":\"FR200\",\"test_criteria\":{\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"Kh\\u00f4ng ki\\u1ec3m\",\"4b932a9d-3de2-472a-bc7c-a263522c46db\":8,\"6c9f7201-e315-4c86-a0c4-8cbef06d3c4d\":null,\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"\\u0110\\u1ea1t\",\"796014b4-dc29-4ab5-b221-b2f0f98eedce\":\"\\u0110\\u1ea1t\",\"8a2d7350-b07d-42c8-8040-6e89d68b5fc1\":\"Kh\\u00f4ng ki\\u1ec3m\",\"8e874171-3796-4a4f-a2ad-48e0b4904bb6\":\"Trong\",\"97d61e62-3978-42a8-a64f-d612019cc912\":\"Kh\\u00f4ng ki\\u1ec3m\",\"a85ae79f-8e0b-4200-aee4-3add3c87e5dd\":\"NYLON 12mic\",\"bda73b96-44b6-48de-ae97-c975d31839ab\":\"\\u0110\\u00fang\",\"d66aca53-6132-4699-808d-7cb2955ae27f\":null,\"f60bf15a-6528-446c-b31e-731fc8bced2a\":150,\"ffbf7252-9792-48f3-8dd6-afcd06fe2e8a\":15,\"68185041-0eeb-4fe4-bdac-5799c7974be4\":null,\"1226379f-8a40-4297-b7c4-b7a502f662a3\":15,\"18172604-4f54-405f-96ed-12b2e2c680d1\":150,\"1de6343d-ae78-4865-9b53-9983c3268ab4\":\"\\u0110\\u00fang\",\"28e070e6-7159-4959-a96d-004263da8b23\":\"U bi\\u00ean\",\"2bb87c4a-ab61-4cce-acca-ea239303c194\":\"Kh\\u00f4ng ki\\u1ec3m\",\"498dd956-d213-44e2-8eb2-0b45d852f3ce\":10,\"58d9f598-0272-428d-a3d6-ca4593b3603e\":null,\"606931cc-50ca-4354-98e1-e545fb6f329d\":15,\"undefined\":[\"M\\u1ea5t m\\u1ef1c\\/ b\\u00f3 m\\u1ef1c\"],\"74059044-0e6d-43a4-9a7b-1ffbb9d8a0c9\":\"\\u0110\\u1ea1t\",\"77a745a3-2077-4469-843f-ec087ac19feb\":null,\"79423db6-1fa5-4310-abb7-73a827fb1920\":20,\"84afef90-bf2a-4ee5-9af2-80ee894edc43\":\"Kh\\u00f4ng ki\\u1ec3m\",\"9111e159-281d-454d-a894-a9a42fb1c70b\":\"\\u0110\\u1ea1t\",\"9f11c82d-7c46-4672-ab4e-09cd1816bad8\":\"Kh\\u00f4ng ki\\u1ec3m\",\"b4deb179-d4aa-457d-9cb9-d516fd59c2b7\":\"Kh\\u00f4ng ki\\u1ec3m\",\"b64f47e5-f41a-45a7-9899-369706f2140e\":2,\"bb24c19b-9d56-4442-943d-94076684a80f\":24,\"ea77aea2-f58f-44b2-81b9-f593f937fb3b\":15,\"ecfb7424-5e50-467d-bad3-ddda2ec7ee88\":\"\\u0110\\u1ea1t\",\"eeea91cc-92ba-4d50-9f55-76ea65675abf\":\"\\u0110\\u00fang\",\"f350385a-a4d6-4fa9-a203-2cccd43826ce\":3.2,\"f43473ba-c62d-43e9-9c06-ca3c26b24f3b\":\"\\u0110\\u1ea1t\",\"f6238d7f-e694-4d81-b123-eea7e14a534f\":\"\\u0110\\u1ea1t\"},\"result\":\"\\u0110\\u1ea1t\"}', 'S22A04398', '2022-11-01 16:23:36', '2022-11-01 19:58:01'),
(38, '{\"mDauVao\":\"3200\",\"mDauRa\":\"1000\",\"msc\":1667269664,\"lot\":5,\"machine_id\":\"FR200\",\"test_criteria\":{\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"\\u0110\\u1ea1t\",\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"\\u0110\\u1ea1t\"}}', 'S22A04398', '2022-11-01 16:27:44', '2022-11-01 17:22:46'),
(39, '{\"mDauVao\":\"8000\",\"mDauRa\":\"2500\",\"msc\":1667268921,\"lot\":2,\"machine_id\":\"FR200\",\"test_criteria\":{\"2eb33d4c-d425-4eba-8b4c-09140a15ee4c\":\"Kh\\u00f4ng ki\\u1ec3m\",\"444c543f-87b1-4597-ad9d-40055f8ed705\":\"\\u0110\\u1ea1t\",\"4b932a9d-3de2-472a-bc7c-a263522c46db\":7,\"6c9f7201-e315-4c86-a0c4-8cbef06d3c4d\":null,\"796014b4-dc29-4ab5-b221-b2f0f98eedce\":\"Kh\\u00f4ng ki\\u1ec3m\",\"8a2d7350-b07d-42c8-8040-6e89d68b5fc1\":\"\\u0110\\u1ea1t\",\"8e874171-3796-4a4f-a2ad-48e0b4904bb6\":\"Trong\",\"97d61e62-3978-42a8-a64f-d612019cc912\":\"\\u0110\\u1ea1t\",\"a85ae79f-8e0b-4200-aee4-3add3c87e5dd\":\"M\\u00e0ng th\\u1ed5i LLDPE\",\"bda73b96-44b6-48de-ae97-c975d31839ab\":\"Sai\",\"d66aca53-6132-4699-808d-7cb2955ae27f\":1.5,\"f60bf15a-6528-446c-b31e-731fc8bced2a\":165,\"ffbf7252-9792-48f3-8dd6-afcd06fe2e8a\":20,\"68185041-0eeb-4fe4-bdac-5799c7974be4\":null,\"1226379f-8a40-4297-b7c4-b7a502f662a3\":7,\"28e070e6-7159-4959-a96d-004263da8b23\":\"\\u0110\\u1ea1t\",\"58d9f598-0272-428d-a3d6-ca4593b3603e\":null,\"74059044-0e6d-43a4-9a7b-1ffbb9d8a0c9\":\"\\u0110\\u1ea1t\",\"84afef90-bf2a-4ee5-9af2-80ee894edc43\":\"\\u0110\\u1ea1t\",\"b4deb179-d4aa-457d-9cb9-d516fd59c2b7\":\"\\u0110\\u1ea1t\",\"ea77aea2-f58f-44b2-81b9-f593f937fb3b\":20,\"f350385a-a4d6-4fa9-a203-2cccd43826ce\":3,\"18172604-4f54-405f-96ed-12b2e2c680d1\":120,\"2bb87c4a-ab61-4cce-acca-ea239303c194\":\"Trong\",\"606931cc-50ca-4354-98e1-e545fb6f329d\":null,\"77a745a3-2077-4469-843f-ec087ac19feb\":null,\"9111e159-281d-454d-a894-a9a42fb1c70b\":\"\\u0110\\u1ea1t\",\"b64f47e5-f41a-45a7-9899-369706f2140e\":0.12,\"ecfb7424-5e50-467d-bad3-ddda2ec7ee88\":\"\\u0110\\u1ea1t\",\"f43473ba-c62d-43e9-9c06-ca3c26b24f3b\":\"Kh\\u00f4ng ki\\u1ec3m\",\"1de6343d-ae78-4865-9b53-9983c3268ab4\":\"\\u0110\\u00fang\",\"498dd956-d213-44e2-8eb2-0b45d852f3ce\":6,\"6cb36ac4-feac-4323-8abc-f423a914628f\":null,\"79423db6-1fa5-4310-abb7-73a827fb1920\":9,\"9f11c82d-7c46-4672-ab4e-09cd1816bad8\":\"\\u0110\\u1ea1t\",\"bb24c19b-9d56-4442-943d-94076684a80f\":8,\"eeea91cc-92ba-4d50-9f55-76ea65675abf\":\"\\u0110\\u00fang\",\"f6238d7f-e694-4d81-b123-eea7e14a534f\":\"Kh\\u00f4ng ki\\u1ec3m\"},\"result\":\"\\u0110\\u1ea1t\"}', 'S22A03908', '2022-11-01 17:14:57', '2022-11-01 20:00:20'),
(40, '{\"mDauVao\":\"1000\",\"mDauRa\":0,\"msc\":1667269664,\"lot\":2,\"machine_id\":\"SH\"}', 'S22A03974', '2022-11-01 17:44:07', '2022-11-01 17:44:07'),
(41, '{\"mDauVao\":0,\"mDauRa\":\"4000\",\"msc\":1667269664,\"lot\":4,\"machine_id\":\"SH\"}', 'S22A03974', '2022-11-01 17:46:16', '2022-11-01 17:49:40'),
(42, '{\"mDauVao\":\"4500\",\"mDauRa\":\"4500\",\"msc\":1667274869,\"lot\":5,\"machine_id\":\"SH\"}', 'S22A03974', '2022-11-01 17:54:29', '2022-11-01 17:54:35'),
(43, '{\"mDauVao\":\"4500\",\"mDauRa\":0,\"msc\":1667274869,\"lot\":5,\"machine_id\":\"SH\"}', 'S22A03974', '2022-11-01 17:57:35', '2022-11-01 17:57:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `machines`
--

CREATE TABLE `machines` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kieu_loai` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ma_so` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_id` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cong_suat` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hang_sx` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nam_sd` int(11) NOT NULL,
  `don_vi_sd` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tinh_trang` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vi_tri` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `machines`
--

INSERT INTO `machines` (`id`, `name`, `kieu_loai`, `ma_so`, `line_id`, `created_at`, `updated_at`, `cong_suat`, `hang_sx`, `nam_sd`, `don_vi_sd`, `tinh_trang`, `vi_tri`) VALUES
('2983f1d1-2cfb-458e-9819-9d5792faeb95', 'MÁY IN TỜ RỜI  KOMORI', 'GL-637+C+IR', 'IN KOMORI - 01', '-1', '2023-07-14 09:51:00', '2023-07-14 09:51:00', '209KW', 'KOMORI LITHRONE', 2021, 'Giấy', 'HĐ', 'XƯỞNG GIẤY MƠI'),
('a5b8b722-57d7-4342-b109-c5b9c6cfeb93', 'MÁY PHỦ UV CỤC BỘ', 'SN-UV', 'PHỦ UV - 02', '-1', '2023-07-14 09:51:00', '2023-07-14 09:51:00', '54KW', 'TRUNG QUỐC', 2021, 'Giấy', 'KHĐ', 'XƯỞNG GIẤY MƠI'),
('ba3ce004-24c0-44d9-b35c-3b70cbb8eedb', 'MÁY GẤP HỘP', 'ACE70CS', 'GẤP HỘP - 02', '-1', '2023-07-14 09:51:00', '2023-07-14 09:51:00', '21.2KW', 'HÀN QUỐC', 2019, 'Giấy', 'HD', 'TẦNG 1 CD'),
('f96c612a-2dc3-445a-84c4-243cda2f22ad', 'MÁY BẾ TỜ RỜI', 'MK1060MF', 'BẾ TỜ RỜI - 01', '-1', '2023-07-14 09:51:00', '2023-07-14 09:51:00', '16.9KW', 'TRUNG QUỐC', 2021, 'Giấy', 'HD', 'XƯỞNG GIẤY MƠI'),
('fcbd5fc2-e4d7-4791-a27f-5346f27356c9', 'MÁY GẤP HỘP', 'SIGNATURE ACE 110CS', 'GẤP HỘP - 03', '-1', '2023-07-14 09:51:00', '2023-07-14 09:51:00', '22KW', 'HÀN QUỐC', 2019, 'Giấy', 'HD', 'TẦNG 1 CD');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `machine_logs`
--

CREATE TABLE `machine_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `machine_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `machine_logs`
--

INSERT INTO `machine_logs` (`id`, `machine_id`, `info`, `created_at`, `updated_at`) VALUES
(1, 'SH', '{\"start_time\":\"2022-10-26 14:12:44\",\"end_time\":\"2022-10-26 14:12:50\",\"reason_id\":\"[1,2]\",\"result\":\"\\u0110\\u00e3 x\\u1eed l\\u00fd xong\"}', '2022-10-26 21:12:44', '2022-10-27 14:19:34'),
(2, 'SH', '{\"start_time\":\"2022-10-26 14:13:06\",\"end_time\":\"2022-10-26 14:13:11\"}', '2022-10-26 21:13:06', '2022-10-26 21:13:11'),
(3, 'FR200', '{\"start_time\":\"2022-10-26 14:13:25\",\"end_time\":\"2022-10-27 05:07:03\"}', '2022-10-26 21:13:25', '2022-10-27 12:07:03'),
(4, 'SH', '{\"start_time\":\"2022-10-27 05:06:22\",\"reason_id\":[{\"id\":5,\"name\":\"H\\u1ecdp\",\"note\":null,\"created_at\":\"2022-10-27T12:17:14.000000Z\",\"updated_at\":\"2022-10-27T12:17:14.000000Z\",\"checked\":false},{\"id\":8,\"name\":\"Thi\\u1ebfu ng\\u01b0\\u1eddi\",\"note\":null,\"created_at\":\"2022-10-27T12:17:14.000000Z\",\"updated_at\":\"2022-10-27T12:17:14.000000Z\",\"checked\":false}],\"result\":\"kh\\u00f4ng\",\"end_time\":\"2022-10-29 15:14:01\"}', '2022-10-27 12:06:22', '2022-10-29 22:14:01'),
(5, 'FR200', '{\"start_time\":\"2022-10-27 05:07:08\",\"end_time\":\"2022-10-27 05:07:12\",\"reason_id\":[{\"id\":1,\"name\":\"C\\u00fap \\u0111i\\u1ec7n\",\"note\":null,\"created_at\":\"2022-10-27T12:17:14.000000Z\",\"updated_at\":\"2022-10-27T12:17:14.000000Z\",\"checked\":false},{\"id\":3,\"name\":\"B\\u1ea3o tr\\u00ec (\\u0111\\u1ecbnh k\\u1ef3, AM tu\\u1ea7n,..)\",\"note\":null,\"created_at\":\"2022-10-27T12:17:14.000000Z\",\"updated_at\":\"2022-10-27T12:17:14.000000Z\",\"checked\":false},{\"id\":5,\"name\":\"H\\u1ecdp\",\"note\":null,\"created_at\":\"2022-10-27T12:17:14.000000Z\",\"updated_at\":\"2022-10-27T12:17:14.000000Z\",\"checked\":false}],\"result\":\"\\u0110\\u00e3 x\\u1eed l\\u00fd xong\"}', '2022-10-27 12:07:08', '2022-10-27 15:40:07'),
(6, 'FR200', '{\"start_time\":\"2022-10-28 05:12:40\",\"end_time\":\"2022-10-28 05:12:49\"}', '2022-10-28 12:12:40', '2022-10-28 12:12:49'),
(7, 'FR200', '{\"start_time\":\"2022-10-29 15:13:21\",\"end_time\":\"2022-10-29 15:13:32\",\"reason_id\":[{\"id\":1,\"name\":\"C\\u00fap \\u0111i\\u1ec7n\",\"note\":null,\"created_at\":\"2022-10-27T05:17:14.000000Z\",\"updated_at\":\"2022-10-27T05:17:14.000000Z\",\"checked\":false},{\"id\":3,\"name\":\"B\\u1ea3o tr\\u00ec (\\u0111\\u1ecbnh k\\u1ef3, AM tu\\u1ea7n,..)\",\"note\":null,\"created_at\":\"2022-10-27T05:17:14.000000Z\",\"updated_at\":\"2022-10-27T05:17:14.000000Z\",\"checked\":false},{\"id\":5,\"name\":\"H\\u1ecdp\",\"note\":null,\"created_at\":\"2022-10-27T05:17:14.000000Z\",\"updated_at\":\"2022-10-27T05:17:14.000000Z\",\"checked\":false}],\"result\":\"\\u0110\\u00e3 x\\u1eed l\\u00fd xong\"}', '2022-10-29 22:13:21', '2022-10-29 22:14:55'),
(8, 'SH', '{\"start_time\":\"2022-10-29 15:14:06\",\"end_time\":\"2022-10-29 15:14:17\"}', '2022-10-29 22:14:06', '2022-10-29 22:14:17'),
(9, 'SH', '{\"start_time\":\"2022-10-31 09:28:17\",\"end_time\":\"2022-10-31 09:28:24\"}', '2022-10-31 16:28:17', '2022-10-31 16:28:24'),
(10, 'FR200', '{\"start_time\":\"2022-10-31 09:28:45\",\"end_time\":\"2022-10-31 09:28:50\",\"reason_id\":[],\"result\":null}', '2022-10-31 16:28:45', '2022-10-31 20:46:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `machine_parameters`
--

CREATE TABLE `machine_parameters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `machine_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `machine_parameters`
--

INSERT INTO `machine_parameters` (`id`, `machine_id`, `info`, `created_at`, `updated_at`) VALUES
(1, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-26 21:12:37', '2022-10-26 21:12:37'),
(2, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-26 21:12:44', '2022-10-26 21:12:44'),
(3, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-26 21:12:50', '2022-10-26 21:12:50'),
(4, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-26 21:13:06', '2022-10-26 21:13:06'),
(5, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-26 21:13:11', '2022-10-26 21:13:11'),
(6, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-26 21:13:21', '2022-10-26 21:13:21'),
(7, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-26 21:13:25', '2022-10-26 21:13:25'),
(8, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-27 12:06:17', '2022-10-27 12:06:17'),
(9, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-27 12:06:22', '2022-10-27 12:06:22'),
(10, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-27 12:06:53', '2022-10-27 12:06:53'),
(11, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-27 12:07:03', '2022-10-27 12:07:03'),
(12, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-27 12:07:08', '2022-10-27 12:07:08'),
(13, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-27 12:07:12', '2022-10-27 12:07:12'),
(14, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-28 12:12:36', '2022-10-28 12:12:36'),
(15, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-28 12:12:40', '2022-10-28 12:12:40'),
(16, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-28 12:12:49', '2022-10-28 12:12:49'),
(17, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-29 22:13:17', '2022-10-29 22:13:17'),
(18, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-29 22:13:21', '2022-10-29 22:13:21'),
(19, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-29 22:13:32', '2022-10-29 22:13:32'),
(20, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-29 22:14:01', '2022-10-29 22:14:01'),
(21, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-29 22:14:06', '2022-10-29 22:14:06'),
(22, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-29 22:14:17', '2022-10-29 22:14:17'),
(23, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-31 16:28:11', '2022-10-31 16:28:11'),
(24, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-31 16:28:17', '2022-10-31 16:28:17'),
(25, 'SH', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-31 16:28:24', '2022-10-31 16:28:24'),
(26, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-31 16:28:41', '2022-10-31 16:28:41'),
(27, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"0\"}', '2022-10-31 16:28:45', '2022-10-31 16:28:45'),
(28, 'FR200', '{\"event\":\"10\\/10\\/2022 10:25\",\"mssp\":\"0\",\"lsx\":\"0\",\"tocdo\":\"150\"}', '2022-10-31 16:28:50', '2022-10-31 16:28:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material`
--

CREATE TABLE `material` (
  `id` varchar(36) NOT NULL,
  `code` varchar(36) NOT NULL,
  `ten` varchar(191) NOT NULL,
  `thong_so` varchar(191) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `material`
--

INSERT INTO `material` (`id`, `code`, `ten`, `thong_so`, `created_at`, `updated_at`) VALUES
('GIAY0137', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 550*690 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:32:07', '2023-07-29 13:32:07'),
('GIAY0151', 'SRIV ', 'Giấy (F) SRIV-VE270G, định lượng 270g/m2, khổ 788mm*500mm', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"270\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0152', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 510*430 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0169', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 690*440 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0175', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 450*550 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0222', 'SRIV ', 'Giấy (F )SRIV-350G(R), ĐL 350g/m2, KT: 630mm*475mm', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"350\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0225', 'SRIV ', 'Giấy (F) SRIV-VE270G, định lượng 270g/m2, khổ 788mm*500mm (HANSOL- Có FSC)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"270\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0229', 'SRIV ', 'Giấy (F) SRIV-350G, định lượng 350g/m2, KT: 550*560 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"350\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0231', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 550*520 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0236', 'SRIV ', 'Giấy (F) SRIV-350G, định lượng 350g/m2, KT: 610*450 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"350\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0237', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 550*425 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:30:13', '2023-07-29 13:30:13'),
('GIAY0245', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 680*440 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 11:29:22', '2023-07-29 11:29:22'),
('GIAY0933', 'SRIV ', 'Giấy (F) SRIV-VE270G, định lượng 270g/m2, khổ 788mm*480mm', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"270\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0936', 'SRIV ', 'Giấy (F) SRIV-240G, định lượng 240g/m2, KT: 420*650 (mm)', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"240\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51'),
('GIAY0950', 'SRIV ', 'Giấy (F )SRIV-350G(R), ĐL 350g/m2, KT: 690mm*450mm', '{\"mau\":\"Tr\\u1eafng\",\"DL\":\"350\"}', '2023-07-29 13:18:51', '2023-07-29 13:18:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material_logs`
--

CREATE TABLE `material_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `cell_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2016_01_04_173148_create_admin_tables', 1),
(4, '2019_08_19_000000_create_failed_jobs_table', 1),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(6, '2022_10_10_081856_create_production_plans_table', 1),
(7, '2022_10_10_081942_create_products_table', 1),
(8, '2022_10_10_082034_create_descriptions_table', 1),
(9, '2022_10_10_082113_create_machines_table', 1),
(10, '2022_10_10_084322_create_test_criterias_table', 1),
(11, '2022_10_10_084728_create_lines_table', 1),
(12, '2022_10_10_085250_create_criterias_values_table', 1),
(13, '2022_10_11_023650_create_ware_houses_table', 1),
(14, '2022_10_11_024011_create_shefts_table', 1),
(15, '2022_10_11_024324_create_cells_table', 1),
(16, '2022_10_11_024844_create_units_table', 1),
(17, '2022_10_11_035255_create_errors_table', 1),
(18, '2022_10_12_033548_create_machine_parameters_table', 1),
(19, '2022_10_14_035012_create_l_s_x_logs_table', 1),
(20, '2022_10_14_073222_create_unusual_types_table', 1),
(21, '2022_10_24_062350_add_line_id_machine', 2),
(22, '2022_10_24_080851_create_ware_house_logs_table', 2),
(23, '2022_10_25_081719_create_machine_logs_table', 3),
(24, '2022_10_25_103216_create_colors_table', 3),
(25, '2022_10_26_021005_create_material_logs_table', 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `created_at`, `updated_at`) VALUES
(423, 'App\\Models\\CustomUser', 3, '', '2b6eeed4696e329585263bfa7a46ab5d58779f25607cf2f48af855e75fe60f27', '[\"*\"]', '2022-10-30 06:37:07', '2022-10-27 22:27:05', '2022-10-30 06:37:07'),
(473, 'App\\Models\\CustomUser', 5, '', '92f7d0f12b7ad940321a8bd7aeb39babdea72ebe2fe9b1cba0190991e60df3be', '[\"*\"]', '2022-11-01 18:14:09', '2022-10-28 10:42:07', '2022-11-01 18:14:09'),
(511, 'App\\Models\\CustomUser', 4, '', '2b1758f2e3e2a4a1123c39e018015d74b9ccbbecc3465d8d18a34fb3b35d0c61', '[\"*\"]', '2022-11-01 20:15:08', '2022-10-28 13:57:09', '2022-11-01 20:15:08'),
(519, 'App\\Models\\CustomUser', 2, '', '46245b59a3eeb8539dca5365ea086916db897c8078875bc97510a13148e4395e', '[\"*\"]', '2022-10-28 14:29:31', '2022-10-28 14:22:42', '2022-10-28 14:29:31'),
(555, 'App\\Models\\CustomUser', 1, '', '84259773371ef35f5596c01aaa15cf89e8957700f4af9de8375628c9855a8c05', '[\"*\"]', '2022-10-28 15:46:27', '2022-10-28 15:43:34', '2022-10-28 15:46:27'),
(556, 'App\\Models\\CustomUser', 1, '', '8e0fd5640004b69aa567b0e796385cf3673dfe7b290d95dc494b2609d2cdf6fa', '[\"*\"]', '2022-10-31 19:49:24', '2022-10-28 15:44:50', '2022-10-31 19:49:24'),
(557, 'App\\Models\\CustomUser', 1, '', '0d9bd46e72e539a918349d2539b719ad1279a37d8184f4eba46176568899d0c1', '[\"*\"]', '2022-10-28 15:56:29', '2022-10-28 15:55:51', '2022-10-28 15:56:29'),
(558, 'App\\Models\\CustomUser', 1, '', '6e61495f21b25b786c16eb4b94268dd539a629dfb7010cd79df2ebc4b79bd520', '[\"*\"]', '2022-11-01 14:07:58', '2022-10-28 15:59:08', '2022-11-01 14:07:58'),
(559, 'App\\Models\\CustomUser', 1, '', '04ee02a7acb8290bace1604a7165aed883531b1212a42231509fde2fcdc6a392', '[\"*\"]', NULL, '2022-10-28 16:08:17', '2022-10-28 16:08:17'),
(560, 'App\\Models\\CustomUser', 1, '', 'a29fc1fbf7d33b0218255997e27a54f55a9d166c43b5c8297c00fe5b51e87fb8', '[\"*\"]', '2022-10-28 16:24:22', '2022-10-28 16:08:17', '2022-10-28 16:24:22'),
(561, 'App\\Models\\CustomUser', 1, '', '8c39f61c84cfa8cef5cfe58c3bbd0bd1d25a4a59468afd5d459e7277a4858dc9', '[\"*\"]', '2022-11-01 19:38:25', '2022-10-28 16:08:46', '2022-11-01 19:38:25'),
(562, 'App\\Models\\CustomUser', 1, '', '42a952c5e3e6979628422bbc40f41a4ec11e2bd690922adf88063fcb34df0aca', '[\"*\"]', '2022-11-01 18:12:29', '2022-10-28 16:59:59', '2022-11-01 18:12:29'),
(563, 'App\\Models\\CustomUser', 1, '', '1120b2b232912114d6f77e3428d9dd209c79ee73c91ffef2443891b438aa586c', '[\"*\"]', '2022-11-01 00:08:13', '2022-10-28 19:44:34', '2022-11-01 00:08:13'),
(564, 'App\\Models\\CustomUser', 1, '', '27553307529c5cff732e213e3e34d3bf82951079d18e1cf5bb30707ae75cd68c', '[\"*\"]', '2022-10-28 22:17:10', '2022-10-28 22:16:36', '2022-10-28 22:17:10'),
(565, 'App\\Models\\CustomUser', 1, '', '1d45c32561875b8633643cff05f241872a7af1c7ccf4592ffbd67e9f5109b17b', '[\"*\"]', '2022-10-29 00:05:20', '2022-10-29 00:01:35', '2022-10-29 00:05:20'),
(566, 'App\\Models\\CustomUser', 1, '', '7155036f1e25d57b20b605cb2f821d5075cf1f3b88094291c0e4f02a56c89852', '[\"*\"]', '2022-10-29 23:16:38', '2022-10-29 00:13:05', '2022-10-29 23:16:38'),
(567, 'App\\Models\\CustomUser', 1, '', '32fba1bf4c94165eb4161df6ab482b93beb8ddbb499f13f137534b5cd5f54a87', '[\"*\"]', '2022-11-01 16:41:44', '2022-10-29 07:40:20', '2022-11-01 16:41:44'),
(568, 'App\\Models\\CustomUser', 1, '', '1269d0a0380d2ccd43c36a4fa70ef5f0e07e5ae474b0e52d210c5c603a966b1a', '[\"*\"]', '2022-11-01 16:27:33', '2022-10-29 07:55:07', '2022-11-01 16:27:33'),
(569, 'App\\Models\\CustomUser', 1, '', 'cdcf838413912eff685ac4b07e49e8c21e554c33272e011e834eccadf1fed2ba', '[\"*\"]', '2022-10-29 08:16:27', '2022-10-29 08:02:16', '2022-10-29 08:16:27'),
(570, 'App\\Models\\CustomUser', 1, '', '8b5f86f388c9760f607e9653f2d6c9636bd20e2b0c45a7bc849bd2e018d18c96', '[\"*\"]', '2022-11-01 19:18:31', '2022-10-29 08:07:51', '2022-11-01 19:18:31'),
(571, 'App\\Models\\CustomUser', 1, '', '86a628a098b8f790ac15c9d7717202c5ac04dfbe8f19d2e7a27f8b72f5a8eb0a', '[\"*\"]', '2022-10-29 08:25:08', '2022-10-29 08:24:32', '2022-10-29 08:25:08'),
(572, 'App\\Models\\CustomUser', 1, '', '85377547fb58df9ca16360d30df618133fde8168ad624adf5fb95f9270f1798b', '[\"*\"]', '2022-10-29 08:28:58', '2022-10-29 08:28:30', '2022-10-29 08:28:58'),
(573, 'App\\Models\\CustomUser', 1, '', '02048ee461d79ba2c02899c14b606c3770b38f59b241092501241bcb04a6e6f7', '[\"*\"]', '2022-10-29 22:20:47', '2022-10-29 08:43:54', '2022-10-29 22:20:47'),
(574, 'App\\Models\\CustomUser', 2, '', 'ff9c8e5c39eeac07ee0b5ca2e1530d1f5320fe711dd36e962ad77bc8d6fc9c1b', '[\"*\"]', '2022-10-29 10:06:16', '2022-10-29 08:52:12', '2022-10-29 10:06:16'),
(575, 'App\\Models\\CustomUser', 1, '', 'd6e8ef8a848d33a4ae17f3696f4370969c7d1eef0f7c0758d78d24cd30f955ef', '[\"*\"]', '2022-10-30 20:38:44', '2022-10-29 08:52:56', '2022-10-30 20:38:44'),
(576, 'App\\Models\\CustomUser', 1, '', 'b1c4d186384e8fba659e55e321b72f94dc278b4cc89d134b3e05151bac119cae', '[\"*\"]', '2022-10-29 09:13:25', '2022-10-29 09:13:14', '2022-10-29 09:13:25'),
(577, 'App\\Models\\CustomUser', 1, '', '71ef21913db29740df5060ed241a3b0d732cb3679ec38ea113ad5030b29a6c5b', '[\"*\"]', '2022-11-01 16:37:35', '2022-10-29 09:21:20', '2022-11-01 16:37:35'),
(578, 'App\\Models\\CustomUser', 1, '', '234ae57ccf0a4095871a47cf4e24412ee69e413f99cfecfa97cc0749cf82c108', '[\"*\"]', '2022-11-01 16:30:03', '2022-10-29 09:22:54', '2022-11-01 16:30:03'),
(579, 'App\\Models\\CustomUser', 1, '', '7bae1818002ea9c6fe1515b45661fce1e405a20fbeeffc824b1a333836994680', '[\"*\"]', '2022-11-01 19:37:02', '2022-10-29 09:27:33', '2022-11-01 19:37:02'),
(580, 'App\\Models\\CustomUser', 1, '', '90fd4a5889d6d4022271f7a51550319852c5edb078bf9458d347f94ac97a43f9', '[\"*\"]', '2022-10-29 09:35:06', '2022-10-29 09:32:53', '2022-10-29 09:35:06'),
(581, 'App\\Models\\CustomUser', 5, '', 'ea5af496001996e60dbceabe8d01772ce8993826b3bd69b92baf38792f64ed7a', '[\"*\"]', '2022-10-29 09:36:41', '2022-10-29 09:36:11', '2022-10-29 09:36:41'),
(582, 'App\\Models\\CustomUser', 1, '', '8bd4f34aecd904b4dc1628b32d6fea6663fb5287080537efbb21f1b2492c6ea6', '[\"*\"]', '2022-10-31 01:09:47', '2022-10-29 09:36:22', '2022-10-31 01:09:47'),
(583, 'App\\Models\\CustomUser', 1, '', 'a0070b7ed38cf73df49716d3f734344aa2ac75fda918cd0fb52c9ee5db543d7b', '[\"*\"]', '2022-11-01 17:53:38', '2022-10-29 17:23:17', '2022-11-01 17:53:38'),
(584, 'App\\Models\\CustomUser', 1, '', '9826c0d61bc8115938cfc21bed2420d58aeb4fffc1c85ad32c29c41a62b0504b', '[\"*\"]', '2022-10-31 17:12:14', '2022-10-29 18:23:47', '2022-10-31 17:12:14'),
(585, 'App\\Models\\CustomUser', 2, '', 'cadcecfa13b353485c51bd999a4bce7d1a526f49a53bdf9cfb2891d4925860e7', '[\"*\"]', '2022-10-29 19:03:40', '2022-10-29 18:55:48', '2022-10-29 19:03:40'),
(586, 'App\\Models\\CustomUser', 1, '', '9233f0a700de58f9a4b3ed0adca159f77b2525507ee7aa02aa2cefaad52ab3a6', '[\"*\"]', '2022-10-30 20:39:09', '2022-10-30 20:39:05', '2022-10-30 20:39:09'),
(587, 'App\\Models\\CustomUser', 2, '', 'dd5d49b8ca5d0973263b86f21986a8ac0587713abb94b4769526285b32fba224', '[\"*\"]', '2022-10-31 02:25:04', '2022-10-30 20:39:43', '2022-10-31 02:25:04'),
(588, 'App\\Models\\CustomUser', 1, '', '00f205ebebbfe23a825764d8a4b95095fb80421aa73eba6aa6f8a93d93b47f25', '[\"*\"]', '2022-10-31 16:00:47', '2022-10-30 20:40:15', '2022-10-31 16:00:47'),
(589, 'App\\Models\\CustomUser', 2, '', '5f2c39034a607108c7095f9cec5f411386b2594db4010157b028a2a237bdf891', '[\"*\"]', '2022-10-31 16:05:53', '2022-10-31 02:41:46', '2022-10-31 16:05:53'),
(590, 'App\\Models\\CustomUser', 1, '', '5948d9a12115249f2787d7481e2c41e43aafc3f0b64eb02fa1eb0f20debd7192', '[\"*\"]', '2022-11-01 17:59:21', '2022-10-31 16:01:49', '2022-11-01 17:59:21'),
(591, 'App\\Models\\CustomUser', 2, '', '54fa13a0342c1dfba06bbc45b6d33b3fef43c1678a9de02ea9228bd2c3f69291', '[\"*\"]', '2022-11-01 03:58:39', '2022-10-31 16:06:41', '2022-11-01 03:58:39'),
(592, 'App\\Models\\CustomUser', 1, '', '5b401dc95e1bdb26223ca7b90fda34c267cab3cfee3e9742c26e061ce449e422', '[\"*\"]', '2022-10-31 16:30:58', '2022-10-31 16:30:01', '2022-10-31 16:30:58'),
(593, 'App\\Models\\CustomUser', 1, '', 'd02e42f3db64f0ced50bb2f8ecc7f97b10bfdcbc07e966478777c15922b3de7b', '[\"*\"]', NULL, '2022-10-31 16:38:23', '2022-10-31 16:38:23'),
(594, 'App\\Models\\CustomUser', 1, '', 'c97df4c71636351d70c6eb36e7bcc7d79aee53fca0343f85143fbbf7b32350d5', '[\"*\"]', NULL, '2022-10-31 16:38:23', '2022-10-31 16:38:23'),
(595, 'App\\Models\\CustomUser', 1, '', 'ab37b2c11ea1ed1ae3e3efb008c7071c1f7190ee58038c10104000d6026f7965', '[\"*\"]', NULL, '2022-10-31 16:38:23', '2022-10-31 16:38:23'),
(596, 'App\\Models\\CustomUser', 1, '', '46c29b144b2cab7ab2bae4bd064f11593dc0bb17344a56c4ff80c8af6c23a0e2', '[\"*\"]', '2022-10-31 17:00:02', '2022-10-31 16:43:53', '2022-10-31 17:00:02'),
(597, 'App\\Models\\CustomUser', 1, '', 'f478fbc209324c6fc46d3583eb677af3cc974a2a479ad5d1bcadd21702c01baf', '[\"*\"]', '2022-10-31 23:11:19', '2022-10-31 16:47:18', '2022-10-31 23:11:19'),
(598, 'App\\Models\\CustomUser', 1, '', '1a003cb803691cb3a7bd25a57320c25cf64675f2059ed2a21794d297c13bde8d', '[\"*\"]', '2022-11-01 18:35:57', '2022-10-31 16:52:55', '2022-11-01 18:35:57'),
(599, 'App\\Models\\CustomUser', 1, '', '7964cdd4a6ce1e0ead829fbac91b4774da330905c53f90e60e2b09d530539ff2', '[\"*\"]', '2022-11-01 17:01:55', '2022-10-31 17:07:47', '2022-11-01 17:01:55'),
(600, 'App\\Models\\CustomUser', 1, '', '578659933bfb701670e906b4c09a686900bba98f853023691291faf540526c30', '[\"*\"]', '2022-10-31 17:10:22', '2022-10-31 17:08:56', '2022-10-31 17:10:22'),
(601, 'App\\Models\\CustomUser', 1, '', 'cc82100e5aee6f2d2e8cdd048bc0c1d4828525e45c7386d9a975ae367a052e8f', '[\"*\"]', '2022-11-01 16:43:36', '2022-10-31 17:47:18', '2022-11-01 16:43:36'),
(602, 'App\\Models\\CustomUser', 1, '', 'a2c7dcb1d484f61f91041ed814850b54e1be2378c852aa9b4632ca50ed9a3faa', '[\"*\"]', '2022-11-01 16:15:42', '2022-10-31 17:53:04', '2022-11-01 16:15:42'),
(603, 'App\\Models\\CustomUser', 1, '', 'ccd6f2068776a51ee26dda1cf9d54e0945da45d8bd56c82b98b201d25b2688b3', '[\"*\"]', '2022-10-31 18:47:35', '2022-10-31 18:45:46', '2022-10-31 18:47:35'),
(604, 'App\\Models\\CustomUser', 1, '', '7b05277d0af4e80224cfa6b8138eabc888b2d77f5c75ca61f655a6a3fc11a15e', '[\"*\"]', '2022-10-31 21:07:58', '2022-10-31 19:17:00', '2022-10-31 21:07:58'),
(605, 'App\\Models\\CustomUser', 1, '', '36e00a1281087f9c830113d0793283b59954f865fda5641e926f7a81aff3ee23', '[\"*\"]', '2022-10-31 19:22:53', '2022-10-31 19:17:42', '2022-10-31 19:22:53'),
(606, 'App\\Models\\CustomUser', 1, '', '30c02478fb70f9bb1961497174d958752c28cb26fa1b49872d254041543b8ae2', '[\"*\"]', '2022-10-31 19:23:17', '2022-10-31 19:18:58', '2022-10-31 19:23:17'),
(607, 'App\\Models\\CustomUser', 1, '', '445528d24de8aa346afe2646a43da59c4c41d9204b9ccf2221d8d3a79e25ea8b', '[\"*\"]', '2022-10-31 19:26:04', '2022-10-31 19:20:57', '2022-10-31 19:26:04'),
(608, 'App\\Models\\CustomUser', 1, '', '4dbdd33ea691687deaf7d16611708bb7e3805abb5fbbed35c4b3d6d558139aa2', '[\"*\"]', '2022-11-01 17:39:12', '2022-10-31 19:23:55', '2022-11-01 17:39:12'),
(609, 'App\\Models\\CustomUser', 1, '', '6f6f12e9e870cf7747ed182d0ccba278eb9bbbcdf07b64c8448bfce8339c14cb', '[\"*\"]', NULL, '2022-10-31 19:24:27', '2022-10-31 19:24:27'),
(610, 'App\\Models\\CustomUser', 1, '', '8327854d74985b2bbb2aba07fc49104f069eb073bc6e76225edaf65c534ad47b', '[\"*\"]', '2022-11-01 17:35:20', '2022-10-31 19:24:27', '2022-11-01 17:35:20'),
(611, 'App\\Models\\CustomUser', 1, '', 'b7c48a18116821fb94fcba35baef9bc4e81761917a50493e70464e111c31a80e', '[\"*\"]', '2022-11-01 17:48:38', '2022-10-31 19:24:36', '2022-11-01 17:48:38'),
(612, 'App\\Models\\CustomUser', 1, '', '5cc6707ed374494468522a5e00c8036c0d1745365a712f86fc6c2fec211e3379', '[\"*\"]', '2022-11-01 17:45:02', '2022-10-31 19:25:17', '2022-11-01 17:45:02'),
(613, 'App\\Models\\CustomUser', 1, '', '88831c128bed660cbc912fd41c9d60b9875d5b0b2645c026680b426bbf60ff47', '[\"*\"]', '2022-10-31 19:32:37', '2022-10-31 19:26:31', '2022-10-31 19:32:37'),
(614, 'App\\Models\\CustomUser', 1, '', '119f1e89025d9faba68bc87873c54945017193bd9f52dbe61daa5323193d71fc', '[\"*\"]', '2022-10-31 19:44:08', '2022-10-31 19:26:57', '2022-10-31 19:44:08'),
(615, 'App\\Models\\CustomUser', 1, '', '57f2d14dfdbc20fff60656229f902eda0d8a0e1b2f0f4b97135b814ad1aad233', '[\"*\"]', '2022-10-31 19:27:18', '2022-10-31 19:26:58', '2022-10-31 19:27:18'),
(616, 'App\\Models\\CustomUser', 1, '', '707f030a3a303de4220033427c005a60a6e818d2dbd1a656a3e1bbb3cee63e3f', '[\"*\"]', '2022-10-31 21:19:57', '2022-10-31 19:28:15', '2022-10-31 21:19:57'),
(617, 'App\\Models\\CustomUser', 1, '', '0e4f678364675be0114ae92d74e71d2fb58569a6e22cfacaa5adf2ffd17968b2', '[\"*\"]', '2022-10-31 21:53:13', '2022-10-31 19:30:16', '2022-10-31 21:53:13'),
(618, 'App\\Models\\CustomUser', 1, '', '55e7e7b94acfb3cf65c85015312a3e3fd607ce588174aa5b7c80e25cdf24b0aa', '[\"*\"]', '2022-10-31 19:44:11', '2022-10-31 19:44:11', '2022-10-31 19:44:11'),
(619, 'App\\Models\\CustomUser', 1, '', '878ce898fbcdefd2beb5053809a28fc51f4660d9f99ebc83c570e198a2560905', '[\"*\"]', '2022-11-01 03:48:48', '2022-10-31 20:00:35', '2022-11-01 03:48:48'),
(620, 'App\\Models\\CustomUser', 1, '', 'f3385000fcef4a1d337c57fdb5aa3d47536cd72998230a3cf0595627da07b107', '[\"*\"]', '2022-11-01 17:52:23', '2022-10-31 20:10:09', '2022-11-01 17:52:23'),
(621, 'App\\Models\\CustomUser', 1, '', 'c7ea9a67e13dbb9567b7b4b3cab7e8eb9b591c1eaf7025a48a553c2b71b3316e', '[\"*\"]', '2022-11-01 17:32:55', '2022-10-31 20:12:22', '2022-11-01 17:32:55'),
(622, 'App\\Models\\CustomUser', 1, '', '4ad910ebf4ddc96ed720188aab9a5f8c8e7d9235dfa2f804b28c11dcb3ac4759', '[\"*\"]', NULL, '2022-10-31 20:16:53', '2022-10-31 20:16:53'),
(623, 'App\\Models\\CustomUser', 1, '', '553f0735fd5021b194a86a566d5024bdbe017451a142f7b54379459d61d80089', '[\"*\"]', '2022-10-31 22:50:19', '2022-10-31 20:17:44', '2022-10-31 22:50:19'),
(624, 'App\\Models\\CustomUser', 1, '', '2f4b1175e4e62d47d7bfe5b9dd1964d275113e3c2ee68a684cc703b01e6e450e', '[\"*\"]', '2022-10-31 20:31:39', '2022-10-31 20:31:36', '2022-10-31 20:31:39'),
(625, 'App\\Models\\CustomUser', 1, '', '0e416324876041830dcf6286884220d7312e15a1a5744ac7de8073ad123fba3d', '[\"*\"]', '2022-10-31 21:15:34', '2022-10-31 20:31:36', '2022-10-31 21:15:34'),
(626, 'App\\Models\\CustomUser', 1, '', '96b21f4ba818ced590f841e8ec58683ac46973ebc5a980216f9dbf68d7b21ca3', '[\"*\"]', NULL, '2022-10-31 20:31:36', '2022-10-31 20:31:36'),
(627, 'App\\Models\\CustomUser', 1, '', '5f994ea19b5d772aa5d7bb303a7958989824eb22a7c1b52664d1a16796a47a83', '[\"*\"]', NULL, '2022-10-31 20:31:36', '2022-10-31 20:31:36'),
(628, 'App\\Models\\CustomUser', 1, '', 'be13034d5da02f9d51b586e0a282cbb9c73e9a9e556fb4a3e42cd840896580d8', '[\"*\"]', NULL, '2022-10-31 20:31:37', '2022-10-31 20:31:37'),
(629, 'App\\Models\\CustomUser', 1, '', 'bdad834f056f7ea943ef1833c989e863fb807ff130a7ac7bb5c346d53852f134', '[\"*\"]', NULL, '2022-10-31 20:31:37', '2022-10-31 20:31:37'),
(630, 'App\\Models\\CustomUser', 1, '', 'aa7db820f1869838781075ca61b3a67bfbe1a1d7b3c978f22312c24ed6910911', '[\"*\"]', '2022-10-31 20:52:12', '2022-10-31 20:31:37', '2022-10-31 20:52:12'),
(631, 'App\\Models\\CustomUser', 1, '', '86c79d4cc472a2a168d4004995e9962c3130a63072940c835b388eaeadc7ee27', '[\"*\"]', '2022-10-31 22:09:20', '2022-10-31 20:31:40', '2022-10-31 22:09:20'),
(632, 'App\\Models\\CustomUser', 1, '', '84a22f7847a68baad4980d59e95c5fe6984c86e1f506304881022adaf0d9dac7', '[\"*\"]', '2022-10-31 20:55:49', '2022-10-31 20:31:40', '2022-10-31 20:55:49'),
(633, 'App\\Models\\CustomUser', 1, '', '971ff07fa0322bfca55ff3e3ebe6a5cb9ff6b2627cbfa45f52dd1b1be6deb96c', '[\"*\"]', '2022-10-31 20:58:54', '2022-10-31 20:32:07', '2022-10-31 20:58:54'),
(634, 'App\\Models\\CustomUser', 1, '', '20fd4264b5f014a72290ea69a00057b6c4e643dbae00450902ed54c2abb5e27d', '[\"*\"]', NULL, '2022-10-31 20:32:08', '2022-10-31 20:32:08'),
(635, 'App\\Models\\CustomUser', 1, '', '20d62996c21f370ff7b0ab274490acd44771faf944978c2e88c6764f9756402a', '[\"*\"]', NULL, '2022-10-31 20:32:09', '2022-10-31 20:32:09'),
(636, 'App\\Models\\CustomUser', 1, '', '64e4f491f21bf5787c75e3fb52c5afcb671893cc1f848144517fb031b689f772', '[\"*\"]', NULL, '2022-10-31 20:32:10', '2022-10-31 20:32:10'),
(637, 'App\\Models\\CustomUser', 1, '', '99b48958bc03b26c34563b55d5fb3e20d377406b92f02fdc2d424f7968672c3d', '[\"*\"]', '2022-10-31 21:32:53', '2022-10-31 20:32:10', '2022-10-31 21:32:53'),
(638, 'App\\Models\\CustomUser', 1, '', '2d176409168699c5a58c927bb4527f4dbd2b57cfd4c7ab5ac853909dee2183d6', '[\"*\"]', '2022-10-31 20:49:52', '2022-10-31 20:32:10', '2022-10-31 20:49:52'),
(639, 'App\\Models\\CustomUser', 1, '', '18a9311473ed292100a8d1552d7f3cda066ffeef6b3543e5f80e8b9be373f1da', '[\"*\"]', '2022-10-31 20:42:37', '2022-10-31 20:32:11', '2022-10-31 20:42:37'),
(640, 'App\\Models\\CustomUser', 1, '', 'fe7cecb7e727fa1aa5eb81a5d45bd7d7703e870ab7e646a30a4efcf40adb2661', '[\"*\"]', NULL, '2022-10-31 20:32:22', '2022-10-31 20:32:22'),
(641, 'App\\Models\\CustomUser', 1, '', '6c1df4155a58c960e8d4628dc1d9b24d100314e1768a3f7991b9015001ebf2b5', '[\"*\"]', '2022-11-01 06:17:31', '2022-10-31 20:32:23', '2022-11-01 06:17:31'),
(642, 'App\\Models\\CustomUser', 1, '', '90a090ad7a6b576fde871958de23b9b80c3499d3028b0b174afaa2268e65cd55', '[\"*\"]', '2022-10-31 20:34:51', '2022-10-31 20:32:27', '2022-10-31 20:34:51'),
(643, 'App\\Models\\CustomUser', 1, '', '9053c9e016ee8ccc65f9300ac5de1c098b4dbe621f2f9caaeddda90650f8bdb2', '[\"*\"]', '2022-10-31 21:04:40', '2022-10-31 20:32:27', '2022-10-31 21:04:40'),
(644, 'App\\Models\\CustomUser', 1, '', 'f1fcc2c0d0dcbeb4526861a0da03b1bcc1ffa967f85b8eaba81b962b9b60b42a', '[\"*\"]', '2022-10-31 20:56:52', '2022-10-31 20:32:36', '2022-10-31 20:56:52'),
(645, 'App\\Models\\CustomUser', 1, '', 'e5b8dc271b3b5d722d35808419b20ff16c915fce04d297990dc5ccef2dbded72', '[\"*\"]', '2022-10-31 21:51:15', '2022-10-31 20:32:38', '2022-10-31 21:51:15'),
(646, 'App\\Models\\CustomUser', 1, '', 'f09c162ef97c8957662f2388e31dad304ff65a037e28222606a858527fd1dbad', '[\"*\"]', '2022-10-31 21:09:20', '2022-10-31 20:32:47', '2022-10-31 21:09:20'),
(647, 'App\\Models\\CustomUser', 1, '', 'f63fa8c43624aec3a55db77138821e9828a02cab4e7d5e27dc13ca8fa76a9e69', '[\"*\"]', '2022-11-01 16:44:06', '2022-10-31 20:32:55', '2022-11-01 16:44:06'),
(648, 'App\\Models\\CustomUser', 1, '', 'fff715c508523cd94c33e76193b1a6fd2ea84f6a0c0a6c49cc7d7f770b607182', '[\"*\"]', NULL, '2022-10-31 20:33:08', '2022-10-31 20:33:08'),
(649, 'App\\Models\\CustomUser', 1, '', '96701aef5deda069d040ab7197729087986d36bd34e0df05ad145fc3a0dfa43c', '[\"*\"]', '2022-10-31 20:35:44', '2022-10-31 20:33:09', '2022-10-31 20:35:44'),
(650, 'App\\Models\\CustomUser', 1, '', '0775ec787184517a9a0efbe4fc3be349494897e1b16bdb444286b216ed12e9cd', '[\"*\"]', '2022-10-31 21:33:50', '2022-10-31 20:33:15', '2022-10-31 21:33:50'),
(651, 'App\\Models\\CustomUser', 1, '', '8768cf495980547c124e19c2038cc8ceaa06b6058cc283bb73e20e9dc5c9c234', '[\"*\"]', '2022-10-31 21:20:47', '2022-10-31 20:33:20', '2022-10-31 21:20:47'),
(652, 'App\\Models\\CustomUser', 1, '', '0e26df816e9ada3f9b75bb51f795dd2c7b1eac98d16937fe0fa675fd45bd4180', '[\"*\"]', '2022-11-01 16:54:45', '2022-10-31 20:33:23', '2022-11-01 16:54:45'),
(653, 'App\\Models\\CustomUser', 1, '', 'd65114f598c06f825a2e308e58a1dc4849acfc8569011ffed264f92948592c55', '[\"*\"]', NULL, '2022-10-31 20:33:26', '2022-10-31 20:33:26'),
(654, 'App\\Models\\CustomUser', 1, '', 'dda8420b8549ee9a04abb1fb76931157ad88dcf18dad55f89806fffcb245e79e', '[\"*\"]', '2022-10-31 21:18:57', '2022-10-31 20:33:27', '2022-10-31 21:18:57'),
(655, 'App\\Models\\CustomUser', 1, '', 'cdcfacfb54f9f9c7ed1d13718e04d0fde0d01b8ce1e4e7de6b3ca62d388880d9', '[\"*\"]', '2022-10-31 20:34:11', '2022-10-31 20:33:33', '2022-10-31 20:34:11'),
(656, 'App\\Models\\CustomUser', 1, '', 'ffb2aefcd9b62fb26f628d907ab429bc23e9e0ce714c0ae1411ecd9413d86002', '[\"*\"]', NULL, '2022-10-31 20:33:57', '2022-10-31 20:33:57'),
(657, 'App\\Models\\CustomUser', 1, '', '180f23f5b16b53fd2c3e0ab87ac9d5b1afcd99a8e9d70d2c2db3f016d6b484e1', '[\"*\"]', '2022-10-31 20:44:08', '2022-10-31 20:33:57', '2022-10-31 20:44:08'),
(658, 'App\\Models\\CustomUser', 1, '', '97f3cedb310a4ab9cf15ebbbaa56c51f06032da536bb6e2124a399bc62dd4316', '[\"*\"]', NULL, '2022-10-31 20:34:07', '2022-10-31 20:34:07'),
(659, 'App\\Models\\CustomUser', 1, '', 'a86e379bb4ec49547d25a286e34667831d12d4f98001e038d972380b5e95241a', '[\"*\"]', '2022-10-31 21:12:42', '2022-10-31 20:34:08', '2022-10-31 21:12:42'),
(660, 'App\\Models\\CustomUser', 1, '', '86f00c6020ab7a2483b29d9ad33756b0edf0441c3b31c8a2824b48917c00994f', '[\"*\"]', '2022-10-31 20:34:47', '2022-10-31 20:34:08', '2022-10-31 20:34:47'),
(661, 'App\\Models\\CustomUser', 1, '', 'e4982bd848df1e013ca975ba62a731ceb9c2893fa0bf62693d7aed8686e26f04', '[\"*\"]', '2022-10-31 20:35:09', '2022-10-31 20:34:17', '2022-10-31 20:35:09'),
(662, 'App\\Models\\CustomUser', 1, '', '392b1126beb2087c7a5609cfde7898f19491e195283ea4926ef7facf223739cd', '[\"*\"]', '2022-10-31 21:51:20', '2022-10-31 20:35:17', '2022-10-31 21:51:20'),
(663, 'App\\Models\\CustomUser', 1, '', '8e1b4cb3bd4f02d141968ae9712c869c0825cd1e0f91fdd4cc2117800052f224', '[\"*\"]', '2022-10-31 21:20:22', '2022-10-31 20:35:43', '2022-10-31 21:20:22'),
(664, 'App\\Models\\CustomUser', 1, '', '26785017476b160b33e88be898d5741e2732e69dc95bb0ce8f60d9807b7db004', '[\"*\"]', NULL, '2022-10-31 20:36:46', '2022-10-31 20:36:46'),
(665, 'App\\Models\\CustomUser', 1, '', '7111c2d835afa55b37b2e261efefbf41e02f1dd653219ad994b790ef3ea9c6a6', '[\"*\"]', '2022-10-31 22:13:07', '2022-10-31 20:36:50', '2022-10-31 22:13:07'),
(666, 'App\\Models\\CustomUser', 1, '', '7cc5a2765ee70cfccc299f94fb4d83669386a7032d9643956ba07e65d4df747b', '[\"*\"]', '2022-10-31 20:49:22', '2022-10-31 20:37:00', '2022-10-31 20:49:22'),
(667, 'App\\Models\\CustomUser', 1, '', '124a95d3a7b6e79cf0c1e6e871664cdd829ed1d130a095888464d68c5e3b0394', '[\"*\"]', '2022-10-31 20:48:10', '2022-10-31 20:37:47', '2022-10-31 20:48:10'),
(668, 'App\\Models\\CustomUser', 1, '', 'caf96cf400f654a5e9648174b20afe39557d25ab54bc6b227e2811d2fda78192', '[\"*\"]', '2022-10-31 21:12:38', '2022-10-31 20:43:07', '2022-10-31 21:12:38'),
(669, 'App\\Models\\CustomUser', 1, '', 'd33e1bba38f269ac8f1f37c9a6ebc20cb6289371d4705cc9a30d84ce076c7511', '[\"*\"]', '2022-10-31 20:52:26', '2022-10-31 20:43:52', '2022-10-31 20:52:26'),
(670, 'App\\Models\\CustomUser', 1, '', '09de946056c71e7c62215349a1ae1a579868bc874b994c5b1c6f81b712b5482c', '[\"*\"]', '2022-10-31 20:47:14', '2022-10-31 20:46:34', '2022-10-31 20:47:14'),
(671, 'App\\Models\\CustomUser', 1, '', 'c8dae9c21f336c32d0c973e05445b13ec37cd7d110aac36c09a261db0968d360', '[\"*\"]', '2022-10-31 21:12:53', '2022-10-31 20:48:15', '2022-10-31 21:12:53'),
(672, 'App\\Models\\CustomUser', 1, '', 'deec1077c1ca5195637de5033946c52e87df36511115b19fb0c5114dd5048b18', '[\"*\"]', '2022-10-31 20:52:13', '2022-10-31 20:51:40', '2022-10-31 20:52:13'),
(673, 'App\\Models\\CustomUser', 1, '', 'd7e77ecb0acd2cbbb066b7f49b5dc70d94c66c81b57b7d0ef05e918e9b67565d', '[\"*\"]', NULL, '2022-10-31 20:56:31', '2022-10-31 20:56:31'),
(674, 'App\\Models\\CustomUser', 1, '', '66f151915c378a5dda86c3f08b200f9af9fd710d1298799ec5d1007f0f4e38b5', '[\"*\"]', '2022-10-31 21:09:04', '2022-10-31 20:56:31', '2022-10-31 21:09:04'),
(675, 'App\\Models\\CustomUser', 1, '', '1b89ab8c80c6e418d82bb3735b886ac69c9ffb7c48e40db0858173df2adef0e9', '[\"*\"]', '2022-10-31 21:01:10', '2022-10-31 20:59:40', '2022-10-31 21:01:10'),
(676, 'App\\Models\\CustomUser', 1, '', '79410979b0f25dc981b957462657bc9ceb88cda2ba3807704005836cb336157e', '[\"*\"]', '2022-10-31 21:02:43', '2022-10-31 21:01:46', '2022-10-31 21:02:43'),
(677, 'App\\Models\\CustomUser', 1, '', 'ee67844fd54c31195fd2814bbc37a4ade90e1e24016b9cdee8020699f2262df9', '[\"*\"]', NULL, '2022-10-31 21:10:59', '2022-10-31 21:10:59'),
(678, 'App\\Models\\CustomUser', 1, '', '4a6279c0a1a4e45e310389fdc2d0c78ba3b3d603710f703968775932d2def4c1', '[\"*\"]', NULL, '2022-10-31 21:10:59', '2022-10-31 21:10:59'),
(679, 'App\\Models\\CustomUser', 1, '', 'c2c96203bb10b24cb2da1a20f2d2b6d8575650dbbe3c6ed4d4cc29b8e7617e82', '[\"*\"]', '2022-11-01 00:06:55', '2022-10-31 21:11:00', '2022-11-01 00:06:55'),
(680, 'App\\Models\\CustomUser', 1, '', 'fe1f1736d6614886f818d77660086acb5bad4d5a23bbc52e44d233165d95b5ea', '[\"*\"]', '2022-10-31 21:18:56', '2022-10-31 21:16:11', '2022-10-31 21:18:56'),
(681, 'App\\Models\\CustomUser', 1, '', 'b2459bec438af1f0b9ad1a7b32e5920a69543c880271d2772f3514023cd43b04', '[\"*\"]', '2022-10-31 21:16:24', '2022-10-31 21:16:21', '2022-10-31 21:16:24'),
(682, 'App\\Models\\CustomUser', 1, '', '1b801b63fac15b22cdd25e41dbc575eefc529fe4527a2faae73a3acdee7b4649', '[\"*\"]', '2022-11-01 17:41:09', '2022-10-31 21:16:38', '2022-11-01 17:41:09'),
(683, 'App\\Models\\CustomUser', 1, '', 'b35710f24e6e6c115ffd6409482af92eff3fb796e553ece88b1887d586708780', '[\"*\"]', '2022-11-01 17:39:51', '2022-10-31 21:22:48', '2022-11-01 17:39:51'),
(684, 'App\\Models\\CustomUser', 1, '', 'd88674476c8749941d7da888e4956fec03e71dd9a80d4bbecbf8aceaa89c0307', '[\"*\"]', '2022-11-01 19:59:55', '2022-10-31 21:24:41', '2022-11-01 19:59:55'),
(685, 'App\\Models\\CustomUser', 1, '', '34b4ca610e43d2cc0d17f87a2923fcc2b170819c8e391b4346245d945e5d7fd4', '[\"*\"]', '2022-11-01 15:39:17', '2022-10-31 21:25:10', '2022-11-01 15:39:17'),
(686, 'App\\Models\\CustomUser', 1, '', '578eff87701742ee6dd71ee44cb3b832857014d6ab816216ffd6f10a38e89fa6', '[\"*\"]', '2022-11-01 20:02:17', '2022-10-31 21:30:42', '2022-11-01 20:02:17'),
(687, 'App\\Models\\CustomUser', 1, '', '5f885773c2120e07716a810d227ef26b480fff64202cba3fcb53d6102c06efd1', '[\"*\"]', '2022-10-31 21:34:20', '2022-10-31 21:32:58', '2022-10-31 21:34:20'),
(688, 'App\\Models\\CustomUser', 1, '', '12988bc88958f7609cac126e19d92c868fc845fa9f5b651fa23b1d717263b05c', '[\"*\"]', '2022-10-31 22:23:47', '2022-10-31 21:33:06', '2022-10-31 22:23:47'),
(689, 'App\\Models\\CustomUser', 1, '', '02490a662e8b7900896a5680c5968aaf04f06e2e73be54283d136c0d0dfc1700', '[\"*\"]', '2022-11-01 17:15:03', '2022-10-31 21:33:57', '2022-11-01 17:15:03'),
(690, 'App\\Models\\CustomUser', 1, '', 'a2a47188250ce4c8ed711eb8b8f4d05356a391ccc8c2865a597b235015fcb1cd', '[\"*\"]', '2022-10-31 21:40:40', '2022-10-31 21:38:36', '2022-10-31 21:40:40'),
(691, 'App\\Models\\CustomUser', 1, '', 'dbb09e56725ff4d14efc96eb7d3a4c8b3c63e2e481a8751a70fee7384e82cc91', '[\"*\"]', NULL, '2022-10-31 21:49:22', '2022-10-31 21:49:22'),
(692, 'App\\Models\\CustomUser', 1, '', '1ef91b8bcba78a84311a028e1bc87e0b37eba3969a749f548060551908c13e94', '[\"*\"]', '2022-10-31 21:52:35', '2022-10-31 21:52:12', '2022-10-31 21:52:35'),
(693, 'App\\Models\\CustomUser', 1, '', 'd18e6860f772c5851314a2bca1a8e3d5b6b30d6f1a3db8345054ba089a977370', '[\"*\"]', '2022-10-31 22:00:10', '2022-10-31 22:00:09', '2022-10-31 22:00:10'),
(694, 'App\\Models\\CustomUser', 1, '', '7b9b4a97d2d18aaa75eeeb892622617efcea1b3ebb9c7a3313290943c4e645da', '[\"*\"]', '2022-10-31 22:18:14', '2022-10-31 22:17:49', '2022-10-31 22:18:14'),
(695, 'App\\Models\\CustomUser', 1, '', '7acfc341b4b7ac76b3f15e10671153d2c9495153f2f405dd20d25d6801bb5948', '[\"*\"]', '2022-11-01 11:53:28', '2022-10-31 22:19:11', '2022-11-01 11:53:28'),
(696, 'App\\Models\\CustomUser', 1, '', 'bdb838a5676863ac95956a97c252018a341f0084ac53a7de0bfa117b4e1c3d02', '[\"*\"]', '2022-11-01 00:11:56', '2022-10-31 22:56:29', '2022-11-01 00:11:56'),
(697, 'App\\Models\\CustomUser', 1, '', 'fb9d721123234e0ff0ade04767b6d0f0920bc97d3bbb486ca6b79748765f710d', '[\"*\"]', '2022-10-31 23:56:38', '2022-10-31 23:04:53', '2022-10-31 23:56:38'),
(698, 'App\\Models\\CustomUser', 1, '', '902586b8d14682521e9a7b4792e5c05d8b490b5e18d036167466345d8771e4df', '[\"*\"]', NULL, '2022-10-31 23:17:14', '2022-10-31 23:17:14'),
(699, 'App\\Models\\CustomUser', 1, '', '1eb8a3a0617b9a8b5bbcb55e9b9b5ff8aad1f6e0c617182d67427dfd4fbed13d', '[\"*\"]', NULL, '2022-10-31 23:19:20', '2022-10-31 23:19:20'),
(700, 'App\\Models\\CustomUser', 1, '', '74ed4411f4e7cb1e73337fa13ac6ac2c40451d629caecccbffa274eb08facbdb', '[\"*\"]', '2022-10-31 23:52:22', '2022-10-31 23:26:49', '2022-10-31 23:52:22'),
(701, 'App\\Models\\CustomUser', 1, '', 'f7d405c23f87fbe2568760b07d0e1bb0378555e1e2d5ce8c77a011fb7828cae4', '[\"*\"]', '2022-10-31 23:46:50', '2022-10-31 23:34:20', '2022-10-31 23:46:50'),
(702, 'App\\Models\\CustomUser', 2, '', '1217ecb518ee7989d3cc4ba567b551122aebbc4dded50a18da26b0fab0bc964b', '[\"*\"]', '2022-11-01 06:21:27', '2022-10-31 23:45:38', '2022-11-01 06:21:27'),
(703, 'App\\Models\\CustomUser', 1, '', '18f9a9714964629bac4054ca3a59e8e241511256e2864224ce239a893d22750b', '[\"*\"]', '2022-11-01 17:04:27', '2022-11-01 01:54:45', '2022-11-01 17:04:27'),
(704, 'App\\Models\\CustomUser', 1, '', '17cdc0a627f198c56121705645706dd7a0168ef8c61d702cedb2eaab4a2b44e4', '[\"*\"]', '2022-11-01 11:43:58', '2022-11-01 03:10:47', '2022-11-01 11:43:58'),
(705, 'App\\Models\\CustomUser', 1, '', '11fb7f1b19782d608fc3c8cbd6298bbc204fd219e7927f817c7d173ecbee4189', '[\"*\"]', '2022-11-01 20:10:40', '2022-11-01 05:26:56', '2022-11-01 20:10:40'),
(706, 'App\\Models\\CustomUser', 1, '', '316a2c12ddb46a8e8320994824edafb14bcf966304d46244dd4083bd8c017125', '[\"*\"]', NULL, '2022-11-01 12:23:07', '2022-11-01 12:23:07'),
(707, 'App\\Models\\CustomUser', 1, '', '392d5a9ce2e5b98fb79787956b7e6197fd15726d183cc7d0b9b739855727d3b3', '[\"*\"]', '2022-11-01 15:23:25', '2022-11-01 15:17:08', '2022-11-01 15:23:25'),
(708, 'App\\Models\\CustomUser', 1, '', 'cb9f0c1603c01cba6583fbb5e5102fde307871465b4b469162efa42a9488bfe8', '[\"*\"]', NULL, '2022-11-01 15:22:40', '2022-11-01 15:22:40'),
(709, 'App\\Models\\CustomUser', 1, '', '8fffe1f2193a1cee0af52aa2f6a6d0caa8a1cde00cfba8e9f173ebd82a0b29cc', '[\"*\"]', '2022-11-01 15:22:42', '2022-11-01 15:22:40', '2022-11-01 15:22:42'),
(710, 'App\\Models\\CustomUser', 1, '', '2aebcc59becc890f88177eb31860e996514beca593ba491831c697f01fb9c217', '[\"*\"]', '2022-11-01 15:51:05', '2022-11-01 15:33:42', '2022-11-01 15:51:05'),
(711, 'App\\Models\\CustomUser', 1, '', '63b5f62d7cbaee5a224a5d4660bc22bd90c89352daa1c66e8da30e5f1ed745a2', '[\"*\"]', '2022-11-01 16:51:44', '2022-11-01 16:26:06', '2022-11-01 16:51:44'),
(712, 'App\\Models\\CustomUser', 1, '', 'd7f9db9a258b87d1a22ebc385ff955fdb937e7c9f66274f3340261e8da712b3d', '[\"*\"]', '2022-11-01 16:50:57', '2022-11-01 16:38:07', '2022-11-01 16:50:57'),
(713, 'App\\Models\\CustomUser', 1, '', '75acfd226e9ebce30d27b631014fff30da2ab71f471ac02acb9641118aac0b38', '[\"*\"]', NULL, '2022-11-01 16:38:43', '2022-11-01 16:38:43'),
(714, 'App\\Models\\CustomUser', 1, '', '4dcf6390dffaea8382cf6838d4caf42892f3937b086e1bc873d5bbcf4e0fb5cf', '[\"*\"]', '2022-11-01 16:44:30', '2022-11-01 16:38:44', '2022-11-01 16:44:30'),
(715, 'App\\Models\\CustomUser', 1, '', 'bdde76adb9a99644ded26bc57bff1a55fd1228609347d8953aa9e1ef3d3e6c3c', '[\"*\"]', NULL, '2022-11-01 16:38:58', '2022-11-01 16:38:58'),
(716, 'App\\Models\\CustomUser', 1, '', 'de459dc771216301ace9f4c062943df20bdfb7ab7d48de1e67ed13af81cd1cf1', '[\"*\"]', '2022-11-01 18:37:05', '2022-11-01 16:38:58', '2022-11-01 18:37:05'),
(717, 'App\\Models\\CustomUser', 1, '', '69b7d4c24d564e77e0311e9984edc0995d13edbba9aba182c1d2e06a0fffee9b', '[\"*\"]', '2022-11-01 18:53:27', '2022-11-01 16:47:59', '2022-11-01 18:53:27'),
(718, 'App\\Models\\CustomUser', 1, '', '9b7ed40aedb46d3aa63509335bb093b7cbc9d035eeedaef68fddce7e02fffd02', '[\"*\"]', '2022-11-01 17:46:17', '2022-11-01 16:54:07', '2022-11-01 17:46:17'),
(719, 'App\\Models\\CustomUser', 1, '', '96da9ea417b75476ad5def8a16ce8593302407577407741bb3bcbcd3b0cd67ef', '[\"*\"]', '2022-11-01 17:04:11', '2022-11-01 17:00:57', '2022-11-01 17:04:11'),
(720, 'App\\Models\\CustomUser', 1, '', 'a3a15f3dd05f932eaccc2fde343ebee55966e9965fac87fa4d0c61eec5e763dd', '[\"*\"]', '2022-11-01 17:06:01', '2022-11-01 17:05:41', '2022-11-01 17:06:01'),
(721, 'App\\Models\\CustomUser', 1, '', '5f9ce7a5303b0db2d221bf66013b59258d2af188003a2e724a9e9524ff6d831c', '[\"*\"]', NULL, '2022-11-01 17:15:15', '2022-11-01 17:15:15'),
(722, 'App\\Models\\CustomUser', 1, '', 'da5a268034e2de3eb51a2c826af80f1434225fe1ed54c25284ccb1660097b0d3', '[\"*\"]', '2022-11-01 17:38:58', '2022-11-01 17:15:15', '2022-11-01 17:38:58'),
(723, 'App\\Models\\CustomUser', 1, '', 'ccd6a43303734d530f5495e77885f7b906ab567a3b5885ee3381117ba5577361', '[\"*\"]', '2022-11-01 17:32:39', '2022-11-01 17:32:38', '2022-11-01 17:32:39'),
(724, 'App\\Models\\CustomUser', 1, '', '24369f5504e9bf9bdb5fb548deec7757abe88f441732bd8bbe75fbb5f24bc2d4', '[\"*\"]', '2022-11-01 17:46:29', '2022-11-01 17:43:14', '2022-11-01 17:46:29'),
(725, 'App\\Models\\CustomUser', 1, '', 'acc74b5151559c54078c649fe36571aa7ba751ec5e453ec8dfaffda6b7f25668', '[\"*\"]', '2022-11-01 17:49:39', '2022-11-01 17:46:03', '2022-11-01 17:49:39'),
(726, 'App\\Models\\CustomUser', 1, '', 'd33234c5aad2efe5a940be964f76c2e61955861f798fbf53d9edfcf05fbe019e', '[\"*\"]', '2022-11-01 17:46:46', '2022-11-01 17:46:27', '2022-11-01 17:46:46'),
(727, 'App\\Models\\CustomUser', 1, '', '8e5cf2405e54e38de3637799110189dca5119a701876b0508a74e60352e10448', '[\"*\"]', '2022-11-01 17:49:11', '2022-11-01 17:46:33', '2022-11-01 17:49:11'),
(728, 'App\\Models\\CustomUser', 1, '', '40a29fe0ced233f3bea946f23171467824c4b099d574e0e4123d2e0f6470de02', '[\"*\"]', '2022-11-01 17:49:41', '2022-11-01 17:49:24', '2022-11-01 17:49:41'),
(729, 'App\\Models\\CustomUser', 1, '', '6f89c481d3f146440541adcbeb616b8661ccf5bcbb026ebca1cb02adbfee8d2c', '[\"*\"]', '2022-11-01 18:20:14', '2022-11-01 17:51:54', '2022-11-01 18:20:14'),
(730, 'App\\Models\\CustomUser', 1, '', 'c0783bf0aab0004ed3d856ab14fc54a54a37f9d5223b29a4b56622439c7a3ba9', '[\"*\"]', '2022-11-01 18:06:30', '2022-11-01 17:54:11', '2022-11-01 18:06:30'),
(731, 'App\\Models\\CustomUser', 1, '', '4a5ae0e1e70410c5d98fd5000f8bbde3b443250b5f65c7300024556ddd066c1f', '[\"*\"]', '2022-11-01 18:18:41', '2022-11-01 18:18:41', '2022-11-01 18:18:41'),
(732, 'App\\Models\\CustomUser', 1, '', '249b1dd11a67da0d4047196197c6d9954635d1d68d017eb199f4d5ae7a924bdf', '[\"*\"]', '2022-11-01 18:39:11', '2022-11-01 18:36:35', '2022-11-01 18:39:11'),
(733, 'App\\Models\\CustomUser', 1, '', '165089d57481adda484f04a6e15215b3feb22d81815eb6be8efdd979e695c119', '[\"*\"]', '2022-11-01 18:46:14', '2022-11-01 18:41:19', '2022-11-01 18:46:14'),
(734, 'App\\Models\\CustomUser', 1, '', '5ec9ae6d0223ebc87e19ee61331711a69d79d19c949e653edef5a0f91f297db6', '[\"*\"]', '2022-11-01 18:52:29', '2022-11-01 18:46:30', '2022-11-01 18:52:29'),
(735, 'App\\Models\\CustomUser', 1, '', '99f572d4cc2932ea4d91f2fbcb322b26a862fad1b88d078c2454624af8214b05', '[\"*\"]', '2022-11-01 19:20:42', '2022-11-01 19:20:32', '2022-11-01 19:20:42'),
(736, 'App\\Models\\CustomUser', 1, '', '84198ea56f8bd54347437644f3b2b6d8af39d8f2172503901b36bb76667af1e7', '[\"*\"]', '2022-11-01 20:12:41', '2022-11-01 20:00:44', '2022-11-01 20:12:41'),
(737, 'App\\Models\\CustomUser', 1, '', '495f89495c3b594a96f60b6a758059eb4a32e4702ad710b91269692d4b171427', '[\"*\"]', '2022-11-02 06:22:24', '2022-11-02 06:18:59', '2022-11-02 06:22:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `production_plans`
--

CREATE TABLE `production_plans` (
  `id` int(11) NOT NULL,
  `ngay_dat_hang` datetime NOT NULL,
  `cong_doan_sx` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ca_sx` int(11) NOT NULL,
  `ngay_sx` datetime NOT NULL,
  `ngay_giao_hang` datetime NOT NULL,
  `machine_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `khach_hang` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lo_sx` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `so_bat` int(11) NOT NULL,
  `sl_nvl` int(11) NOT NULL,
  `sl_thanh_pham` int(11) NOT NULL,
  `thu_tu_uu_tien` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `UPH` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhan_luc` int(11) NOT NULL,
  `tong_tg_thuc_hien` int(11) NOT NULL,
  `thoi_gian_bat_dau` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `so_may_can_sx` int(11) DEFAULT NULL,
  `file` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fromUI',
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `production_plans`
--

INSERT INTO `production_plans` (`id`, `ngay_dat_hang`, `cong_doan_sx`, `ca_sx`, `ngay_sx`, `ngay_giao_hang`, `machine_id`, `product_id`, `khach_hang`, `lo_sx`, `so_bat`, `sl_nvl`, `sl_thanh_pham`, `thu_tu_uu_tien`, `note`, `UPH`, `nhan_luc`, `tong_tg_thuc_hien`, `thoi_gian_bat_dau`, `so_may_can_sx`, `file`, `status`, `created_at`, `updated_at`) VALUES
(18, '2023-06-01 00:00:00', 'In', 2, '2045-12-06 00:00:00', '2045-12-08 00:00:00', 'IWASAKY ', 'abc123', 'Hanbo', '1/6/23', 6, 5000, 3000, 'số 1', 'Sản phẩm\n ECN', NULL, 3, 3, '7:00', NULL, 'd4e8788c9c4fa3246c1b6c30d8d6eb7e', 0, '2023-07-31 04:12:10', '2023-07-31 04:12:10'),
(19, '2023-06-06 00:00:00', 'In', 1, '2045-12-06 00:00:00', '2045-12-08 00:00:00', 'IWASAKY ', 'abc456', 'Hanbo', '1/6/23', 8, 3000, 2000, 'số 2', NULL, NULL, 3, 4, '10:00', NULL, 'd4e8788c9c4fa3246c1b6c30d8d6eb7e', 0, '2023-07-31 04:12:10', '2023-07-31 04:12:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '1:product,2:meterial',
  `customer_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nhiet_do_phong` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `do_am_phong` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `do_am_giay` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thoi_gian_bao_on` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dinh_muc` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `unit_id`, `info`, `material_id`, `customer_id`, `nhiet_do_phong`, `do_am_phong`, `do_am_giay`, `thoi_gian_bao_on`, `dinh_muc`, `created_at`, `updated_at`) VALUES
('MHB077', 'NVL-EF-EA536BP10+0.1', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB083', ' NVL-EF-NS908BP20+0.1', NULL, NULL, 'GIAY0151', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB123', 'NVL-EF-EA536BLAP10+2', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB124', ' NVL-EF-EA536BLAP10+3', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB132', 'NVL-EF-ZS901BKP10.1', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB133', 'NVL-EF-ZS901WTP10.1', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB134', 'NVL-EF-ZS906BKP10.1', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('MHB135', 'NVL-EF-ZS906WTP10.1', NULL, NULL, 'GIAY0933', 'NTL464', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT021', 'MGBAD0008300', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT022', 'MGBAD0008310', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT023', 'MGBAD0008320', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT024', 'MGBAD0008330', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT032', 'MGBAD0006222', NULL, NULL, 'GIAY0237', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT033', 'MGBAD0006232', NULL, NULL, 'GIAY0237', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT034', 'MGBAD0006252', NULL, NULL, 'GIAY0237', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT035', 'MGBAD0006262', NULL, NULL, 'GIAY0237', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT036', 'MGBAD0007042', NULL, NULL, 'GIAY0169', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT040', 'MGBAD0007082', NULL, NULL, 'GIAY0169', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT041', 'MGBAD0007252', NULL, NULL, 'GIAY0169', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT042', 'MGBAD0008340', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT043', 'MGBAD0008350', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT044', 'MGBAD0008360', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT045', 'MGBAD0006272', NULL, NULL, 'GIAY0237', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT046', 'MGBAD0008370', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT047', 'MGBAD0008380', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT048', 'MGBAD0008390', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT049', 'MGBAD0008400', NULL, NULL, 'GIAY0936', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT056 ', 'MGBAD0008910', NULL, NULL, 'GIAY0950', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT057 ', ' MGBAD0008920', NULL, NULL, 'GIAY0950', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT058', 'MGBAD0008930', NULL, NULL, 'GIAY0950', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT059', 'MGBAD0008940', NULL, NULL, 'GIAY0950', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT071  ', 'MGBAD0006953', NULL, NULL, 'GIAY0229', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT072   ', 'MGBAD0005803', NULL, NULL, 'GIAY0231', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT073', 'MGBAD0005813', NULL, NULL, 'GIAY0231', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT075', 'MGBAD0008990', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT076', 'MGBAD0009000', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT077 ', 'MGBAD0009010', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT078  ', 'MGBAD0009020', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT079  ', 'MGBAD0009030', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('RFT080', 'MGBAD0009040', NULL, NULL, 'GIAY0236', 'NTL499', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW030', 'SA69-00193G', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW038', 'SA69-00207A', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW039', 'SA69-00207B', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW067', 'SA69-00187G', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW068', 'SA69-00187H', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW069', 'SA69-00187J', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW070', 'SA69-00193H', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW071', 'SA69-00193J', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW072', 'SA69-00193K', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW087', 'SA69-00199P', NULL, NULL, 'GIAY0151', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW088', 'SA69-00199 L', NULL, NULL, 'GIAY0151', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW089', 'SA69-00199N', NULL, NULL, 'GIAY0151', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW090', 'SA69-00199M', NULL, NULL, 'GIAY0151', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW091', 'SA69-00187N', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW092', 'SA69-00187K', NULL, NULL, 'GIAY0933', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW094', 'SA69-00199Q', NULL, NULL, 'GIAY0151', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW130', 'SA69-00214A', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW131', 'SA69-00214B', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW132', 'SA69-00214C', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW138', ' SA69-00224A', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW139', ' SA69-00224B', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW140', ' SA69-00224C', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW147', 'SA69-00225A', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW148', 'SA69-00225B', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('SW149', 'SA69-00225C', NULL, NULL, 'GIAY0225', 'NTL202  ', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TBJ167', 'SPA G-BOX 028-W0 (IN)', NULL, NULL, 'GIAY0137', 'NTL170', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TBJ168', 'SPA G-BOX 028-K0 (IN)', NULL, NULL, 'GIAY0137', 'NTL170', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR303', 'CTC-0306SZT-KA', NULL, NULL, 'GIAY0137', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR506', 'CTC-0280ZNT-WA', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR514', 'CTC-0299ZWO-WB', NULL, NULL, 'GIAY0245', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR515', 'CTC-0280ZNT-WB', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR517', 'CTC-0300ZZO-WB', NULL, NULL, 'GIAY0245', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR518', 'CTC-0280RZT-WC', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR526', 'CTC-0320RZO-ZA', NULL, NULL, 'GIAY0245', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR527', 'CTC-0318UCO-WA', NULL, NULL, 'GIAY0245', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR528', 'CTC-0317WWO-WA', NULL, NULL, 'GIAY0245', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR602', 'CTC-0280CNT-WA', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR603', 'CTC-0280UCT-WA', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR607', 'CTC-0280RZT-WB', NULL, NULL, 'GIAY0231', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR612', 'CTC-0303ZZO-WA', NULL, NULL, 'GIAY0222', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TCR619', 'CTC-0302ZZO-WA', NULL, NULL, 'GIAY0222', 'NTL121', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI001', 'SAG000196A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI013', 'SAG000196C', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI014', 'SAG000192C', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI015', 'SAG000192G', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI016', 'SAG000196G', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI017', 'SAG000196B', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI018', 'SAG000196E', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI019', 'SAG000196D', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI021', 'SAG000196F', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI022', 'SAG000196H', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI023', 'SAG000196I', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI024', 'SAG000192B', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI025', 'SAG000192E', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI026', 'SAG000192D', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI028', 'SAG000192F', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI029', 'SAG000192H', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI030', 'SAG000192I', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI042', 'SAG000230B', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI043', 'SAG000230C', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI044', 'SAG000232D', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI045', 'SAG000232E', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI046', 'SAG000232F', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI047', 'SAG000232G', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI048', 'SAG000232H', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI049', 'SAG000232I', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI050', 'SAG000232A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI051', 'SAG000232B', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI052', 'SAG000232C', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI053', 'SAG000230A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI054', 'SAG000230D', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI055', 'SAG000230E', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI056', 'SAG000230F', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI057', 'SAG000230G', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI058', 'SAG000230H', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI059', 'SAG000230I', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI113 ', 'SAG000300A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI117', 'SAG000304A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI120', 'SAG000320A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI121', 'SAG000324A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI124', 'SAG000377B', NULL, NULL, 'GIAY0175', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI127', 'SAG000356B', NULL, NULL, 'GIAY0175', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI137', 'SAG000402A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TUI141', 'SAG000398A', NULL, NULL, 'GIAY0152', 'NTL400', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB147', 'CBBAD-00589', NULL, NULL, 'GIAY0222', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB159', 'CBBAA-00201(V02)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB160', 'CBBAA-00200(V02)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB166', 'CBBAD-00593(V01)', NULL, NULL, 'GIAY0222', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB227', 'CBBAA-00196(V01)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB228', 'CBBAA-00197(V01)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB229', 'CBBAA-00205(V02)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23'),
('TYB230', 'CBBAA-00206(V02)', NULL, NULL, 'GIAY0245', 'NTL383', '22~28', '40~60', '5~7', '≥ 12', 2000, '2023-07-31 09:07:23', '2023-07-31 09:07:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reasons`
--

CREATE TABLE `reasons` (
  `id` bigint(20) NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reasons`
--

INSERT INTO `reasons` (`id`, `name`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Cúp điện', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(2, 'Sản phẩm mới (duyệt mẫu)', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(3, 'Bảo trì (định kỳ, AM tuần,..)', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(4, 'Vệ sinh 5S', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(5, 'Họp', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(6, 'Ăn cơm', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(7, 'Đổi lệnh sản xuất', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(8, 'Thiếu người', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(9, 'Thiếu nguyên vật liệu', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(10, 'Nguyên vật liệu không đạt', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(11, 'Vệ sinh giao ca', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(12, 'Chờ xử lý', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(13, 'Lô hư', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(14, 'Không có hàng', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14'),
(15, 'Máy hư', NULL, '2022-10-27 19:17:14', '2022-10-27 19:17:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reason_machine`
--

CREATE TABLE `reason_machine` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reason_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `machine_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `result` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `shefts`
--

CREATE TABLE `shefts` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `warehouse_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `shefts`
--

INSERT INTO `shefts` (`id`, `name`, `note`, `warehouse_id`, `created_at`, `updated_at`) VALUES
('0003a77c-d0a7-46fe-8bc7-74f681b0c9e0', 'F', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('035a25e2-da69-46e2-995f-fed5bb8d124f', 'K', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('0865e5f0-d5a8-4aa9-8364-80721c4990e7', 'I46', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('08851e7c-c7c6-4fbc-a5c2-7712acaadd99', 'I56', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:52', '2022-10-25 18:59:52'),
('0c9b334b-d78e-43d5-9f22-7bc62ef56e86', 'I26', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:42', '2022-10-28 02:36:42'),
('1156cecc-af1e-4b3f-9e84-dc9309bd9890', 'H', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('12248875-cba7-44ea-8536-41413456e8bd', 'J', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:32', '2022-10-29 23:39:32'),
('12a0fbc8-416d-43c3-a58c-abf174e27b72', 'D', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:29', '2022-10-29 23:39:29'),
('13fcf4df-da56-4f46-9b58-69b7d847ac91', 'I10', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('1617fe0c-c34b-4794-aa9a-adf83330a514', 'I62', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:51', '2022-10-28 02:36:51'),
('17f25cf2-5aa2-4bed-88d0-6110c9b10301', 'I04', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('183095d2-f58e-4dbe-9a7c-ace10d130f8a', 'I50', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:47', '2022-10-28 02:36:47'),
('193b5434-8a7f-47f8-9a95-ddc48f2eae88', 'S', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:14:43', '2022-10-29 06:14:43'),
('1c80d31d-e635-436e-90f7-9ef92899c934', 'I54', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('206e25e9-4b3c-4771-a28c-b10f5a2139ab', 'G', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-31 20:52:47', '2022-10-31 20:52:47'),
('211a1ce4-2d0b-4b83-b8e0-ca2843772600', 'I18', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:41', '2022-10-28 02:36:41'),
('219f6bb4-908b-4917-ace8-319b1ed945ca', 'L', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:33', '2022-10-29 04:15:33'),
('249b9a41-18ff-4f32-aeb6-b1783d14604d', 'I34', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('2cb4b5ea-8095-4704-b620-a58549e6e1f6', 'I24', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('2fbcfb31-fa5c-440b-b735-0248b6a686d8', 'I18', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('351b5e63-cd08-441a-90b0-5586a001f8f0', 'I38', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('3aeaf703-de2a-44cc-8ffe-a28304452acb', 'M', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:34', '2022-10-29 04:15:34'),
('3b2a39e3-e2b3-457c-96e1-892a76e3642b', 'I52', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:48', '2022-10-28 02:36:48'),
('3bd0c21b-ee44-449e-9d01-71bfdb65d38c', 'I48', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:47', '2022-10-28 02:36:47'),
('3e3cdbc9-4110-4b7c-b5a5-b5a60b2ec0ba', 'I36', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:44', '2022-10-28 02:36:44'),
('3f6473ef-2b2f-408a-96b1-fd07315cab0d', 'I60', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:50', '2022-10-28 02:36:50'),
('436001ef-ade6-4b0b-ac32-464053eaf283', 'F', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:27', '2022-10-29 23:39:27'),
('44b810c5-a237-4383-b695-6d6876134d6b', 'H', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:32', '2022-10-29 23:39:32'),
('4706e272-f2df-4dd3-ad07-91979638f9ce', 'I38', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:44', '2022-10-28 02:36:44'),
('47a8a17e-4717-4b76-b888-dfaa7096c6ba', 'C', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('492818cf-d03e-4331-8a8a-e6530691f408', 'O', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:34', '2022-10-29 04:15:34'),
('4e5cc0c7-891e-4a8d-8cd4-8f486ea45b13', 'I30', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:42', '2022-10-28 02:36:42'),
('514289a9-d350-496c-892e-32af45261365', 'K', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:28', '2022-10-29 23:39:28'),
('52909df1-b37d-4bd6-99c6-0142e1056a5e', 'I06', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('53c37626-e51a-497f-8ab9-241af7da3585', 'I14', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('566c0bfc-6e76-4e16-99d9-95f90334e243', 'I44', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('59aa84a4-b3b8-42b0-8d0d-1492781a7c2e', 'I08', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('614f7b24-d8ac-4f86-b3ed-6cabb25eaec4', 'I12', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('61c0ae28-dc03-4179-8b9b-f6d222e9ab64', 'I60', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:52', '2022-10-25 18:59:52'),
('621994af-2896-4938-ba74-5a2ab96ebfdb', 'I22', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('621cb91c-fe58-49cf-a357-dcd1e675fa30', 'Q', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:13:25', '2022-10-29 06:13:25'),
('62fa0f67-a326-4d76-8e92-2c45b6b935f9', 'B', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:28', '2022-10-29 23:39:28'),
('64d34689-f925-4f47-8d4e-b5f75566893b', 'I20', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:41', '2022-10-28 02:36:41'),
('6c0c91cf-da10-4816-9cf8-e08b1d57758c', 'I02', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('6c70c1cc-a1b0-469e-8540-00d841f47865', 'J', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('6e4f6069-5f0b-473d-9960-f8b42f11aee4', 'I22', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:41', '2022-10-28 02:36:41'),
('70247e22-4d3d-4377-876f-0af04f4678ac', 'I', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('7117e109-5fcf-413d-a740-9c8a474e44b5', 'I30', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('7163a830-fda5-4ad4-9c85-76c1124eb65c', 'T', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:13:53', '2022-10-29 06:13:53'),
('7451b048-5735-4b23-ab2c-7fdb3a4f9b61', 'P', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:14:29', '2022-10-29 06:14:29'),
('7a60b5aa-af72-4ce0-9cc4-da28733f1de2', 'I48', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('7b8c9347-c75d-4260-bb2e-b206648396e5', 'R', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:13:47', '2022-10-29 06:13:47'),
('7cefd29d-377c-4748-a0fc-68f5bbc7acbd', 'I26', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('8484eebf-41bb-40ba-82a6-b9b67daa2091', 'I40', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:45', '2022-10-28 02:36:45'),
('8ce414a7-ad38-47f2-8388-1c467d238ca2', 'N', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:34', '2022-10-29 04:15:34'),
('92de1aa5-a686-44a9-a5b9-9c8fe1bc7bb5', 'I42', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('9350d687-cdbc-4599-a76d-ddd34735b643', 'I14', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('9517d2d3-0ce1-4c03-9799-a5054916f2ed', 'I54', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:48', '2022-10-28 02:36:48'),
('95abe462-edf8-40c0-9a8d-73acb1c00e21', 'I20', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('996b4b32-1b93-46da-b29d-b8eb5f90316d', 'I02', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:55:03', '2022-10-25 18:55:03'),
('99feee1c-ee8c-4ca9-9753-13ba16fce766', 'I08', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:01:16', '2022-10-29 22:01:16'),
('9c45bf37-032a-4406-939f-5d45394f28c0', 'I28', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:42', '2022-10-28 02:36:42'),
('9edc07d5-27de-4e91-b880-76394661c457', 'I06', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('9ef6a4ad-196d-4091-a96c-ac8734112f70', 'I32', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('9efa98af-997d-462f-8742-c1cf72ca2b4e', 'I52', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('a5107cac-0802-4aee-9bd0-5369a74a242e', 'U', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:14:08', '2022-10-29 06:14:08'),
('a6d71fea-99e3-4852-a86f-c46539de525f', 'C', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:29', '2022-10-29 23:39:29'),
('a76ca2de-222c-4fa5-9f35-a3e329283992', 'B', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('aee51f28-0f9e-4ddd-8f23-2823b5d7e5a6', 'I28', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('b0b7cea0-81dc-46a0-b4c4-3b53b0b79226', 'I40', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('b1cc659a-8144-456e-82c0-efa71a20800b', 'E', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:33', '2022-10-29 04:15:33'),
('b2084b96-edc6-464b-9c67-9a37b3e47dbc', 'I32', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:43', '2022-10-28 02:36:43'),
('b7274a5e-465c-4c48-b234-fb6b8558aeea', 'I10', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('b9645524-3565-4558-a126-c447f60f246e', 'I12', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:01:35', '2022-10-29 22:01:35'),
('b9c1b1be-7548-4d37-b37c-b834f855b856', 'I16', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('b9cf492b-377f-4129-b464-4ff6d9d91e6e', 'A', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('bc14e496-f1f8-49bf-a72b-2e3ef488c889', 'D', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-13 18:27:42', '2022-10-13 18:27:42'),
('c0a183f4-5ffb-4a55-9543-e245cc17f0ca', 'I46', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:46', '2022-10-28 02:36:46'),
('c69d99ef-eb35-4851-92b1-8e6ca0825d8f', 'I42', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:45', '2022-10-28 02:36:45'),
('c97c780f-ca03-4333-b347-de7bdf5d0f94', 'I36', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('cbad710d-055c-454b-aa38-0a34e4fb578b', 'A', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:28', '2022-10-29 23:39:28'),
('cc8f6f3f-04f9-4696-aa7e-f6887bda5de9', '', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-31 21:54:26', '2022-10-31 21:54:26'),
('cd8b9e1d-aeea-4f8a-9365-7542e1802822', 'I58', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:52', '2022-10-25 18:59:52'),
('cf4b7475-12b2-4b5b-b5e0-0eb6146ce2fa', 'I02', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:00:50', '2022-10-29 22:00:50'),
('d6e288e2-5ed1-43ee-8a1a-30b55023c31c', 'I08', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('d7a7040c-4b70-4fc1-a164-eec36154e8ea', 'I04', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:00:58', '2022-10-29 22:00:58'),
('d81e4677-a7a4-44b2-9216-e28ff00a9786', 'I34', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:43', '2022-10-28 02:36:43'),
('db14e1b5-0257-4aec-a634-5659ee8f968c', 'I16', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:41', '2022-10-28 02:36:41'),
('db79c2b5-a270-4769-9eaa-ddfc72568cec', 'I58', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:50', '2022-10-28 02:36:50'),
('dca561a0-f59c-40da-bd48-233750b586e8', 'I50', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:51', '2022-10-25 18:59:51'),
('dcb02378-24d9-435c-a50e-279ac2eeaeef', 'G', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 04:15:33', '2022-10-29 04:15:33'),
('e17c0db9-7a63-4d3e-b8c5-829937dcbda0', 'I62', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:52', '2022-10-25 18:59:52'),
('e249149e-b54e-4ed9-82fc-70c6747eb8cf', 'W', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:13:33', '2022-10-29 06:13:33'),
('e4cd9663-1bba-4477-a76d-134d069145af', 'I06', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:01:14', '2022-10-29 22:01:14'),
('e67013ba-8a6a-44c3-bebb-0607f27c0c38', 'I10', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b4b', '2022-10-29 22:01:19', '2022-10-29 22:01:19'),
('e6a2f5c1-a515-43a6-9f5d-2fd4658b2242', 'I56', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:49', '2022-10-28 02:36:49'),
('e712fafd-0966-47f0-8c12-1ebcb1a1c05a', 'I24', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:41', '2022-10-28 02:36:41'),
('e82aff52-7ec6-4289-8792-bed603115318', 'I12', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:40', '2022-10-28 02:36:40'),
('eaf6802c-25f9-41a7-8132-d492fdb7a284', 'Y', '.', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-29 06:14:01', '2022-10-29 06:14:01'),
('ee0192ec-ab72-45c6-afac-4e057dbacf20', 'I', '', '07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', '2022-10-29 23:39:27', '2022-10-29 23:39:27'),
('f21233c1-cc79-43b5-aa9b-2f1a811dade2', 'I04', '', '115ec568-26e1-41c4-ad31-4a21067e7e23', '2022-10-25 18:59:50', '2022-10-25 18:59:50'),
('ff3c423f-86e7-4dad-af96-b3cd2a4b7bbb', 'I44', '', '15b688b5-2dc2-41da-b6a4-debe2df706dd', '2022-10-28 02:36:46', '2022-10-28 02:36:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `test_criterias`
--

CREATE TABLE `test_criterias` (
  `id` int(11) NOT NULL,
  `line_id` int(36) NOT NULL,
  `chi_tieu` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hang_muc` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tieu_chuan` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phan_dinh` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'OK/NG',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `test_criterias`
--

INSERT INTO `test_criterias` (`id`, `line_id`, `chi_tieu`, `hang_muc`, `tieu_chuan`, `phan_dinh`, `created_at`, `updated_at`) VALUES
(254, 16, 'Kích thước', 'Chiều rộng phôi', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(255, 16, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(256, 16, 'Đặc tính', 'Độ ẩm giấy', '5 ~ 7 (%)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(257, 16, 'Kích thước', 'Chiều dài phôi', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(258, 16, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(259, 16, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(260, 16, 'Kích thước', 'Độ dày giấy', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(261, 16, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(262, 16, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(263, 16, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(264, 16, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(265, 16, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(266, 10, 'Kích thước', 'Vị trí hình in 1', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(267, 10, 'Ngoại quan', 'Nội dung in', 'Giống \nmẫu chuẩn', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(268, 10, 'Đặc tính', 'Test mài mòn', '500g/10 lần\n (Tốc độ máy 40 lần/ phút) ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(269, 10, 'Kích thước', 'Vị trí hình in 2', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(270, 10, 'Ngoại quan', 'In đúng mặt', 'In vào mặt\nnhẵn của giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(271, 10, 'Đặc tính', 'Đo độ bóng', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(272, 10, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(273, 10, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(274, 10, 'Đặc tính', 'Đọc màu', 'ΔE ≤1.5', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(275, 10, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(276, 10, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(277, 10, 'Đặc tính', 'Đọc mã vạch', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(278, 10, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(279, 10, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(280, 10, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(281, 10, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(282, 10, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(283, 10, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(284, 11, 'Kích thước', 'Vị trí phủ', 'Phần phủ lệch hình in không\n được lệch nhau\n ≤ 0.2 (mm)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(285, 11, 'Ngoại quan', 'Nội dung in', 'Giống \nmẫu chuẩn', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(286, 11, 'Đặc tính', 'Test mài mòn', '500g/10 lần\n (Tốc độ máy 40 lần/ phút) ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(287, 11, 'Kích thước', 'Vị trí hình in 2', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(288, 11, 'Ngoại quan', 'In đúng mặt', 'In vào mặt\nnhẵn của giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(289, 11, 'Đặc tính', 'Đo độ bóng', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(290, 11, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(291, 11, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(292, 11, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(293, 11, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(294, 11, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(295, 11, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(296, 11, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(297, 11, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(298, 11, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(299, 11, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(300, 11, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(301, 11, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(302, 11, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(303, 11, 'Ngoại quan', 'Phần phủ', 'Mịn đẹp, không rỗ, loang\nkeo phủ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(304, 11, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(305, 12, 'Kích thước', 'Chiều rộng sản\nphẩm sau khi bế', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(306, 12, 'Ngoại quan', 'Nội dung in', 'Giống \nmẫu chuẩn', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(307, 12, 'Đặc tính', 'Gấp đóng thử hộp', 'Các thao tác gấp hộp dễ dàng', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(308, 12, 'Kích thước', 'Chiều dài sản\nphẩm sau khi bế', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(309, 12, 'Ngoại quan', 'In đúng mặt', 'In vào mặt\nnhẵn của giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(310, 12, 'Đặc tính', 'Độ bền đường hằn', 'Đường hằn  bẻ góc 180o  \n2 lần  xuôi không nứt giấy vỡ giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(311, 12, 'Kích thước', 'Vị trí hình in 1', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(312, 12, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(313, 12, 'Đặc tính', 'Test khóa cài', 'Sản phẩm thành phẩm đóng thử \nkhóa cài  chắc khít,không chéo,lệch', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(314, 12, 'Kích thước', 'Vị trí hình in 2', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(315, 12, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(316, 12, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(317, 12, 'Kích thước', 'Vị trí đường hằn', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(318, 12, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(319, 12, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(320, 12, 'Kích thước', 'Chiều rộng \nđường hằn', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(321, 12, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(322, 12, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(323, 12, 'Kích thước', 'Chiều cao đường hằn', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(324, 12, 'Ngoại quan', 'Đường hằn', 'Không bị vỡ rách giấy\ntại vị trí đường hằn', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(325, 12, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(326, 13, 'Kích thước', 'Chiều rộng sau khi\ngấp dán\nchưa dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(327, 13, 'Ngoại quan', 'Nội dung in', 'Giống \nmẫu chuẩn', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(328, 13, 'Đặc tính', 'Gấp đóng thử hộp', 'Các thao tác gấp hộp dễ dàng', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(329, 13, 'Kích thước', 'Chiều dài sau khi\ngấp dán\nchưa dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(330, 13, 'Ngoại quan', 'In đúng mặt', 'In vào mặt\nnhẵn của giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(331, 13, 'Đặc tính', 'Độ bền đường hằn', 'Đường hằn  bẻ góc 180o  \n2 lần  xuôi không nứt giấy vỡ giấy', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(332, 13, 'Kích thước', 'Chiều rộng \nđã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(333, 13, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(334, 13, 'Đặc tính', 'Test khóa cài', 'Sản phẩm thành phẩm đóng thử \nkhóa cài  chắc khít,không chéo,lệch', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(335, 13, 'Kích thước', 'Chiều dài đã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(336, 13, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(337, 13, 'Đặc tính', 'Độ bám dính keo', '≥0.8 (kgf)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(338, 13, 'Kích thước', 'Chiều cao\nđã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(339, 13, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(340, 13, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(341, 13, 'Kích thước', 'Vị trí hình in 1', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(342, 13, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(343, 13, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(344, 13, 'Kích thước', 'Vị trí hình in 2', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(345, 13, 'Ngoại quan', 'Keo dán', 'Sản phẩm không có bị tràn keo, bẩn keo, mất keo', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(346, 13, 'Đặc tính', ' ', ' ', 'OK/NG', '2023-07-17 03:26:39', '2023-07-17 03:26:39'),
(347, 15, 'Kích thước', 'Chiều rộng sau khi\ngấp dán\nchưa dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(348, 15, 'Ngoại quan', 'Nội dung in', 'Giống \nmẫu chuẩn', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(349, 15, 'Đặc tính', 'Lên mực', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(350, 15, 'Kích thước', 'Chiều dài sau khi\ngấp dán\nchưa dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(351, 15, 'Ngoại quan', 'In đúng mặt', 'In vào mặt\nnhẵn của giấy', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(352, 15, 'Đặc tính', 'Khác màu', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(353, 15, 'Kích thước', 'Chiều rộng \nđã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(354, 15, 'Ngoại quan', 'Chấm/bụi', 'Cho phép chấm ≤0.5mm2 \nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(355, 15, 'Đặc tính', 'Mờ hình in', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(356, 15, 'Kích thước', 'Chiều dài đã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(357, 15, 'Ngoại quan', 'Xước', 'Đường kính cho phép < 0.1mm. \nDài cho phép < 5mm\nCho phép 1ea vào mặt phụ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(358, 15, 'Đặc tính', 'Lệch hình in', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(359, 15, 'Kích thước', 'Chiều cao\nđã dựng hộp', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(360, 15, 'Ngoại quan', 'Vết hằn\nvết đâm', 'Bán kính dưới 10㎜\n≤1.0(W) * 15(L) : cho phép 1 ea (tất cả các mặt, cho phép 2 ea)', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(361, 15, 'Đặc tính', 'Nhòe hình in', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(362, 15, 'Kích thước', 'Vị trí hình in 1', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(363, 15, 'Ngoại quan', 'Xơ, bavia', 'Sản phẩm không\n được phép xơ, bavia', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(364, 15, 'Đặc tính', 'Rỗ hình in', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(365, 15, 'Kích thước', 'Vị trí hình in 2', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(366, 15, 'Ngoại quan', 'Keo dán', 'Sản phẩm không có bị tràn keo, bẩn keo, mất keo', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(367, 15, 'Đặc tính', 'Mất nét hình in', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(368, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(369, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(370, 15, 'Đặc tính', 'Loang phủ', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(371, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(372, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(373, 15, 'Đặc tính', 'Lệch phủ', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(374, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(375, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(376, 15, 'Đặc tính', 'Xước phủ', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(377, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(378, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(379, 15, 'Đặc tính', 'Không phủ', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(380, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(381, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(382, 15, 'Đặc tính', 'Bế lệch', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(383, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(384, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(385, 15, 'Đặc tính', 'Dính phôi', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(386, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(387, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(388, 15, 'Đặc tính', 'Bong keo', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(389, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(390, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(391, 15, 'Đặc tính', 'Bẩn keo', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(392, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(393, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(394, 15, 'Đặc tính', 'Tràn keo', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(395, 15, 'Kích thước', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(396, 15, 'Ngoại quan', ' ', ' ', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40'),
(397, 15, 'Đặc tính', 'Gấp lệch', 'Không được phép xuất hiện', 'OK/NG', '2023-07-17 03:26:40', '2023-07-17 03:26:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `time_set`
--

CREATE TABLE `time_set` (
  `id` int(11) NOT NULL,
  `ten_ca` varchar(191) DEFAULT NULL,
  `thoi_gian_bat_dau` datetime NOT NULL,
  `thoi_gian_ket_thuc` datetime NOT NULL,
  `nghi_giua_ca` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `units`
--

CREATE TABLE `units` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ware_houses`
--

CREATE TABLE `ware_houses` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ware_houses`
--

INSERT INTO `ware_houses` (`id`, `name`, `note`, `created_at`, `updated_at`) VALUES
('07dfd403-39bc-4c6f-8849-bfd3a45c4b3a', 'Kho Thành Phẩm', '.', '2022-10-14 15:34:42', '2022-10-14 15:34:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ware_house_logs`
--

CREATE TABLE `ware_house_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cell_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `info` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ware_house_logs`
--

INSERT INTO `ware_house_logs` (`id`, `cell_id`, `product_id`, `type`, `info`, `created_at`, `updated_at`) VALUES
(8, 'O03-14', '1667033180', 2, '{\"quantity\":\"150\",\"note\":\"\",\"current_quantity\":151,\"_info\":{\"ngay_pha\":\"1970-01-01\",\"han_su_dung\":\"1970-01-01\",\"ngay_tai_nhap\":null,\"ngay_ton_kho\":\"153\",\"ngay_con_lai\":118}}', '2022-10-28 15:41:18', '2022-10-28 15:41:18'),
(15, 'F03-14', '1667033185', 2, '{\"quantity\":\"150\",\"note\":\"\",\"current_quantity\":150,\"_info\":null}', '2022-10-31 16:21:16', '2022-10-31 16:21:16'),
(16, 'A01-03', '1667033180', 2, '{\"quantity\":\"100\",\"note\":\"\",\"current_quantity\":100,\"_info\":null}', '2022-10-31 20:29:47', '2022-10-31 20:29:47'),
(17, 'A01-03', '1667033180', 1, '{\"quantity\":\"50\",\"note\":\"\",\"current_quantity\":50,\"_info\":null}', '2022-10-31 20:30:38', '2022-10-31 20:30:38'),
(18, 'A01-03', '1667203952', 2, '{\"quantity\":\"100\",\"note\":\"\",\"current_quantity\":100,\"_info\":null}', '2022-10-31 22:13:48', '2022-10-31 22:13:48');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin_menu`
--
ALTER TABLE `admin_menu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `admin_operation_log`
--
ALTER TABLE `admin_operation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_operation_log_user_id_index` (`user_id`);

--
-- Chỉ mục cho bảng `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_permissions_name_unique` (`name`),
  ADD UNIQUE KEY `admin_permissions_slug_unique` (`slug`);

--
-- Chỉ mục cho bảng `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_roles_name_unique` (`name`),
  ADD UNIQUE KEY `admin_roles_slug_unique` (`slug`);

--
-- Chỉ mục cho bảng `admin_role_menu`
--
ALTER TABLE `admin_role_menu`
  ADD KEY `admin_role_menu_role_id_menu_id_index` (`role_id`,`menu_id`);

--
-- Chỉ mục cho bảng `admin_role_permissions`
--
ALTER TABLE `admin_role_permissions`
  ADD KEY `admin_role_permissions_role_id_permission_id_index` (`role_id`,`permission_id`);

--
-- Chỉ mục cho bảng `admin_role_users`
--
ALTER TABLE `admin_role_users`
  ADD KEY `admin_role_users_role_id_user_id_index` (`role_id`,`user_id`);

--
-- Chỉ mục cho bảng `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_users_username_unique` (`username`);

--
-- Chỉ mục cho bảng `admin_user_permissions`
--
ALTER TABLE `admin_user_permissions`
  ADD KEY `admin_user_permissions_user_id_permission_id_index` (`user_id`,`permission_id`);

--
-- Chỉ mục cho bảng `cells`
--
ALTER TABLE `cells`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cell_product`
--
ALTER TABLE `cell_product`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `check_sheet`
--
ALTER TABLE `check_sheet`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `check_sheet_works`
--
ALTER TABLE `check_sheet_works`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `criterias_values`
--
ALTER TABLE `criterias_values`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `descriptions`
--
ALTER TABLE `descriptions`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `errors`
--
ALTER TABLE `errors`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `lines`
--
ALTER TABLE `lines`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `lot`
--
ALTER TABLE `lot`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `l_s_x_logs`
--
ALTER TABLE `l_s_x_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `machine_logs`
--
ALTER TABLE `machine_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `machine_parameters`
--
ALTER TABLE `machine_parameters`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `material_logs`
--
ALTER TABLE `material_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Chỉ mục cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Chỉ mục cho bảng `production_plans`
--
ALTER TABLE `production_plans`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reasons`
--
ALTER TABLE `reasons`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reason_machine`
--
ALTER TABLE `reason_machine`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `shefts`
--
ALTER TABLE `shefts`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `test_criterias`
--
ALTER TABLE `test_criterias`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `time_set`
--
ALTER TABLE `time_set`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Chỉ mục cho bảng `ware_houses`
--
ALTER TABLE `ware_houses`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `ware_house_logs`
--
ALTER TABLE `ware_house_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin_menu`
--
ALTER TABLE `admin_menu`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `admin_operation_log`
--
ALTER TABLE `admin_operation_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1716;

--
-- AUTO_INCREMENT cho bảng `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT cho bảng `check_sheet`
--
ALTER TABLE `check_sheet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `check_sheet_works`
--
ALTER TABLE `check_sheet_works`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lines`
--
ALTER TABLE `lines`
  MODIFY `id` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `l_s_x_logs`
--
ALTER TABLE `l_s_x_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `machine_logs`
--
ALTER TABLE `machine_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `machine_parameters`
--
ALTER TABLE `machine_parameters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `material_logs`
--
ALTER TABLE `material_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=738;

--
-- AUTO_INCREMENT cho bảng `production_plans`
--
ALTER TABLE `production_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `reasons`
--
ALTER TABLE `reasons`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `reason_machine`
--
ALTER TABLE `reason_machine`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `test_criterias`
--
ALTER TABLE `test_criterias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=398;

--
-- AUTO_INCREMENT cho bảng `time_set`
--
ALTER TABLE `time_set`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `ware_house_logs`
--
ALTER TABLE `ware_house_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
