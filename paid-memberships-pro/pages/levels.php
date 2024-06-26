<?php
/**
 * Template: Levels
 * Version: 3.0.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
$pmpro_levels = apply_filters( 'pmpro_levels_array', $pmpro_levels );

$level_groups  = pmpro_get_level_groups_in_order();

if($pmpro_msg)
{
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
	<?php
}
?>
<div id="pmpro_levels_table" class="pmpro_checkout">
<?php
foreach ( $level_groups as $level_group ) {
	$levels_in_group = pmpro_get_level_ids_for_group( $level_group->id );

	// The pmpro_levels_array filter is sometimes used to hide levels from the levels page.
	// Let's make sure that every level in the group should still be displayed.
	$levels_to_show_for_group = array();
	foreach ( $pmpro_levels as $level ) {
		if ( in_array( $level->id, $levels_in_group ) ) {
			$levels_to_show_for_group[] = $level;
		}
	}

	if ( empty( $levels_to_show_for_group ) ) {
		continue;
	}

	if ( count( $level_groups ) > 1  ) {
		?>
		<h2><?php echo esc_html( $level_group->name ); ?></h2>
		<?php
		if ( ! empty( $level_group->allow_multiple_selections ) ) {
			?>
			<p><?php esc_html_e( 'You may select multiple levels from this group.', 'paid-memberships-pro' ); ?></p>
			<?php
		} else {
			?>
			<p><?php esc_html_e( 'You may select only one level from this group.', 'paid-memberships-pro' ); ?></p>
			<?php
		}
	}

	?>
	<!--<table class="<?php /*echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_levels_table pmpro_checkout', 'pmpro_levels_table' ) ); */?>">
		<thead>
		<tr>
			<th><?php /*esc_html_e('Level', 'paid-memberships-pro' );*/?></th>
			<th><?php /*esc_html_e('Price', 'paid-memberships-pro' );*/?></th>
			<th>&nbsp;</th>
		</tr>
		</thead>
		<tbody>-->
		<?php
		$count = 0;
		foreach($levels_to_show_for_group as $level)
		{?>
			<div class="column">
				<div class="full_width <?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if($current_level == $level) { ?> active<?php } ?>">
					<?php if ($level->id == 4) : ?>
						<div class="highlight">
							<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bass-clef.png" alt="Bass Clef Image"/><p>Most Bass For Your Buck!</p>
						</div>
					<?php endif; ?>
					<div class="column_heading full_width">
						<?php if ($level->id == 1) : ?>
							<!--<p>With FREE 3-Day Trial</p>-->
							<h2>FREE 3 Day Trial</h2>
							<p>then only $9.99/month</p>
						<?php elseif ($level->id == 2 ) :?>
							<h2>FREE 3 Day Trial</h2>
							<p>then only $28.99/3 months</p>
						<?php elseif ($level->id == 3) : ?>
							<h2>FREE 3 Day Trial</h2>
							<p>then only $54.99/6 months</p>
						<?php elseif ($level->id == 4) : ?>
							<h2>FREE 3 Day Trial</h2>
							<p>then only $99.99/year</p>
						<?php endif; ?>
					</div>
				</div>
				<div class="full_width cost">
					<?php if ($level->id == 1) : ?>
						<h3>Monthly</h3>
					<?php elseif ($level->id == 2 ) :?>
						<h3>3 Months</h3>
					<?php elseif ($level->id == 3) : ?>
						<h3>6 Months</h3>
					<?php elseif ($level->id == 4) : ?>
						<h3>Annual</h3>
					<?php endif; ?>
					<p>Recurring</p>
					<p>Bass Nation Membership</p>
					<div class="button_wrap full_width">
						<?php if(empty($current_user->membership_level->ID)) { ?>
							<a class="pmpro_btn pmpro_btn-select button round_button" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('GET STARTED!', 'pmpro');?></a>
						<?php } elseif ( !$current_level ) { ?>
							<a class="pmpro_btn pmpro_btn-select button round_button" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('GET STARTED!', 'pmpro');?></a>
						<?php } elseif($current_level) { ?>

							<?php
							//if it's a one-time-payment level, offer a link to renew
							if( pmpro_isLevelExpiringSoon( $current_user->membership_level) && $current_user->membership_level->allow_signups ) {
								?>
								<a class="pmpro_btn pmpro_btn-select button round_button" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('Renew', 'pmpro');?></a>
								<?php
							} else {
								?>
								<a class="pmpro_btn disabled button round_button" href="<?php echo pmpro_url("account")?>"><?php _e('Your&nbsp;Level', 'pmpro');?></a>
								<?php
							}
							?>

						<?php } ?>
					</div><!-- button wrap -->
				</div> <!-- cost -->
				<div class="description full_width">
					<ul>
						<li><p>FREE for 3 days</p></li>
						<li><p>ALL Complete Lessons</p></li>
						<li><p>Lesson Commenting</p></li>
						<li><p>Bass Nation Forum Access</p></li>
						<li><p>Member Directory</p></li>
						<li><p>Messaging System</p></li>
					</ul>
				</div>
			</div><!-- column -->
			<?php
			/*$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );
			$has_level = ! empty( $user_level );
			*/?><!--
			<tr class="<?php /*if($count++ % 2 == 0) { */?>odd<?php /*} */?><?php /*if( $has_level ) { */?> active<?php /*} */?>">
				<th><?php /*echo $has_level ? '<strong>' . esc_html( $level->name ) . '</strong>' : esc_html( $level->name )*/?></th>
				<td>
					<?php
/*					$cost_text = pmpro_getLevelCost($level, true, true);
					$expiration_text = pmpro_getLevelExpiration($level);
					if(!empty($cost_text) && !empty($expiration_text))
						echo wp_kses_post( $cost_text . "<br />" . $expiration_text );
					elseif(!empty($cost_text))
						echo wp_kses_post( $cost_text );
					elseif(!empty($expiration_text))
						echo wp_kses_post( $expiration_text );
					*/?>
				</td>
				<td>
					<?php /*if ( ! $has_level ) { */?>
						<a aria-label="<?php /*echo esc_attr( sprintf( __('Select the %s membership level', 'paid-memberships-pro' ), $level->name ) ); */?>" class="<?php /*echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); */?>" href="<?php /*echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) */?>"><?php /*esc_html_e('Select', 'paid-memberships-pro' );*/?></a>
					<?php /*} else { */?>
						<?php
/*						//if it's a one-time-payment level, offer a link to renew
						if( pmpro_isLevelExpiringSoon( $user_level ) && $level->allow_signups ) {
							*/?>
							<a aria-label="<?php /*echo esc_attr( sprintf( __('Renew your %s membership level', 'paid-memberships-pro' ), $level->name ) ); */?>" class="<?php /*echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); */?>" href="<?php /*echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) */?>"><?php /*esc_html_e('Renew', 'paid-memberships-pro' );*/?></a>
							<?php
/*						} else {
							*/?>
							<a aria-label="<?php /*echo esc_attr( sprintf( __('View your %s membership account', 'paid-memberships-pro' ), $level->name ) ); */?>" class="<?php /*echo esc_attr( pmpro_get_element_class( 'pmpro_btn disabled', 'pmpro_btn' ) ); */?>" href="<?php /*echo esc_url( pmpro_url( "account" ) ) */?>"><?php /*esc_html_e('Your&nbsp;Level', 'paid-memberships-pro' );*/?></a>
							<?php
/*						}
						*/?>
					<?php /*} */?>
				</td>
			</tr>-->
			<?php
		}
		?>
		<!--</tbody>
	</table>-->
<?php } ?>
</div>
