<?php
/**
 * The site groups scheduler form
 *
 * @since 0.2.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 *
 * @todo Re-implement this as list table.
 */
?>

<form id="branded-auto-updates-for-mainwp-settings-auto-updates" action="" method="">
<?php wp_nonce_field( 'baufm_settings_nonce' ); ?>


<input type="hidden" name="page" value="branded-auto-updates-for-mainwp">
<input type="hidden" name="tab" value="site-groups">

<br>

<table class="wp-list-table widefat fixed striped sites">
	<thead>
		<tr>
			<th scope="col" id="" class="manage-column column- column-primary"><span>Site Group</span></th>
			<th scope="col" id="" class="manage-column column-"><span>Last Update</span></th>
			<th scope="col" id="" class="manage-column column-"><span>Next Update</span></th>
		</tr>
	</thead>

	<tbody id="the-list">
	<?php
		$groups_and_count = MainWPDB::Instance()->getGroupsAndCount();
		$websites 		  = MainWPDB::Instance()->getWebsitesCheckUpdates( 4 );

	foreach ( $groups_and_count as $group ) : ?>
			<tr>
				<td class="blogname column-blogname has-row-actions column-primary" data-colname="URL">
					
					<strong><?php echo $group->name; ?></strong>
					
					<div>
						<span><?php echo sprintf( _n( '%d site', '%d sites', $group->nrsites ), $group->nrsites ); ?></span>
					</div>
					
					<div class="row-actions">
						<?php
						$edit_sechdule_url = add_query_arg( array(
							'page' 			=> 'branded-auto-updates-for-mainwp',
							'tab' 			=> 'site-groups',
							'tab-content' 	=> 'site-group-schedule',
							'group-id'		=> $group->id,
						), admin_url( 'admin.php' ) );
						?>
						<span class="edit"><span class="edit"><a href="<?php echo esc_url( $edit_sechdule_url ); ?>">Edit Schedule</a></span> | </span>
						<span class="edit"><span class="edit"><a href="<?php echo $group->id; ?>">Cancel Schedule</a></span> | </span>
						<span class="edit"><span class="edit"><a href="<?php echo $group->id; ?>">Update Now</a></span></span>
					</div>
				</td>

				<td class="lastupdated column-lastupdated" data-colname="Last Updated">
					<?php
						$last_scheduled_update_time = get_option( "baufm_last_scheduled_update_{$group->id}", 0 );
						$last_scheduled_update = __( 'Never.', 'baufm' );

					if ( 0 !== (int) $last_scheduled_update_time ) {
						$last_scheduled_update = date_i18n( 'l, j F Y h:i A e', $last_scheduled_update_time );
					}

						echo $last_scheduled_update;
					?>
				</td>

				<td class="nextupdate column-nextupdate" data-colname="Next Update">
					<?php
					$last_automatic_update = MainWPDB::Instance()->getWebsitesLastAutomaticSync();

					if ( 0 == $last_automatic_update ) {
						$next_automatic_update = __( 'Any minute.', 'baufm' );
					} else if ( MainWPDB::Instance()->getWebsitesCountWhereDtsAutomaticSyncSmallerThenStart() > 0 || MainWPDB::Instance()->getWebsitesCheckUpdatesCount() > 0 ) {
						$next_automatic_update = __( 'Processing your websites.', 'baufm' );
					} else {
						$daily_schedule = baufm_get_scheduled_day_of_week( $group->id, 'int' );
						$is_off 	 	= ( 0 === $daily_schedule || ! $daily_schedule );
						$is_daily 	 	= ( 1 === $daily_schedule );

						if ( $is_off ) {
							$next_automatic_update = baufm_format_scheduled_day_of_week( $daily_schedule );
						} else {
							if ( $is_daily ) {
								if ( date_i18n( 'G' ) >= baufm_get_scheduled_time_of_day( $group->id, 'int' ) ) {
									$next_automatic_update = sprintf( __( 'Tomorrow at %s %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ), date_i18n( 'e' ) );
									$str_to_time = sprintf( __( 'tomorrow %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ) );
								} else {
									$next_automatic_update = sprintf( __( 'Today at %s %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ), date_i18n( 'e' ) );
									$str_to_time = sprintf( __( 'today %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ) );
								}
							} else {
								if ( date_i18n( 'G' ) >= baufm_get_scheduled_time_of_day( $group->id, 'int' ) ) {
									$next_automatic_update = sprintf( __( 'Next %s at %s %s', 'baufm' ), baufm_format_scheduled_day_of_week( $daily_schedule ), baufm_get_scheduled_time_of_day( $group->id ), date_i18n( 'e' ) );
									$str_to_time = sprintf( __( 'next %s %s', 'baufm' ), baufm_format_scheduled_day_of_week( $daily_schedule ), baufm_get_scheduled_time_of_day( $group->id ) );
								} else {
									$next_automatic_update = sprintf( __( 'Today at %s %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ), date_i18n( 'e' ) );
									$str_to_time = sprintf( __( 'today %s', 'baufm' ), baufm_get_scheduled_time_of_day( $group->id ) );
								}
							}
						}
					}
					?>

					<?php echo $next_automatic_update; ?>
					<br>
					<?php
						$from = strtotime( date( 'l, j F Y h:i A e', strtotime( $str_to_time ) ) );
						$to = time();

					if ( $from > $to ) {
						$days = floor( ( $from - $to ) / ( 60 * 60 * 24 ) );
						$hours = floor( ( ( $from - $to ) / ( 60 * 60 ) ) - ( $days * 24 ) );
						$minutes = floor( ( ( $from - $to ) / 60 ) - ( $days * 24 * 60 ) - ( $hours * 60 ) );
						$seconds = ( $from - $to ) - ( $days * 24 * 60 * 60 ) - ( $hours * 60 * 60 ) - ( $minutes * 60 );

						echo sprintf( 'Updating in %d days %d hours %d minutes %d seconds', $days, $hours, $minutes, $seconds );
					} else {
						echo __( 'Updating now.', 'baufm' );
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

</form>
