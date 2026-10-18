# SIA Track

SIA Track is a Laravel attendance and registration system with role-based
accounts, academic sections, class schedules, and an optional ACR122U NFC
reader bridge.

## Requirements

- PHP 8.2 or later
- Composer
- Node.js and npm
- MySQL (the current project `.env` uses MySQL) or SQLite
- Python 3 on Windows if the NFC bridge is required
- An installed ACR122U/PCSC smart-card reader for NFC attendance

## First-time setup

Run these commands from the project folder:

```bat
composer install
copy .env.example .env
php artisan key:generate
```

If a `.env` file already exists, do not overwrite it. Verify the database
settings before continuing. For MySQL, set `DB_DATABASE`, `DB_USERNAME`, and
`DB_PASSWORD` in `.env`, and make sure the database already exists. For
SQLite, use `DB_CONNECTION=sqlite` and create the database file:

```bat
type nul > database\database.sqlite
```

Install and build the frontend assets:

```bat
npm install
npm run build
php artisan storage:link
```

## Database setup order

Run the migration first, then run the seeders:

```bat
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` runs the seeders in this order:

1. `RoleSeeder` - creates ADMIN, FACULTY, STUDENT, and MANAGEMENT roles.
2. `DefaultUsersSeeder` - creates the administrator and management accounts,
   and creates the active `2025-2026`, `1st Semester` academic period when
   needed.
3. `DemoDataSeeder` - creates the demo faculty, sections, students, schedules,
   and student-section assignments.

Management accounts (role 4) sign in to a separate, read-only portal. From
**User Management**, an administrator can enable or disable login and grant
each management account either or both permissions: the Principal Evaluation
form for evaluating faculty, and the dashboard/master lists for viewing
attendance summaries, students, teachers, sections, and schedules. Both
permissions are off for existing accounts until an administrator grants them.

The demo timetable includes eight subject teachers and eight class periods per
section from Monday through Friday. Periods have varied durations and visible
gaps; STEM A also has overlapping Math and Chemistry classes from 9:00 AM to
11:00 AM and PE from 3:00 PM to 5:00 PM.

To complete an existing demo database without deleting student accounts,
sections, or the existing sample schedules, run:

```bat
php artisan db:seed --class="Database\Seeders\DemoScheduleCompletionSeeder"
```

The completion seeder replaces only a recognized starter timetable (the
original nine demo rows or the previous 120-row demo timetable), adds any
missing demo faculty, and refuses to change unknown schedules.

For a disposable development database only, reset everything and seed it in
one command:

```bat
php artisan migrate:fresh --seed
```

Do not use `migrate:fresh` on a database containing data you need to keep.
Running `DemoDataSeeder` again removes existing faculty and student demo data
before recreating it.

## Seeded accounts

All seeded accounts use the email address as the login username.

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@siatrack.edu.ph` | `AdminPass2026!` |
| Management | `management@siatrack.edu.ph` | `siamanagement@123` |
| Faculty (Juan Dela Cruz) | `juan.delacruz@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Maria Santos) | `maria.santos@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Ramon Magsaysay) | `ramon.magsaysay@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Liza Reyes) | `liza.reyes@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Carlo Mendoza) | `carlo.mendoza@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Ana Villanueva) | `ana.villanueva@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Roberto Garcia) | `roberto.garcia@siatrack.edu.ph` | `siafaculty@123` |
| Faculty (Teresa Flores) | `teresa.flores@siatrack.edu.ph` | `siafaculty@123` |

The 30 seeded student accounts all use the password `onesia@123`. Their
emails follow `first-name.last-name@siatrack.edu.ph` (spaces in surnames are
removed):

```text

