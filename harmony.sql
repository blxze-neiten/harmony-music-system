-- ============================================================
-- 🎵 HARMONY MUSIC INDUSTRY SYSTEM – FINAL DATABASE SCHEMA
-- Includes analytics, royalties, payouts, collaborations, licensing,
-- notifications, streams, likes/dislikes, comments, approvals, and admin.
-- Compatible with XAMPP (PHP 8 + MySQL 8)
-- ============================================================

DROP DATABASE IF EXISTS harmony;
CREATE DATABASE harmony CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE harmony;

-- ------------------------------------------------------------
-- 1️⃣ Roles
-- ------------------------------------------------------------
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE
);

INSERT INTO roles (name)
VALUES ('User'), ('Artist'), ('Producer'), ('Admin');

-- ------------------------------------------------------------
-- 2️⃣ Users
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  profile_pic VARCHAR(255),
  bio TEXT,
  phone VARCHAR(20),
  country VARCHAR(100),
  is_active TINYINT(1) DEFAULT 1,
  email_verified TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ------------------------------------------------------------
-- 3️⃣ Music
-- ------------------------------------------------------------
CREATE TABLE music (
  id INT AUTO_INCREMENT PRIMARY KEY,
  artist_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  genre VARCHAR(100),
  file_path VARCHAR(255) NOT NULL,
  views INT DEFAULT 0,
  likes INT DEFAULT 0,
  status VARCHAR(20) DEFAULT 'pending',
  featured TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 4️⃣ Likes & Dislikes
-- ------------------------------------------------------------
CREATE TABLE likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  music_id INT NOT NULL,
  reaction ENUM('like','dislike') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_reaction (user_id, music_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 5️⃣ Comments
-- ------------------------------------------------------------
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  user_id INT NOT NULL,
  comment TEXT NOT NULL,
  parent_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 6️⃣ Streams (Tracks number of plays)
-- ------------------------------------------------------------
CREATE TABLE streams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  user_id INT NOT NULL,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7️⃣ Royalties
-- ------------------------------------------------------------
CREATE TABLE royalties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  streams_count INT DEFAULT 0,
  gross_amount DECIMAL(10,2) DEFAULT 0,
  artist_share DECIMAL(10,2) DEFAULT 0,
  producer_share DECIMAL(10,2) DEFAULT 0,
  status ENUM('pending','paid') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 8️⃣ Transactions (M-Pesa)
-- ------------------------------------------------------------
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  mpesa_phone VARCHAR(20),
  amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(20) DEFAULT 'Completed',
  mpesa_code VARCHAR(50),
  transaction_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 9️⃣ Notifications & History
-- ------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notification_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  message TEXT NOT NULL,
  target_type ENUM('all','role') NOT NULL,
  target_value VARCHAR(100),
  recipient_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 🔟 Payouts (Royalty Withdrawals)
-- ------------------------------------------------------------
CREATE TABLE payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) DEFAULT 'Mpesa',
  status ENUM('pending','sent','failed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 1️⃣1️⃣ Licensing Requests
-- ------------------------------------------------------------
CREATE TABLE licensing_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  requester_id INT NOT NULL,
  usage_description TEXT,
  fee_offered DECIMAL(10,2) DEFAULT 0,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 1️⃣2️⃣ Producer Collaborations (with chat + status tracking)
-- ------------------------------------------------------------
CREATE TABLE producer_collabs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  artist_id INT NOT NULL,
  producer_id INT NOT NULL,
  revenue_share DECIMAL(5,2) DEFAULT 30.00,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (producer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 💬 Collaboration chat messages between artist & producer
CREATE TABLE collab_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collab_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (collab_id) REFERENCES producer_collabs(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ✅ END OF FINAL DATABASE SCHEMA
