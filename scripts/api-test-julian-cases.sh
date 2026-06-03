#!/bin/sh
BASE="http://127.0.0.1"
COOKIE="/tmp/julian-cookie.txt"
rm -f "$COOKIE"

LOGIN=$(curl -s -c "$COOKIE" -X POST "$BASE/api/v1/App/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"julian.asignador","password":"Julian2026!"}')

echo "=== LOGIN ==="
echo "$LOGIN" | head -c 500
echo ""

CASES=$(curl -s -b "$COOKIE" "$BASE/api/v1/Case?maxSize=20&select=id,name,status")
echo ""
echo "=== CASE LIST ==="
echo "$CASES" | head -c 2000
echo ""

NOTIF=$(curl -s -b "$COOKIE" "$BASE/api/v1/Notification?maxSize=10")
echo ""
echo "=== NOTIFICATIONS ==="
echo "$NOTIF" | head -c 1500
echo ""
