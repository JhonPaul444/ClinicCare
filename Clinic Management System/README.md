# ClinicCare

ClinicCare is a PHP and MariaDB clinic management application for appointment scheduling and patient records. The browser interface uses the existing JSON API in `api/index.php` and the approved schema in `database/schema.sql`.

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Create `config/database.local.php` from `config/database.local.example.php` and set local MariaDB connection values. This local-only file is ignored by Git.
3. Use the existing `cliniccare` database. Import `database/schema.sql` only when its tables do not already exist. Do not import it over an existing installation.
4. Create the first local Admin using the one-time CLI setup in [docs/setup.md](docs/setup.md). The tool does not run through a web page and refuses to create an account after an Admin exists.
5. Open `http://localhost/Clinic%20Management%20System/login.php` and sign in.

The application does not run `seed.sql` automatically. Do not run it against real clinic data. It contains fictional records and waits for the group-approved gender options before inserting anything.

## Features

- Admin: dashboard, patient, doctor, specialization, schedule, account, appointment, and report management.
- Receptionist: patient registration and maintenance, appointment booking and rescheduling, daily summaries, and reports.
- Doctor: assigned appointments, authorized patient details and history, and consultation records that complete appointments atomically.

See [docs/backend-api.md](docs/backend-api.md) for API routes and [docs/test-cases.md](docs/test-cases.md) for verification steps.

## Known group decision

The database stores patient gender as `VARCHAR(30)`. The group has not confirmed the allowed values. The interface accepts a value without inventing options; confirm the list before replacing the text field with a dropdown or adding stricter validation.
