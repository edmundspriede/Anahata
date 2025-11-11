<?php

/**
 * Shortcode: [user_checkin_form]
 * Display a user search and check-in form for events
 */
function user_checkin_form_shortcode() {
    // Security check - only admins can use this
    if ( ! current_user_can( 'manage_options' ) ) {
        return '<p>You do not have permission to access this form.</p>';
    }
    
    // Get current event/class ID
    $class_id = get_the_ID();
    
    // Generate unique ID for this shortcode instance to support multiple forms on same page
    static $instance_counter = 0;
    $instance_counter++;
    $unique_id = 'checkin_' . $instance_counter . '_' . uniqid();
    
    ob_start();
    ?>
    <div class="user-checkin-wrapper">
        <style>
            .user-checkin-wrapper { max-width: 600px; margin: 20px 0; }
            
            /* Select Button Styling */
            .user-select-btn {
                width: 100%;
                padding: 12px 15px;
                font-size: 16px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: left;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .user-select-btn:hover { background: #f5f5f5; }
            .user-select-btn .arrow { font-size: 12px; }
            .selected-user-text { color: #333; }
            .placeholder-text { color: #999; }
            
            /* Modal Styling */
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                justify-content: center;
                align-items: center;
            }
            .modal-overlay.active { display: flex; }
            
            .modal-content {
                background: white;
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            }
            
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-header h3 { margin: 0; }
            .modal-close {
                display: none;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
            }
            .modal-close:hover { color: #000; }
            
            .modal-body {
                padding: 20px;
                overflow-y: auto;
                max-height: calc(80vh - 140px);
            }
            
            .search-box {
                margin-bottom: 15px;
            }
            .search-box input {
                width: 100%;
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .user-list {
                border: 1px solid #ddd;
                border-radius: 4px;
                max-height: 300px;
                overflow-y: auto;
            }
            .user-item {
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                cursor: pointer;
                transition: background 0.2s;
            }
            .user-item:last-child { border-bottom: none; }
            .user-item:hover { background: #f5f5f5; }
            .user-item.selected { background: #e3f2fd; }
            .user-name { font-weight: bold; color: #333; }
            .user-details { font-size: 13px; color: #666; margin-top: 4px; }
            
            .no-users {
                padding: 20px;
                text-align: center;
                color: #999;
            }
            
            /* Membership Details in Modal */
            .membership-details {
                display: none;
                margin-top: 20px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .membership-details.active { display: block; }
            .membership-details h4 { margin-top: 0; margin-bottom: 15px; }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: bold; color: #555; }
            .detail-value { color: #333; }
            
            .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #ddd;
                display: flex !important;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 10px;
                background: white;
                position: relative;
                z-index: 10;
                flex-shrink: 0;
            }
            .modal-error {
                color: #721c24;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                display: none;
            }
            .modal-error.active {
                display: block;
            }
            .modal-success {
                color: #155724;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                display: none;
            }
            .modal-success.active {
                display: block;
            }
            .modal-message {
                flex: 1;
                margin-right: 10px;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: background 0.2s;
            }
            .btn-cancel {
                background: #f0f0f0;
                color: #333;
            }
            .btn-cancel:hover { background: #e0e0e0; }
            .btn-primary {
                background: #0073aa;
                color: white;
            }
            .btn-primary:hover { background: #005a87; }
            .btn-primary:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            
            .user-count {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            
            /* Top Page Notification */
            .page-notification {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 999999;
                padding: 15px 30px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: none;
                min-width: 300px;
                text-align: center;
                animation: slideDown 0.3s ease-out;
            }
            .page-notification.show { display: block; }
            .page-notification.success {
                background: #d4edda;
                color: #155724;
                border: 2px solid #c3e6cb;
            }
            .page-notification.error {
                background: #f8d7da;
                color: #721c24;
                border: 2px solid #f5c6cb;
            }
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            
            .message {
                padding: 12px;
                margin: 15px 0;
                border-radius: 4px;
            }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            
            /* Check-in Options Styling */
            .checkin-options {
                display: none;
                margin-top: 20px;
                padding: 15px;
                background: #f0f8ff;
                border: 1px solid #b3d9ff;
                border-radius: 4px;
            }
            .checkin-options.active { display: block; }
            .checkin-options h4 { margin-top: 0; margin-bottom: 15px; color: #0073aa; }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #555;
            }
            .form-group select,
            .form-group input[type="text"],
            .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .form-group textarea {
                min-height: 60px;
                resize: vertical;
            }
            /* Toggle Switch Styling */
            .toggle-field {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            .toggle-field label {
                margin: 0;
                font-weight: bold;
                color: #555;
            }
            .toggle-field .field-meta {
                font-size: 12px;
                color: #999;
                font-weight: normal;
                margin-left: 5px;
            }
            
            /* Toggle Switch */
            .toggle-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 30px;
            }
            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 30px;
            }
            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 22px;
                width: 22px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            .toggle-switch input:checked + .toggle-slider {
                background-color: #4CAF50;
            }
            .toggle-switch input:checked + .toggle-slider:before {
                transform: translateX(30px);
            }
            .toggle-slider:after {
                content: 'OFF';
                color: white;
                display: block;
                position: absolute;
                transform: translate(-50%, -50%);
                top: 50%;
                left: 70%;
                font-size: 10px;
                font-weight: bold;
            }
            .toggle-switch input:checked + .toggle-slider:after {
                content: 'ON';
                left: 30%;
            }
            .field-note {
                font-size: 12px;
                color: #666;
                margin-top: 3px;
                font-style: italic;
            }
            .onetime-note {
                display: none;
                margin-top: 10px;
            }
            .onetime-note.active { display: block; }
            
            /* User Registration Form Styling */
            .create-user-prompt {
                padding: 20px;
                text-align: center;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-top: 10px;
            }
            .create-user-prompt p {
                margin: 0 0 15px 0;
                color: #666;
            }
            .btn-create-user {
                background: #0073aa;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            .btn-create-user:hover { background: #005a87; }
            
            .user-registration-form {
                display: none;
                margin-top: 20px;
                padding: 20px;
                background: #f0f8ff;
                border: 1px solid #b3d9ff;
                border-radius: 4px;
            }
            .user-registration-form.active { display: block; }
            .user-registration-form h4 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #0073aa;
            }
            .registration-form-group {
                margin-bottom: 15px;
            }
            .registration-form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #555;
            }
            .registration-form-group input,
            .registration-form-group select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .registration-form-group input:focus,
            .registration-form-group select:focus {
                outline: none;
                border-color: #0073aa;
                box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            }
            .registration-form-group .field-hint {
                font-size: 12px;
                color: #666;
                margin-top: 3px;
                font-style: italic;
            }
            .registration-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            .registration-actions button {
                flex: 1;
            }
        </style>

        <div>
           
            <button type="button" class="user-select-btn" id="open-user-modal-<?php echo $unique_id; ?>">
                <span class="placeholder-text" id="selected-user-display-<?php echo $unique_id; ?>">-- Select a user --</span>
                <span class="arrow">▼</span>
            </button>
        </div>

        <div id="message-container-<?php echo $unique_id; ?>" style="margin-top: 15px;"></div>
        
        <!-- Top Page Notification -->
        <div id="page-notification-<?php echo $unique_id; ?>" class="page-notification"></div>

        <!-- Modal -->
        <div class="modal-overlay" id="user-modal-<?php echo $unique_id; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Select User for Check-in</h3>
                    <button type="button" class="modal-close" id="close-modal-<?php echo $unique_id; ?>">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="search-box">
                        <input type="text" id="user-search-<?php echo $unique_id; ?>" placeholder="Search by name, email, or user ID..." autocomplete="off">
                        <div class="user-count" id="user-count-<?php echo $unique_id; ?>"></div>
                    </div>
                    
                    <div class="user-list" id="user-list-<?php echo $unique_id; ?>">
                        <div style="padding: 20px; text-align: center; color: #999;">Loading users...</div>
                    </div>
                    
                    <div class="membership-details" id="membership-details-<?php echo $unique_id; ?>">
                        <h4>Membership Information</h4>
                        <div class="detail-row">
                            <span class="detail-label">User ID:</span>
                            <span class="detail-value" id="detail-user-id-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value" id="detail-name-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value" id="detail-email-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Membership Type:</span>
                            <span class="detail-value" id="detail-membership-type-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Start Date:</span>
                            <span class="detail-value" id="detail-membership-start-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">End Date:</span>
                            <span class="detail-value" id="detail-membership-end-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Initial Check-ins:</span>
                            <span class="detail-value" id="detail-checkins-initial-<?php echo $unique_id; ?>"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Remaining Check-ins:</span>
                            <span class="detail-value" id="detail-checkins-remaining-<?php echo $unique_id; ?>"></span>
                        </div>
                    </div>
                    
                    <div class="checkin-options" id="checkin-options-<?php echo $unique_id; ?>">
                        <h4>Check-in Options</h4>
                        
                        <div class="toggle-field">
                            <div>
                                <label for="online-toggle-<?php echo $unique_id; ?>">Online</label>
                                <span class="field-meta">Name: online</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="online-toggle-<?php echo $unique_id; ?>" name="online" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="toggle-field">
                            <div>
                                <label for="onetime-toggle-<?php echo $unique_id; ?>">Onetime</label>
                                <span class="field-meta">Name: onetime</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="onetime-toggle-<?php echo $unique_id; ?>" name="onetime">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="note-field-<?php echo $unique_id; ?>">Note <span class="field-meta">(note_569)</span></label>
                            <textarea id="note-field-<?php echo $unique_id; ?>" name="note_569" placeholder="Enter note or comment..."></textarea>
                        </div>
                    </div>
                    
                    <!-- User Registration Form -->
                    <div class="user-registration-form" id="user-registration-form-<?php echo $unique_id; ?>">
                        <h4>Create New User</h4>
                        
                        <div class="registration-form-group">
                            <label for="reg-name-<?php echo $unique_id; ?>">Full Name *</label>
                            <input type="text" id="reg-name-<?php echo $unique_id; ?>" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="registration-form-group">
                            <label for="reg-email-<?php echo $unique_id; ?>">Email Address *</label>
                            <input type="email" id="reg-email-<?php echo $unique_id; ?>" placeholder="user@example.com" required>
                            <div class="field-hint">Must be a valid, unique email address</div>
                        </div>
                        
                        <div class="registration-form-group">
                            <label for="reg-password-<?php echo $unique_id; ?>">Password *</label>
                            <input type="password" id="reg-password-<?php echo $unique_id; ?>" placeholder="Enter password" required>
                            <div class="field-hint">Minimum 8 characters recommended</div>
                        </div>
                        
                        <div class="registration-form-group">
                            <label for="reg-checkins-<?php echo $unique_id; ?>">Initial Check-ins</label>
                            <input type="number" id="reg-checkins-<?php echo $unique_id; ?>" value="10" min="0" max="9999">
                            <div class="field-hint">Number of check-ins allowed for this user</div>
                        </div>
                        
                        <div class="registration-actions">
                            <button type="button" class="btn btn-cancel" id="cancel-registration-<?php echo $unique_id; ?>">Cancel</button>
                            <button type="button" class="btn btn-primary" id="submit-registration-<?php echo $unique_id; ?>">Create User &amp; Select</button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                   
                    <button type="button" class="btn btn-cancel" id="modal-cancel-<?php echo $unique_id; ?>">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modal-checkin-<?php echo $unique_id; ?>" disabled>Check In User</button>
                </div>
            </div>
        </div>

        <input type="hidden" id="selected-user-id-<?php echo $unique_id; ?>" value="">
        <input type="hidden" id="class-id-<?php echo $unique_id; ?>" value="<?php echo esc_attr( $class_id ); ?>">
    </div>

    <script>
    (function() {
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is required for the check-in form');
            return;
        }
        
    jQuery(document).ready(function($) {
        // Unique ID for this instance
        var uniqueId = '<?php echo $unique_id; ?>';
        var allUsers = [];
        var selectedUserId = null;

        // Verify elements exist
        console.log('[Checkin Form] Initializing instance:', uniqueId);
        console.log('[Checkin Form] Modal button exists:', $('#open-user-modal-' + uniqueId).length > 0);
        console.log('[Checkin Form] Checkin button exists:', $('#modal-checkin-' + uniqueId).length > 0);
        console.log('[Checkin Form] Modal footer exists:', $('#user-modal-' + uniqueId + ' .modal-footer').length > 0);
        console.log('[Checkin Form] Cancel button exists:', $('#modal-cancel-' + uniqueId).length > 0);
        
        // Ensure modal footer is visible on init
        $('#user-modal-' + uniqueId + ' .modal-footer').show();

        // Open modal
        $('#open-user-modal-' + uniqueId).on('click', function() {
            console.log('[Checkin Form] Opening modal for instance:', uniqueId);
            $('#user-modal-' + uniqueId).addClass('active');
            // Clear all messages
            $('#modal-error-' + uniqueId).removeClass('active').empty();
            $('#modal-success-' + uniqueId).removeClass('active').empty();
            // Show empty state with search prompt
            $('#user-list-' + uniqueId).html('<div class="no-users">Type to search for users by name, email, or ID...</div>').show();
            $('#user-search-' + uniqueId).show().val('');
            $('#user-count-' + uniqueId).text('').show();
            // Hide membership and check-in sections
            $('#membership-details-' + uniqueId).removeClass('active');
            $('#checkin-options-' + uniqueId).removeClass('active');
            // Reset allUsers array
            allUsers = [];
        });

        // Close modal
        function closeModal() {
            $('#user-modal-' + uniqueId).removeClass('active');
            $('#user-search-' + uniqueId).val('');
            $('#membership-details-' + uniqueId).removeClass('active');
            $('#checkin-options-' + uniqueId).removeClass('active');
            $('#modal-error-' + uniqueId).removeClass('active').empty();
            $('#modal-success-' + uniqueId).removeClass('active').empty();
            // Hide registration form and show search
            $('#user-registration-form-' + uniqueId).removeClass('active');
            $('#user-list-' + uniqueId).show();
            $('#user-search-' + uniqueId).show();
            $('#user-count-' + uniqueId).show();
            // Clear registration fields
            $('#reg-name-' + uniqueId).val('');
            $('#reg-email-' + uniqueId).val('');
            $('#reg-password-' + uniqueId).val('');
            $('#reg-checkins-' + uniqueId).val('10');
            $('#submit-registration-' + uniqueId).prop('disabled', false).text('Create User & Select');
            // Reset check-in form fields
            $('#online-toggle-' + uniqueId).prop('checked', true);
            $('#onetime-toggle-' + uniqueId).prop('checked', false);
            $('#note-field-' + uniqueId).val('');
            // Reset button state
            $('#modal-checkin-' + uniqueId).prop('disabled', true);
        }

        $('#close-modal-' + uniqueId + ', #modal-cancel-' + uniqueId).on('click', closeModal);
        
        // Close on overlay click
        $('#user-modal-' + uniqueId).on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Load users via AJAX
        function loadUsers() {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_all_users_for_checkin',
                    nonce: '<?php echo wp_create_nonce('user_checkin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        allUsers = response.data;
                        renderUserList(allUsers);
                    } else {
                        $('#user-list-' + uniqueId).html('<div class="no-users">Error loading users</div>');
                    }
                },
                error: function() {
                    $('#user-list-' + uniqueId).html('<div class="no-users">Failed to load users. Please try again.</div>');
                }
            });
        }

        // Render user list
        function renderUserList(users) {
            const $list = $('#user-list-' + uniqueId);
            
            if (users.length === 0) {
                // Show "Create New User" option when no results
                $list.html(`
                    <div class="create-user-prompt">
                        <p>No users found matching your search.</p>
                        <button type="button" class="btn-create-user" id="show-create-user-${uniqueId}">
                            + Create New User
                        </button>
                    </div>
                `);
                return;
            }

            let html = '';
            users.forEach(function(user) {
                // Escape HTML to prevent XSS
                const escapedName = $('<div>').text(user.display_name).html();
                const escapedEmail = $('<div>').text(user.user_email).html();
                html += `<div class="user-item" data-user-id="${user.ID}" data-instance="${uniqueId}">
                    <div class="user-name">${escapedName}</div>
                    <div class="user-details">${escapedEmail} • ID: ${user.ID}</div>
                </div>`;
            });

            $list.html(html);
            $('#user-count-' + uniqueId).text(`Showing ${users.length} user${users.length !== 1 ? 's' : ''}`);
        }
        
        // Show registration form when "Create New User" is clicked
        $(document).on('click', '#show-create-user-' + uniqueId, function() {
            console.log('[Checkin Form] Showing user registration form');
            $('#user-list-' + uniqueId).hide();
            $('#user-search-' + uniqueId).hide();
            $('#user-count-' + uniqueId).hide();
            $('#user-registration-form-' + uniqueId).addClass('active');
            // Ensure modal footer remains visible during registration
            $('.modal-footer').show();
        });
        
        // Cancel registration and return to search
        $('#cancel-registration-' + uniqueId).on('click', function() {
            console.log('[Checkin Form] Cancelling user registration');
            $('#user-registration-form-' + uniqueId).removeClass('active');
            $('#user-list-' + uniqueId).show();
            $('#user-search-' + uniqueId).show();
            $('#user-count-' + uniqueId).show();
            // Clear form fields
            $('#reg-name-' + uniqueId).val('');
            $('#reg-email-' + uniqueId).val('');
            $('#reg-password-' + uniqueId).val('');
            $('#reg-checkins-' + uniqueId).val('10');
            // Ensure modal footer is visible
            $('.modal-footer').show();
            $('#modal-checkin-' + uniqueId).show();
        });

        // Search users - load via AJAX on input
        var searchTimeout = null;
        $('#user-search-' + uniqueId).on('input', function() {
            const searchTerm = $(this).val().trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (searchTerm === '') {
                // Show empty state when search is cleared
                $('#user-list-' + uniqueId).html('<div class="no-users">Type to search for users by name, email, or ID...</div>');
                $('#user-count-' + uniqueId).text('');
                allUsers = [];
                return;
            }
            
            // Show loading state
            $('#user-list-' + uniqueId).html('<div style="padding: 20px; text-align: center; color: #999;">Searching...</div>');
            
            // Debounce search - wait 500ms after user stops typing
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_all_users_for_checkin',
                        nonce: '<?php echo wp_create_nonce('user_checkin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Filter users based on search term
                            const searchLower = searchTerm.toLowerCase();
                            const filtered = response.data.filter(function(user) {
                                return user.display_name.toLowerCase().includes(searchLower) ||
                                       user.user_email.toLowerCase().includes(searchLower) ||
                                       user.ID.toString().includes(searchLower);
                            });
                            allUsers = filtered;
                            renderUserList(filtered);
                        } else {
                            $('#user-list-' + uniqueId).html('<div class="no-users">Error loading users</div>');
                        }
                    },
                    error: function() {
                        $('#user-list-' + uniqueId).html('<div class="no-users">Failed to load users. Please try again.</div>');
                    }
                });
            }, 500);
        });

        // Select user (only for this instance)
        $(document).on('click', '.user-item[data-instance="' + uniqueId + '"]', function() {
            $('.user-item[data-instance="' + uniqueId + '"]').removeClass('selected');
            $(this).addClass('selected');
            
            selectedUserId = $(this).data('user-id');
            $('#selected-user-id-' + uniqueId).val(selectedUserId);
            
            // Ensure modal footer is visible when selecting user
            $('.modal-footer').show();
            $('#modal-checkin-' + uniqueId).show();
            
            // Load membership details
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_user_membership_data',
                    user_id: selectedUserId,
                    nonce: '<?php echo wp_create_nonce('user_checkin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        $('#detail-user-id-' + uniqueId).text(data.user_id);
                        $('#detail-name-' + uniqueId).text(data.name);
                        $('#detail-email-' + uniqueId).text(data.email);
                        $('#detail-membership-type-' + uniqueId).text(data.membership_type || 'N/A');
                        // Format dates properly
                        $('#detail-membership-start-' + uniqueId).text(data.membership_start_formatted || 'N/A');
                        $('#detail-membership-end-' + uniqueId).text(data.membership_end_formatted || 'N/A');
                        
                        // Show Initial (original total - never changes)
                        const initial = parseInt(data.checkins_initial) || 0;
                        $('#detail-checkins-initial-' + uniqueId).text(initial);
                        
                        // Show Remaining (current available - decrements)
                        const remaining = parseInt(data.checkins_remaining) || 0;
                        const used = initial - remaining;
                        $('#detail-checkins-remaining-' + uniqueId).text(remaining + ' (Used: ' + used + ')');
                        
                        $('#membership-details-' + uniqueId).addClass('active');
                        $('#checkin-options-' + uniqueId).addClass('active');
                        
                        // Ensure modal footer is visible
                        $('.modal-footer').show();
                        $('#modal-checkin-' + uniqueId).show().prop('disabled', false);
                        console.log('[Checkin Form] Button enabled for user:', data.user_id);
                        
                        // Update button text with escaped data
                        const displayText = $('<div>').text(data.name + ' (' + data.email + ')').html();
                        $('#selected-user-display-' + uniqueId)
                            .removeClass('placeholder-text')
                            .addClass('selected-user-text')
                            .html(displayText);
                    } else {
                        $('#message-container-' + uniqueId).html('<div class="message error">Failed to load membership details</div>');
                    }
                },
                error: function() {
                    $('#message-container-' + uniqueId).html('<div class="message error">Error loading membership details</div>');
                }
            });
        });

        // Submit user registration
        $('#submit-registration-' + uniqueId).on('click', function() {
            const name = $('#reg-name-' + uniqueId).val().trim();
            const email = $('#reg-email-' + uniqueId).val().trim();
            const password = $('#reg-password-' + uniqueId).val();
            const checkins = $('#reg-checkins-' + uniqueId).val();
            
            // Clear previous messages
            $('#modal-error-' + uniqueId).removeClass('active').empty();
            $('#modal-success-' + uniqueId).removeClass('active').empty();
            
            // Validate inputs
            if (!name) {
                $('#modal-error-' + uniqueId).html('<strong>✗ Error:</strong> Please enter a full name').addClass('active');
                return;
            }
            if (!email) {
                $('#modal-error-' + uniqueId).html('<strong>✗ Error:</strong> Please enter an email address').addClass('active');
                return;
            }
            if (!password || password.length < 6) {
                $('#modal-error-' + uniqueId).html('<strong>✗ Error:</strong> Password must be at least 6 characters').addClass('active');
                return;
            }
            
            console.log('[Checkin Form] Submitting user registration:', { name, email, checkins });
            
            $(this).prop('disabled', true).text('Creating User...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'register_user_for_checkin',
                    name: name,
                    email: email,
                    password: password,
                    checkins_initial: checkins,
                    nonce: '<?php echo wp_create_nonce('user_checkin_nonce'); ?>'
                },
                success: function(response) {
                    console.log('[Checkin Form] Registration response:', response);
                    
                    if (response.success) {
                        const newUserId = response.data.user_id;
                        console.log('[Checkin Form] User created successfully. ID:', newUserId);
                        
                        // Show success message
                        $('#page-notification-' + uniqueId)
                            .removeClass('error')
                            .addClass('success show')
                            .html('✓ User created successfully! Reloading page...');
                        
                        // Show success in modal too
                        $('#modal-success-' + uniqueId)
                            .html('<strong>✓ Success:</strong> User created successfully! Reloading page...')
                            .addClass('active');
                        
                        // Reload page after 2 seconds to show the success message
                        setTimeout(function() {
                            console.log('[Checkin Form] Reloading page after user registration...');
                            location.reload();
                        }, 2000);
                    } else {
                        $('#modal-error-' + uniqueId)
                            .html('<strong>✗ Error:</strong> ' + $('<div>').text(response.data || 'Failed to create user').html())
                            .addClass('active');
                        $('#submit-registration-' + uniqueId).prop('disabled', false).text('Create User & Select');
                    }
                },
                error: function() {
                    $('#modal-error-' + uniqueId)
                        .html('<strong>✗ Error:</strong> Network error. Please try again.')
                        .addClass('active');
                    $('#submit-registration-' + uniqueId).prop('disabled', false).text('Create User & Select');
                }
            });
        });

        // Check in user
        let isSubmitting = false; // Prevent multiple submissions
        var $checkinBtn = $('#modal-checkin-' + uniqueId);
        console.log('[Checkin Form] Binding click handler to button:', $checkinBtn.length > 0 ? 'SUCCESS' : 'FAILED');
        
        $checkinBtn.on('click', function() {
            console.log('[Checkin Form] Check-in button clicked for instance:', uniqueId);
            
            const userId = $('#selected-user-id-' + uniqueId).val();
            const classId = $('#class-id-' + uniqueId).val();
            const online = $('#online-toggle-' + uniqueId).is(':checked') ? '1' : '0';
            const onetime = $('#onetime-toggle-' + uniqueId).is(':checked') ? '1' : '0';
            const note569 = $('#note-field-' + uniqueId).val();

            console.log('[Checkin Form] Submitting check-in with values:', { 
                userId: userId, 
                classId: classId, 
                online: online, 
                onetime: onetime,
                note: note569 ? 'Yes' : 'No'
            });

            // Clear previous messages
            $('#modal-error-' + uniqueId).removeClass('active').empty();
            $('#modal-success-' + uniqueId).removeClass('active').empty();
            
            if (!userId || isSubmitting) {
                console.log('[Checkin Form] Validation failed: no user or already submitting');
                return;
            }

            isSubmitting = true;
            $(this).prop('disabled', true).text('Processing...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'process_user_checkin',
                    user_id: userId,
                    class_id: classId,
                    online: online,
                    onetime: onetime,
                    note_569: note569,
                    nonce: '<?php echo wp_create_nonce('user_checkin_nonce'); ?>'
                },
                success: function(response) {
                    console.log('[Checkin Form] AJAX Response received:', response);
                    console.log('[Checkin Form] Response success:', response.success);
                    console.log('[Checkin Form] Response data:', response.data);
                    isSubmitting = false;
                    
                    if (response.success) {
                        console.log('[Checkin Form] Check-in successful!');
                        
                        // Close modal immediately
                        $('#user-modal-' + uniqueId).removeClass('active');
                        
                        // Show SUCCESS at top center of page with detailed info
                        const checkinsInfo = response.data.checkins_remaining !== undefined 
                            ? ` (Initial: ${response.data.checkins_initial}, Used: ${response.data.checkins_used}, Remaining: ${response.data.checkins_remaining})`
                            : '';
                        const successMsg = (response.data.message || 'User checked in successfully!') + checkinsInfo;
                        const escapedMessage = $('<div>').text(successMsg).html();
                        $('#page-notification-' + uniqueId)
                            .removeClass('error')
                            .addClass('success show')
                            .html('✓ ' + escapedMessage);
                        
                        // Also show in modal for backup
                        $('#modal-success-' + uniqueId)
                            .html('<strong>✓ Success:</strong> ' + escapedMessage)
                            .addClass('active');
                        
                        // Reload page after showing success message (3 seconds delay)
                        setTimeout(function() {
                            console.log('[Checkin Form] Reloading page after successful check-in...');
                            location.reload();
                        }, 3000);
                        
                    } else {
                        console.error('[Checkin Form] Check-in failed:', response.data);
                        
                        // Show ERROR at top center of page
                        const errorMsg = response.data || 'Check-in failed';
                        const escapedError = $('<div>').text(errorMsg).html();
                        $('#page-notification-' + uniqueId)
                            .removeClass('success')
                            .addClass('error show')
                            .html('✗ ' + escapedError);
                        
                        // Also show in modal
                        $('#modal-error-' + uniqueId)
                            .html('<strong>✗ Error:</strong> ' + escapedError)
                            .addClass('active');
                        
                        // Re-enable button so user can try again or close
                        $('#modal-checkin-' + uniqueId).prop('disabled', false).text('Try Again');
                        
                        // No reload on error - allow user to review and try again
                        console.log('[Checkin Form] Error displayed. Page will NOT reload.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Checkin Form] AJAX Error:', { xhr, status, error });
                    isSubmitting = false;
                    
                    // Show ERROR at top center of page
                    $('#page-notification-' + uniqueId)
                        .removeClass('success')
                        .addClass('error show')
                        .html('✗ Network error. Please check your connection.');
                    
                    // Also show in modal
                    $('#modal-error-' + uniqueId)
                        .html('<strong>✗ Error:</strong> Network error. Please check your connection.')
                        .addClass('active');
                    
                    // Re-enable button
                    $('#modal-checkin-' + uniqueId).prop('disabled', false).text('Try Again');
                    
                    // No reload on network error - allow user to retry
                    console.log('[Checkin Form] Network error displayed. Page will NOT reload.');
                }
            });
        });
    });
    })(); // End IIFE
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'user_checkin_form', 'user_checkin_form_shortcode' );