sophia.bautista@siatrack.edu.ph
gabriel.fernandez@siatrack.edu.ph
angela.aquino@siatrack.edu.ph
ethan.reyes@siatrack.edu.ph
chloe.gonzales@siatrack.edu.ph
joshua.torres@siatrack.edu.ph
samantha.flores@siatrack.edu.ph
liam.mercado@siatrack.edu.ph
hannah.ocampo@siatrack.edu.ph
nathan.delossantos@siatrack.edu.ph
alexander.perez@siatrack.edu.ph
beatrice.santiago@siatrack.edu.ph
daniel.ramos@siatrack.edu.ph
ella.mendoza@siatrack.edu.ph
lucas.castillo@siatrack.edu.ph
mia.villanueva@siatrack.edu.ph
noah.gutierrez@siatrack.edu.ph
olivia.rivera@siatrack.edu.ph
patrick.delacruz@siatrack.edu.ph
rachel.sanjose@siatrack.edu.ph
adrian.cruz@siatrack.edu.ph
alyssa.reyes@siatrack.edu.ph
benjamin.santos@siatrack.edu.ph
danica.bautista@siatrack.edu.ph
elijah.garcia@siatrack.edu.ph
fiona.marquez@siatrack.edu.ph
jacob.evangelista@siatrack.edu.ph
kaitlyn.beltran@siatrack.edu.ph
marcus.ventura@siatrack.edu.ph
nicole.pineda@siatrack.edu.ph
```

These are development/demo credentials. Change or remove them before using
the application outside a local development environment.

## Run the application

To run only the Laravel application:

```bat
php artisan serve
```

Then open <http://127.0.0.1:8000>.

For automatic post-subject absence notifications, keep Laravel's scheduler
running in a second terminal:

```bat
php artisan schedule:work
```

The scheduler checks subject schedules every minute and runs the advisory check
every weekday. In **System Configuration**, administrators can set the advisory
grace period after 8:30 AM (default: 0 minutes) and the subject absence grace
period after each subject's start time (default: 30 minutes). The checks apply
the configured values and only send alerts to approved Telegram parent links
with notifications enabled. A completely empty subject attendance scan is
treated as incomplete data, so subject alerts are skipped until at least one
valid attendance scan exists for that class. Successful notifications are
deduplicated by date and attendance context; failed deliveries are recorded
and retried by the next scheduled check.

System Configuration also stores the school name, official school ID (not a
database record ID), and school head name.

## Telegram attendance and absence notifications

The application can receive parent link requests through a Telegram HTTPS
webhook and send attendance updates to school-approved parent accounts.
Outbound notifications use `laravel-notification-channels/telegram`; the
webhook and bot-management operations use Telegram's Bot API. No bot token is
stored in the database. Configure these values in the server's protected `.env`:

```dotenv
TELEGRAM_BOT_ENABLED=false
TELEGRAM_BOT_TOKEN=your-private-bot-token
TELEGRAM_WEBHOOK_SECRET=a-random-secret-using-letters-numbers-underscores-or-hyphens
```

The bot is disabled by default. With `TELEGRAM_BOT_ENABLED=false`, the webhook
acknowledges and ignores incoming commands (including `/start`) and the
application does not send Telegram notifications. Set it to `true` only when
you intend to reactivate the bot.

### Local Telegram webhook testing on Windows

ngrok offers a free plan, subject to its current usage and feature limits; see
<https://ngrok.com/pricing>.

#### 1. Install ngrok (On the current or another PC)

**Option A: Using Windows Package Manager (Quickest):**

Open Command Prompt or PowerShell and run:

```bat
winget install ngrok.ngrok
```

Restart your terminal window after installation to refresh the `PATH`.

**Option B: Manual ZIP Download:**

1. Download the Windows installer/binary from [ngrok.com/download](https://ngrok.com/download).
2. Extract the downloaded archive to retrieve `ngrok.exe`.
3. Move `ngrok.exe` to a folder included in your system `PATH` (such as `C:\Windows\System32` or a dedicated folder like `C:\ngrok` added to your system environment variables).

#### 2. Configure the ngrok authtoken

1. Sign up or log in to the [ngrok Dashboard](https://dashboard.ngrok.com/).
2. Copy your authtoken from [Your Authtoken](https://dashboard.ngrok.com/get-started/your-authtoken).
3. Open your terminal (Command Prompt or PowerShell) and run:

```bat
ngrok config add-authtoken YOUR_NGROK_AUTHTOKEN
```

Replace `YOUR_NGROK_AUTHTOKEN` with the token copied from your dashboard.

4. Verify your ngrok configuration:

```bat
ngrok config check
```

*(Optional)* You can also set it via environment variable in your terminal session:

```bat
set NGROK_AUTHTOKEN=YOUR_NGROK_AUTHTOKEN
```

*(Optional - Docker)* If running ngrok inside a Docker container instead of the native Windows CLI:

```bat
docker run --net=host -it -e NGROK_AUTHTOKEN=YOUR_NGROK_AUTHTOKEN ngrok/ngrok http 8000
```

> **Note:** Never commit your personal ngrok authtoken into the repository.

#### 3. Start the local server and Telegram tunnel (Standard Setup - Same PC)

1. Make sure `.env` has Telegram enabled and configured:
   ```dotenv
   TELEGRAM_BOT_ENABLED=true
   TELEGRAM_BOT_TOKEN=your-private-bot-token
   TELEGRAM_WEBHOOK_SECRET=your-webhook-secret
   ```
2. Start the Laravel server first by running:
   ```bat
   START_SERVER.bat
   ```
   *(or run `php artisan serve` in a terminal)*
3. In another terminal, run the Telegram launcher:
   ```bat
   START_TELEGRAM_LOCAL.bat
   ```

`START_TELEGRAM_LOCAL.bat` will:
- Check that PHP and ngrok are installed in PATH.
- Check and validate the configured ngrok authtoken.
- Verify Laravel is running on port 8000.
- Start the ngrok tunnel on port 8000.
- Retrieve the HTTPS tunnel URL and automatically register the webhook with Telegram (`php artisan telegram:webhook <NGROK_URL>/api/telegram/webhook`).

Keep both the Laravel server and ngrok console windows open while testing. If you restart ngrok and get a new URL, re-run `START_TELEGRAM_LOCAL.bat` to update the webhook.

#### 4. Running ngrok on a Separate PC (LAN / Wi-Fi)

If you are running ngrok on a secondary machine (PC B) while Laravel runs on the primary machine (PC A):

1. **Ensure both PCs are on the same local Wi-Fi / LAN network.**
2. **Find the local IPv4 address of PC A (Laravel PC):**
   ```bat
   ipconfig
   ```
   *(Look for `IPv4 Address`, e.g., `192.168.1.45`)*.
3. **Start Laravel on PC A listening to the local network:**
   ```bat
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   *(Ensure Windows Firewall allows inbound TCP connections on port 8000).*
4. **On PC B (ngrok PC), point ngrok to PC A's local IP:**
   ```bat
   ngrok http http://<PC_A_IP_ADDRESS>:8000
   ```
   *(Example: `ngrok http http://192.168.1.45:8000`)*.
