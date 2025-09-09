<?php
/**
 * Template: Levels
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
$pmpro_levels = apply_filters( 'pmpro_levels_array', $pmpro_levels );

$level_groups  = pmpro_get_level_groups_in_order();
$isWinBackVisit = db_is_winback_visit();
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<?php if($pmpro_msg)
	{
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
		<?php
	} ?>
	<section id="pmpro_levels" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_levels' ) ); ?>" class="pmpro_checkout">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
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
			?>
			<div id="pmpro_level_group-<?php echo esc_attr( $level_group->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_level_group', 'pmpro_level_group-' . esc_attr( $level_group->id ) ) ); ?>">
				<?php
					if ( count( $level_groups ) > 1  ) {
						?>
						<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php echo esc_html( $level_group->name ); ?></h2>
						<?php
					}
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
			<?php
			if ( count( $level_groups ) > 1  ) {
				?>
				<!-- <h2><?php echo esc_html( $level_group->name ); ?></h2> -->
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
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
				<?php
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
					<div class="column <?php
					if (($level->id == 4 && $isWinBackVisit) || ($level->id == 3 && !$isWinBackVisit)) {
						echo "col_highlight";
					}; ?>">
						<div class="column_content">
							<div class="full_width">
								<?php if ( ($level->id == 4 && $isWinBackVisit) ||
								           ($level->id == 3 && !$isWinBackVisit)) : ?>
									<div class="highlight">
										<p>Most Bass For Your Buck!</p>
									</div>
								<?php  else : ?>
									<div class="column_heading full_width">
										<h2>
											FREE
											<?php if ($isWinBackVisit) : ?>
												7
											<?php else : ?>
												3
											<?php endif; ?>
											Day Trial
										</h2>
									</div>
								<?php endif; ?>
							</div>
							<div class="full_width cost <?php
							if (($level->id == 3 && !$isWinBackVisit) || ($level->id == 4 && $isWinBackVisit)){
								echo "highlighted";
							}
							?>">
							<?php if ($isWinBackVisit) : ?>
								<?php if ($level->id == 1) : ?>
									<h4>First Month</h4>
									<div class="discount_text">
										<h3>$7.99</h3>
										<h3>
											<span class="original_price">
												$9.99
												<span class="slash"></span>
											</span>
										</h3>
										<p>20% OFF</p>
									</div>
								<?php elseif ($level->id == 2) : ?>
									<h4>First 3 Months</h4>
									<div class="discount_text">
										<h3>$22.99</h3>
										<h3 class="relative">
											<span class="original_price">
												$28.99
												<span class="slash"></span>
											</span>
										</h3>
										<p>10% OFF</p>
									</div>
								<?php elseif ($level->id == 3) : ?>
									<h4>First 6 Months</h4>
									<div class="discount_text">
										<h3>$43.99</h3>
										<h3>
											<span class="original_price">
												$54.99
												<span class="slash"></span>
											</span>
										</h3>
										<p>20% OFF</p>
									</div>
								<?php elseif ($level->id == 4) : ?>
									<h4>First Year</h4>
									<div class="discount_text">
										<h3>$74.99</h3>
										<h3>
											<span class="original_price">
												$99.99
												<span class="slash"></span>
											</span>
										</h3>
										<p>25% OFF</p>
									</div>
								<?php endif; ?>
							<?php else: ?>
								<?php if ($level->id == 1) : ?>
									<p>then only</p>
									<h3>$9.99</h3>
									<h4>a month</h4>
								<?php elseif ($level->id == 2 ) :?>
									<p>then only</p>
									<h3>$28.99</h3>
									<h4>Every 3 months</h4>
								<?php elseif ($level->id == 3) : ?>
									<p>only</p>
									<h3>$54.99</h3>
									<h4>Every 6 months</h4>
								<?php elseif ($level->id == 4) : ?>
									<p>then only</p>
									<h3>$99.99</h3>
									<h4>Every Year</h4>
								<?php endif; ?>
							<?php endif; ?>
							</div> <!-- cost -->
							<div class="description full_width">
								<div class="top">
									<p>Recurring</p>
									<p>Bass Nation Membership</p>
								</div>
								<ul>
									<li><p>FREE for
											<?php if ($isWinBackVisit) : ?>
												7
											<?php else : ?>
												3
											<?php endif; ?>
											days</p></li>
									<li><p>ALL Complete Lessons</p></li>
									<li><p>Lesson Commenting</p></li>
									<li><p>Bass Nation Forum Access</p></li>
									<li><p>Member Directory</p></li>
									<li><p>Messaging System</p></li>
								</ul>
							</div>
							<div class="button_wrap full_width">
								<?php if(empty($current_user->membership_level->ID)) { ?>
									<a class="pmpro_btn pmpro_btn-select button yellow" href="<?php echo db_checkout_url_with_passthrough( $level->id); ?>"><?php _e('GET STARTED!', 'pmpro');?>
										<span>
											<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
										</span>
									</a>
								<?php } elseif ( $level->id != $current_user->membership_level->ID) { ?>
									<a class="pmpro_btn pmpro_btn-select button yellow" href="<?php echo db_checkout_url_with_passthrough( $level->id); ?>"><?php _e('GET STARTED!', 'pmpro');?>
										<span>
											<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
										</span>
									</a>
								<?php } elseif ($level->id == $current_user->membership_level->ID) { ?>

									<?php
									//if it's a one-time-payment level, offer a link to renew
									if( pmpro_isLevelExpiringSoon( $current_user->membership_level) && $current_user->membership_level->allow_signups ) {
										?>
										<a class="pmpro_btn pmpro_btn-select button yellow" href="<?php echo db_checkout_url_with_passthrough( $level->id); ?>"><?php _e('Renew', 'pmpro');?>
										<span>
											<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
										</span>
										</a>
										<?php
									} else {
										?>
										<button disabled class="pmpro_btn disabled button yellow"><?php _e('Your&nbsp;Level', 'pmpro');?></button>
										<?php
									}
									?>

								<?php } ?>
							</div><!-- button wrap -->
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
			</div><!-- end pmpro_card_content -->
		</div><!-- end pmpro_card -->
		<?php } ?>
		</div> <!-- end pmpro_section_content -->
	</section>
</div>
