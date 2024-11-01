<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

global $wpdb;

delete_option('mkg_ccolumns_options');