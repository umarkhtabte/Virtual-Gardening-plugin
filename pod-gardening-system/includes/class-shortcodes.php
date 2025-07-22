<?php
class TPGS_Shortcodes {

    public function __construct() {
        add_shortcode('tpgs_garden_dashboard', [$this, 'render_garden_dashboard']);
        add_shortcode('tpgs_garden_dashboard_v2', [$this, 'render_garden_dashboard_v2']);
        add_shortcode('tpgs_garden_dashboard_v3', [$this, 'render_garden_dashboard_v3']);
    }

    /**
     * Main Dashboard with Badges Integration
     */
    public function render_garden_dashboard($atts) {
        if (!is_user_logged_in()) {
            return $this->login_required_message();
        }
        
        $user_id = get_current_user_id();
        $data = [
            'pods' => TPGS_Pod_Manager::get_user_pods($user_id),
            'active_pods' => TPGS_Pod_Manager::get_active_pod_count($user_id),
            'vegetables' => TPGS_Vegetable_Manager::get_vegetables(),
            'badges' => $this->get_badge_data($user_id),
            'next_harvest' => TPGS_Pod_Manager::get_next_harvest($user_id)
        ];
        
        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Alternate Dashboard Layout
     */
    public function render_garden_dashboard_v2($atts) {
        if (!is_user_logged_in()) {
            return $this->login_required_message();
        }
        
        $user_id = get_current_user_id();
        $data = [
            'pods' => TPGS_Pod_Manager::get_user_pods($user_id),
            'active_pods' => TPGS_Pod_Manager::get_active_pod_count($user_id),
            'next_harvest' => TPGS_Pod_Manager::get_next_harvest($user_id),
            'badges' => $this->get_badge_data($user_id),
            'stats' => $this->get_user_stats($user_id)
        ];
        
        ob_start();
        include TPGS_PLUGIN_DIR . 'templates/frontend/dashboardv2.php';
        return ob_get_clean();
    }

    /**
     * Get Gamipress Badge Data
     */
    private function get_badge_data($user_id) {
        if (!class_exists('GamiPress')) return false;
        
        return [
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
    }

    /**
     * Get User Statistics
     */
    private function get_user_stats($user_id) {
        $stats = get_user_meta($user_id, 'tpgs_gamification_stats', true) ?: [];
        
        return wp_parse_args($stats, [
            'total_planted' => 0,
            'total_harvested' => 0,
            'first_planting' => '',
            'last_harvest' => ''
        ]);
    }

    /**
     * Login Required Message
     */
    private function login_required_message() {
        return '<div class="alert alert-warning text-center">
            <i class="fas fa-sign-in-alt me-2"></i>
            Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your garden.
        </div>';
    }

    public function render_garden_dashboard_v3() {
    if (!is_user_logged_in()) {
        return $this->login_required_message();
    }
    
    ob_start();
    include TPGS_PLUGIN_DIR . 'templates/frontend/dashboard-v3.php';
    return ob_get_clean();
}
}