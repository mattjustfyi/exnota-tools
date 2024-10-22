
-- Clear data
BEGIN;
DELETE FROM spotify_play_histories;
delete from spotify_albums;
delete from spotify_artists;
COMMIT;