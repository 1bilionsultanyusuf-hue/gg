CREATE TABLE `apps` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT
);

CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100),
  `email` VARCHAR(255) UNIQUE,
  `role` ENUM ('programmer', 'support', 'admin') DEFAULT 'support',
  `password` VARCHAR(255)
);

CREATE TABLE `todos` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `app_id` INT,
  `title` VARCHAR(255),
  `description` TEXT,
  `priority` ENUM ('low', 'medium', 'high') DEFAULT 'medium',
  `created_at` TIMESTAMP DEFAULT (CURRENT_TIMESTAMP),
  `user_id` INT
);

CREATE TABLE `taken` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `id_todos` int,
  `date` date,
  `status` ENUM ('in_progress', 'done') DEFAULT 'in_progress',
  `user_id` INT
);

CREATE TABLE `reports` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `date` DATE NOT NULL,                 
  `user_id` INT NOT NULL,               
  `role` ENUM('client','support','programmer','admin') NOT NULL,
  `activity` TEXT NOT NULL,            
  `problem` TEXT,                       
  `status` ENUM('in_progress','done','pending') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(id)
);


ALTER TABLE `todos` ADD FOREIGN KEY (`app_id`) REFERENCES `apps` (`id`) ON DELETE CASCADE;

ALTER TABLE `taken` ADD FOREIGN KEY (`id_todos`) REFERENCES `todos` (`id`);

ALTER TABLE `todos` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `taken` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);