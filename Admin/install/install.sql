BEGIN TRANSACTION;
DROP TABLE IF EXISTS "admin_login_attempts";
CREATE TABLE "admin_login_attempts" (
	"id"	INTEGER NOT NULL UNIQUE,
	"ip_address"	TEXT NOT NULL,
	"attempts"	INTEGER NOT NULL DEFAULT 0,
	"last_attempt"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"is_blocked"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "files";
CREATE TABLE "files" (
	"id"	INTEGER UNIQUE,
	"file_name"	VARCHAR(255) NOT NULL,
	"file_type"	VARCHAR(50),
	"file_size"	BIGINT,
	"file_path"	TEXT,
	"file_uuid"	TEXT UNIQUE,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	"updated_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	"user_id"	INT,
	"status"	VARCHAR(50) DEFAULT 'active',
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "groups";
CREATE TABLE "groups" (
	"group_id"	INTEGER NOT NULL UNIQUE,
	"group_name"	TEXT NOT NULL UNIQUE,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("group_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "messages";
CREATE TABLE "messages" (
	"id"	INTEGER NOT NULL UNIQUE,
	"type"	TEXT DEFAULT 'user',
	"content"	TEXT NOT NULL,
	"user_name"	TEXT NOT NULL,
	"user_ip"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "system_logs";
CREATE TABLE "system_logs" (
	"log_id"	INTEGER NOT NULL UNIQUE,
	"log_type"	TEXT NOT NULL,
	"message"	TEXT NOT NULL,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("log_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "system_sets";
CREATE TABLE "system_sets" (
	"id"	INTEGER NOT NULL UNIQUE,
	"name"	TEXT NOT NULL UNIQUE,
	"value"	TEXT NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "user_sets";
CREATE TABLE "user_sets" (
	"id"	INTEGER NOT NULL UNIQUE,
	"user_id"	INTEGER NOT NULL,
	"set_name"	TEXT NOT NULL,
	"value"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "user_tokens";
CREATE TABLE "user_tokens" (
	"id"	INTEGER NOT NULL UNIQUE,
	"user_id" INTEGER NOT NULL UNIQUE,
	"token"	VARCHAR(256) NOT NULL,
	"expiration"	DATETIME,
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
	"user_id"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL UNIQUE,
	"password"	TEXT NOT NULL,
	"email"	TEXT,
	"register_ip"	REAL,
	"group_id"	INTEGER NOT NULL DEFAULT 2,
	"avatar_url"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("user_id" AUTOINCREMENT)
);
DROP INDEX IF EXISTS "admin_login_attempts_index";
CREATE UNIQUE INDEX "admin_login_attempts_index" ON "admin_login_attempts" (
	"id"
);
DROP INDEX IF EXISTS "files_index";
CREATE INDEX "files_index" ON "files" (
	"file_uuid"
);
DROP INDEX IF EXISTS "groups_index";
CREATE UNIQUE INDEX "groups_index" ON "groups" (
	"group_id",
	"group_name"
);
DROP INDEX IF EXISTS "messages_index";
CREATE UNIQUE INDEX "messages_index" ON "messages" (
	"id",
	"content"
);
DROP INDEX IF EXISTS "system_logs_index";
CREATE UNIQUE INDEX "system_logs_index" ON "system_logs" (
	"log_id",
	"message"
);
DROP INDEX IF EXISTS "system_sets_index";
CREATE UNIQUE INDEX "system_sets_index" ON "system_sets" (
	"name",
	"id"
);
DROP INDEX IF EXISTS "user_sets_index";
CREATE UNIQUE INDEX "user_sets_index" ON "user_sets" (
	"id",
	"set_name"
);
DROP INDEX IF EXISTS "user_tokens_index";
CREATE UNIQUE INDEX "user_tokens_index" ON "user_tokens" (
	"id",
	"user_id"
);
DROP INDEX IF EXISTS "users_index";
CREATE UNIQUE INDEX "users_index" ON "users" (
	"user_id",
	"username"
);
COMMIT;
