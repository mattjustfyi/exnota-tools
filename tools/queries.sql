-- Clear local data
begin;
DELETE FROM workspaces;
DELETE FROM users;
commit;

select * from users;