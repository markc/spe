CREATE TABLE IF NOT EXISTS `news` (
  `id` integer PRIMARY KEY AUTOINCREMENT,
  `title` text NOT NULL,
  `content` text NOT NULL,
  `author` text NOT NULL,
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL
);
INSERT INTO `news` VALUES
(null,'News Item 1','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 2','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 3','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 4','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 5','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 6','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 7','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 8','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 9','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 10','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 11','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45'),
(null,'News Item 12','Lorem ipsum etc...','admin','2017-02-20 17:14:36','2017-02-12 00:08:45');
