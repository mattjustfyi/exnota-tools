# Exnota Tools

**Stack**: Laravel/PHP, PostgreSQL/Redis, Vue/Tailwind/Vite, Docker/VPS.

## Apps

### Delete Duplicates

Bulk delete duplicates entries in your Notion databases. Great for CSV imports.

<img src="/docs/delete-duplicates.gif" width="500">

[Full walkthrough](https://youtu.be/utRpbNlllu4)

### Spotify Sync

Sync Spotify play status and history to Notion. A widget is used for play status and play history is saved to a collection of databases that you can use to find your top songs, artists, albums. 

<img src="/docs/spotify-to-notion.gif" width="500">

[Full walkthrough](https://youtu.be/ApY_hU4iqKE)

## TOC

- [Exnota Tools](#exnota-tools)
  - [Cheatsheet](#cheatsheet)
  - [Deploy to prod](#deploy-to-prod)
    - [Updating environment variables (.env)](#updating-environment-variables-env)
    - [Steps](#steps)
    - [Troubleshooting](#troubleshooting)
      - [`error: remote unpack failed: unable to create temporary object directory`](#error-remote-unpack-failed-unable-to-create-temporary-object-directory)
      - [Error: At least one invalid signature was encountered, or "no space left on device"](#error-at-least-one-invalid-signature-was-encountered-or-no-space-left-on-device)
  - [Local development](#local-development)
    - [Running](#running)
      - [Automatic](#automatic)
      - [Commands](#commands)
    - [Debug on mobile](#debug-on-mobile)
      - [Setup](#setup)
      - [Use](#use)
      - [Troubleshooting](#troubleshooting-1)
    - [Redis](#redis)
    - [Setup](#setup-1)
    - [Database UI](#database-ui)
      - [Local](#local)
      - [Prod](#prod)
    - [Testing](#testing)
    - [Troubleshooting](#troubleshooting-2)
      - [Pages/endpoints aren't working as expected (including using ngrok)](#pagesendpoints-arent-working-as-expected-including-using-ngrok)
      - [Web: After Notion connect, it redirects back to `/duplicates` instead of `/duplicates/create-account`](#web-after-notion-connect-it-redirects-back-to-duplicates-instead-of-duplicatescreate-account)
      - [Web: Inertia route opens in iframe](#web-inertia-route-opens-in-iframe)
    - [Laravel Model: BadMethodCallException \[...\] Method \[nethodName in model\] does not exist](#laravel-model-badmethodcallexception--method-nethodname-in-model-does-not-exist)
      - [DBeaver: Column has dot and on hover says `read-only no corresponding table column`](#dbeaver-column-has-dot-and-on-hover-says-read-only-no-corresponding-table-column)
      - [Error: `SQLSTATE[08006] [7] could not translate host name "pgsql" to address: nodename nor servname provided`](#error-sqlstate08006-7-could-not-translate-host-name-pgsql-to-address-nodename-nor-servname-provided)
      - [Error: `Page Expired` on public URL](#error-page-expired-on-public-url)
    - [Testing prod build locally](#testing-prod-build-locally)
      - [Broadcasting to public URL](#broadcasting-to-public-url)
      - [Other](#other)
  - [Initial prod setup](#initial-prod-setup)
    - [SSH](#ssh)
    - [Dokku](#dokku)
    - [Troubleshooting initial setup](#troubleshooting-initial-setup)
  - [Old documentation copied from `exnota` repo](#old-documentation-copied-from-exnota-repo)
    - [Troubleshooting assorted old](#troubleshooting-assorted-old)
  - [Maintenance](#maintenance)
    - [Check PHP settings (incl. php.ini)](#check-php-settings-incl-phpini)
    - [Environment variables](#environment-variables)
    - [Checking local logs](#checking-local-logs)
    - [Checking prod logs](#checking-prod-logs)
      - [Dokku](#dokku-1)
      - [Laravel Pail](#laravel-pail)
    - [Restart jobs (prod)](#restart-jobs-prod)
    - [How to execute Dokku commands](#how-to-execute-dokku-commands)
  - [Hetzner SSH](#hetzner-ssh)
    - [Terminal](#terminal)
    - [CLI](#cli)
  - [Archive](#archive)
    - [Manual Hetzner deploy](#manual-hetzner-deploy)

## Cheatsheet

- Local artisan commands, outside Docker:

    ```bash
    ./vendor/bin/sail artisan list
    ```

- Migrate
  - Prod

      ```bash
      # Local machine
      ssh root@195.201.33.178

      # Hetzner
      docker exec -it exnota-tools.web.1 php artisan migrate
      ```

  - Local

    ```bash
    # Outside Docker. Use 'listen' so 
    ./vendor/bin/sail debug queue:listen
    ```

## Deploy to prod

### Updating environment variables (.env)

Follow steps in BitWarden item `Exnota Tools Production .env`

### Steps

1. Confirm on the correct branch locally (i.e. not a feature branch).

    ```bash
    git checkout main; git pull; 
    ```

2. Run tests locally.

    ```bash
    ./sail.sh debug test tests/Feature/AuthorizationTest.php
    ```

3. (FIRST TIME ONLY) deploying from a specific device.
   - Don't run inside dev container!
   - Ensure you have configured [SSH](#ssh)
   - Add Dokku remote to git repo

        ```bash
        # `exnota-tools` is the remote branch name, matching the app name
        git remote add dokku dokku@<domain>:exnota-tools
        ```

4. Check no-one's using the service: <https://app.crisp.chat/website/ce54fcea-7cfd-42ed-af5c-29d2512544d8/visitors/>

5. Deploy app

   TODO: Migrations technically need to be run first to avoid issues. When site
   starts getting traffic, need to find a way to run migrations first. This
   currently isn't possible using the deploy method below because until the git
   push happens, the updated code with migrations isn't on the server. Might
   need to search things like: laravel auto run migrations; dokku pre deploy
   hooks. Also see below under 'Deploy improvement ideas:'.

      ```bash
      # Deploy master branch
      git push dokku main

      # OR: Deploy branch <branch-name>
      git push dokku <branch-name>:main
      ```

6. Run migrations

      ```bash
      ssh -t dokku@195.201.33.178 enter exnota-tools web php artisan migrate

      # OR
      ssh root@195.201.33.178
      docker exec -it exnota-tools.web.1 php artisan migrate
      ```

7. Monitoring (deploy will disconnect connections, so must go after deploy):
   1. (First time) `iterm2-exnota-tools-prod.py` to `/Users/matt/Library/Application Support/iTerm2/Scripts`
   2. Open new iTerm2 window
   3. ⌘ ⇧ O
   4. Select `iterm2-exnota-tools-prod`
   5. Insert password to each window:
      1. `⌘ ⌥ F` to open password manager
      2. `↓` then `⏎`
      3. `⌘ ⌥ ↑↓←→` (next terminal)
      4. Repeat

8. (Probably not needed) Manually restart worker

    Laravel workers need to be restarted to get code changes: <https://laravel.com/docs/11.x/queues#the-queue-work-command>.

    I would have thought `git push` deploy would cause this to happen anyway,
    but I'm not sure. The first time getting jobs working, the worker threw an
    error due to a missing dependency. I re-deployed a fix with `git push` and
    it still threw the same error until I restarted the worker manually.

    TODO: Test if manual restart is necessary (i.e. if the worker runs old code
    even after a deploy with git) and update this message with results.

    ```bash
    ssh -t dokku@195.201.33.178 ps:restart exnota-tools

    # OR
    dokku ps:restart exnota-tools
    ```

9. Check that web/worker services are running with `dokku ps:report exnota-tools`

    ```bash
    ssh -t dokku@195.201.33.178 ps:report exnota-tools

    # OR
    dokku ps:report exnota-tools
    ```

Deploy improvement ideas:

- Create a plugin that has a post-deploy hook that runs migrations and restarts:
  - <https://dokku.com/docs/development/plugin-creation/>
- Don't deploy directly from `git push`
  - Maybe skip deploy when pushing (not sure if possible): <https://dokku.com/docs/deployment/application-deployment/#skipping-deployment>
  - Or manually fetch changes from repo and build image: <https://dokku.com/docs/deployment/methods/git/>
- Then, if successful, create and run a script that does all steps above.

### Troubleshooting

#### `error: remote unpack failed: unable to create temporary object directory`

#### Error: At least one invalid signature was encountered, or "no space left on device"

Former is apparently caused by insufficient space according to this [SO post](https://stackoverflow.com/questions/62473932/at-least-one-invalid-signature-was-encountered) (which has more options for freeing space in addition to below).

Fix:

1. SSH to Hetzner server.
2. `df -h` to confirm `/dev/sda1` is full
3. Run

    ```bash
    docker builder prune
    ```

4. Run `df -h` again to confirm space freed.

## Local development

### Running

#### Automatic

1. Copy `iterm2-exnota-tools-local.py` to `/Users/matt/Library/Application Support/iTerm2/Scripts`
2. Cmd > `Debug: Select and Start Debugging` > `Listen for Sail XDebug`
3. Make sure Docker is running and Herd is **not** running.
4. Start PHP debug listener if you want to avoid warnings about not being able to connect to debugger.
5. Open new iTerm2 window
6. ⌘ ⇧ O
7. Select `iterm2-exnota-tools-local`
8. Go to `http://localhost`

More

- Scheduler: `./sail.sh debug schedule:work -vvv`
  - After cancelling, run `./sail.sh debug schedule:clear-cache` before starting again.
- Broadcaster: `./sail.sh debug reverb:start --debug` (also requires `default` job queue)

#### Commands

```bash
# Sail-managed docker-compose
./vendor/bin/sail up
./vendor/bin/sail down

# Frontend
npm run dev

# Workers (For more details: supervised-worker.conf)
./vendor/bin/sail debug queue:listen -vvv --queue=default --timeout=180
./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=180
./vendor/bin/sail debug queue:listen -vvv --queue=delete-2 --timeout=180
```

### Debug on mobile

#### Setup

1. Vite must be running with `--host 0.0.0.0` (not sure why)
2. Follow steps in [Remote debug Android Devices](https://developer.chrome.com/docs/devtools/remote-debugging).
3. On host, setup port forwarding in [chrome://inspect/#devices](chrome://inspect/#devices)
    - 5173 -> localhost:5173
    - 8000 -> localhost:80

#### Use

1. On host open [chrome://inspect/#devices](chrome://inspect/#devices)
   - This turns on port forwarding
2. Connect phone to USB
3. On phone open [http://localhost:8000](http://localhost:8000)
4. Click `Inspect` on Devices tab on host

#### Troubleshooting

- Vite will connect on 5174 if default is in use. So add 5174 to port forwarding.

### Redis

Use CLI

```bash
# Via Docker
docker exec -it exnota-tools-redis redis-cli

# Via Dokku
dokku config:get exnota-tools REDIS_PASSWORD
dokku redis:enter exnota-tools-redis redis-cli

127.0.0.1:6379> auth <REDIS_PASSWORD>
# The cache is in 'database 1'. See this line in database.php:
#   'database' => env('REDIS_CACHE_DB', '1'),
127.0.0.1:6379> select 1

# Assorted commands
127.0.0.1:6379> keys *

# Set/clear delay
set db_duplicate_delete-delay 10000
del db_duplicate_delete-delay
```

### Setup

- Extension: Laravel Extra Intellisense
  - Set `Laravel Extra Intellisense: PHP Command` to `./vendor/bin/sail php -r "{code}"`
  - Set `Laravel Extra Intellisense: Base Path For Code` to `/var/www/html`
- Debugging
  - XDebug should already be added to the Sail Dockerfile in `vendor/laravel/sail/runtimes/8.3/Dockerfile`
  - Confirm XDebug is running with `./vendor/bin/sail php -v`
  - Add to .env `SAIL_XDEBUG_MODE=develop,debug,coverage` ([link](https://laravel.com/docs/11.x/sail#debugging-with-xdebug))
  - Enable VSCode setting `Debug: Allow Breakpoints Everywhere` ([link](https://stackoverflow.com/a/65459011))
  - I also rebuilt via Sail with `./vendor/bin/sail up --build -d` but I doubt that's necessary. Remove this step if you succeeded without it.
  - Then start the relevant `launch.json` config in VSCode and run `./vendor/bin/sail up` like normal.
  - According to the docs it's also possible to debug artisan commands somehow ([link](https://laravel.com/docs/11.x/sail#xdebug-cli-usage))

### Database UI

#### Local

1. Install DBeaver
2. Create a connection with:
    > host: localhost
    > database: laravel
    > username: sail
    > password: <see .env>
    Double check `.env` values if there's an issue
3. Setup scripts
   - In the bottom left section: Right click > Create > Link folder > Select exnota-tools/tools
   - To use:
     - Open a script.
     - Right click > Execute > Set Active Connection
     - Execute a command

#### Prod

1. Expose port outside docker so local services can connect

  ```bash
  # Hetzner
  dokku postgres:expose exnota-tools-db 127.0.0.1:5432
  ```

2. In DBeaver create the connection:
   > SSH tab:
   > - ✅ Use SSH tunnel
   > - User/pass: [Hetzner SSH credentials]
   > - Auth method: [Public Key]
   > - Private key/passphrase: Same as SSH
   > Then test tunnel connection
   >
   > Main tab:
   > - Host: 127.0.0.1
   > - Database: exnotatools
   > - User/Pass: [look up]

### Testing

Vite (frontend) must be running.

- In debug `./sail.sh debug test`
- Specific test `./sail.sh debug test --filter test_method_name`
- Specific file `./sail.sh debug test tests/Feature/AuthorizationTest.php`

### Troubleshooting

#### Pages/endpoints aren't working as expected (including using ngrok)

Check that the correct hostname and port are in .env.

For ngrok using `npm build` instead of `npm run dev` may be required.

#### Web: After Notion connect, it redirects back to `/duplicates` instead of `/duplicates/create-account`

Check that you've cleared users from the database AND cleared all cookies locally.

Encountered this when I forgot to clear cookies every time I cleared the database. It redirected from `/duplicates/connect` to `/duplicates/create-account` to `/login` to `/duplicates`. I believe the second redirect was a authentication error, hence the /login redirect and then /duplicates.

#### Web: Inertia route opens in iframe

Make sure the endpoint returns a proper Intertia response, e.g. `Inertia::render` ([link](https://github.com/inertiajs/inertia/issues/586#issuecomment-810270558)).

### Laravel Model: BadMethodCallException [...] Method [nethodName in model] does not exist

The file might be cached. Try adding a log statement and see if that busts the cache. This seemed to fix the issues I had once.

#### DBeaver: Column has dot and on hover says `read-only no corresponding table column`

Disconnect/reconnect ("Invalidate" may not work). DBeaver doesn't have updated DDKs.

#### Error: `SQLSTATE[08006] [7] could not translate host name "pgsql" to address: nodename nor servname provided`

When local, use `sail` to run migrations, tinker, etc in the Docker container from the host (your machine).

```bash
./vendor/bin/sail artisan migrate
```

#### Error: `Page Expired` on public URL

Add the path to `app.php` at:

```php
        $middleware->validateCsrfTokens(except: [
            '/api/notion/auth/save',
        ]);
```

There may be a more secure way to do this.

Reference: <https://laravel.com/docs/11.x/csrf#csrf-excluding-uris>

### Testing prod build locally

1. In `.env` set `APP_URL=http://localhost:8000`, `ASSET_URL=http://localhost:8000`
2. In `docker-compose-yml`, comment out `laravel.local` and uncomment `laravel.prod`
3. Run

    ```bash
    # "--force-recreate" fixes network issues that may arise, probably due to
    # using docker-compose directly here and via Sail other times.
    docker-compose up --build --force-recreate
    ```  
  
4. Start workers

    ```bash
    docker exec -it exnota-tools-prod php /var/www/html/artisan queue:work -vvv --queue=default --timeout=180 --max-time=3600
    docker exec -it exnota-tools-prod php /var/www/html/artisan queue:work -vvv --queue=delete-1 --timeout=180 --max-time=3600
    docker exec -it exnota-tools-prod php /var/www/html/artisan queue:work -vvv --queue=delete-2 --timeout=180 --max-time=3600
    ```

5. Start scheduler

    ```bash
    docker exec -it exnota-tools-prod cron -f
    docker exec -it exnota-tools-prod tail -f --retry /var/log/cron.log
    ```

6. Access via <http://localhost:8000/duplicates>

#### Broadcasting to public URL

This is how I got broadcasting working inside a Notion embed.

1. Start the supporting Laravel services:
   1. `default` job queue worker for handling broadcast events:

        ```bash
        ./vendor/bin/sail debug queue:listen -vvv --queue=default --timeout=180
        ```

   2. Scheduler for pinging Spotify:

        ```bash
        ./sail.sh debug schedule:work -vvv
        ```

2. Start ngrok forwarding to both ports 80 and 8080

    ```bash
    # ngrok auth token can be found via `ngrok config check` and opening the
    # yml file.
    ngrok start --config ~/code/exnota-tools/ngrok.yml --all --authtoken="AUTH_TOKEN_HERE"
    ```

    This will provide two URLs. The one that routes outside traffic to your
    local port 80 is for the widget itself. And one that routes traffic to your
    port 8080, which is for the websocket connection inside the widget.

3. Laravel/web config:

    ```env
    # If you don't want to see the debug UI in the widget.
    APP_DEBUG=false

    # Set the ngrok URL that points to **port 80**
    APP_URL=<ngrok port 80 url>
    ASSET_URL=<ngrok port 80 url>
    ```

4. Vite config:

    ```env
    VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"

    # Set the ngrok URL that points to **port 8080** (exclude 'https://')
    VITE_REVERB_HOST=<ngrok port 8080 url>

    # No VITE_REVERB_PORT set
    # No VITE_REVERB_SCHEME set
    ```

    So I think the VITE_\* config is how the frontend should connect to the
    broadcast service. Whereas REVERB_* is probably: a) How the Reverb service
    itself is configured, and b) How other Laravel services should connect to
    the Reverb service.

    According to `echo.js`, the port should default to `443` (which is
    apparently) the default for `wss` (which is the `https` equivalent) and
    scheme default to `https`.

5. Rebuild frontend via Vite: `npm run build`

    This will pull in the latest .env changes. Also required for APP/ASSET_URL
    changes.

    I don't think dev mode (`npm run dev`) will work over ngrok as Vite runs
    over port `5173` in that case.

6. Start Reverb service:

    ```bash
    ./sail.sh debug reverb:start --debug -vvv
    ```

    With ngrok passing the hostname isn't necessary, probably because ngrok
    itself forwards traffic to localhost:8080. This is probably also the case
    when using nginx.

    Some environments may require the hostname/host/port to be set. See: <https://laravel.com/docs/11.x/reverb#ssl>

7. Embed the widget using the ngrok URL forwarding to your port 80
    Or replace existing widgets if the ngrok URL changed.

    E.g. <ngrok port 80 url>/spotify/dashboard/3/widget

8. Open console and check websockets is working.

#### Other

- Debugging and live updates: I think this worked when the volume `.:/var/www/html` was configured in docker-compose.yml. Now it's disabled so I don't think it will.

## Initial prod setup

### SSH

1. Create an SSH key locally (not dev container) and add to Hetzner via UI.
   - This will be used for deploys and can be used to configure Dokku remotely.

### Dokku

1. Create app and database

    ```bash
    dokku apps:create exnota-tools

    # Might give 'already exists' error if alreay downloaded
    sudo dokku plugin:install https://github.com/dokku/dokku-postgres.git

    # Might need to set DB version
    dokku postgres:create exnota-tools-db

    dokku postgres:link exnota-tools-db exnota-tools

    # Setup database and user
    #
    # In Hetzner, which opens db container
    dokku postgres:enter exnota-tools-db

    # In db container
    psql -U postgres

    # Postgres commands
    #
    # Note: Choose a user password without $ or ! characters as they might
    # cause issues when entering them as environment variables via the command
    # line. I tried escaping them: One worked, the other didn't. Easier to avoid.
    #
    CREATE DATABASE exnotatools;
    \c exnotatools
    CREATE USER app WITH PASSWORD 'your_password';
    GRANT CREATE, CONNECT ON DATABASE exnotatools TO app;
    GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO app;
    GRANT USAGE, CREATE ON SCHEMA public TO app;

    # Configure worker
    dokku ps:set exnota-tools restart-policy unless-stopped
    ```

2. Create redis

    ```bash
    dokku plugin:install https://github.com/dokku/dokku-redis.git redis
    dokku redis:create exnota-tools-redis --image-version 7.4.0 --password <REDIS_PASSWORD>
    dokku redis:link exnota-tools-redis exnota-tools
    dokku config:set exnota-tools --no-restart CACHE_STORE=redis REDIS_HOST=dokku-redis-exnota-tools-redis REDIS_PASSWORD=<REDIS_PASSWORD>
    # Expose to outside
    dokku redis:expose exnota-tools-redis 127.0.0.1:6379
    ```

3. Add Dokku as git remote

    ```bash
    # `exnota-tools` is the remote branch name, matching the app name
    git remote add dokku dokku@195.201.33.178:exnota-tools
    ```
    <!-- Probably don't need this: -->
    <!-- 3. Set build directory

    ```bash
    dokku builder:set exnota-tools build-dir app/backend
    ``` -->

4. Set builder type to Docker

    ```bash
    dokku builder:set exnota-tools selected dockerfile

    ```
    <!-- Don't think this is needed -->
    <!-- # Set multi-stage build target
    dokku docker-options:add exnota-tools build "--target prod" -->

5. Add domain (assuming its DNS config already points to VPS IP)

   ```bash
   dokku domains:add exnota-tools tools.exnota.com
   ```

    <!-- 6. Remove default 'Welcome to nginx' page -->

    <!-- Not sure this is needed -->
    <!-- ```bash
    rm /etc/nginx/sites-enabled/default
    ``` -->

    <!-- Not sure this is needed -->
    <!-- 7. Make app accessible without `8080` port:

    ```bash
    dokku ports:add exnota-tools http:80:8080
    ``` -->

6. Setup lets encrypt

    ```bash
    dokku letsencrypt:set exnota-tools email email@mattschultz.info
    dokku letsencrypt:enable exnota-tools

    # Have not tested this. Unsure how to.
    dokku letsencrypt:cron-job --add
    ```

    `letsencrypt:enable` will give a warning like:

    > Your account credentials have been saved in your Let's Encrypt configuration directory at "/certs/accounts".

    However the folder is actually empty. I found one folder by doing this:

    ```bash
    find / -type f -name "docker.env"
    ```

    Technically you're supposed to regularly backup, I suppose because it will be regenerated. But I'll leave that to another day.

    You can get a local copy by running this from your host:

    ```bash
    scp -r root@195.201.33.178:/home/dokku/exnota-tools/letsencrypt/certs exnota-tools-letsencrypt-backup
    ```

7. Setup environment variables from .env

- Get `DB_HOST` from `dokku postgres:info exnota-tools-db` under `Dsn` between `@` and `:`
- Get `APP_KEY` by:
  - Creating a dummy `.env` file.
  - Running `php artisan key:generate`.
- For the remaining, adapt from local `.env`.

   ```bash
   ssh -t dokku@195.201.33.178 config:set exnota-tools --no-restart VAR_1=VALUE VAR_2="VALUE WITH SPACE"
   ```

    <!-- Maybbe I don' need perisstence? -->
    <!-- 9. Setup volume for persistence

    ```bash
    # In the future should probably follow these steps INSTEAD of separate mkdir
    # and chown commands: https://dokku.com/docs/advanced-usage/persistent-storage/#creating-storage-directories

    mkdir -p /var/lib/dokku/data/storage/exnota-tools/pb_data

    chown dokku:dokku /var/lib/dokku/data/storage/exnota-tools/pb_data

    dokku docker-options:add exnota-tools deploy "-v /var/lib/dokku/data/storage/exnota-tools/pb_data:/pb/pb_data"
    ``` -->

<!-- 10. Increase file upload limit

    ```bash
    ssh -t dokku@195.201.33.178 nginx:set exnota-tools client-max-body-size 5M
    ssh -t dokku@195.201.33.178 ps:restart exnota-tools
    ``` -->

### Troubleshooting initial setup

- Check if app can connect to database:

    ```bash
    # Hetzner
    dokku enter exnota-tools

    # Docker
    php artisan db:show
    ```

## Old documentation copied from `exnota` repo

### Troubleshooting assorted old

## Maintenance

### Check PHP settings (incl. php.ini)

```bash
php artisan tinker

phpinfo()
```

### Environment variables

Required variables in `/app/backend/cmd/config/config.go`.

```bash
ssh -t dokku@195.201.33.178 config:get exnota-tools VARIABLE_NAME
ssh -t dokku@195.201.33.178 config:set exnota-tools VARIABLE_NAME=VALUE --no-restart
```

See <https://dokku.com/docs/configuration/environment-variables/>

### Checking local logs

```bash
# Using Sail
docker exec -it exnota-tools-local tail -f /var/www/html/storage/logs/laravel.log

# Prod-like: App + Default Worker + Delete Worker + Supervisor
docker exec -it exnota-tools-prod tail -f /var/www/html/storage/logs/laravel.log
docker exec -it exnota-tools-prod-workers tail -f /var/www/html/storage/logs/laravel.log
docker exec -it exnota-tools-prod-workers tail -f /var/log/supervisor/worker-default.log
docker exec -it exnota-tools-prod-workers tail -f /var/log/supervisor/worker-delete-1.log
docker exec -it exnota-tools-prod-workers tail -f /var/log/supervisor/supervisor.log
```

### Checking prod logs

```bash
# Web + Default Worker + Delete Worker + Supervisor
ssh -t dokku@195.201.33.178 enter exnota-tools web tail -f --retry /var/www/html/storage/logs/laravel.log
ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/www/html/storage/logs/laravel.log
ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/worker-default.log
ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/worker-delete-1.log
ssh -t dokku@195.201.33.178 enter exnota-tools worker tail -f --retry /var/log/supervisor/supervisor.log
```

#### Dokku

```bash
# Local machine
ssh -t dokku@195.201.33.178 logs exnota-tools -n 10000 -t
```

#### Laravel Pail

So far I haven't found these useful. Maybe because prod logs by default are minimal.

```bash
# "web" is the default app process when no Dokku Procfile is provided
dokku enter exnota-tools web php artisan pail -vv
```

### Restart jobs (prod)

```bash
# Single (on server)
php artisan queue:retry <uuid>

# Single (off server)
ssh -t dokku@195.201.33.178 enter exnota-tools worker php artisan queue:retry <uuid>

# All (on server)
php artisan queue:retry all

# All (off server)
ssh -t dokku@195.201.33.178 enter exnota-tools worker php artisan queue:retry <uuid>
```

### How to execute Dokku commands

There is an [official client](https://dokku.com/docs/deployment/remote-commands/#official-client) but commands can be executed simply via SSH:

1. Ensure SSH is setup as below.

2. Omit `dokku` root command and send sub-command. On local machine, not dev container.

    ```bash
        ssh -t dokku@195.201.33.178 <sub-command>

        # E.g. Send `dokku version`
        ssh -t dokku@195.201.33.178 version
    ```

## Hetzner SSH

### Terminal

```bash
% ssh root@195.201.33.178
Enter passphrase for key '/Users/matt/.ssh/id_ed25519_hetzner2':
```

### CLI

Have not logged in using CLI successfully yet. Maybe because of this ([link](https://docs.hetzner.com/cloud/servers/getting-started/vnc-console/)):

> If your server was created with an SSH key, or if you no longer have the password, you can request a new root password:

## Archive

Notes that may be useful in the future.

### Manual Hetzner deploy

1. Login as root (no other option for now)
2. mkdir deploy
3. Create ssh key to use as Github Deploy Key ([link](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys#deploy-keys))
   - Ensure to also follow the Linux steps for 'Adding your SSH key to the ssh-agent'
4. Might need to manually add `~/.ssh/config` file
5. Clone. Which must be done via SSH not HTTPS (latter will ask for user/pass rather than use SSH Key)

    ```bash
    git clone -b pocketbase-poc git@github.com:mttschltz/exnota.git
    ```

6. cd exnota/app/backend
7. docker compose up
