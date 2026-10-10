-- JAHONORO — boshlang'ich sxema (ARCHITECTURE.md 5-bo'lim)
-- MySQL 8 / MariaDB 10.3+. phpMyAdmin → Import orqali yuklanadi.
-- Vaqtlar Asia/Tashkent mahalliy vaqtida, pul DECIMAL(14,2) so'mda.

SET NAMES utf8mb4;
SET time_zone = '+05:00';

CREATE TABLE IF NOT EXISTS users (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  telegram_user_id  BIGINT NOT NULL,
  display_name      VARCHAR(128) NOT NULL DEFAULT '',
  username          VARCHAR(64) NULL,
  role              ENUM('owner','member') NOT NULL DEFAULT 'member',
  status            ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
  member_checked_at DATETIME NULL,
  member_ok         TINYINT(1) NOT NULL DEFAULT 0,
  last_seen_at      DATETIME NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_telegram (telegram_user_id),
  KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip          VARCHAR(45) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_sessions_token (token_hash),
  KEY idx_sessions_expires (expires_at),
  KEY idx_sessions_user (user_id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title                VARCHAR(160) NOT NULL,
  event_type           ENUM('kelin','nikoh','sunnat','beshik','fotiha','korporativ','boshqa') NOT NULL DEFAULT 'kelin',
  event_date           DATE NOT NULL,
  start_time           TIME NOT NULL,
  starts_at            DATETIME NOT NULL,
  nahor_time           TIME NULL,
  abet_time            TIME NULL,
  assembly_time        TIME NULL,
  departure_time       TIME NULL,
  soundcheck_time      TIME NULL,
  duration_min         SMALLINT UNSIGNED NULL,
  venue_name           VARCHAR(160) NOT NULL DEFAULT '',
  venue_address        VARCHAR(255) NOT NULL DEFAULT '',
  district             VARCHAR(100) NOT NULL DEFAULT '',
  latitude             DECIMAL(9,6) NULL,
  longitude            DECIMAL(9,6) NULL,
  location_verified    TINYINT(1) NOT NULL DEFAULT 0,
  total_price          DECIMAL(14,2) NULL,
  financial_visibility ENUM('inherit','private','members') NOT NULL DEFAULT 'inherit',
  client_name          VARCHAR(128) NOT NULL DEFAULT '',
  client_phone         VARCHAR(32) NOT NULL DEFAULT '',
  performance_notes    TEXT NULL,
  equipment_notes      TEXT NULL,
  dress_code           VARCHAR(255) NOT NULL DEFAULT '',
  schedule_notes       TEXT NULL,
  internal_notes       TEXT NULL,
  status               ENUM('planned','confirmed','completed','cancelled','archived') NOT NULL DEFAULT 'planned',
  created_by           INT UNSIGNED NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cancelled_at         DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_events_starts (starts_at),
  KEY idx_events_status_starts (status, starts_at),
  KEY idx_events_date (event_date),
  KEY idx_events_created_by (created_by),
  CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_events_price CHECK (total_price IS NULL OR total_price >= 0),
  CONSTRAINT chk_events_duration CHECK (duration_min IS NULL OR duration_min <= 1440),
  CONSTRAINT chk_events_lat CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90)),
  CONSTRAINT chk_events_lng CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id    INT UNSIGNED NOT NULL,
  amount      DECIMAL(14,2) NOT NULL,
  paid_on     DATE NOT NULL,
  note        VARCHAR(255) NOT NULL DEFAULT '',
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_event (event_id),
  KEY idx_payments_created_by (created_by),
  CONSTRAINT fk_payments_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_payments_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_reminders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id       INT UNSIGNED NOT NULL,
  reminder_type  ENUM('day_before','manual') NOT NULL,
  dedupe_key     VARCHAR(100) NOT NULL,
  scheduled_at   DATETIME NOT NULL,
  status         ENUM('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  attempts       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_at      DATETIME NULL,
  claim_token    CHAR(32) NULL,
  sent_at        DATETIME NULL,
  tg_message_id  BIGINT NULL,
  last_error     VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reminders_dedupe (dedupe_key),
  KEY idx_reminders_status_scheduled (status, scheduled_at),
  KEY idx_reminders_event (event_id),
  KEY idx_reminders_claim (claim_token),
  CONSTRAINT fk_reminders_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key    VARCHAR(64) NOT NULL,
  setting_value  TEXT NULL,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_user_id  INT UNSIGNED NULL,
  action         VARCHAR(64) NOT NULL,
  entity_type    VARCHAR(32) NOT NULL,
  entity_id      INT UNSIGNED NULL,
  safe_metadata  LONGTEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_actor (actor_user_id),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  bucket        VARCHAR(100) NOT NULL,
  window_start  INT UNSIGNED NOT NULL,
  hits          INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (bucket, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
