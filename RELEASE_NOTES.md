# Water Station 2.2.2 — iPhone home-screen web app

- Added a standalone web-app manifest, opaque 180/192/512 px Water Station icons, and Apple home-screen launch metadata.
- Added a Safari installation-help dialog on the web login screen; it is hidden in Capacitor and standalone mode.
- Added a phone-server launcher and iPhone installation guide. The combined browser UI is served by the same Laravel server as the API, on port 8001 for the current local demonstration.
- This is the user-selected home-screen web app alternative for a Windows-only setup. No signed iOS .ipa was produced, and no Apple account or Mac build is needed to add the website to an iPhone Home Screen.
- No service worker or offline sale synchronization was added. The computer/server and Wi-Fi connection remain necessary. The Android 2.2.1 installer remains the tested Android binary; Android native source metadata advances to 2.2.2 for future rebuilds.

## Previous release: 2.2.1 — Android connection update

- Enabled the built-in CapacitorHttp fetch/XMLHttpRequest patch, so the installed APK's Axios calls use native HTTP.
- Changed the login connection test to call CapacitorHttp directly with separate connection/read timeouts.
- Added an APK version label and clearer connection-error guidance. The existing 10-second connection test and 20-second API timeouts were retained.
- Increased the Android version code to 22001 for an installable update. Phone verification is still needed; the specific device-level cause of the reported timeout has not been confirmed.

## Previous release: 2.2.0 — Android APK

- Added Capacitor 8 mode and the generated Android Gradle project, app ID `com.ahy.waterstation`, Android version 2.2.0 / code 22000, minimum Android 7 / API 24.
- Bundled the compiled frontend inside the APK. Laravel and its database remain on the computer or a hosted API.
- Added a mobile-only Server connection panel with health testing, saved API address, localhost/public-HTTP validation, and protection against switching servers during an unresolved checkout.
- Added bearer-token CORS support for the Android WebView origin.
- Added native receipt printing and receipt/CSV file sharing, plus a water-drop launcher/splash icon and native system-bar inset handling.
- Added `BUILD_ANDROID.ps1`, `START_ANDROID_BACKEND.ps1`, and an Android installation/rebuild guide. Browser startup remains `php artisan serve` and `npm run dev`.
- The APK is a debug-signed classroom demonstration build. HTTP is allowed by its debug manifest for a private Wi-Fi API; the release manifest retains the HTTPS default.

See VERIFICATION.md for observed checks and device-testing limits.

## Previous release: 2.1.1 — built-in local admin and two-terminal startup

Release date: 9 November 2026, Asia/Manila.

- Added DemoAdminSeeder, called by the default seeder, for **Admin@Email.com / Admin@123456** with the administrator role. The User model hashes the password. No registration or password prompt is needed.
- Replaced the skeleton Composer setup with a local setup that installs backend/frontend dependencies, prepares missing configuration/SQLite files, and migrates/seeds.
- Restored the documented startup: php artisan serve in backend and npm run dev in frontend. Open http://127.0.0.1:9000.
- Updated login help and startup instructions, including fixes for the missing Composer autoloader and Quasar dependencies in the screenshot.
- Repeated setup preserves keys, users/passwords/roles, sessions, and stock. Demo credentials are not seeded in production.
- Retained the dashboard, POS, retry recovery, customer, inventory, receipt, delivery, and management improvements from 2.1.0. The combined build remains optional.

This is a source ZIP. Dependencies need a one-time installation. Fresh installation seeds the documented login; existing accounts keep their existing passwords. See VERIFICATION.md for checks and limits. GitHub/Vercel publication is separate.
