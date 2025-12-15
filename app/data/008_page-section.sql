CREATE TABLE `page` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `slug` VARCHAR(150) NOT NULL,
  `label` VARCHAR(150) NOT NULL,

  `template` VARCHAR(64) NOT NULL DEFAULT 'default',

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,

  CONSTRAINT `uq_page_slug` UNIQUE (`slug`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


CREATE TABLE `section` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `slug` VARCHAR(150) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `uq_section_slug` UNIQUE (`slug`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


CREATE TABLE `page_section` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `page_id` INT NOT NULL,
  `section_id` INT NOT NULL,

  `sort_order` INT NOT NULL,
  `title_hidden` TINYINT(1) NOT NULL DEFAULT 0,
  
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `uq_page_section` UNIQUE (`page_id`, `section_id`),

  CONSTRAINT `uq_page_section_order` UNIQUE (`page_id`, `sort_order`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


ALTER TABLE `page_section`
  ADD CONSTRAINT `fk_page_section_page`
    FOREIGN KEY (`page_id`)
    REFERENCES `page` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;
    
ALTER TABLE `page_section`
  ADD CONSTRAINT `fk_page_section_section`
    FOREIGN KEY (`section_id`)
    REFERENCES `section` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;
