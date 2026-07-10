<?php
/**
 * Clean up plugin data on uninstall.
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_metadata( 'user', 0, 'switchmybar_bar_color', '', true );

delete_option( 'switchmybar_options' );
delete_option( 'switchmybar_colors_detected' );
// Legacy names used before the switchmybar prefix (pre-1.6.0).
delete_option( 'abps_options' );
delete_option( 'abps_colors_detected' );

// Multisite: remove the options from every site.
if ( is_multisite() ) {
	$switchmybar_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $switchmybar_site_ids as $switchmybar_site_id ) {
		switch_to_blog( $switchmybar_site_id );
		delete_option( 'switchmybar_options' );
		delete_option( 'switchmybar_colors_detected' );
		delete_option( 'abps_options' );
		delete_option( 'abps_colors_detected' );
		restore_current_blog();
	}
}
