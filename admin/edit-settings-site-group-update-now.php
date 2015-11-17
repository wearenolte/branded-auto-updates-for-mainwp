<?php
/**
 * Update now confirmation form.
 *
 * @since 0.2.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'You do not have sufficient permissions to edit this site.' ) );
}

$group_id  	  = isset( $_REQUEST['group-id'] ) ? intval( $_REQUEST['group-id'] ) : 0;
$groups_and_count = MainWPDB::Instance()->getGroupsAndCount();
$group 		  = $groups_and_count[ $group_id ];

if ( ! is_numeric( $group_id ) && ! $group_id ) {
	wp_die( __( 'Invalid site group ID.' ) );
}

$schedule_in_week  = get_option( "baufm_schedule_in_week_group_$group_id" );
$schedule_in_day   = get_option( "baufm_schedule_in_day_group_$group_id" );
$scheduled_action  = get_option( "baufm_scheduled_action_group_$group_id" );
?>

<form method="get" action="">
  <?php wp_nonce_field( 'baufm_settings_nonce' ); ?>

  <input type="hidden" name="group-id"    value="<?php echo esc_attr( $group_id ); ?>" />
  <input type="hidden" name="page"        value="branded-auto-updates-for-mainwp">
  <input type="hidden" name="tab"         value="site-groups">
  <input type="hidden" name="tab-content" value="site-group-update-now">
  <input type="hidden" name="scheduled_action" value="<?php echo esc_attr( $scheduled_action ); ?>">

  <table class="form-table">
    <tr class="form-field form-required">
      <th scope="row"><?php _e( 'Group' ); ?></th>
      <td>
        <?php echo $group->name; ?>
        <p class="description"><?php echo sprintf( _n( 'You have only 1 site in this group.', 'You have %d sites in this group.', (int) $group->nrsites, 'baufm' ), (int) $group->nrsites ); ?></p>
      </td>
    </tr>

    <tr class="form-field form-required">
      <th scope="row"><?php _e( 'Sites' ); ?></th>
      <td>
        <p> You are about to update the following sites below. </p>
        <ul>
        <?php $websites = MainWPDB::Instance()->getWebsitesByGroupId( $group_id ); ?>
        <?php foreach ( $websites as $website ) : ?>
        <li><a href="<?php echo esc_url( $website->url ); ?>"><?php echo esc_html( $website->name ); ?></a></li>
        <?php endforeach; ?>
        </ul>
      </td>
    </tr>
  </table>

  <?php submit_button( __( 'Update Now', 'baufm' ), 'primary', 'baufm_update_now', false ); ?>
</form>
