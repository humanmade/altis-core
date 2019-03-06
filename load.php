<?php

/*
 * Main entry point loader for the Core module.
 */

add_filter( 'plugins_url', 'HM\\Platform\\fix_plugins_url', 10, 3 );
