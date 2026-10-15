-- Campus Event Reminder database schema (MySQL 8 / MariaDB 10.4+).
--
-- WARNING: this recreates every table and deletes existing data.
-- Import it with phpMyAdmin (Import tab) or:
--   mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS campus_event_reminder
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campus_event_reminder;

DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS organisers;

CREATE TABLE organisers (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_organisers_email (email)
) ENGINE=InnoDB;

CREATE TABLE students (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name           VARCHAR(100) NOT NULL,
    registration_number VARCHAR(30)  NOT NULL,
    email               VARCHAR(254) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_email (email),
    UNIQUE KEY uq_students_registration_number (registration_number)
) ENGINE=InnoDB;

-- starts_at replaces the old event_date + event_time pair so that
-- "upcoming" queries can use an index instead of TIMESTAMP(...) scans.
CREATE TABLE events (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    organiser_id INT UNSIGNED NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT         NULL,
    starts_at    DATETIME     NOT NULL,
    venue        VARCHAR(150) NOT NULL,
    category     VARCHAR(30)  NOT NULL DEFAULT 'General',
    status       ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_status_starts (status, starts_at),
    KEY idx_events_status_category_starts (status, category, starts_at),
    KEY idx_events_organiser_starts (organiser_id, starts_at),
    CONSTRAINT fk_events_organiser
        FOREIGN KEY (organiser_id) REFERENCES organisers (id)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    INT UNSIGNED NOT NULL,
    event_id      INT UNSIGNED NOT NULL,
    registered_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registrations_student_event (student_id, event_id),
    KEY idx_registrations_event (event_id),
    CONSTRAINT fk_registrations_student
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reminders (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    INT UNSIGNED NOT NULL,
    event_id      INT UNSIGNED NOT NULL,
    reminder_type ENUM('one_hour_before') NOT NULL DEFAULT 'one_hour_before',
    scheduled_for DATETIME     NOT NULL,
    status        ENUM('pending', 'read', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reminders_student_event_type (student_id, event_id, reminder_type),
    KEY idx_reminders_due (student_id, status, scheduled_for),
    KEY idx_reminders_event (event_id),
    CONSTRAINT fk_reminders_student
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fk_reminders_event
        FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Failed logins, used to slow down password guessing.
CREATE TABLE login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(254) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_email (email, attempted_at),
    KEY idx_login_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB;
