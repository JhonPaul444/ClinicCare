-- ClinicCare development seed data
-- Fictional records for local development and demonstrations only.
-- Password hashes were generated with PHP password_hash(..., PASSWORD_DEFAULT).
-- Plaintext demo passwords are not stored in this SQL file.
-- Re-run safe for sequential imports: each existence check is a separate
-- read-only SELECT INTO, followed by an INSERT that does not read its target.
--
-- The approved frontend gender options are not present in the repository.
-- Before execution, replace the NULL gender variables below with the group's
-- confirmed option values. Until all are supplied, @seed_data_ready is 0 and
-- the script performs no inserts or Scheduled-appointment refresh.

USE cliniccare;

SET @seed_gender_alex = NULL;
SET @seed_gender_jamie = NULL;
SET @seed_gender_morgan = NULL;
SET @seed_data_ready = (
    @seed_gender_alex IS NOT NULL
    AND @seed_gender_jamie IS NOT NULL
    AND @seed_gender_morgan IS NOT NULL
);

-- The next Monday is always strictly after CURRENT_DATE (ISO Monday=1).
-- The completed sample is at least seven days in the past.
SET @seed_next_monday = DATE_ADD(
    CURRENT_DATE,
    INTERVAL (7 - WEEKDAY(CURRENT_DATE)) DAY
);
SET @seed_completed_monday = DATE_SUB(@seed_next_monday, INTERVAL 14 DAY);
SET @seed_next_tuesday = DATE_ADD(@seed_next_monday, INTERVAL 1 DAY);

-- Run as one import. If any statement errors before COMMIT, stop the import
-- and issue ROLLBACK in the same connection before retrying.
START TRANSACTION;

-- 1. Specializations
SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM specializations
    WHERE specialization_name = 'Family Medicine'
)) INTO @seed_add_family_medicine;
INSERT INTO specializations (specialization_name, description, status)
SELECT 'Family Medicine', 'Fictional ClinicCare development specialization', 'Active'
WHERE @seed_add_family_medicine = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM specializations
    WHERE specialization_name = 'Pediatrics'
)) INTO @seed_add_pediatrics;
INSERT INTO specializations (specialization_name, description, status)
SELECT 'Pediatrics', 'Fictional ClinicCare development specialization', 'Active'
WHERE @seed_add_pediatrics = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM specializations
    WHERE specialization_name = 'Internal Medicine'
)) INTO @seed_add_internal_medicine;
INSERT INTO specializations (specialization_name, description, status)
SELECT 'Internal Medicine', 'Fictional ClinicCare development specialization', 'Active'
WHERE @seed_add_internal_medicine = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM specializations
    WHERE specialization_name = 'Dermatology'
)) INTO @seed_add_dermatology;
INSERT INTO specializations (specialization_name, description, status)
SELECT 'Dermatology', 'Fictional ClinicCare development specialization', 'Active'
WHERE @seed_add_dermatology = 1;

-- 2. Demo users. Existing usernames are left unchanged.
SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'cliniccare_admin'
)) INTO @seed_add_admin;
INSERT INTO users (full_name, username, password_hash, role, status)
SELECT 'ClinicCare Demo Administrator', 'cliniccare_admin',
       '$2y$12$ZAqorSq77ZwKwsO3EX6GVem0PWkrWCDEyipy.cGbtwMr.T8CXMSum', 'Admin', 'Active'
WHERE @seed_add_admin = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'cliniccare_reception'
)) INTO @seed_add_receptionist;
INSERT INTO users (full_name, username, password_hash, role, status)
SELECT 'ClinicCare Demo Receptionist', 'cliniccare_reception',
       '$2y$12$5Sd6xyqrlFVVbD1OmnH9EeauXm5bcx4M7mXum6lcvR39QJbBxhIKS', 'Receptionist', 'Active'
WHERE @seed_add_receptionist = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'cliniccare_doctor1'
)) INTO @seed_add_doctor_user1;
INSERT INTO users (full_name, username, password_hash, role, status)
SELECT 'Dr Avery Sample', 'cliniccare_doctor1',
       '$2y$12$45jWOcdhk0ELiCwJ.jnxEOLf.xS5.TiFDyVYwhZCxBUEgIpkxGcxS', 'Doctor', 'Active'
