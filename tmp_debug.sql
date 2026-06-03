SELECT c.name, c.status, u.user_name AS assigned_to, c.created_at
FROM "case" c
LEFT JOIN "user" u ON u.id = c.assigned_user_id
WHERE c.deleted = false
ORDER BY c.created_at DESC;

SELECT n.type, n.read, n.created_at, u.user_name, n.data
FROM notification n
JOIN "user" u ON u.id = n.user_id
ORDER BY n.created_at DESC
LIMIT 10;
