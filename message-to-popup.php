/**
 * JetPopup Global Form Notification System
 */

function enqueue_jetpopup_notification_script() {
    ?>
    <style>
        /* Global Message Container */
        #jet-popup-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999;
            display: none;
        }

        /* Overlay Styling */
        #jet-popup-message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.4);
            z-index: 100;
            backdrop-filter: blur(5px) brightness(40%);
        }

        /* Message Box Styling */
        #jet-popup-message-box {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background-color: #d2e3d8;
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
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        #jet-popup-message-box.visible {
            opacity: 1;
            transform: translateY(0);
        }

        #jet-popup-message-box.error {
            background: #fff2f2;
            border-left: 4px solid #dc3545;
        }

        #jet-popup-message-box.success {
            background: #f0fff4;
            border-left: 4px solid #28a745;
        }

        #jet-popup-message-content {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }

        .success #jet-popup-message-content {
            margin-bottom: 0;
        }

        #jet-popup-message-close {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        #jet-popup-message-close:hover {
            background-color: #0056b3;
        }

        .error #jet-popup-message-close {
            background: #dc3545;
        }

        .error #jet-popup-message-close:hover {
            background: #bd2130;
        }

        .success #jet-popup-message-close {
            display: none !important;
        }

        /* Responsive Styles */
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
        <div id="jet-popup-message-overlay"></div>
        <div id="jet-popup-message-box">
            <div id="jet-popup-message-content"></div>
            <button type="button" id="jet-popup-message-close">Aizvērt</button>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Move message container to body
        $('#jet-popup-message').appendTo('body');

        // Close message function
        function closeMessage() {
            $('#jet-popup-message').hide();
            $('#jet-popup-message-box').removeClass('error success visible');
            $('#jet-popup-message-content').empty();
        }

        // Show message function
        function showMessage(message, type) {
            var defaultSuccessMessage = 'Action completed successfully';
            var finalMessage = type === 'success' ? (message || defaultSuccessMessage) : message;
            
            $('#jet-popup-message-content').text(finalMessage);
            $('#jet-popup-message-box').removeClass('error success').addClass(type);
            $('#jet-popup-message').show();
            
            // Add visible class after a small delay for animation
            setTimeout(function() {
                $('#jet-popup-message-box').addClass('visible');
            }, 10);

            // For success messages, auto-hide after 2 seconds
            if (type === 'success') {
                setTimeout(closeMessage, 2000);
            }

            // Hide original messages
            $('.jet-form-builder-message--success, .jet-form-builder-message--error, #login_error, .message').hide();
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
            
            var $error = $('.jet-form-builder-message--error:visible');
            var $success = $('.jet-form-builder-message--success:visible');
            var $loginError = $('#login_error:visible');
            var $message = $('.message:visible');

            if ($error.length) {
                message = $error.text();
                type = 'error';
            } else if ($success.length) {
                message = $success.text();
                type = 'success';
            } else if ($loginError.length) {
                message = $loginError.text();
                type = 'error';
            } else if ($message.length && !$message.hasClass('jet-form-builder-message')) {
                message = $message.text();
                type = $message.hasClass('updated') ? 'success' : 'error';
            }

            if (message || type === 'success') {
                showMessage(message, type);
            }
        }

        // Handle JetForm Builder success events
        $(document).on('jet-form-builder/ajax/on-success', function(event, response, $form, data) {
            showMessage(response.message || 'Form submitted successfully', 'success');
            
            if (window.JetEngine) {
                let formId = $form.data('form-id');
                
                // Update listings marked for this specific form
                $('.update-on-form-' + formId).each(function() {
                    refreshListing($(this));
                });
                
                // Refresh all listings with check-ins counters
                refreshAllCheckinsCounters();
                
                // Add a small delay and refresh again
                setTimeout(refreshAllCheckinsCounters, 500);
            }
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
                let $container = $listing.children('.elementor-widget-container');
                $container.html($(response.data.html));
                window.JetEngine.widgetListingGrid($listing);
                window.JetEngine.initElementsHandlers($container);
            });
        }

        // Form submission
        $(document).on('submit', 'form', function(event) {
            $(document).on('ajaxComplete', function(event, xhr, settings) {
                setTimeout(checkMessages, 100);
            });
        });

        // Content changes
        var observer = new MutationObserver(function() {
            setTimeout(checkMessages, 100);
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
