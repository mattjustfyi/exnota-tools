-- Clear local data
begin;
delete from notion_objects;
DELETE FROM workspaces;
DELETE FROM users;
commit;

select * from users;