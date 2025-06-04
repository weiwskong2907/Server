-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql-db
-- Generation Time: Jun 04, 2025 at 06:58 PM
-- Server version: 8.0.42
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `family_forum`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `entity_type`, `entity_id`, `description`, `created_at`) VALUES
(1, 1, 'comment_moderation', 'comment', 3, 'Comment marked as spam', '2025-05-16 06:09:20'),
(2, 1, 'comment_moderation', 'comment', 4, 'Comment marked as spam', '2025-05-16 06:42:45'),
(3, 1, 'comment_deletion', 'comment', 4, 'Comment deleted', '2025-05-16 06:42:49'),
(4, 1, 'comment_moderation', 'comment', 5, 'Comment marked as spam', '2025-05-16 07:27:05'),
(5, 1, 'comment_deletion', 'comment', 7, 'Comment deleted', '2025-05-16 07:40:18');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attachments`
--

INSERT INTO `attachments` (`id`, `post_id`, `filename`, `original_filename`, `file_type`, `created_at`) VALUES
(12, 33, '6826e7a78069a.jpg', 'WhatsApp 图像2025-05-08于22.29.03_217680ed.jpg', 'image/jpeg', '2025-05-16 07:22:15'),
(14, 35, '6826eb260dc07.jpg', 'IMG_6242.jpeg', 'image/jpeg', '2025-05-16 07:37:10'),
(15, 36, '6826ec9b75163.jpg', 'IMG-20250510-WA0027.jpg', 'image/jpeg', '2025-05-16 07:43:23'),
(16, 37, '6826f0576d4d3.jpg', 'inbound4485459816962660261.jpg', 'image/jpeg', '2025-05-16 07:59:19');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'General', 'General discussion topics', '2025-05-13 13:55:23'),
(2, 'Technology', 'Technology related posts', '2025-05-13 13:55:23'),
(3, 'News', 'Latest news and updates', '2025-05-13 13:55:23'),
(4, 'Tutorial', 'How-to guides and tutorials', '2025-05-13 13:55:23'),
(9, 'Yes', 'BLablabal', '2025-05-16 07:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','spam') DEFAULT 'pending',
  `moderated_by` int DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`, `status`, `moderated_by`, `moderated_at`) VALUES
(5, 33, 1, 'spam', '2025-05-16 07:24:34', 'spam', 1, '2025-05-16 07:27:05'),
(6, 35, 1, '？', '2025-05-16 07:39:15', 'pending', NULL, NULL),
(8, 33, 9, '🥰🥰', '2025-05-16 07:55:32', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(3, 1, 'a01edbac5206277e50b411abbf72b6258a979a1e3962b5f4254006dac3c105ab', '2025-05-15 07:31:52', 0, '2025-05-15 06:31:52'),
(4, 1, '1e60168d8d92a8328db7bdf3ec33d4deb6460095b20275e01d3ffec1b3ba4290', '2025-05-15 07:41:19', 0, '2025-05-15 06:41:19'),
(5, 1, '39cfb3e8da84c160a7dee5521ad34683eb592061f27330df9267cb6545a1b685', '2025-05-15 08:34:50', 1, '2025-05-15 07:34:50'),
(6, 1, '7a4642e56182ad4e1d752669ef51aeb1300913d2a336dc481c80149656ad90a8', '2025-05-15 19:48:16', 0, '2025-05-15 18:48:16'),
(7, 1, '4a77ab6614e0b2781249532c1c35fbe7bc2e11cd14d2ecb5e63ef0d8291ebe9e', '2025-05-15 19:49:06', 0, '2025-05-15 18:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `title`, `content`, `created_at`, `category_id`) VALUES
(33, 7, 'Did u Think Justin is Shabi???', '<p>Hey everybody , did u have any shabi friends that around u ? Ya , he name Justin Kong.</p>', '2025-05-16 07:22:15', 1),
(35, 8, '某某学校有位学生......', '<p>Hoho 大家快看～呆毛🤪</p>\r\n<p>迷茫疑惑的江哥😗</p>', '2025-05-16 07:37:10', 1),
(36, 1, '不要再发我的丑照了', '<p>我谢谢你们哈</p>', '2025-05-16 07:43:23', 9),
(37, 9, 'title: Weng Hin', '<p>₍ ˃ᯅ˂)</p>\r\n<p>( <strong>ꪊꪻ&sub;)</strong></p>', '2025-05-16 07:59:19', 2),
(38, 10, '大家我发现一个好看的电影！！！', '<p>大家我发现一个叫boku no pico的电影，听说好多人推荐人生一定要看一次。有谁想和我一起看吗？</p>', '2025-05-16 07:59:20', 3);

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`post_id`, `tag_id`) VALUES
(33, 13),
(36, 14),
(37, 15),
(38, 16);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `created_at`) VALUES
(1, 'news', '2025-05-13 15:58:36'),
(8, 'tutorial', '2025-05-15 05:43:45'),
(12, '李婷婷', '2025-05-16 06:41:35'),
(13, 'gossip', '2025-05-16 07:22:15'),
(14, 'new ，technology', '2025-05-16 07:43:23'),
(15, 'goon', '2025-05-16 07:59:19'),
(16, 'Newa', '2025-05-16 07:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `birthday` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0',
  `profile_picture` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `bio` text,
  `location` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `admin_level` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `birthday`, `created_at`, `is_admin`, `profile_picture`, `name`, `bio`, `location`, `website`, `admin_level`) VALUES
