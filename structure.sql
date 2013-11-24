CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) NOT NULL auto_increment,
  `email` varchar(128) NOT NULL default '',
  `password` varchar(60) NOT NULL default '',
  `first_name` varchar(128) NOT NULL default '',
  `last_name` varchar(128) NOT NULL default '',
  `token` varchar(40) default '',
  `active` tinyint(1) NOT NULL default '0',
  `suspended` tinyint(1) NOT NULL default '0',
  `date_forgotten_password` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime default '0000-00-00 00:00:00',
  `date_last_action` datetime default '0000-00-00 00:00:00',
  `date_last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `token`, `active`, `suspended`, `date_forgotten_password`, `date_created`, `date_last_action`, `date_last_login`) VALUES
    (1, 'user@domain.com', '$2a$12$A.gUXr4F0MYGFcEGG98.Q.m1lqo0PwSPPVKOYQi/Pmgm85YF74fGe', 'Test', 'User', 'c5d1ab4a95a1099deeec5f6e5998e326a93929d4', 1, 0, '2013-07-05 13:55:12', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
    
CREATE TABLE `persistent_logins` (
    `user_id` INT(10) NOT NULL,
    `token_hash` VARCHAR(40) NOT NULL,
    `expiry` INT(11) NOT NULL,
    INDEX `FK_persistent_logins_users` (`user_id`),
    CONSTRAINT `FK_persistent_logins_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
