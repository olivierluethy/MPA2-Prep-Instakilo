DROP DATABASE IF EXISTS instakilo;
CREATE DATABASE instakilo;
USE instakilo;

--
-- Tabelle 'Users'
--

CREATE TABLE users (
  userId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL UNIQUE,
  email VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  imageType varchar(255) NOT NULL,
  imageData longblob NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--
-- Tabelle 'Followers'
--

CREATE TABLE followers (
  followId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  fk_userId INT NOT NULL,
  fk_followsId INT NOT NULL,
  FOREIGN KEY (fk_userId) REFERENCES users(userId),
  FOREIGN KEY (fk_followsId) REFERENCES users(userId)
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
  likes INT,
  imageType varchar(255) NOT NULL,
  imageData longblob NOT NULL,
	fk_userId INT NOT NULL,
	FOREIGN KEY (fk_userId) REFERENCES users(userid)
);