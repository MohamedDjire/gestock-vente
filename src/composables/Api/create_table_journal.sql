-- Table SQL pour journal des mouvements
CREATE TABLE IF NOT EXISTS journal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATETIME NOT NULL,
  user VARCHAR(100) NOT NULL,
  action VARCHAR(255) NOT NULL,
  details TEXT
);