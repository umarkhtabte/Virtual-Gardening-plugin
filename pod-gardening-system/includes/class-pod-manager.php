<?php
class TPGS_Pod_Manager
{

    public function __construct()
    {
        add_action('wp_ajax_tpgs_plant_vegetable', array($this, 'plant_vegetable'));
        add_action('wp_ajax_tpgs_update_pod_date', array($this, 'update_pod_date'));
        add_action('wp_ajax_tpgs_reset_pod', array($this, 'reset_pod'));
        add_action('wp_ajax_tpgs_get_pod_details', array($this, 'get_pod_details'));
        add_action('wp_ajax_tpgs_get_pod_html', array($this, 'ajax_get_pod_html'));
    }

    public static function get_user_pods($user_id)
    {
        $pods = array();

        for ($i = 1; $i <= 12; $i++) {
            $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $i, true);
            $pods[$i] = $pod_data ? $pod_data : array(
                'vegetable_id' => 0,
                'date_planted' => '',
                'days_remaining' => 0,
                'status' => 'empty'
            );
        }

        return $pods;
    }

    public function ajax_get_pod_html()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $pod_data = isset($_POST['pod_data']) ? $_POST['pod_data'] : array();

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        wp_send_json_success(array(
            'html' => self::get_pod_html($pod_id, $pod_data)
        ));
    }

    public function get_pod_details()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data)) {
            $pod_data = array(
                'vegetable_id' => 0,
                'date_planted' => '',
                'days_remaining' => 0,
                'status' => 'empty'
            );
        }

        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/pod-detail.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    public static function get_active_pod_count($user_id)
    {
        $pods = self::get_user_pods($user_id);
        $count = 0;

        foreach ($pods as $pod) {
            if ($pod['status'] !== 'empty') {
                $count++;
            }
        }

        return $count;
    }

    /**
 * Award badges when pod count reaches thresholds
 */
// private function award_badges($user_id, $new_pod_count) {
//     if (!function_exists('gamipress_trigger_event')) return;