(1, 'admin', '$2y$10$dkICVsx4CUDB3KQETZnf5OlABXzgA6tylVpHg5X2mc9oWz8P2lR.y', 'wskong.justin700@gmail.com', '2025-02-12', '2025-05-13 13:48:45', 1, 'uploads/profile_pictures/profile_68275bf13ef57.png', 'KONG WEI SHUN JUSTIN', '', '', '', 2),
(6, '2309645', '$2y$10$53OIDRSGRnzUIMAvuEHNWO2NMf7FUMLChb4Zrdj9D.BmpuewJKv.m', 'limchunchuan341@gmail.com', '2025-05-08', '2025-05-16 06:40:12', 0, NULL, NULL, NULL, NULL, NULL, 0),
(7, 'justinshabi', '$2y$10$X6VQvuXmvww2bYxFlw.1j.32M21ADpabhNMWi0c0iunXwPbrnVz9q', 'justinshabi@gmail.com', '2005-02-06', '2025-05-16 07:19:49', 0, NULL, NULL, NULL, NULL, NULL, 0),
(8, 'Xuannn1028', '$2y$10$PgyOE/WAOJIjzlH0E.v30.rfz..HGhsIluH2XCWiNGJHH3waOuSzi', 'laujx888@gmail.com', '2004-10-28', '2025-05-16 07:29:28', 0, NULL, NULL, NULL, NULL, NULL, 0),
(9, 'the', '$2y$10$FDd0536lycJd7RHJP7jFsuokilBWsKxyi2uEmWWmJTISZ5jqNAz6O', 'sample@gmail.com', '2025-05-16', '2025-05-16 07:54:03', 0, NULL, NULL, NULL, NULL, NULL, 0),
(10, 'Boku', '$2y$10$HvcsFVA2tl3fgno2TAnJ6.CpezQBBb.rxA6kwZLPflY5/TnVemTgK', 'boku@gmail.com', '2025-05-16', '2025-05-16 07:57:23', 0, NULL, NULL, NULL, NULL, NULL, 0),
(11, 'Random', '$2y$10$otBvbO..n9nUAI9pLAqF0upayiCkGT1a9zu1bGRgvCdKDEaUlW1sq', 'random@g.com', '2025-05-16', '2025-05-16 07:57:58', 0, NULL, NULL, NULL, NULL, NULL, 0),
(12, 'admin2', '$2y$10$b/yYT3fmh0G/1bo/iKO4WOOvicGzR3Iz4c0NBB2aakr5B/PNwvute', 'admin2@gmail.com', NULL, '2025-05-16 08:16:01', 1, NULL, NULL, NULL, NULL, NULL, 0),
(13, '5huyuu', '$2y$10$lUAmvzvypqGTGYQT2WRuJuHGPl1gHac.gU1BJCoGeHlXzAkVwmDv2', 'shuyuteoh68@gmail.com', '2005-06-08', '2025-05-16 09:54:11', 0, NULL, NULL, NULL, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `moderated_by` (`moderated_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
