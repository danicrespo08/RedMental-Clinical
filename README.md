# Sistema Clinical

Aplicación Laravel 12 / PHP 8.2 para la gestión clínica de una
organización de salud conductual. Documenta el ciclo completo de
atención del paciente en tres disciplinas: PSR (rehabilitación
psicosocial grupal), IT (terapia individual) y TCM (case management).

## Stack

- Laravel 12 · PHP 8.2+
- SQLite single-file (`database/database.sqlite`)
- Spatie Permission para roles y permisos granular
- Blade · Alpine.js · Tailwind CSS

## Setup inicial

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve --port=8002
```

Abrir http://127.0.0.1:8002/login.

## Cuentas demo

| Email                              | Contraseña     | Rol             |
|------------------------------------|----------------|-----------------|
| `superadmin@tesis.local`           | `super123`     | Super Admin     |
| `admin@demo-bh.local`              | `admin123`     | Client Admin    |
| `clinical-admin@demo-bh.local`     | `clinical123`  | Clinical Admin  |
| `david.martinez@demo-bh.local`     | `password123`  | Therapist       |
| `miguel.hernandez@demo-bh.local`   | `password123`  | Case Manager    |

## Disciplinas

### PSR (Psychosocial Rehabilitation)

Servicio grupal con admisión, intake firmado, evaluación biopsicosocial,
FARS (escala 18 dominios), plan de tratamiento con goals/objectives,
autorizaciones, sesiones grupales con asistencia, notas de progreso
(SOAP/DAP/BIRP/GIRP), service log, superbill semanal y descarga.

### IT (Individual Therapy)

Terapia individual 1:1. Cada sesión integra el formato SOAP como nota
de progreso. Incluye plan de tratamiento, autorizaciones, service log,
superbill y descarga.

### TCM (Targeted Case Management)

Coordinación de cuidados. Contactos con tipos (in-person, phone, video,
email, collateral, home_visit), plan de servicios, autorizaciones,
service log, superbill y descarga con campos específicos de case
management.

## Funcionalidades transversales

- Firma electrónica de documentos clínicos (intake, bio, FARS, plan,
  notas, descarga)
- Documentos firmados son read-only; se permiten addenda
- AI suggest para goals de planes de tratamiento (con mock fallback)
- Multi-tenancy automático vía trait `BelongsToClient`
- Audit log HIPAA-compatible con redacción de PHI

## Re-sembrar datos demo

```bash
php artisan migrate:fresh --seed
```