/**
 * AJAX Handler: Get all users for check-in
 * Security: Nonce verification and capability check
 */
add_action( 'wp_ajax_get_all_users_for_checkin', 'get_all_users_for_checkin_ajax' );
function get_all_users_for_checkin_ajax() {
    // Verify nonce for security
    check_ajax_referer( 'user_checkin_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $users = get_users( array(
        'orderby' => 'display_name',
        'order' => 'ASC',
        'fields' => array( 'ID', 'display_name', 'user_email' )
    ) );

    wp_send_json_success( $users );
}

/**
 * AJAX Handler: Get user membership data
 * Security: Input validation, nonce verification, and capability check
 */
add_action( 'wp_ajax_get_user_membership_data', 'get_user_membership_data_ajax' );
function get_user_membership_data_ajax() {
    // Verify nonce for security
    check_ajax_referer( 'user_checkin_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    // Validate input exists
    if ( ! isset( $_POST['user_id'] ) ) {
        wp_send_json_error( 'Missing user ID' );
    }

    $user_id = absint( $_POST['user_id'] );
    
    // Validate user ID is valid
    if ( $user_id <= 0 ) {
        wp_send_json_error( 'Invalid user ID' );
    }
    
    $user = get_user_by( 'ID', $user_id );
    
    if ( ! $user ) {
        wp_send_json_error( 'User not found' );
    }

    // Get membership dates
    $membership_start = get_user_meta( $user_id, 'membership_start', true );
    $membership_end = get_user_meta( $user_id, 'membership_end', true );
    
    // Format dates - convert Unix timestamp to readable format
    $membership_start_formatted = '';
    $membership_end_formatted = '';
    
    if ( ! empty( $membership_start ) && is_numeric( $membership_start ) ) {
        $membership_start_formatted = date_i18n( 'F j, Y', $membership_start );
    } elseif ( ! empty( $membership_start ) ) {
        $membership_start_formatted = $membership_start;
    }
    
    if ( ! empty( $membership_end ) && is_numeric( $membership_end ) ) {
        $membership_end_formatted = date_i18n( 'F j, Y', $membership_end );
    } elseif ( ! empty( $membership_end ) ) {
        $membership_end_formatted = $membership_end;
    }
    
    $data = array(
        'user_id' => $user_id,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'membership_type' => get_user_meta( $user_id, 'membership_type', true ),
        'membership_start' => $membership_start,
        'membership_start_formatted' => $membership_start_formatted,
        'membership_end' => $membership_end,
        'membership_end_formatted' => $membership_end_formatted,
        'checkins_initial' => get_user_meta( $user_id, 'checkins_initial', true ),
        'checkins_remaining' => get_user_meta( $user_id, 'checkins_remaining', true )
    );

    wp_send_json_success( $data );
}

/**
 * AJAX Handler: Process user check-in
 * MODIFIED: Uses standard WordPress post meta (wp_postmeta) instead of custom table
 * Security: Comprehensive validation, duplicate prevention, and rollback on failure
 * Features: 
 * - Prevents duplicate check-ins on same calendar day
 * - Validates all inputs
 * - Uses meta_input for simplicity (like old.php)
 * - Implements rollback mechanism if update fails
 */
add_action( 'wp_ajax_process_user_checkin', 'process_user_checkin_ajax' );
function process_user_checkin_ajax() {
    // Verify nonce for security
    check_ajax_referer( 'user_checkin_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    // Verify 'checkins' post type exists
    if ( ! post_type_exists( 'checkins' ) ) {
        error_log( '[Check-in Error] The "checkins" post type does not exist. Please register it first.' );
        wp_send_json_error( 'Check-in system not properly configured. Please contact the administrator.' );
    }

    // Validate input exists
    if ( ! isset( $_POST['user_id'] ) || ! isset( $_POST['class_id'] ) ) {
        wp_send_json_error( 'Missing required parameters' );
    }

    $user_id = absint( $_POST['user_id'] );
    $class_id = absint( $_POST['class_id'] );
    $online = isset( $_POST['online'] ) ? sanitize_text_field( $_POST['online'] ) : '0';
    $onetime = isset( $_POST['onetime'] ) ? sanitize_text_field( $_POST['onetime'] ) : '0';
    $note_569 = isset( $_POST['note_569'] ) ? sanitize_textarea_field( $_POST['note_569'] ) : '';
    
    // Derive location_type from Online switcher
    $location_type = ( $online === '1' ) ? 'online' : 'studio';
    
    // Validate IDs are positive integers
    if ( $user_id <= 0 ) {
        wp_send_json_error( 'Invalid user ID' );
    }
    
    if ( $class_id <= 0 ) {
        wp_send_json_error( 'Invalid class ID' );
    }
    
    // Validate user exists
    if ( ! get_user_by( 'ID', $user_id ) ) {
        wp_send_json_error( 'Invalid user' );
    }
    
    // Validate class/post exists
    if ( ! get_post( $class_id ) ) {
        wp_send_json_error( 'Invalid class or event' );
    }

    // Check for duplicate check-in: Same user + Same class + Same calendar day
    // This allows users to check into DIFFERENT classes on the same day
    $today_date = date( 'Y-m-d', current_time( 'timestamp' ) );
    
    error_log( sprintf(
        '[Check-in Debug] Checking for duplicates: user_id=%d, class_id=%d, date=%s',
        $user_id, $class_id, $today_date
    ) );
    
    $existing_checkins = get_posts( array(
        'post_type' => 'checkins',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'user_id',
                'value' => (string) $user_id,  // Ensure string comparison
                'compare' => '='
            ),
            array(
                'key' => 'class_id',
                'value' => (string) $class_id,  // Ensure string comparison
                'compare' => '='
            )
        )
    ) );
    
    error_log( sprintf(
        '[Check-in Debug] Found %d existing check-ins for user %d + class %d',
        count( $existing_checkins ), $user_id, $class_id
    ) );
    
    // Manually verify dates to ensure same calendar day check
    if ( ! empty( $existing_checkins ) ) {
        foreach ( $existing_checkins as $checkin ) {
            $saved_user_id = get_post_meta( $checkin->ID, 'user_id', true );
            $saved_class_id = get_post_meta( $checkin->ID, 'class_id', true );
            $checkin_datetime = get_post_meta( $checkin->ID, 'datetime', true );
            
            error_log( sprintf(
                '[Check-in Debug] Existing check-in ID %d: user=%s, class=%s, datetime=%s',
                $checkin->ID, $saved_user_id, $saved_class_id, $checkin_datetime
            ) );
            
            if ( ! empty( $checkin_datetime ) ) {
                $checkin_date = date( 'Y-m-d', strtotime( $checkin_datetime ) );
                
                // Only block if SAME user + SAME class + SAME date
                if ( $checkin_date === $today_date && 
                     $saved_user_id == $user_id && 
                     $saved_class_id == $class_id ) {
                    error_log( sprintf(
                        '[Check-in Duplicate] BLOCKED: User %d already checked in to class %d today. Existing check-in ID: %d',
                        $user_id, $class_id, $checkin->ID
                    ) );
                    wp_send_json_error( sprintf(
                        'User has already checked in to this class today (Check-in ID: %d)',
                        $checkin->ID
                    ) );
                }
            }
        }
    }
    
    error_log( sprintf(
        '[Check-in Debug] No duplicate found. User %d can check into class %d',
        $user_id, $class_id
    ) );
    
    // Get membership limits
    $checkins_initial = (int) get_user_meta( $user_id, 'checkins_initial', true );
    $checkins_remaining = (int) get_user_meta( $user_id, 'checkins_remaining', true );
    
    error_log( sprintf(
        '[Check-in Debug] User %d membership: initial=%d, remaining=%d',
        $user_id, $checkins_initial, $checkins_remaining
    ) );
    
    // Check if user has check-ins left (based on remaining count)
    if ( $checkins_remaining <= 0 ) {
        error_log( sprintf(
            '[Check-in Blocked] User %d has no check-ins remaining (initial: %d, remaining: %d)',
            $user_id, $checkins_initial, $checkins_remaining
        ) );
        wp_send_json_error( sprintf(
            'User has no check-ins remaining. Original: %d, Used: %d',
            $checkins_initial, $checkins_initial
        ) );
    }

    // Create check-in post with sanitized title
    $user_obj = get_user_by( 'ID', $user_id );
    $post_obj = get_post( $class_id );
    
    // Format location type for display (replace + with space)
    $location_display = str_replace( '+', ' + ', $location_type );
    
    // Title format: "Class Title location_type – User Name"
    // Example: "Yoga for Practitioners studio + online – Sandra Spūle"
    $post_title = sprintf(
        '%s %s – %s',
        sanitize_text_field( $post_obj->post_title ),
        $location_display,
        sanitize_text_field( $user_obj->display_name )
    );
    
    // Prepare meta data for standard WordPress meta
    $current_datetime = current_time( 'mysql' );
    
    $meta_input = array(
        'user_id' => $user_id,
        'class_id' => $class_id,
        'datetime' => $current_datetime,
        'location_type' => $location_type,
        'online' => $online,
        'onetime' => $onetime
    );
    
    // Add note_569 if provided
    if ( ! empty( $note_569 ) ) {
        $meta_input['note_569'] = $note_569;
    }
    
    // Create check-in post with standard WordPress meta
    $checkin_post = array(
        'post_title' => $post_title,
        'post_type' => 'checkins',
        'post_status' => 'publish',
        'meta_input' => $meta_input  // WordPress handles this automatically
    );
    
    error_log( sprintf(
        '[Check-in Debug] Creating check-in with meta_input: %s',
        print_r( $meta_input, true )
    ) );
    
    $checkin_id = wp_insert_post( $checkin_post );
    
    if ( is_wp_error( $checkin_id ) ) {
        error_log( '[Check-in Error] wp_insert_post failed: ' . $checkin_id->get_error_message() );
        wp_send_json_error( 'Failed to create check-in post: ' . $checkin_id->get_error_message() );
    }
    
    if ( ! $checkin_id ) {
        error_log( '[Check-in Error] wp_insert_post returned false' );
        wp_send_json_error( 'Failed to create check-in post' );
    }
    
    // Verify meta was saved
    $saved_user_id = get_post_meta( $checkin_id, 'user_id', true );
    $saved_datetime = get_post_meta( $checkin_id, 'datetime', true );
    
    error_log( sprintf(
        '[Check-in Debug] Check-in created. ID: %d. Verifying meta - user_id: %s, datetime: %s',
        $checkin_id, $saved_user_id, $saved_datetime
    ) );
    
    error_log( sprintf(
        '[Check-in Created] Post ID: %d, User: %d, Class: %d, DateTime: %s, Location: %s, Online: %s, Onetime: %s',
        $checkin_id, $user_id, $class_id, $current_datetime, $location_type, $online, $onetime
    ) );

    // Decrement check-ins remaining (DO NOT touch checkins_initial - it stays constant)
    $new_checkins_remaining = $checkins_remaining - 1;
    $update_result = update_user_meta( $user_id, 'checkins_remaining', $new_checkins_remaining );
    
    // Verify checkins_initial was NOT modified
    $verify_initial = (int) get_user_meta( $user_id, 'checkins_initial', true );
    if ( $verify_initial !== $checkins_initial ) {
        error_log( sprintf(
            '[Check-in Warning] checkins_initial changed unexpectedly! Was %d, now %d',
            $checkins_initial, $verify_initial
        ) );
    }
    
    // Rollback if update fails
    if ( $update_result === false ) {
        wp_delete_post( $checkin_id, true );
        error_log( '[Check-in Error] Failed to update check-in count for user: ' . $user_id );
        wp_send_json_error( 'Failed to update check-in count' );
    }
    
    // Calculate how many check-ins have been used
    $checkins_used = $checkins_initial - $new_checkins_remaining;
    
    error_log( sprintf(
        '[Check-in Success] User %d checked into class %d. Initial: %d, Used: %d, Remaining: %d. Check-in ID: %d',
        $user_id, $class_id, $checkins_initial, $checkins_used, $new_checkins_remaining, $checkin_id
    ) );

    wp_send_json_success( array(
        'message' => 'User checked in successfully!',
        'checkins_initial' => $checkins_initial,
        'checkins_remaining' => $new_checkins_remaining,
        'checkins_used' => $checkins_used,
        'checkin_id' => $checkin_id
    ) );
}

