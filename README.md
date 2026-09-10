# QMRS MODULE 1 - COMPLETE

## Included
- Page 0 Introduction
- Registration
- OTP verification
- Login
- Forgot Password request
- Protected dashboard
- Oracle database SQL
- PHP OCI8 connection
- Registration/login/OTP/recovery APIs
- Password hashing
- PHP sessions
- Basic security helpers
- Oracle connection test

## Setup
1. Install PHP 8.x and OCI8.
2. Run `database/01_module_01_tables.sql` in Oracle.
3. Edit `config/app.php` with your Oracle username, password and service.
4. Start:
   `php -S localhost:8000 -t public`
5. Open:
   `http://localhost:8000/db-test.php`
6. Expected:
   `STATUS : CONNECTED`
7. Then open:
   `http://localhost:8000/`

## Development OTP
APP_ENV is `development`, so the API returns a development OTP to make local testing possible.
Before production, set APP_ENV to production and integrate a real email/SMS provider. Never expose OTPs in production.

## Important production hardening
- HTTPS
- Secrets outside source code
- Rate limiting
- CSRF tokens on browser forms/AJAX
- Security headers
- Email/SMS provider
- Audit logging
- Least-privilege Oracle account
- Strong deployment configuration
