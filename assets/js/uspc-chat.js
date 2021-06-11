/* global USP, USPUploaders */

var uspc_chat_last_activity = {}; //last request for new messages 
var uspc_chat_beat = new Array; //array of open chats
var uspc_chat_write = 0; //user writes
var uspc_chat_contact_token = 0; //open contact
var uspc_chat_inactive_counter = -1; //user idle counter
var uspc_chat_important = 0;
var uspc_chat_max_words = 300;
var uspc_chat_sound = {};

jQuery(function ($) {

    uspc_chat_init_sound();

    uspc_chat_inactivity_counter();

    jQuery('.chat-new-messages').parents('#uspc-chat-noread-box').animateCss('tada');

    if (USPUploaders.isset('uspc_chat_uploader')) {

        USPUploaders.get('uspc_chat_uploader').animateLoading = function (status) {
            status ? usp_preloader_show(jQuery('.rcl-chat .chat-form')) : usp_preloader_hide();
        };

    }

});

function uspc_chat_init_sound() {

    var options = {
        sounds: ['e-oh'],
        path: USP.usp_chat.sounds,
        multiPlay: false,
        volume: '0.5'
    };

    uspc_chat_sound = usp_apply_filters('uspc_chat_sound_options', options);
    if (typeof jQuery.ionSound !== 'undefined') {
        jQuery.ionSound(uspc_chat_sound);
    }
}

function uspc_chat_inactivity_cancel() {
    uspc_chat_inactive_counter = -1;
}

function uspc_chat_inactivity_counter() {
    uspc_chat_inactive_counter++;
    setTimeout('uspc_chat_inactivity_counter()', 60000);
}

function uspc_scroll_down(token) {
    jQuery('.rcl-chat[data-token="' + token + '"] .chat-messages').scrollTop(jQuery('.rcl-chat[data-token="' + token + '"] .chat-messages').get(0).scrollHeight);
}

function uspc_reset_active_mini_chat() {
    jQuery('.rcl-noread-users .rcl-chat-user > a ').removeClass('active-chat');
}

function uspc_chat_counter_reset(form) {
    form.children('.words-counter').text(USP.usp_chat.words).removeAttr('style');
}

function uspc_chat_add_message(e) {
    var form = jQuery(e).parents('form');
    
    uspc_chat_add_new_message(form);
}

function uspc_chat_clear_beat(token) {

    var all_beats = usp_beats;
    var all_chats = uspc_chat_beat;

    all_beats.forEach(function (beat, index, usp_beats) {
        if (beat.beat_name != 'uspc_chat_beat_core')
            return;
        if (beat.data.token != token)
            return;
        delete usp_beats[index];
    });

    all_chats.forEach(function (chat_token, index, chats) {
        if (chat_token != token)
            return;
        delete uspc_chat_beat[index];
    });

    console.log('chat beat ' + token + ' clear');

}

function uspc_set_active_mini_chat(e) {
    uspc_reset_active_mini_chat();
    jQuery(e).addClass('active-chat').children('i').remove();
}

function uspc_init_chat(chat) {
    jQuery(function ($) {

        chat = usp_apply_filters('uspc_init_chat', chat);

        uspc_scroll_down(chat.token);

        uspc_chat_max_words = chat.max_words;
        uspc_chat_last_activity[chat.token] = chat.open_chat;

        var i = uspc_chat_beat.length;
        uspc_chat_beat[i] = chat.token;

        usp_do_action('uspc_init_chat', chat);

    });
}

function uspc_chat_close(e) {

    uspc_reset_active_mini_chat();
    var token = jQuery(e).parents('.rcl-mini-chat').find('.rcl-chat').data('token');
    uspc_chat_clear_beat(token);
    var minichat_box = jQuery('#uspc-chat-noread-box');
    minichat_box.removeClass('active-chat');
    var animationName = minichat_box.hasClass('left-panel') ? 'fadeOutLeft' : 'fadeOutRight';
    minichat_box.children('.rcl-mini-chat').animateCss(animationName, function (e) {
        jQuery(e).empty();
    });
    usp_do_action('uspc_chat_close', token);

}

function uspc_chat_write_status(token) {
    var chat = jQuery('.rcl-chat[data-token="' + token + '"]');
    var chat_status = chat.find('.chat-status');
    chat_status.css({
        width: 12
    });
    chat_status.animate({
            width: 35
        },
        1000);
    uspc_chat_write = setTimeout('uspc_chat_write_status("' + token + '")', 3000);

    usp_do_action('uspc_add_chat_write', token);

}

function uspc_chat_write_status_cancel(token) {
    clearTimeout(uspc_chat_write);
    var chat = jQuery('.rcl-chat[data-token="' + token + '"]');
    var chat_status = chat.find('.chat-status');
    chat_status.css({
        width: 0
    });

    usp_do_action('uspc_remove_chat_write', token);
}

