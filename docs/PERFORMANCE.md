# Performance

## Delete job

Ideas not implemented in initial performance fixes:

- Limit users max deletions (i.e. don't allow over 5,000)
- Use debugbar in prod
- Try closing DB connection while requests are being sent out, in the jobs
  - <https://laracasts.com/discuss/channels/laravel/understanding-how-database-connections-work-in-laravel>
  - DB::reconnect()
- Try adding a sleep on default jobs
- Use 'nice' to lower cpu priority on certain jobs: <https://sitehost.nz/blog/laravel-queue-performance>
- Try redis instead of db queues: <https://laracasts.com/discuss/channels/laravel/very-slow-queue>
  - If trying redis, maybe also add Horizon: <https://laravel.com/docs/11.x/horizon>