WHERE @seed_add_doctor_user1 = 1;

SELECT (@seed_data_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'cliniccare_doctor2'
)) INTO @seed_add_doctor_user2;
INSERT INTO users (full_name, username, password_hash, role, status)
SELECT 'Dr Taylor Sample', 'cliniccare_doctor2',
       '$2y$12$Q/wU3QkPPfQDZJCLER1zBOkwH9TrcsEkuuR.CL4mIPpLX2wFWUBSq', 'Doctor', 'Active'
WHERE @seed_add_doctor_user2 = 1;

-- 3. Fictional patients. Values for gender must be approved before execution.
SELECT (@seed_data_ready = 1 AND @seed_gender_alex IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM patients
    WHERE full_name = 'Alex Sample' AND contact_number = '202-555-0101'
)) INTO @seed_add_patient_alex;
INSERT INTO patients (
    full_name, birth_date, gender, contact_number, address, status
)
SELECT 'Alex Sample', '1992-04-12', @seed_gender_alex, '202-555-0101',
       '100 Demo Lane, Sample City', 'Active'
WHERE @seed_add_patient_alex = 1;

SELECT (@seed_data_ready = 1 AND @seed_gender_jamie IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM patients
    WHERE full_name = 'Jamie Sample' AND contact_number = '202-555-0102'
)) INTO @seed_add_patient_jamie;
INSERT INTO patients (
    full_name, birth_date, gender, contact_number, address, status
)
SELECT 'Jamie Sample', '1985-09-23', @seed_gender_jamie, '202-555-0102',
       '200 Example Street, Sample City', 'Active'
WHERE @seed_add_patient_jamie = 1;

SELECT (@seed_data_ready = 1 AND @seed_gender_morgan IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM patients
    WHERE full_name = 'Morgan Sample' AND contact_number = '202-555-0103'
)) INTO @seed_add_patient_morgan;
INSERT INTO patients (
    full_name, birth_date, gender, contact_number, address, status
)
SELECT 'Morgan Sample', '2001-01-17', @seed_gender_morgan, '202-555-0103',
       '300 Fiction Avenue, Sample City', 'Active'
WHERE @seed_add_patient_morgan = 1;

-- 4. Doctor profiles; each referenced user must have the Doctor role.
SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM users
        WHERE username = 'cliniccare_doctor1'
          AND role = 'Doctor' AND status = 'Active'
    )
    AND EXISTS (
        SELECT 1 FROM specializations
        WHERE specialization_name = 'Family Medicine' AND status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctors d
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
           OR d.license_number = 'DEMO-LIC-0001'
    )
) INTO @seed_add_doctor_profile1;
INSERT INTO doctors (user_id, specialization_id, license_number, status)
SELECT u.user_id, s.specialization_id, 'DEMO-LIC-0001', 'Active'
FROM users u
JOIN specializations s ON s.specialization_name = 'Family Medicine'
WHERE u.username = 'cliniccare_doctor1'
  AND u.role = 'Doctor'
  AND u.status = 'Active'
  AND s.status = 'Active'
  AND @seed_add_doctor_profile1 = 1;

SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM users
        WHERE username = 'cliniccare_doctor2'
          AND role = 'Doctor' AND status = 'Active'
    )
    AND EXISTS (
        SELECT 1 FROM specializations
        WHERE specialization_name = 'Pediatrics' AND status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctors d
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
           OR d.license_number = 'DEMO-LIC-0002'
    )
) INTO @seed_add_doctor_profile2;
INSERT INTO doctors (user_id, specialization_id, license_number, status)
SELECT u.user_id, s.specialization_id, 'DEMO-LIC-0002', 'Active'
FROM users u
JOIN specializations s ON s.specialization_name = 'Pediatrics'
WHERE u.username = 'cliniccare_doctor2'
  AND u.role = 'Doctor'
  AND u.status = 'Active'
  AND s.status = 'Active'
  AND @seed_add_doctor_profile2 = 1;

