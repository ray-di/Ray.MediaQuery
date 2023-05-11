SELECT
  todo.id AS id, 
  todo.title AS title, 
  memo.id AS memo_id, 
  memo.body AS memo_body
FROM todo
LEFT OUTER JOIN memo 
    ON memo.todo_id = todo.id
WHERE todo.id = :id;
