<?php
/**
 * The file that handles weekly auto-updates
 *
 * @since 0.2.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

/**
 * Class for handling automatic weekly batch updates.
 *
 * @since 0.2.0
 */
class BAUFM_Updater {

	/**
	 * Singleton.
	 *
	 * @since 0.2.0
	 * @var BAUFM_Updater
	 */
	private static $instance;

	/**
	 * Return the instance of the current object of this class.
	 *
	 * @since 0.2.0
	 *
	 * @static
	 * @return BAUFM_Updater Singleton instance of this class.
	 */
	static function _instance() {
		return self::$instance;
	}

	/**
	 * PHP5 Constructor.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function __construct() {

		// Make our instance available on static calls.
		self::$instance = $this;

		// Use the cron schedules added by MainWP.
		add_filter( 'cron_schedules', array( 'MainWPUtility', 'getCronSchedules' ) );

		// Call back action for use with WP scheduled events that runs every minute.
		add_action( 'baufm_check_for_updates_action', array( &$this, 'check_for_updates' ) );

		add_action( 'init', array( $this, 'parse_init' ) );

	  do_action( 'mainwp_cronload_action' );

		// Check if MainWP is set to use WP Cron.
		$use_wp_cron = get_option( 'mainwp_wp_cron' );

		// If we do not have anything scheduled.
		if ( false === ( $sched = wp_next_scheduled( 'baufm_check_for_updates_action' ) ) ) {

			// If we have nothing scheuled, and we are allowed to use WP cron, then schedule our task minutely.
			if ( $use_wp_cron ) {
				wp_schedule_event( time(), 'minutely', 'baufm_check_for_updates_action' );
			}

			// If we have something scheduled.
		} else {

			// If we have something scheduled and we are not allowed to user WP cron, then un-schedule it.
			if ( ! $use_wp_cron ) {
				wp_unschedule_event( $sched, 'baufm_check_for_updates_action' );
			}
		}
	}

	public function get_updates_for_current_group( $group_id, $limit = 50 ) {

		// All updates. @todo Write our own DB class for this.
		$websites = MainWPDB::Instance()->getWebsitesCheckUpdates( $limit );

		MainWPLogger::Instance()->info( count( $websites ) . ' updates available.' );

		// Surely, we have a list right.
		if ( 0 === count( $websites ) ) {
			return array();
		}

		// We will only deal with updates available for a particular group.
		$updates = array();

		foreach ( $websites as $website ) {
			// Get all the groups to which this particular website belongs to.
			$website_in_groups = $this->get_group_by_site_id( $website->id );

			// We note that a site can belong to 0, 1, or more groups.
			if ( in_array( $group_id, $website_in_groups ) ) {
				$updates[] = $website;
			}
		}

		return $updates;
	}

	public function pre_update_setup() {
		ignore_user_abort( true );
		set_time_limit( 0 );
		$mem = '512M';
		ini_set( 'memory_limit', $mem );
		ini_set( 'max_execution_time', 0 );
	}

	/**
	 * @since 0.2.0
	 */
	public function check_for_updates() {

		$this->pre_update_setup();

		// Used in MainWPServerInformation.page.php for display purposes only.
		MainWPUtility::update_option( 'mainwp_cron_last_updatescheck', time() );

		// We will only deal with batch, or grouped updates FOR NOW.
		$groups = MainWPDB::Instance()->getNotEmptyGroups();

		// We don't have a group. No batch update to make.
		if ( empty( $groups ) ) {
			MainWPLogger::Instance()->info( 'CRON :: We dont have a group.' );
			return;
		}

		// The current group scheduled now.
		$group = BAUFM_Schedules::get_group_scheduled_now( $groups );

		// We don't have a group scheduled now.
		if ( empty( $group ) ) {
			MainWPLogger::Instance()->info( 'CRON :: No group scheduled now.' );
			return;
		}

		/*
		 * Action to take when an update is available:
		 * None, email updates, or update sites.
		 */
		$action = get_option( "baufm_scheduled_action_group_{$group->id}" );

		$this->update_group( $group->id, $action );
	}

	public function update_group( $group_id, $action ) {
		MainWPLogger::Instance()->info( "CRON :: Calling update_group $group_id." );

		$updates = $this->get_updates_for_current_group( $group_id );

		// No updates for the current group.
		if ( 0 === count( $updates ) ) {
			MainWPLogger::Instance()->info( "CRON :: No update for group $group_id." );
			return;
		}

		$user_id = $this->get_user_id( $updates );
		MainWPLogger::Instance()->info( 'CRON :: Applying updates.' );

		$this->apply_updates( $updates, $user_id, $group_id, $action );
	}

