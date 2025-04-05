START TRANSACTION;
DROP TABLE IF EXISTS `admin_login_attempts`;
CREATE TABLE `admin_login_attempts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `last_attempt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `is_blocked` TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
    `event_id` INT NOT NULL AUTO_INCREMENT,
    `event_type` VARCHAR(100) NOT NULL,
    `user_id` INT NOT NULL,
    `target_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `additional_data` TEXT,
    PRIMARY KEY(`event_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50),
    `file_size` BIGINT,
    `file_path` TEXT,
    `file_uuid` VARCHAR(36),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `user_id` INT,
    `status` VARCHAR(50) DEFAULT 'active',
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`),
    UNIQUE KEY(`file_uuid`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
    `group_id` INT NOT NULL AUTO_INCREMENT,
    `group_name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`group_id`),
    UNIQUE KEY(`group_id`),
    UNIQUE KEY(`group_name`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) DEFAULT 'user',
    `content` TEXT NOT NULL,
    `user_name` VARCHAR(255) NOT NULL,
    `user_ip` VARCHAR(45),
    `reply_to` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `status` VARCHAR(50) DEFAULT 'active',
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `log_type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`log_id`),
    UNIQUE KEY(`log_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `system_sets`;
CREATE TABLE `system_sets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`),
    UNIQUE KEY(`name`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `user_sets`;
CREATE TABLE `user_sets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `set_name` VARCHAR(255) NOT NULL,
    `value` TEXT,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE `user_tokens` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(256) NOT NULL,
    `expiration` DATETIME,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`id`),
    UNIQUE KEY(`user_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255),
    `register_ip` VARCHAR(45),
    `group_id` INT NOT NULL DEFAULT 2,
    `avatar_url` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`user_id`),
    UNIQUE KEY(`user_id`),
    UNIQUE KEY(`username`)
) ENGINE=InnoDB;

COMMIT;