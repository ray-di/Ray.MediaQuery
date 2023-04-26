SELECT 
    user.user_id,
    permission.permission_name
FROM
    user
JOIN
    user_permission
ON
    user.user_id = user_permission.user_id
JOIN
    permission
ON
    user_permission.permission_id = permission.permission_id
WHERE
    user.user_id = :userId
;
