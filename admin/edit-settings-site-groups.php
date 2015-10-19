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
		// Get all groups, along with count information.
		$groups_and_count = MainWPDB::Instance()->getGroupsAndCount();

		// Loop over each group. And put all data in a table.
	foreach ( $groups_and_count as $group ) : ?>
			<tr>
				<td class="groupname column-groupname has-row-actions column-primary">
					
					<strong><?php echo $group->name; ?></strong>
					
					<div>
						<span><?php echo sprintf( _n( '%d site', '%d sites', $group->nrsites ), $group->nrsites ); ?></span>
					</div>
					
					<div class="row-actions">
						<?php
						$edit_schedule_url = add_query_arg( array(
							'page' 			  => 'branded-auto-updates-for-mainwp',
							'tab' 			  => 'site-groups',
							'tab-content' => 'site-group-schedule',
							'group-id'		=> $group->id,
						), admin_url( 'admin.php' ) );
						?>

						<span class="edit"><span class="edit">
						<a href="<?php echo esc_url( $edit_schedule_url ); ?>">
							<?php esc_html_e( 'Edit Schedule', 'baufm' ); ?>
						</a>
						</span> | </span>

						<?php
						$cancel_schedule_url = add_query_arg( array(
							'page'      	=> 'branded-auto-updates-for-mainwp',
							'tab'       	=> 'site-groups',
							'tab-content' => 'site-groups',
							'group-id' 		=> $group->id,
						), admin_url( 'admin.php' ) );
						?>

						<span class="edit"><span class="edit">
						<a href="<?php echo esc_url( $cancel_schedule_url ); ?>">
							<?php esc_html_e( 'Cancel Schedule', 'baufm' ); ?>
						</a>
						</span> | </span>

						<?php
						$update_now_url = add_query_arg( array(
							'page'      	=> 'branded-auto-updates-for-mainwp',
							'tab'       	=> 'site-groups',
							'tab-content' => 'site-group-update-now',
							'group-id' 		=> $group->id,
						), admin_url( 'admin.php' ) );
						?>

						<span class="edit"><span class="edit">
						<a href="<?php echo esc_url( $update_now_url ); ?>">
							<?php esc_html_e( 'Update Now', 'baufm' ); ?>
						</a>
						</span></span>
					</div>
				</td>

				<td class="lastupdated column-lastupdated">
					<?php
					$last_scheduled_update_time = BAUFM_Schedules::get_group_last_scheduled_update( $group->id );

					if ( 0 !== (int) $last_scheduled_update_time ) {
						echo  date_i18n( 'l, j F Y h:i A e', $last_scheduled_update_time );
					} else {
						esc_html_e( 'Never.', 'baufm' );
					}
					?>
				</td>

				<td class="nextupdate column-nextupdate">
				
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

</form>
