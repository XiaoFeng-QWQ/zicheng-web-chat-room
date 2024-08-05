BEGIN TRANSACTION;

-- Groups table
DROP TABLE IF EXISTS "groups";
CREATE TABLE IF NOT EXISTS "groups" (
    "group_id" INTEGER NOT NULL UNIQUE,
    "group_name" TEXT NOT NULL UNIQUE,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY("group_id" AUTOINCREMENT)
);

-- Users table
DROP TABLE IF EXISTS "users";
CREATE TABLE IF NOT EXISTS "users" (
    "user_id" INTEGER NOT NULL UNIQUE,
    "username" TEXT NOT NULL UNIQUE,
    "password" TEXT NOT NULL,
    "email" TEXT,
    "register_ip" TEXT,
    "login_token" VARCHAR(255),
    "group_id" INTEGER NOT NULL DEFAULT 2,
    "avatar_url" TEXT,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY("group_id") REFERENCES "groups"("group_id"),
    PRIMARY KEY("user_id" AUTOINCREMENT)
);

-- User sets table
DROP TABLE IF EXISTS "user_sets";
CREATE TABLE IF NOT EXISTS "user_sets" (
    "id" INTEGER NOT NULL UNIQUE,
    "user_id" INTEGER NOT NULL,
    "set_name" TEXT NOT NULL,
    "value" TEXT,
    FOREIGN KEY("user_id") REFERENCES "users"("user_id"),
    PRIMARY KEY("id" AUTOINCREMENT)
);

-- System sets table
DROP TABLE IF EXISTS "system_sets";
CREATE TABLE IF NOT EXISTS "system_sets" (
    "id" INTEGER NOT NULL UNIQUE,
    "name" TEXT NOT NULL UNIQUE,
    "value" TEXT NOT NULL,
    PRIMARY KEY("id" AUTOINCREMENT)
);

-- Messages table
DROP TABLE IF EXISTS "messages";
CREATE TABLE IF NOT EXISTS "messages" (
    "id" INTEGER NOT NULL UNIQUE,
    "type" TEXT DEFAULT 'user',
    "content" TEXT NOT NULL,
    "user_name" TEXT NOT NULL,
    "user_ip" TEXT,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY("user_name") REFERENCES "users"("username"),
    PRIMARY KEY("id" AUTOINCREMENT)
);

-- Admin login attempts table
DROP TABLE IF EXISTS "admin_login_attempts";
CREATE TABLE IF NOT EXISTS "admin_login_attempts" (
    "id" INTEGER NOT NULL UNIQUE,
    "ip_address" TEXT NOT NULL,
    "attempts" INTEGER NOT NULL DEFAULT 0,
    "last_attempt" DATETIME DEFAULT CURRENT_TIMESTAMP,
    "is_blocked" INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY("id" AUTOINCREMENT),
    UNIQUE("ip_address")
);

-- Create unique indices
DROP INDEX IF EXISTS "admin_login_attempts_index";
CREATE UNIQUE INDEX IF NOT EXISTS "admin_login_attempts_index" ON "admin_login_attempts" ("id", "ip_address");

DROP INDEX IF EXISTS "groups_index";
CREATE UNIQUE INDEX IF NOT EXISTS "groups_index" ON "groups" ("group_id");

DROP INDEX IF EXISTS "messages_index";
CREATE UNIQUE INDEX IF NOT EXISTS "messages_index" ON "messages" ("id", "user_ip");

DROP INDEX IF EXISTS "user_sets_index";
CREATE UNIQUE INDEX IF NOT EXISTS "user_sets_index" ON "user_sets" ("user_id");

DROP INDEX IF EXISTS "users_index";
CREATE UNIQUE INDEX IF NOT EXISTS "users_index" ON "users" ("user_id", "username");

COMMIT;