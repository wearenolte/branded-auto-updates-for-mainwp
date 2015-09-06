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

// @ini_set( 'display_errors', FALSE );
// @error_reporting( 0 );

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


        add_action( 'baufm_updater_cron_updates_check_action', array($this, 'baufm_updater_cron_updates_check_action' ) );

        if ( ( $sched = wp_next_scheduled( 'baufm_updater_cron_updates_check_action' ) ) == FALSE) {
            if ( $useWPCron ) {
                wp_schedule_event( time(), 'minutely', 'baufm_updater_cron_updates_check_action' );
            }
        } else {
            if ( ! $useWPCron ) wp_unschedule_event( $sched, 'baufm_updater_cron_updates_check_action' );
        }
    }

    public function baufm_updater_cron_updates_check_action() {
        MainWPLogger::Instance()->info('CRON :: updates check');

        @ignore_user_abort(TRUE);
        @set_time_limit(0);
        $mem =  '512M';
        @ini_set('memory_limit', $mem);
        @ini_set('max_execution_time', 0);

        MainWPUtility::update_option('mainwp_cron_last_updatescheck', time());

        $mainwpAutomaticDailyUpdate = get_option('mainwp_automaticDailyUpdate');

        $mainwpLastAutomaticUpdate = get_option('mainwp_updatescheck_last');
        if ($mainwpLastAutomaticUpdate == date('d/m/Y'))
        {
            MainWPLogger::Instance()->debug('CRON :: updates check :: already updated today');
            return;
        }

        $websites = MainWPDB::Instance()->getWebsitesCheckUpdates(4);
        MainWPLogger::Instance()->debug('CRON :: updates check :: found ' . count($websites) . ' websites');

        $userid = null;
        foreach ($websites as $website)
        {
            $websiteValues = array(
                'dtsAutomaticSyncStart' => time()
            );
            if ($userid == null) $userid = $website->userid;

            MainWPDB::Instance()->updateWebsiteSyncValues($website->id, $websiteValues);
        }

        if (count($websites) == 0)
        {
            $busyCounter = MainWPDB::Instance()->getWebsitesCountWhereDtsAutomaticSyncSmallerThenStart();

            if ($busyCounter == 0)
            {
                MainWPLogger::Instance()->debug('CRON :: updates check :: got to the mail part');

                //Send the email & update all to this time!
                $mail = '';
                $sendMail = FALSE;

                $sitesCheckCompleted = null;
                if (get_option('mainwp_backup_before_upgrade') == 1)
                {
                    $sitesCheckCompleted = get_option('mainwp_automaticUpdate_backupChecks');
                    if (!is_array($sitesCheckCompleted)) $sitesCheckCompleted = null;
                }


                $pluginsNewUpdate = get_option('mainwp_updatescheck_mail_update_plugins_new');
                if (!is_array($pluginsNewUpdate)) $pluginsNewUpdate = array();
                $pluginsToUpdate = get_option('mainwp_updatescheck_mail_update_plugins');
                if (!is_array($pluginsToUpdate)) $pluginsToUpdate = array();
                $ignoredPluginsNewUpdate = get_option('mainwp_updatescheck_mail_ignore_plugins_new');
                if (!is_array($ignoredPluginsNewUpdate)) $ignoredPluginsNewUpdate = array();
                $ignoredPluginsToUpdate = get_option('mainwp_updatescheck_mail_ignore_plugins');
                if (!is_array($ignoredPluginsToUpdate)) $ignoredPluginsToUpdate = array();

                if ((count($pluginsNewUpdate) != 0) || (count($pluginsToUpdate) != 0) || (count($ignoredPluginsNewUpdate) != 0) || (count($ignoredPluginsToUpdate) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Plugin Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($pluginsNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($pluginsToUpdate, $sitesCheckCompleted);
                    $mail .= $this->print_updates_array_lines($ignoredPluginsNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($ignoredPluginsToUpdate, null);
                    $mail .= '</ul>';
                }

                $themesNewUpdate = get_option('mainwp_updatescheck_mail_update_themes_new');
                if (!is_array($themesNewUpdate)) $themesNewUpdate = array();
                $themesToUpdate = get_option('mainwp_updatescheck_mail_update_themes');
                if (!is_array($themesToUpdate)) $themesToUpdate = array();
                $ignoredThemesNewUpdate = get_option('mainwp_updatescheck_mail_ignore_themes_new');
                if (!is_array($ignoredThemesNewUpdate)) $ignoredThemesNewUpdate = array();
                $ignoredThemesToUpdate = get_option('mainwp_updatescheck_mail_ignore_themes');
                if (!is_array($ignoredThemesToUpdate)) $ignoredThemesToUpdate = array();

                if ((count($themesNewUpdate) != 0) || (count($themesToUpdate) != 0) || (count($ignoredThemesNewUpdate) != 0) || (count($ignoredThemesToUpdate) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Themes Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($themesNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($themesToUpdate, $sitesCheckCompleted);
                    $mail .= $this->print_updates_array_lines($ignoredThemesNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($ignoredThemesToUpdate, null);
                    $mail .= '</ul>';
                }

                $coreNewUpdate = get_option('mainwp_updatescheck_mail_update_core_new');
                if (!is_array($coreNewUpdate)) $coreNewUpdate = array();
                $coreToUpdate = get_option('mainwp_updatescheck_mail_update_core');
                if (!is_array($coreToUpdate)) $coreToUpdate = array();
                $ignoredCoreNewUpdate = get_option('mainwp_updatescheck_mail_ignore_core_new');
                if (!is_array($ignoredCoreNewUpdate)) $ignoredCoreNewUpdate = array();
                $ignoredCoreToUpdate = get_option('mainwp_updatescheck_mail_ignore_core');
                if (!is_array($ignoredCoreToUpdate)) $ignoredCoreToUpdate = array();

                if ((count($coreNewUpdate) != 0) || (count($coreToUpdate) != 0) || (count($ignoredCoreNewUpdate) != 0) || (count($ignoredCoreToUpdate) != 0))
                {
                    $sendMail = TRUE;

                    $mail .= '<div><strong>WordPress Core Updates</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $this->print_updates_array_lines($coreNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($coreToUpdate, $sitesCheckCompleted);
                    $mail .= $this->print_updates_array_lines($ignoredCoreNewUpdate, null);
                    $mail .= $this->print_updates_array_lines($ignoredCoreToUpdate, null);
                    $mail .= '</ul>';
                }

                $pluginConflicts = get_option('mainwp_updatescheck_mail_pluginconflicts');
                if ($pluginConflicts === FALSE) $pluginConflicts = '';

                if ($pluginConflicts != '')
                {
                    $sendMail = TRUE;
                    $mail .= '<div><strong>WordPress Plugin Conflicts</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $pluginConflicts;
                    $mail .= '</ul>';
                }

                $themeConflicts = get_option('mainwp_updatescheck_mail_themeconflicts');
                if ($themeConflicts === FALSE) $themeConflicts = '';

                if ($themeConflicts != '')
                {
                    $sendMail = TRUE;
                    $mail .= '<div><strong>WordPress Theme Conflicts</strong></div>';
                    $mail .= '<ul>';
                    $mail .= $themeConflicts;
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

                if ($mainwpAutomaticDailyUpdate !== FALSE && $mainwpAutomaticDailyUpdate != 0)
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
        }
        else
        {
            $userExtension = MainWPDB::Instance()->getUserExtensionByUserId($userid);

            $decodedIgnoredPlugins = json_decode($userExtension->ignored_plugins, TRUE);
            if (!is_array($decodedIgnoredPlugins)) $decodedIgnoredPlugins = array();

            $trustedPlugins = json_decode($userExtension->trusted_plugins, TRUE);
            if (!is_array($trustedPlugins)) $trustedPlugins = array();

            $decodedIgnoredThemes = json_decode($userExtension->ignored_themes, TRUE);
            if (!is_array($decodedIgnoredThemes)) $decodedIgnoredThemes = array();

            $trustedThemes = json_decode($userExtension->trusted_themes, TRUE);
            if (!is_array($trustedThemes)) $trustedThemes = array();

            $coreToUpdateNow = array();
            $coreToUpdate = array();
            $coreNewUpdate = array();
            $ignoredCoreToUpdate = array();
            $ignoredCoreNewUpdate = array();

            $pluginsToUpdateNow = array();
            $pluginsToUpdate = array();
            $pluginsNewUpdate = array();
            $ignoredPluginsToUpdate = array();
            $ignoredPluginsNewUpdate = array();

            $themesToUpdateNow = array();
            $themesToUpdate = array();
            $themesNewUpdate = array();
            $ignoredThemesToUpdate = array();
            $ignoredThemesNewUpdate = array();

            $pluginConflicts = '';
            $themeConflicts = '';

            $allWebsites = array();

            $infoTrustedText = ' (<span style="color:#008000"><strong>Trusted</strong></span>)';
            $infoNotTrustedText = ' (<strong><span style="color:#ff0000">Not Trusted</span></strong>)';

            foreach ($websites as $website)
            {
                $websiteDecodedIgnoredPlugins = json_decode($website->ignored_plugins, TRUE);
                if (!is_array($websiteDecodedIgnoredPlugins)) $websiteDecodedIgnoredPlugins = array();

                $websiteDecodedIgnoredThemes = json_decode($website->ignored_themes, TRUE);
                if (!is_array($websiteDecodedIgnoredThemes)) $websiteDecodedIgnoredThemes = array();

                //Perform check & update
                if (!MainWPSync::syncSite($website, FALSE, TRUE))
                {
                    $websiteValues = array(
                        'dtsAutomaticSync' => time()
                    );

                    MainWPDB::Instance()->updateWebsiteSyncValues($website->id, $websiteValues);

                    continue;
                }
                $website = MainWPDB::Instance()->getWebsiteById($website->id);

                /** Check core upgrades **/
                $websiteLastCoreUpgrades = json_decode(MainWPDB::Instance()->getWebsiteOption($website, 'last_wp_upgrades'), TRUE);
                $websiteCoreUpgrades = json_decode(MainWPDB::Instance()->getWebsiteOption($website, 'wp_upgrades'), TRUE);

                //Run over every update we had last time..
                if (isset($websiteCoreUpgrades['current']))
                {
                    $infoTxt = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $websiteCoreUpgrades['current'] . ' to ' . $websiteCoreUpgrades['new'];
                    $infoNewTxt = '*NEW* <a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $websiteCoreUpgrades['current'] . ' to ' . $websiteCoreUpgrades['new'];
                    $newUpdate = !(isset($websiteLastCoreUpgrades['current']) && ($websiteLastCoreUpgrades['current'] == $websiteCoreUpgrades['current']) && ($websiteLastCoreUpgrades['new'] == $websiteCoreUpgrades['new']));
                    if ($website->automatic_update == 1)
                    {
                        if ($newUpdate)
                        {
                            $coreNewUpdate[] = array($website->id, $infoNewTxt, $infoTrustedText);
                        }
                        else
                        {
                            //Check ignore ? $ignoredCoreToUpdate
                            $coreToUpdateNow[] = $website->id;
                            $allWebsites[$website->id] = $website;
                            $coreToUpdate[] = array($website->id, $infoTxt, $infoTrustedText);
                        }
                    }
                    else
                    {
                        if ($newUpdate)
                        {
                            $ignoredCoreNewUpdate[] = array($website->id, $infoNewTxt, $infoNotTrustedText);
                        }
                        else
                        {
                            $ignoredCoreToUpdate[] = array($website->id, $infoTxt, $infoNotTrustedText);
                        }
                    }
                }

                /** Check plugins **/
                $websiteLastPlugins = json_decode(MainWPDB::Instance()->getWebsiteOption($website, 'last_plugin_upgrades'), TRUE);
                $websitePlugins = json_decode($website->plugin_upgrades, TRUE);

                /** Check themes **/
                $websiteLastThemes = json_decode(MainWPDB::Instance()->getWebsiteOption($website, 'last_theme_upgrades'), TRUE);
                $websiteThemes = json_decode($website->theme_upgrades, TRUE);

                $decodedPremiumUpgrades = json_decode(MainWPDB::Instance()->getWebsiteOption($website, 'premium_upgrades'), TRUE);
                if (is_array($decodedPremiumUpgrades))
                {
                    foreach ($decodedPremiumUpgrades as $slug => $premiumUpgrade)
                    {
                        if ($premiumUpgrade['type'] == 'plugin')
                        {
                            if (!is_array($websitePlugins)) $websitePlugins = array();
                            $websitePlugins[$slug] = $premiumUpgrade;
                        }
                        else if ($premiumUpgrade['type'] == 'theme')
                        {
                            if (!is_array($websiteThemes)) $websiteThemes = array();
                            $websiteThemes[$slug] = $premiumUpgrade;
                        }
                    }
                }


                //Run over every update we had last time..
                foreach ($websitePlugins as $pluginSlug => $pluginInfo)
                {
                    $infoTxt = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $pluginInfo['Name'] . ' ' . $pluginInfo['Version'] . ' to ' . $pluginInfo['update']['new_version'];
                    $infoNewTxt = '*NEW* <a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $pluginInfo['Name'] . ' ' . $pluginInfo['Version'] . ' to ' . $pluginInfo['update']['new_version'];

                    $newUpdate = !(isset($websiteLastPlugins[$pluginSlug]) && ($pluginInfo['Version'] == $websiteLastPlugins[$pluginSlug]['Version']) && ($pluginInfo['update']['new_version'] == $websiteLastPlugins[$pluginSlug]['update']['new_version']));
                    //update this..
                    if (in_array($pluginSlug, $trustedPlugins) && !isset($decodedIgnoredPlugins[$pluginSlug]) && !isset($websiteDecodedIgnoredPlugins[$pluginSlug]))
                    {
                        //Trusted
                        if ($newUpdate)
                        {
                            $pluginsNewUpdate[] = array($website->id, $infoNewTxt, $infoTrustedText);
                        }
                        else
                        {
                            $pluginsToUpdateNow[$website->id][] = $pluginSlug;
                            $allWebsites[$website->id] = $website;
                            $pluginsToUpdate[] = array($website->id, $infoTxt, $infoTrustedText);
                        }
                    }
                    else
                    {
                        //Not trusted
                        if ($newUpdate)
                        {
                            $ignoredPluginsNewUpdate[] = array($website->id, $infoNewTxt, $infoNotTrustedText);
                        }
                        else
                        {
                            $ignoredPluginsToUpdate[] = array($website->id, $infoTxt, $infoNotTrustedText);
                        }
                    }
                }

                //Run over every update we had last time..
                foreach ($websiteThemes as $themeSlug => $themeInfo)
                {
                    $infoTxt = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $themeInfo['Name'] . ' ' . $themeInfo['Version'] . ' to ' . $themeInfo['update']['new_version'];
                    $infoNewTxt = '*NEW* <a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ' . $themeInfo['Name'] . ' ' . $themeInfo['Version'] . ' to ' . $themeInfo['update']['new_version'];

                    $newUpdate = !(isset($websiteLastThemes[$themeSlug]) && ($themeInfo['Version'] == $websiteLastThemes[$themeSlug]['Version']) && ($themeInfo['update']['new_version'] == $websiteLastThemes[$themeSlug]['update']['new_version']));
                    //update this..
                    if (in_array($themeSlug, $trustedThemes) && !isset($decodedIgnoredThemes[$themeSlug]) && !isset($websiteDecodedIgnoredThemes[$themeSlug]))
                    {
                        //Trusted
                        if ($newUpdate)
                        {
                            $themesNewUpdate[] = array($website->id, $infoNewTxt, $infoTrustedText);
                        }
                        else
                        {
                            $themesToUpdateNow[$website->id][] = $themeSlug;
                            $allWebsites[$website->id] = $website;
                            $themesToUpdate[] = array($website->id, $infoTxt, $infoTrustedText);
                        }
                    }
                    else
                    {
                        //Not trusted
                        if ($newUpdate)
                        {
                            $ignoredThemesNewUpdate[] = array($website->id, $infoNewTxt, $infoNotTrustedText);
                        }
                        else
                        {
                            $ignoredThemesToUpdate[] = array($website->id, $infoTxt, $infoNotTrustedText);
                        }
                    }
                }

                /**
                 * Show plugin conflicts
                 */
                $sitePluginConflicts = json_decode($website->pluginConflicts, TRUE);
                if (count($sitePluginConflicts) > 0)
                {
                    $infoTxt = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ';

                    $pluginConflicts .= '<li>' . $infoTxt;
                    $added = FALSE;
                    foreach ($sitePluginConflicts as $sitePluginConflict)
                    {
                        if ($added) $pluginConflicts .= ', ';
                        $pluginConflicts .= $sitePluginConflict;
                        $added = TRUE;
                    }
                    $pluginConflicts .= '</li>' . "\n";
                }

                /**
                 * Show theme conflicts
                 */
                $siteThemeConflicts = json_decode($website->themeConflicts, TRUE);
                if (count($siteThemeConflicts) > 0)
                {
                    $infoTxt = '<a href="' . admin_url('admin.php?page=managesites&dashboard=' . $website->id) . '">' . $website->name . '</a> - ';

                    $themeConflicts .= '<li>' . $infoTxt;
                    $added = FALSE;
                    foreach ($siteThemeConflicts as $siteThemeConflict)
                    {
                        if ($added) $themeConflicts .= ', ';
                        $themeConflicts .= $siteThemeConflict;
                        $added = TRUE;
                    }
                    $themeConflicts .= '</li>' . "\n";
                }

                //Loop over last plugins & current plugins, check if we need to upgrade them..
                $user = get_userdata($website->userid);
                $email = MainWPUtility::getNotificationEmail($user);
                MainWPUtility::update_option('mainwp_updatescheck_mail_email', $email);
                MainWPDB::Instance()->updateWebsiteSyncValues($website->id, array('dtsAutomaticSync' => time()));
                MainWPDB::Instance()->updateWebsiteOption($website, 'last_wp_upgrades', json_encode($websiteCoreUpgrades));
                MainWPDB::Instance()->updateWebsiteOption($website, 'last_plugin_upgrades', $website->plugin_upgrades);
                MainWPDB::Instance()->updateWebsiteOption($website, 'last_theme_upgrades', $website->theme_upgrades);
            }

            if (count($coreNewUpdate) != 0)
            {
                $coreNewUpdateSaved = get_option('mainwp_updatescheck_mail_update_core_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_core_new', MainWPUtility::array_merge($coreNewUpdateSaved, $coreNewUpdate));
            }

            if (count($pluginsNewUpdate) != 0)
            {
                $pluginsNewUpdateSaved = get_option('mainwp_updatescheck_mail_update_plugins_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_plugins_new', MainWPUtility::array_merge($pluginsNewUpdateSaved, $pluginsNewUpdate));
            }

            if (count($themesNewUpdate) != 0)
            {
                $themesNewUpdateSaved = get_option('mainwp_updatescheck_mail_update_themes_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_themes_new', MainWPUtility::array_merge($themesNewUpdateSaved, $themesNewUpdate));
            }

            if (count($coreToUpdate) != 0)
            {
                $coreToUpdateSaved = get_option('mainwp_updatescheck_mail_update_core');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_core', MainWPUtility::array_merge($coreToUpdateSaved, $coreToUpdate));
            }

            if (count($pluginsToUpdate) != 0)
            {
                $pluginsToUpdateSaved = get_option('mainwp_updatescheck_mail_update_plugins');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_plugins', MainWPUtility::array_merge($pluginsToUpdateSaved, $pluginsToUpdate));
            }

            if (count($themesToUpdate) != 0)
            {
                $themesToUpdateSaved = get_option('mainwp_updatescheck_mail_update_themes');
                MainWPUtility::update_option('mainwp_updatescheck_mail_update_themes', MainWPUtility::array_merge($themesToUpdateSaved, $themesToUpdate));
            }

            if (count($ignoredCoreToUpdate) != 0)
            {
                $ignoredCoreToUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_core');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_core', MainWPUtility::array_merge($ignoredCoreToUpdateSaved, $ignoredCoreToUpdate));
            }

            if (count($ignoredCoreNewUpdate) != 0)
            {
                $ignoredCoreNewUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_core_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_core_new', MainWPUtility::array_merge($ignoredCoreNewUpdateSaved, $ignoredCoreNewUpdate));
            }

            if (count($ignoredPluginsToUpdate) != 0)
            {
                $ignoredPluginsToUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_plugins');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_plugins', MainWPUtility::array_merge($ignoredPluginsToUpdateSaved, $ignoredPluginsToUpdate));
            }

            if (count($ignoredPluginsNewUpdate) != 0)
            {
                $ignoredPluginsNewUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_plugins_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_plugins_new', MainWPUtility::array_merge($ignoredPluginsNewUpdateSaved, $ignoredPluginsNewUpdate));
            }

            if (count($ignoredThemesToUpdate) != 0)
            {
                $ignoredThemesToUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_themes');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_themes', MainWPUtility::array_merge($ignoredThemesToUpdateSaved, $ignoredThemesToUpdate));
            }

            if (count($ignoredThemesNewUpdate) != 0)
            {
                $ignoredThemesNewUpdateSaved = get_option('mainwp_updatescheck_mail_ignore_themes_new');
                MainWPUtility::update_option('mainwp_updatescheck_mail_ignore_themes_new', MainWPUtility::array_merge($ignoredThemesNewUpdateSaved, $ignoredThemesNewUpdate));
            }

            if ($pluginConflicts != '')
            {
                $pluginConflictsSaved = get_option('mainwp_updatescheck_mail_pluginconflicts');
                if ($pluginConflictsSaved == FALSE) $pluginConflictsSaved = '';
                MainWPUtility::update_option('mainwp_updatescheck_mail_pluginconflicts', $pluginConflictsSaved . $pluginConflicts);
            }

            if ($themeConflicts != '')
            {
                $themeConflictsSaved = get_option('mainwp_updatescheck_mail_themeconflicts');
                if ($themeConflictsSaved == FALSE) $themeConflictsSaved = '';
                MainWPUtility::update_option('mainwp_updatescheck_mail_themeconflicts', $themeConflictsSaved . $themeConflicts);
            }

            if ((count($coreToUpdate) == 0) && (count($pluginsToUpdate) == 0) && (count($themesToUpdate) == 0) && (count($ignoredCoreToUpdate) == 0)  && (count($ignoredCoreNewUpdate) == 0) && (count($ignoredPluginsToUpdate) == 0) && (count($ignoredPluginsNewUpdate) == 0) && (count($ignoredThemesToUpdate) == 0) && (count($ignoredThemesNewUpdate) == 0) && ($pluginConflicts == '') && ($themeConflicts == ''))
            {
                return;
            }

            if (get_option('mainwp_automaticDailyUpdate') != 1) return;


            //Check if backups are required!
            if (get_option('mainwp_backup_before_upgrade') == 1)
            {
                $sitesCheckCompleted = get_option('mainwp_automaticUpdate_backupChecks');
                if (!is_array($sitesCheckCompleted)) $sitesCheckCompleted = array();

                $websitesToCheck = array();
                foreach ($pluginsToUpdateNow as $websiteId => $slugs)
                {
                    $websitesToCheck[$websiteId] = TRUE;
                }

                foreach ($themesToUpdateNow as $websiteId => $slugs)
                {
                    $websitesToCheck[$websiteId] = TRUE;
                }

                foreach ($coreToUpdateNow as $websiteId)
                {
                    $websitesToCheck[$websiteId] = TRUE;
                }

                foreach ($websitesToCheck as $siteId => $bool)
                {
                    if ($allWebsites[$siteId]->backup_before_upgrade == 0)
                    {
                        $sitesCheckCompleted[$siteId] = TRUE;
                    }
                    if (isset($sitesCheckCompleted[$siteId])) continue;

                    $dir = MainWPUtility::getMainWPSpecificDir($siteId);
                    //Check if backup ok
                    $lastBackup = -1;
                    if (file_exists($dir) && ($dh = opendir($dir)))
                    {
                        while (($file = readdir($dh)) !== FALSE)
                        {
                            if ($file != '.' && $file != '..')
                            {
                                $theFile = $dir . $file;
                                if (MainWPUtility::isArchive($file) && !MainWPUtility::isSQLArchive($file) && (filemtime($theFile) > $lastBackup))
                                {
                                    $lastBackup = filemtime($theFile);
                                }
                            }
                        }
                        closedir($dh);
                    }

                    $backupRequired = ($lastBackup < (time() - (7 * 24 * 60 * 60)) ? TRUE : FALSE);

                    if (!$backupRequired)
                    {
                        $sitesCheckCompleted[$siteId] = TRUE;
                        MainWPUtility::update_option('mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted);
                        continue;
                    }

                    try
                    {
                        $result = MainWPManageSites::backup($siteId, 'full', '', '', 0, 0, 0, 0);
                        MainWPManageSites::backupDownloadFile($siteId, 'full', $result['url'], $result['local']);
                        $sitesCheckCompleted[$siteId] = TRUE;
                        MainWPUtility::update_option('mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted);
                    }
                    catch (Exception $e)
                    {
                        $sitesCheckCompleted[$siteId] = FALSE;
                        MainWPUtility::update_option('mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted);
                    }
                }
            }
            else
            {
                $sitesCheckCompleted = null;
            }


            //Update plugins
            foreach ($pluginsToUpdateNow as $websiteId => $slugs)
            {
                if (($sitesCheckCompleted != null) && ($sitesCheckCompleted[$websiteId] == FALSE)) continue;

                try
                {
                    MainWPUtility::fetchUrlAuthed($allWebsites[$websiteId], 'upgradeplugintheme', array(
                        'type' => 'plugin',
                        'list' => urldecode(implode(',', $slugs))
                    ));

                    if (isset($information['sync']) && !empty($information['sync'])) MainWPSync::syncInformationArray($allWebsites[$websiteId], $information['sync']);
                }
                catch (Exception $e)
                {
                }
            }

            //Update themes
            foreach ($themesToUpdateNow as $websiteId => $slugs)
            {
                if (($sitesCheckCompleted != null) && ($sitesCheckCompleted[$websiteId] == FALSE)) continue;

                try
                {
                    MainWPUtility::fetchUrlAuthed($allWebsites[$websiteId], 'upgradeplugintheme', array(
                        'type' => 'theme',
                        'list' => urldecode(implode(',', $slugs))
                    ));

                    if (isset($information['sync']) && !empty($information['sync'])) MainWPSync::syncInformationArray($allWebsites[$websiteId], $information['sync']);
                }
                catch (Exception $e)
                {
                }
            }

            //Update core
            foreach ($coreToUpdateNow as $websiteId)
            {
                if (($sitesCheckCompleted != null) && ($sitesCheckCompleted[$websiteId] == FALSE)) continue;

                try
                {
                    MainWPUtility::fetchUrlAuthed($allWebsites[$websiteId], 'upgrade');
                }
                catch (Exception $e)
                {
                }
            }
	        do_action( 'mainwp_cronupdatecheck_action', $pluginsNewUpdate, $pluginsToUpdate, $pluginsToUpdateNow, $themesNewUpdate, $themesToUpdate, $themesToUpdateNow, $coreNewUpdate, $coreToUpdate, $coreToUpdateNow );
        }
    }

    public function print_updates_array_lines( $array, $backupChecks ) {
        $output = '';
        foreach ( $array as $line ) {
            $siteId = $line[0];
            $text = $line[1];
            $trustedText = $line[2];

            $output .= '<li>' . $text . $trustedText . ($backupChecks == null || !isset($backupChecks[$siteId]) || ($backupChecks[$siteId] == TRUE) ? '' : '(Requires manual backup)') . '</li>'."\n";
        }
        return $output;
    }
}
