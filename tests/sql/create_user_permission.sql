CREATE TABLE IF NOT EXISTS user_permission
(
    user_id TEXT,
    permission_id TEXT,
    PRIMARY KEY(user_id, permission_id)
);
