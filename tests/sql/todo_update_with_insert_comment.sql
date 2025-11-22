-- INSERT comment that should not trigger INSERT detection
UPDATE todo SET title = :title WHERE id = :id;
