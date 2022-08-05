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
  followers INT,
  imageType varchar(255) NOT NULL,
  imageData longblob NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--
-- Tabelle 'Followers'
--

CREATE TABLE followers (
  followId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  userId INT NOT NULL, /* User der gefolgt wird */
  followsId INT NOT NULL /* Werd den User folgen will */
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

/* Beispiel Daten */
INSERT INTO `users` (`userId`, `username`, `email`, `password`, `description`, `imageType`, `imageData`, `created_at`) VALUES
(1, 'LE FOU', 'olivier@kauz.ch', '$2y$10$y0xUU6lSEjcHPsx51kXfReInLBFC/6YgrXtjJM.mqLfykeR9eALJq', NULL, '', '', '2022-08-05 13:54:26'),
(2, 'TestFaktor', 'test@test', '$2y$10$H6NDgXrwP82NF99WPDDwJeMy1FZTnsIVYcMc.dCKSSTFoILukq.Am', NULL, '', '', '2022-08-05 13:55:59');