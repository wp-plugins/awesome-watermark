<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option( 'awm_general_settings' );
delete_option( 'awm_advanced_settings' );
?>