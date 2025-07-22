<?php
/*
Plugin Name: 12-Pod Gardening System
Description: A digital garden management system for WordPress.
Version: 1.0
Author: Umar Khtab
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('TPGS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPGS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once TPGS_PLUGIN_DIR . 'includes/class-pod-manager.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-vegetable-manager.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-notifications.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once TPGS_PLUGIN_DIR . 'includes/class-shortcodes.php';
// Load Gamipress integration
if (class_exists('GamiPress')) {
    try {
        require_once TPGS_PLUGIN_DIR . 'includes/class-gamipress-integration.php';
    } catch (Exception $e) {
        error_log('TPGS GamiPress integration failed: ' . $e->getMessage());
    }
}

class Twelve_Pod_Gardening_System {

    public function __construct() {
        // Initialize all components
        $this->init_components();
        $this->register_hooks();
    }

    private function init_components() {
        new TPGS_Pod_Manager();
        new TPGS_Vegetable_Manager();
        new TPGS_Notifications();
        new TPGS_Cron_Handler();
        new TPGS_Shortcodes();
    }

    private function register_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function activate() {
        if (!current_user_can('activate_plugins')) {
        return;
    }
        // Set up default vegetables if none exist
        TPGS_Vegetable_Manager::setup_default_vegetables();
        
        // Schedule our cron event
        if (!wp_next_scheduled('tpgs_daily_growth_tracker')) {
            wp_schedule_event(time(), 'daily', 'tpgs_daily_growth_tracker');
        }
        
        // Create required database tables or options
        update_option('tpgs_version', '1.0');

        $users = get_users();
    foreach ($users as $user) {
        delete_user_meta($user->ID, '_gamipress_achievements');
        delete_transient('tpgs_badges_' . $user->ID);
    }
    }

    public function deactivate() {
        // Clear our cron event
        wp_clear_scheduled_hook('tpgs_daily_growth_tracker');
    }

    public function enqueue_assets() {
        // Bootstrap CSS
        wp_enqueue_style(
            'tpgs-bootstrap', 
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
        
        // Bootstrap JS Bundle (with Popper)
        wp_enqueue_script(
            'tpgs-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Plugin styles
        wp_enqueue_style(
            'tpgs-styles',
            TPGS_PLUGIN_URL . 'assets/css/style.css',
            array(),
            filemtime(TPGS_PLUGIN_DIR . 'assets/css/style.css')
        );
        
        // Plugin scripts
        wp_enqueue_script(
            'tpgs-scripts',
            TPGS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            filemtime(TPGS_PLUGIN_DIR . 'assets/js/frontend.js'),
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('tpgs-scripts', 'tpgs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tpgs_nonce')
        ));
    }

    public function enqueue_admin_assets($hook) {
        if ($hook === 'settings_page_tpgs_vegetables_config') {
            wp_enqueue_style('tpgs-admin-styles', TPGS_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('tpgs-admin-scripts', TPGS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), null, true);
        }
    }
}

// Initialize the plugin
new Twelve_Pod_Gardening_System();