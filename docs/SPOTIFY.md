# Spotify

## User setup

1. Duplicate dashboard/template in Notion.
2. Delete subpages
3. Grant access to Exnota.
4. Get user to grant access to Spotify and provide `code`. See URL in `spotify.http`.
5. Use `code` to request access token. See request in `spotify.http`.
   - Response should include an access token and refresh token.
6. Create a new row in `spotify_dashboards`
   - TODO: How to get user to create a workspace?
   - With these values
     - workspace_id (TODO:)
     - access_token (from response)
     - refresh_token (from response)
     - page/database IDs
     - active = true

## Refresh user

1. Stop scheduler
1. Delete Notion pages
3. Delete database entries for dashboard for: play history, tracks, albums, artists.
