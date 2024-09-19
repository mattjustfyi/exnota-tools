# Dockerfile-based deploys, like we use, are considered a power user feature and
# might not behave the same as much of the docs. This doc is very useful in this
# regard: https://dokku.com/docs/deployment/builders/dockerfiles/#customizing-the-run-command
#
# Some things to note:
# - The `web` process type is required.
# - `apache2-foreground` is the command from the Dockerfile image (php:8.2-apache).
# - These commands are passed to `docker run`, which means they override any
#   `CMD` or `ENTRYPOINT` in the Dockerfile.
web: apache2-foreground

# TODO: How to restart this?
# - Dokku should be able to do it on: dokku ps:set exnota-tools restart-policy unless-stopped
#   - But would need to test in production
# - Otherwise, supervisor?
#   - Instead: supervisorctl start "laravel-worker:*"
worker: php artisan queue:work -v --timeout=120
