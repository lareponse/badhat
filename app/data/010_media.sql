CREATE TABLE `mime_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL UNIQUE,         -- e.g., image/png
  `max_size` INT DEFAULT 5,            -- Max size in mbyte (default 5 MB)
  
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `mime_type` (`type`, `category`, `max_size`) VALUES
('image/jpeg', 'image', 1),
('image/png', 'image', 1),
('image/webp', 'image', 1),
('image/svg+xml', 'image', 1),
('video/mp4', 'video', 100),
('video/webm', 'video', 100),
('audio/mp3', 'audio', 10),
('audio/wav', 'audio', 50),
('audio/ogg', 'audio', 10),
('application/pdf', 'document', 20);