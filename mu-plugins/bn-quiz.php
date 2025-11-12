<?php
/**
 * BN quiz render endpoint (supports Quiz Maker and Watu)
 * GET /wp-json/bn/v1/quiz?id=123&provider=quizmaker
 */
add_action('rest_api_init', function () {
	register_rest_route('bn/v1', '/quiz', [
		'methods'  => 'GET',
		'permission_callback' => '__return_true',
		'callback' => function (WP_REST_Request $req) {
			$id       = absint($req->get_param('id'));
			$provider = strtolower(sanitize_text_field($req->get_param('provider') ?: 'quizmaker'));
			if (!$id) {
				return new WP_Error('no_id', 'Missing id', ['status' => 400]);
			}

			// Ensure front assets are available on this view.
			// Many quiz plugins only enqueue on normal page loads; because we're rendering via REST,
			// we proactively enqueue their public assets here if available.
			if ($provider === 'quizmaker') {
				// Quiz Maker (Ays) shortcode is typically: [ays_quiz id="123"]
				// If the plugin registered public handles, try enqueuing them safely:
				// (Handles vary by version; enqueuing is best-effort + harmless if not registered.)
				wp_enqueue_style('ays-quiz-public');
				wp_enqueue_script('ays-quiz-public');
				wp_enqueue_script('ays-quiz-front');
				$shortcode = sprintf('[ays_quiz id="%d"]', $id);
			} elseif ($provider === 'watu') {
				wp_enqueue_style('watu-style');
				wp_enqueue_script('watu-front');
				$shortcode = sprintf('[watu %d]', $id);
			} else {
				return new WP_Error('bad_provider', 'Unknown provider', ['status' => 400]);
			}

			// Render the shortcode
			$html = do_shortcode($shortcode);

			// Return just the HTML payload; front assets are enqueued above for the page footer.
			return new WP_REST_Response(['html' => $html, 'provider' => $provider, 'id' => $id], 200);
		},
	]);
});

// Global bucket for this page view
$GLOBALS['bn_quiz_ids_runtime'] = [];

/** Call this from your lesson loop when you know a quiz id */
function bn_register_quiz_id( $quiz_id ) {
	$quiz_id = (int) $quiz_id;
	if ( $quiz_id > 0 ) {
		$GLOBALS['bn_quiz_ids_runtime'][$quiz_id] = $quiz_id; // de-dupe by key
	}
}

/** Default provider for the footer cache; lets templates/other code add more via filter */
add_filter('bn_quiz_ids_for_page', function ($ids) {
	$ids = array_map('intval', (array) $ids);
	// merge in anything collected during this request
	if (!empty($GLOBALS['bn_quiz_ids_runtime'])) {
		$ids = array_merge($ids, array_values($GLOBALS['bn_quiz_ids_runtime']));
	}
	$ids = array_values(array_unique(array_filter($ids)));
	// Fallback: if still empty, you can optionally load ALL quizzes (see Option 3), but prefer not to.
	return $ids;
});

add_action('wp_footer', function () {
	$title = get_the_title();
	if (!is_page('lessons') && !str_contains(strtoLower($title), "course") ) return; // adjust your condition

	// List all quiz IDs you might show from the modal this page view.
	// If you don't know them at build-time, you can echo them into the page from PHP.
	$quiz_ids = apply_filters('bn_quiz_ids_for_page', []); // <-- replace with real IDs
	if (empty($quiz_ids)) return; // nothing to cache

	echo '<div id="bn-quiz-cache" aria-hidden="true" style="display:none!important;visibility:hidden; position:absolute; left:-99999px; top:auto; width:0; height:0; overflow:hidden;">';
	foreach ($quiz_ids as $qid) {
		$qid = (int) $qid;
		if ($qid > 0) {
			echo '<div class="bn-quiz-holder" id="bn-quiz-' . $qid . '" style="display:none">';
			// This renders the real Quiz Maker shortcode, which enqueues its JS/CSS and binds events.
			echo do_shortcode('[ays_quiz id="' . $qid . '"]');
			echo '</div>';
		}
	}
	echo '</div>';
}, 1);
