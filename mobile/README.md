# CheckA+ mobile (Capacitor · Android first)

A thin native Android shell that loads the live CheckA+ site, so we can ship to
the Play Store and (next step) add native push notifications. Built entirely in
the cloud by GitHub Actions — **no local Android tooling required.**

## ⚠️ Set the live URL first
Edit `capacitor.config.json` → `server.url` to your real subdomain, e.g.
`https://checka.yourdomain.co.za`. (The current value is a best-guess and must
be confirmed, or the app will show the loading placeholder.)

## Getting a testable APK
1. Make sure `mobile/` and `.github/workflows/android.yml` are committed to the
   repo (github.com/mkazre/checka) — they're pushed from the server like the app.
2. On GitHub → **Actions** tab → run **"Build Android (CheckA+)"** (it also runs
   automatically when `mobile/**` changes).
3. When it finishes, open the run → **Artifacts** → download **checka-debug-apk**.
4. Copy `app-debug.apk` to your phone and install it (allow "install from unknown
   sources"). It opens CheckA+ full-screen as an app.

## Next steps (later phases)
- **Push notifications:** create a free Firebase project, add
  `@capacitor/push-notifications`, drop `google-services.json` into
  `android/app/`, and the server already has the token-registration endpoint
  ready (`/push/register`) + `NotificationService::pushToUser`.
- **Play Store release:** add a signing keystore (can be generated in the
  workflow) and switch the build to `bundleRelease` to produce a signed `.aab`.
- **iOS:** add the iOS platform later (needs a Mac + Apple Developer account).
