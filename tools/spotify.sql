
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
BEGIN;
DELETE FROM spotify_play_histories;
delete from spotify_tracks;
delete from spotify_albums;
delete from spotify_artists;
COMMIT;