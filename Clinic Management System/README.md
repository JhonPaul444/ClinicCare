# ClinicCare

ClinicCare is the group's shared PHP and MySQL clinic appointment and patient records application. Backend authentication, role checks, PDO access, and JSON endpoints are documented in [docs/backend-api.md](docs/backend-api.md).

The backend calls the shared `cliniccare_pdo()` connection interface and uses PALO's approved `cliniccare` schema. It does not create or alter the database or its files.

The role page files are protected route stubs for the frontend integration. Galemma's interfaces can use the API routes described in the backend API guide. Gender option validation remains subject to the group's approved values.
