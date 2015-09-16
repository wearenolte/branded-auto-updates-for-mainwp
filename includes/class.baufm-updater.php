<?php
/**
 * The file that handles weekly auto-updates
 *
 * @since 0.2.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( session_id() == '' ) { session_start(); }
ini_set( 'display_errors', true );
error_reporting( E_ALL | E_STRICT );

/**
 * Class for handling automatic weekly batch updates.
 */
class BAUFM_Updater {
	// Singleton.
	private static $instance = null;

	/**
	 * Return the instance of the current object of this class.
	 *
	 * @static
	 * @return BAUFM_Updater
	 */
	static function _instance() {
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Make our instance available on static calls.
		BAUFM_Updater::$instance = $this;

		// Use the cron schedules added by MainWP.
		add_filter( 'cron_schedules', array( 'MainWPUtility', 'getCronSchedules' ) );

		// Check if MainWP is set to use WP Cron.
		$use_wp_cron = get_option( 'mainwp_wp_cron' );

		do_action( 'mainwp_cronload_action' );

		// Call back action for use with WP scheduled events that runs every minute.
		add_action( 'baufm_updater_cron_updates_check_action', array( $this, 'baufm_updater_cron_updates_check_action' ) );

		add_action( 'init', array( $this, 'parse_init' ) );

		// If we do not have anything scheduled.
		if ( false == ( $sched = wp_next_scheduled( 'baufm_updater_cron_updates_check_action' ) ) ) {

			// If we have nothing scheuled, and we are allowed to use WP cron, then schedule our task minutely.
			if ( $use_wp_cron ) {
				wp_schedule_event( time(), 'minutely', 'baufm_updater_cron_updates_check_action' );
			}

			// If we have something scheduled.
		} else {

			// If we have something scheduled and we are not allowed to user WP cron, then un-schedule it.
			if ( ! $use_wp_cron ) {
				wp_unschedule_event( $sched, 'baufm_updater_cron_updates_check_action' );
			}
		}
	}

	/**
	 *
	 */
	public function get_scheduled_day_of_week( $group_id ) {
		$schedule_in_week = (int) get_option( "baufm_schedule_in_week_group_$group_id", 0 );

		$days = array(
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
		);

		if ( $schedule_in_week >= 2 ) {
			$day = $schedule_in_week - 2;
			return $days[ $day ];
		}

		if ( 1 === $schedule_in_week ) {
			return date_i18n( 'w' );
		}

		return 0;
	}

	/**
	 *
	 */
	public function set_scheduled_day_of_week( $group_id, $timestamp ) {
		update_option( "baufm_schedule_in_week_group_$group_id", $timestamp );
	}

	/**
	 *
	 */
	public function get_scheduled_time_of_day( $group_id ) {
		return get_option( "baufm_schedule_in_day_group_$group_id", 0 );
	}

	/**
	 *
	 */
	public function get_last_scheduled_update_for_group( $group_id ) {
		return get_option( "baufm_last_scheduled_update_$group_id" );
	}

	/**
	 *
	 */
	public function set_last_scheduled_update_for_group( $group_id, $timestamp ) {
		update_option( "baufm_last_scheduled_update_$group_id", $timestamp );
	}

