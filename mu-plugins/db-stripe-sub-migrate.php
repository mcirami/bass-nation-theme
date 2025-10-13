<?php
/**
 * WP-CLI command: migrate Braintree members to Stripe subscriptions and link to PMPro.
 *
 * Usage (dry run first):
 *   wp dbstripe migrate --csv=wp-content/uploads/stripe_migrate.csv --dry-run=1
 *
 * Live:
 *   wp dbstripe migrate --csv=wp-content/uploads/stripe_migrate.csv
 */

use Stripe\Stripe;

if ( defined('WP_CLI') && WP_CLI) {

	// Ensure PMPro is loaded (some CLI invocations skip plugins)
	if (!defined('PMPRO_DIR')) {
		$pmpro_bootstrap = WP_PLUGIN_DIR . '/paid-memberships-pro/paid-memberships-pro.php';
		if (file_exists($pmpro_bootstrap)) {
			include_once $pmpro_bootstrap;
		}
	}

	// Ensure PMPro and Stripe lib are loaded
	if (!class_exists('\\Stripe\\Stripe')) {
		$candidates = array_filter([
			defined('PMPRO_DIR') ? PMPRO_DIR . '/includes/lib/Stripe/init.php' : null,               // PMPro’s bundled SDK (older versions)
			WP_PLUGIN_DIR . '/paid-memberships-pro/includes/lib/Stripe/init.php',                    // direct path (case: Stripe)
			WP_PLUGIN_DIR . '/paid-memberships-pro/vendor/stripe/stripe-php/init.php',               // PMPro with vendor dir
			ABSPATH . 'vendor/autoload.php',                                                         // site-level Composer
		], 'file_exists');

		foreach ($candidates as $file) {
			require_once $file;
			if (class_exists('\Stripe\Stripe')) break;
		}
	}

	if (!class_exists('\Stripe\Stripe')) {
		WP_CLI::error('Stripe SDK not found. Ensure PMPro is active or install stripe/stripe-php via Composer and try again.');
	}

	class DB_Stripe_Migrate_CLI {
		private $price_map = [
			1 => 'price_1S1DCDLKwspvjncWXvulavzB', // Monthly
			2 => 'price_1S1DCpLKwspvjncWfNRYWC3k',   // Every 3 months
			3 => 'price_1S1DD9LKwspvjncW2XWSsmUP',  // Every 6 months
			4 => 'price_1S1DDVLKwspvjncWbPmfG260',
		];

		/**
		 * wp dbstripe migrate --csv=path --dry-run=1
		 */
		public function migrate($args, $assoc_args) {
			$csv = isset($assoc_args['csv']) ? $assoc_args['csv'] : '';
			$dry = !empty($assoc_args['dry-run']);
			if (!$csv || !file_exists(ABSPATH . $csv) && !file_exists($csv)) {
				WP_CLI::error("CSV not found: $csv");
				return;
			}
			$path = file_exists($csv) ? $csv : ABSPATH . $csv;

			$secret = function_exists('pmpro_getOption') ? pmpro_getOption('stripe_secretkey') : '';

			if (!$secret && defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY) {
				$secret = STRIPE_SECRET_KEY;
			}

			if (!$secret && getenv('STRIPE_SECRET_KEY')) {
				$secret = getenv('STRIPE_SECRET_KEY');
			}

			if (!$secret) {
				WP_CLI::error('Stripe secret key not configured. Set it in PMPro settings, or define STRIPE_SECRET_KEY in wp-config.php, or export STRIPE_SECRET_KEY in your shell.');
			}
			if (!$secret) WP_CLI::error('Stripe secret key not configured in PMPro.');
			Stripe::setApiKey($secret);

			$env = pmpro_getOption('gateway_environment');
			$rows = $this->read_csv($path);
			WP_CLI::log("Loaded " . count($rows) . " rows. Env: $env. Dry-run: " . ($dry ? 'yes' : 'no'));

			$processed = 0; $skipped = 0; $created = 0; $linked = 0;

			foreach ($rows as $i => $r) {
				$rowno = $i + 2; // header is row 1
				// Normalize fields
				$email   = isset($r['email']) ? sanitize_email($r['email']) : '';
				$uid     = isset($r['wp_user_id']) ? (int)$r['wp_user_id'] : 0;
				$level   = isset($r['pmpro_level_id']) ? (int)$r['pmpro_level_id'] : 0;
				$cus     = isset($r['stripe_customer_id']) ? trim($r['stripe_customer_id']) : '';
				$price   = isset($r['price_id']) && $r['price_id'] ? trim($r['price_id']) : ($this->price_map[$level] ?? '');
				$anchor  = isset($r['anchor_date']) ? trim($r['anchor_date']) : '';

				if (!$uid && $email) {
					$u = get_user_by('email', $email);
					$uid = $u ? (int)$u->ID : 0;
				}
				if (!$uid) {
					$skipped++;
					WP_CLI::warning("Row $rowno: no user found for email={$email}");
					continue;
				}
				if (!$level || !$price) {
					$skipped++;
					WP_CLI::warning("Row $rowno: missing level/price (level_id={$level}, price_id={$price})");
					continue;
				}
				if (!$cus) {
					$skipped++;
					WP_CLI::warning("Row $rowno: missing stripe_customer_id for user_id={$uid} ({$email})");
					continue;
				}

                $user = get_userdata($uid);
                if (!$user) {
					$skipped++;
					WP_CLI::warning("Row $rowno: WP user $uid not found");
					continue;
				}

                // Determine billing start (anchor) as UNIX timestamp (end of day local)
                $anchor_ts = 0;
                if ($anchor) {
                    $anchor_ts = strtotime($anchor . ' 23:59:59 ' . wp_timezone_string());
                } else {
                    // Fallbacks: membership enddate, else last order + cycle
                    $anchor_ts = $this->guess_anchor_from_wp($uid, $level);
                }
                if (!$anchor_ts || $anchor_ts < time()) {
                    // If anchor in past, set to +1 hour so Stripe can create the sub and bill soon
                    $anchor_ts = time() + 3600;
                }

                // Already linked? If a PMPro order exists with a Stripe sub id, skip creation
                $existing_sub = get_user_meta($uid, 'db_stripe_sub_id', true);
                if ($existing_sub) {
                    WP_CLI::log("Row $rowno: already linked to $existing_sub; skipping create.");
                    $skipped++; continue;
                }

                // Ensure customer exists and has a default payment method
                try {
                    $customer = \Stripe\Customer::retrieve($cus);
                } catch (Exception $e) {
                    $skipped++; WP_CLI::warning("Row $rowno: Stripe customer $cus not found: " . $e->getMessage()); continue;
                }
                if (empty($customer->invoice_settings) || empty($customer->invoice_settings->default_payment_method)) {
                    WP_CLI::warning("Row $rowno: Stripe customer $cus has no default payment method. Skipping.");
                    $skipped++; continue;
                }

                // Create the subscription in Stripe (trial until anchor)
                $sub = null;
                if ($dry) {
                    WP_CLI::log("Row $rowno DRY: would create sub for cus=$cus price=$price trial_end=" . gmdate('c', $anchor_ts));
                } else {
                    try {
                        $sub = \Stripe\Subscription::create([
                            'customer' => $cus,
                            'items'    => [['price' => $price]],
                            'trial_end' => $anchor_ts, // no charge until this date
                            'proration_behavior' => 'none',
                            'payment_behavior'   => 'default_incomplete',
                            'metadata' => [
                                'wp_user_id' => (string)$uid,
                                'pmpro_level_id' => (string)$level,
                                'migration' => 'braintree_to_stripe',
                                'site' => home_url(),
                                'email' => $user->user_email,
                            ],
                        ]);
                        $created++;
                        WP_CLI::success("Row $rowno: created Stripe sub {$sub->id} (bills after " . gmdate('c', $anchor_ts) . ")");
                    } catch (Exception $e) {
                        $skipped++; WP_CLI::warning("Row $rowno: failed to create sub: " . $e->getMessage()); continue;
                    }
                }

                // Link to PMPro so webhooks match future renewals
                if (!$dry && $sub) {
                    // Save customer id for PMPro
                    update_user_meta($uid, 'pmpro_stripe_customerid', $cus);

                    // Create a zero-$ order to store subscription id (so PMPro can look it up on webhooks)
                    if (!class_exists('MemberOrder') && defined('PMPRO_DIR')) {
                        require_once PMPRO_DIR . '/classes/class.memberorder.php';
                    }
                    $order = new MemberOrder();
                    $order->user_id = $uid;
                    $order->membership_id = $level;
                    $order->gateway = 'stripe';
                    $order->gateway_environment = pmpro_getOption('gateway_environment');
                    $order->PaymentAmount = 0;
                    $order->subtotal = 0;
                    $order->tax = 0;
                    $order->total = 0;
                    $order->InitialPayment = 0;
                    $order->status = 'success';
                    $order->payment_transaction_id = ''; // none yet
                    $order->subscription_transaction_id = $sub->id; // critical
                    $order->notes = 'Imported from Braintree; Stripe sub ' . $sub->id . ' created ' . current_time('mysql');
                    $order->saveOrder();

                    // Optional: mark in user meta so we don't process twice
                    update_user_meta($uid, 'db_stripe_sub_id', $sub->id);

                    $linked++;
                }

                $processed++;
                // Throttle a bit to be gentle on API limits
                if (!$dry) usleep(150000); // 0.15s
            }

            WP_CLI::log("Processed: $processed; Created subs: $created; Linked orders: $linked; Skipped: $skipped");
        }

        private function read_csv($path) {
            $rows = [];
            if (($h = fopen($path, 'r')) !== false) {
                $header = fgetcsv($h);
                $header = array_map(function($h){ return strtolower(trim($h)); }, $header);
                while (($data = fgetcsv($h)) !== false) {
                    $row = [];
                    foreach ($header as $i => $key) $row[$key] = $data[$i] ?? '';
                    $rows[] = $row;
                }
                fclose($h);
            }
            return $rows;
        }

        private function guess_anchor_from_wp($user_id, $level_id) {
            // 1) membership enddate if set
            if (function_exists('pmpro_getMembershipLevelsForUser')) {
                $levels = pmpro_getMembershipLevelsForUser($user_id);
                if (!empty($levels)) {
                    foreach ($levels as $lvl) {
                        if ((int)$lvl->id === (int)$level_id && !empty($lvl->enddate)) {
                            return strtotime($lvl->enddate);
                        }
                    }
                }
            }
            // 2) last successful order date + cycle
            global $wpdb;
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->pmpro_membership_orders} WHERE user_id=%d AND membership_id=%d AND status='success' ORDER BY id DESC LIMIT 1",
                $user_id, $level_id
            ));
            if ($order) {
                // Map level to cycle in seconds (fallback)
                $cycle = [1 => '+1 month', 2 => '+3 months', 3 => '+6 months', 4 => '+1 year'][$level_id] ?? '+1 month';
                return strtotime($cycle, strtotime($order->timestamp));
            }
            return 0;
        }
    }

    WP_CLI::add_command('dbstripe', 'DB_Stripe_Migrate_CLI');
}

