DROP DATABASE IF EXISTS instakilo;
CREATE DATABASE instakilo;
USE instakilo;

--
-- Tabelle 'Users'
--

CREATE TABLE users (
  userid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--
-- Tabelle 'Images'
--

CREATE TABLE images (
  imageId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  titel VARCHAR(100) NOT NULL,
	beschreibung VARCHAR(255) NOT NULL,
  datum DATE NOT NULL,
	ort VARCHAR(50) NOT NULL,
  oeffentlich TINYINT(1) NOT NULL,
	fk_userId INT NOT NULL,
	FOREIGN KEY (fk_userId) REFERENCES users(userid)
);