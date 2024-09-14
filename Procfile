# TODO: To keep the queue:work process running permanently in the background,
# you should use a process monitor such as Supervisor to ensure that the queue
# worker does not stop running.

# TODO: Test this somehow:
# "Remember, queue workers are long-lived processes and store the booted
# application state in memory. As a result, they will not notice changes in your
# code base after they have been started. So, during your deployment process, be
# sure to restart your queue workers."

# web: vendor/bin/heroku-php-apache2 public/
# web: vendor/bin/heroku-php-apache2 public/
web: vendor/bin/heroku-php-nginx public/
# worker: php artisan queue:work -v

# This one didn't work:
# web: vendor/bin/heroku-php-apache2 public/
#
#
# remote: #22 naming to docker.io/dokku/exnota-tools:latest done
# remote: #22 DONE 4.3s
# -----> Releasing exnota-tools...
# -----> Checking for predeploy task
#        No predeploy task found, skipping
# -----> Checking for release task
#        No release task found, skipping
# =====> Processing deployment checks
# remote:  !     No healthchecks found in app.json for web process type
#        No web healthchecks found in app.json. Simple container checks will be performed.
#        For more efficient zero downtime deployments, add healthchecks to your app.json. See https://dokku.com/docs/deployment/zero-downtime-deploys/ for examples
# -----> Deploying exnota-tools via the docker-local scheduler...
# -----> Deploying web (count=1)
#        Attempting pre-flight checks (web.1)
# -----> Executing 2 healthchecks
#        Running healthcheck name='default' type='uptime' uptime=10
#        Running healthcheck name='port listening check' attempts=3 port=80 retries=2 timeout=5 type='listening' wait=5
# remote:  !     Failure in name='default': container has restarted 7 times
# =====> Start healthcheck output
#        state=restarting
# =====> End healthcheck output
# remote:  !     Failure in name='port listening check': container state is not running
# 245223cddf49c5f32c9e7d1b33939ea7771d6ef5e4dc21821a99ecfa50e094ee
# remote:  !     Could not start due to 1 failed checks (web.1)
# =====> Start of exnota-tools container output (web.1)
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
# =====> End of exnota-tools container output (web.1)
# =====> Start of  container output (.)
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
#        /usr/local/bin/docker-php-entrypoint: 9: exec: vendor/bin/heroku-php-apache2: not found
# =====> End of  container output (.)
# remote: parallel: This job failed:
# remote: /var/lib/dokku/plugins/available/scheduler-docker-local/bin/scheduler-deploy-process-container exnota-tools dockerfile dokku/exnota-tools:latest latest web 1 1
# To 195.201.33.178:exnota-tools
#  ! [remote rejected] main -> main (pre-receive hook declined)
# error: failed to push some refs to '195.201.33.178:exnota-tools'