if (defined('WP_CLI') && WP_CLI && class_exists('\Stripe\Stripe')) {
	WP_CLI::add_command('dbstripe ensure-defaults', function($args, $assoc){
		$csv = $assoc['csv'] ?? '';
		if (!$csv || (!file_exists($csv) && !file_exists(ABSPATH.$csv))) {
			WP_CLI::error("CSV not found: $csv");
		}
		$path = file_exists($csv) ? $csv : ABSPATH.$csv;

		// get Stripe key same way you do in migrate()
		$secret = function_exists('pmpro_getOption') ? pmpro_getOption('stripe_secretkey') : '';
		if (!$secret && defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY) $secret = STRIPE_SECRET_KEY;
		if (!$secret && getenv('STRIPE_SECRET_KEY')) $secret = getenv('STRIPE_SECRET_KEY');
		if (!$secret) WP_CLI::error('Stripe secret key not configured.');
		\Stripe\Stripe::setApiKey($secret);

		// read csv
		$h = fopen($path, 'r'); $header = fgetcsv($h);
		$map = [];
		foreach ($header as $i => $col) $map[strtolower(trim($col))] = $i;

		$fixed_pm = 0; $fixed_src = 0; $skipped = 0;
		while (($row = fgetcsv($h)) !== false) {
			$cus = trim($row[$map['stripe_customer_id']] ?? '');
			if (!$cus) { $skipped++; continue; }

			// 1) Prefer PaymentMethods
			$pms = \Stripe\PaymentMethod::all(['customer' => $cus, 'type' => 'card', 'limit' => 1]);
			if (!empty($pms->data)) {
				$pm = $pms->data[0]->id;
				\Stripe\Customer::update($cus, ['invoice_settings' => ['default_payment_method' => $pm]]);
				WP_CLI::log("Set default_payment_method for $cus to $pm");
				$fixed_pm++;
				usleep(120000);
				continue;
			}

			// 2) Fallback to legacy card sources
			$cust = \Stripe\Customer::retrieve($cus, ['expand' => ['sources']]);
			$source = $cust->default_source ?: ((isset($cust->sources->data[0])) ? $cust->sources->data[0]->id : null);
			if ($source) {
				\Stripe\Customer::update($cus, ['default_source' => $source]);
				WP_CLI::log("Set default_source for $cus to $source");
				$fixed_src++;
				usleep(120000);
				continue;
			}

			WP_CLI::warning("No PMs or sources for $cus — needs manual attention.");
			$skipped++;
		}
		fclose($h);
		WP_CLI::success("Defaults set: PM=$fixed_pm, sources=$fixed_src, skipped=$skipped");
	});
}

