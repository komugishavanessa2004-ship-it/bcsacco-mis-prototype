CREATE DATABASE IF NOT EXISTS bombo_sacco;
USE bombo_sacco;

CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_no VARCHAR(20) NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  nin VARCHAR(20),
  joined_on DATE NOT NULL,
  savings DECIMAL(14,2) NOT NULL DEFAULT 0,
  shares DECIMAL(14,2) NOT NULL DEFAULT 0
);

CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_no VARCHAR(20) NOT NULL UNIQUE,
  member_id INT NOT NULL,
  purpose VARCHAR(160) NOT NULL,
  principal DECIMAL(14,2) NOT NULL,
  months INT NOT NULL,
  monthly_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0300,
  guarantor VARCHAR(120) NOT NULL,
  status ENUM('pending','active','rejected','closed') NOT NULL DEFAULT 'pending',
  outstanding DECIMAL(14,2) NOT NULL,
  disbursed_on DATE NULL,
  FOREIGN KEY (member_id) REFERENCES members(id)
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  kind ENUM('deposit','withdrawal','share','disbursement','repayment') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  posted_on DATE NOT NULL,
  note VARCHAR(160),
  FOREIGN KEY (member_id) REFERENCES members(id)
);
