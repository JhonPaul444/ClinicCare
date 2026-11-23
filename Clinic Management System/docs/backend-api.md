# ClinicCare backend API

The PHP backend lives in this existing application. It uses the shared PDO connection in `config/database.php` and the approved `cliniccare` database. It does not create or alter tables.

## Database connection

The backend expects the shared `cliniccare_pdo()` function from `config/database.php`, using the existing `cliniccare` database configured by PALO. This backend does not implement connection setup or change any database files. Set `CLINIC_TIMEZONE` to the clinic's IANA timezone if it differs from `Asia/Singapore`.

## Request conventions

- Base URL: `api/index.php?resource=<resource>[/<id>]`
- Use JSON request bodies (`Content-Type: application/json`) or regular form bodies.
- Mutating requests require the session's CSRF token in `X-CSRF-Token` (or `_csrf` for forms), including login. Obtain a pre-login token from `GET ?resource=auth/csrf`; successful login returns a fresh authenticated token too.
- Responses are JSON with `data`, `message`, or `error`. Validation errors use HTTP 422; unauthenticated requests use 401; disallowed roles use 403; conflicts use 409.

## Routes

| Method | Resource | Allowed roles / purpose |
| --- | --- | --- |
| POST | `auth/login` | Authenticate; returns user and CSRF token |
| GET | `auth/csrf` | Any session; get a CSRF token before login or after authentication |
| POST | `auth/logout` | Any authenticated user; invalidates session |
| GET, POST | `patients` | Admin, Receptionist; list/search or register |
| GET, PUT, PATCH | `patients/{id}` | Admin, Receptionist; details, update demographics, set status |
| GET, POST | `users` | Admin; list/create accounts |
| GET, PUT | `users/{id}` | Admin; view or update profile, role, status, optional password |
| GET | `doctors` | Admin, Receptionist; list profiles for appointment booking |
| POST | `doctors` | Admin; create profiles |
| GET, PUT, PATCH | `doctors/{id}` | Admin; details, specialization/license update, activate/deactivate |
| GET, POST | `specializations` | Admin; list/create |
| PUT | `specializations/{id}` | Admin; update or deactivate |
| GET, POST | `schedules` | Admin; list/create schedules |
| PUT | `schedules` | Admin; update using `schedule_id` in body |
| PATCH | `schedules/{id}` | Admin; activate/deactivate |
| GET | `dashboard` | Role-specific Admin/Receptionist/Doctor summary counts |
| GET, POST | `appointments` | Admin/Receptionist list or book; Doctor sees own assignments |
| GET, PUT, PATCH | `appointments/{id}` | Get authorized details; reschedule (Admin/Receptionist); cancel or mark No-show while Scheduled |
| GET, POST | `medical-records` | Admin can view; Doctor can view authorized history/create a consultation record |
| GET | `reports` | Admin, Receptionist; date range totals by status and doctor |

Appointment list filters: `date`, `status`, `q`, and Admin-only `doctor_id`. Reports accept `from` and `to` in `YYYY-MM-DD` form.
Medical-record lists can be filtered by `patient_id`; doctor results remain limited to patients assigned to or previously treated by that doctor.

## Security and booking rules

Sessions regenerate their ID on login, use HTTP-only/SameSite cookies, and require CSRF tokens on state changes. Queries use PDO prepared statements. Role checks are enforced in the API and existing role page entry points. Patient and medical-record fields are selected separately so receptionist patient routes do not return diagnoses or notes.

Appointment creation and rescheduling transact and lock doctor rows in ascending ID order before checking active users/profiles, patient status, schedule coverage, and time conflicts. Conflict intervals use `[start,end)` semantics and omit Cancelled appointments. Schedule changes lock the doctor and reject overlap or removal of coverage for existing upcoming appointments. A doctor can create one record for an appointment assigned to their profile; this atomically completes the appointment. The appointment status endpoint does not accept `Completed`, so completion only occurs with record creation in the same transaction.

## Pending group confirmation

The current `database/schema.sql` defines `patients.gender` as `VARCHAR(30)`. The group has not confirmed an allowed-value list, so the interface accepts a required text value and the backend enforces the database length without inventing options. Confirm the approved values before replacing the text input with a dropdown or adding an allowlist. Other column lengths are read from MySQL metadata.

For XAMPP setup and the one-time local Admin creation process, see [setup.md](setup.md). The web frontend calls these endpoints with same-origin session cookies and a CSRF token.
