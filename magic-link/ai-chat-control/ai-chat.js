/**
 * Magic Link JavaScript for AI Chat Control
 */
jQuery(document).ready(function($) {
    let jsObject;
    
    // Initialize the jsObject from PHP data
    if (typeof window.dt_magic_link_data !== 'undefined') {
        jsObject = window.dt_magic_link_data;
    }
    
    // Set title
    $('#title').text('AI Chat Control');
    
    // Initialize the chat container
    let chatContainer = $('<div id="chat-container"></div>');
    $('#content').prepend(chatContainer);
    
    // Message display function
    function displayMessage(text, isUser = false) {
        const messageClass = isUser ? 'user-message' : 'system-message';
        const message = $(`<div class="${messageClass}"><div class="message-content">${text}</div></div>`);
        chatContainer.append(message);
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }
    
    // Display welcome message
    displayMessage("Welcome to AI Chat Control. You can type or speak commands like 'I met with Mary on Thursday and we discussed her Bible reading. She is growing in faith.' to update contact records with meeting details, notes, faith status, and more.");
    
    // Handle text input submission
    $('#submit-btn').on('click', function() {
        processUserInput();
    });
    
    $('#text-input').on('keypress', function(e) {
        if (e.which === 13) {
            processUserInput();
        }
    });
    
    function processUserInput() {
        const userInput = $('#text-input').val().trim();
        
        if (!userInput) {
            return;
        }
        
        // Display user message
        displayMessage(userInput, true);
        
        // Clear input field
        $('#text-input').val('');
        
        // Show loading indicator
        displayMessage("Processing...");
        
        // Send command to API
        sendChatCommand(userInput);
    }
    
    function sendChatCommand(command) {
        $.ajax({
            url: jsObject.root + 'ai/v1/control/go',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                command: command,
                parts: jsObject.parts,

            }),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce);
            },
            success: function(response) {
                // Remove loading message
                $('.system-message:last').remove();
                
                if (response.success) {
                    displayMessage(response.message);
                } else {
                    let errorMessage = response.message || "I couldn't understand that command.";
                    displayMessage(errorMessage);
                    
                    // Provide helpful suggestions
                    if (errorMessage.includes("not found")) {
                        displayMessage("Try using the full name of the contact, or check if the contact exists in the system.");
                    } else {
                        displayMessage("Try something like 'I met with [contact name] yesterday and we talked about [topic]' or 'Had a meeting with [contact name] about [details]. They are now reading the Bible regularly and want to be baptized next Sunday (YYYY-MM-DD).'");
                    }
                }
            },
            error: function(xhr, status, error) {
                // Remove loading message
                $('.system-message:last').remove();
                
                console.error('Error details:', xhr.responseText);
                
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse && errorResponse.message) {
                        displayMessage("Error: " + errorResponse.message);
                    } else {
                        displayMessage("Something went wrong. Please try again.");
                    }
                } catch (e) {
                    displayMessage("Something went wrong. Please try again.");
                }
                
                displayMessage("If the problem persists, try refreshing the page or contact support.");
            }
        });
    }
    
    // Fetch data from API
    window.get_chat_control_data = () => {
        jQuery.ajax({
            type: "GET",
            data: { action: 'get', parts: jsObject.parts },
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
            }
        })
        .done(function(data) {
            window.load_chat_control_data(data);
        })
        .fail(function(e) {
            console.log(e);
            jQuery('#error').html(e);
        });
    };
    
    // Process and display chat control data
    window.load_chat_control_data = (data) => {
        let content = jQuery('#api-content');
        let spinner = jQuery('.loading-spinner');

        content.empty();
        let html = ``;
        
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(item => {
                html += `
                    <div class="cell">
                        ${window.lodash.escape(item.name)}
                    </div>
                `;
            });
        } else {
            html = `<div class="cell">No chat control data available</div>`;
        }
        
        content.html(html);
        spinner.removeClass('active');
    };
    
    // Initialize date pickers
    $('.dt_date_picker').datepicker({
        constrainInput: false,
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: "1900:2050",
    }).each(function() {
        if (this.value && moment.unix(this.value).isValid()) {
            this.value = window.SHAREDFUNCTIONS.formatDate(this.value);
        }
    });
}); 