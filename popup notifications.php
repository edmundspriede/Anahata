/**
 * JetPopup Login Form Notification System
 */

function enqueue_jetpopup_notification_script() {
    ?>
    <style>
        #jet-popup-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999;
            display: none;
        }

        #jet-popup-message-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        #jet-popup-message-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-align: center;
            width: 90%;
            max-width: 400px;
            z-index: 1;
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
            margin-bottom: 0 !important;
        }

        #jet-popup-message-close {
            display: inline-block;
            min-width: 120px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        #jet-popup-message-close:hover {
            background: #0056b3;
            transform: translateY(-1px);
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
    </style>

    <div id="jet-popup-message">
        <div id="jet-popup-message-overlay"></div>
        <div id="jet-popup-message-box">
            <div id="jet-popup-message-content"></div>
            <button type="button" id="jet-popup-message-close">Close</button>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Move message container to body
        $('#jet-popup-message').appendTo('body');

        // Close message function
        function closeMessage() {
            $('#jet-popup-message').hide();
            $('#jet-popup-message-box').removeClass('error success');
            $('#jet-popup-message-content').empty();
        }

        // Show message function
        function showMessage(message, type) {
            var defaultSuccessMessage = 'You are logged in successfully';
            var finalMessage = type === 'success' ? (message || defaultSuccessMessage) : message;
            
            $('#jet-popup-message-content').text(finalMessage);
            $('#jet-popup-message-box').removeClass('error success').addClass(type);
            $('#jet-popup-message').show();

            // For success messages, auto-hide after 2 seconds
            if (type === 'success') {
                setTimeout(closeMessage, 2000);
            }

            // Hide original messages
            $('.jet-form-builder-message--success, .jet-form-builder-message--error, #login_error, .message').hide();
        }

        // Close button click (only visible for error messages)
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

        // Form submission
        $(document).on('submit', 'form', function() {
            $(document).on('ajaxComplete', function( event, xhr, settings ) {
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
