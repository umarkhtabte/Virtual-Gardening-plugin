jQuery(document).ready(function ($) {
    function refreshPodDisplay(podId, podData) {
        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_get_pod_html',
                nonce: tpgs_ajax.nonce,
                pod_id: podId,
                pod_data: podData
            },
            success: function (response) {
                if (response.success) {
                    $(`.pod-${podId}`).replaceWith(response.data.html);
                }
            }
        });
    }

    // ============ POD DETAILS FUNCTION ============
    function loadPodDetails(podId) {
        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_get_pod_details',
                nonce: tpgs_ajax.nonce,
                pod_id: podId
            },
            beforeSend: function() {
                $('#podDetailModal .modal-content').html(`
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                $('#podDetailModal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    $('#podDetailModal .modal-content').html(response.data.html);
                } else {
                    $('#podDetailModal .modal-content').html(`
                        <div class="modal-body">
                            <div class="alert alert-danger">${response.data}</div>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#podDetailModal .modal-content').html(`
                    <div class="modal-body">
                        <div class="alert alert-danger">Failed to load pod details: ${error}</div>
                    </div>
                `);
            }
        });
    }

    // ============ DASHBOARD V3 HANDLERS ============
    function initDashboardV3() {
        if (!$('.dashboard').length) return;

        // Handle empty pod clicks
        $(document).on('click', '.pod.empty-pod, .add-plant', function() {
            const podId = $(this).closest('.pod').data('pod-id');
            $('#plantingModal').data('pod-id', podId).modal('show');
        });

        // Handle active pod clicks
        $(document).on('click', '.pod.active-pod', function() {
            const podId = $(this).data('pod-id');
            loadPodDetails(podId);
        });
    }

    // ============ INITIALIZATION ============
    initDashboardV3();
    $('[data-bs-toggle="tooltip"]').tooltip();

    $('.pods-grid').on('click', '.pod', function () {
        const podId = $(this).data('pod-id');
        const podStatus = $(this).hasClass('empty') ? 'empty' :
            $(this).hasClass('ready') ? 'ready' : 'growing';

        if (podStatus === 'empty') {
            $('#plantingModal').data('pod-id', podId).modal('show');
        } else {
            loadPodDetails(podId);
        }
    });

    $(document).on('click', '.vegetable-item', function () {
        $('.vegetable-item').removeClass('selected');
        $(this).addClass('selected');
    });

    function refreshBadges() {
    $.ajax({
        url: tpgs_ajax.ajax_url,
        type: 'GET',
        data: {
            action: 'tpgs_refresh_badges',
            user_id: tpgs_ajax.user_id,
            nonce: tpgs_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                // Small delay to ensure cache clears (optional)
                console.log("hello");
                setTimeout(() => {
                    $('.badges-section').replaceWith(response.data.html);
                    $('[data-bs-toggle="tooltip"]').tooltip('dispose').tooltip();
                    
                    if (response.data.badges_lost > 0) {
                        showAlert(`${response.data.badges_lost} badge(s) were removed`, 'warning');
                    }
                }, 300);
            }
        },
        error: function(xhr) {
            console.error("Badge refresh failed:", xhr.responseText);
            showAlert('Failed to update badges. Please refresh the page.', 'error');
        }
    });
}

    $('#confirmPlanting').on('click', function () {
        const podId = $('#plantingModal').data('pod-id');
        const vegetableId = $('.vegetable-item.selected').data('vegetable-id');

        if (!vegetableId) {
            showAlert('Please select a vegetable to plant', 'error');
            return;
        }

        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_plant_vegetable',
                nonce: tpgs_ajax.nonce,
                pod_id: podId,
                vegetable_id: vegetableId
            },
            beforeSend: function () {
                $('#confirmPlanting').prop('disabled', true).text('Planting...');
            },
            success: function (response) {
                if (response.success) {
                    $(`.pod-${podId}`).replaceWith(response.data.pod_html);
                    $('.pod-counter span').text(response.data.active_count);
                    if (response.data.badges_updated) {
                        refreshBadges();
                    }
                    $('#plantingModal').modal('hide');
                    showAlert(response.data.message, 'success');
                } else {
                    showAlert(response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                showAlert('An error occurred: ' + error, 'error');
            },
            complete: function () {
                $('#confirmPlanting').prop('disabled', false).text('Plant');
            }
        });
    });

    function loadPodDetails(podId) {
        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_get_pod_details',
                nonce: tpgs_ajax.nonce,
                pod_id: podId
            },
            beforeSend: function () {
                $('#podDetailModal .modal-content').html('<div class="modal-body text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $('#podDetailModal').modal('show');
            },
            success: function (response) {
                if (response.success) {
                    $('#podDetailModal .modal-content').html(response.data.html);
                } else {
                    $('#podDetailModal .modal-content').html(`<div class="modal-body"><div class="alert alert-danger">${response.data}</div></div>`);
                }
            },
            error: function (xhr, status, error) {
                $('#podDetailModal .modal-content').html(`<div class="modal-body"><div class="alert alert-danger">Failed to load pod details: ${error}</div></div>`);
            }
        });
    }

    $(document).on('click', '#updatePodDate', function () {
        const podId = $(this).data('pod-id');
        const newDate = $('#podDatePicker').val();

        if (!podId || podId < 1 || podId > 12) {
            showAlert('Invalid pod selection', 'error');
            return;
        }

        if (!newDate) {
            showAlert('Please select a valid date', 'error');
            return;
        }

        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_update_pod_date',
                nonce: tpgs_ajax.nonce,
                pod_id: podId,
                new_date: newDate
            },
            beforeSend: function () {
                $('#updatePodDate').prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {
                    $(`.pod-${podId}`).replaceWith(response.data.pod_html);
                    $('.pod-counter span').text(response.data.active_count);
                    if ($('#podDetailModal').is(':visible')) {
                        $('.days-remaining-value').text(response.data.days_remaining);
                        $('.pod-status-value').text(response.data.status_text);
                        if (response.data.status === 'ready') {
                            $('.pod-status-value').removeClass('text-warning').addClass('text-success');
                        }
                    }
                    showAlert('Pod updated successfully!', 'success');
                } else {
                    showAlert(response.data, 'error');
                }
            },
            error: function (xhr) {
                let errorMsg = 'Failed to update pod';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                showAlert(errorMsg, 'error');
            },
            complete: function () {
                $('#updatePodDate').prop('disabled', false).text('Update Date');
            }
        });
    });

    $(document).on('click', '#resetPod', function () {
        if (!confirm('Are you sure you want to reset this pod? This cannot be undone.')) {
            return;
        }

        const podId = $(this).data('pod-id');

        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpgs_reset_pod',
                nonce: tpgs_ajax.nonce,
                pod_id: podId
            },
            beforeSend: function () {
                $('#resetPod').prop('disabled', true).text('Resetting...');
            },
            success: function (response) {
                if (response.success) {
                    $(`.pod-${podId}`).replaceWith(response.data.pod_html);
                    $('.pod-counter span').text(response.data.active_count);
                    $('#podDetailModal').modal('hide');
                    if (response.data.badges_updated) {
                        refreshBadges();
                    }
                    showAlert(response.data.message, 'success');
                } else {
                    showAlert(response.data, 'error');
                }
            },
            error: function (xhr, status, error) {
                showAlert('An error occurred: ' + error, 'error');
            },
            complete: function () {
                $('#resetPod').prop('disabled', false).text('Reset Pod');
            }
        });
    });

    function showAlert(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

        $('.tpgs-alert-container').remove();
        $('body').append(`<div class="tpgs-alert-container"></div>`);
        $('.tpgs-alert-container').html(alertHtml);
        
        setTimeout(() => {
            $('.tpgs-alert-container .alert').alert('close');
        }, 5000);
    }

    function checkNewBadges(userId) {
        if (!userId) return;
        
        $.ajax({
            url: tpgs_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'tpgs_check_badges',
                user_id: userId,
                nonce: tpgs_ajax.nonce
            },
            success: function (response) {
                if (response.success && response.badges?.length) {
                    response.badges.forEach(badge => {
                        showBadgeNotification(badge);
                    });
                }
            }
        });
    }

    function showBadgeNotification(badge) {
        const notification = `
        <div class="badge-notification alert alert-success alert-dismissible fade show">
            <div class="d-flex align-items-center">
                ${badge.image ? `<img src="${badge.image}" class="me-2" width="40">` : ''}
                <div>
                    <strong>New Badge Earned!</strong><br>
                    ${badge.title}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
        
        $('#badge-notifications-container').append(notification);
        setTimeout(() => $('.badge-notification').alert('close'), 5000);
    }

    $('body').append('<div id="badge-notifications-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999"></div>');
    $('[data-bs-toggle="tooltip"]').tooltip();

    $(document).on('click', '.empty-pod, .add-plant', function() {
        const podId = $(this).closest('.pod').data('pod-id') || $(this).data('pod-id');
        $('#plantingModal').data('pod-id', podId).modal('show');
    });
});