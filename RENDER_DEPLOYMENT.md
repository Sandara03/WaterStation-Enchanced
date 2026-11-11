# Render classroom demo

The Dockerfile builds Quasar and serves its compiled website with Laravel/Apache at one HTTPS address. The Blueprint uses a **Free** web service and a separate **Free PostgreSQL** database. It never runs migrations against the computer's SQLite/MySQL database.

## Publish from GitHub

1. Push this source, including Dockerfile, deploy/, and render.yaml, to Sandara03/WaterStation-Enchanced.
2. Sign in at https://dashboard.render.com/ and choose **New → Blueprint**.
3. Select that repository and its main branch. Review the resources: both must say **Free**. Keep Singapore for the web service and database.
4. Apply the Blueprint and wait for the web service deployment to become Live.
5. Open the service's actual HTTPS URL. `/api/health` should return `{"status":"ok"}`.
6. Sign in with **Admin@Email.com**. For this public deployment, the password is the generated **HOSTED_ADMIN_PASSWORD** in that web service's Environment settings. The local password **Admin@123456** stays local; it does not grant access to the online database.

Render generates the application encryption key once and keeps it across restarts. Keep that value unchanged. The startup script applies normal migrations and initializes sample products plus a private administrator on the new database. Redeploys preserve changed passwords, roles, account status, product stock, and records. No registration is needed.

If the account's free database allowance is already used, stop and review that existing resource; do not delete a database or switch to a paid plan just to complete this setup.

## Android without the server connection panel

Use the **actual live URL**, not a guessed service name. After the web deployment works, rebuild the APK from the project root:

```powershell
.\BUILD_ANDROID.ps1 -ApiUrl 'https://YOUR-ACTUAL-SERVICE.onrender.com/api'
```

JDK 21 and the Android SDK setup in ANDROID_APP_GUIDE.md remain required. A build with a fixed HTTPS API URL connects automatically and hides **Server connection**, the address input, and **Test and save connection**. It replaces a saved old Wi-Fi address and clears that previous server's login/cart before connecting. An unresolved checkout is kept on its original server until resolved. A local build without a fixed HTTPS API retains its editable connection settings.

The currently installed v2.2.1 APK will not change just because the server is deployed. Install the rebuilt APK; keep the original signing key so Android accepts it as an update. If an older version code prevents updating, increment it before building.

## iPhone and desktop

Open the hosted HTTPS website. On iPhone, open it in Safari and use **Share → Add to Home Screen**. These clients use the same hosted database. The computer can be switched off once the hosted deployment works. All clients still need internet; offline sales are not included.

## Free-plan limits

- Free web services sleep after 15 minutes idle. The next request may take about a minute; login permits up to 90 seconds for this wake-up.
- Free Render PostgreSQL expires after **30 days**. Export/back up demo records before expiry. This is not permanent free storage.
- The web service's local filesystem is temporary. This setup stores sales and account data in PostgreSQL, not in SQLite inside the container. Do not use local uploaded files as permanent storage.
- This deployment starts with a **new demo database**. Your computer's existing transactions are not uploaded.

The local setup in README.md continues to work independently, even when the free hosting expires.

References: [Render Laravel/Docker guide](https://render.com/docs/deploy-php-laravel-docker), [free-plan limits](https://render.com/docs/free), [Blueprint specification](https://render.com/docs/blueprint-spec).
