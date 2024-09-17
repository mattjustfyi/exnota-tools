-- Clear local data
begin;
delete from jobs;
delete from tool_duplicate_requests;
delete from notion_objects;
DELETE FROM workspaces;
DELETE FROM users;
commit;

select * from users;