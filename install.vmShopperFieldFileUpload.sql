CREATE TABLE IF NOT EXISTS `#__plgVmuserfieldVmShopperFieldFileUpload_files` (
  `f_id` INT(11) UNSIGNED NOT NULL auto_increment,
  `uid` INT(11) DEFAULT NULL,
  `time` INT(11) DEFAULT NULL,
  `ip` TINYTEXT NOT NULL,
  `fieldname` TINYTEXT NOT NULL,
  `filename` TINYTEXT NOT NULL,
  `mime` TINYTEXT NOT NULL,
  `size` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  PRIMARY KEY  (`f_id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
