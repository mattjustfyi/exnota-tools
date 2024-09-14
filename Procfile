# TODO: To keep the queue:work process running permanently in the background,
# you should use a process monitor such as Supervisor to ensure that the queue
# worker does not stop running.

# TODO: Test this somehow:
# "Remember, queue workers are long-lived processes and store the booted
# application state in memory. As a result, they will not notice changes in your
# code base after they have been started. So, during your deployment process, be
# sure to restart your queue workers."

# Might not need to specify web as it uses the dockerfile command?
web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:work -v
