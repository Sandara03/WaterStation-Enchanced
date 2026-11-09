# Run Water Station 2.2.2

For an iPhone home-screen app on Windows, start with **IPHONE_INSTALLATION.md**.

For the Android APK, read **ANDROID_APP_GUIDE.md**. These instructions start the browser version.

## Administrator login

- Email: **Admin@Email.com**
- Password: **Admin@123456**

The administrator is included in `backend/database/seeders/DemoAdminSeeder.php`. One-time setup seeds it automatically into a fresh local database, with a hashed password. No registration, account creation command, or password prompt is needed.

## One-time setup

Extract this updated ZIP into a new folder and open **AHY_WaterStation_Final** in VS Code. Install PHP 8.2+, Composer 2, and Node.js 22.22+ first. Enable PHP's PDO SQLite extension. Internet access is needed to install dependencies.

From the project root:

```powershell
cd backend
composer run setup
```

This installs backend and frontend dependencies, creates a missing .env with a private key, creates the default SQLite database, migrates, and seeds the admin plus four water products. Existing keys, records, passwords, and stock are preserved. Setup is for APP_ENV=local.

## Start the website

Terminal 1, starting from the project root:

```powershell
cd backend
php artisan serve
```

Terminal 2, starting from the project root:

```powershell
cd frontend
npm run dev
```

Open **http://127.0.0.1:9000** and sign in using the credentials above. Laravel runs at **http://127.0.0.1:8000**; the frontend forwards /api requests to it. Keep both terminals open. Ctrl+C stops each server. On later runs, only these two start commands are needed.

## Fix the screenshot errors

- **Missing vendor/autoload.php:** backend dependencies were not installed. Run `composer run setup` in backend.
- **quasar is not recognized during npm run dev:** frontend dependencies were not installed. Setup installs them; alternatively, run `npm install` in frontend. Use `npm run dev` to find the local Quasar dependency. A global Quasar installation is unnecessary.
- **npm.ps1 cannot be loaded:** use `npm.cmd run dev` in PowerShell or use VS Code's Command Prompt terminal. Changing Windows execution policy is unnecessary.
- **Missing SQLite driver:** enable pdo_sqlite and sqlite3 in the terminal's PHP configuration; `php --ini` identifies it.

## Existing installations

Back up your database and private .env first. Setup never resets existing passwords or roles. If Admin@Email.com already exists, it keeps its existing password. For the documented default login, use a fresh local extraction/database. Do not delete real transaction records to obtain it.

After manual dependency/configuration setup, `php artisan migrate --seed` applies migrations and seeds the same login. Demo credentials are created only in local/testing environments. Change the shared demo password before public hosting.
