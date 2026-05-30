SELECT
    id,
    article_id,
    body,
    posted_at
FROM comment
WHERE article_id = :articleId
ORDER BY id;
