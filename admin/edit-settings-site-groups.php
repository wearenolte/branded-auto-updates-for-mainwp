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
		$websites 		  = MainWPDB::Instance()->getWebsitesCheckUpdates(4);
		
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
					
				</td>

				<td class="nextupdate column-nextupdate" data-colname="Next Update">
					<?php
						$last_automatic_update = MainWPDB::Instance()->getWebsitesLastAutomaticSync();

						if ( 0 == $last_automatic_update ) {
				            $next_automatic_update = __( 'Any minute.', 'baufm' );
				        } else if ( MainWPDB::Instance()->getWebsitesCountWhereDtsAutomaticSyncSmallerThenStart() > 0 || MainWPDB::Instance()->getWebsitesCheckUpdatesCount() > 0 ) {
				            $next_automatic_update = __( 'Processing your websites.', 'baufm' );
				        } else {
				        	$next_automatic_update = get_option( "baufm_scheduled_action_group_{$group->id}", __( 'Never.', 'baufm' ) );
				        }
					?>

					<?php echo $next_automatic_update; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

</form>