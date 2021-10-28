/* global USP, USPUploaders, usp_beats, ssi_modal */
// noinspection JSUnresolvedFunction,JSUnresolvedVariable ,EqualityComparisonWithCoercionJS

var uspc_last_activity = {}; //last request for new messages 
var uspc_beat = []; //array of open chats
var uspc_write = 0; //user writes
var uspc_inactive_counter = -1; //user idle counter
var uspc_important = 0;
var uspc_max_words = 300;

jQuery(function ($) {
    uspc_chat_inactivity_counter();

    // noinspection SpellCheckingInspection
    $('.uspc-message-for-you').animateCss('tada');

    if (USPUploaders.isset('uspc_chat_uploader')) {
        USPUploaders.get('uspc_chat_uploader').animateLoading = function (status) {
            status ? usp_preloader_show($('.uspc-im__form')) : usp_preloader_hide();
        };
    }
});

// play
function uspc_play_sound() {
    if (!uspc_mute_chat()) return;

    const audioPlay = (() => {
        let context = null;
        return async url => {
            if (context)
                context.close();
            context = new AudioContext();
            const source = context.createBufferSource();
            source.buffer = await fetch(url)
                .then(res => res.arrayBuffer())
                .then(arrayBuffer => context.decodeAudioData(arrayBuffer));
            source.connect(context.destination);
            source.start();
        };
    })();

    return audioPlay(USP.usp_chat.sounds);
}

function uspc_chat_inactivity_cancel() {
    uspc_inactive_counter = -1;
}

function uspc_chat_inactivity_counter() {
    uspc_inactive_counter++;
    setTimeout('uspc_chat_inactivity_counter()', 60000);
}

function uspc_scroll_down(token) {
    if (!token)
        return;

    uspc_scroll_by_selector('.uspc-im[data-token="' + token + '"] .uspc-im__talk');
}

function uspc_scroll_by_selector(html) {
    let talk = jQuery(html);

    if (talk.length > 0) {
        jQuery(talk).scrollTop(jQuery(talk).get(0).scrollHeight);
    }
}

function uspc_chat_counter_reset(form) {
    form.find('.uspc-im-form__sign-count').text(USP.usp_chat.words).removeAttr('style');
}

// if empty dialog
function uspc_clear_notice() {
    jQuery('.uspc-im-talk__write').hide();
}

function uspc_get_wrap_im_by_token(token) {
    return jQuery('.uspc-im[data-token="' + token + '"]');
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_add_message(e) {
    if (uspc_connection_is()) {
        let form = jQuery(e).parents('.uspc-im__form');

        uspc_chat_add_new_message(form);
    } else {
        uspc_connection_lost_message();
    }
}

function uspc_connection_is() {
    return navigator.onLine;
}

function uspc_connection_lost_message() {
    usp_notice(USP.local.uspc_network_lost, 'error', 6000);
}

function uspc_chat_clear_beat(token) {
    let all_beats = usp_beats;
    let all_chats = uspc_beat;

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
        delete uspc_beat[index];
    });

    console.log('chat beat ' + token + ' clear');
}

// noinspection JSUnusedGlobalSymbols
function uspc_init_chat(chat) {
    chat = usp_apply_filters('uspc_init', chat);

    uspc_scroll_down(chat.token);

    uspc_max_words = chat.max_words;
    uspc_last_activity[chat.token] = chat.open_chat;

    let i = uspc_beat.length;
    uspc_beat[i] = chat.token;

    usp_do_action('uspc_init', chat);
}

function uspc_disable_button(form) {
    jQuery(form).find('.uspc-im-form-bttn__send').addClass('usp-bttn__disabled');
}

function uspc_enable_button(form) {
    jQuery(form).find('.uspc-im-form-bttn__send').removeClass('usp-bttn__disabled');
}

// send button opening after load file
usp_add_action('usp_uploader_after_done', 'uspc_after_upload');

function uspc_after_upload(e) {
    let form = jQuery(e.target).parents('.uspc-im__form');
    uspc_enable_button(form);
}

// send button opening after insert emoji
usp_add_action('usp_emoji_insert', 'uspc_insert_emoji_enabled_bttn');

