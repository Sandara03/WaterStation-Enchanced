# Install Water Station on an iPhone Home Screen

This is the Safari home-screen web app selected for the Windows-only setup. It does not install an .ipa or require an Apple Developer account. The supplied manifest and Apple metadata request standalone launch; Safari performs the Home Screen installation.

## Current computer

The current website URL is **http://192.168.254.156:8001/**. Open that exact website address in Safari on the iPhone. Do not append /api or /api/health: those are server endpoints, not the app interface.

Keep the iPhone and computer on the same Wi-Fi. Keep Laravel running on port 8001 and keep the computer awake.

## Add the icon

1. Open the website in **Safari on the iPhone**, rather than a chat app's embedded browser.
2. Tap Safari's **Share** button. On some layouts, open the page menu and then Share.
3. Choose **Add to Home Screen**.
4. If **Open as Web App** is shown, leave it on.
5. Tap **Add**. Open the **AHY Station** icon on the Home Screen.
6. Sign in using the existing station account. The seeded admin is **Admin@Email.com / Admin@123456** (capital A in the password). A changed account password remains unchanged.

The web login screen also has an **Add to Home Screen** help button. That button displays instructions; Safari's own Share menu completes installation. Once the web app is in standalone mode, the help button is hidden. Safari and the installed web app can keep separate sessions, so signing in again after adding the icon is normal.

## Start it again later

From a terminal inside backend:

```powershell
php artisan serve --host=0.0.0.0 --port=8001
```

Or run `START_PHONE_BACKEND.ps1` from the project root. Stop an existing server on that port before starting another. Keep the terminal open.

The compiled website and API share this server, so the installed iPhone web app does not need `npm run dev`. Use the existing two-server commands only for live frontend development.

## Prepare a fresh copy on Windows

Install PHP 8.2+, Composer 2, and Node.js 22.22+ with the PHP extensions listed in README.md. From the project root:

```powershell
cd backend
composer run setup
cd ..
.\BUILD_LOCAL.ps1
.\START_PHONE_BACKEND.ps1
```

If PowerShell blocks .ps1 files, use the manual build commands instead of changing execution policy:

```powershell
cd frontend
npm install
$env:AHY_COMBINED_BUILD='1'
npm run build
New-Item -ItemType Directory -Path ../backend/public/app -Force
Copy-Item -Path dist/spa/* -Destination ../backend/public/app -Recurse -Force
Remove-Item Env:AHY_COMBINED_BUILD
cd ../backend
php artisan serve --host=0.0.0.0 --port=8001
```

Those manual commands assume backend setup has already completed. On another computer, use its Wi-Fi IPv4 address from `ipconfig` instead of 192.168.254.156. If the address changes after installation, open the new website URL in Safari and add the icon again.

## What remains required

The computer hosts Laravel and the database. Sales, stock, login, reports, and other data operations need it to be reachable. No offline sale synchronization or service-worker cache was added. Home Screen launch on iOS does not require a service worker; this local HTTP demonstration is not a hosted, offline-capable PWA.

The root page, manifest, icon links, login, and responsive layout can be checked from Windows. Actual Safari Add to Home Screen, installed launch, keyboard, printing, and file downloads still need checking on an iPhone. A native .ipa remains a separate Mac/cloud build and signing task.

References: [Apple's iPhone installation steps](https://support.apple.com/guide/iphone/open-as-web-app-iphea86e5236/ios), [WebKit Home Screen behavior](https://webkit.org/blog/17333/webkit-features-in-safari-26-0/), [WebKit manifest and standalone mode](https://webkit.org/blog/13878/).
