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
        EXTRACT(HOUR FROM (search_ended_at - search_started_at))::integer, 'h ',
        EXTRACT(MINUTE FROM (search_ended_at - search_started_at))::integer, 'm ',
        EXTRACT(SECOND FROM (search_ended_at - search_started_at))::integer, 's'
    ) AS search_duration,
    CONCAT(
        EXTRACT(HOUR FROM (delete_ended_at - delete_started_at))::integer, 'h ',
        EXTRACT(MINUTE FROM (delete_ended_at - delete_started_at))::integer, 'm ',
        EXTRACT(SECOND FROM (delete_ended_at - delete_started_at))::integer, 's'
    ) AS delete_duration
FROM 
    tool_duplicate_requests
WHERE 
	search_started_at is not null
	and search_ended_at is not null
    and delete_started_at IS NOT NULL 
    AND delete_ended_at IS NOT null
    order by created_at asc;
    