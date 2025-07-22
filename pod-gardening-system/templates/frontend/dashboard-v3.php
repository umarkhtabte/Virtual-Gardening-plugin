<?php
// Get all required data
$user_id = get_current_user_id();
$user = wp_get_current_user();
$pods = TPGS_Pod_Manager::get_user_pods($user_id);
$active_count = TPGS_Pod_Manager::get_active_pod_count($user_id);
$next_harvest = TPGS_Pod_Manager::get_next_harvest($user_id);
$vegetables = TPGS_Vegetable_Manager::get_vegetables();
$stats = get_user_meta($user_id, 'tpgs_gamification_stats', true);
?>

<div class="dashboard">
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <div class="logo-icon">🌱</div>
            <span class="logo-text">My Garden</span>
        </div>

        <div class="header-right">
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user->display_name, 0, 2)); ?></div>
                <span class="user-name"><?php echo esc_html($user->display_name); ?></span>
            </div>
        </div>
    </header>

    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Active Pods Counter -->
            <div class="stats-card">
                <h3>Garden Status</h3>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $active_count; ?>/12</span>
                    <span class="stat-label">Active Pods</span>
                </div>
                <?php if ($stats && $stats['harvested']): ?>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['harvested']; ?></span>
                    <span class="stat-label">Total Harvests</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Next Harvest -->
            <?php if ($next_harvest): ?>
            <div class="harvest-card">
                <h3>Next Harvest</h3>
                <div class="harvest-item">
                    <?php if ($next_harvest['icon']): ?>
                        <img src="<?php echo esc_url($next_harvest['icon']); ?>" class="harvest-icon">
                    <?php endif; ?>
                    <div class="harvest-info">
                        <span class="harvest-name"><?php echo esc_html($next_harvest['name']); ?></span>
                        <span class="harvest-days"><?php echo esc_html($next_harvest['days']); ?> days left</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Garden Area -->
        <main class="garden-area">
            <div class="garden-header">
                <h1>My 12-Pod Garden</h1>
            </div>

            <!-- Plant Grid -->
            <div class="plant-grid">
                <?php foreach ($pods as $pod_id => $pod_data): ?>
                <div class="pod <?php echo $pod_data['status'] !== 'empty' ? 'active-pod' : 'empty-pod'; ?>" 
                     data-pod-id="<?php echo $pod_id; ?>">
                    <?php if ($pod_data['status'] !== 'empty'): ?>
                        <?php $vegetable = TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']); ?>
                        <div class="plant-container">
                            <?php if ($vegetable['icon']): ?>
                                <img src="<?php echo esc_url($vegetable['icon']); ?>" class="plant-icon">
                            <?php endif; ?>
                        </div>
                        <div class="plant-info">
                            <h4><?php echo esc_html($vegetable['name']); ?></h4>
                            <p><?php echo esc_html($pod_data['days_remaining']); ?> days</p>
                        </div>
                    <?php else: ?>
                        <div class="add-plant">+</div>
                        <span class="pod-label">Pod <?php echo $pod_id; ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- Pod Detail Modal -->
<div class="modal fade" id="podDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>


<!-- Planting Modal -->
<div class="modal fade" id="plantingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Plant a Vegetable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="vegetables-grid">
                    <?php foreach ($vegetables as $vegetable): ?>
                        <div class="vegetable-item" data-vegetable-id="<?php echo $vegetable['id']; ?>">
                            <?php if (!empty($vegetable['icon'])): ?>
                                <img src="<?php echo esc_url($vegetable['icon']); ?>"
                                    alt="<?php echo esc_attr($vegetable['name']); ?>" class="vegetable-icon">
                            <?php endif; ?>
                            <div class="vegetable-name"><?php echo esc_html($vegetable['name']); ?></div>
                            <div class="vegetable-duration"><?php echo esc_html($vegetable['growth_duration']); ?> days
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPlanting">Plant</button>
            </div>
        </div>
    </div>
</div>

<?php if (!wp_style_is('font-awesome')) : ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php endif; ?>