function uspc_chat_add_new_message(form) {

    uspc_chat_inactivity_cancel();

    var token = form.children('[name="chat[token]"]').val();
    var chat = jQuery('.rcl-chat[data-token="' + token + '"]');
    var message_text = form.children('textarea').val();

    if (!message_text.length) {
        usp_notice(USP.local.uspc_not_written, 'error', 10000);
        return false;
    }

    if (message_text.length > USP.usp_chat.words) {
        usp_notice(USP.local.uspc_max_words, 'error', 10000);
        return false;
    }

    usp_preloader_show('.rcl-chat .chat-form > form');

    usp_ajax({
        data: 'action=uspc_chat_add_message&'
            + form.serialize()
            + '&office_ID=' + USP.office_ID
            + '&last_activity=' + uspc_chat_last_activity[token],
        success: function (data) {

            if (data['content']) {
                form.find('textarea').val('');
                jQuery("#rcl-upload-gallery-uspc_chat_uploader").html('');

                chat.find('.chat-messages').append(data['content']).find('.chat-message').last().animateCss('zoomIn');
                chat.find('.rcl-chat-uploader').show();
                chat.find('.chat-preloader-file').empty();

                uspc_scroll_down(token);
                uspc_chat_counter_reset(form);

                if (data.new_messages) {
                    if (typeof jQuery.ionSound !== 'undefined') {
                        jQuery.ionSound.play(uspc_chat_sound.sounds[0]);
                    }
                }

                uspc_chat_last_activity[token] = data.last_activity;

                usp_do_action('uspc_chat_add_message', {
                    token: token,
                    result: data
                });
            }
        }
    });

    return false;
}

function uspc_chat_navi(e) {

    uspc_chat_inactivity_cancel();

    var token = jQuery(e).parents('.rcl-chat').data('token');

    usp_preloader_show('.rcl-chat .chat-form > form');

    usp_ajax({
        data: {
            action: 'uspc_get_chat_page',
            token: token,
            page: jQuery(e).data('page'),
            'pager-id': jQuery(e).data('pager-id'),
            in_page: jQuery(e).parents('.rcl-chat').data('in_page'),
            important: uspc_chat_important
        },
        success: function (data) {

            if (data['content']) {

                jQuery(e).parents('.chat-messages-box').html(data['content']).animateCss('fadeIn');

                uspc_scroll_down(token);

            }
        }
    });

    return false;
}

function uspc_get_mini_chat(e, user_id) {

    if (uspc_chat_contact_token) {
        uspc_chat_clear_beat(uspc_chat_contact_token);
    }

    usp_preloader_show('#uspc-chat-noread-box > div');

    usp_ajax({
        data: {
            action: 'uspc_get_chat_private_ajax',
            user_id: user_id
        },
        success: function (data) {

            if (data['content']) {
                var minichat_box = jQuery('#uspc-chat-noread-box');
                var animationName = minichat_box.hasClass('left-panel') ? 'fadeInLeft' : 'fadeInRight';
                minichat_box.children('.rcl-mini-chat').html(data['content']).animateCss(animationName);
                minichat_box.addClass('active-chat');
                uspc_chat_contact_token = data['chat_token'];
                uspc_set_active_mini_chat(e);
                uspc_scroll_down(uspc_chat_contact_token);
            }
        }
    });

    return false;
}

function uspc_chat_words_count(e, elem) {

    evt = e || window.event;

    var key = evt.keyCode;

    if (key == 13 && evt.ctrlKey) {
        var form = jQuery(elem).parents('form');
        uspc_chat_add_new_message(form);
        return false;
    }

    var words = jQuery(elem).val();
    var counter = uspc_chat_max_words - words.length;
    var color;

    if (counter > (uspc_chat_max_words - 1))
        return false;

    if (counter < 0) {
        jQuery(elem).val(words.substr(0, (uspc_chat_max_words - 1)));
        return false;
    }

    if (counter > 150)
        color = 'green';
    else if (50 < counter && counter < 150)
        color = 'orange';
    else if (counter < 50)
        color = 'red';

    jQuery(elem).parent('form').children('.words-counter').css('color', color).text(counter);
}

function uspc_chat_remove_contact(e, chat_id) {

    usp_preloader_show('.rcl-chat-contacts');

    var contact = jQuery(e).parents('.contact-box').data('contact');

    usp_ajax({
        data: {
            action: 'uspc_chat_remove_contact',
            chat_id: chat_id
        },
        success: function (data) {

            if (data['remove']) {
                jQuery('[data-contact="' + contact + '"]').animateCss('flipOutX', function (e) {
                    jQuery(e).remove();
                });

                usp_do_action('uspc_chat_remove_contact', chat_id);
            }
        }
    });

    return false;
}

function uspc_chat_message_important(message_id) {

    usp_preloader_show('.chat-message[data-message="' + message_id + '"] > div');

    usp_ajax({
        data: {
            action: 'uspc_chat_message_important',
            message_id: message_id
        },
        success: function (data) {

            jQuery('.chat-message[data-message="' + message_id + '"]').find('.message-important').toggleClass('active-important');

        }
    });

    return false;
}