//     // Trigger check for all thresholds
//     $thresholds = [1, 3, 6, 9, 12];
//     foreach ($thresholds as $threshold) {
//         if ($new_pod_count >= $threshold) {
//             gamipress_trigger_event([
//                 'event' => 'tpgs_pod_threshold',
//                 'user_id' => $user_id,
//                 'pod_count' => $new_pod_count
//             ]);
//         }
//     }
// }

    public function plant_vegetable()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $vegetable_id = isset($_POST['vegetable_id']) ? intval($_POST['vegetable_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $vegetable = TPGS_Vegetable_Manager::get_vegetable($vegetable_id);
        if (!$vegetable) {
            wp_send_json_error('Invalid vegetable');
        }

        // Check if pod is empty
        $current_pod = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (!empty($current_pod) && $current_pod['status'] !== 'empty') {
            wp_send_json_error('Pod is not empty');
        }

        // Update pod data
        $pod_data = array(
            'vegetable_id' => $vegetable_id,
            'date_planted' => current_time('mysql'),
            'days_remaining' => $vegetable['growth_duration'],
            'status' => 'growing'
        );

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data)) {
        $this->update_gamification_stats($user_id, 'planted');
        $active_count = self::get_active_pod_count($user_id);
        $badge_result = $this->evaluate_badges($user_id, $active_count);
        
        wp_send_json_success(array(
            'message' => 'Vegetable planted successfully',
            'pod_html' => self::get_pod_html($pod_id, $pod_data),
            'active_count' => $active_count,
            'badges_updated' => $badge_result['updated'],
            'badges_lost' => $badge_result['lost']
        ));
    }
    }

    public function update_pod_date()
    {
        // Verify nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpgs_nonce')) {
            wp_send_json_error('Invalid nonce', 403);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in', 401);
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';

        // Debugging - log received data
        error_log("Update Pod Request - Pod ID: $pod_id, New Date: $new_date");

        // Validate pod ID
        if ($pod_id < 1 || $pod_id > 12) {
            error_log("Invalid Pod ID received: $pod_id");
            wp_send_json_error('Invalid pod ID. Pod ID must be between 1-12.', 400);
        }

        // Validate date
        if (empty($new_date)) {
            wp_send_json_error('Please select a valid date', 400);
        }

        $date_time = strtotime($new_date);
        if (!$date_time || $date_time > current_time('timestamp')) {
            wp_send_json_error('Invalid date - must be in the past and valid', 400);
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data) || $pod_data['status'] === 'empty') {
            wp_send_json_error('Pod is empty', 400);
        }

        $vegetable = TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']);
        if (!$vegetable) {
            wp_send_json_error('Invalid vegetable in pod', 400);
        }

        // Calculate new days remaining
        $days_passed = floor((current_time('timestamp') - $date_time) / DAY_IN_SECONDS);
        $days_remaining = $vegetable['growth_duration'] - $days_passed;

        if ($days_remaining <= 0) {
            $days_remaining = 0;
            $status = 'ready';

            // Trigger notification if status changed to ready
            if ($pod_data['status'] !== 'ready') {
                TPGS_Notifications::send_harvest_notification($user_id, $pod_id, $pod_data['vegetable_id']);
            }
        } else {
            $status = 'growing';
        }

        // Update pod data
        $pod_data['date_planted'] = date('Y-m-d H:i:s', $date_time);
        $pod_data['days_remaining'] = $days_remaining;
        $pod_data['status'] = $status;

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $pod_data)) {
            // Return updated pod HTML and active count
            $updated_pod_html = self::get_pod_html($pod_id, $pod_data);
            $active_count = self::get_active_pod_count($user_id);

            wp_send_json_success(array(
                'days_remaining' => $days_remaining,
                'status' => $status,
                'status_text' => self::get_status_text($status),
                'pod_html' => $updated_pod_html,
                'active_count' => $active_count
            ));
        } else {
            wp_send_json_error('Failed to update pod date', 500);
        }
    }

    public function reset_pod()
    {
        check_ajax_referer('tpgs_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }

        $user_id = get_current_user_id();
        $pod_id = isset($_POST['pod_id']) ? intval($_POST['pod_id']) : 0;

        if ($pod_id < 1 || $pod_id > 12) {
            wp_send_json_error('Invalid pod ID');
        }

        $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $pod_id, true);
        if (empty($pod_data) || $pod_data['status'] === 'empty') {
            wp_send_json_error('Pod is already empty');
        }

        // Update gamification stats if pod was ready to harvest
        if ($pod_data['status'] === 'ready') {
            $this->update_gamification_stats($user_id, 'harvested');
        }

        // Reset pod
        $empty_pod = array(
            'vegetable_id' => 0,
            'date_planted' => '',
            'days_remaining' => 0,
            'status' => 'empty'
        );

        if (update_user_meta($user_id, 'tpgs_pod_' . $pod_id, $empty_pod)) {
        $active_count = self::get_active_pod_count($user_id);
        $badge_result = $this->evaluate_badges($user_id, $active_count);
        
        if ($pod_data['status'] === 'ready') {
            $this->update_gamification_stats($user_id, 'harvested');
        }
        
        wp_send_json_success(array(
            'message' => 'Pod reset successfully',
            'pod_html' => self::get_pod_html($pod_id, $empty_pod),
            'active_count' => $active_count,
            'badges_updated' => $badge_result['updated'],
            'badges_lost' => $badge_result['lost']
        ));
    }
    }

/**
 * Strict threshold-based badge evaluation
 */
