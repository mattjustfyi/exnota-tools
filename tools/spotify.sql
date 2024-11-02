
select * from spotify_dashboards;

-- Get current track info
SELECT 
    play_status, 
    (current_track->'name')::text || ' - ' || (current_track->'album_name') AS track_album,
    spotify_track_id_in_notion 
FROM 
    spotify_dashboards
WHERE 
    play_status IS NOT NULL;

-- Clear data
select * from spotify_play_histories sph where spotify_dashboard_id = 3;
   
   
BEGIN;
update spotify_dashboards set
  fetch_history_after = null,
  current_track = null,
  play_status = null
 where id = 3;
DELETE FROM spotify_play_histories where spotify_dashboard_id = 3;
delete from spotify_tracks where spotify_dashboard_id = 3;
delete from spotify_albums where spotify_dashboard_id = 3;
delete from spotify_artists where spotify_dashboard_id = 3;
COMMIT;