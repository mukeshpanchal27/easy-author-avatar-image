<?php
/**
 * Plugin uninstaller logic.
 */

// If uninstall.php is not called by WordPress, bail.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// For a multisite, delete the option for all sites (however limited to 100 sites to avoid memory limit or timeout problems in large scale networks).
if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields'                 => 'ids',
			'number'                 => 100,
			'update_site_cache'      => false,
			'update_site_meta_cache' => false,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		eaai_delete_plugin_option();
		restore_current_blog();
	}
} else {
	eaai_delete_plugin_option();
}

// User meta lives in a single network-wide table, so this runs once rather than per site.
eaai_delete_plugin_user_meta();

/**
 * Delete the current site's option.
 *
 * The option was written by the onboarding settings page removed in 1.5. It is still
 * deleted here so sites upgrading from an earlier version don't leave the row behind.
 */
function eaai_delete_plugin_option(): void {
	delete_option( 'easy_author_avatar_image_option' );
}

/**
 * Delete every user's stored avatar.
 *
 * This is the data the plugin actually creates: one row per user who set a custom
 * avatar. The $delete_all argument removes the meta key for all users in one query.
 */
function eaai_delete_plugin_user_meta(): void {
	delete_metadata( 'user', 0, 'easy-author-avatar-profile-image', '', true );
}
