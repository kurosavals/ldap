ALTER TABLE prefix_user DROP INDEX user_mail;

CREATE TABLE IF NOT EXISTS `prefix_user_ad` (
  `user_id` int(11) unsigned NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `prefix_ad_cron` (
  `cron_id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `prefix_ad_log` (
  `ad_date` datetime NOT NULL,
  `ad_log` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

