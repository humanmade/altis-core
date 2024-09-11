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
