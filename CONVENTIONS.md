# Conventions

- This is a Laravel repo.
- It uses Sail, which is available at `./vendor/bin/sail`
- `artisan` commands can be executed using `./vendor/bin/sail artisan ...`
- When making model/database changes, make a migration script. See steps below.
- These are the steps for adding a database migration:
  1. Add `artisan` to the chat so you can read its output.
  2. Create a migration with: `artisan make:migration migration_description_here`
  3. That command will create a file and output it's name, e.g. `database/migrations/2024_09_10_053206_migration_description_here.php`
  4. In that file, add the up and down parts of the migration to that file.
  5. Run the migration using `./vendor/bin/sail migrate`
