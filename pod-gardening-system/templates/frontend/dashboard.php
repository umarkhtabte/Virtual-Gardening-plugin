<?php
// Initialize variables with defaults
$user_id = get_current_user_id();
$pods = isset($pods) ? $pods : TPGS_Pod_Manager::get_user_pods($user_id);
$vegetables = isset($vegetables) ? $vegetables : TPGS_Vegetable_Manager::get_vegetables();
$badges = isset($badges) ? $badges : (class_exists('GamiPress') ? [
    'all' => gamipress_get_achievements([
        'post_type' => 'gardening_badges',
        'posts_per_page' => -1
    ]),
    'earned' => gamipress_get_user_achievements([
        'user_id' => $user_id,
        'achievement_type' => 'gardening_badges'
    ])
] : false);
?>

<div class="tpgs-garden-container">
    <div class="garden-header">
        <h2>My 12-Pod Garden</h2>
        <div class="pod-counter">
            Active Pods: <span class="active-count"><?php echo TPGS_Pod_Manager::get_active_pod_count($user_id); ?></span>/12
        </div>
    </div>

    <div class="pods-grid">
        <?php if (is_array($pods) && !empty($pods)) : ?>
            <?php foreach ($pods as $pod_id => $pod_data) : ?>
                <?php echo TPGS_Pod_Manager::get_pod_html($pod_id, $pod_data); ?>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-info">No pods found. Please contact support.</div>
        <?php endif; ?>
    </div>

    <?php if ($badges && !empty($badges['all'])) : ?>
        <div class="badges-section mt-5">
            <h3 class="section-title">
                <i class="fas fa-award"></i> Your Badges
                <small class="text-muted">
                    <?php echo count($badges['earned'] ?? []); ?>/<?php echo count($badges['all']); ?> earned
                </small>
            </h3>
            
            <div class="badges-grid">
                <?php foreach ($badges['all'] as $badge) : 
                    $earned = is_array($badges['earned']) && in_array($badge->ID, wp_list_pluck($badges['earned'], 'ID'));
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
    <?php endif; ?>
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