function uspc_insert_emoji_enabled_bttn(box) {
    let form = jQuery(box).parents('.uspc-im__form');
    uspc_enable_button(form);
}

usp_add_action('usp_get_emoji_ajax', 'uspc_auto_height_emoji_lists');

function uspc_auto_height_emoji_lists() {
    let messageBox = jQuery('.uspc-im__box').outerHeight() + 50; // +50px on top panel
    jQuery('.usp-emoji__all').css({'max-height': messageBox});
}

// send button disabled after delete file if empty textarea
usp_add_action('usp_uploader_delete', 'uspc_delete_attachment_actions');

function uspc_delete_attachment_actions(e) {
    let form = jQuery(e).parents('.uspc-im__form');

    if (!form.find('.uspc-im-form__textarea').val()) {
        uspc_disable_button(form);
    }
}

usp_add_action('uspc_init', 'uspc_chat_init_beat');

function uspc_chat_init_beat(chat) {
    let delay = (chat.delay != 0) ? chat.delay : USP.usp_chat.delay;
    usp_add_beat('uspc_chat_beat_core', delay, chat);
}

function uspc_chat_write_status(token) {
    let chat = uspc_get_wrap_im_by_token(token),
        chat_status = chat.find('.uspc-im__writes');

    chat_status.css({
        width: 18
    });
    chat_status.animate({
        width: 36
    }, 1000);

    uspc_write = setTimeout('uspc_chat_write_status("' + token + '")', 3000);

    usp_do_action('uspc_user_write', token);
}

function uspc_chat_write_status_cancel(token) {
    clearTimeout(uspc_write);

    let chat = uspc_get_wrap_im_by_token(token),
        chat_status = chat.find('.uspc-im__writes');

    chat_status.css({
        width: 0
    });

    usp_do_action('uspc_user_stopped_writing', token);
}

// if the attempt to send an empty message
function uspc_mark_textarea() {
    let field = jQuery('.uspc-im-form__textarea');
    field.css('border', '2px solid var(--uspRed-300)');
    setTimeout(() => {
        field.css('border', '');
    }, 5000);
}

