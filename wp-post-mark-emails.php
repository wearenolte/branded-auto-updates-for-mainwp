<?php

/*
Plugin Name: WP Post Mark Emails
Description: Provides a nice function that can be used like wp_mail() to send out emails to using PostMark.
Version: 0.1.0
Author: Moxie
Author URI: http://getmoxied.net
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp_post_mark_emails
Domain Path: /languages
*/

/*
Copyright 2014  Dominique Mariano ( dominique.acpal.mariano@gmail.com )

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! function_exists( 'add_action' ) && ! function_exists( 'add_filter' ) ) {
  echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
  exit;
}

define( 'WP_POST_MARK_EMAILS', plugin_basename( __FILE__ ) );
define( 'WP_POST_MARK_EMAILS_PREFIX', 'wp_post_mark_emails_' );
define( 'WP_POST_MARK_EMAILS_PREFIX_PLUGIN_VERSION', '0.1.0' );
define( 'WP_POST_MARK_EMAILS_WP_VERSION', '4.2.3' );
define( 'WP_POST_MARK_EMAILS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_POST_MARK_EMAILS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once('vendor/autoload.php');
require_once('includes/vars.php');
require_once('admin/admin.php');

register_activation_hook( __FILE__, array( 'WP_Post_Mark_Emails', 'maybe_deactivate' ) );
register_activation_hook( __FILE__, array( 'WP_Post_Mark_Emails', 'maybe_update' ) );
register_activation_hook( __FILE__, array( 'WP_Post_Mark_Emails', 'flush_rules' ) );
register_deactivation_hook( __FILE__, array( 'WP_Post_Mark_Emails', 'flush_rules' ) );
add_action( 'init', array( 'WP_Post_Mark_Emails', 'init' ) );