	/**
	 *
	 */
	public function baufm_updater_cron_updates_check_action() {

		MainWPLogger::Instance()->info( 'CRON :: updates check' );

		ignore_user_abort( true );
		set_time_limit( 0 );
		$mem = '512M';
		ini_set( 'memory_limit', $mem );
		ini_set( 'max_execution_time', 0 );

		// Used in MainWPServerInformation.page.php for display purposes only.
		MainWPUtility::update_option( 'mainwp_cron_last_updatescheck', time() );

		// We will only deal with batch, or grouped updates FOR NOW.
		$site_groups = MainWPDB::Instance()->getNotEmptyGroups();

		MainWPLogger::Instance()->info( 'CRON :: found ' . count( $site_groups ) . ' site groups' );

		if ( 0 == count( $site_groups ) ) {
			MainWPLogger::Instance()->info( 'CRON :: no site group found, exit now' );
			return;
		}

		// Loop through each site group and check if there is an update to do.
		foreach ( $site_groups as $group ) {
			MainWPLogger::Instance()->info( 'CRON :: get_scheduled_day_of_week ' .  $this->get_scheduled_day_of_week( $group->id ) . ' and now is ' . date_i18n( 'w' ) );
			if ( $this->get_scheduled_day_of_week( $group->id ) === date_i18n( 'w' ) ) {
				if ( (int) date_i18n( 'G' ) >= (int) $this->get_scheduled_time_of_day( $group->id ) ) {
					if ( date_i18n( 'd/m/Y', $this->get_last_scheduled_update_for_group( $group->id ) ) === date_i18n( 'd/m/Y' ) ) {
						// No action to take. Already updated.
						MainWPLogger::Instance()->info( 'CRON :: updates check :: already updated today' );
						continue;
					} else {
						// We should proceed with the update.
						$current_group = $group;
						MainWPLogger::Instance()->info( 'CRON :: ' .  $current_group->name . ' is scheduled now at ' . time() );
						break;
					}
				}
			}
		}

		// We don't have a group. No batch update to make.
		if ( ! isset( $current_group ) ) {
			MainWPLogger::Instance()->info( 'CRON :: found no group currently scheduled this ' . date_i18n( 'l' ) . ' ' . date_i18n( 'G' ) );
			return;
		}

		// The group ID of the current group.
		$group_id = $current_group->id;
		MainWPLogger::Instance()->info( 'CRON :: current group ID is ' . $group_id );

		// Action to take when an update is available: None, email updates, or update sites.
		$do_what_for_group = get_option( "baufm_scheduled_action_group_$group_id" );
		MainWPLogger::Instance()->info( 'CRON :: action to take for current group is ' . $do_what_for_group );

		// All updates.
		$websites = MainWPDB::Instance()->getWebsitesCheckUpdates( 4 );

		MainWPLogger::Instance()->info( 'CRON :: updates check found ' . count( $websites ) . ' website(s)' );

		// Surely, we have a list right.
		if ( 0 === count( $websites ) ) {
			MainWPLogger::Instance()->info( 'CRON :: we have no updates, exit' );
			return;
		}

		// We will only deal with updates available for a particular group.
		$updates_for_current_group = array();
		foreach ( $websites as $website ) {

			// Get all the groups to which this particular website belongs to.
			$website_in_groups = $this->get_group_by_site_id( $website->id );

			// We note that a site can belong to 0, 1, or more groups.
			if ( in_array( $group_id, $website_in_groups ) ) {
				MainWPLogger::Instance()->info( 'CRON :: site with ID ' . $website->id . ' belongs to ' . $current_group->name . ', continue' );
				$updates_for_current_group[] = $website;
			} else {
				MainWPLogger::Instance()->info( 'CRON :: site with ID ' . $website->id . ' does not belong to ' . $current_group->name . ', exit' );
			}
		}

		// Clear variables to free them for later use.
		unset( $websites, $website, $website_in_groups );

		// No updates for the current group.
		if ( 0 === count( $updates_for_current_group ) ) {
			MainWPLogger::Instance()->info( 'CRON :: updates check :: no updates for current group' . $group_id );
			return;
		}

		/*
         * Each site has an associated user_id, which is points to info about trusted/ignored/conflicting updates.
         * Use the first user_id we encounter.
         */
		$user_id = null;
		foreach ( $updates_for_current_group as $website ) {
			$website_values = array(
				'dtsAutomaticSyncStart' => time(),
			);

			if ( null === $user_id ) {
				MainWPLogger::Instance()->info( 'CRON :: ' . $website->userid .  ' as user id' );
				$user_id = $website->userid;
			}

			MainWPLogger::Instance()->info( 'CRON :: updating website sync values for website id ' . $website->id );
			MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );
		}

		// We'll use this variable again, so clear it.
		unset( $website, $website_values );

