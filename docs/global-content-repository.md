# Global Content Repository

Altis includes a Global Content Repository framework. This is a site on the network that can be used to distribute content elsewhere via the REST API or directly to other sites on the network.

By default the Global Content Repository only exposes the user management admin. Other Altis modules may extend this, for example [the Altis Media module has a Global Media Library feature](docs://media/global-media-library.md) built on top of the Global Content Repository that allows for uploading media files and accessing them from all sites on the network.

## User Management

Users on the multisite network can have different roles on different sites. This gives you control over who can add and edit global content versus who can only use the global content on their own site.

You might have a user who is an author on the main site, but only a subscriber to the Global Content Repository meaning they could read content to use in their posts but not create or edit global content.

Conversely you may have a user who does not have access to your primary site but who can create and edit content on the Global Content Repository.

## Filters

**`altis.core.global_content_site_args: array $args`**

Filters the arguments used to create the Global Content Repository site. The arguments are passed to `wp_insert_site()`.

**`altis.core.global_content_site_menu_pages: array $pages`**

Filters the allowed top level admin menu pages. Defaults to `[ 'users.php' ]`. To add support for pages you could use the following code:

```php
add_filter( 'altis.core.global_content_site_menu_pages', function ( array $pages ) : array {
    $pages[] = 'edit.php?post_type=page';
    $pages[] = 'post-new.php?post_type=page';
    return $pages;
} );
```
