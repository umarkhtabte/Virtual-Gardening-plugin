jQuery(document).ready(function($) {
    // Use the globally defined tpgsNextId
    var nextId = typeof tpgsNextId !== 'undefined' ? tpgsNextId : 1;
    
    // Add new vegetable
    $('#add_vegetable').on('click', function() {
        const name = $('#new_vegetable_name').val().trim();
        const icon = $('#new_vegetable_icon').val().trim();
        const duration = $('#new_vegetable_duration').val();
        
        if (!name || !icon || !duration) {
            alert('Please fill in all fields');
            return;
        }
        
        const newRow = `
            <tr>
                <td>
                    <input type="hidden" name="tpgs_vegetables[${nextId}][id]" value="${nextId}">
                    <input type="text" name="tpgs_vegetables[${nextId}][name]" value="${name}" class="regular-text">
                </td>
                <td>
                    <input type="text" name="tpgs_vegetables[${nextId}][icon]" value="${icon}" class="regular-text">
                </td>
                <td>
                    <input type="number" name="tpgs_vegetables[${nextId}][growth_duration]" value="${duration}" min="1" class="small-text">
                </td>
                <td>
                    <button type="button" class="button button-secondary remove-vegetable">Remove</button>
                </td>
            </tr>
        `;
        
        $('#vegetables-list').append(newRow);
        nextId++; // Increment for next addition
        
        // Clear form
        $('#new_vegetable_name, #new_vegetable_icon, #new_vegetable_duration').val('');
    });
    
    // Remove vegetable
    $(document).on('click', '.remove-vegetable', function() {
        if (confirm('Are you sure you want to remove this vegetable?')) {
            $(this).closest('tr').remove();
        }
    });
});