private function evaluate_badges($user_id, $current_pods) {
    if (!class_exists('GamiPress')) return;

    $threshold_map = [
        'seedling' => 1,
        'green-thumb' => 3,
        'crop-master' => 6,
        'harvest-leader' => 9,
        'pod-perfectionist' => 12
    ];

    // Get all gardening badges
    $all_badges = gamipress_get_achievements([
        'post_type' => 'gardening_badges',
        'posts_per_page' => -1
    ]);

    $badges_updated = false;
    $badges_lost = 0;

    foreach ($all_badges as $badge) {
        $slug = $badge->post_name;
        $required_pods = $threshold_map[$slug] ?? 0;
        $has_earned = gamipress_has_user_earned_achievement($badge->ID, $user_id);
        
        // Check if user meets exact requirement
        if ($current_pods >= $required_pods) {
            if (!$has_earned) {
                gamipress_award_achievement_to_user($badge->ID, $user_id);
                $badges_updated = true;
            }
        } else {
            if ($has_earned) {
                gamipress_revoke_achievement_to_user($badge->ID, $user_id);
                $badges_updated = true;
                $badges_lost++;
            }
        }
    }
    
    // Clear Gamipress cache
    if ($badges_updated) {
        // gamipress_delete_user_achievements_cache($user_id);
        delete_transient('tpgs_badges_' . $user_id);
    }
    
    return [
        'updated' => $badges_updated,
        'lost' => $badges_lost
    ];
}

    private function update_gamification_stats($user_id, $action)
    {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);

        if (empty($stats)) {
            $stats = array(
                'planted' => 0,
                'harvested' => 0,
                'first_planting' => '',
                'last_harvest' => ''
            );
        }

        if ($action === 'planted') {
            $stats['planted']++;

            if (empty($stats['first_planting'])) {
                $stats['first_planting'] = current_time('mysql');
            }
        } elseif ($action === 'harvested') {
            $stats['harvested']++;
            $stats['last_harvest'] = current_time('mysql');
        }

        update_user_meta($user_id, 'tpgs_gamification_stats', $stats);
    }

    public static function get_pod_html($pod_id, $pod_data)
    {
        ob_start();

        $vegetable = $pod_data['vegetable_id'] ? TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']) : false;
        $status_class = $pod_data['status'] === 'empty' ? 'empty' : ($pod_data['status'] === 'ready' ? 'ready' : 'growing');

        ?>
        <div class="pod pod-<?php echo $pod_id; ?> <?php echo $status_class; ?>" data-pod-id="<?php echo $pod_id; ?>">
            <?php if ($pod_data['status'] === 'empty'): ?>
                <div class="pod-empty">
                    <span class="pod-number"><?php echo $pod_id; ?></span>
                    <span class="pod-status">Empty</span>
                </div>
            <?php else: ?>
                <div class="pod-content">
                    <span class="pod-number"><?php echo $pod_id; ?></span>
                    <?php if ($vegetable && !empty($vegetable['icon'])): ?>
                        <img src="<?php echo esc_url($vegetable['icon']); ?>" alt="<?php echo esc_attr($vegetable['name']); ?>"
                            class="vegetable-icon">
                    <?php endif; ?>
                    <span class="vegetable-name"><?php echo $vegetable ? esc_html($vegetable['name']) : 'Unknown'; ?></span>
                    <span class="days-remaining"><?php echo esc_html($pod_data['days_remaining']); ?> days</span>
                    <span class="pod-status"><?php echo esc_html(self::get_status_text($pod_data['status'])); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function get_status_text($status)
    {
        switch ($status) {
            case 'empty':
                return 'Empty';
            case 'growing':
                return 'Growing';
            case 'ready':
                return 'Ready to Harvest';
            default:
                return '';
        }
    }

    public static function get_next_harvest($user_id) {
    $pods = self::get_user_pods($user_id);
    $next_harvest = null;
    
    foreach ($pods as $pod) {
        if ($pod['status'] === 'growing') {
            $vegetable = TPGS_Vegetable_Manager::get_vegetable($pod['vegetable_id']);
            if ($vegetable && (!$next_harvest || $pod['days_remaining'] < $next_harvest['days'])) {
                $next_harvest = [
                    'name' => $vegetable['name'],
                    'icon' => $vegetable['icon'],
                    'days' => $pod['days_remaining']
                ];
            }
        }
    }
    
    return $next_harvest;
}

}

