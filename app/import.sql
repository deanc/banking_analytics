CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `category` (`category_id`, `title`) VALUES
(1, 'Utilities'),
(2, 'Restaurant/Cafe'),
(3, 'Groceries'),
(4, 'Alcohol/Bars'),
(5, 'Taxi'),
(6, 'Public transport'),
(7, 'Friend'),
(8, 'Banking'),
(9, 'Clothes'),
(10, 'Fast Food'),
(11, 'Candy'),
(12, 'Tourism'),
(13, 'Home'),
(14, 'Flights'),
(15, 'Hotels'),
(16, 'Gifts'),
(17, 'Salary'),
(18, 'Health'),
(19, 'Recreation'),
(20, 'Electronics'),
(21, 'Boats'),
(22, 'Rent');

CREATE TABLE IF NOT EXISTS `place` (
  `place_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `place` varchar(255) NOT NULL,
  `category_id` int(10) NOT NULL,
  PRIMARY KEY (`place_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `transaction` (
  `transaction_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `place_id` int(10) unsigned NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `hash` char(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;