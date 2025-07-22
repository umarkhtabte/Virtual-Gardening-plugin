<?php
defined('ABSPATH') || exit;

class TPGS_Gamipress_Integration {

    public function __construct() {
        // Register custom trigger
        add_filter('gamipress_activity_triggers', [$this, 'register_triggers']);
        
        // Validate badge awards
        add_filter('gamipress_user_has_access_to_achievement', [$this, 'validate_badge'], 10, 6);
        add_action('wp_ajax_tpgs_check_badges', [$this, 'check_new_badges']);
add_action('wp_ajax_nopriv_tpgs_check_badges', [$this, 'check_new_badges']);
add_action('wp_ajax_tpgs_refresh_badges', [$this, 'ajax_refresh_badges']);
    }

    /**
     * Register custom trigger
     */
    public function register_triggers($triggers) {
        $triggers['12-Pod Gardening'] = [
            'tpgs_pod_threshold' => __('Reach pod planting threshold', 'twelve-pod-gardening')
        ];
        return $triggers;
    }

    /**
     * Validate badge requirements
     */
    public function validate_badge($can_earn, $user_id, $achievement, $trigger, $site_id, $args) {
        
        // Only for our badge type and trigger
        if ($achievement->post_type !== 'gardening_badges' || $trigger !== 'tpgs_pod_threshold') {
            return $can_earn;
        }

        // Get current active pods
        $current_pods = TPGS_Pod_Manager::get_active_pod_count($user_id);
        
        // Requirement mapping
        $requirements = [
            'seedling' => 1,
            'green-thumb' => 3,
            'crop-master' => 6,
            'harvest-leader' => 9,
            'pod-perfectionist' => 12
        ];

        $achievement_slug = $achievement->post_name;
        
        return isset($requirements[$achievement_slug]) 
            ? ($current_pods >= $requirements[$achievement_slug])
            : $can_earn;
    }

public function check_new_badges() {
    check_ajax_referer('tpgs_nonce', 'nonce');
    
    $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    $recent_badges = [];
    
    if ($user_id) {
        $recent_badges = gamipress_get_user_achievements([
            'user_id' => $user_id,
            'achievement_type' => 'gardening_badges',
            'since' => date('Y-m-d H:i:s', time() - 3600) // Last hour
        ]);
    }
    
    wp_send_json_success([
        'badges' => array_map(function($badge) {
            return [
                'title' => $badge->post_title,
                'image' => get_the_post_thumbnail_url($badge->ID, 'thumbnail')
            ];
        }, $recent_badges)
    ]);
}

public function ajax_refresh_badges() {
    check_ajax_referer('tpgs_nonce', 'nonce');
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    
    if (!$user_id || !is_user_logged_in()) {
        wp_send_json_error('Invalid user ID or not logged in.', $user_id);
    }

    // Clear GamiPress cache to force fresh data
    if (function_exists('gamipress_delete_user_achievements_cache')) {
        // gamipress_delete_user_achievements_cache($user_id);
    }

    // Force WordPress to clear transients
    wp_cache_delete('user_achievements_' . $user_id, 'gamipress');
    delete_transient('tpgs_badges_' . $user_id);

    // Render fresh badge HTML
    ob_start();
    $this->render_badges_section($user_id);
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

private function render_badges_section($user_id) {
    $badges = [
        'all' => gamipress_get_achievements([
            'post_type' => 'gardening_badges',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]),
        'earned' => gamipress_get_user_achievements([
            'user_id' => $user_id,
            'achievement_type' => 'gardening_badges'
        ])
    ];
    ?>
    <div class="badges-section mt-5">
        <h3 class="section-title">
            <i class="fas fa-award"></i> Your Badges
            <small class="text-muted">
                <?php echo count($badges['earned']); ?>/<?php echo count($badges['all']); ?> earned
            </small>
        </h3>
        
        <div class="badges-grid">
            <?php foreach ($badges['all'] as $badge) : 
                $earned = in_array($badge->ID, wp_list_pluck($badges['earned'], 'ID'));
            ?>
                <div class="badge-item <?php echo $earned ? 'earned' : 'locked'; ?>"
                     data-bs-toggle="tooltip" 
                     title="<?php echo $earned ? esc_attr($badge->post_title) : 'Locked'; ?>">
                    <?php if ($earned) : ?>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($badge->ID, 'thumbnail')); ?>" 
                             alt="<?php echo esc_attr($badge->post_title); ?>">
                    <?php else : ?>
                        <div class="locked-badge">
                            <i class="fas fa-lock"></i>
                        </div>
                    <?php endif; ?>
                    <span class="badge-label"><?php echo esc_html($badge->post_title); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
}

// Initialize integration
if (class_exists('GamiPress')) {
    new TPGS_Gamipress_Integration();
}