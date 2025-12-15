CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `label` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,

  `logo` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


CREATE TABLE `tag_organization` (
  `organization_id` INT NOT NULL,
  `tag_id` INT NOT NULL,

  PRIMARY KEY (`organization_id`, `tag_id`),
  KEY `idx_ot_tag` (`tag_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

ALTER TABLE `tag_organization`
  ADD CONSTRAINT `fk_ot_organization`
    FOREIGN KEY (`organization_id`)
    REFERENCES `organization` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `tag_organization`
  ADD CONSTRAINT `fk_ot_tag`
    FOREIGN KEY (`tag_id`)
    REFERENCES `tag` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;



CREATE TABLE `organization_contact_point` (
  `organization_id` INT NOT NULL,
  `contact_point_id` INT NOT NULL,
  `sort_order` SMALLINT DEFAULT NULL,

  PRIMARY KEY (`organization_id`, `contact_point_id`),
  KEY `idx_ocp_contact_point` (`contact_point_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


ALTER TABLE `organization_contact_point`
  ADD CONSTRAINT `fk_ocp_organization`
    FOREIGN KEY (`organization_id`)
    REFERENCES `organization` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `organization_contact_point`
  ADD CONSTRAINT `fk_ocp_contact_point`
    FOREIGN KEY (`contact_point_id`)
    REFERENCES `contact_point` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;


CREATE TABLE `school` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `label` VARCHAR(255) NOT NULL,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


CREATE TABLE `school_profile` (
  `school_id` INT PRIMARY KEY,

  `title` VARCHAR(255) NOT NULL,
  `intro` TEXT,

  `public_label` VARCHAR(255),
  `age_range` VARCHAR(64),

  `description` TEXT,

  `accompaniment` TEXT,
  `admission_criteria` TEXT,
  `modalities` TEXT,

  `team_overview` TEXT,
  `supports_overview` TEXT,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

ALTER TABLE `school_profile`
  ADD CONSTRAINT `fk_school_profile_school`
    FOREIGN KEY (`school_id`)
    REFERENCES `school` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;


CREATE TABLE `school_contact_point` (
  `school_id` INT NOT NULL,
  `contact_point_id` INT NOT NULL,
  `sort_order` SMALLINT DEFAULT NULL,

  PRIMARY KEY (`school_id`, `contact_point_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

ALTER TABLE `school_contact_point`
  ADD CONSTRAINT `fk_scp_school`
    FOREIGN KEY (`school_id`)
    REFERENCES `school` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `school_contact_point`
  ADD CONSTRAINT `fk_scp_contact_point`
    FOREIGN KEY (`contact_point_id`)
    REFERENCES `contact_point` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;
