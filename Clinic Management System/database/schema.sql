-- ClinicCare database schema
-- Target: MariaDB 10.11.19 (XAMPP)
-- This script creates the database if it does not exist and creates
-- seven tables. It does not drop or replace existing databases or tables.
--
-- Application/backend rules (not enforced by these table definitions):
-- 1. Appointment creation and rescheduling must use an InnoDB transaction
--    and lock the doctor's row with SELECT ... FOR UPDATE before checking
--    active status, schedule coverage, and conflicts.
-- 2. Appointment intervals are [start, end). Adjacent appointments are
--    allowed. Exclude Cancelled appointments from conflict checks; include
--    Scheduled, Completed, and No-show appointments.
-- 3. Reject past appointments and appointments not fully covered by an
--    active schedule. Reject appointments extending beyond midnight and
--    bookings for inactive patients or doctors.
-- 4. Schedule create/edit and doctor deactivation must use transactions
--    and lock the same doctor row used by booking, preventing races.
--    Active schedule intervals for one doctor/day must not overlap.
-- 5. A doctor profile must link to an active user account with role Doctor.
--    Inactive doctor accounts/profiles cannot access doctor functions or
--    receive new appointments.
-- 6. A future patient birth date must be rejected by the application.
--    Validate gender against the application's agreed dropdown options.
-- 7. A medical record may be created only by an authorized doctor assigned
--    to the appointment. In one transaction, lock the Scheduled appointment,
--    update it to Completed, and insert its medical record. Commit both
--    changes together or roll both back.
-- 8. patients.contact_number and patients.address are required registration
--    fields (NOT NULL); confirm this requirement with the group.

CREATE DATABASE IF NOT EXISTS cliniccare
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cliniccare;


CREATE TABLE users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Receptionist', 'Doctor') NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE patients (
    patient_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    birth_date DATE NOT NULL,
    gender VARCHAR(30) NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    date_registered DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (patient_id),
    KEY idx_patients_full_name (full_name),
    KEY idx_patients_contact_number (contact_number)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE specializations (
    specialization_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    specialization_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',

    PRIMARY KEY (specialization_id),
    UNIQUE KEY uq_specializations_name (specialization_name)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE doctors (
    doctor_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    specialization_id INT UNSIGNED NOT NULL,
    license_number VARCHAR(50) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (doctor_id),
    UNIQUE KEY uq_doctors_user_id (user_id),
    UNIQUE KEY uq_doctors_license_number (license_number),
    KEY idx_doctors_specialization_id (specialization_id),

    CONSTRAINT fk_doctors_user
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_doctors_specialization
        FOREIGN KEY (specialization_id)
        REFERENCES specializations (specialization_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE doctor_schedules (
    schedule_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    doctor_id INT UNSIGNED NOT NULL,
    available_day TINYINT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',

    PRIMARY KEY (schedule_id),
    KEY idx_schedules_doctor_day_status
        (doctor_id, available_day, status, start_time, end_time),

    CONSTRAINT chk_schedules_day
        CHECK (available_day BETWEEN 1 AND 7),

    CONSTRAINT chk_schedules_start_time
        CHECK (start_time >= '00:00:00'),

    CONSTRAINT chk_schedules_end_time
        CHECK (end_time <= '23:59:59'),

    CONSTRAINT chk_schedules_time_range
        CHECK (end_time > start_time),

    CONSTRAINT fk_schedules_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES doctors (doctor_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE appointments (
    appointment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    reason VARCHAR(500) NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show')
        NOT NULL DEFAULT 'Scheduled',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (appointment_id),
    KEY idx_appointments_doctor_date_status_time
        (doctor_id, appointment_date, status, appointment_time),
    KEY idx_appointments_patient_date
        (patient_id, appointment_date),
    KEY idx_appointments_status_date
        (status, appointment_date),
    KEY idx_appointments_created_by (created_by),

    CONSTRAINT chk_appointments_start_time
        CHECK (
            TIME_TO_SEC(appointment_time) >= 0
            AND TIME_TO_SEC(appointment_time) < 86400
        ),

    CONSTRAINT chk_appointments_duration
        CHECK (duration_minutes > 0),

    CONSTRAINT chk_appointments_before_midnight
        CHECK (
            TIME_TO_SEC(appointment_time) + (duration_minutes * 60)
            <= 86400
        ),

    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients (patient_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES doctors (doctor_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_appointments_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (user_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE medical_records (
    record_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment TEXT NOT NULL,
    notes TEXT NULL,
    consultation_date DATETIME NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (record_id),
    UNIQUE KEY uq_medical_records_appointment_id (appointment_id),
    KEY idx_medical_records_created_by (created_by),

    CONSTRAINT fk_medical_records_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments (appointment_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_medical_records_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (user_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
