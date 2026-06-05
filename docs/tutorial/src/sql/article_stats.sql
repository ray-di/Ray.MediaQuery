SELECT
    a.id,
    a.title,
    a.body,
    (
        SELECT COUNT(*)
        FROM comment AS c
        WHERE c.article_id = a.id
    ) AS comment_count,
    a.status
FROM article AS a
WHERE a.id = :id;
