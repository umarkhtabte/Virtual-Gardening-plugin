<div class="wrap">
    <h1>12-Pod Gardening Vegetables</h1>
    
    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Vegetables updated successfully!</p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php settings_fields('tpgs_vegetables_group'); ?>
        <?php do_settings_sections('tpgs_vegetables_group'); ?>
        
        <h2>Current Vegetables</h2>
        <table class="form-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Icon URL</th>
                    <th>Growth Duration (days)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="vegetables-list">
                <?php 
                $vegetables = TPGS_Vegetable_Manager::get_vegetables();
                if (is_array($vegetables)): 
                    foreach ($vegetables as $vegetable): 
                        if (!is_array($vegetable)) continue; // Skip if not an array
                ?>
                    <tr>
                        <td>
                            <input type="hidden" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][id]" value="<?php echo esc_attr($vegetable['id']); ?>">
                            <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][name]" value="<?php echo esc_attr($vegetable['name']); ?>" class="regular-text">
                        </td>
                        <td>
                            <input type="text" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][icon]" value="<?php echo esc_url($vegetable['icon']); ?>" class="regular-text">
                        </td>
                        <td>
                            <input type="number" name="tpgs_vegetables[<?php echo esc_attr($vegetable['id']); ?>][growth_duration]" value="<?php echo esc_attr($vegetable['growth_duration']); ?>" min="1" class="small-text">
                        </td>
                        <td>
                            <button type="button" class="button button-secondary remove-vegetable">Remove</button>
                        </td>
                    </tr>
                <?php 
                    endforeach;
                endif; 
                ?>
            </tbody>
        </table>
        
        <h2>Add New Vegetable</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="new_vegetable_name">Name</label></th>
                <td><input type="text" id="new_vegetable_name" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="new_vegetable_icon">Icon URL</label></th>
                <td><input type="text" id="new_vegetable_icon" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="new_vegetable_duration">Growth Duration (days)</label></th>
                <td><input type="number" id="new_vegetable_duration" min="1" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td><button type="button" id="add_vegetable" class="button button-primary">Add Vegetable</button></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
    // Calculate next ID safely
    var tpgsNextId = <?php 
        $vegetables = TPGS_Vegetable_Manager::get_vegetables();
        $next_id = 1;
        if (is_array($vegetables) && !empty($vegetables)) {
            $ids = array_column($vegetables, 'id');
            $next_id = max($ids) + 1;
        }
        echo $next_id;
    ?>;
</script>