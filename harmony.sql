-- ============================================================
-- 🎶 HARMONY MUSIC INDUSTRY SYSTEM (FINAL DATABASE SCHEMA)
-- Compatible with XAMPP + MySQL + PHP
-- ============================================================

DROP DATABASE IF EXISTS harmony;
CREATE DATABASE harmony CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE harmony;

-- ------------------------------------------------------------
-- 1️⃣ Roles Table
-- ------------------------------------------------------------
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE
);

INSERT INTO roles (name) VALUES ('User'),('Artist'),('Producer'),('Admin');

-- ------------------------------------------------------------
-- 2️⃣ Users Table
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ------------------------------------------------------------
-- 3️⃣ Music Table
-- ------------------------------------------------------------
CREATE TABLE music (
  id INT AUTO_INCREMENT PRIMARY KEY,
  artist_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  genre VARCHAR(100) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 4️⃣ Streams Table
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
-- 5️⃣ Comments Table (with Replies)
-- ------------------------------------------------------------
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  user_id INT NOT NULL,
  comment TEXT NOT NULL,
  parent_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 6️⃣ Comment Reactions (Likes/Dislikes)
-- ------------------------------------------------------------
CREATE TABLE comment_reactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  comment_id INT NOT NULL,
  user_id INT NOT NULL,
  reaction ENUM('like','dislike') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(comment_id, user_id),
  FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7️⃣ Royalties Table
-- ------------------------------------------------------------
CREATE TABLE royalties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  period_start DATE,
  period_end DATE,
  streams_count INT DEFAULT 0,
  gross_amount DECIMAL(10,2) DEFAULT 0,
  artist_share DECIMAL(10,2) DEFAULT 0,
  producer_share DECIMAL(10,2) DEFAULT 0,
  status ENUM('pending','paid') DEFAULT 'pending',
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 8️⃣ Transactions (M-Pesa Payments)
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
-- 9️⃣ Notifications (with Read Status)
-- ------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 🔟 Payouts / Withdrawals
-- ------------------------------------------------------------
CREATE TABLE payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) DEFAULT 'Mpesa',
  status ENUM('pending','sent') DEFAULT 'pending',
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
-- 1️⃣2️⃣ Producer Collaboration Requests
-- ------------------------------------------------------------
CREATE TABLE producer_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producer_id INT NOT NULL,
  artist_id INT NOT NULL,
  music_id INT NULL,
  message TEXT,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 1️⃣3️⃣ Accepted Collaborations
-- ------------------------------------------------------------
CREATE TABLE producer_collabs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  music_id INT NOT NULL,
  artist_id INT NOT NULL,
  producer_id INT NOT NULL,
  revenue_share DECIMAL(5,2) DEFAULT 30.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
  FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (producer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 1️⃣4️⃣ Collaboration Chat Messages
-- ------------------------------------------------------------
CREATE TABLE collab_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collab_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (collab_id) REFERENCES producer_collabs(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 1️⃣5️⃣ Default Admin Account
-- ------------------------------------------------------------
INSERT INTO users (name, email, password, role_id)
VALUES ('Admin', 'admin@harmony.com',
'$2y$10$Z5vZ2S1B4fxXQjUz4XyCOe9xNHKuRjls1eXPV5RwU8/7tkC21jM0K', 4);
-- Password: admin123
