# Exnota Tools

- [Exnota Tools](#exnota-tools)
  - [Cheatsheet](#cheatsheet)
  - [Deploy to prod](#deploy-to-prod)
    - [Steps](#steps)
  - [Local development](#local-development)
    - [Running](#running)
    - [Workers](#workers)
    - [Redis](#redis)
    - [Setup](#setup)
    - [Database UI](#database-ui)
      - [Local](#local)
      - [Prod](#prod)
    - [Troubleshooting](#troubleshooting)
      - [Web: Inertia route opens in iframe](#web-inertia-route-opens-in-iframe)
    - [Laravel Model: BadMethodCallException \[...\] Method \[nethodName in model\] does not exist](#laravel-model-badmethodcallexception--method-nethodname-in-model-does-not-exist)
      - [DBeaver: Column has dot and on hover says `read-only no corresponding table column`](#dbeaver-column-has-dot-and-on-hover-says-read-only-no-corresponding-table-column)
      - [Error: `SQLSTATE[08006] [7] could not translate host name "pgsql" to address: nodename nor servname provided`](#error-sqlstate08006-7-could-not-translate-host-name-pgsql-to-address-nodename-nor-servname-provided)
      - [Error: `Page Expired` on public URL](#error-page-expired-on-public-url)
    - [Testing prod build locally](#testing-prod-build-locally)
  - [Initial prod setup](#initial-prod-setup)
    - [SSH](#ssh)
    - [Dokku](#dokku)
    - [Troubleshooting initial setup](#troubleshooting-initial-setup)
  - [Old documentation copied from `exnota` repo](#old-documentation-copied-from-exnota-repo)
    - [Troubleshooting assorted old](#troubleshooting-assorted-old)
      - [Error: At least one invalid signature was encountered, or "no space left on device"](#error-at-least-one-invalid-signature-was-encountered-or-no-space-left-on-device)
  - [Maintenance](#maintenance)
    - [Check PHP settings](#check-php-settings)
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

### Steps

1. !FIRST TIME ONLY! deploying from a specific device.
   - Don't run inside dev container!
   - Ensure you have configured [SSH](#ssh)
   - Add Dokku remote to git repo

        ```bash
        # `exnota-tools` is the remote branch name, matching the app name
        git remote add dokku dokku@<domain>:exnota-tools
        ```

2. Deploy app
   TODO: Migrations technically need to be run first to avoid issues. When site
   starts getting traffic, need to move to this.

      ```bash
      # Deploy master branch
      git push dokku main

      # OR: Deploy branch <branch-name>
      git push dokku <branch-name>:main
      ```

3. Run migrations

      ```bash
      ssh -t dokku@195.201.33.178 enter exnota-tools web php artisan migrate

      # OR
      ssh root@195.201.33.178
      docker exec -it exnota-tools.web.1 php artisan migrate
      ```

4. Manually restart worker

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

5. Check statuses with `dokku ps:report exnota-tools`

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

## Local development

### Running

```bash
./vendor/bin/sail up
./vendor/bin/sail down
```

### Workers

Manually start workers in debug mode:

```bash
# See supervised-worker.conf
./vendor/bin/sail debug queue:listen -vvv --queue=default --timeout=120
./vendor/bin/sail debug queue:listen -vvv --queue=delete-1 --timeout=120
```

### Redis

Use CLI

```bash
docker exec -it exnota-tools-redis redis-cli

127.0.0.1:6379> auth <REDIS_PASSWORD>
# The cache is in 'database 1'. See this line in database.php:
#   'database' => env('REDIS_CACHE_DB', '1'),
127.0.0.1:6379> select 1

# Assorted commands
127.0.0.1:6379> keys *
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

### Troubleshooting

#### Web: Inertia route opens in iframe

Make sure the endpoint returns a proper Intertia response, e.g. `Inertia::render` ([link](https://github.com/inertiajs/inertia/issues/586#issuecomment-810270558)).

### Laravel Model: BadMethodCallException [...] Method [nethodName in model] does not exist

The file might be cached. Try adding a log statement and see if that busts the cache. This seemed to fix the issues I had once.

#### DBeaver: Column has dot and on hover says `read-only no corresponding table column`

Disconnect/reconnect ("Invalidate" may not work). DBeaver doesn't have updated DDKs.

#### Error: `SQLSTATE[08006] [7] could not translate host name "pgsql" to address: nodename nor servname provided`

When local, use `sail` to run migrations in the Docker container from the host (your machine).

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

1. In `docker-compose-yml`, comment out `laravel.local` and uncomment `laravel.prod`
2. Run

  ```bash
  # "--force-recreate" fixes network issues that may arise, probably due to
  # using docker-compose directly here and via Sail other times.
  docker-compose up --build --force-recreate
  ```  
  
3. Access via <http://localhost:8000/duplicates>

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

2. Add Dokku as git remote

    ```bash
    # `exnota-tools` is the remote branch name, matching the app name
    git remote add dokku dokku@195.201.33.178:exnota-tools
    ```
    <!-- Probably don't need this: -->
    <!-- 3. Set build directory

    ```bash
    dokku builder:set exnota-tools build-dir app/backend
    ``` -->

3. Set builder type to Docker

    ```bash
    dokku builder:set exnota-tools selected dockerfile

    ```
    <!-- Don't think this is needed -->
    <!-- # Set multi-stage build target
    dokku docker-options:add exnota-tools build "--target prod" -->

4. Add domain (assuming its DNS config already points to VPS IP)

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

5. Setup lets encrypt

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

6. Setup environment variables from .env

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

#### Error: At least one invalid signature was encountered, or "no space left on device"

Former is apparently caused by insufficient space according to this [SO post](https://stackoverflow.com/questions/62473932/at-least-one-invalid-signature-was-encountered) (which has more options for freeing space in addition to below).

Fix:

1. SSH to Hetzner server.
2. Run

    ```bash
    docker builder prune
    ```

Use `df -h` before/after to check how much space this is freeing up.

## Maintenance

### Check PHP settings

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
