<?php
/**
 * Dashboard v2 - Full dynamic-static hybrid
 */
if (!is_user_logged_in()) {
    echo '<div class="alert alert-warning">Please log in to access your garden.</div>';
    return;
}

$user_id = get_current_user_id();
$pods = TPGS_Pod_Manager::get_user_pods($user_id);
$active_count = TPGS_Pod_Manager::get_active_pod_count($user_id);
$next_harvest = TPGS_Pod_Manager::get_next_harvest($user_id);
?>
<div class="dashboard">
    <!-- Static Header -->
    <header class="header">
        <div class="logo">
            <div class="logo-icon">🌱</div>
            <span class="logo-text">workwell</span>
        </div>

        <nav class="nav">
            <button class="nav-btn active">
                <span class="nav-icon">🏠</span>
                Home
            </button>
            <button class="nav-btn">
                <span class="nav-icon">👥</span>
                Community
            </button>
            <button class="nav-btn">
                <span class="nav-icon">📚</span>
                Learn
            </button>
            <button class="nav-btn">
                <span class="nav-icon">🛒</span>
                Shop
            </button>
        </nav>

        <div class="header-right">
            <button class="notification-btn">
                <span class="notification-icon">🔔</span>
                <span class="notification-badge"></span>
            </button>
            <button class="settings-btn">⚙️</button>
            <div class="user-profile">
                <div class="user-avatar">BR</div>
                <span class="user-name">Ben Reny</span>
                <span class="dropdown-arrow">▼</span>
            </div>
        </div>
    </header>

    <div class="main-content">
        <!-- Static Sidebar -->
        <aside class="sidebar">
            <!-- Post Creation -->
            <div class="post-card">
                <input type="text" placeholder="What are you growing today?" class="post-input">
                <div class="post-actions">
                    <button class="post-action-btn">📷 Image</button>
                    <button class="post-action-btn">🎥 Video</button>
                    <button class="post-action-btn">📊 Poll</button>
                    <button class="post-btn">Post</button>
                </div>
            </div>

            <!-- Weekly Streak -->
            <div class="streak-card">
                <div class="streak-header">
                    <span class="streak-icon">⚡</span>
                    <h3>Weekly streak</h3>
                </div>

                <div class="plant-illustration">
                    <div class="plant-pot"></div>
                    <div class="plant-leaves"></div>
                </div>

                <div class="streak-info">
                    <div class="streak-number">4</div>
                    <div class="streak-text">Day Streak!</div>
                    <div class="streak-subtitle">😊 Keep your garden thriving</div>
                </div>

                <div class="week-days">
                    <div class="day completed">Mon</div>
                    <div class="day completed">Tue</div>
                    <div class="day completed">Wed</div>
                    <div class="day completed">Thu</div>
                    <div class="day">Fri</div>
                    <div class="day">Sat</div>
                    <div class="day">Sun</div>
                </div>

                <button class="log-care-btn">Log today plant care</button>
            </div>

            <!-- Badges -->
            <div class="badges-card">
                <div class="badges-header">
                    <span class="badges-icon">⚡</span>
                    <h3>Badges</h3>
                </div>

                <div class="badges-grid">
                    <div class="badge">
                        <div class="badge-icon sprouter">🌱</div>
                        <span class="badge-name">Sprouter</span>
                    </div>
                    <div class="badge">
                        <div class="badge-icon nurturer">🌿</div>
                        <span class="badge-name">Nurturer</span>
                    </div>
                    <div class="badge">
                        <div class="badge-icon harvester">❄️</div>
                        <span class="badge-name">Harvester</span>
                    </div>
                    <div class="badge">
                        <div class="badge-icon thriver">🌾</div>
                        <span class="badge-name">Thriver</span>
                    </div>
                    <div class="badge">
                        <div class="badge-icon rooted">🍂</div>
                        <span class="badge-name">Rooted</span>
                    </div>
                    <div class="badge">
                        <div class="badge-icon growmate">🌰</div>
                        <span class="badge-name">Growmate</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Dynamic Garden Area -->
        <main class="garden-area">
            <div class="garden-header">
                <h1>Office desk garden</h1>
            </div>

            <div class="garden-content">
                <!-- Dynamic Next Harvest -->
                <div class="next-harvest">
                    <h2>Next harvest</h2>
                    <?php if ($next_harvest) : ?>
                        <div class="harvest-item">
                            <span class="harvest-icon"><?php echo esc_html($next_harvest['icon']); ?></span>
                            <div class="harvest-info">
                                <h3><?php echo esc_html($next_harvest['name']); ?> in <?php echo esc_html($next_harvest['days']); ?> days</h3>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="harvest-item">
                            <span class="harvest-icon">🌱</span>
                            <div class="harvest-info">
                                <h3>No upcoming harvests</h3>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dynamic Active Pods Counter -->
                <div class="active-pods">
                    <h2>Active pods</h2>
                    <div class="pods-count">
                        <span class="active-count"><?php echo esc_html($active_count); ?></span> / 12
                    </div>
                </div>
            </div>

            <!-- Dynamic Plant Grid -->
            <div class="plant-grid">
                <?php foreach ($pods as $pod_id => $pod_data) : 
                    $vegetable = $pod_data['vegetable_id'] ? TPGS_Vegetable_Manager::get_vegetable($pod_data['vegetable_id']) : false;
                    $status_class = $pod_data['status'] === 'empty' ? 'empty-pod' : 'active-pod';
                ?>
                    <div class="pod <?php echo esc_attr($status_class); ?>" 
                         data-pod-id="<?php echo esc_attr($pod_id); ?>">
                        
                        <?php if ($pod_data['status'] === 'empty') : ?>
                            <div class="add-plant">+</div>
                            <span class="pod-label">Pod <?php echo esc_html($pod_id); ?></span>
                        <?php else : ?>
                            <div class="plant-container">
                                <div class="plant-icon"><?php echo esc_html($vegetable['icon'] ?? '🌱'); ?></div>
                            </div>
                            <div class="plant-info">
                                <h4><?php echo esc_html($vegetable['name'] ?? 'Unknown'); ?></h4>
                                <p><?php echo esc_html($pod_data['days_remaining']); ?> days to harvest</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- Include Modals -->
<?php 
include TPGS_PLUGIN_DIR . 'templates/frontend/planting-modal.php'; 
include TPGS_PLUGIN_DIR . 'templates/frontend/pod-detail.php';
?>


    <!-- <script>
    jQuery(document).ready(function($) {
        // Pod click handlers (same as original)
        $('.plant-grid').on('click', '.pod', function() {
            const podId = $(this).data('pod-id');
            const isActive = $(this).hasClass('active-pod');
            
            if (isActive) {
                // Load pod details
                $.ajax({
                    url: tpgs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tpgs_get_pod_details',
                        nonce: tpgs_ajax.nonce,
                        pod_id: podId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#podDetailModal .modal-content').html(response.data.html);
                            $('#podDetailModal').modal('show');
                        }
                    }
                });
            } else {
                // Show planting modal
                $('#plantingModal').data('pod-id', podId).modal('show');
            }
        });

        // Active counter updates automatically via existing AJAX handlers
    });
    </script> -->