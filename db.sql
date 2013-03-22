ALTER TABLE prefix_user DROP INDEX user_mail;

CREATE TABLE IF NOT EXISTS `prefix_user_ad` (
  `user_id` int(11) unsigned NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
