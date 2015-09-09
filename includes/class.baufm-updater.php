<?php
/**
 * The main plugin file for setup and initialization
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( session_id() == '' ) session_start();
ini_set( 'display_errors', TRUE );
error_reporting( E_ALL | E_STRICT );

class BAUFM_Updater {
    // Singleton
    private static $instance = NULL;
 
    /**
     * @static
     * @return BAUFM_Updater
     */
    static function Instance() {
        return self::$instance;
    }

    public function __construct() {      
 
        BAUFM_Updater::$instance = $this;

        add_filter( 'cron_schedules', array( 'MainWPUtility', 'getCronSchedules' ) );

        $useWPCron = ( get_option( 'mainwp_wp_cron' ) === FALSE) || ( get_option( 'mainwp_wp_cron' ) == 1 );

        do_action( 'mainwp_cronload_action' );

        add_action( 'baufm_updater_cron_updates_check_action', array( $this, 'baufm_updater_cron_updates_check_action' ) );

        if ( ( $sched = wp_next_scheduled( 'baufm_updater_cron_updates_check_action' ) ) == FALSE) {
            if ( $useWPCron ) {
                wp_schedule_event( time(), 'minutely', 'baufm_updater_cron_updates_check_action' );
            }
        } else {
            if ( ! $useWPCron ) wp_unschedule_event( $sched, 'baufm_updater_cron_updates_check_action' );
        }
    }

    public function get_scheduled_day_of_week( $group_id ) {
        $schedule_in_week = (int) get_option( "baufm_schedule_in_week_group_$group_id", 0 );
        
        $days = array(
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        );

        if ( $schedule_in_week > 2 ) {
            $day = $schedule_in_week - 2;
            return $days[ $day ];
        }

        return '';
    }

    public function get_scheduled_time_of_day( $group_id ) {
        $schedule_in_day  = get_option( "baufm_schedule_in_day_group_$group_id" );
        $suffix = ( $schedule_in_day >= 12 ) ? 'PM' : 'AM';
        $time = ( $schedule_in_day <= 12 ) ? $schedule_in_day : $schedule_in_day - 12;
        $time   = ( $time < 10 ) ? '0' . $time : $time; 
        $time = $time . ':00 ' . $suffix . ' ' . date_i18n( 'e' );

        return $time;
    }

    public function get_last_scheduled_update_for_group( $group_id ) {
        return get_option( "baufm_last_scheduled_update_$group_id" );
    }

    public function set_last_scheduled_update_for_group( $group_id, $timestamp ) {
        update_option( "baufm_last_scheduled_update_$group_id", $timestamp );
    }

    public function baufm_updater_cron_updates_check_action() {

        MainWPLogger::Instance()->info( 'CRON :: updates check' );

        @ignore_user_abort( TRUE );
        @set_time_limit( 0 );
        $mem = '512M';
        @ini_set( 'memory_limit', $mem );
        @ini_set( 'max_execution_time', 0 );

        // Used in MainWPServerInformation.page.php for display purposes only.
        MainWPUtility::update_option( 'mainwp_cron_last_updatescheck', time() );
        
        // Last time we performed an update for ALL groups.
        $baufm_last_automatic_update_for_all = get_option( 'mainwp_updatescheck_last' );

        $site_groups = MainWPDB::Instance()->getNotEmptyGroups();

        // We will only deal with batch, or grouped updates.
        if ( empty( $site_groups ) ) {
            return;
        }

        // Loop through each site group and check if there is an update to do.
        foreach ( $site_groups as $group ) {
            if ( $this->get_scheduled_day_of_week( $group->id ) === date_i18n( 'l' ) ) {
                if ( date_i18n( 'G' ) >= $this->get_scheduled_time_of_day( $group->id ) ) {
                    if ( $this->get_last_scheduled_update_for_group( $group->id ) === date_i18n( 'd/m/Y' ) ) {
                        // No action to take. Already updated.
                        MainWPLogger::Instance()->debug( 'CRON :: updates check :: already updated today' );
                        continue;
                    } else {
                        // We should proceed with the update.
                        $current_group = $group;
                        break;
                    }
                }
            }
        }

        // We don't have a group. No batch update to make.
        if ( ! isset( $current_group ) ) {
            return;
        }

        // The group ID of the current group
        $group_id = $current_group->id; 

        // Action to take when an update is available: None, email updates, or update sites.
        $do_what_for_group = get_option( "baufm_automatic_weekly_update_for_group_$group_id" );

        // All updates.
        $websites = MainWPDB::Instance()->getWebsitesCheckUpdates( 4 );
        $updates_for_current_group = array();
        
        MainWPLogger::Instance()->debug( 'CRON :: updates check :: found ' . count( $websites ) . ' websites' );

        // We will only deal with updates available for a particular group.
        foreach ( $websites as $website ) {
            if ( $website->id === $group_id ) {
                $updates_for_current_group[] = $website;
            }
        }

        // Clear variables to free them for later use.
        unset( $websites, $website );

        // No updates for the current group.
        if ( 0 === count( $updates_for_current_group ) ) {
            MainWPLogger::Instance()->debug( 'CRON :: updates check :: no updates for current group' . $group_id );
            return;
        }

        $user_id = NULL;

        foreach ( $updates_for_current_group as $website ) {
            $website_values = array(
                'dtsAutomaticSyncStart' => time()
            );

            if ( NULL === $user_id ) {
                $user_id = $website->userid;
            }

            MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );
        }

        unset( $website );

        if ( 0 === count( $updates_for_current_group ) ) {
            $busyCounter = MainWPDB::Instance()->getWebsitesCountWhereDtsAutomaticSyncSmallerThenStart();

            if ( 0 === $busyCounter ) {
                MainWPLogger::Instance()->debug('CRON :: updates check :: got to the mail part');

                //Send the email & update all to this time!
                $mail = '';
                $sendMail = FALSE;

                $sites_check_completed = null;
                if (get_option('mainwp_backup_before_upgrade') == 1)
                {
                    $sites_check_completed = get_option('mainwp_automaticUpdate_backupChecks');
                    if (!is_array($sites_check_completed)) $sites_check_completed = null;
                }


                $plugins_new_update = get_option('mainwp_updatescheck_mail_update_plugins_new');
                if (!is_array($plugins_new_update)) $plugins_new_update = array();
                $plugins_to_update = get_option('mainwp_updatescheck_mail_update_plugins');
                if (!is_array($plugins_to_update)) $plugins_to_update = array();
                $ignored_plugins_new_update = get_option('mainwp_updatescheck_mail_ignore_plugins_new');
                if (!is_array($ignored_plugins_new_update)) $ignored_plugins_new_update = array();
                $ignored_plugins_to_update = get_option('mainwp_updatescheck_mail_ignore_plugins');
                if (!is_array($ignored_plugins_to_update)) $ignored_plugins_to_update = array();

                if ((count($plugins_new_update) != 0) || (count($plugins_to_update) != 0) || (count($ignored_plugins_new_update) != 0) || (count($ignored_plugins_to_update) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Plugin Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($plugins_new_update, null);
                    $mail .= $this->print_updates_array_lines($plugins_to_update, $sites_check_completed);
                    $mail .= $this->print_updates_array_lines($ignored_plugins_new_update, null);
                    $mail .= $this->print_updates_array_lines($ignored_plugins_to_update, null);
                    $mail .= '</ul>';
                }

                $themes_new_update = get_option('mainwp_updatescheck_mail_update_themes_new');
                if (!is_array($themes_new_update)) $themes_new_update = array();
                $themes_to_update = get_option('mainwp_updatescheck_mail_update_themes');
                if (!is_array($themes_to_update)) $themes_to_update = array();
                $ignored_themes_new_update = get_option('mainwp_updatescheck_mail_ignore_themes_new');
                if (!is_array($ignored_themes_new_update)) $ignored_themes_new_update = array();
                $ignored_themes_to_update = get_option('mainwp_updatescheck_mail_ignore_themes');
                if (!is_array($ignored_themes_to_update)) $ignored_themes_to_update = array();

                if ((count($themes_new_update) != 0) || (count($themes_to_update) != 0) || (count($ignored_themes_new_update) != 0) || (count($ignored_themes_to_update) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Themes Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($themes_new_update, null);
                    $mail .= $this->print_updates_array_lines($themes_to_update, $sites_check_completed);
                    $mail .= $this->print_updates_array_lines($ignored_themes_new_update, null);
                    $mail .= $this->print_updates_array_lines($ignored_themes_to_update, null);
                    $mail .= '</ul>';
                }

                $core_new_update = get_option('mainwp_updatescheck_mail_update_core_new');
                if (!is_array($core_new_update)) $core_new_update = array();
                $core_to_update = get_option('mainwp_updatescheck_mail_update_core');
                if (!is_array($core_to_update)) $core_to_update = array();
                $ignored_core_new_update = get_option('mainwp_updatescheck_mail_ignore_core_new');
                if (!is_array($ignored_core_new_update)) $ignored_core_new_update = array();
                $ignored_core_to_update = get_option('mainwp_updatescheck_mail_ignore_core');
                if (!is_array($ignored_core_to_update)) $ignored_core_to_update = array();

                if ((count($core_new_update) != 0) || (count($core_to_update) != 0) || (count($ignored_core_new_update) != 0) || (count($ignored_core_to_update) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Core Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($core_new_update, null);
                    $mail .= $this->print_updates_array_lines($core_to_update, $sites_check_completed);
                    $mail .= $this->print_updates_array_lines($ignored_core_new_update, null);
                    $mail .= $this->print_updates_array_lines($ignored_core_to_update, null);
                    $mail .= '</ul>';
                }

                $plugin_conflicts = get_option('mainwp_updatescheck_mail_pluginconflicts');
                if ($plugin_conflicts === FALSE) $plugin_conflicts = '';

                if ($plugin_conflicts != '')
                {
                    $sendMail = TRUE;
                    $mail .= '<div><strong>WordPress Plugin Conflicts</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $plugin_conflicts;
                    $mail .= '</ul>';
                }

                $theme_conflicts = get_option('mainwp_updatescheck_mail_themeconflicts');
                if ($theme_conflicts === FALSE) $theme_conflicts = '';

                if ($theme_conflicts != '')
                {
                    $sendMail = TRUE;
                    $mail .= '<div><strong>WordPress Theme Conflicts</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $theme_conflicts;
                    $mail .= '</ul>';
                }

                MainWPUtility::update_option('mainwp_automaticUpdate_backupChecks', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_update_core_new', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_plugins_new', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_themes_new', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_update_core', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_plugins', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_themes', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_core', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_plugins', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_themes', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_core_new', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_plugins_new', '');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_themes_new', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_pluginconflicts', '');

                MainWPUtility::update_option('mainwp_updatescheck_mail_themeconflicts', '');

                MainWPUtility::update_option('mainwp_updatescheck_last', date('d/m/Y'));
                if (!$sendMail)
                {
                    MainWPLogger::Instance()->debug('CRON :: updates check :: sendMail is FALSE');
                    return;
                }

                if ($baufm_automatic_weekly_update !== FALSE && $baufm_automatic_weekly_update != 0)
                {
                    //Create a nice email to send
                    $email = get_option('mainwp_updatescheck_mail_email');
                    MainWPLogger::Instance()->debug('CRON :: updates check :: send mail to ' . $email);
                    if ($email != FALSE && $email != '') {
                        $mail = '<div>We noticed the following updates are available on your MainWP Dashboard. (<a href="'.site_url().'">'.site_url().'</a>)</div>
                                 <div></div>
                                 ' . $mail.'
                                 Update Key: (<strong><span style="color:#008000">Trusted</span></strong>) will be auto updated within 24 hours. (<strong><span style="color:#ff0000">Not Trusted</span></strong>) you will need to log into your Main Dashboard and update
                                 <div> </div>
                                 <div>If your MainWP is configured to use Auto Updates these upgrades will be installed in the next 24 hours. To find out how to enable automatic updates please see the FAQs below.</div>
                                 <div><a href="http://docs.mainwp.com/marking-a-plugin-as-trusted/" style="color:#446200" target="_blank">http://docs.mainwp.com/marking-a-plugin-as-trusted/</a></div>
                                 <div><a href="http://docs.mainwp.com/marking-a-theme-as-trusted/" style="color:#446200" target="_blank">http://docs.mainwp.com/marking-a-theme-as-trusted/</a></div>
                                 <div><a href="http://docs.mainwp.com/marking-a-sites-wp-core-updates-as-trusted/" style="color:#446200" target="_blank">http://docs.mainwp.com/marking-a-sites-wp-core-updates-as-trusted/</a></div>';
                        wp_mail($email, 'MainWP - Trusted Updates', MainWPUtility::formatEmail($email, $mail), array('From: "'.get_option('admin_email').'" <'.get_option('admin_email').'>', 'content-type: text/html'));
                    }
                }
            }
        } else {

            /**
             * In MainWP, the mainwp_users table lists the trusted/ignored/dismissed
             * plugins/themes/conflicts FOR EACH user. So we get them them here for the
             * current user.
             */
            $user_extension = MainWPDB::Instance()->getUserExtensionByUserId( $user_id );

            // Get the ignored plugins.
            $decoded_ignored_plugins = json_decode( $user_extension->ignored_plugins, TRUE );
            if ( ! is_array( $decoded_ignored_plugins ) ) {
                $decoded_ignored_plugins = array();
            }

            // Get the trusted plugins.
            $trusted_plugins = json_decode( $user_extension->trusted_plugins, TRUE);
            if ( ! is_array( $trusted_plugins ) ) {
                $trusted_plugins = array();
            }

            // Get the ignored themes.
            $decoded_ignored_themes = json_decode( $user_extension->ignored_themes, TRUE );
            if ( ! is_array( $decoded_ignored_themes ) ) {
                $decoded_ignored_themes = array();
            }

            // Get the trusted themes.
            $trusted_themes = json_decode( $user_extension->trusted_themes, TRUE );
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

            // Go over each website in our current group.
            foreach ( $updates_for_current_group as $website ) {
                // Get the ignored plugins.
                $website_decoded_ignored_plugins = json_decode( $website->ignored_plugins, TRUE );
                if ( ! is_array( $website_decoded_ignored_plugins ) ) $website_decoded_ignored_plugins = array();

                // Get the ignored themes.
                $website_decoded_ignored_themes = json_decode( $website->ignored_themes, TRUE );
                if ( ! is_array( $website_decoded_ignored_themes ) ) $website_decoded_ignored_themes = array();

                // Perform check and update.
                if ( ! MainWPSync::syncSite( $website, FALSE, TRUE ) ) {
                    $website_values = array(
                        'dtsAutomaticSync' => time()
                    );

                    MainWPDB::Instance()->updateWebsiteSyncValues( $website->id, $website_values );

                    continue;
                }

                $website = MainWPDB::Instance()->getWebsiteById( $website->id );

                // Check core upgrades.
                $website_last_core_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_wp_upgrades' ), TRUE );
                $website_core_upgrades      = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'wp_upgrades' ), TRUE );

                // Run over every update we had last time.
                if ( isset( $website_core_upgrades['current'] ) ) {
                    $info_txt     = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
                    $info_new_txt = '*NEW* <a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ' . $website_core_upgrades['current'] . ' to ' . $website_core_upgrades['new'];
                    $new_update   = ! ( isset( $website_last_core_upgrades['current'] ) && ( $website_last_core_upgrades['current'] == $website_core_upgrades['current'] ) && ( $website_last_core_upgrades['new'] == $website_core_upgrades['new'] ) );
                    
                    $do_what_for_group = apply_filters( 'baufm_do_what_for_group', $do_what_for_group, $website->id, $group_id );

                    // If we are OK to install trusted updates.
                    if ( 1 === $do_what_for_group ) {
                        if ( $new_update ) {
                            $core_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
                        } else {
                            // Check ignore ? $ignored_core_to_update.
                            $core_to_update_now[]           = $website->id;
                            $all_websites[ $website->id ]   = $website;
                            $core_to_update[]               = array($website->id, $info_txt, $info_trusted_text);
                        }
                    // Nope, we're either set to email updates for approval or do nothing.
                    } else {
                        if ( $new_update ) {
                            $ignored_core_new_update[] = array( $website->id, $info_new_txt, $info_not_trusted_text );
                        } else {
                            $ignored_core_to_update[] = array( $website->id, $info_txt, $info_not_trusted_text );
                        }
                    }
                }

                // Check plugins.
                $website_last_plugins = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_plugin_upgrades' ), TRUE );
                $website_plugins      = json_decode( $website->plugin_upgrades, TRUE );

                // Check themes.
                $website_last_themes  = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'last_theme_upgrades' ), TRUE );
                $website_themes       = json_decode( $website->theme_upgrades, TRUE );

                $decoded_premium_upgrades = json_decode( MainWPDB::Instance()->getWebsiteOption( $website, 'premium_upgrades' ), TRUE );
                
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
                    $info_txt     = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $plugin_info['Name'] . ' ' . $plugin_info['Version'] . ' to ' . $plugin_info['update']['new_version'];
                    $info_new_txt = '*NEW* <a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $plugin_info['Name'] . ' ' . $plugin_info['Version'] . ' to ' . $plugin_info['update']['new_version'];

                    $new_update = ! ( isset( $website_last_plugins[ $plugin_slug ] ) && ($plugin_info['Version'] == $website_last_plugins[ $plugin_slug ]['Version'] ) && ( $plugin_info['update']['new_version'] == $website_last_plugins[ $plugin_slug ]['update']['new_version'] ) );
                    
                    // Update this.
                    if ( in_array( $plugin_slug, $trusted_plugins ) && ! isset( $decoded_ignored_plugins[ $plugin_slug ] ) && ! isset( $website_decoded_ignored_plugins[ $plugin_slug ] ) ) {                        
                        // Trusted.
                        if ( $new_update ) {
                            $plugins_new_update[] = array( $website->id, $info_new_txt, $info_trusted_text );
                        } else {
                            $plugins_to_updateNow[ $website->id ][] = $plugin_slug;
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

                    $new_update = ! ( isset( $website_last_themes[ $theme_slug ] ) && ( $theme_info['Version'] == $website_last_themes[$theme_slug]['Version']) && ($theme_info['update']['new_version'] == $website_last_themes[$theme_slug]['update']['new_version']));
    
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
                $site_plugin_conflicts = json_decode( $website->pluginConflicts, TRUE );
                if ( count( $site_plugin_conflicts ) > 0 ) {
                    $info_txt            = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ';
                    $plugin_conflicts   .= '<li>' . $info_txt;
                    $added               = FALSE;

                    foreach ( $site_plugin_conflicts as $site_plugin_conflict ) {
                        if ( $added ) {
                            $plugin_conflicts .= ', ';
                        }
                        
                        $plugin_conflicts .= $site_plugin_conflict;
                        $added = TRUE;
                    }

                    $plugin_conflicts .= '</li>' . "\n";
                }

                // Show theme conflicts.
                $site_theme_conflicts = json_decode( $website->themeConflicts, TRUE );
                if ( count( $site_theme_conflicts ) > 0 ) {
                    $info_txt         = '<a href="' . admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ) . '">' . $website->name . '</a> - ';
                    $theme_conflicts .= '<li>' . $info_txt;
                    $added            = FALSE;
                    
                    foreach ( $site_theme_conflicts as $site_theme_conflict ) {
                        if ( $added ) {
                            $theme_conflicts .= ', ';
                        }

                        $theme_conflicts .= $site_theme_conflict;
                        $added = TRUE;
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
                $core_new_update_saved = get_option( 'mainwp_updatescheck_mail_update_core_new' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_core_new', MainWPUtility::array_merge( $core_new_update_saved, $core_new_update ) );
            }

            if ( count( $plugins_new_update ) != 0 ) {
                $plugins_new_update_saved = get_option( 'mainwp_updatescheck_mail_update_plugins_new' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_plugins_new', MainWPUtility::array_merge( $plugins_new_update_saved, $plugins_new_update ) );
            }

            if ( count( $themes_new_update ) != 0 ) {
                $themes_new_update_saved = get_option( 'mainwp_updatescheck_mail_update_themes_new' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_themes_new', MainWPUtility::array_merge( $themes_new_update_saved, $themes_new_update ) );
            }

            if ( count( $core_to_update ) != 0 ) {
                $core_to_update_saved = get_option( 'mainwp_updatescheck_mail_update_core' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_core', MainWPUtility::array_merge( $core_to_update_saved, $core_to_update ) );
            }

            if ( count( $plugins_to_update ) != 0 ) {
                $plugins_to_update_saved = get_option( 'mainwp_updatescheck_mail_update_plugins' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_plugins', MainWPUtility::array_merge( $plugins_to_update_saved, $plugins_to_update ) );
            }

            if ( count( $themes_to_update ) != 0 ) {
                $themes_to_update_saved = get_option( 'mainwp_updatescheck_mail_update_themes' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_update_themes', MainWPUtility::array_merge( $themes_to_update_saved, $themes_to_update ) );
            }

            if ( count( $ignored_core_to_update ) != 0 ) {
                $ignored_core_to_update_saved = get_option( 'mainwp_updatescheck_mail_ignore_core' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_core', MainWPUtility::array_merge( $ignored_core_to_update_saved, $ignored_core_to_update ) );
            }

            if ( count( $ignored_core_new_update ) != 0 ) {
                $ignored_core_new_update_saved = get_option( 'mainwp_updatescheck_mail_ignore_core_new' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_core_new', MainWPUtility::array_merge( $ignored_core_new_update_saved, $ignored_core_new_update ) );
            }

            if ( count( $ignored_plugins_to_update ) != 0 ) {
                $ignored_plugins_to_update_saved = get_option( 'mainwp_updatescheck_mail_ignore_plugins' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_plugins', MainWPUtility::array_merge( $ignored_plugins_to_update_saved, $ignored_plugins_to_update ) );
            }

            if ( count( $ignored_plugins_new_update ) != 0 ) {
                $ignored_plugins_new_update_saved = get_option('mainwp_updatescheck_mail_ignore_plugins_new');
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_plugins_new', MainWPUtility::array_merge( $ignored_plugins_new_update_saved, $ignored_plugins_new_update ) );
            }

            if ( count( $ignored_themes_to_update ) != 0) {
                $ignored_themes_to_update_saved = get_option( 'mainwp_updatescheck_mail_ignore_themes' );
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_themes', MainWPUtility::array_merge( $ignored_themes_to_update_saved, $ignored_themes_to_update ) );
            }

            if ( count( $ignored_themes_new_update ) != 0 ) {
                $ignored_themes_new_updateSaved = get_option('mainwp_updatescheck_mail_ignore_themes_new');
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_ignore_themes_new', MainWPUtility::array_merge($ignored_themes_new_updateSaved, $ignored_themes_new_update));
            }

            if ( $plugin_conflicts != '' ) {
                $plugin_conflicts_saved = get_option( 'mainwp_updatescheck_mail_pluginconflicts' );
                
                if ( FALSE == $plugin_conflicts_saved ) {
                    $plugin_conflicts_saved = '';
                }

                MainWPUtility::update_option( 'mainwp_updatescheck_mail_pluginconflicts', $plugin_conflicts_saved . $plugin_conflicts );
            }

            if ( $theme_conflicts != '' ) {
                $theme_conflicts_saved = get_option( 'mainwp_updatescheck_mail_themeconflicts' );
                
                if ( FALSE == $theme_conflicts_saved ) {
                    $theme_conflicts_saved = '';
                }
                
                MainWPUtility::update_option( 'mainwp_updatescheck_mail_themeconflicts', $theme_conflicts_saved . $theme_conflicts );
            }

            if ( ( count( $core_to_update ) == 0 ) && ( count( $plugins_to_update ) == 0 ) && ( count( $themes_to_update ) == 0 ) && ( count( $ignored_core_to_update ) == 0 )  && ( count( $ignored_core_new_update ) == 0 ) && ( count( $ignored_plugins_to_update ) == 0 ) && ( count( $ignored_plugins_new_update ) == 0 ) && ( count( $ignored_themes_to_update ) == 0 ) && ( count( $ignored_themes_new_update ) == 0 ) && ( $plugin_conflicts == '' ) && ( $theme_conflicts == '' ) ) {
                return;
            }

            if ( get_option( 'baufm_automatic_weekly_update' ) != 1 ) {
                return;
            }

            // Check if backups are required!
            if ( 1 == get_option( 'mainwp_backup_before_upgrade' ) ) {
                
                $sites_check_completed = get_option( 'mainwp_automaticUpdate_backupChecks' );
                
                if ( ! is_array( $sites_check_completed ) ) {
                    $sites_check_completed = array();
                }

                $websitesToCheck = array();
                
                foreach ( $plugins_to_update_now as $websiteId => $slugs ) {
                    $websitesToCheck[ $websiteId ] = TRUE;
                }

                foreach ( $themes_to_update_now as $websiteId => $slugs ) {
                    $websitesToCheck[ $websiteId ] = TRUE;
                }

                foreach ( $core_to_update_now as $websiteId ) {
                    $websitesToCheck[ $websiteId ] = TRUE;
                }

                foreach ( $websitesToCheck as $siteId => $bool ) {

                    if ( $all_websites[$siteId]->backup_before_upgrade == 0 ) {
                        $sites_check_completed[ $siteId ] = TRUE;
                    }

                    if ( isset( $sites_check_completed[ $siteId ] ) ) { 
                        continue;
                    }

                    $dir = MainWPUtility::getMainWPSpecificDir( $siteId );
                    
                    // Check if backup ok.
                    $lastBackup = -1;
                    if ( file_exists( $dir ) && ( $dh = opendir( $dir ) ) ) {
                        while ( ( $file = readdir( $dh ) ) !== FALSE ) {
                            if ( $file != '.' && $file != '..' ) {
                                $theFile = $dir . $file;
                                if ( MainWPUtility::isArchive( $file ) && ! MainWPUtility::isSQLArchive( $file ) && ( filemtime( $theFile ) > $lastBackup ) ) {
                                    $lastBackup = filemtime($theFile);
                                }
                            }
                        }
                        closedir( $dh );
                    }

                    $backupRequired = ( $lastBackup < ( time() - ( 7 * 24 * 60 * 60 ) ) ? TRUE : FALSE );

                    if ( ! $backupRequired ) {
                        $sites_check_completed[ $siteId ] = TRUE;
                        MainWPUtility::update_option( 'mainwp_automaticUpdate_backupChecks', $sites_check_completed );
                        continue;
                    }

                    try {
                        $result = MainWPManageSites::backup( $siteId, 'full', '', '', 0, 0, 0, 0 );
                        MainWPManageSites::backupDownloadFile( $siteId, 'full', $result['url'], $result['local'] );
                        $sites_check_completed[ $siteId ] = TRUE;
                        MainWPUtility::update_option( 'mainwp_automaticUpdate_backupChecks', $sites_check_completed );
                    } catch ( Exception $e ) {
                        $sites_check_completed[ $siteId ] = FALSE;
                        MainWPUtility::update_option( 'mainwp_automaticUpdate_backupChecks', $sites_check_completed );
                    }
                }
            } else {
                $sites_check_completed = NULL;
            }

            // Update plugins.
            foreach ( $plugins_to_update_now as $websiteId => $slugs ) {
                
                // Skip if site check is not completed.
                if ( ( $sites_check_completed != NULL ) && ( $sites_check_completed[ $websiteId ] == FALSE ) ) {
                    continue;
                }

                // Let's do it!
                try {
                    MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgradeplugintheme', array(
                        'type' => 'plugin',
                        'list' => urldecode( implode( ',', $slugs ) )
                    ) );

                    if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] );
                } catch ( Exception $e ) {
                }
            }

            // Update themes
            foreach ( $themes_to_update_now as $websiteId => $slugs ) {

                // Skip if site check is not completed.
                if ( ( $sites_check_completed != null ) && ( $sites_check_completed[ $websiteId ] == FALSE ) ) {
                    continue;
                }

                // Let's do it!
                try {
                    MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ] , 'upgradeplugintheme', array(
                        'type' => 'theme',
                        'list' => urldecode(implode(',', $slugs))
                    ) );

                    if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) {
                        MainWPSync::syncInformationArray( $all_websites[ $websiteId ], $information['sync'] );
                    }
                } catch ( Exception $e ) {
                }
            }

            // Update core.
            foreach ( $core_to_update_now as $websiteId ) {
                
                if ( ( $sites_check_completed != null ) && ( $sites_check_completed[ $websiteId ] == FALSE ) ) {
                    continue;
                }

                // Let's do it!
                try {
                    MainWPUtility::fetchUrlAuthed( $all_websites[ $websiteId ], 'upgrade' );
                } catch ( Exception $e ) {
                }
            }

	        do_action( 'baufm_cronupdatecheck_action', $plugins_new_update, $plugins_to_update, $plugins_to_updateNow, $themes_new_update, $themes_to_update, $themes_to_update_now, $core_new_update, $core_to_update, $core_to_update_now );
        }
    }

    public function print_updates_array_lines( $array, $backupChecks ) {
        $output = '';

        foreach ( $array as $line ) {
            $siteId      = $line[0];
            $text        = $line[1];
            $trustedText = $line[2];

            $output .= '<li>' . $text . $trustedText . ( $backupChecks == null || ! isset( $backupChecks[ $siteId ] ) || ( $backupChecks[ $siteId ] == TRUE ) ? '' : '(Requires manual backup)' ) . '</li>'."\n";
        }

        return $output;
    }
}
