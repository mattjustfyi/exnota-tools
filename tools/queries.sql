-- ------------------------------
-- Searches
-- ------------------------------

-- ----------
-- Faster select of Tool Duplicate Requests (without large columns)
-- ----------

SELECT id, created_at, updated_at, notion_object_id,
--  duplicates,
  search_started_at, search_ended_at, delete_started_at, delete_ended_at,
--  delete_results, delete_list,
  delete_batch_id,
  search_batch_id,
--  search_results,
  search_failed, delete_cancelled
FROM public.tool_duplicate_requests
order by created_at asc;

-- ----------
-- Durations and counts of duplicate search/delete requests
-- ----------
SELECT 
    id, created_at,
    COALESCE(jsonb_array_length(search_results), 0) AS search_count,
    CASE 
        WHEN search_started_at IS NOT NULL AND search_ended_at IS NOT NULL THEN
            CONCAT(
                EXTRACT(HOUR FROM (search_ended_at - search_started_at))::integer, 'h ',
                EXTRACT(MINUTE FROM (search_ended_at - search_started_at))::integer, 'm ',
                EXTRACT(SECOND FROM (search_ended_at - search_started_at))::integer, 's'
            )
        ELSE NULL
    END AS search_duration,
    COALESCE(jsonb_array_length(delete_results), 0) AS delete_count,
    CASE 
        WHEN delete_started_at IS NOT NULL AND delete_ended_at IS NOT NULL THEN
            CONCAT(
                EXTRACT(HOUR FROM (delete_ended_at - delete_started_at))::integer, 'h ',
                EXTRACT(MINUTE FROM (delete_ended_at - delete_started_at))::integer, 'm ',
                EXTRACT(SECOND FROM (delete_ended_at - delete_started_at))::integer, 's'
            )
        ELSE NULL
    END AS delete_duration
FROM 
    tool_duplicate_requests
WHERE 
    search_started_at IS NOT NULL
ORDER BY 
    created_at ASC;


-- ------------------------------
-- 🚨 DELETIONS / CLEANUPS
-- ------------------------------

-- ----------
-- Clear consent
-- ----------
update users set consent = null where id = <id>;
   
-- ----------
-- Delete a user and related data
-- ----------
select * from users;
-- FIRST, check for jobs
-- 1. Get user ID(s)
SELECT id, email 
FROM users 
WHERE email IN ('email@matt.fyi', 'matt@maki.world');

-- 2. Use user IDs to find jobs (UPDATE INLINE)
SELECT *
FROM jobs
WHERE payload ~ (':\\"App\\\\Models\\\\User\\";s:\d+:\\"id\\";i:' ||
	'40' -- User ID (UPDATE!) 
	|| ';');


-- 3. If no jobs, then delete
BEGIN;
CREATE TEMP TABLE temp_user_ids on commit drop AS
SELECT id, email 
FROM users 
WHERE email IN ('email@matt.fyi', 'matt@maki.world');
select * from temp_user_ids;

CREATE TEMP TABLE temp_user_workspaces on commit drop AS
SELECT id, workspace_name, user_id 
FROM workspaces 
WHERE user_id IN (SELECT id FROM temp_user_ids);
select * from temp_user_workspaces;

CREATE TEMP TABLE temp_user_notion_objects on commit drop AS
SELECT id, workspace_id, name 
FROM notion_objects 
WHERE workspace_id IN (SELECT id FROM temp_user_workspaces);
select * from temp_user_notion_objects;

CREATE TEMP TABLE temp_user_tool_duplicate_requests on commit drop AS
SELECT id, notion_object_id 
FROM tool_duplicate_requests 
WHERE notion_object_id IN (SELECT id FROM temp_user_notion_objects);
select * from temp_user_tool_duplicate_requests;

-- Perform deletions in reverse order
DELETE FROM tool_duplicate_requests tdr
WHERE tdr.id IN (SELECT id FROM temp_user_tool_duplicate_requests);

DELETE FROM notion_objects no
WHERE no.id IN (SELECT id FROM temp_user_notion_objects);

DELETE FROM workspaces w
WHERE w.id IN (SELECT id FROM temp_user_workspaces);

DELETE FROM users u
WHERE u.id IN (SELECT id FROM temp_user_ids);

ROLLBACK;

-- ----------
-- 🟨 Delete ALL users and related data 🟨
-- ----------
begin;
delete from jobs;
delete from tool_duplicate_requests;
delete from notion_objects;
DELETE FROM workspaces;
DELETE FROM users;
rollback;