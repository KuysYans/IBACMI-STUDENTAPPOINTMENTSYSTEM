# IBA College of Mindanao — Student Appointment System

PHP + MySQL (XAMPP-ready). Covers the four system features:
**Online Appointment · Schedule Management · Notifications · Appointment History**

## 1. Setup (XAMPP)

1. Copy the whole `iba-system` folder into `htdocs/` (e.g. `C:\xampp\htdocs\iba-system`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** → **SQL** tab → paste the contents of `schema.sql` → **Go**.
   (This creates the `iba_appointment_system` database, all tables, and demo accounts.)
4. Visit `http://localhost/iba-system/index.php`.

If your MySQL root user has a password, edit `config/db.php` and set `$DB_PASS`.

## 2. Demo accounts

| Role      | Email                  | Password      |
|-----------|-------------------------|----------------|
| Student   | student@iba.edu.ph      | student123     |
| Registrar | registrar@iba.edu.ph    | registrar123   |
| Cashier   | cashier@iba.edu.ph      | cashier123     |

Log in from the **Portal** dropdown or the ticket card on the landing page — pick a role, then sign in.

## 3. Folder structure

```
iba-system/
├── schema.sql              ← import this first
├── config/db.php           ← DB connection (edit if needed)
├── includes/
│   ├── auth.php            ← session guard, login/logout
│   ├── notify.php          ← notification helpers
│   ├── sidebar_top.php     ← shared dashboard layout (open)
│   └── sidebar_bottom.php  ← shared dashboard layout (close)
├── assets/theme.css        ← shared styling for dashboards
├── index.php                ← landing page + login modal
├── logout.php
├── student/
│   ├── dashboard.php        ← Online Appointment overview + upcoming list
│   ├── book.php              ← Online Appointment — book a new slot
│   ├── history.php           ← Appointment History
│   └── notifications.php     ← Notifications
└── staff/                    ← shared by Registrar & Cashier accounts
    ├── dashboard.php         ← today's queue, confirm/done/no-show/cancel
    ├── schedule.php          ← Schedule Management — open/remove time slots
    └── history.php           ← Appointment History (office-wide log)
```

## 4. How each feature works

- **Online Appointment** — students go to *Book Appointment*, pick an office
  (Registrar/Cashier), a service, then an open time slot. Booking is done
  inside a DB transaction with a row lock, so two students can't grab the
  last seat in a slot at the same time. A queue code like `IBA-0001` is
  generated automatically.
- **Schedule Management** — Registrar and Cashier accounts each see their
  own *Schedule Management* page to open new date/time slots with a seat
  capacity, and remove slots that have no bookings yet.
- **Notifications** — every booking, cancellation, confirmation, "done", or
  "no-show" writes a row into `notifications` for the student, shown on
  their *Notifications* page with an unread badge in the sidebar.
- **Appointment History** — students see every appointment they've ever
  made (filterable by status); staff see the same for their whole office.

## 5. Extending it

- Add more services per office by inserting rows into `services`.
- Registrar/Cashier passwords are demo-only — in production, add a
  signup/admin flow instead of hardcoding.
- To add an "admin" role that manages all offices, duplicate the
  `staff/` folder pattern and adjust `require_role()`.
