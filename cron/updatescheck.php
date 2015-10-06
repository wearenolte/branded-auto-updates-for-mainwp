<?php
include_once( 'bootstrap.php' );

MainWPLogger::Instance()->info( 'CRON :: remote call to baufm_updater_cron_updates_check_action' );

BAUFM_Updater::_instance()->baufm_updater_cron_updates_check_action();
