-- =========================================================
-- IBA College of Mindanao — Student Appointment System
-- Database: iba_appointment_system
-- Import this file in phpMyAdmin (SQL tab) or via:
--   mysql -u root -p < schema.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS iba_appointment_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE iba_appointment_system;

-- ---------------------------------------------------------
-- USERS  (student / registrar / cashier accounts)
-- ---------------------------------------------------------
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(120)        NOT NULL,
  email       VARCHAR(150)        NOT NULL UNIQUE,
  password    VARCHAR(255)        NOT NULL,
  role        ENUM('student','registrar','cashier') NOT NULL,
  created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SERVICES  (the "purpose" of an appointment, per office)
-- ---------------------------------------------------------
CREATE TABLE services (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  office      ENUM('registrar','cashier') NOT NULL,
  name        VARCHAR(120)        NOT NULL,
  is_active   TINYINT(1)          NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SCHEDULE SLOTS  (Schedule Management feature)
-- Registrar/Cashier staff open up date + time slots with a
-- capacity; students book into these slots.
-- ---------------------------------------------------------
CREATE TABLE schedule_slots (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  office        ENUM('registrar','cashier') NOT NULL,
  slot_date     DATE                NOT NULL,
  slot_time     TIME                NOT NULL,
  capacity      INT                 NOT NULL DEFAULT 5,
  booked_count  INT                 NOT NULL DEFAULT 0,
  created_by    INT                 NULL,
  created_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_slot (office, slot_date, slot_time),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- APPOINTMENTS  (Online Appointment + Appointment History)
-- ---------------------------------------------------------
CREATE TABLE appointments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  queue_code    VARCHAR(20)         NULL UNIQUE,
  student_id    INT                 NOT NULL,
  service_id    INT                 NOT NULL,
  slot_id       INT                 NOT NULL,
  status        ENUM('pending','confirmed','done','cancelled','no_show') NOT NULL DEFAULT 'confirmed',
  notes         VARCHAR(255)        NULL,
  created_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id),
  FOREIGN KEY (slot_id)    REFERENCES schedule_slots(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------
CREATE TABLE notifications (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT                 NOT NULL,
  appointment_id  INT                 NULL,
  message         VARCHAR(255)        NOT NULL,
  is_read         TINYINT(1)          NOT NULL DEFAULT 0,
  created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- =========================================================

-- Demo accounts (passwords are already hashed with PHP password_hash)
--   student@iba.edu.ph   / student123
--   registrar@iba.edu.ph / registrar123
--   cashier@iba.edu.ph   / cashier123
INSERT INTO users (full_name, email, password, role) VALUES
('Juan Delacruz',   'student@iba.edu.ph',   '$2y$10$Ii4sbMKuRj2.oI.pq7fFtOTVAofRkSTmmPqXOd9MnpkMiEaZbirxO', 'student'),
('Maria Santos',    'registrar@iba.edu.ph', '$2y$10$ugcv1MP9gZWEm3D71BXOaegTCtY9kwkBF6VW.QlujELbya0BdT9rO', 'registrar'),
('Pedro Ramos',     'cashier@iba.edu.ph',   '$2y$10$3slJsdx/NBz2jZZdjePPmuUODfSgDUpS4jabu9f4jB5vVlc1Q1DmW', 'cashier');

-- Services offered per office
INSERT INTO services (office, name) VALUES
('registrar', 'TOR Request'),
('registrar', 'Enrollment Clearance'),
('registrar', 'Certificate of Enrollment'),
('registrar', 'ID Replacement'),
('registrar', 'Good Moral Certificate'),
('cashier',   'Tuition Payment'),
('cashier',   'Miscellaneous Fees'),
('cashier',   'Installment Payment'),
('cashier',   'Refund Processing');

-- A few sample open slots for the next 3 days so the system
-- has something to book right after import (staff can add more
-- via Schedule Management).
INSERT INTO schedule_slots (office, slot_date, slot_time, capacity) VALUES
('registrar', CURDATE() + INTERVAL 1 DAY, '09:00:00', 5),
('registrar', CURDATE() + INTERVAL 1 DAY, '10:00:00', 5),
('registrar', CURDATE() + INTERVAL 2 DAY, '09:00:00', 5),
('cashier',   CURDATE() + INTERVAL 1 DAY, '09:00:00', 5),
('cashier',   CURDATE() + INTERVAL 1 DAY, '13:00:00', 5),
('cashier',   CURDATE() + INTERVAL 2 DAY, '09:00:00', 5);
