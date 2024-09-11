# Conventions

- This is a Laravel repo.
- It uses Sail, which is available at `./vendor/bin/sail`
- `artisan` commands can be executed using `./vendor/bin/sail artisan ...`
- When making a model/database change, also create a migration using these steps:
  1. Create a migration with: `artisan make:migration migration_description_here`
  2. That command will create a file and output it's name, e.g. `database/migrations/2024_09_10_053206_migration_description_here.php`
  3. Add the up and down parts of the migration to that file.
  4. Run the migration using `./vendor/bin/sail migrate`
