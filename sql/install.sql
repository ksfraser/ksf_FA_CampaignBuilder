-- CampaignBuilder module database schema for FrontAccounting

CREATE TABLE IF NOT EXISTS `fa_campaigns` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('Draft','Active','Paused','Completed') NOT NULL DEFAULT 'Draft',
    `trigger_type` VARCHAR(50) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fa_campaign_nodes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` INT(11) NOT NULL,
    `node_type` VARCHAR(50) NOT NULL,
    `config` JSON,
    `position_x` INT(4) DEFAULT 0,
    `position_y` INT(4) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `campaign_id` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fa_modules` (`name`, `version`, `enabled`, `installed`) VALUES ('CampaignBuilder', '1.0.0', 1, NOW()) ON DUPLICATE KEY UPDATE `version` = '1.0.0';