-- Serialize seed schedule/appointment checks with application operations that
-- follow the approved doctor-row locking rule. Keep these locks in this txn.
SELECT d.doctor_id
FROM doctors d
JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor1'
FOR UPDATE;

SELECT d.doctor_id
FROM doctors d
JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor2'
FOR UPDATE;

-- 5. Active schedules use ISO weekdays (Monday=1, Sunday=7).
-- Doctor 1: Monday, Wednesday, Friday, 09:00-12:00.
-- Doctor 2: Tuesday and Thursday, 13:00-16:00.
-- An overlapping active interval blocks schedule insertion.
SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM doctors d JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND d.status = 'Active' AND u.status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctor_schedules x
        JOIN doctors d ON d.doctor_id = x.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND x.available_day = 1 AND x.status = 'Active'
          AND x.start_time < '12:00:00' AND '09:00:00' < x.end_time
    )
) INTO @seed_add_schedule_d1_mon;
INSERT INTO doctor_schedules (doctor_id, available_day, start_time, end_time, status)
SELECT d.doctor_id, 1, '09:00:00', '12:00:00', 'Active'
FROM doctors d JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor1'
  AND u.role = 'Doctor'
  AND d.status = 'Active' AND u.status = 'Active'
  AND @seed_add_schedule_d1_mon = 1;

SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM doctors d JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND d.status = 'Active' AND u.status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctor_schedules x
        JOIN doctors d ON d.doctor_id = x.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND x.available_day = 3 AND x.status = 'Active'
          AND x.start_time < '12:00:00' AND '09:00:00' < x.end_time
    )
) INTO @seed_add_schedule_d1_wed;
INSERT INTO doctor_schedules (doctor_id, available_day, start_time, end_time, status)
SELECT d.doctor_id, 3, '09:00:00', '12:00:00', 'Active'
FROM doctors d JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor1'
  AND u.role = 'Doctor'
  AND d.status = 'Active' AND u.status = 'Active'
  AND @seed_add_schedule_d1_wed = 1;

SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM doctors d JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND d.status = 'Active' AND u.status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctor_schedules x
        JOIN doctors d ON d.doctor_id = x.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor'
          AND x.available_day = 5 AND x.status = 'Active'
          AND x.start_time < '12:00:00' AND '09:00:00' < x.end_time
    )
) INTO @seed_add_schedule_d1_fri;
INSERT INTO doctor_schedules (doctor_id, available_day, start_time, end_time, status)
SELECT d.doctor_id, 5, '09:00:00', '12:00:00', 'Active'
FROM doctors d JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor1'
  AND u.role = 'Doctor'
  AND d.status = 'Active' AND u.status = 'Active'
  AND @seed_add_schedule_d1_fri = 1;

SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM doctors d JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
          AND u.role = 'Doctor'
          AND d.status = 'Active' AND u.status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctor_schedules x
        JOIN doctors d ON d.doctor_id = x.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
          AND u.role = 'Doctor'
          AND x.available_day = 2 AND x.status = 'Active'
          AND x.start_time < '16:00:00' AND '13:00:00' < x.end_time
    )
) INTO @seed_add_schedule_d2_tue;
INSERT INTO doctor_schedules (doctor_id, available_day, start_time, end_time, status)
SELECT d.doctor_id, 2, '13:00:00', '16:00:00', 'Active'
FROM doctors d JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor2'
  AND u.role = 'Doctor'
  AND d.status = 'Active' AND u.status = 'Active'
  AND @seed_add_schedule_d2_tue = 1;

SELECT (
    @seed_data_ready = 1
    AND EXISTS (
        SELECT 1 FROM doctors d JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
          AND u.role = 'Doctor'
          AND d.status = 'Active' AND u.status = 'Active'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM doctor_schedules x
        JOIN doctors d ON d.doctor_id = x.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
          AND u.role = 'Doctor'
          AND x.available_day = 4 AND x.status = 'Active'
          AND x.start_time < '16:00:00' AND '13:00:00' < x.end_time
    )
) INTO @seed_add_schedule_d2_thu;
INSERT INTO doctor_schedules (doctor_id, available_day, start_time, end_time, status)
SELECT d.doctor_id, 4, '13:00:00', '16:00:00', 'Active'
FROM doctors d JOIN users u ON u.user_id = d.user_id
WHERE u.username = 'cliniccare_doctor2'
  AND u.role = 'Doctor'
  AND d.status = 'Active' AND u.status = 'Active'
  AND @seed_add_schedule_d2_thu = 1;