5. **Register the Telegram Webhook on PC A using the ngrok HTTPS URL:**
   ```bat
   php artisan telegram:webhook https://<your-ngrok-subdomain>.ngrok-free.app/api/telegram/webhook
   ```

Run migrations after deploying:

```bat
php artisan migrate
```

Then register the webhook from the project folder, replacing the public HTTPS
hostname. The command reads the token and secret from Laravel configuration:

```bat
php artisan telegram:webhook https://your-host.example/api/telegram/webhook
```

Telegram webhooks and `getUpdates` long polling cannot be active together. This
application uses the webhook only. BotFather commands should include `/start`,
`/link`, `/my_students`, `/notifications`, `/unlink`, and `/help`.

Administrators can review links, view and export student codes, and inspect
delivery history from **Telegram Settings** in the admin sidebar. Regenerating
a student code from User Management redirects to Telegram Settings and displays
the new code there. **Absence Notifications** lets
administrators and teachers send absence alerts or custom messages, but only to
approved, enabled Telegram-linked parent/guardian accounts. Teacher recipients
are restricted to their assigned students. Recipients are selected from the
application; arbitrary phone numbers are not accepted. On the admin or teacher
`/message` page, each role can save its own reusable custom-message template.
Placeholders such as `{student_name}`, `{parent_name}`, `{grade_level}`,
`{section}`, `{date}`, `{school_name}`, and `{sender_name}` are filled for each
student separately when the message is sent.

Each student receives a school-issued Telegram linking code when their account
is created. Existing student accounts receive codes when migrations are run.
Administrators can find, regenerate, and export codes on the **Telegram
Settings** page. A guardian links a student by choosing **Link Student** in
the bot and entering the student's 12-digit LRN followed by the school-issued
code. Matching details link immediately without
an administrator approval step; one code can be used by more than one guardian.
Regenerating a code invalidates the previous code. Codes are stored in
readable form for this development-stage workflow and the CSV contains these
codes, so distribute the export only through school-approved channels.
Guardians can link multiple students, change each student's alert preference,
and unlink a student who is currently linked. The bot only shows **Unlink a
Child** when at least one student is linked; students who have never been linked
are shown as **Not linked**.

Set and test the webhook on a public HTTPS deployment before expecting
notifications. Bot API failures are recorded without interrupting attendance
recording. To activate a deployment, set `TELEGRAM_BOT_ENABLED=true` before
registering the webhook; after changing environment configuration, rebuild the
Laravel config cache and restart long-running application/queue processes.

For the NFC workflow, keep the Laravel server and scheduler running, then
start the bridge in another terminal:

```bat
python -u nfc_bridge.py
```

The bridge posts card taps to `http://127.0.0.1:8000/api/nfc/tap`. On Windows,
the `START_SERVER.bat` helper starts the Laravel server, scheduler, and NFC
bridge, then opens the application:

```bat
START_SERVER.bat
```

Run the first-time setup and database commands before using
`START_SERVER.bat`. The reader must be connected and its Windows smart-card
service/driver must be available for NFC taps to work.

## Useful commands

```bat
php artisan route:list
php artisan optimize:clear
php artisan test
npm run dev
```

Use `npm run dev` instead of `npm run build` during frontend development.
