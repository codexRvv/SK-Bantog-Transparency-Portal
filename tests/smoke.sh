#!/usr/bin/env bash
set -euo pipefail

# This test only runs against CI's disposable MySQL database.
cp config/local.example.php config/local.php
sed -i "s/'password' => ''/'password' => 'testpass'/" config/local.php
MYSQL_PWD=testpass mysql -h 127.0.0.1 -u root < database/schema.sql
printf 'Test Admin\nLongTemporaryPassword123!\n' | php bin/create-admin.php admin@example.com

php -S 127.0.0.1:8080 -t public > /tmp/sk-bantog-php.log 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT
sleep 2

base=http://127.0.0.1:8080
current_year=$(date +%Y)
cookie_file=/tmp/sk-bantog-cookies.txt

curl -fsS "$base/" > /tmp/home.html
grep -q 'Open records' /tmp/home.html
grep -q 'Awaiting publication' /tmp/home.html
curl -fsS "$base/index.php?page=budget" > /tmp/budget-before.html
grep -q 'Annual SK Budget' /tmp/budget-before.html

curl -fsS -c "$cookie_file" "$base/admin/index.php?view=login" > /tmp/login.html
csrf=$(grep -o 'name="csrf" value="[a-f0-9]*"' /tmp/login.html | head -1 | cut -d '"' -f 4)
test -n "$csrf"

curl -fsS -b "$cookie_file" -c "$cookie_file" -D /tmp/login-headers.txt -o /tmp/login-result.txt \
  --data-urlencode "csrf=$csrf" \
  --data-urlencode 'action=login' \
  --data-urlencode 'email=admin@example.com' \
  --data-urlencode 'password=LongTemporaryPassword123!' \
  "$base/admin/index.php?view=login"
grep -q '303 See Other' /tmp/login-headers.txt
curl -fsS -b "$cookie_file" "$base/admin/index.php?view=overview" > /tmp/overview.html
grep -q 'Welcome, Test Admin' /tmp/overview.html

printf '%%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n' > /tmp/ci-document.pdf
curl -fsS -b "$cookie_file" -c "$cookie_file" -D /tmp/upload-headers.txt -o /tmp/upload-result.txt \
  -F "csrf=$csrf" -F "action=upload" -F "title=CI Published Document" \
  -F "description=Automated smoke test" -F "category_id=1" \
  -F "fiscal_year=$current_year" -F 'pdf=@/tmp/ci-document.pdf;type=application/pdf' \
  "$base/admin/index.php?view=upload"
grep -q '303 See Other' /tmp/upload-headers.txt

doc_id=$(MYSQL_PWD=testpass mysql -h 127.0.0.1 -u root sk_bantog_portal -N -B -e "SELECT id FROM documents WHERE title='CI Published Document' LIMIT 1;")
test -n "$doc_id"
curl -fsS "$base/index.php?page=documents&year=all" > /tmp/drafts-hidden.html
if grep -q 'CI Published Document' /tmp/drafts-hidden.html; then
  echo 'Draft incorrectly visible on public website'
  exit 1
fi

curl -fsS -b "$cookie_file" -c "$cookie_file" -D /tmp/publish-headers.txt -o /tmp/publish-result.txt \
  --data-urlencode "csrf=$csrf" --data-urlencode 'action=document_status' \
  --data-urlencode "id=$doc_id" --data-urlencode 'status=published' \
  "$base/admin/index.php?view=documents"
grep -q '303 See Other' /tmp/publish-headers.txt
curl -fsS "$base/index.php?page=documents&year=all" > /tmp/public-docs.html
grep -q 'CI Published Document' /tmp/public-docs.html
curl -fsS "$base/index.php?page=download&id=$doc_id" -o /tmp/downloaded.pdf
head -c 5 /tmp/downloaded.pdf | grep -q '%PDF-'

curl -fsS -b "$cookie_file" -D /tmp/save-budget-headers.txt -o /tmp/save-budget-result.txt \
  --data-urlencode "csrf=$csrf" --data-urlencode 'action=save_budget' \
  --data-urlencode "fiscal_year=$current_year" --data-urlencode 'approved_amount=100000' \
  --data-urlencode 'remarks=CI approved budget' \
  "$base/admin/index.php?view=budget"
grep -q '303 See Other' /tmp/save-budget-headers.txt

curl -fsS -b "$cookie_file" -D /tmp/save-expense-headers.txt -o /tmp/save-expense-result.txt \
  --data-urlencode "csrf=$csrf" --data-urlencode 'action=add_expense' \
  --data-urlencode "fiscal_year=$current_year" --data-urlencode 'transaction_date=2026-01-10' \
  --data-urlencode 'title=CI Expenditure' --data-urlencode 'amount=1250.50' \
  --data-urlencode 'description=CI financial entry' --data-urlencode 'publish=1' \
  "$base/admin/index.php?view=budget"
grep -q '303 See Other' /tmp/save-expense-headers.txt
curl -fsS "$base/index.php?page=budget&year=$current_year" > /tmp/budget-after.html
grep -q 'CI Expenditure' /tmp/budget-after.html
grep -q '100,000.00' /tmp/budget-after.html

curl -fsS -b "$cookie_file" -D /tmp/project-headers.txt -o /tmp/project-result.txt \
  --data-urlencode "csrf=$csrf" --data-urlencode 'action=add_project' \
  --data-urlencode 'title=CI Youth Project' \
  --data-urlencode 'description=CI program details' \
  --data-urlencode "fiscal_year=$current_year" --data-urlencode 'status=ongoing' \
  --data-urlencode 'publish=1' \
  "$base/admin/index.php?view=projects"
grep -q '303 See Other' /tmp/project-headers.txt
curl -fsS "$base/index.php?page=projects" > /tmp/public-projects.html
grep -q 'CI Youth Project' /tmp/public-projects.html

echo 'Public pages, login, private PDF upload, publishing, download, budget, expenses and projects: PASS'