-- 6. Refresh only the seeded Scheduled appointment after its appointment date
-- has passed (or its start time has passed today). Completed and Cancelled
-- appointments are never changed. Refresh only if schedule and conflict checks
-- allow the next Monday slot; otherwise leave it untouched.
SET @seed_scheduled_id = (
    SELECT appointment_id FROM appointments
    WHERE reason = 'ClinicCare demo seed [SCHEDULED-01]: future consultation'
      AND status = 'Scheduled'
    ORDER BY appointment_id
    LIMIT 1
);
SET @seed_scheduled_doctor_id = (
    SELECT doctor_id FROM appointments
    WHERE appointment_id = @seed_scheduled_id
);
SET @seed_scheduled_patient_id = (
    SELECT patient_id FROM appointments
    WHERE appointment_id = @seed_scheduled_id
);
SELECT (
    @seed_data_ready = 1
    AND @seed_scheduled_id IS NOT NULL
    AND EXISTS (
        SELECT 1 FROM patients
        WHERE patient_id = @seed_scheduled_patient_id AND status = 'Active'
    )
    AND EXISTS (
        SELECT 1 FROM doctors d
        JOIN users u ON u.user_id = d.user_id
        WHERE d.doctor_id = @seed_scheduled_doctor_id
          AND d.status = 'Active' AND u.role = 'Doctor' AND u.status = 'Active'
    )
    AND EXISTS (
        SELECT 1 FROM doctor_schedules
        WHERE doctor_id = @seed_scheduled_doctor_id
          AND available_day = 1 AND status = 'Active'
          AND start_time <= '09:00:00' AND end_time >= '09:30:00'
    )
    AND NOT EXISTS (
        SELECT 1 FROM appointments existing
        WHERE existing.doctor_id = @seed_scheduled_doctor_id
          AND existing.appointment_id <> @seed_scheduled_id
          AND existing.appointment_date = @seed_next_monday
          AND existing.status <> 'Cancelled'
          AND TIME_TO_SEC(existing.appointment_time) < 34200
          AND TIME_TO_SEC(existing.appointment_time)
              + existing.duration_minutes * 60 > 32400
    )
) INTO @seed_refresh_scheduled;
UPDATE appointments
SET appointment_date = @seed_next_monday,
    appointment_time = '09:00:00',
    duration_minutes = 30
WHERE appointment_id = @seed_scheduled_id
  AND status = 'Scheduled'
  AND (
      appointment_date < CURRENT_DATE
      OR (appointment_date = CURRENT_DATE AND appointment_time <= CURRENT_TIME)
  )
  AND @seed_refresh_scheduled = 1;

-- 7. Appointment inserts. Duplicate-marker, schedule, and overlap checks are
-- read into session variables before each INSERT; these INSERTs do not read
-- from their target table. Cancelled rows are excluded from conflict checks.

