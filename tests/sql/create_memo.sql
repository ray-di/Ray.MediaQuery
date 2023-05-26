CREATE TABLE IF NOT EXISTS memo
(
    id INTEGER,
    body TEXT,
    todo_id INTEGER,
    FOREIGN KEY(todo_id) REFERENCES todo(id)
);
