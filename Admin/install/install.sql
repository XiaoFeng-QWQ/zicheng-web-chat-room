BEGIN TRANSACTION;
DROP TABLE IF EXISTS "groups";
CREATE TABLE IF NOT EXISTS "groups" (
	"group_id"	INTEGER NOT NULL UNIQUE,
	"group_name"	TEXT NOT NULL UNIQUE,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("group_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "messages";
CREATE TABLE IF NOT EXISTS "messages" (
	"id"	INTEGER NOT NULL UNIQUE,
	"type"	TEXT DEFAULT 'user',
	"content"	TEXT NOT NULL,
	"user_name"	TEXT NOT NULL,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY("user_name") REFERENCES "users"("username"),
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "users";
CREATE TABLE IF NOT EXISTS "users" (
	"user_id"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL UNIQUE,
	"password"	TEXT NOT NULL,
	"email"	TEXT,
	"login_token"	VARCHAR(255),
	"group_id"	INTEGER NOT NULL DEFAULT 2,
	"avatar_url"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY("group_id") REFERENCES "groups"("group_id"),
	PRIMARY KEY("user_id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "user_sets";
CREATE TABLE IF NOT EXISTS "user_sets" (
	"id"	INTEGER NOT NULL UNIQUE,
	"user_id"	INTEGER NOT NULL UNIQUE,
	"user_name"	INTEGER NOT NULL UNIQUE,
	"sst_name"	INTEGER NOT NULL,
	"value"	INTEGER,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "system_sets";
CREATE TABLE IF NOT EXISTS "system_sets" (
	"id"	INTEGER NOT NULL UNIQUE,
	"name"	INTEGER NOT NULL UNIQUE,
	"value"	INTEGER NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
INSERT INTO "groups" ("group_id","group_name","created_at") VALUES (1,'管理员','2024-07-11 03:03:24'),
 (2,'普通用户','2024-07-11 03:03:36');
DROP INDEX IF EXISTS "groups_index";
CREATE INDEX IF NOT EXISTS "groups_index" ON "groups" (
	"group_id"
);
DROP INDEX IF EXISTS "messages_index";
CREATE INDEX IF NOT EXISTS "messages_index" ON "messages" (
	"id"
);
DROP INDEX IF EXISTS "users_index";
CREATE INDEX IF NOT EXISTS "users_index" ON "users" (
	"user_id",
	"username"
);
COMMIT;