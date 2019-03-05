<?php

namespace HM\Platform;

add_filter( 'plugins_url', __NAMESPACE__ . '\\fix_plugins_url', 10, 3 );
