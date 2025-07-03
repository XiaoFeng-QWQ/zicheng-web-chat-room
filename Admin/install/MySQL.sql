CREATE TABLE `admin_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_bin NOT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_blocked` boolean NOT NULL DEFAULT false,
  PRIMARY KEY (`id`),
  INDEX (`ip_address`),
  INDEX (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `groups` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `group_name` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_bin DEFAULT NULL,
  `register_ip` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `group_id` int NOT NULL DEFAULT '2',
  `avatar_url` mediumtext COLLATE utf8mb4_bin,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`),
  INDEX (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `file_path` mediumtext COLLATE utf8mb4_bin,
  `file_md5` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_bin DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_md5` (`file_md5`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  INDEX (`user_id`),
  INDEX (`status`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `user_id` int NOT NULL,
  `target_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `additional_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`event_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  INDEX (`user_id`),
  INDEX (`event_type`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_bin DEFAULT 'user',
  `content` mediumtext COLLATE utf8mb4_bin NOT NULL,
  `user_name` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `user_ip` varbinary(16) DEFAULT NULL,
  `reply_to` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) COLLATE utf8mb4_bin DEFAULT 'active',
  PRIMARY KEY (`id`),
  INDEX (`user_name`),
  INDEX (`created_at`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `system_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `log_type` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `message` mediumtext COLLATE utf8mb4_bin NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  INDEX (`log_type`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `system_sets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `value` mediumtext COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `user_sets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `set_name` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `value` mediumtext COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  UNIQUE KEY `user_set` (`user_id`, `set_name`),
  INDEX (`set_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `user_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_bin NOT NULL,
  `expiration` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  UNIQUE KEY `token` (`token`),
  INDEX (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;