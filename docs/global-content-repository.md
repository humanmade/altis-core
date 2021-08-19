# Global Content Repository

Altis includes a Global Content Repository framework. This is a site on the network that can be used to distribute content elsewhere via the REST API or directly to other sites on the network.

By default the Global Content Repository only exposes the user management admin. Other Altis modules may extend this, for example [the Altis Media module has a Global Media Library feature](docs://media/global-media-library.md) built on top of the Global Content Repository that allows for uploading media files and accessing them from all sites on the network.

Global Content Repository site is set to be a private site by default, this can be modified by switching the site to a public one from the Network Admin > Sites management page, clicking `Edit` on the site, and checking the `Public` checkbox.

## User Management

Users on the multisite network can have different roles on different sites. This gives you control over who can add and edit global content versus who can only use the global content on their own site.

You might have a user who is an author on the main site, but have no role on the Global Content Repository meaning they could read content to use in their posts but not create or edit global content nor access the global site admin.

Conversely you may have a user who does not have a role on your primary site but who can create and edit content on the Global Content Repository site.

## Functions

**`Altis\Global_Content\get_site_id() : ?int`**

Returns the Global Content Repository site ID or null if it doesn't exist yet.

**`Altis\GlobaL_Content\get_site_url() : ?string`**

Returns the Global Content Repository site URL or null if it doesn't exist yet.

**`Altis\Global_Content\is_global_site( ?int $site_id = null ) : bool`**

Returns `true` if the current site is the Global Content Repository or if the site with the passed `$site_id` is.

**`Altis\Global_Content\get_allowed_admin_pages() : array`**

Returns the list of page slugs allowe din the Global Content Repository site admin menu.

## Filters

**`altis.core.global_content_site_args : array $args`**

Filters the arguments used to create the Global Content Repository site. The arguments are passed to `wp_insert_site()`.

The below example changes the title, path and domain of the site created. These could also be edited via the network admin as well:

```php
use Altis\Cloud;

add_filter( 'altis.core.global_content_site_menu_pages', function ( array $args ) : array {
    // Use the subdomain global.example.org.
    $args['domain'] = sprintf( 'global.%s', parse_url( Cloud\get_main_site_url(), PHP_URL_HOST ) );
    $args['path'] = '/';
    $args['title'] = __( 'Shared Content' );
    return $args;
} );
```

**`altis.core.global_content_site_menu_pages : array $pages`**

Filters the allowed top level admin menu pages. Defaults to `[ 'users.php' ]`. To add support for pages you could use the following code:

```php
add_filter( 'altis.core.global_content_site_menu_pages', function ( array $pages ) : array {
    $pages[] = 'edit.php?post_type=page';
    $pages[] = 'post-new.php?post_type=page';
    return $pages;
} );
```