/**
 * AJAX Handler: Register new user from check-in interface
 * Security: Nonce verification, capability check, email validation
 * Creates user with Customer role and membership meta fields
 */
add_action( 'wp_ajax_register_user_for_checkin', 'register_user_for_checkin_ajax' );
function register_user_for_checkin_ajax() {
    // Verify nonce for security
    check_ajax_referer( 'user_checkin_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    // Validate required fields
    if ( ! isset( $_POST['name'] ) || ! isset( $_POST['email'] ) || ! isset( $_POST['password'] ) ) {
        wp_send_json_error( 'Missing required fields' );
    }
    
    $name = sanitize_text_field( $_POST['name'] );
    $email = sanitize_email( $_POST['email'] );
    $password = $_POST['password']; // Don't sanitize password - wp_create_user handles it
    $checkins_initial = isset( $_POST['checkins_initial'] ) ? absint( $_POST['checkins_initial'] ) : 10;
    
    // Validate name
    if ( empty( $name ) ) {
        wp_send_json_error( 'Please enter a valid name' );
    }
    
    // Validate email
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Please enter a valid email address' );
    }
    
    // Check if email already exists
    if ( email_exists( $email ) ) {
        wp_send_json_error( 'This email address is already registered' );
    }
    
    // Validate password
    if ( empty( $password ) || strlen( $password ) < 6 ) {
        wp_send_json_error( 'Password must be at least 6 characters' );
    }
    
    // Generate username from email (before @)
    $username = sanitize_user( substr( $email, 0, strpos( $email, '@' ) ) );
    
    // Ensure username is unique
    $original_username = $username;
    $counter = 1;
    while ( username_exists( $username ) ) {
        $username = $original_username . $counter;
        $counter++;
    }
    
    error_log( sprintf(
        '[User Registration] Creating user: username=%s, email=%s, name=%s, checkins=%d',
        $username, $email, $name, $checkins_initial
    ) );
    
    // Create the user
    $user_id = wp_create_user( $username, $password, $email );
    
    if ( is_wp_error( $user_id ) ) {
        error_log( '[User Registration] wp_create_user failed: ' . $user_id->get_error_message() );
        wp_send_json_error( 'Failed to create user: ' . $user_id->get_error_message() );
    }
    
    // Update user display name
    wp_update_user( array(
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => $name // Can be parsed into first/last if needed
    ) );
    
    // Set user role to customer (WooCommerce role for check-in users)
    $user = new WP_User( $user_id );
    $user->set_role( 'customer' );
    
    // Set membership meta fields
    $current_timestamp = current_time( 'timestamp' );
    $end_timestamp = strtotime( '+1 year', $current_timestamp );
    
    update_user_meta( $user_id, 'checkins_initial', $checkins_initial );
    update_user_meta( $user_id, 'checkins_remaining', $checkins_initial );
    update_user_meta( $user_id, 'membership_type', 'Standard' ); // Default type
    update_user_meta( $user_id, 'membership_start', $current_timestamp );
    update_user_meta( $user_id, 'membership_end', $end_timestamp );
    
    error_log( sprintf(
        '[User Registration] User created successfully. ID: %d, Username: %s, Email: %s, Role: customer, Check-ins: %d',
        $user_id, $username, $email, $checkins_initial
    ) );
    
    wp_send_json_success( array(
        'message' => 'User created successfully!',
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'name' => $name,
        'checkins_initial' => $checkins_initial,
        'checkins_remaining' => $checkins_initial
    ) );
}
