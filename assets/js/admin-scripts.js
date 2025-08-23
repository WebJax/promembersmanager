(function($) {
    'use strict';
    
    // Initialize when document ready
    $(document).ready(function() {
        initDashboard();
        initMemberModal();
        initExportFeature();
    });
    
    function initDashboard() {
        // Load dashboard stats
        if ($('#total-members').length) {
            loadDashboardStats();
        }
        
        // Initialize charts if Chart.js is available
        if (typeof Chart !== 'undefined' && $('#membershipChart').length) {
            initMembershipChart();
        }
    }
    
    function loadDashboardStats() {
        $.ajax({
            url: pmmAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'pmm_load_dashboard_stats',
                nonce: pmmAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#total-members').text(stats.total_members || 0);
                    $('#private-members').text(stats.private_members || 0);
                    $('#union-members').text(stats.union_members || 0);
                    $('#auto-renewals').text(stats.auto_renewals || 0);
                }
            },
            error: function() {
                console.log('Failed to load dashboard stats');
            }
        });
    }
    
    function initMembershipChart() {
        const ctx = document.getElementById('membershipChart').getContext('2d');
        
        $.ajax({
            url: pmmAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'pmm_load_chart_data',
                nonce: pmmAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Membership Growth Over Time'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    }
    
    function initMemberModal() {
        // Edit member button click
        $(document).on('click', '.pmm-edit-member', function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('order-id');
            if (!orderId) return;
            
            loadMemberData(orderId);
        });
        
        // Save member form submit
        $(document).on('submit', '#pmm-member-form', function(e) {
            e.preventDefault();
            saveMemberData($(this));
        });
        
        // Close modal
        $(document).on('click', '.pmm-modal-close, .pmm-modal', function(e) {
            if (e.target === this) {
                closeMemberModal();
            }
        });
        
        // Create new member
        $(document).on('click', '#pmm-create-member', function(e) {
            e.preventDefault();
            showCreateMemberModal();
        });
    }
    
    function loadMemberData(orderId) {
        showMemberModal();
        showLoading('#pmm-member-modal .pmm-modal-content');
        
        $.ajax({
            url: pmmAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'pmm_edit_member',
                order_id: orderId,
                nonce: pmmAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateMemberForm(response.data.member);
                } else {
                    showNotice('error', response.data.message || 'Failed to load member data');
                }
            },
            error: function() {
                showNotice('error', 'Failed to load member data');
            },
            complete: function() {
                hideLoading('#pmm-member-modal .pmm-modal-content');
            }
        });
    }
    
    function saveMemberData($form) {
        const submitButton = $form.find('input[type="submit"]');
        const originalText = submitButton.val();
        
        submitButton.prop('disabled', true).val('Saving...');
        
        $.ajax({
            url: pmmAdmin.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=pmm_save_member&nonce=' + pmmAdmin.nonce,
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message || 'Member saved successfully');
                    closeMemberModal();
                    // Reload the page to show updated data
                    location.reload();
                } else {
                    showNotice('error', response.data.message || 'Failed to save member');
                }
            },
            error: function() {
                showNotice('error', 'Failed to save member');
            },
            complete: function() {
                submitButton.prop('disabled', false).val(originalText);
            }
        });
    }
    
    function showMemberModal() {
        // Create modal if it doesn't exist
        if ($('#pmm-member-modal').length === 0) {
            const modalHTML = `
                <div id="pmm-member-modal" class="pmm-modal">
                    <div class="pmm-modal-content">
                        <span class="pmm-modal-close">&times;</span>
                        <h2>Edit Member</h2>
                        <div id="pmm-modal-body">
                            <!-- Content loaded dynamically -->
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHTML);
        }
        
        $('#pmm-member-modal').show();
    }
    
    function closeMemberModal() {
        $('#pmm-member-modal').hide();
    }
    
    function populateMemberForm(member) {
        const formHTML = `
            <form id="pmm-member-form" class="pmm-member-form">
                <input type="hidden" name="order_id" value="${member.id}">
                
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">First Name</label></th>
                        <td><input type="text" id="first_name" name="first_name" value="${member.name.split(' ')[0] || ''}" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name</label></th>
                        <td><input type="text" id="last_name" name="last_name" value="${member.name.split(' ').slice(1).join(' ') || ''}" required></td>
                    </tr>
                    <tr>
                        <th><label for="company">Company</label></th>
                        <td><input type="text" id="company" name="company" value="${member.company || ''}"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" value="${member.email || ''}" required></td>
                    </tr>
                    <tr>
                        <th><label for="address_1">Address</label></th>
                        <td><input type="text" id="address_1" name="address_1" value="${member.address.line1 || ''}"></td>
                    </tr>
                    <tr>
                        <th><label for="address_2">Address 2</label></th>
                        <td><input type="text" id="address_2" name="address_2" value="${member.address.line2 || ''}"></td>
                    </tr>
                    <tr>
                        <th><label for="city">City</label></th>
                        <td><input type="text" id="city" name="city" value="${member.address.city || ''}"></td>
                    </tr>
                    <tr>
                        <th><label for="postcode">Postal Code</label></th>
                        <td><input type="text" id="postcode" name="postcode" value="${member.address.postcode || ''}"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone" value="${member.phone || ''}"></td>
                    </tr>
                </table>
                
                <div class="pmm-actions">
                    <input type="submit" class="button button-primary" value="Save Member">
                    <button type="button" class="button pmm-modal-close">Cancel</button>
                </div>
            </form>
        `;
        
        $('#pmm-modal-body').html(formHTML);
    }
    
    function showCreateMemberModal() {
        showMemberModal();
        $('#pmm-member-modal h2').text('Create New Member');
        
        const formHTML = `
            <form id="pmm-create-form" class="pmm-member-form">
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">First Name</label></th>
                        <td><input type="text" id="first_name" name="first_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name</label></th>
                        <td><input type="text" id="last_name" name="last_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="company">Company</label></th>
                        <td><input type="text" id="company" name="company"></td>
                    </tr>
                    <tr>
                        <th><label for="address_1">Address</label></th>
                        <td><input type="text" id="address_1" name="address_1"></td>
                    </tr>
                    <tr>
                        <th><label for="city">City</label></th>
                        <td><input type="text" id="city" name="city"></td>
                    </tr>
                    <tr>
                        <th><label for="postcode">Postal Code</label></th>
                        <td><input type="text" id="postcode" name="postcode"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone"></td>
                    </tr>
                    <tr>
                        <th><label for="product_id">Membership Type</label></th>
                        <td>
                            <select id="product_id" name="product_id" required>
                                <option value="">Select membership type</option>
                                <option value="9503">Private - Auto Renewal</option>
                                <option value="10968">Private - Manual Renewal</option>
                                <option value="28736">Pension - Auto Renewal</option>
                                <option value="28735">Pension - Manual Renewal</option>
                                <option value="30734">Union - Auto Renewal</option>
                                <option value="19221">Union - Manual Renewal</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div class="pmm-actions">
                    <input type="submit" class="button button-primary" value="Create Member">
                    <button type="button" class="button pmm-modal-close">Cancel</button>
                </div>
            </form>
        `;
        
        $('#pmm-modal-body').html(formHTML);
    }
    
    function initExportFeature() {
        $(document).on('click', '#pmm-export-csv', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const originalText = button.text();
            
            button.text('Exporting...').prop('disabled', true);
            
            // Create form with current filters and submit
            const form = $('<form>', {
                method: 'POST',
                action: pmmAdmin.ajax_url
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'pmm_export_csv'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: pmmAdmin.nonce
            }));
            
            // Add current filter values
            const currentUrl = new URL(window.location.href);
            const params = currentUrl.searchParams;
            
            params.forEach((value, key) => {
                if (key !== 'page') {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: key,
                        value: value
                    }));
                }
            });
            
            $('body').append(form);
            form.submit();
            form.remove();
            
            setTimeout(() => {
                button.text(originalText).prop('disabled', false);
            }, 2000);
        });
    }
    
    function showLoading(container) {
        $(container).prepend('<div class="pmm-loading"></div>');
    }
    
    function hideLoading(container) {
        $(container).find('.pmm-loading').remove();
    }
    
    function showNotice(type, message) {
        const notice = $(`<div class="pmm-notice ${type}">${message}</div>`);
        $('.wrap h1').after(notice);
        
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 5000);
    }
    
})(jQuery);