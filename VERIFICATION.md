# Water Station 2.2.2 verification

9 November 2026, Asia/Manila.

## iPhone home-screen web app

- The user chose the Safari home-screen web app alternative for a Windows-only setup. No signed .ipa was produced.
- 10 frontend tests passed, Vue/TypeScript passed, and the combined production SPA build passed. Backend application code was unchanged.
- The compiled website and API are served together at http://192.168.254.156:8001/. The root page returned HTTP 200.
- /app/manifest.webmanifest returned HTTP 200 with application/manifest+json. Its display is standalone, scope/start URL are /, and it references 192/512 px icons.
- Apple touch icon returned HTTP 200 with image/png. Generated 180/192/512 px icons were checked for their declared dimensions and opaque RGB format.
- The built HTML references the correct /app/ manifest and Apple touch icon URLs and includes Apple home-screen metadata.
- Browser verification at 390 px width showed login, the Safari installation-help dialog, successful admin sign-in, and the dashboard. Login page width was 390 px; dashboard document width was 382 px, without horizontal overflow. These checks used Chromium viewport sizing, not an iPhone simulator.
- The installation-help dialog was captured in docs/screenshots/iphone-home-screen-help.png. No browser console errors were reported in the completed preview. The viewport override was reset afterward.
- BUILD_LOCAL.ps1, START_PHONE_BACKEND.ps1, and START_ANDROID_BACKEND.ps1 parsed successfully.
- The Desktop project's private .env and database were preserved. Its prebuilt browser UI was added under backend/public/app. The release ZIP includes that compiled UI, with source and setup instructions, but excludes private environments, databases, dependency folders, native build caches, and signing keys.
- Actual Safari Add to Home Screen, installed launch, keyboard behavior, printing, and downloads still need checking on an iPhone. No service worker, offline sales, public hosting, or native iOS signing is claimed.
- The Android 2.2.1 APK remains the tested Android installer. The user confirmed its connection and dashboard login after Laravel was started on port 8001. No 2.2.2 Android APK was generated for this web-app change.

## Previous 2.2.1 build evidence

## Connection update checks

- 10 frontend tests passed; Vue/TypeScript and browser production build passed.
- Capacitor UI built and synchronized; Gradle `:app:assembleDebug` completed successfully in 58 seconds (129 tasks, 27 executed).
- The packaged APK config contains `plugins.CapacitorHttp.enabled=true`. Connection testing uses CapacitorHttp directly; Axios uses the native fetch/XMLHttpRequest patch in the APK. Timeout values were retained.
- APK metadata: com.ahy.waterstation, versionName 2.2.1, versionCode 22001, minSdk 24, targetSdk 36. The login screen includes the 2.2.1 version label.
- apksigner verification passed. Its signing certificate matches 2.2.0, supporting installation as an update.
- APK size: 4,834,691 bytes. SHA-256: a84040bf5a2f68ab4e687840deb4a6210fbabdc8937666604003f21b9a7f9d37.
- The computer's LAN API returned 200 / ok and the Android origin CORS header. The user also reported that the health URL opened in Chrome on the phone while the previous APK timed out. No phone logs were available to prove the exact device-level cause.
- No physical Android phone was attached to ADB. The new APK's actual connection/login result requires a phone retest. This update changes the networking path; compilation and packaged configuration checks do not establish that the reported phone issue is resolved.
- Backend code was unchanged; the 43-test backend result below is the previous 2.2.0 run.

## Previous 2.2.0 build evidence

| Check | Observed result |
| --- | --- |
| Backend regression suite | 43 tests, 242 assertions passed |
| Frontend suite | 10 tests passed, including checkout/recovery and mobile API URL validation |
| Vue/TypeScript | Passed |
| Browser production build | Passed |
| Capacitor production UI | Built and synchronized to Android; bundled assets contain the selected Wi-Fi API address |
| Android debug APK | Gradle assembleDebug completed BUILD SUCCESSFUL in 9m 11s, 184 tasks executed; native Java/Kotlin code compiled |
| APK signature | apksigner verify passed; Android Debug RSA 2048 signer, APK Signature Scheme v2 |
| Package metadata | com.ahy.waterstation, versionName 2.2.0, versionCode 22000, minSdk 24, targetSdk 36 |
| App size | 4,834,371 bytes |
| APK SHA-256 | b9406227950d93bc9a7c1f7c4634eba5999b0fdd38e1faffff32235721cdcb6f |
| Wi-Fi API | http://192.168.254.156:8000/api/health returned 200 / ok |
| Android-origin CORS | Origin https://localhost accepted; login preflight returned 204; an unlisted web origin was not granted CORS access |
| Default administrator | Admin@Email.com / Admin@123456 signed in with the admin role against the Desktop database; test session signed out afterward |
| Script syntax | BUILD_ANDROID.ps1 and START_ANDROID_BACKEND.ps1 parsed successfully |

## Reproducibility

- PHP 8.2.12 / PHPUnit 11.5.57, Node.js 24.14.0, Quasar 2.34.0 / app-vite 2.6.2, Capacitor core/CLI/Android 8.6.0, app 8.1.2, filesystem 8.1.5, share 8.0.3.
- JDK 21.0.13, Gradle 8.14.3, Android Gradle Plugin 8.13.0, Android platform 36, Build-Tools 35.0.0. The Gradle wrapper distribution checksum is pinned. JDK and platform downloads were checked against publisher checksums.
- Native/frontend builds ran in work/frontend-check. Java, SDK additions, caches, and build outputs were kept in workspace build directories. Source and Android project files were copied to the existing Desktop project; its environment and database were preserved.
- The managed Windows backend test runtime retains the prior PHPUnit readability and Laravel cache-writability probe adaptations. These dependency adaptations are not in the source ZIP. Tests use isolated in-memory SQLite.
- The local setup helper and default administrator behavior were verified in 2.1.1. The web runtime is still started by php artisan serve and npm run dev. For the phone, Laravel must listen on the LAN; the compiled APK needs no frontend development server.
- Non-fatal Android build messages included SDK XML-version and flatDir repository warnings. These did not prevent generating or verifying the APK.

## Device acceptance still required

No physical Android phone was connected. A disposable emulator could not start because Windows hardware acceleration was unavailable. No Windows virtualization features were changed. Therefore Android installation/startup, touch navigation, actual print/share dialogs, phone-to-computer firewall access, and app resume have not been claimed as tested.

Use the real-phone checklist in ANDROID_APP_GUIDE.md. Compilation/signature verification and API tests do not replace device testing. Existing screenshots under docs/screenshots/final show the earlier 2.1.0 browser build, not this APK. MySQL concurrency, physical printers, hosted APIs, production signing, and store publication are separate checks. GitHub/Vercel publication was not performed.
