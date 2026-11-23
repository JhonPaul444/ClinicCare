# Local XAMPP setup

## Start the application

1. Start **Apache** and **MySQL** in XAMPP.
2. Keep the project under `C:\xampp\htdocs\Clinic Management System`, or copy the current `feature/integration` files there.
3. Confirm `config/database.local.php` points to the local `cliniccare` database. The current local XAMPP setup uses port `3307`; use the port shown in your XAMPP panel.
4. In phpMyAdmin, expand `cliniccare` and check for the seven tables: `users`, `patients`, `specializations`, `doctors`, `doctor_schedules`, `appointments`, and `medical_records`.
5. If the tables are absent, import `database/schema.sql`. Do not import it again if those tables already exist.

The application and the CLI setup tool use `cliniccare_pdo()` from `config/database.php`. No database file is changed by the setup tool.

## Create the first local Admin

The setup tool is intended for a deliberate local command-line action. It refuses web requests, requires two matching passwords of at least 12 characters, hashes the password with `password_hash(..., PASSWORD_DEFAULT)`, serializes setup attempts with a MariaDB advisory lock, and stops if an Admin already exists. After insertion, it reads the new row and verifies the hash with `password_verify()` without displaying the password. It does not expose an Admin registration page.

In PowerShell, change to the project folder. Prompt for the password twice as hidden secure strings and invoke the tool:

```powershell
Set-Location "C:\Users\Jhon Paul\Documents\GitHub\ClinicCare\Clinic Management System"
$firstPassword = Read-Host "New local Admin password" -AsSecureString
$secondPassword = Read-Host "Confirm local Admin password" -AsSecureString
$secureToPlain = {
    param($secureValue)
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureValue)
    try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}
try {
    $env:CLINICCARE_INITIAL_ADMIN_PASSWORD = & $secureToPlain $firstPassword
    $env:CLINICCARE_INITIAL_ADMIN_PASSWORD_CONFIRM = & $secureToPlain $secondPassword
    & "C:\xampp\php\php.exe" ".\tools\create-first-admin.php" "Local Clinic Admin" "clinic_admin"
}
finally {
    Remove-Item Env:CLINICCARE_INITIAL_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    Remove-Item Env:CLINICCARE_INITIAL_ADMIN_PASSWORD_CONFIRM -ErrorAction SilentlyContinue
    $firstPassword.Dispose()
    $secondPassword.Dispose()
}
```

Choose a username not already in use. The password is supplied only through the current PowerShell process environment and is removed afterward. The script stores only its password hash. It creates an account only when no Admin account exists. It has not been run as part of integration because account creation changes the database.

After setup, open `http://localhost/Clinic%20Management%20System/login.php`. Create Receptionist and Doctor accounts from the Admin **Users** page. Create a specialization, then a Doctor profile linked to a Doctor account, and set that doctor's schedule before creating appointments.

## Data safety

- Existing clinic data is preserved; the app does not reset or delete records.
- `database/seed.sql` is optional fictional development data and is not run automatically. Its gender placeholders are intentionally unset. Do not run it unless the group approves the option values and sample records.
- If the database already has an Admin account, use its normal credentials. The first-Admin tool will refuse to add another.
- Keep `config/database.local.php` private and do not commit database credentials.

## Gender values

The schema requires `gender` but defines it as free text. The integration does not invent a dropdown list. Confirm the clinic's approved values with the group before standardizing entry choices.
