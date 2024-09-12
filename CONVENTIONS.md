# Conventions

- This is a Laravel repo.
- It uses Sail, which is available at `./vendor/bin/sail`
- `artisan` commands can be executed using `./vendor/bin/sail artisan ...`
- When making model/database changes, make a migration script. See steps below.
- These are the steps for adding a database migration:
  1. Before proceeding, request `artisan` be added to the chat.
  2. Create a migration with: `artisan make:migration migration_description_here`
  3. Request to read the output. Use this to get the full filename of the script it created.
  4. That command will create a file and output it's name, e.g. `database/migrations/2024_09_10_053206_migration_description_here.php`
  5. Request that file to be added to the chat.
  6. Modify that file by adding the up and down parts of the migration to it.
  7. Run the migration using `./vendor/bin/sail migrate`
