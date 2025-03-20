/**
 * JetPopup Global Form Notification System
 */

function enqueue_jetpopup_notification_script() {
    ?>
    <style>
        /* Hide all default messages */
        .jet-form-builder-message--success,
        .jet-form-builder-message--error,
        #login_error,
        .message,
        .jet-engine-message,
        .jet-form-messages-wrap,
        .jet-form-builder-messages-wrap {
            display: none !important;
        }

        /* Message Container */
        #jet-popup-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999;
            display: none;
        }

        /* Overlay */
        #jet-popup-message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            z-index: 100;
            backdrop-filter: blur(5px) brightness(40%);
        }

        /* Message Box */
        #jet-popup-message-box {
            position: fixed;
            right: 20px;
            bottom: 20px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            z-index: 101;
            box-sizing: border-box;
            text-align: center;
            width: auto;
            max-width: 400px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        #jet-popup-message-box.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Success State */
        #jet-popup-message-box.success {
            background: #28a745;
            border-left: 4px solid #1e7e34;
            color: white;
        }

        /* Error State */
        #jet-popup-message-box.error {
            background: #dc3545;
            border-left: 4px solid #bd2130;
            color: white;
        }

        #jet-popup-message-content {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
            color: inherit;
        }

        .success #jet-popup-message-content {
            margin-bottom: 0;
        }

        #jet-popup-message-close {
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: transparent;
            color: white;
        }

        .error #jet-popup-message-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .success #jet-popup-message-close {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            #jet-popup-message-box {
                width: 80%;
                right: 10%;
            }
        }

        @media (max-width: 767px) {
            #jet-popup-message-box {
                width: 90%;
                right: 5%;
            }
        }
    </style>

    <div id="jet-popup-message">
        <!-- <div id="jet-popup-message-overlay"></div> -->
        <div id="jet-popup-message-box">
            <div id="jet-popup-message-content"></div>
            <button type="button" id="jet-popup-message-close">Aizvērt</button>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Move message container to body
        $('#jet-popup-message').appendTo('body');

        // Clean HTML tags from message
        function cleanMessage(message) {
            // Create a temporary div to handle HTML content
            let temp = document.createElement('div');
            temp.innerHTML = message;
            // Get text content only
            return temp.textContent || temp.innerText || '';
        }

        // Close message function
        function closeMessage() {
            $('#jet-popup-message').hide();
            $('#jet-popup-message-box').removeClass('error success visible');
            $('#jet-popup-message-content').empty();
        }

        // Enhanced showMessage function
        function showMessage(message, type) {
            // Clean the message from HTML tags
            let cleanedMessage = cleanMessage(message);
            
            // Set default messages
            if (type === 'success' && !cleanedMessage) {
                cleanedMessage = 'Action completed successfully';
            }
            
            // Hide all possible message containers
            $('.jet-form-builder-message--success, .jet-form-builder-message--error, #login_error, .message, .jet-engine-message, .jet-form-messages-wrap, .jet-form-builder-messages-wrap').hide();
            
            $('#jet-popup-message-content').text(cleanedMessage);
            $('#jet-popup-message-box').removeClass('error success').addClass(type);
            $('#jet-popup-message').show();
            
            setTimeout(function() {
                $('#jet-popup-message-box').addClass('visible');
            }, 10);

            if (type === 'success') {
                setTimeout(closeMessage, 2000);
            }
        }

        // Close button click
        $('#jet-popup-message-close').click(function() {
            closeMessage();
            return false;
        });

        // Overlay click - only for error messages
        $('#jet-popup-message-overlay').click(function() {
            if ($('#jet-popup-message-box').hasClass('error')) {
                closeMessage();
            }
            return false;
        });

        // ESC key - only for error messages
        $(document).keydown(function(e) {
            if (e.key === 'Escape' && $('#jet-popup-message-box').hasClass('error')) {
                closeMessage();
            }
        });

        // Check for messages
        function checkMessages() {
            var message = '';
            var type = 'error';
            
            var $error = $('.jet-form-builder-message--error:visible, #login_error:visible');
            var $success = $('.jet-form-builder-message--success:visible');
            var $message = $('.message:visible');

            if ($error.length) {
                message = $error.text();
                type = 'error';
            } else if ($success.length) {
                message = $success.text();
                type = 'success';
            } else if ($message.length && !$message.hasClass('jet-form-builder-message')) {
                message = $message.text();
                type = $message.hasClass('updated') ? 'success' : 'error';
            }

            if (message) {
                showMessage(message, type);
            }
        }

        // Enhanced event handlers
        $(document).on('jet-form-builder/ajax/on-fail', function(event, response, $form, data) {
            let message = response.message || 'Form submit failed';
            showMessage(message, 'error');
            
        });    
        
        $(document).on('jet-form-builder/ajax/on-success', function(event, response, $form, data) {
            let message = response.message || 'Form submitted successfully';
            showMessage(message, 'success');
            
            if (window.JetEngine) {
                let formId = $form.data('form-id');
                
                // Delay the grid refresh slightly to ensure proper rendering
                setTimeout(function() {
                    // Update listings marked for this specific form
                    $('.update-on-form-' + formId).each(function() {
                        refreshListing($(this));
                    });
                    
                    // Refresh all listings with check-ins counters
                    refreshAllCheckinsCounters();
                }, 100);
            }
        });

        // Add instant check-in specific handler
        $(document).on('click', '[data-action="checkin"]', function() {
            // Hide any existing messages immediately
            $('.jet-form-builder-message--success, .jet-form-builder-message--error, #login_error, .message, .jet-engine-message, .jet-form-messages-wrap, .jet-form-builder-messages-wrap').hide();
        });

        // Function to refresh all listings that might contain check-ins counters
        function refreshAllCheckinsCounters() {
            $('.jet-listing-grid').each(function() {
                let $listing = $(this);
                if ($listing.find('[data-field*="check"], [data-field*="remain"], .remaining-checkins, .check-in-counter, .checkins-count, .jet-listing-dynamic-field:contains("remain")').length) {
                    refreshListing($listing);
                }
            });
        }

        // Helper function to refresh a JetEngine listing
        function refreshListing($listing) {
            let $items = $listing.find('.jet-listing-grid__items');
            if (!$items.length) return;
            
            let nav = $items.data('nav');
            if (!nav || !nav.query) return;
            
            let args = {
                handler: 'get_listing',
                container: $listing.find('.elementor-widget-container'),
                masonry: false,
                slider: false,
                append: false,
                query: nav.query,
                widgetSettings: nav.widget_settings,
            };
            
            window.JetEngine.ajaxGetListing(args, function(response) {
                if (response.success && response.data.html) {
                    let $container = $listing.children('.elementor-widget-container');
                    $container.html($(response.data.html));
                    
                    // Ensure proper initialization
                    if (window.JetEngine.widgetListingGrid) {
                        window.JetEngine.widgetListingGrid($listing);
                    }
                    if (window.JetEngine.initElementsHandlers) {
                        window.JetEngine.initElementsHandlers($container);
                    }
                }
            });
        }

        // Enhanced MutationObserver
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.classList && 
                            (node.classList.contains('jet-form-builder-message--success') ||
                             node.classList.contains('jet-form-builder-message--error') ||
                             node.classList.contains('jet-engine-message') ||
                             node.classList.contains('jet-form-messages-wrap') ||
                             node.classList.contains('jet-form-builder-messages-wrap'))) {
                            $(node).hide();
                            let message = $(node).text();
                            if (message) {
                                // Determine if it's an error message
                                let type = node.classList.contains('jet-form-builder-message--error') ? 'error' : 'success';
                                showMessage(message, type);
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

      
    });
    </script>
    <?php
}
add_action('wp_footer', 'enqueue_jetpopup_notification_script');