SELECT (
    @seed_data_ready = 1
    AND NOT EXISTS (
        SELECT 1 FROM appointments
        WHERE reason = 'ClinicCare demo seed [SCHEDULED-01]: future consultation'
    )
    AND EXISTS (
        SELECT 1
        FROM patients p
        JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
        JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor1'
        JOIN doctors d ON d.user_id = doctor_user.user_id
        JOIN doctor_schedules s ON s.doctor_id = d.doctor_id
        WHERE p.full_name = 'Alex Sample' AND p.contact_number = '202-555-0101'
          AND p.status = 'Active'
          AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
          AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
          AND d.status = 'Active'
          AND s.available_day = 1 AND s.status = 'Active'
          AND s.start_time <= '09:00:00' AND s.end_time >= '09:30:00'
    )
    AND NOT EXISTS (
        SELECT 1 FROM appointments existing
        JOIN doctors d ON d.doctor_id = existing.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND existing.appointment_date = @seed_next_monday
          AND existing.status <> 'Cancelled'
          AND TIME_TO_SEC(existing.appointment_time) < 34200
          AND TIME_TO_SEC(existing.appointment_time)
              + existing.duration_minutes * 60 > 32400
    )
) INTO @seed_add_scheduled_appointment;
INSERT INTO appointments (
    patient_id, doctor_id, appointment_date, appointment_time,
    duration_minutes, reason, status, created_by
)
SELECT p.patient_id, d.doctor_id, @seed_next_monday, '09:00:00',
       30, 'ClinicCare demo seed [SCHEDULED-01]: future consultation',
       'Scheduled', receptionist.user_id
FROM patients p
JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor1'
JOIN doctors d ON d.user_id = doctor_user.user_id
WHERE p.full_name = 'Alex Sample'
  AND p.contact_number = '202-555-0101'
  AND p.status = 'Active'
  AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
  AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
  AND d.status = 'Active'
  AND EXISTS (
      SELECT 1 FROM doctor_schedules s
      WHERE s.doctor_id = d.doctor_id AND s.available_day = 1
        AND s.status = 'Active' AND s.start_time <= '09:00:00'
        AND s.end_time >= '09:30:00'
  )
  AND @seed_add_scheduled_appointment = 1;

SELECT (
    @seed_data_ready = 1
    AND NOT EXISTS (
        SELECT 1 FROM appointments
        WHERE reason = 'ClinicCare demo seed [COMPLETED-01]: historical consultation'
    )
    AND EXISTS (
        SELECT 1
        FROM patients p
        JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
        JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor1'
        JOIN doctors d ON d.user_id = doctor_user.user_id
        JOIN doctor_schedules s ON s.doctor_id = d.doctor_id
        WHERE p.full_name = 'Jamie Sample' AND p.contact_number = '202-555-0102'
          AND p.status = 'Active'
          AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
          AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
          AND d.status = 'Active'
          AND s.available_day = 1 AND s.status = 'Active'
          AND s.start_time <= '10:00:00' AND s.end_time >= '10:30:00'
    )
    AND NOT EXISTS (
        SELECT 1 FROM appointments existing
        JOIN doctors d ON d.doctor_id = existing.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor1'
          AND existing.appointment_date = @seed_completed_monday
          AND existing.status <> 'Cancelled'
          AND TIME_TO_SEC(existing.appointment_time) < 37800
          AND TIME_TO_SEC(existing.appointment_time)
              + existing.duration_minutes * 60 > 36000
    )
) INTO @seed_add_completed_appointment;
INSERT INTO appointments (
    patient_id, doctor_id, appointment_date, appointment_time,
    duration_minutes, reason, status, created_by
)
SELECT p.patient_id, d.doctor_id, @seed_completed_monday, '10:00:00',
       30, 'ClinicCare demo seed [COMPLETED-01]: historical consultation',
       'Completed', receptionist.user_id
FROM patients p
JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor1'
JOIN doctors d ON d.user_id = doctor_user.user_id
WHERE p.full_name = 'Jamie Sample'
  AND p.contact_number = '202-555-0102'
  AND p.status = 'Active'
  AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
  AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
  AND d.status = 'Active'
  AND EXISTS (
      SELECT 1 FROM doctor_schedules s
      WHERE s.doctor_id = d.doctor_id AND s.available_day = 1
        AND s.status = 'Active' AND s.start_time <= '10:00:00'
        AND s.end_time >= '10:30:00'
  )
  AND @seed_add_completed_appointment = 1;

