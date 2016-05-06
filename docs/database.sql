CREATE TABLE IF NOT EXISTS `file` (
  `id` INT(11) NOT NULL AUTO INCREMENT,
  `parentID` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `localName` VARCHAR(255) NOT NULL,
  `extension` VARCHAR(255) NOT NULL,
  `type` INT(11) NOT NULL,
  `size` INT(11) NOT NULL,
  `created` TIMESTAMP NOT NULL,
  `changed` TIMESTAMP,
  PRIMARY KEY(`id`)
)