	/**
	 * Each site has an associated user_id, which is points to info about trusted/ignored/conflicting updates.
	 * Use the first user_id we encounter.
	 *
	 * @since 0.2.0
	 */
	private function get_user_id( array $updates_for_current_group ) {

		$user_id = null;

		foreach ( $updates_for_current_group as $website ) {
			$website_values = array(
				'dtsAutomaticSyncStart' => 0,
			);

			if ( null === $user_id ) {
				$user_id = $website->userid;
			}

			MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );
		}

		return $user_id;
	}

	/**
	 *
	 *
	 * @since 0.2.0
	 */
	public function apply_updates( array $updates, $user_id, $group_id, $action ) {

		  MainWPLogger::Instance()->info( 'CRON :: Attempting updates.' );

			/**
			 * In MainWP, the mainwp_users table lists the trusted/ignored/dismissed
			 * plugins/themes/conflicts FOR EACH user. So we get them them here for the
			 * current user.
			 */
			$user_extension = MainWPDB::Instance()->getUserExtensionByUserId( $user_id );

			MainWPLogger::Instance()->info( 'CRON :: User extension is ' . wp_json_encode( $user_extension ) );

			// Get the ignored plugins.
			$decoded_ignored_plugins = json_decode( $user_extension->ignored_plugins, true );
			if ( ! is_array( $decoded_ignored_plugins ) ) {
				$decoded_ignored_plugins = array();
			}

			// Get the trusted plugins.
			$trusted_plugins = json_decode( $user_extension->trusted_plugins, true );
			if ( ! is_array( $trusted_plugins ) ) {
				$trusted_plugins = array();
			}

			// Get the ignored themes.
			$decoded_ignored_themes = json_decode( $user_extension->ignored_themes, true );
			if ( ! is_array( $decoded_ignored_themes ) ) {
				$decoded_ignored_themes = array();
			}

			// Get the trusted themes.
			$trusted_themes = json_decode( $user_extension->trusted_themes, true );
			if ( ! is_array( $trusted_themes ) ) {
				$trusted_themes = array();
			}

			// Core updates.
			$core_to_update_now         = array();
			$core_to_update             = array();
			$core_new_update            = array();
			$ignored_core_to_update     = array();
			$ignored_core_new_update    = array();

			// Plugin updates.
			$plugins_to_update_now      = array();
			$plugins_to_update          = array();
			$plugins_new_update         = array();
			$ignored_plugins_to_update  = array();
			$ignored_plugins_new_update = array();

			// Theme updates.
			$themes_to_update_now       = array();
			$themes_to_update           = array();
			$themes_new_update          = array();
			$ignored_themes_to_update   = array();
			$ignored_themes_new_update  = array();

			// Conflicts.
			$plugin_conflicts           = '';
			$theme_conflicts            = '';

			// All
			$all_websites               = array();

			$info_trusted_text          = ' (<span style="color:#008000"><strong>Trusted</strong></span>)';
			$info_not_trusted_text      = ' (<strong><span style="color:#ff0000">Not Trusted</span></strong>)';

			MainWPLogger::Instance()->info( 'CRON :: Beginning loop on current group.' );

			// Go over each website in our current group.
			foreach ( $updates as $website ) {

				// Get the ignored plugins.
				$website_decoded_ignored_plugins = json_decode( $website->ignored_plugins, true );
				if ( ! is_array( $website_decoded_ignored_plugins ) ) {
					$website_decoded_ignored_plugins = array();
				}

				// Get the ignored themes.
				$website_decoded_ignored_themes = json_decode( $website->ignored_themes, true );
				if ( ! is_array( $website_decoded_ignored_themes ) ) {
					$website_decoded_ignored_themes = array();
				}

				// Make sure updates are always ready..
				if ( ! MainWPSync::syncSite( $website, false, true ) ) {
					$website_values = array(
						'dtsAutomaticSync' => 0,
					);

					MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );
					continue;
				}

				$website = MainWPDB::Instance()->getWebsiteById( $website->id );

				// Check core upgrades.
				$website_last_core_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_wp_upgrades' ), true );
				$website_core_upgrades      = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'wp_upgrades' ), true );

				// Run over every update we had last time.
				if ( isset( $website_core_upgrades['current'] ) ) {
					$info_txt     = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
					$info_new_txt = '*NEW* <a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
					$new_update   = ! ( isset( $website_last_core_upgrades['current'] ) && ( $website_last_core_upgrades['current'] == $website_core_upgrades['current'] ) && ( $website_last_core_upgrades['new'] == $website_core_upgrades['new'] ) );

					// If we are OK to install trusted updates.
					if ( in_array( $action, array( 2, 3 ) ) ) {
						// If we have a new update.
						if ( $new_update ) {
							$core_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
						} else {
							// Check ignore ? $ignored_core_to_update.
							$core_to_update_now[]           = $website->id;
							$all_websites[ $website->id ]   = $website;
							$core_to_update[]               = array( $website->id, $info_txt, $info_trusted_text );
						}
						// Nope, we're either set to just email updates for approval or do nothing.
					} else {
						if ( $new_update ) {
							$ignored_core_new_update[] = array( $website->id, $info_new_txt, $info_not_trusted_text );
						} else {
							$ignored_core_to_update[] = array( $website->id, $info_txt, $info_not_trusted_text );
						}
					}
				}

				// Check plugins.
				$website_last_plugins = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_plugin_upgrades' ), true );
				$website_plugins = json_decode( $website->plugin_upgrades, true );

				// Check themes.
				$website_last_themes = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_theme_upgrades' ), true );
				$website_themes = json_decode( $website->theme_upgrades, true );
				$decoded_premium_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'premium_upgrades' ), true );

				// Add premium upgrades to the list of themes and plugins.
				if ( is_array( $decoded_premium_upgrades ) ) {
					foreach ( $decoded_premium_upgrades as $slug => $premium_upgrade ) {
						if ( 'plugin' === $premium_upgrade['type'] ) {
							if ( ! is_array( $website_plugins ) ) {
								$website_plugins = array();
							}
							$website_plugins[ $slug ] = $premium_upgrade;
						} else if ( 'theme' === $premium_upgrade['type'] ) {
							if ( ! is_array( $website_themes ) ) {
								$website_themes = array();
							}
							$website_themes[ $slug ] = $premium_upgrade;
						}
					}
				}

				// Run over every update we had last time.
				foreach ( $website_plugins as $plugin_slug => $plugin_info ) {
					$info_txt     = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $plugin_info['Name'] . ' ' . $plugin_info['Version'] . ' to ' . $plugin_info['update']['new_version'];
					$info_new_txt = '*NEW* <a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $plugin_info['Name'] . ' ' . $plugin_info['Version'] . ' to ' . $plugin_info['update']['new_version'];

					$new_update = ! ( isset( $website_last_plugins[ $plugin_slug ] ) && ($plugin_info['Version'] == $website_last_plugins[ $plugin_slug ]['Version'] ) && ( $plugin_info['update']['new_version'] == $website_last_plugins[ $plugin_slug ]['update']['new_version'] ) );

					// Update this.
					if ( in_array( $plugin_slug, $trusted_plugins ) && ! isset( $decoded_ignored_plugins[ $plugin_slug ] ) && ! isset( $website_decoded_ignored_plugins[ $plugin_slug ] ) ) {
						// Trusted.
						if ( $new_update ) {
							$plugins_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
						} else {
							$plugins_to_update_now[ $website->id ][] = $plugin_slug;
							$all_websites[ $website->id ]           = $website;
							$plugins_to_update[]                    = array( $website->id, $info_txt, $info_trusted_text );
						}
					} else {
						// Not trusted.
						if ( $new_update ) {
							$ignored_plugins_new_update[] = array( $website->id, $info_new_txt, $info_not_trusted_text );
						} else {
							$ignored_plugins_to_update[] = array( $website->id, $info_txt, $info_not_trusted_text );
						}
					}
				}

				// Run over every update we had last time.
				foreach ( $website_themes as $theme_slug => $theme_info ) {
					$info_txt     = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $theme_info['Name'] . ' ' . $theme_info['Version'] . ' to ' . $theme_info['update']['new_version'];
					$info_new_txt = '*NEW* <a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $theme_info['Name'] . ' ' . $theme_info['Version'] . ' to ' . $theme_info['update']['new_version'];

					$new_update = ! ( isset( $website_last_themes[ $theme_slug ] ) && ( $theme_info['Version'] == $website_last_themes[ $theme_slug ]['Version']) && ($theme_info['update']['new_version'] == $website_last_themes[ $theme_slug ]['update']['new_version']));

					// Update this.
					if ( in_array( $theme_slug, $trusted_themes ) && ! isset( $decoded_ignored_themes[ $theme_slug ] ) && ! isset( $website_decoded_ignored_themes[ $theme_slug ] ) ) {
						// Trusted.
						if ( $new_update ) {
							$themes_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
						} else {
							$themes_to_update_now[ $website->id ][] = $theme_slug;
							$all_websites[ $website->id ]           = $website;
							$themes_to_update[]                     = array( $website->id, $info_txt, $info_trusted_text );
						}
					} else {
						// Not trusted.
						if ( $new_update ) {
							$ignored_themes_new_update[] = array( $website->id, $info_new_txt, $info_not_trusted_text );
						} else {
							$ignored_themes_to_update[] = array( $website->id, $info_txt, $info_not_trusted_text );
						}
					}
				}

				// Show plugin conflicts.
				$site_plugin_conflicts = json_decode( $website->pluginConflicts, true );
				if ( count( $site_plugin_conflicts ) > 0 ) {
					$info_txt            = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ';
					$plugin_conflicts   .= '<li>' . $info_txt;
					$added               = false;

					foreach ( $site_plugin_conflicts as $site_plugin_conflict ) {
						if ( $added ) {
							$plugin_conflicts .= ', ';
						}

						$plugin_conflicts .= $site_plugin_conflict;
						$added = true;
					}

					$plugin_conflicts .= '</li>' . "\n";
				}

				// Show theme conflicts.
				$site_theme_conflicts = json_decode( $website->themeConflicts, true );
				if ( count( $site_theme_conflicts ) > 0 ) {
					$info_txt         = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ';
					$theme_conflicts .= '<li>' . $info_txt;
					$added            = false;

					foreach ( $site_theme_conflicts as $site_theme_conflict ) {
						if ( $added ) {
							$theme_conflicts .= ', ';
						}

						$theme_conflicts .= $site_theme_conflict;
						$added = true;
					}
					$theme_conflicts .= '</li>' . "\n";
				}

				// Loop over last plugins & current plugins, check if we need to upgrade them.
				$user  = get_userdata( $website->userid );
				$email = MainWPUtility::getNotificationEmail( $user );

				MainWPUtility::update_option( 'mainwp_updatescheck_mail_email', $email );

				MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, array( 'dtsAutomaticSync' => 0 ) );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_wp_upgrades', json_encode( $website_core_upgrades ) );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_plugin_upgrades', $website->plugin_upgrades );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_theme_upgrades', $website->theme_upgrades );
			}

			MainWPLogger::Instance()->info( 'CRON :: Exiting loop on current group.' );

			if ( ( 0 == count( $core_to_update ) ) && ( 0 == count( $plugins_to_update ) ) && ( 0 == count( $themes_to_update ) ) && ( 0 == count( $ignored_core_to_update ) )  && ( 0 == count( $ignored_core_new_update ) ) && ( 0 == count( $ignored_plugins_to_update ) ) && ( 0 == count( $ignored_plugins_new_update ) ) && ( 0 == count( $ignored_themes_to_update ) ) && ( 0 == count( $ignored_themes_new_update ) ) && ( '' == $plugin_conflicts ) && ( '' == $theme_conflicts ) ) {
				MainWPLogger::Instance()->info( 'CRON :: We have no updates.' );
				return;
			}

			MainWPLogger::Instance()->info( 'CRON :: We have updates.' );

			if ( in_array( $action, array( 0, 1 ) ) ) {
				MainWPLogger::Instance()->info( 'CRON :: We are not updating.' );
				return;
			}

			// Check if backups are required!
			if ( 1 == get_option( 'mainwp_backup_before_upgrade' ) ) {

				MainWPLogger::Instance()->info( 'CRON :: Backups are required.' );

				$sites_check_completed = get_option( "mainwp_automaticUpdate_backup_checks_$group_id" );

				if ( ! is_array( $sites_check_completed ) ) {
					$sites_check_completed = array();
				}

				$websitesToCheck = array();

				foreach ( $plugins_to_update_now as $websiteId => $slugs ) {
					$websitesToCheck[ $websiteId ] = true;
				}

				foreach ( $themes_to_update_now as $websiteId => $slugs ) {
					$websitesToCheck[ $websiteId ] = true;
				}

				foreach ( $core_to_update_now as $websiteId ) {
					$websitesToCheck[ $websiteId ] = true;
				}

				foreach ( $websitesToCheck as $siteId => $bool ) {

					if ( $all_websites[ $siteId ]->backup_before_upgrade == 0 ) {
						$sites_check_completed[ $siteId ] = true;
					}

					if ( isset( $sites_check_completed[ $siteId ] ) ) {
						continue;
					}

					$dir = MainWPUtility::getMainWPSpecificDir( $siteId );

					// Check if backup ok.
					$lastBackup = -1;
					if ( file_exists( $dir ) && ( $dh = opendir( $dir ) ) ) {
						while ( ( $file = readdir( $dh ) ) !== false ) {
							if ( '.' != $file && '..' != $file ) {
								$theFile = $dir . $file;
								if ( MainWPUtility::isArchive( $file ) && ! MainWPUtility::isSQLArchive( $file ) && ( filemtime( $theFile ) > $lastBackup ) ) {
									$lastBackup = filemtime( $theFile );
								}
							}
						}
						closedir( $dh );
					}

					$backupRequired = ( $lastBackup < ( time() - ( 7 * 24 * 60 * 60 ) ) ? true : false );

					if ( ! $backupRequired ) {
						$sites_check_completed[ $siteId ] = true;
						MainWPUtility::update_option( "mainwp_automaticUpdate_backup_checks_$group_id", $sites_check_completed );
						continue;
					}

					try {
						$result = MainWPManageSites::backup( $siteId, 'full', '', '', 0, 0, 0, 0 );
						MainWPManageSites::backupDownloadFile( $siteId, 'full', $result['url'], $result['local'] );
						$sites_check_completed[ $siteId ] = true;
						MainWPUtility::update_option( "mainwp_automaticUpdate_backup_checks_$group_id", $sites_check_completed );
					} catch ( Exception $e ) {
						$sites_check_completed[ $siteId ] = false;
						MainWPUtility::update_option( "mainwp_automaticUpdate_backup_checks_$group_id", $sites_check_completed );
					}
				}
			} else {
				$sites_check_completed = null;
			}

			// Update plugins.
			foreach ( $plugins_to_update_now as $websiteId => $slugs ) {

				// Skip if site check is not completed.
				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					continue;
				}

				MainWPLogger::Instance()->info( 'CRON :: Looping for plugin updates.' );

				// Let's do it!
				try {

					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgradeplugintheme', array(
						'type' => 'plugin',
						'list' => urldecode( implode( ',', $slugs ) ),
					) );

					if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) { MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] ); }
				} catch ( Exception $e ) {
				}
			}

			// Update themes
			foreach ( $themes_to_update_now as $websiteId => $slugs ) {

				// Skip if site check is not completed.
				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					continue;
				}

				// Let's do it!
				try {

					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ] , 'upgradeplugintheme', array(
						'type' => 'theme',
						'list' => urldecode( implode( ',', $slugs ) ),
					) );

					if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) {
						MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] );
					}
				} catch ( Exception $e ) {
				}
			}

			// Update core.
			foreach ( $core_to_update_now as $websiteId ) {

				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					continue;
				}

				// Let's do it!
				try {
					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgrade' );
				} catch ( Exception $e ) {
				}
			}

			BAUFM_Schedules::set_group_last_scheduled_update( $group_id, time() );

	        do_action( 'baufm_cronupdatecheck_action', $plugins_new_update, $plugins_to_update, $plugins_to_update_now, $themes_new_update, $themes_to_update, $themes_to_update_now, $core_new_update, $core_to_update, $core_to_update_now );
	}

	/**
	 * @since 0.2.0
	 */
	public function print_updates_array_lines( $array, $backup_checks ) {
		$output = '';

		foreach ( $array as $line ) {
			$siteId       = $line[0];
			$text         = $line[1];
			$trusted_text = $line[2];

			$output .= '<li>';
			$output	.= $text;
			$output .= $trusted_text;

			if ( ! ( null === $backup_checks || ! isset( $backup_checks[ $siteId ] ) || ( true === $backup_checks[ $siteId ] ) ) ) {
				$ouput .= esc_html__( '(Requires manual backup)', 'baufm' );
			}

			$output .= "</li>\n";
		}

		return $output;
	}

	/**
	 * @since 0.2.0
	 */
	public function get_group_by_site_id( $site_id ) {
		global $wpdb;

		if ( ! is_numeric( $site_id ) ) {
			return;
		}

		$group_ids_obj = $wpdb->get_results( 'SELECT groupid FROM ' . $wpdb->prefix . 'mainwp_wp_group WHERE wpid = ' . $site_id );
		$group_ids 		 = array();

		foreach ( $group_ids_obj as $group ) {
			$group_ids[] = $group->groupid;
		}

		return $group_ids;
	}

	/**
	 * @since 0.2.0
	 */
	public function parse_init() {
		// Reuse old value 'cronUpdatesCheck' for compatibilty with our override.
		if ( isset( $_GET['do'] ) && 'cronUpdatesCheck' == $_GET['do'] ) {
			$this->check_for_updates();
		}
	}
}
