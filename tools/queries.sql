-- Tool Duplicate Requests
--
-- Without huge fields (to save time)
SELECT id, created_at, updated_at, notion_object_id,
--  duplicates,
  search_started_at, search_ended_at, delete_started_at, delete_ended_at,
--  delete_results, delete_list,
  delete_batch_id,
--  delete_retry_list,
  search_batch_id,
--  search_results,
  search_failed, delete_cancelled
FROM public.tool_duplicate_requests
order by created_at asc;


-- Search/Delete counts and durations
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
   
   
   
-- Clear local data
begin;
delete from jobs;
delete from tool_duplicate_requests;
delete from notion_objects;
DELETE FROM workspaces;
DELETE FROM users;
commit;
    