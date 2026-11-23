# ClinicCare manual verification

Use a local development database and fictional test entries only. Do not use real patient data. Creating the first Admin is a deliberate database change; follow `docs/setup.md` only when ready. The integration work does not run the seed script.

## Authentication and access

1. Open `login.php`; verify the form includes a CSRF token and an invalid username/password shows an escaped error message.
2. Sign in as each role after creating its local account. Confirm each role lands on its own dashboard.
3. Try opening an Admin URL while signed out; confirm it redirects to sign-in. Try an Admin URL as Receptionist or Doctor; confirm HTTP 403.
4. Sign out using the profile menu. Confirm the session ends and a protected URL redirects to sign-in, including after refreshing or using browser Back.
5. Mark an account inactive and verify that account cannot continue using the app.

## Admin

1. Confirm the dashboard counts and recent appointments come from the API and show useful zero-data states in a fresh database.
2. Create, search, view, edit, and deactivate/reactivate a patient. Confirm changes remain after refresh.
3. Create a specialization. Create a Doctor user, then link it to a specialization in Doctors. Edit and deactivate/reactivate the profile.
4. Create, edit, activate, and deactivate a doctor schedule. Try an overlapping active schedule and confirm the API rejects it.
5. Create an appointment with an active patient and doctor inside an active schedule. Try an overlapping appointment and one outside schedule hours; confirm both are rejected.
6. Reschedule an appointment, cancel it, and verify cancelled appointments no longer block that time.
7. Confirm `Completed` cannot be set through the appointment status endpoint; it must be set only when the assigned Doctor creates the consultation record in the same transaction.
8. Create and edit a user account. Confirm no password hash is ever returned or displayed.
9. Run reports for a chosen date range and compare totals with the appointments and patients list.

## Receptionist

1. Confirm the sidebar omits Admin-only users and medical records.
2. Register, search, view, edit, and deactivate/reactivate patients.
3. Book an appointment, view it, reschedule it, and cancel it. Confirm the API rejects unauthorized status changes.
4. Run reports and verify the results match the selected date range.

## Doctor

1. Confirm the dashboard and appointment list contain only appointments assigned to the signed-in doctor.
2. Open an assigned appointment and confirm authorized patient details and medical history are available.
3. Create a consultation record for an eligible appointment. Verify the appointment becomes Completed and the medical record appears.
4. Attempt to record the same appointment twice, record another doctor's appointment, and access unrelated patient history; verify the API rejects unauthorized or duplicate operations.
5. Mark an appointment No-show and confirm it no longer appears as a scheduled booking.

## API and persistence

- Use browser Network tools to confirm requests go to `api/index.php?resource=...` and successful writes are followed by fresh API reads.
- Confirm state-changing requests send `X-CSRF-Token`; try an invalid token and confirm the API rejects it.
- Confirm validation errors, conflicts, expired sessions, and server errors appear as readable page messages.
- Check the XAMPP PHP and MariaDB logs if a request fails. Never paste database secrets or real patient details into a report.

## Environment limits

Tests requiring an authenticated role need locally created accounts. Do not use the initial Admin setup tool or create records until authorized by the project owner. The gender field remains free text until the group confirms approved values.
