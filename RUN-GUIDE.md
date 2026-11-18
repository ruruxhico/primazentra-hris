# PrimaZentra HRIS — Run Guide

The system is three CodeIgniter 4 apps that exchange data over REST APIs and
store their own data in one Firebase (Firestore) project:

| App | Folder | URL | Owns |
|---|---|---|---|
| PrimaZentra HRIS (main) | `primazentra-hris` | http://localhost/primazentra-hris/ | users, employees |
| Attendance | `hris-attendance` | http://localhost/hris-attendance/ | DTR records, period locks |
| Payroll | `hris-payroll` | http://localhost/hris-payroll/ | payroll runs and details |

Each app's own `README.md` documents its APIs, rules and formulas.

## What you receive separately

These are not in the source code and are handed over with the submission:

1. **`firebase_key.json`**: a Firebase service-account key for the project
   `primazentra-hris`, issued for this review only.
2. **A login**: email and password for an Administrator account.

## 1. Requirements

- Windows with **XAMPP** (PHP **8.2** and Apache). Check with `php -v`.
- **Composer** 2 (https://getcomposer.org).
- In `C:\xampp\php\php.ini`, these extensions must be enabled (remove the
  leading `;`): `curl`, `intl`, `mbstring`, `openssl`, `gd`, `zip`, `sodium`.
  Restart Apache after editing.
- Internet access (Firestore is a cloud database).

## 2. Put the folders in place

Copy the three folders into `C:\xampp\htdocs\` so they sit side by side:

```
C:\xampp\htdocs\primazentra-hris
C:\xampp\htdocs\hris-attendance
C:\xampp\htdocs\hris-payroll
```

The folder names must stay as shown; the URLs and settings depend on them.

## 3. Install dependencies

Run in each of the three folders:

```
composer install --ignore-platform-req=ext-grpc
```

The flag is needed because the Firestore library lists the gRPC extension;
the apps use Firestore over REST, so gRPC is not required.

## 4. Configure

Run once, from `C:\xampp\htdocs\primazentra-hris`:

```
php tools/hris_setup.php --key=C:\path\to\firebase_key.json
```

This writes a `.env` in each app from its `.env.example`, with six newly
generated API keys that match across the apps, and copies the Firebase key
into each app's `writable/credentials/`. To configure by hand instead, copy
each `.env.example` to `.env` and follow the comments in it.

## 5. Load the demo data (optional)

From `C:\xampp\htdocs\primazentra-hris`:

```
php tools/hris_demo_data.php restore --yes
```

This resets the HRIS data to the demo's starting point: two employees, three
DTR records for 16–17 November 2026 (one still pending approval) and draft
payrolls for both November periods. It deletes any other employees, DTR and
payroll data in the project.

## 6. Start and log in

1. Start **Apache** in the XAMPP Control Panel. MySQL is not needed.
2. Open http://localhost/primazentra-hris/ and log in with the account you
   received.
3. Open http://localhost/hris-attendance/ and http://localhost/hris-payroll/.
   Both log in with the same PrimaZentra account (the main app checks it
   over its API).

## 7. Check that the apps are connected

| Do this | Expect |
|---|---|
| Main → **Employees** | Ana and Dan, with this period's attendance and latest pay per employee. Those two columns come from Attendance and Payroll. |
| Attendance → enter a DTR for Ana, then approve it | The record shows as approved. |
| Payroll → open **2026-11-B** | The draft recomputes automatically and includes the approved DTR. |
| Payroll → **Finalize** | Attendance locks that period's DTR; editing one is refused. |
| Payroll → **Reopen for corrections** | Attendance unlocks the period. |
| Rename the `hris-attendance` folder for a moment, then reload Main → Employees | The page still loads and says Attendance did not respond. Rename it back afterwards. |

## 8. Run the tests

In each folder:

```
vendor\bin\phpunit
```

Expected: Payroll 28 tests, Attendance 9, main app 17 in `tests/unit`
(`vendor\bin\phpunit tests\unit`). The main app's `ExampleDatabaseTest`
comes from the CodeIgniter starter and needs SQLite, so it is not part of
the HRIS suite.

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `Unauthorized` / `Missing or invalid X-Api-Key` | The keys do not match across the three `.env` files. Run `php tools/hris_setup.php --force` to regenerate all three. |
| "a required system did not respond" | The other app's URL in `.env` is wrong, or its folder is missing. |
| `Invalid firebase_key.json` or Firestore permission errors | The key file is missing from `writable/credentials/` in that app, or it was revoked. |
| Composer fails on `ext-grpc` | Add `--ignore-platform-req=ext-grpc`. |
| Blank page or 500 error | Check `writable/logs/` in that app. |
