-- Minimal schema for ConnectHub
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  age INT NOT NULL,
  gender ENUM('male','female') NOT NULL,
  role ENUM('user','shopkeeper','admin') DEFAULT 'user',
  bio TEXT,
  profile_pic VARCHAR(255),
  banned_until DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT,
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  post_id INT,
  UNIQUE(user_id, post_id)
);

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  post_id INT,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS friends (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT,
  receiver_id INT,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  UNIQUE(sender_id, receiver_id)
);

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT,
  receiver_id INT,
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shopkeeper_id INT,
  name VARCHAR(255),
  description TEXT,
  price DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT,
  target_user_id INT,
  message TEXT,
  action ENUM('report','ban','delete') DEFAULT 'report',
  until_date DATE
);


CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  seen TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);