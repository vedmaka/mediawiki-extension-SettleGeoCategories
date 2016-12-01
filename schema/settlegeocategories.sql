CREATE TABLE /*_*/settlegeocategories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title_key VARCHAR(512) NOT NULL,
  geo_scope INT(1) DEFAULT 0,
  description VARCHAR(2048) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  parent_id INT(11) DEFAULT NULL,
  PRIMARY KEY (id)
) /*$wgDbTableOptions*/;