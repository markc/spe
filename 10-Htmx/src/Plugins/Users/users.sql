CREATE TABLE IF NOT EXISTS `users` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  `grp` integer NOT NULL DEFAULT '0',
  `acl` integer NOT NULL DEFAULT '0',
  `login` varchar(64) NOT NULL,
  `fname` varchar(64) NOT NULL DEFAULT '',
  `lname` varchar(64) NOT NULL DEFAULT '',
  `altemail` varchar(64) NOT NULL DEFAULT '',
  `webpw` varchar(64) NOT NULL DEFAULT '',
  `otp` varchar(64) NOT NULL DEFAULT '',
  `otpttl` integer NOT NULL DEFAULT '0',
  `cookie` varchar(64) NOT NULL DEFAULT '',
  `anote` text NOT NULL,
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL
);
INSERT INTO `users` VALUES
(null,1,0,'sysadm@example.org','Sys','Adm','','','',0,'','','2017-03-02 17:54:28','2017-02-21 01:32:00'),
(null,1,2,'user1@example.org','User','One','','','',0,'','','2017-02-12 00:08:38','2017-02-12 00:08:38'),
(null,1,2,'user2@example.org','User','Two','','','',0,'','','2017-02-12 00:08:38','2017-02-12 00:08:38'),
(null,1,1,'admin1@example.org','Admin','One','','','',0,'','','2017-02-12 00:08:38','2017-02-12 00:08:38'),
(null,4,2,'user3@example.org','User','Three','','','',0,'','','2017-02-12 00:08:38','2017-02-12 00:08:38'),
(null,4,2,'user4@example.org','User','Four','','','',0,'','','2017-02-12 00:08:38','2017-02-12 00:08:38');
