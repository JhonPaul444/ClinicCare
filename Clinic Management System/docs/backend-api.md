# ClinicCare backend API

The PHP backend lives in this existing application. It uses the shared PDO connection in `config/database.php` and the approved `cliniccare` database. It does not create or alter tables.

## Database connection

The backend expects the shared `cliniccare_pdo()` function from `config/database.php`, using the existing `cliniccare` database configured by PALO. This backend does not implement connection setup or change any database files. Set `CLINIC_TIMEZONE` to the clinic's IANA timezone if it differs from `Asia/Singapore`.

## Request conventions

- Base URL: `api/index.php?resource=<resource>[/<id>]`
- Use JSON request bodies (`Content-Type: application/json`) or regular form bodies.
- Mutating requests require the session's CSRF token in `X-CSRF-Token` (or `_csrf` for forms). Obtain a pre-login token from `GET ?resource=auth/csrf`; successful login returns a fresh authenticated token too.
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
| GET, POST | `doctors` | Admin; list/create profiles |
| GET, PUT, PATCH | `doctors/{id}` | Admin; details, specialization/license update, activate/deactivate |
| GET, POST | `specializations` | Admin; list/create |
| PUT | `specializations/{id}` | Admin; update or deactivate |
| GET, POST | `schedules` | Admin; list/create schedules |
| PUT | `schedules` | Admin; update using `schedule_id` in body |
| PATCH | `schedules/{id}` | Admin; activate/deactivate |
| GET | `dashboard` | Role-specific Admin/Receptionist/Doctor summary counts |
| GET, POST | `appointments` | Admin/Receptionist list or book; Doctor sees own assignments |
| GET, PUT, PATCH | `appointments/{id}` | Get authorized details; reschedule (Admin/Receptionist); update status (authorized staff/assigned Doctor) |
| GET, POST | `medical-records` | Admin can view; Doctor can view authorized history/create a consultation record |
| GET | `reports` | Admin, Receptionist; date range totals by status and doctor |

Appointment list filters: `date`, `status`, `q`, and Admin-only `doctor_id`. Reports accept `from` and `to` in `YYYY-MM-DD` form.
Medical-record lists can be filtered by `patient_id`; doctor results remain limited to patients assigned to or previously treated by that doctor.

## Security and booking rules

Sessions regenerate their ID on login, use HTTP-only/SameSite cookies, and require CSRF tokens on state changes. Queries use PDO prepared statements. Role checks are enforced in the API and existing role page entry points. Patient and medical-record fields are selected separately so receptionist patient routes do not return diagnoses or notes.

Appointment creation and rescheduling transact and lock doctor rows in ascending ID order before checking active users/profiles, patient status, schedule coverage, and time conflicts. Conflict intervals use `[start,end)` semantics and omit Cancelled appointments. Schedule changes lock the doctor and reject overlap or removal of coverage for existing upcoming appointments. A doctor can create one record for an appointment assigned to their profile; this atomically completes the appointment.

## Pending group confirmation

The prompt says patient gender options are still being decided. The backend does not invent a list; when PALO's approved schema defines `gender` as an `ENUM`, validation follows those exact database options. If it is a free-text column, the approved values still need confirmation from the group and frontend before final validation can be completed. Column character limits are read from MySQL metadata.