// wp cli: wp dbstripe cleanup-paypal [--apply=1]
if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::add_command('dbstripe cleanup-paypal', function($args, $assoc){
		$apply = !empty($assoc['apply']);

		// Load Stripe SDK (same loader you used elsewhere)
		if (!class_exists('\Stripe\Stripe')) {
			if (defined('PMPRO_DIR') && file_exists(PMPRO_DIR . '/includes/lib/Stripe/init.php')) {
				require_once PMPRO_DIR . '/includes/lib/Stripe/init.php';
			} elseif (file_exists(WP_PLUGIN_DIR . '/paid-memberships-pro/vendor/stripe/stripe-php/init.php')) {
				require_once WP_PLUGIN_DIR . '/paid-memberships-pro/vendor/stripe/stripe-php/init.php';
			} elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
				require_once ABSPATH . 'vendor/autoload.php';
			}
		}
		if (!class_exists('\Stripe\Stripe')) WP_CLI::error('Stripe SDK not found.');

		$secret = function_exists('pmpro_getOption') ? pmpro_getOption('stripe_secretkey') : '';
		if (!$secret && defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY) $secret = STRIPE_SECRET_KEY;
		if (!$secret && getenv('STRIPE_SECRET_KEY')) $secret = getenv('STRIPE_SECRET_KEY');
		if (!$secret) WP_CLI::error('Stripe secret key not configured.');
		\Stripe\Stripe::setApiKey($secret);

		global $wpdb;
		$t = $wpdb->pmpro_membership_orders;

		// Gateways to treat as PayPal (adjust if your DISTINCT query showed other slugs)
		$pp = array('paypalexpress','paypal','paypalstandard');

		// Find users who have ANY successful PayPal order AND also have a Stripe sub order
		$placeholders = implode(',', array_fill(0, count($pp), '%s'));
		$sql = $wpdb->prepare("
            SELECT u.ID AS user_id, u.user_email, s.membership_id, s.id AS stripe_order_id, s.subscription_transaction_id
            FROM {$wpdb->users} u
            JOIN $t s
              ON s.user_id = u.ID
             AND s.gateway = 'stripe'
             AND s.subscription_transaction_id <> ''
            WHERE EXISTS (
                SELECT 1 FROM $t p
                 WHERE p.user_id = u.ID
                   AND p.status = 'success'
                   AND p.gateway IN ($placeholders)
            )
            ORDER BY u.ID, s.membership_id, s.id
        ", $pp);

		$rows = $wpdb->get_results($sql);
		$candidates = count($rows);
		WP_CLI::log("Candidates with PayPal history + Stripe sub orders: $candidates");

		if (!$candidates) {
			WP_CLI::success('Done. (No candidates matched.)');
			return;
		}

		$cancelled = 0; $deleted = 0; $skipped = 0;
		foreach ($rows as $r) {
			$sub_id = trim($r->subscription_transaction_id);
			if (strpos($sub_id, 'sub_') !== 0) { $skipped++; continue; }

			// Try to retrieve the subscription
			try {
				$sub = \Stripe\Subscription::retrieve($sub_id);
				$status = $sub ? $sub->status : 'unknown';
			} catch (\Exception $e) {
				WP_CLI::warning("Could not retrieve $sub_id for user {$r->user_id}: " . $e->getMessage());
				$skipped++; continue;
			}

			WP_CLI::log("User {$r->user_id} ({$r->user_email}) level {$r->membership_id}: found Stripe sub $sub_id (status=$status) but user has PayPal history.");

			if ($apply) {
				// Cancel the accidental Stripe sub (keep PayPal as source of truth)
				if ($sub && $sub->status !== 'canceled') {
					try {
						// Prefer instance cancel() if available in your SDK
						if (method_exists($sub, 'cancel')) {
							// Immediate cancel, no invoice/proration
							$sub->cancel(['invoice_now' => false, 'prorate' => false]);
						} else {
							// Fallback for SDKs without instance cancel(): cancel at period end
							\Stripe\Subscription::update($sub_id, ['cancel_at_period_end' => true]);
						}
						WP_CLI::log("  - Canceled $sub_id");
						$cancelled++;
					} catch (\Exception $e) {
						WP_CLI::warning("  - Cancel failed for $sub_id: " . $e->getMessage());
					}
				}

				// Remove the PMPro order that stored the Stripe sub (likely $0 “imported”)
				if (!class_exists('MemberOrder') && defined('PMPRO_DIR')) {
					require_once PMPRO_DIR . '/classes/class.memberorder.php';
				}

				$looks_zero = (float) $wpdb->get_var(
						$wpdb->prepare("SELECT total FROM $t WHERE id=%d", $r->stripe_order_id)
					) == 0.0;

				$notes = (string) $wpdb->get_var(
					$wpdb->prepare("SELECT notes FROM $t WHERE id=%d", $r->stripe_order_id)
				);
				$looks_imported = stripos($notes, 'import') !== false;

				if (class_exists('MemberOrder') && ($looks_zero || $looks_imported)) {
					$o = new MemberOrder($r->stripe_order_id);
					if (method_exists($o, 'deleteMe')) {
						$o->deleteMe();
						$deleted++;
						WP_CLI::log("  - Deleted PMPro order #{$r->stripe_order_id}");
					}
				}

				// Optional: clear helper meta you set during migration
				delete_user_meta($r->user_id, 'db_stripe_sub_id');
			}
		}

		WP_CLI::success("Processed: $candidates; canceled: $cancelled; deleted orders: $deleted; skipped: $skipped. " . ($apply ? 'APPLIED.' : 'DRY-RUN.'));
	});
}
