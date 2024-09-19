# Conventions

- This is a Laravel repo.
- It uses Sail, which is available at `./vendor/bin/sail`
- `artisan` commands can be executed using `./vendor/bin/sail artisan ...`
- When making model/database changes, make a migration script. See steps below.
- When asked for a migration for a model change, follow these steps.
  1. Create an empty migration file with: `artisan make:migration migration_description_here`
  2. Get the file name from the output of that command
  3. In the file, add the migration 'up' code to the `up()` method, and the same for down and `down()`
  4. After you have added the content to `up()` and `down()` methods, then run the migration using `./vendor/bin/sail migrate`