function uspc_chat_important_manager_shift(e, status) {

    usp_preloader_show('.rcl-chat');

    uspc_chat_important = status;

    var token = jQuery(e).parents('.rcl-chat').data('token');

    usp_ajax({
        data: {
            action: 'uspc_chat_important_manager_shift',
            token: token,
            status_important: status
        },
        success: function (data) {

            if (data['content']) {

                jQuery(e).parents('.chat-messages-box').html(data['content']).animateCss('fadeIn');

                uspc_scroll_down(token);

            }
        }
    });

    return false;
}

function uspc_chat_delete_message(message_id) {

    usp_preloader_show('.chat-message[data-message="' + message_id + '"] > div');

    usp_ajax({
        data: {
            action: 'uspc_chat_ajax_delete_message',
            message_id: message_id
        },
        success: function (data) {

            if (data['remove']) {
                jQuery('.chat-message[data-message="' + message_id + '"]').animateCss('flipOutX', function (e) {
                    jQuery(e).remove();
                });

                usp_do_action('uspc_chat_delete_message', message_id);
            }
        }
    });

    return false;
}

function uspc_chat_delete_attachment(e, attachment_id) {

    usp_preloader_show('.chat-form > form');

    usp_ajax({
        data: {
            action: 'uspc_chat_delete_attachment',
            attachment_id: attachment_id
        },
        success: function (data) {

            if (data['remove']) {
                var form = jQuery(e).parents('form');
                form.find('.rcl-chat-uploader').show();
                form.find('.chat-preloader-file').empty();
            }
        }
    });

    return false;
}

function uspc_chat_shift_contact_panel() {

    var box = jQuery('#uspc-chat-noread-box');

    if (box.hasClass('active-chat'))
        return true;

    var view = (jQuery.cookie('uspc_chat_contact_panel') == 1) ? 0 : 1;

    if (view) {
        box.removeClass('hidden-contacts').animateCss('slideInUp');
    } else {
        box.addClass('hidden-contacts');
    }

    jQuery.cookie('uspc_chat_contact_panel', view, {
        expires: 30,
        path: '/'
    });

    return false;
}

usp_add_action('uspc_init_chat', 'uspc_chat_init_beat');
function uspc_chat_init_beat(chat) {
    var delay = (chat.delay != 0) ? chat.delay : USP.usp_chat.delay, chat;
    usp_add_beat('uspc_chat_beat_core', delay, chat);
}

function uspc_chat_beat_core(chat) {

    if (chat.timeout == 1) {
        if (uspc_chat_inactive_counter >= USP.usp_chat.inactivity) {
            console.log('inactive:' + uspc_chat_inactive_counter);
            return false;
        }
    }

    var chatBox = jQuery('.rcl-chat[data-token="' + chat.token + '"]');

    var chat_form = chatBox.find('form');

    var beat = {
        action: 'uspc_chat_get_new_messages',
        success: 'uspc_chat_beat_success',
        data: {
            last_activity: uspc_chat_last_activity[chat.token],
            token: chat.token,
            update_activity: 1,
            user_write: (chat_form.find('textarea').val()) ? 1 : 0
        }
    };

    return beat;

}

function uspc_chat_beat_success(data) {

    var chat = jQuery('.rcl-chat[data-token="' + data.token + '"]');

    if (!chat.length) {
        uspc_chat_clear_beat(data.token);
        return;
    }

    var user_write = 0;
    chat.find('.chat-users').html('');
    uspc_chat_write_status_cancel(data.token);

    if (data['errors']) {
        jQuery.each(data['errors'], function (index, error) {
            usp_notice(error, 'error', 10000);
        });
    }

    if (data['success']) {

        uspc_chat_last_activity[data.token] = data['current_time'];

        if (data['users']) {
            jQuery.each(data['users'], function (index, data) {
                chat.find('.chat-users').append(data['link']);
                if (data['write'] == 1)
                    user_write = 1;
            });
        }

        if (data['content']) {
            if (typeof jQuery.ionSound !== 'undefined') {
                jQuery.ionSound.play(uspc_chat_sound.sounds[0]);
            }
            chat.find('.chat-messages').append(data['content']);
            uspc_scroll_down(data.token);
        } else {
            if (user_write)
                uspc_chat_write_status(data.token);
        }
    }

    usp_do_action('uspc_chat_get_messages', {
        token: data.token,
        result: data
    });

}

function uspc_get_chat_window(e, user_id) {

    if (e && jQuery(e).parents('.preloader-parent')) {
        usp_preloader_show(jQuery(e).parents('.preloader-parent'));
    }

    usp_ajax({
        data: {
            action: 'uspc_get_ajax_chat_window',
            user_id: user_id
        }
    });

}