// send new message in form
function uspc_chat_add_new_message(form) {
    uspc_chat_inactivity_cancel();

    var token = form.children('[name="chat[token]"]').val(),
        chat = uspc_get_wrap_im_by_token(token),
        message_text = form.find('.uspc-im-form__textarea').val(),
        file = form.find('#usp-media-uspc_chat_uploader .usp-media__item');

    message_text = jQuery.trim(message_text);

    if (!message_text.length && !file.length) {
        usp_notice(USP.local.uspc_empty, 'error', 10000);
        uspc_mark_textarea();
        return false;
    }

    if (message_text.length > USP.usp_chat.words) {
        usp_notice(USP.local.uspc_text_words, 'error', 10000);
        return false;
    }

    usp_preloader_show('.uspc-im__form');

    usp_ajax({
        data: 'action=uspc_chat_add_message&'
            + form.serialize()
            + '&office_ID=' + USP.office_ID
            + '&last_activity=' + uspc_last_activity[token],
        success: function (data) {

            if (data['content']) {
                form.find('textarea').val('');
                jQuery("#usp-media-uspc_chat_uploader").html('');

                chat.find('.uspc-im__talk').append(data['content']).find('.uspc-post').last().animateCss('zoomIn');
                chat.find('.uspc-chat-uploader').show();

                uspc_scroll_down(token);
                uspc_chat_counter_reset(form);

                if (data.new_messages) {
                    uspc_play_sound();
                    uspc_blink_tab();
                }

                uspc_last_activity[token] = data.last_activity;

                uspc_clear_notice();
                uspc_disable_button(form);
                uspc_auto_height_textarea();

                usp_do_action('uspc_added_message', {
                    token: token,
                    result: data
                });
            }
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_navi(page, e) {
    uspc_chat_inactivity_cancel();

    var im = jQuery(e).parents('.uspc-im'),
        token = jQuery(im).data('token'),
        important = jQuery(im).data('important');

    if (!important)
        important = uspc_important;

    usp_preloader_show('.uspc-im__footer');

    usp_ajax({
        data: {
            action: 'uspc_get_chat_page',
            token: token,
            page: page,
            in_page: jQuery(im).data('in_page'),
            important: important
        },
        success: function (data) {
            if (data['content']) {
                let imBox = jQuery(e).parents('.uspc-im__box');
                imBox.find('.uspc-im__talk, .uspc-im__footer').remove();
                imBox.append(data['content']).animateCss('fadeIn');

                uspc_scroll_down(token);
                usp_do_action('uspc_load_page');
            }
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_contacts_navi(page, e) {
    usp_preloader_show('.usp-subtab-content');

    usp_ajax({
        data: {
            action: 'uspc_get_contacts_navi',
            page: page
        },
        success: function (data) {
            if (data['content']) {
                let list = jQuery(e).parents('.usp-subtab-content');
                list.find('.uspc-contact-box, .uspc-mini__nav').remove();
                list.append(data['nav']).find('.uspc-userlist').append(data['content']).animateCss('fadeIn');
            }
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_words_count(e, elem) {
    // noinspection JSDeprecatedSymbols
    let evt = e || window.event;

    let key = evt.keyCode,
        form = jQuery(elem).parents('.uspc-im__form');

    if (key == 13 && evt.ctrlKey) {
        uspc_chat_add_new_message(form);
        return false;
    }

    let words = jQuery(elem).val();
    words = jQuery.trim(words);
    let counter = uspc_max_words - words.length,
        color;

    if (counter > (uspc_max_words - 1)) {
        uspc_disable_button(form);
        return false;
    }

    if (counter < 0) {
        jQuery(elem).val(words.substr(0, (uspc_max_words - 1)));
        return false;
    }

    uspc_enable_button(form);

    if (counter > 150)
        color = 'var(--uspGreen-800)';
    else if (50 < counter && counter < 150)
        color = 'var(--uspYellow-900)';
    else if (counter < 50)
        color = 'var(--uspRed-800)';

    jQuery(elem).parents('.uspc-im__form').find('.uspc-im-form__sign-count').css('color', color).text(counter);
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_remove_contact(e, chat_id) {
    usp_preloader_show('.uspc-userlist');

    var contact = jQuery(e).parents('.uspc-contact-box').data('contact');

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

                usp_do_action('uspc_removed_contact', chat_id);
            }
        }
    });

    return false;
}

// scroll down in all important tab
usp_add_action('usp_upload_tab', 'uspc_important_load');
usp_add_action('usp_init', 'uspc_important_load');

function uspc_important_load(e) {
    if (e && e.result.subtab_id === 'important-messages') {
        var content = e.result.content,
            token = jQuery(content).find('.uspc-im').data('token');
    } else {
        let tab = usp_get_value_url_params();
        if (tab && tab['subtab'] === 'important-messages') {
            token = jQuery('.uspc-im').data('token');
        }
    }

    if (token) {
        uspc_scroll_down(token);
    }
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_message_important(message_id) {
    usp_preloader_show('.uspc-post[data-message="' + message_id + '"] > div');

    usp_ajax({
        data: {
            action: 'uspc_chat_message_important',
            message_id: message_id
        },
        success: function (data) {
            jQuery('.uspc-post[data-message="' + message_id + '"]').toggleClass('uspc-post__saved');
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_important_manager_shift(e, status) {
    usp_preloader_show('.uspc-im__box');

    uspc_important = status;

    var token = jQuery(e).parents('.uspc-head-box').nextAll('.uspc-im').data('token');

    usp_ajax({
        data: {
            action: 'uspc_chat_important_manager_shift',
            token: token,
            status_important: status
        },
        success: function (data) {
            if (data['content']) {
                let form = jQuery(e).parents('.uspc-head-box').nextAll('.uspc-im').find('.uspc-im__form');
                status ? form.hide() : form.show();

                jQuery(e).parents('.uspc-head-box').nextAll('.uspc-im').find('.uspc-im__box').html(data['content']).animateCss('fadeIn');
                if (status === 1) {
                    jQuery(e).find('i').removeClass('fa-star').addClass('fa-star-fill');
                    jQuery(e).attr('onclick', 'uspc_chat_important_manager_shift(this,0);return false;');
                } else {
                    jQuery(e).find('i').removeClass('fa-star-fill').addClass('fa-star');
                    jQuery(e).attr('onclick', 'uspc_chat_important_manager_shift(this,1);return false;');
                }

                uspc_scroll_down(token);
            }
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_delete_message(message_id) {
    usp_preloader_show('.uspc-post[data-message="' + message_id + '"] > div');

    usp_ajax({
        data: {
            action: 'uspc_chat_ajax_delete_message',
            message_id: message_id
        },
        success: function (data) {
            if (data['remove']) {
                jQuery('.uspc-post[data-message="' + message_id + '"]').animateCss('flipOutX', function (e) {
                    jQuery(e).remove();
                });

                usp_do_action('uspc_deleted_message', message_id);
            }
        }
    });

    return false;
}

// noinspection JSUnusedGlobalSymbols
function uspc_chat_delete_attachment(e, attachment_id) {
    usp_preloader_show('.uspc-im__form');

    usp_ajax({
        data: {
            action: 'uspc_chat_delete_attachment',
            attachment_id: attachment_id
        },
        success: function (data) {
            if (data['remove']) {
                let form = jQuery(e).parents('.uspc-im__form');
                form.find('.uspc-chat-uploader').show();
            }
        }
    });

    return false;
}

function uspc_chat_beat_core(chat) {
    if (chat.timeout == 1) {
        if (uspc_inactive_counter >= USP.usp_chat.inactivity) {
            console.log('inactive:' + uspc_inactive_counter);
            return false;
        }
    }

    let chatBox = jQuery('.uspc-im[data-token="' + chat.token + '"]'),
        chat_form = chatBox.find('.uspc-im__form');
    return {
        action: 'uspc_chat_get_new_messages',
        success: 'uspc_chat_beat_success',
        data: {
            last_activity: uspc_last_activity[chat.token],
            token: chat.token,
            update_activity: 1,
            user_write: (chat_form.find('textarea').val()) ? 1 : 0
        }
    };
}

function uspc_chat_beat_success(data) {
    var chat = jQuery('.uspc-im[data-token="' + data.token + '"]');

    if (!chat.length) {
        uspc_chat_clear_beat(data.token);
        return;
    }

    var user_write = 0;
    let onlineList = chat.parents('.uspc-messenger-box').find('.uspc-im-online__items');

    onlineList.html('');
    uspc_chat_write_status_cancel(data.token);

    if (data['errors']) {
        jQuery.each(data['errors'], function (index, error) {
            usp_notice(error, 'error', 10000);
        });
    }

    if (data['success']) {
        uspc_last_activity[data.token] = data['current_time'];

        if (data['users']) {
            uspc_status_in_chat(chat, data['users']);
            jQuery.each(data['users'], function (index, data) {
                onlineList.append(data['link']);
                if (data['write'] == 1)
                    user_write = 1;
            });
        }

        if (data['content']) {
            uspc_play_sound();
            uspc_blink_tab();
            chat.find('.uspc-im__talk').append(data['content']);
            uspc_scroll_down(data.token);
        } else {
            if (user_write)
                uspc_chat_write_status(data.token);
        }
    }

    usp_do_action('uspc_get_new_messages', {
        token: data.token,
        result: data
    });
}

/* Direct messages */

// add in top dm status
function uspc_status_in_chat(chat, allData) {
    var head = chat.parents('.uspc-messenger-box').find('.uspc-head-box');
    var headStatus = head.find('.uspc-head__status');
    var users = [];

    jQuery.each(allData, function (index, data) {
        users.push(+data['user_id']);
    });

    if (jQuery.inArray(head.data('head-id'), users) != -1) {
        let offline = head.find('.usp-status-user.usp-offline');
        if (offline.length) {
            offline.remove();
            head.find('.uspc-head__left > a').append('<i class="uspi fa-circle usp-status-user usp-online"></i>');
        }
        if (headStatus.html() == '') {
            headStatus.text(USP.local.uspc_in_chat);
        }
    } else {
        headStatus.text('');
    }
}

// open direct message
// noinspection JSUnusedGlobalSymbols
function uspc_get_chat_dm(e, user_id) {
    usp_preloader_show(jQuery(e));

    usp_ajax({
        data: {
            action: 'uspc_get_direct_message',
            user_id: user_id
        },
        success: function (data) {
            if (data.content) {
                jQuery('.usp-subtab-content').html('');
                jQuery('.usp-tab-chat .usp-subtab-title').remove();
                jQuery('#usp-tab__chat.usp-bttn__active').addClass('usp-bttn__active-dm');
                jQuery('#usp-subtab-private-contacts .usp-subtab-content').prepend(data.content);
            }
        }
    });

}

// tab loading hook
usp_add_action('usp_upload_tab', 'uspc_set_chat_button_in_menu_active');

function uspc_set_chat_button_in_menu_active(e) {
    // is chat tab
    if (e.result.tab_id === 'chat') {
        jQuery('#usp-tab__chat').addClass('usp-bttn__active');
    }
}

//
jQuery(function ($) {
    // after leaving the communication, clear usp_beat
    $('body').on('click', '.uspc-head__bttn', function () {
        let tok = $('.uspc-head__bttn').data('token-dm');
        uspc_chat_clear_beat(tok);
    });

    uspc_auto_height_textarea();

    // click on the block with unread - we will reduce the counters
    $('body').on('click', '.uspc-unread__incoming', function () {
        var cnt = $('#usp-tab__chat .usp-bttn__count').text(),
            bttn = $('.uspc_js_counter_unread .usp-bttn__count');

        if (cnt > 1) {
            let contactIncoming = $(this).data('unread');
            setTimeout(function () {
                bttn.html(cnt - contactIncoming);
            }, 1000);
        } else if (cnt === '1') {
            setTimeout(function () {
                bttn.hide();
            }, 1000);
        }

        // remove notify on contact panel
        let contact = $(this).data('contact');
        let contact_panel_user = $('.uspc-mini__contacts').find("[data-contact='" + contact + "']");

        contact_panel_user.children(' .uspc-mini-person__in').hide();
    });
});

// from the chat tab went to direct communication
usp_add_action('uspc_get_direct_message', 'uspc_logged_in_to_dm');

function uspc_logged_in_to_dm() {
    // scroll posts
    let mess = jQuery('.uspc-post');
    if (mess.length >= 1) {
        setTimeout(function () {
            jQuery('.uspc-post').scrollTop(jQuery('.uspc-post').get(0).scrollHeight);
        }, 200);
    }

    uspc_slide_to_textarea();

    uspc_auto_height_textarea();
}

// auto-height of the input field
function uspc_auto_height_textarea() {
    let form = jQuery('.uspc-im__form textarea');
    jQuery(form).css({'height': 'auto', 'overflow-y': 'auto'});

    form.each(function () {
        let initial = this.scrollHeight;
        this.setAttribute('style', 'height:' + initial + 'px;overflow-y:hidden;');
    }).on('input', function () {

        this.style.height = 'auto';
        let h = this.scrollHeight + 9;
        if (h > 150) {
            h = 150;
            this.setAttribute('style', 'height:' + h + 'px;overflow-y:auto;');
            return;
        }
        this.style.height = h + 'px';
    });
}

// scroll to form
function uspc_slide_to_textarea(top = 0) {
    let h = window.innerHeight;
    let chatForm = jQuery('.uspc-im__form');

    if (chatForm.length < 1)
        return false;

    //  offset to form
    let offsetToChat = chatForm.offset().top;
    // if the browser window is huge - not scroll
    if ((h + 150) < offsetToChat) {
        let vw = jQuery(window).width();
        // height of the form
        let offset = 160;
        if (vw <= 480) {
            offset = 200;
        }
        jQuery('body,html').animate({
            scrollTop: offsetToChat - (h - offset - top)
        }, 1000);
    }
}

/* end */

// noinspection JSUnusedGlobalSymbols
function uspc_on_off_sound() {
    let mute = (+jQuery.cookie('uspc_sound_off') === 1) ? 0 : 1;

    jQuery.cookie('uspc_sound_off', mute, {
        expires: 30,
        path: '/'
    });

    let volume = jQuery('.uspc-im-form__on-off i');
    if (mute) {
        volume.removeClass('fa-volume-up').addClass('fa-volume-off');
    } else {
        volume.removeClass('fa-volume-off').addClass('fa-volume-up');
    }

    return false;
}

function uspc_mute_chat() {
    return (+jQuery.cookie('uspc_sound_off') === 1) ? 0 : 1;
}

/* blink title tab */
var uspcTitle = document.title;
var isActive = true;
var uspcTimer;

window.onfocus = function () {
    isActive = true;

    clearInterval(uspcTimer);
    document.title = uspcTitle;
};

window.onblur = function () {
    isActive = false;
};

function uspc_blink_tab() {
    if (isActive)
        return;

    var mess = '*';
    uspcTimer = setInterval(function () {
        if (mess === '*') {
            mess = '**';
        } else if (mess === '**') {
            mess = '***';
        } else {
            mess = '*';
        }

        document.title = mess + ' ' + uspcTitle;
    }, 1000);
}

/**/

usp_add_action('uspc_init', 'uspc_run_in_chat');
usp_add_action('uspc_load_page', 'uspc_run_in_chat');
usp_add_action('uspc_load_focus_modal', 'uspc_run_in_chat');
usp_add_action('uspc_close_focus_modal', 'uspc_run_in_chat');
usp_add_action('uspc_get_direct_message', 'uspc_run_in_chat');

function uspc_run_in_chat() {
    uspc_max_width();
    uspc_date_sticky();
    uspc_hide_date();
}

function uspc_max_width() {
    let maxWidth = Math.max.apply(null, jQuery('.uspc-date__day').map(function () {
            return jQuery(this).outerWidth(true);
        }).get()
    );

    jQuery('.uspc-date__day').css({'min-width': maxWidth});
}

function uspc_date_sticky() {
    document.querySelectorAll(".uspc-date").forEach((i) => {
        const observer = new IntersectionObserver(([i]) =>
                i.target.classList.toggle("uspc-date-sticky", i.intersectionRatio < 1),
            {root: document.querySelector('.uspc-im__talk'), rootMargin: '0px 0px 300px 0px', threshold: [1]});
        observer.observe(i);
    })
}

function uspc_hide_date() {
    var waiting = false;
    var t = false;
    jQuery(".uspc-im__talk").on('scroll', function () {
        if (waiting) {
            return;
        }
        waiting = true;
        setTimeout(function () {
            waiting = false;
        }, 400);

        clearTimeout(t);
        jQuery(".uspc-date-sticky").removeClass('uspc-date-hide');
        checkScroll();
    });

    function checkScroll() {
        t = setTimeout(function () {
            jQuery(".uspc-date-sticky").addClass('uspc-date-hide');
        }, 2000);
    }

    return;
}

// noinspection JSUnusedGlobalSymbols
function uspc_focus_modal_shift(i) {
    let chat = jQuery(i).parents('.uspc-messenger-js');
    let html = chat.html();

    chat.css({'height': jQuery(chat).outerHeight(), 'width': jQuery(chat).outerWidth()});

    jQuery('.uspc-messenger-js').html('');

    let chatModal = ssi_modal.show({
        content: html,
        bodyElement: true,
        sizeClass: 'medium',
        className: 'uspc-chat-modal ssi-dialog ssi-no-padding',
    })

    chatModal.get$modal().on('onShow.ssi-modal', function () {
        usp_do_action('uspc_load_focus_modal', html);
    });
    chatModal.get$modal().on('onClose.ssi-modal', function () {
        usp_do_action('uspc_close_focus_modal', html);
    });
}

usp_add_action('uspc_load_focus_modal', 'uspc_scroll_in_modal');

function uspc_scroll_in_modal() {
    USPUploaders.init();

    uspc_scroll_by_selector('.uspc-chat-modal .uspc-im__talk');
}

usp_add_action('uspc_close_focus_modal', 'uspc_is_close_modal');

function uspc_is_close_modal(html) {
    let chat = jQuery('.uspc-messenger-js');
    chat.removeAttr('style');
    chat.html(html);

    USPUploaders.init();

    uspc_scroll_by_selector('.uspc-im__talk');
}

// pm in modal window
// open PM in modal: 1 - this; 2 - id chat user
function uspc_get_chat_window(e, user_id) {
    usp_preloader_show(jQuery(e), 36);

    usp_ajax({
        data: {
            action: 'uspc_get_ajax_chat_window',
            user_id: user_id
        }
    });
}
