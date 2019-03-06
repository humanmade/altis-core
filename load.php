<?php

/*
 * Main entry point loader for the Core module.
 */

add_filter( 'plugins_url', __NAMESPACE__ . '\\fix_plugins_url', 10, 3 );
