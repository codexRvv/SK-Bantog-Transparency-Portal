# Local installation (Windows XAMPP)

Requirements: PHP 8.2+ with PDO MySQL and fileinfo, MySQL 8+ / compatible MariaDB, Apache or PHP built-in server.

## 1. Clone the repository
Clone into a development folder, for example C:\xampp\htdocs\SK-Bantog-Transparency-Portal using GitHub Desktop.

## 2. Configure MySQL
Start MySQL from XAMPP. Open phpMyAdmin and import database/schema.sql. The script creates the sk_bantog_portal database and its tables.

Open PowerShell **in the repository root** and run:

```powershell
Copy-Item config\local.example.php config\local.php
notepad config\local.php
php bin/check-setup.php
```

The name in the file must be `sk_bantog_portal`. For a standard local XAMPP MySQL installation, host is `127.0.0.1`, port is `3306`, user is `root`, and password may be blank. Use the actual credentials you configured, and **never commit `config/local.php`**.

If you previously saw **"Portal setup or service required"**, the table import can be complete while `config/local.php` is still missing. Run `php bin/check-setup.php` to identify the remaining missing step.

## 3. Create the first administrator
In a terminal at the repository root:

    php bin/create-admin.php skadmin@example.com

Enter a display name and a new long password when prompted. On Windows, the password is visibly entered in this basic setup script; use a private terminal. Do not reuse a password that has been pasted into chats or logs. Rerun `php bin/check-setup.php` after creating the account.

This installer does not create any financial figures or upload example documents.

## 4. Run
Recommended development command from the repository root:

    php -S 127.0.0.1:8080 -t public

Open http://127.0.0.1:8080 and use the **Officials sign in** link. The same application can be served by Apache only when its document root (VirtualHost) points to the repository's public/ directory.

### Security precautions
- **Do not serve the repository root as the public document root.** The config/, app/, database/, bin/ and storage/ directories must remain inaccessible from the web.
- Keep the GitHub repository free of real passwords, private data and uploaded PDF contents.
- Uploaded files are saved in storage/uploads, outside public/; public files are streamed only after a publish action.
- Before production use: enforce HTTPS, backups, secure hosting, rate limits, virus scanning, an approved government branding policy, and an authorization/publishing process with appropriate officials.
- The public repository is **source code only**, not a document hosting service.
- A production deployment needs a dedicated MySQL database and write permissions to storage/uploads.
- GitHub Pages does **not** execute PHP or MySQL. Deploy to a PHP/MySQL host instead.

## Publishing workflow
1. An editor or administrator logs in and uploads a PDF (up to 12 MB). It begins as a draft.
2. An administrator or publisher reviews its contents and selects Publish.
3. Published records are visible to the public and downloadable.
4. Archived records are no longer publicly downloadable; metadata remains in the admin workspace.

The public website shows no financial amounts until a publisher enters the corresponding approved annual budget. The expenditure list displays **only published** transactions.


## 2027 youth planning module (one-time database upgrade)

Existing installations must apply the **non-destructive** migration after pulling the latest code:

1. Back up `sk_bantog_portal` using phpMyAdmin > Export.
2. Open phpMyAdmin, select `sk_bantog_portal`, choose **SQL**.
3. Paste and execute `database/migrations/002_youth_plans.sql` once. It creates `youth_plans` and `youth_plan_items`; it does not erase existing records.
4. Open `http://127.0.0.1:8080/admin/plans.php` after signing in.
5. Create a **CBYDP** (three-year plan) or **ABYIP** (annual plan) for 2027, add activities, then keep as draft until the official signed plan is approved.
6. A publisher or administrator can publish the structured plan. The public can view published entries at `http://127.0.0.1:8080/plans.php`.

This module is an editable planning and disclosure interface, **not an official replacement for the prescribed DILG/DBM/NYC forms**. Upload the approved and signed PDF using Document Management as well. Review the latest required annexes and local LYDO instructions before submitting.

Official reference: DBM-DILG-NYC JMC No. 2025-1 (May 23, 2025), and DILG SK Full Public Disclosure Annexes A–E.
