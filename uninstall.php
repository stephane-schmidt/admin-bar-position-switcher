<?php
/**
 * Clean up plugin data on uninstall.
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'abps_options' );

// Multisite: remove the option from every site.
if ( is_multisite() ) {
	$abps_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $abps_site_ids as $abps_site_id ) {
		switch_to_blog( $abps_site_id );
		delete_option( 'abps_options' );
		restore_current_blog();
	}
}
