SELECT
  todo.id AS id, 
  todo.title AS title,
  GROUP_CONCAT(memo.id),
  GROUP_CONCAT(memo.body)
FROM todo
LEFT OUTER JOIN memo
    ON memo.todo_id = todo.id
GROUP BY todo.id;