SELECT (
    @seed_data_ready = 1
    AND NOT EXISTS (
        SELECT 1 FROM appointments
        WHERE reason = 'ClinicCare demo seed [CANCELLED-01]: cancelled test consultation'
    )
    AND EXISTS (
        SELECT 1
        FROM patients p
        JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
        JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor2'
        JOIN doctors d ON d.user_id = doctor_user.user_id
        JOIN doctor_schedules s ON s.doctor_id = d.doctor_id
        WHERE p.full_name = 'Morgan Sample' AND p.contact_number = '202-555-0103'
          AND p.status = 'Active'
          AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
          AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
          AND d.status = 'Active'
          AND s.available_day = 2 AND s.status = 'Active'
          AND s.start_time <= '14:00:00' AND s.end_time >= '14:30:00'
    )
    AND NOT EXISTS (
        SELECT 1 FROM appointments existing
        JOIN doctors d ON d.doctor_id = existing.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE u.username = 'cliniccare_doctor2'
          AND existing.appointment_date = @seed_next_tuesday
          AND existing.status <> 'Cancelled'
          AND TIME_TO_SEC(existing.appointment_time) < 52200
          AND TIME_TO_SEC(existing.appointment_time)
              + existing.duration_minutes * 60 > 50400
    )
) INTO @seed_add_cancelled_appointment;
INSERT INTO appointments (
    patient_id, doctor_id, appointment_date, appointment_time,
    duration_minutes, reason, status, created_by
)
SELECT p.patient_id, d.doctor_id, @seed_next_tuesday, '14:00:00',
       30, 'ClinicCare demo seed [CANCELLED-01]: cancelled test consultation',
       'Cancelled', receptionist.user_id
FROM patients p
JOIN users receptionist ON receptionist.username = 'cliniccare_reception'
JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor2'
JOIN doctors d ON d.user_id = doctor_user.user_id
WHERE p.full_name = 'Morgan Sample'
  AND p.contact_number = '202-555-0103'
  AND p.status = 'Active'
  AND receptionist.role = 'Receptionist' AND receptionist.status = 'Active'
  AND doctor_user.role = 'Doctor' AND doctor_user.status = 'Active'
  AND d.status = 'Active'
  AND EXISTS (
      SELECT 1 FROM doctor_schedules s
      WHERE s.doctor_id = d.doctor_id AND s.available_day = 2
        AND s.status = 'Active' AND s.start_time <= '14:00:00'
        AND s.end_time >= '14:30:00'
  )
  AND @seed_add_cancelled_appointment = 1;

-- 8. Add one fictional record only for the completed seed appointment and its
-- assigned doctor. Unique appointment_id remains the final database safeguard.
SELECT (
    @seed_data_ready = 1
    AND NOT EXISTS (
        SELECT 1
        FROM medical_records mr
        JOIN appointments a ON a.appointment_id = mr.appointment_id
        WHERE a.reason = 'ClinicCare demo seed [COMPLETED-01]: historical consultation'
    )
    AND EXISTS (
        SELECT 1
        FROM appointments a
        JOIN doctors d ON d.doctor_id = a.doctor_id
        JOIN users u ON u.user_id = d.user_id
        WHERE a.reason = 'ClinicCare demo seed [COMPLETED-01]: historical consultation'
          AND a.status = 'Completed'
          AND u.username = 'cliniccare_doctor1'
          AND u.role = 'Doctor' AND u.status = 'Active'
          AND d.status = 'Active'
    )
) INTO @seed_add_medical_record;
INSERT INTO medical_records (
    appointment_id, diagnosis, treatment, notes, consultation_date, created_by
)
SELECT a.appointment_id,
       'Fictional demo diagnosis: seasonal allergies',
       'Fictional demo treatment: routine follow-up advised',
       'Synthetic seed record for interface testing; not real patient information.',
       TIMESTAMP(a.appointment_date, a.appointment_time),
       doctor_user.user_id
FROM appointments a
JOIN users doctor_user ON doctor_user.username = 'cliniccare_doctor1'
JOIN doctors d ON d.user_id = doctor_user.user_id
WHERE a.reason = 'ClinicCare demo seed [COMPLETED-01]: historical consultation'
  AND a.status = 'Completed'
  AND a.doctor_id = d.doctor_id
  AND doctor_user.role = 'Doctor'
  AND doctor_user.status = 'Active'
  AND d.status = 'Active'
  AND @seed_add_medical_record = 1
ORDER BY a.appointment_id
LIMIT 1;

COMMIT;
