# Altis CLI Command

The Altis CLI command is used for general maintenance tasks and allows other modules and custom code to hook in.

## `wp altis migrate`

The migration command runs all basic maintenance tasks required after upgrading Altis. By default this includes:

- Ensuring the WordPress database schema is up to date
- Ensuring the Cavalcade database schema is up to date
- Creating the [Global Content Repository](./global-content-repository.md)

There are 2 ways to hook into this command:

1. Using the `altis.migrate` action hook:

   ```php
   add_action( 'altis.migrate', function ( array $args, array $assoc_args ) {
       // Run upgrade routines here.
   } );
   ```

2. Using WP CLI hooks:

   ```php
   WP_CLI::add_hook( 'after_invoke:altis migrate', function () {
       // Run migration routines here.
   } );
   ```

If you wish to run additional WP CLI commands on this hook you can do so using the `WP_CLI::runcommand()` method.

```php
WP_CLI::add_hook( 'after_invoke:altis migrate', function () {
    WP_CLI::runcommand( 'cli info' );
} );
```

## `wp altis post-sync`

The post-sync command runs routine maintenance tasks after syncing an environment, for example after pulling a production 
database to staging or development. By default, this includes:

- Truncating the Cavalcade cron logs table
- Reindexing Elasticsearch when the Search module is enabled (see
  [Search module docs](docs://search/cli-command.md))

### Hooking into the command

There are 2 ways to hook into this command:

1. Using the `altis.post_sync` action hook:

   ```php
   add_action( 'altis.post_sync', function () {
       // Run post-sync routines here.
   } );
   ```

2. Using WP CLI hooks:

   ```php
   WP_CLI::add_hook( 'after_invoke:altis post-sync', function () {
       // Run post-sync routines here.
   } );
   ```

### Removing default tasks

All default tasks are registered as named functions, so they can be unhooked individually:

```php
// Skip truncating Cavalcade logs.
remove_action( 'altis.post_sync', 'Altis\Post_Sync\truncate_cavalcade_logs' );
```

Tasks contributed by other modules (for example the Search module's Elasticsearch reindex) are documented
alongside those modules.

### Customer examples

Here are some common tasks you might want to run after syncing an environment:

```php
add_action( 'altis.post_sync', 'myproject_post_sync_tasks' );

function myproject_post_sync_tasks() {
    // Anonymize user data on non-production environments.
    global $wpdb;
    $wpdb->query(
        "UPDATE {$wpdb->users} SET user_email = CONCAT( 'user', ID, '@example.com' ), user_pass = '' WHERE ID > 1"
    );
    WP_CLI::success( 'User data anonymized.' );

    // Truncate a large logging table to reduce database size.
    $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}activity_log" );
    WP_CLI::success( 'Activity log truncated.' );

    // Disable analytics tracking on non-production environments.
    update_option( 'analytics_enabled', false );
    WP_CLI::success( 'Analytics disabled.' );

    // Disable outbound API integrations.
    update_option( 'crm_sync_enabled', false );
    update_option( 'email_service_live', false );
    WP_CLI::success( 'External API integrations disabled.' );
}
```
