<?php
/**
 * WP-CLI: Build a Stripe subscription migration CSV by joining Stripe/Braintree exports with PMPro data.
 *
 * Usage:
 *   wp dbmigrate build --stripe=wp-content/uploads/stripe_customers.csv \
 *                      --braintree=wp-content/uploads/braintree_subs.csv \
 *                      --out=wp-content/uploads/stripe_migrate.csv
 *   (the --braintree file is optional; builder will still work without it)
 */

if (defined('WP_CLI') && WP_CLI) {
    class DB_Migrate_Builder_CLI_V2 {
        // Fill with your LIVE Stripe Price IDs
        private $price_map = [
	        1 => 'price_1S1DCDLKwspvjncWXvulavzB', // Monthly
	        2 => 'price_1S1DCpLKwspvjncWfNRYWC3k',   // Every 3 months
	        3 => 'price_1S1DD9LKwspvjncW2XWSsmUP',  // Every 6 months
	        4 => 'price_1S1DDVLKwspvjncWbPmfG260', // Annually
        ];

        public function build($args, $assoc) {
            $stripePath    = $assoc['stripe']    ?? '';
            $braintreePath = $assoc['braintree'] ?? '';
            $outPath       = $assoc['out']       ?? 'wp-content/uploads/stripe_migrate.csv';

            $stripePath    = $this->abs_or_rel($stripePath);
            $braintreePath = $braintreePath ? $this->abs_or_rel($braintreePath) : '';
            $outPath       = $this->abs_or_rel($outPath);

            if (!file_exists($stripePath)) WP_CLI::error("Stripe CSV not found: $stripePath");

            // --- Build maps ---
            $stripe = $this->mapStripeCustomers($stripePath); // ['byEmail'=>[], 'byOldId'=>[]]
            $bt     = $braintreePath && file_exists($braintreePath) ? $this->mapBraintreeSubs($braintreePath) : ['byId'=>[], 'byEmail'=>[], 'emailToId'=>[]];

            // --- Open output ---
            $out = fopen($outPath, 'w');
            fputcsv($out, ['email','wp_user_id','pmpro_level_id','stripe_customer_id','price_id','anchor_date']);

            $users = get_users(['fields' => ['ID','user_email']]);
            $count = 0; $written = 0; $skipped = 0; $noStripe = 0; $noLevel = 0;

            foreach ($users as $u) {
                $count++;
                $uid   = (int) $u->ID;
                $email = strtolower(trim($u->user_email));

                // Active level(s)?
                $levels = function_exists('pmpro_getMembershipLevelsForUser') ? pmpro_getMembershipLevelsForUser($uid) : [];
                if (empty($levels)) continue;

                foreach ($levels as $lvl) {
                    $level_id = (int) $lvl->id;
                    if (empty($this->price_map[$level_id])) { $noLevel++; continue; }

                    // 1) Find Stripe customer ID
                    $cus = '';
                    // by email (if Stripe CSV had email column)
                    if (!empty($stripe['byEmail'][$email])) {
                        $cus = $stripe['byEmail'][$email];
                    }
                    // by old/braintree customer id
                    if (!$cus) {
                        $btCustomerId = get_user_meta($uid, 'pmpro_braintree_customerid', true);
                        if (!$btCustomerId && !empty($bt['emailToId'][$email])) {
                            $btCustomerId = $bt['emailToId'][$email];
                        }
                        if ($btCustomerId && !empty($stripe['byOldId'][$btCustomerId])) {
                            $cus = $stripe['byOldId'][$btCustomerId];
                        }
                    }
                    // last resort: any previously stored Stripe customer id in WP
                    if (!$cus) {
                        $maybe = get_user_meta($uid, 'pmpro_stripe_customerid', true);
                        if ($maybe && strpos($maybe, 'cus_') === 0) $cus = $maybe;
                    }

                    if (!$cus) {
                        $noStripe++;
                        WP_CLI::warning("No Stripe customer for $email (user $uid)");
                        continue; // can’t write this row
                    }

                    // 2) Determine anchor date (trial_end) = when Stripe should start billing
                    $anchor = '';
                    // Prefer Braintree Next Billing Date (by BT customer id or by email)
                    if (!empty($btCustomerId) && !empty($bt['byId'][$btCustomerId])) {
                        $anchor = $bt['byId'][$btCustomerId]; // YYYY-MM-DD
                    } elseif (!empty($bt['byEmail'][$email])) {
                        $anchor = $bt['byEmail'][$email];
                    } elseif (!empty($lvl->enddate)) {
                        // PMPro level enddate if used
                        $anchor = date('Y-m-d', strtotime($lvl->enddate));
                    } else {
                        // Last successful order + cycle length
                        $last = $this->lastSuccessOrder($uid, $level_id);
                        if ($last) {
                            $cycleStr = [1=>'+1 month',2=>'+3 months',3=>'+6 months',4=>'+1 year'][$level_id] ?? '+1 month';
                            $anchor = date('Y-m-d', strtotime($cycleStr, strtotime($last)));
                        }
                    }
                    if (!$anchor) {
                        $anchor = date('Y-m-d', time() + 86400); // safe default = tomorrow
                    }

                    $price = $this->price_map[$level_id];

                    fputcsv($out, [$email, $uid, $level_id, $cus, $price, $anchor]);
                    $written++;
                }
            }

            fclose($out);
            WP_CLI::success("Wrote $written rows to $outPath (checked $count users; skipped=$skipped; noStripe=$noStripe; noLevelMap=$noLevel).\nStripe maps: byEmail=" . count($stripe['byEmail']) . ", byOldId=" . count($stripe['byOldId']) . ". BT maps: byId=" . count($bt['byId']) . ", byEmail=" . count($bt['byEmail']) . ", emailToId=" . count($bt['emailToId']) . ".");
        }

        private function abs_or_rel($p) { return strpos($p, ABSPATH) === 0 ? $p : ABSPATH . ltrim($p, '/'); }

        private function read_csv($path) {
            $rows = [];
            if (($h = fopen($path, 'r')) !== false) {
                $header = fgetcsv($h);
                // Normalize headers (lowercase, strip brackets/spaces)
                $norm = [];
                foreach ($header as $i => $col) {
                    $key = strtolower(trim($col));
                    $key = preg_replace('/\s+/', ' ', $key);
                    $norm[$i] = $key;
                }
                while (($data = fgetcsv($h)) !== false) {
                    $row = [];
                    foreach ($norm as $i => $key) $row[$key] = $data[$i] ?? '';
                    $rows[] = $row;
                }
                fclose($h);
            }
            return $rows;
        }

        // Stripe customers export → build maps by Email and by legacy/old/braintree customer id
        private function mapStripeCustomers($path) {
            $rows = $this->read_csv($path);
            $byEmail = [];
            $byOldId = [];

            foreach ($rows as $r) {
                // Customer ID column (variations: id, ID, customer id)
                $cus = trim($r['id'] ?? $r['customer id'] ?? $r['customer_id'] ?? $r['id'] ?? '');
                if (!$cus || strpos($cus, 'cus_') !== 0) continue;

                // Email column may be missing in your export
                $email = strtolower(trim($r['email'] ?? $r['customer email'] ?? ''));
                if ($email) $byEmail[$email] = $cus;

                // Look for any column that looks like an old/braintree id or metadata key
                foreach ($r as $col => $val) {
                    if (!$val) continue;
                    // Normalize column name
                    $coln = strtolower($col);
                    if (
	                    str_contains( $coln, 'old_customer_id' ) ||
	                    str_contains( $coln, 'braintree_customer_id' ) ||
	                    str_contains( $coln, 'legacy_customer_id' ) ||
	                    str_contains( $coln, 'old customer id' ) ||
	                    str_contains( $coln, 'braintree id' ) ||
	                    str_contains( $coln, 'braintree_customer' ) ||
	                    str_contains( $coln, 'metadata' ) && strpos($coln, 'old') !== false
                    ) {
                        $old = trim($val);
                        if ($old) $byOldId[$old] = $cus;
                    }
                }
            }

            WP_CLI::log('Stripe customers mapped: emails=' . count($byEmail) . ', old_ids=' . count($byOldId));
            return ['byEmail' => $byEmail, 'byOldId' => $byOldId];
        }

        // Braintree subscriptions export → date maps and email→customer_id map
        private function mapBraintreeSubs($path) {
            $rows = $this->read_csv($path);
            $byId = [];
            $byEmail = [];
            $emailToId = [];

            foreach ($rows as $r) {
                $cid   = trim($r['customer id'] ?? $r['customer_id'] ?? $r['customerid'] ?? '');
                $email = strtolower(trim($r['email'] ?? $r['customer email'] ?? ''));
                $next  = trim($r['next billing date'] ?? $r['next_billing_date'] ?? '');
                $pthru = trim($r['paid through date'] ?? $r['paid_through_date'] ?? '');
                $first = trim($r['first bill date'] ?? $r['first_bill_date'] ?? '');

                $candidate = $next ?: $pthru;
                if (!$candidate && $first) $candidate = $first;

                if ($candidate) {
                    $ts = strtotime($candidate);
                    if ($ts) {
                        $date = date('Y-m-d', $ts);
                        if ($cid)   $byId[$cid]     = $date;
                        if ($email) $byEmail[$email] = $date;
                    }
                }
                if ($cid && $email) $emailToId[$email] = $cid;
            }

            WP_CLI::log('Braintree subs mapped: byIdDates=' . count($byId) . ', byEmailDates=' . count($byEmail) . ', emailToId=' . count($emailToId));
            return ['byId' => $byId, 'byEmail' => $byEmail, 'emailToId' => $emailToId];
        }

        private function lastSuccessOrder($user_id, $level_id) {
            global $wpdb;
            $table = $wpdb->pmpro_membership_orders;
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT timestamp FROM $table WHERE user_id=%d AND membership_id=%d AND status='success' ORDER BY id DESC LIMIT 1",
                $user_id, $level_id
            ));
            return $row ? $row->timestamp : null;
        }
    }

    WP_CLI::add_command('dbmigrate', 'DB_Migrate_Builder_CLI_V2');
}
