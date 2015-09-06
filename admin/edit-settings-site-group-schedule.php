<?php
/**
 * The site groups table list
 *
 * @since 0.2.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( ! current_user_can( 'manage_options' ) ) {
  wp_die( __( 'You do not have sufficient permissions to edit this site.' ) );
}

$group_id = isset( $_REQUEST['group-id'] ) ? intval( $_REQUEST['group-id'] ) : 0;
$groups_and_count = MainWPDB::Instance()->getGroupsAndCount();
$group = $groups_and_count[ $group_id ];

if ( ! is_numeric( $group_id ) && ! $group_id ) {
  wp_die( __( 'Invalid site group ID.' ) );
}

$schedule_in_week = get_option( "baufm_schedule_in_week_group_$group_id" );
$schedule_in_day  = get_option( "baufm_schedule_in_day_group_$group_id" );
$sheduled_action  = get_option( "baufm_scheduled_action_group_$group_id" );
?>

<form method="get" action="">
  <?php wp_nonce_field( 'baufm_settings_nonce' ); ?>

  <input type="hidden" name="group-id"    value="<?php echo esc_attr( $group_id ); ?>" />
  <input type="hidden" name="page"        value="branded-auto-updates-for-mainwp">
  <input type="hidden" name="tab"         value="site-groups">
  <input type="hidden" name="tab-content" value="site-group-schedule">

  <table class="form-table">
    <tr class="form-field form-required">
      <th scope="row"><?php _e( 'Group' ); ?></th>
      <td>
        <?php echo $group->name; ?>
        <p class="description"><?php echo sprintf( _n( 'You have only 1 site in this group.', 'You have %d sites in this group.', (int) $group->nrsites, 'baufm' ), (int) $group->nrsites ); ?></p>
      </td>
    </tr>

    <tr class="form-field form-required">
      <th scope="row"><?php _e( 'Batch Schedule' ); ?></th>
      <td>
        <select name="schedule_in_week">
          <?php 
            $schedule_in_week_values = array(
              'Off',
              'Everyday',
              'Every Monday',
              'Every Tuesday',
              'Every Wednesday',
              'Every Thursday',
              'Every Friday',
              'Every Saturday',
              'Every Sunday',
            );
          
            foreach ( $schedule_in_week_values as $index => $day ) {
              ?>
                <option value="<?php echo esc_attr( $index ); ?>" <?php selected( $index, $schedule_in_week ); ?>><?php esc_html_e( $day ); ?></option>
              <?php
            }
          ?>
        </select>

        <select name="schedule_in_day">
          <?php for ( $i = 0; $i < 24; $i++ ) : ?>
            <?php 
              $suffix = ( $i >= 12 ) ? 'PM' : 'AM';
              $time   = ( $i <= 12 ) ? $i : $i - 12;
              $time   = ( $time < 10 ) ? '0' . $time : $time; 
            ?>
            <?php if ( 0 == $i ) : ?>
              <option value="0" <?php echo selected( $i, $schedule_in_day ); ?>>12:00 <?php echo $suffix; ?></option>
            <?php else: ?>
              <option value="<?php echo $time; ?>" <?php echo selected( $i, $schedule_in_day ); ?>><?php echo $time; ?>:00 <?php echo $suffix; ?></option>
            <?php endif; ?>
          <?php endfor; ?>
        </select>

        <span><?php _e( 'in local time.', 'baufm' ); ?></span>

        <p class="description">UTC time today is <code><?php echo date_i18n( 'l, j F Y h:i A', time(), 'gmt' ); ?></code>.</p>
        <p class="description">Local time today is <code><?php echo date_i18n( 'l, j F Y h:i A e' ); ?></code></p>

        <br>

        <span>
        
        <?php
        $current_offset = get_option('gmt_offset');
        $tzstring = get_option('timezone_string');

        $check_zone_info = true;

        // Remove old Etc mappings. Fallback to gmt_offset.
        if ( false !== strpos($tzstring,'Etc/GMT') )
          $tzstring = '';

        if ( empty($tzstring) ) { // Create a UTC+- zone if no timezone string exists
          $check_zone_info = false;
          if ( 0 == $current_offset )
            $tzstring = 'UTC+0';
          elseif ($current_offset < 0)
            $tzstring = 'UTC' . $current_offset;
          else
            $tzstring = 'UTC+' . $current_offset;
        }

        // Set TZ so localtime works.
        date_default_timezone_set($tzstring);
        $now = localtime(time(), true);
        if ( $now['tm_isdst'] )
          _e('This timezone is currently in daylight saving time.');
        else
          _e('This timezone is currently in standard time.');
        ?>
        <br />
        <?php
        $allowed_zones = timezone_identifiers_list();

        if ( in_array( $tzstring, $allowed_zones) ) {
          $found = false;
          $date_time_zone_selected = new DateTimeZone($tzstring);
          $tz_offset = timezone_offset_get($date_time_zone_selected, date_create());
          $right_now = time();
          foreach ( timezone_transitions_get($date_time_zone_selected) as $tr) {
            if ( $tr['ts'] > $right_now ) {
                $found = true;
              break;
            }
          }

          if ( $found ) {
            echo ' ';
            $message = $tr['isdst'] ?
              __('Daylight saving time begins on: <code>%s</code>.') :
              __('Standard time begins on: <code>%s</code>.');
            // Add the difference between the current offset and the new offset to ts to get the correct transition time from date_i18n().
            printf( $message, date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $tr['ts'] + ($tz_offset - $tr['offset']) ) );
          } else {
            _e('This timezone does not observe daylight saving time.');
          }
        }
        // Set back to UTC.
        date_default_timezone_set('UTC');
        ?>
        </span>
      </td> 
    </tr>

    <tr class="form-field form-required">
      <th scope="row"><?php _e( 'Scheduled Action' ); ?></th>
      <td>
        <select name="sheduled_action">
          <?php
            $sheduled_action_list = array(
              'Off',
              'Email Updates',
              'Install Trusted Updates',
            );
          
            foreach ( $sheduled_action_list as $value => $text ) {
              ?>
              <option value="<?php esc_attr_e( $value ); ?>"  <?php selected( $sheduled_action, $value ); ?>><?php esc_html_e( $text ); ?></option>
              <?php
            }
          ?>
        </select>
      </td>
    </tr>
  </table>

  <?php submit_button( __( 'Save', 'baufm' ), 'primary', 'baufm_save_site_group_sched', false ); ?>
</form>