		// If we have no updates for the current group.
		if ( 0 === count( $updates_for_current_group ) ) {
			MainWPLogger::Instance()->info( 'CRON :: no updates for current group ' );

			$busy_counter = MainWPDB::Instance()->getWebsitesCountWhereDtsAutomaticSyncSmallerThenStart();
			MainWPLogger::Instance()->info( 'CRON :: busy counter is ' . $busy_counter );

			if ( 0 === $busy_counter ) {
				MainWPLogger::Instance()->info( 'CRON :: updates check :: got to the mail part' );

				MainWPUtility::update_option( "mainwp_automaticUpdate_backupChecks_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_core_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_plugins_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_themes_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_core_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_plugins_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_themes_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_core_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_plugins_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_themes_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_core_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_plugins_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_themes_new_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_pluginconflicts_$group_id", '' );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_themeconflicts_$group_id", '' );

				MainWPLogger::Instance()->info( 'CRON :: setting last scheduled update for ' . $group_id . ' as ' . date( 'd/m/Y' ) );
				$this->set_last_scheduled_update_for_group( $group_id, time() ); // date( 'd/m/Y' )
			}
		} else {

			MainWPLogger::Instance()->info( 'CRON :: got to the updates part' );

			/**
			 * In MainWP, the mainwp_users table lists the trusted/ignored/dismissed
			 * plugins/themes/conflicts FOR EACH user. So we get them them here for the
			 * current user.
			 */
			$user_extension = MainWPDB::Instance()->getUserExtensionByUserId( $user_id );

			// Get the ignored plugins.
			$decoded_ignored_plugins = json_decode( $user_extension->ignored_plugins, true );
			if ( ! is_array( $decoded_ignored_plugins ) ) {
				$decoded_ignored_plugins = array();
			}
			MainWPLogger::Instance()->info( 'CRON :: ignored plugins are ' .  var_export( $decoded_ignored_plugins ) );

			// Get the trusted plugins.
			$trusted_plugins = json_decode( $user_extension->trusted_plugins, true );
			if ( ! is_array( $trusted_plugins ) ) {
				$trusted_plugins = array();
			}
			MainWPLogger::Instance()->info( 'CRON :: ' . count( $trusted_plugins ) . 'trusted plugins are ' .  var_export( $trusted_plugins ) );

			// Get the ignored themes.
			$decoded_ignored_themes = json_decode( $user_extension->ignored_themes, true );
			if ( ! is_array( $decoded_ignored_themes ) ) {
				$decoded_ignored_themes = array();
			}
			MainWPLogger::Instance()->info( 'CRON :: ignored themes are ' .  var_export( $decoded_ignored_themes ) );

			// Get the trusted themes.
			$trusted_themes = json_decode( $user_extension->trusted_themes, true );
			if ( ! is_array( $trusted_themes ) ) {
				$trusted_themes = array();
			}
			MainWPLogger::Instance()->info( 'CRON :: trusted themes are ' .  var_export( $trusted_themes ) );

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

			// Go over each website in our current group.
			foreach ( $updates_for_current_group as $website ) {
				MainWPLogger::Instance()->info( 'CRON :: looping through each website on the current group ' . $group_id );

				// Get the ignored plugins.
				$website_decoded_ignored_plugins = json_decode( $website->ignored_plugins, true );
				if ( ! is_array( $website_decoded_ignored_plugins ) ) {
					$website_decoded_ignored_plugins = array();
				}
				MainWPLogger::Instance()->info( 'CRON :: ignored plugins for site ' . $website->id . ' ' . var_export( $website_decoded_ignored_plugins ) );

				// Get the ignored themes.
				$website_decoded_ignored_themes = json_decode( $website->ignored_themes, true );
				if ( ! is_array( $website_decoded_ignored_themes ) ) {
					$website_decoded_ignored_themes = array();
				}
				MainWPLogger::Instance()->info( 'CRON :: ignored themes for site ' . $website->id . ' ' . var_export( $website_decoded_ignored_themes ) );

				// Perform check and update.
				if ( ! MainWPSync::syncSite( $website, false, true ) ) {
					$website_values = array(
						'dtsAutomaticSync' => time(),
					);

					MainWPLogger::Instance()->info( 'CRON :: sync site ' . $website->id );
					MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );

					continue;
				}

				$website = MainWPDB::Instance()->getWebsiteById( $website->id );
				MainWPLogger::Instance()->info( 'CRON :: checking info for ' . $website->id );

				// Check core upgrades.
				$website_last_core_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_wp_upgrades' ), true );
				MainWPLogger::Instance()->info( 'CRON :: last wp upgrade for ' . $website->id  . ' is ' . var_export( $website_last_core_upgrades ) );

				$website_core_upgrades      = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'wp_upgrades' ), true );
				MainWPLogger::Instance()->info( 'CRON :: wp upgrades for ' . $website->id  . ' is ' . var_export( $website_core_upgrades ) );

				// Run over every update we had last time.
				if ( isset( $website_core_upgrades['current'] ) ) {
					MainWPLogger::Instance()->info( 'CRON :: run over every update we had last time' );

					$info_txt     = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
					$info_new_txt = '*NEW* <a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
					$new_update   = ! ( isset( $website_last_core_upgrades['current'] ) && ( $website_last_core_upgrades['current'] == $website_core_upgrades['current'] ) && ( $website_last_core_upgrades['new'] == $website_core_upgrades['new'] ) );
					MainWPLogger::Instance()->info( 'CRON :: we have a new update :: ' . ( $new_update ) ? 'yes' : 'no' );

					$do_what_for_group = apply_filters( 'baufm_do_what_for_group', $do_what_for_group, $website->id, $group_id );
					MainWPLogger::Instance()->info( 'CRON :: do what for group ' . $group_id . ' ::: ' . $do_what_for_group );

					// If we are OK to install trusted updates.
					if ( in_array( $do_what_for_group, array( 2, 3 ) ) ) {
						MainWPLogger::Instance()->info( 'CRON :: we are ok to install trusted updates' );

						// If we have a new update.
						if ( $new_update ) {
							$core_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
							MainWPLogger::Instance()->info( 'CRON :: we have new update for core ' . var_export( $core_new_update ) );

						} else {
							// Check ignore ? $ignored_core_to_update.
							$core_to_update_now[]           = $website->id;
							$all_websites[ $website->id ]   = $website;
							$core_to_update[]               = array( $website->id, $info_txt, $info_trusted_text );

							MainWPLogger::Instance()->info( 'CRON :: core to update now ' . var_export( $core_to_update_now ) );
							MainWPLogger::Instance()->info( 'CRON :: all websites ' . var_export( $all_websites ) );
							MainWPLogger::Instance()->info( 'CRON :: core to update ' . var_export( $core_to_update ) );
						}

						// Nope, we're either set to just email updates for approval or do nothing.
					} else {
						MainWPLogger::Instance()->info( 'CRON :: we will not install trusted updates' );

						if ( $new_update ) {
							$ignored_core_new_update[] = array( $website->id, $info_new_txt, $info_not_trusted_text );
							MainWPLogger::Instance()->info( 'CRON :: new update, but ignored core new update ', var_export( $ignored_core_new_update ) );
						} else {
							$ignored_core_to_update[] = array( $website->id, $info_txt, $info_not_trusted_text );
							MainWPLogger::Instance()->info( 'CRON :: no new update, ignored core to update for ' . $website->id . ' ' . var_export( $ignored_core_to_update ) );
						}
					}
				}

				// Check plugins.
				$website_last_plugins = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_plugin_upgrades' ), true );
				MainWPLogger::Instance()->info( 'CRON :: website last plugin upgrades for ' . $website->id . ' ' . var_export( $website_last_plugins ) );

				$website_plugins = json_decode( $website->plugin_upgrades, true );
				MainWPLogger::Instance()->info( 'CRON :: website plugin upgrade for ' . $website->id . ' ' . var_export( $website_plugins ) );

				// Check themes.
				$website_last_themes = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_theme_upgrades' ), true );
				MainWPLogger::Instance()->info( 'CRON :: website last theme upgrades for' . $website->id . ' ' . var_export( $website_last_themes ) );

				$website_themes = json_decode( $website->theme_upgrades, true );
				MainWPLogger::Instance()->info( 'CRON :: website theme upgrades for' . $website->id . ' ' . var_export( $website_themes ) );

				$decoded_premium_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'premium_upgrades' ), true );
				MainWPLogger::Instance()->info( 'CRON :: website premium upgrades for ' . $website->id . ' ' . var_export( $decoded_premium_upgrades ) );

				// Add premium upgrades to the list of themes and plugins.
				if ( is_array( $decoded_premium_upgrades ) ) {
					MainWPLogger::Instance()->info( 'CRON :: before we start looping on premium upgrades' );

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

				MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, array( 'dtsAutomaticSync' => time() ) );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_wp_upgrades', json_encode( $website_core_upgrades ) );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_plugin_upgrades', $website->plugin_upgrades );
				MainWPDB::Instance()->updateWebsiteOption( $website, 'last_theme_upgrades', $website->theme_upgrades );
			}

			if ( count( $core_new_update ) != 0 ) {
				$core_new_update_saved = get_option( "mainwp_updatescheck_mail_update_core_new_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_core_new_$group_id", MainWPUtility::array_merge( $core_new_update_saved, $core_new_update ) );
			}

			if ( count( $plugins_new_update ) != 0 ) {
				$plugins_new_update_saved = get_option( "mainwp_updatescheck_mail_update_plugins_new_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_plugins_new_$group_id", MainWPUtility::array_merge( $plugins_new_update_saved, $plugins_new_update ) );
			}

			if ( count( $themes_new_update ) != 0 ) {
				$themes_new_update_saved = get_option( "mainwp_updatescheck_mail_update_themes_new_$group_id" );
				MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_themes_new_$group_id', MainWPUtility::array_merge( $themes_new_update_saved, $themes_new_update ) );
			}

			if ( count( $core_to_update ) != 0 ) {
				$core_to_update_saved = get_option( "mainwp_updatescheck_mail_update_core_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_core_$group_id", MainWPUtility::array_merge( $core_to_update_saved, $core_to_update ) );
			}

			if ( count( $plugins_to_update ) != 0 ) {
				$plugins_to_update_saved = get_option( "mainwp_updatescheck_mail_update_plugins_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_plugins_$group_id", MainWPUtility::array_merge( $plugins_to_update_saved, $plugins_to_update ) );
			}

			if ( count( $themes_to_update ) != 0 ) {
				$themes_to_update_saved = get_option( "mainwp_updatescheck_mail_update_themes_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_update_themes_$group_id", MainWPUtility::array_merge( $themes_to_update_saved, $themes_to_update ) );
			}

			if ( count( $ignored_core_to_update ) != 0 ) {
				$ignored_core_to_update_saved = get_option( "mainwp_updatescheck_mail_ignore_core_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_core_$group_id", MainWPUtility::array_merge( $ignored_core_to_update_saved, $ignored_core_to_update ) );
			}

			if ( count( $ignored_core_new_update ) != 0 ) {
				$ignored_core_new_update_saved = get_option( "mainwp_updatescheck_mail_ignore_core_new_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_core_new_$group_id", MainWPUtility::array_merge( $ignored_core_new_update_saved, $ignored_core_new_update ) );
			}

			if ( count( $ignored_plugins_to_update ) != 0 ) {
				$ignored_plugins_to_update_saved = get_option( "mainwp_updatescheck_mail_ignore_plugins_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_plugins_$group_id", MainWPUtility::array_merge( $ignored_plugins_to_update_saved, $ignored_plugins_to_update ) );
			}

			if ( count( $ignored_plugins_new_update ) != 0 ) {
				$ignored_plugins_new_update_saved = get_option( "mainwp_updatescheck_mail_ignore_plugins_new_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_plugins_new_$group_id", MainWPUtility::array_merge( $ignored_plugins_new_update_saved, $ignored_plugins_new_update ) );
			}

			if ( count( $ignored_themes_to_update ) != 0 ) {
				$ignored_themes_to_update_saved = get_option( "mainwp_updatescheck_mail_ignore_themes_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_themes_$group_id", MainWPUtility::array_merge( $ignored_themes_to_update_saved, $ignored_themes_to_update ) );
			}

			if ( count( $ignored_themes_new_update ) != 0 ) {
				$ignored_themes_new_updateSaved = get_option( "mainwp_updatescheck_mail_ignore_themes_new_$group_id" );
				MainWPUtility::update_option( "mainwp_updatescheck_mail_ignore_themes_new_$group_id", MainWPUtility::array_merge( $ignored_themes_new_updateSaved, $ignored_themes_new_update ) );
			}

			if ( '' != $plugin_conflicts ) {
				$plugin_conflicts_saved = get_option( "mainwp_updatescheck_mail_pluginconflicts_$group_id" );

				if ( false == $plugin_conflicts_saved ) {
					$plugin_conflicts_saved = '';
				}

				MainWPUtility::update_option( "mainwp_updatescheck_mail_pluginconflicts_$group_id", $plugin_conflicts_saved . $plugin_conflicts );
			}

			if ( '' != $theme_conflicts ) {
				$theme_conflicts_saved = get_option( "mainwp_updatescheck_mail_themeconflicts_$group_id" );

				if ( false == $theme_conflicts_saved ) {
					$theme_conflicts_saved = '';
				}

				MainWPUtility::update_option( 'mainwp_updatescheck_mail_themeconflicts_$group_id', $theme_conflicts_saved . $theme_conflicts );
			}

			if ( ( 0 == count( $core_to_update ) ) && ( 0 == count( $plugins_to_update ) ) && ( 0 == count( $themes_to_update ) ) && ( 0 == count( $ignored_core_to_update ) )  && ( 0 == count( $ignored_core_new_update ) ) && ( 0 == count( $ignored_plugins_to_update ) ) && ( 0 == count( $ignored_plugins_new_update ) ) && ( 0 == count( $ignored_themes_to_update ) ) && ( 0 == count( $ignored_themes_new_update ) ) && ( '' == $plugin_conflicts ) && ( '' == $theme_conflicts ) ) {
				return;
			}

			if ( in_array( $do_what_for_group, array( 0, 1 ) ) ) {
				MainWPLogger::Instance()->info( 'CRON :: we are not installing updates, exit' );
				return;
			}

			MainWPLogger::Instance()->info( 'CRON :: looks like we will be installing updates' );

			// Check if backups are required!
			if ( 1 == get_option( 'mainwp_backup_before_upgrade' ) ) {

				MainWPLogger::Instance()->info( 'CRON :: checking backups before upgrade' );

				$sites_check_completed = get_option( "mainwp_automaticUpdate_backupChecks_$group_id" );

				if ( ! is_array( $sites_check_completed ) ) {
					MainWPLogger::Instance()->info( 'CRON :: site check not completed' );
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
						MainWPUtility::update_option( "mainwp_automaticUpdate_backupChecks_$group_id", $sites_check_completed );
						continue;
					}

					try {
						$result = MainWPManageSites::backup( $siteId, 'full', '', '', 0, 0, 0, 0 );
						MainWPManageSites::backupDownloadFile( $siteId, 'full', $result['url'], $result['local'] );
						$sites_check_completed[ $siteId ] = true;
						MainWPUtility::update_option( "mainwp_automaticUpdate_backupChecks_$group_id", $sites_check_completed );
					} catch ( Exception $e ) {
						$sites_check_completed[ $siteId ] = false;
						MainWPUtility::update_option( "mainwp_automaticUpdate_backupChecks_$group_id", $sites_check_completed );
					}
				}
			} else {
				MainWPLogger::Instance()->info( 'CRON :: no backups checks needed before upgrade for ' . $siteId );
				$sites_check_completed = null;
			}

			// Update plugins.
			foreach ( $plugins_to_update_now as $websiteId => $slugs ) {

				// Skip if site check is not completed.
				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					MainWPLogger::Instance()->info( 'CRON :: skipping plugin updates ' . var_export( $slugs ) . ' for ' . $websiteId );
					continue;
				}

				// Let's do it!
				try {
					MainWPLogger::Instance()->info( 'CRON :: updating plugins ' . var_export( $slugs ) . ' for ' . $websiteId );

					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgradeplugintheme', array(
						'type' => 'plugin',
						'list' => urldecode( implode( ',', $slugs ) ),
					) );

					if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) { MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] ); }
				} catch ( Exception $e ) {
					MainWPLogger::Instance()->info( 'CRON :: failed plugin updates ' . var_export( $slugs ) . ' for ' . $websiteId );
				}
			}

			// Update themes
			foreach ( $themes_to_update_now as $websiteId => $slugs ) {

				// Skip if site check is not completed.
				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					MainWPLogger::Instance()->info( 'CRON :: skipping theme updates ' . var_export( $slugs ) . ' for ' . $websiteId );
					continue;
				}

				// Let's do it!
				try {
					MainWPLogger::Instance()->info( 'CRON :: updating themes ' . var_export( $slugs ) . ' for ' . $websiteId );

					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ] , 'upgradeplugintheme', array(
						'type' => 'theme',
						'list' => urldecode( implode( ',', $slugs ) ),
					) );

					if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) {
						MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] );
					}
				} catch ( Exception $e ) {
					MainWPLogger::Instance()->info( 'CRON :: failed theme updates ' . var_export( $slugs ) . ' for ' . $websiteId );
				}
			}

			// Update core.
			foreach ( $core_to_update_now as $websiteId ) {

				if ( ( null != $sites_check_completed ) && ( false == $sites_check_completed[ $websiteId ] ) ) {
					MainWPLogger::Instance()->info( 'CRON :: skipping core updates for ' . $websiteId );
					continue;
				}

				// Let's do it!
				try {
					MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgrade' );
					MainWPLogger::Instance()->info( 'CRON :: doing core updates for ' . $websiteId );
				} catch ( Exception $e ) {
					MainWPLogger::Instance()->info( 'CRON :: failed core updates for ' . $websiteId );
				}
			}

			$this->set_last_scheduled_update_for_group( $group_id, time() );

	        do_action( 'baufm_cronupdatecheck_action', $plugins_new_update, $plugins_to_update, $plugins_to_update_now, $themes_new_update, $themes_to_update, $themes_to_update_now, $core_new_update, $core_to_update, $core_to_update_now );
		}
	}

	public function print_updates_array_lines( $array, $backupChecks ) {
		$output = '';

		foreach ( $array as $line ) {
			$siteId      = $line[0];
			$text        = $line[1];
			$trustedText = $line[2];

			$output .= '<li>' . $text . $trustedText . ( null == $backupChecks || ! isset( $backupChecks[ $siteId ] ) || ( true == $backupChecks[ $siteId ] ) ? '' : '(Requires manual backup)' ) . '</li>'."\n";
		}

		return $output;
	}

	public function get_group_by_site_id( $site_id ) {
		global $wpdb;

		if ( ! is_numeric( $site_id ) ) {
			return;
		}

		$group_ids_obj = $wpdb->get_results( 'SELECT groupid FROM ' . $wpdb->prefix . 'mainwp_wp_group WHERE wpid = ' . $site_id );
		$group_ids = array();

		foreach ( $group_ids_obj as $group ) {
			$group_ids[] = $group->groupid;
		}

		return $group_ids;
	}

	public function parse_init() {
		if ( isset( $_GET['do'] ) && 'cronUpdatesCheck' == $_GET['do'] ) {
			$this->baufm_updater_cron_updates_check_action();
		}
	}
}
