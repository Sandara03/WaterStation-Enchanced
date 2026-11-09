# AHY Water Station Android app

App ID: `com.ahy.waterstation`. Version: **2.2.1** (22001). Minimum: Android 7.0 / API 24. This is a debug-signed APK for classroom installation; it is not a Google Play release.

Version 2.2.1 enables Capacitor native HTTP for app requests and uses a direct native health check. Install it over 2.2.0, then confirm **Android app v2.2.1** below Server connection. Existing app settings are retained when Android accepts the signed update. The exact phone-level cause of the earlier timeout was not confirmed without device logs; retrying on the phone is still required.

## Install the supplied APK

1. Copy **AHY_WaterStation_v2.2.1-debug.apk** to your Android phone using USB or a file-sharing method you trust.
2. Open the APK on the phone. If Android asks, allow that file manager/browser to install this APK, then install it.
3. Open **AHY Water Station**. The interface is included in the APK. Login, products, checkout, reports, and other data still need the Laravel server.

When the provided computer is serving the APK, you can instead open **http://192.168.254.156:8000/apk/AHY_WaterStation_v2.2.1.apk** on a phone on the same Wi-Fi. This versioned URL avoids confusing the older installer with the update. The address is only for the current local setup, not a public download link.

## Connect to the computer

Keep the computer and phone on the same Wi-Fi. In the project's backend terminal, run:

```powershell
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

Stop an existing server on port 8000 first. Keep this terminal open. For the installed APK, `npm run dev` is not needed because its frontend is bundled.

The provided APK starts with **http://192.168.254.156:8000/api**, the computer's Wi-Fi address when it was built. In the app's login screen, open **Server connection** and choose **Test and save connection**. Change that address if the computer's IP changes. The address is saved on the phone; changing it requires no rebuild. Do not use localhost or 127.0.0.1: they refer to the phone itself.

Sign in using the existing backend account. For the seeded local administrator:

- Email: **Admin@Email.com**
- Password: **Admin@123456**

Existing changed passwords are retained. Use the password already set on that database. To initialize a fresh backend, use `composer run setup` in backend. There is no public administrator registration.

If connection fails, open `http://COMPUTER_IP:8000/api/health` in the phone's browser. It should show `status: ok`. Check the Wi-Fi address using `ipconfig` on the computer. Guest Wi-Fi/client isolation can prevent access. If Windows Firewall asks, allow the PHP server on the private network used for the demo. Do not disable the firewall.

The APK's local HTTP demonstration connection requires the debug variant. For a hosted API, use HTTPS and update Server connection. To host the app permanently, deploy Laravel/database separately and use private production credentials. The APK does not include PHP or the SQLite/MySQL database, and does not synchronize offline sales.

## Android-specific actions

- Receipt Print / Save PDF opens Android's print dialog through a local Capacitor plugin.
- Receipt text and report CSV exports use the Android share sheet. Choose a destination app or storage option offered by your phone.
- The app uses the same server-side admin/cashier permissions and checkout validation as the browser version.
- It requests internet access; receipt/report files are written to the app cache for sharing. It does not request camera, contacts, or location permissions.

## Rebuild after changing the code

Install Node.js 22.22+, Android Studio, **JDK 21**, Android SDK Platform **36**, and Build-Tools **35.0.0**. Keep the Capacitor 8 / Gradle 8.14.3 / Android Gradle Plugin 8.13.0 toolchain together. Android Studio's JBR 25 is too new for this Gradle version; select JDK 21 in the Gradle JDK setting or pass its path to the script.

Close the frontend development server before rebuilding dependencies. From the project root:

```powershell
.\BUILD_ANDROID.ps1 -ApiUrl 'http://YOUR_COMPUTER_WIFI_IP:8000/api' -JavaHomePath 'C:\path\to\jdk-21' -AndroidSdkPath 'C:\path\to\Android\Sdk'
```

If JAVA_HOME and the default SDK location are already correct, omit those two path options. Omitting ApiUrl produces an APK that asks the user to configure Server connection before signing in. The script installs locked dependencies, runs frontend checks, builds/synchronizes Capacitor, and calls `gradlew.bat assembleDebug`.

The rebuilt APK is copied to `artifacts/AHY_WaterStation-debug.apk` and `backend/public/apk/AHY_WaterStation.apk`. The native Gradle output is `frontend/src-capacitor/android/app/build/outputs/apk/debug/app-debug.apk`.

For manual rebuilding after dependencies are installed:

```powershell
cd frontend
$env:AHY_API_URL='http://YOUR_COMPUTER_WIFI_IP:8000/api'
npm exec -- quasar build -m capacitor -T android --skip-pkg
cd src-capacitor/android
.\gradlew.bat assembleDebug
```

Configure the SDK path in the untracked `android/local.properties` or ANDROID_HOME first. To use Android Studio instead, open `frontend/src-capacitor/android`, choose Gradle JDK 21, allow synchronization, and build a debug APK. Capacitor mode is already added; do not add it again. Use the project-local Quasar command through npm rather than requiring a global CLI.

## Check on the phone before submission

Verify installation/startup, server connection, admin and cashier permissions, navigation/back button, one fictional cash checkout and change, receipt printing/sharing, inventory reduction, customer/delivery screens, report export, and reconnection after losing Wi-Fi. Check that a retry creates one transaction. Native print/share behavior depends on the installed Android services and destination apps.

The provided environment could not run the emulator because Windows hardware acceleration was unavailable, and no physical phone was connected. Compilation and API checks do not replace this device acceptance test. A release signing key and Google Play submission are separate from this debug APK.

References: [Quasar Capacitor preparation](https://quasar.dev/quasar-cli-vite/developing-capacitor-apps/preparation/), [Quasar Capacitor builds](https://quasar.dev/quasar-cli-vite/developing-capacitor-apps/build-commands/), [Capacitor 8 toolchain](https://capacitorjs.com/docs/updating/8-0).
