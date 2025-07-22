<?php
class TPGS_Cron_Handler {

    public function __construct() {
        add_action('tpgs_daily_growth_tracker', array($this, 'process_daily_growth'));
    }

    public function process_daily_growth() {
        $users = get_users(array(
            'meta_key' => 'tpgs_pod_1',
            'meta_compare' => 'EXISTS'
        ));
        
        foreach ($users as $user) {
            $this->process_user_pods($user->ID);
        }
    }

    private function process_user_pods($user_id) {
        for ($i = 1; $i <= 12; $i++) {
            $pod_data = get_user_meta($user_id, 'tpgs_pod_' . $i, true);
            
            if (empty($pod_data) || $pod_data['status'] !== 'growing') {
                continue;
            }
            
            $vegetable = TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']);
            if (!$vegetable) {
                continue;
            }
            
            // Decrement days remaining
            $pod_data['days_remaining']--;
            
            // Check if ready to harvest
            if ($pod_data['days_remaining'] <= 0) {
                $pod_data['days_remaining'] = 0;
                $pod_data['status'] = 'ready';
                
                // Send notification
                TPGS_Notifications::send_harvest_notification($user_id, $i, $pod_data['vegetable_id']);
            }
            
            // Update pod
            update_user_meta($user_id, 'tpgs_pod_' . $i, $pod_data);
        }
    }
}