# SK Barangay Bantog | Budget Transparency Portal

A public information portal for the Sangguniang Kabataan of Barangay Bantog, Tarlac City, with a private publishing workspace for authorized SK officials.

## Fiscal year and launch

The portal is being prepared in 2026 for operational use beginning in **2027**. The public dashboard and official entry forms default to fiscal year 2027. Earlier years can still be viewed when explicitly selected. No financial records are generated or published automatically.

## Purpose

Make approved SK budget records, financial reports, public project updates, and supporting documents easy for residents to find and download. **No financial data is pre-filled or represented as official**; figures are visible only after authorized officials enter and publish records.

## Stack

- PHP 8.2+ and MySQL 8 / MariaDB (XAMPP)
- Vanilla HTML, CSS and JavaScript (no framework or Composer required)
- Server-rendered public portal and authenticated administration
- Files served through a controlled PHP download endpoint, not a public upload directory

## Setup

Full setup and security guidance are in [INSTALL.md](INSTALL.md). This repository is intended to be configured with **`public/` as the web document root**. Never commit `config/local.php` or upload data.

## Development

The source is maintained in GitHub so updates can be fetched via GitHub Desktop.

The website is not an official government service until authorized by Barangay Bantog/SK and reviewed for operational/security compliance.
