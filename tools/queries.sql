-- Clear local data
begin;
delete from jobs;
delete from tool_duplicate_requests;
delete from notion_objects;
DELETE FROM workspaces;
DELETE FROM users;
commit;

select * from users;

-- Deletion durations
SELECT 
    id, created_at,
    CONCAT(
        EXTRACT(HOUR FROM (delete_ended_at - delete_started_at))::integer, 'h ',
        EXTRACT(MINUTE FROM (delete_ended_at - delete_started_at))::integer, 'm ',
        EXTRACT(SECOND FROM (delete_ended_at - delete_started_at))::integer, 's'
    ) AS duration
FROM 
    tool_duplicate_requests
WHERE 
    delete_started_at IS NOT NULL 
    AND delete_ended_at IS NOT NULL;