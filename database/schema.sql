CREATE TABLE IF NOT EXISTS events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 starts_at DATETIME NULL,
 location VARCHAR(200) NOT NULL DEFAULT '',
 description VARCHAR(500) NOT NULL DEFAULT '',
 status ENUM('active','archived') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 username VARCHAR(50) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('superadmin','admin','petugas') NOT NULL,
 event_id INT UNSIGNED NULL,
 active TINYINT NOT NULL DEFAULT 1,
 session_version INT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_user_event FOREIGN KEY (event_id) REFERENCES events(id),
 INDEX idx_user_event (event_id,role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS guests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id INT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 category VARCHAR(60) NOT NULL DEFAULT 'Umum',
 token CHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 checked_at DATETIME NULL,
 checked_by INT UNSIGNED NULL,
 deleted_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_guest_event FOREIGN KEY (event_id) REFERENCES events(id),
 CONSTRAINT fk_guest_staff FOREIGN KEY (checked_by) REFERENCES users(id),
 INDEX idx_guest_list (event_id,deleted_at,id),
 INDEX idx_guest_attendance (event_id,deleted_at,checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 event_id INT UNSIGNED NULL,
 action VARCHAR(60) NOT NULL,
 target_id INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_event (event_id,id),
 CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
 CONSTRAINT fk_audit_event FOREIGN KEY (event_id) REFERENCES events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
 bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts INT NOT NULL DEFAULT 0,
 first_at DATETIME NOT NULL,
 INDEX idx_attempt_time (first_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS installation (
 id TINYINT PRIMARY KEY,
 installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
