#!/usr/bin/env bash
#
# Smoke test — every page must answer with the expected HTTP status AND carry
# no PHP error text in its body. Run against any environment:
#
#   BASE=http://127.0.0.1:8080 ADMIN_USER=reception ADMIN_PASS=... tests/smoke.sh
#
# Without ADMIN_PASS the signed-in admin checks are skipped, so the script is
# also safe to point at production for a public-pages check.

set -u

BASE="${BASE:-http://127.0.0.1:8080}"
ADMIN_USER="${ADMIN_USER:-}"
ADMIN_PASS="${ADMIN_PASS:-}"

JAR="$(mktemp)"
BODY="$(mktemp)"
trap 'rm -f "$JAR" "$BODY"' EXIT
FAIL=0

check() {
  local url="$1" auth="${2:-}" expect="${3:-200}" needle="${4:-}"
  local code errs
  code=$(curl -s ${auth:+-b "$JAR"} -o "$BODY" -w "%{http_code}" "$BASE/$url")
  errs=$(grep -ciE "fatal error|uncaught|<b>warning</b>|<b>notice</b>|<b>deprecated</b>|call to undefined" "$BODY" || true)

  if [ "$code" != "$expect" ]; then
    printf '  FAIL %-42s http=%s (expected %s)\n' "$url" "$code" "$expect"; FAIL=1
  elif [ "$errs" != "0" ]; then
    printf '  FAIL %-42s %s PHP error(s) in body\n' "$url" "$errs"
    grep -oiE "(fatal error|uncaught|call to undefined)[^<]{0,110}" "$BODY" | head -2 | sed 's/^/         /'
    FAIL=1
  elif [ -n "$needle" ] && ! grep -q "$needle" "$BODY"; then
    printf '  FAIL %-42s missing "%s"\n' "$url" "$needle"; FAIL=1
  else
    printf '   ok  %-42s http=%s\n' "$url" "$code"
  fi
}

TOMORROW=$(date -d "+1 day" +%Y-%m-%d 2>/dev/null || date -v+1d +%Y-%m-%d)

echo "PUBLIC PAGES ($BASE)"
for p in index.php about.php doctors.php services.php diabetic-centre.php \
         maternity.php emergency.php facilities.php tariff.php gallery.php \
         contact.php book.php booking.php queue.php cancel.php display.php \
         credits.php sitemap.php robots.txt; do
  check "$p"
done
check "404.php" "" 404
check "doctor.php?slug=dr-gundavarapu-venkatesh" ""
check "doctor.php?slug=nope" "" 404
check "booking.php?ref=NOSUCHREF" ""
check "book.php?doctor=1" ""
check "queue.php?ref=NOSUCHREF" ""
check "availability.php?doctor_id=1&date=$TOMORROW" "" 200 '"sessions"'
check "queue-status.php" "" 200 '"doctors"'
check "queue-status.php?ref=SNQQQQQQ" "" 200 '"booking":null'

if [ -n "$ADMIN_PASS" ]; then
  echo "ADMIN LOGIN"
  TOKEN=$(curl -s -c "$JAR" "$BASE/admin/login.php" \
          | grep -o 'name="csrf_token" value="[^"]*"' | sed 's/.*value="//;s/"//')
  curl -s -b "$JAR" -c "$JAR" -o /dev/null \
       --data-urlencode "csrf_token=$TOKEN" \
       --data-urlencode "username=$ADMIN_USER" \
       --data-urlencode "password=$ADMIN_PASS" \
       "$BASE/admin/login.php"

  echo "ADMIN PAGES (signed in)"
  for p in index.php bookings.php new.php schedule.php leave.php \
           analytics.php register.php password.php; do
    check "admin/$p" auth
  done
  check "admin/index.php?date=$TOMORROW" auth
  check "admin/analytics.php?days=7" auth
  check "admin/register.php?export=csv" auth 200 "Date,Session,Token"
  check "admin/bookings.php?status=booked&doctor_id=1" auth
  # Admin-only screens still answer for the admin role; a reception account
  # is bounced, so accept either outcome and only reject PHP errors.
  for p in users.php doctors.php images.php settings.php hospital.php tariff.php services.php pages.php backup.php; do
    code=$(curl -s -b "$JAR" -o "$BODY" -w "%{http_code}" "$BASE/admin/$p")
    case "$code" in
      200|302) printf '   ok  %-42s http=%s\n' "admin/$p" "$code" ;;
      *) printf '  FAIL %-42s http=%s\n' "admin/$p" "$code"; FAIL=1 ;;
    esac
  done
else
  echo "ADMIN PAGES skipped (set ADMIN_USER / ADMIN_PASS to include them)"
fi

echo ""
if [ $FAIL -eq 0 ]; then echo "ALL CHECKS PASSED"; else echo "FAILURES PRESENT"; fi
exit $FAIL
