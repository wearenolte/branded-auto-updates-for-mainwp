<?php

class BAUFM_Schedules {
	/**
	 * Handy constant to indicate that nothing is scheduled or
	 * schedules are off for a particular group.
	 *
	 * @var int
	 */
	const NONE = 8;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * on a daily basis.
	 *
	 * @var int
	 */
	const EVERY_DAY = 9;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Sunday.
	 *
	 * @var int
	 */
	const EVERY_SUNDAY = 0;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Monday.
	 *
	 * @var int
	 */
	const EVERY_MONDAY = 1;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Tuesday.
	 *
	 * @var int
	 */
	const EVERY_TUESDAY = 2;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Wednesday.
	 *
	 * @var int
	 */
	const EVERY_WEDNESDAY = 3;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Thursday.
	 *
	 * @var int
	 */
	const EVERY_THURSDAY = 4;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Friday.
	 *
	 * @var int
	 */
	const EVERY_FRIDAY = 5;

	/**
	 * Handy constant to indicate that we have something scheduled
	 * every Saturday.
	 *
	 * @var int
	 */
	const EVERY_SATURDAY = 6;

	/**
	 * @since 0.2.0
	 */
	public static function get_schedules_text() {
		return array(
			self::NONE 			      => esc_html__( 'Off', 'baufm' ),
			self::EVERY_DAY 	    => esc_html__( 'Everyday', 'baufm' ),
			self::EVERY_SUNDAY    => esc_html__( 'Every Sunday', 'baufm' ),
			self::EVERY_MONDAY    => esc_html__( 'Every Monday', 'baufm' ),
			self::EVERY_TUESDAY   => esc_html__( 'Every Tuesday', 'baufm' ),
			self::EVERY_WEDNESDAY => esc_html__( 'Every Wednesday', 'baufm' ),
			self::EVERY_THURSDAY  => esc_html__( 'Every Thursday', 'baufm' ),
			self::EVERY_FRIDAY    => esc_html__( 'Every Friday', 'baufm' ),
			self::EVERY_SATURDAY  => esc_html__( 'Every Saturday', 'baufm' ),
		);
	}

	/**
	 * @since 0.2.0
	 */
	public static function is_group_schedule_off( $group_id ) {
		return self::NONE === self::get_group_scheduled_day_of_week( $group_id );
	}

	/**
	 * @since 0.2.0
	 */
	public static function is_group_schedule_daily( $group_id ) {
		return self::EVERY_DAY === self::get_group_scheduled_day_of_week( $group_id );
	}

	/**
	 * @since 0.2.0
	 */
	public static function get_group_scheduled_day_of_week( $group_id ) {
		return (int) get_option( "baufm_schedule_in_week_group_$group_id", 0 );
	}

	/**
	 * @since 0.2.0
	 */
	public static function get_group_scheduled_time_of_day( $group_id ) {
		return (int) get_option( "baufm_schedule_in_day_group_$group_id", 0 );
	}

	/**
	 * @since 0.2.0
	 */
	public static function get_group_last_scheduled_update( $group_id ) {
		return (int) get_option( "baufm_last_scheduled_update_$group_id", 0 );
	}

	/**
	 *
	 * @since 0.2.0
	 */
	public static function set_group_last_scheduled_update( $group_id, $timestamp ) {
		update_option( "baufm_last_scheduled_update_$group_id", $timestamp );
	}

	/**
	 * @since 0.2.0
	 */
	public static function set_group_scheduled_day_of_week( $group_id, $timestamp ) {
		update_option( "baufm_schedule_in_week_group_$group_id", $timestamp );
	}

	/**
	 * @since 0.2.0
	 */
	public static function is_group_updated_today( $group_id ) {
		return date_i18n( 'd/m/Y', self::get_group_last_scheduled_update( $group_id ) ) === date_i18n( 'd/m/Y' );
	}

	/**
	 * @since 0.2.0
	 */
	public static function is_group_scheduled_today( $group_id ) {
		return self::get_group_scheduled_day_of_week( $group_id ) === (int) date_i18n( 'w' );
	}

	/**
	 * @since 0.2.0
	 */
	public static function is_group_scheduled_this_hour( $group_id ) {
		return self::get_group_scheduled_time_of_day( $group_id ) <= (int) date_i18n( 'G' );
	}

	/**
	 * @since 0.2.0
	 */
	public static function get_group_scheduled_now( array $site_groups ) {
		$current_group = array();

		if ( 0 === count( $site_groups ) ) {
			return array();
		}

		// Loop through each site group and check if there is an update to do.
		foreach ( $site_groups as $group ) {

			if ( self::is_group_scheduled_today( $group->id ) ) {
				if ( self::is_group_scheduled_this_hour( $group->id ) ) {
					if ( self::is_group_updated_today( $group->id ) ) {
						// No action to take. Already updated.
						MainWPLogger::Instance()->info( 'CRON :: Skipping group info ' . wp_json_encode( $group ) );
						continue;
					} else {
						// We should proceed with the update.

						MainWPLogger::Instance()->info( 'CRON :: Current group info ' . wp_json_encode( $group ) );
						$current_group = $group;
					}
				}
			}
		}

		return $current_group;
	}
}

