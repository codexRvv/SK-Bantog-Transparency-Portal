# Local installation (Windows XAMPP)

Requirements: PHP 8.2+ with PDO MySQL and fileinfo, MySQL 8+ / compatible MariaDB, Apache or PHP built-in server.

## 1. Clone the repository
Clone into a development folder, for example C:\xampp\htdocs\SK-Bantog-Transparency-Portal using GitHub Desktop.

## 2. Configure MySQL
Start MySQL from XAMPP. Open phpMyAdmin and import database/schema.sql. The script creates the sk_bantog_portal database and its tables.

Copy config/local.example.php to config/local.php, then edit your **local** MySQL host/username/password. Do not commit local.php.

## 3. Create the first administrator
In a terminal at the repository root:

    php bin/create-admin.php skadmin@example.com

Enter a display name and a long password when prompted. On Windows, the password is visibly entered in this basic setup script; use a private terminal. This installer does not create any financial figures or upload example documents.

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
