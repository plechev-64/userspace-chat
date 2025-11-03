const initializeChat = () => {
    const UspCore = window.UspCore;
    if (!UspCore || !UspCore.api) {
        console.error('UserSpace API not found.');
        return;
    }

    const chatContainer = document.querySelector('.usp-chat-container:not([data-initialized])');
    if (!chatContainer) {
        return; // Контейнер не найден или уже инициализирован
    }
    chatContainer.dataset.initialized = 'true'; // Помечаем как инициализированный

    const l10n = window.uspChatL10n || {};
    const state = {
        currentChatId: null,
        currentUserId: UspCore.userId, // Получаем ID текущего пользователя
        profileOwnerId: null, // ID владельца профиля, на котором открыт чат
        chats: {}, // Хранилище состояний для каждого чата
        isLoadingMore: false,
    };

    const elements = {
        chatList: chatContainer.querySelector('.usp-chat-list'),
        messagesWindow: chatContainer.querySelector('.usp-chat-messages-window'),
        chatTitle: chatContainer.querySelector('.usp-chat-title'),
        messageForm: chatContainer.querySelector('.usp-chat-message-form'),
        messageInput: chatContainer.querySelector('.usp-chat-message-input'),
    };

    /**
     * Инициализация чата.
     */
    const init = async () => {
        // Обработчик отправки сообщения
        elements.messagesWindow.addEventListener('scroll', onScrollMessages);
        elements.messageForm.addEventListener('submit', onSendMessage);
        elements.chatList.addEventListener('click', onChatListItemClick);

        // Определяем, какой чат инициализировать
        const initialUserId = parseInt(chatContainer.dataset.userId, 10);
        const initialTopicId = chatContainer.dataset.topicId;

        state.profileOwnerId = !isNaN(initialUserId) && initialUserId > 0 ? initialUserId : null;

        if (initialTopicId) {
            chatContainer.classList.add('usp-chat-container--topic');
            await findOrCreateTopicChat(initialTopicId, chatContainer.dataset.title);
        } else if (!isNaN(initialUserId) && initialUserId > 0) {
            await findOrCreatePrivateChat(initialUserId);
        } else {
            // Иначе просто загружаем список чатов
            await loadChatList();
        }

        // Подключаемся к SSE
        initChatSse();
    };

    /**
     * Находит или создает тематический чат и загружает его.
     * @param {string} topicId
     * @param {string} title
     */
    const findOrCreateTopicChat = async (topicId, title) => {
        try {
            const response = await UspCore.api.post('/chat/topic', { 'topic-id': topicId, title: title });
            if (response.chat_id) {
                await switchToChat(response.chat_id, title || l10n.defaultTopicTitle);
            } else {
                console.error('Error creating or finding topic chat:', response.message);
            }
        } catch (error) {
            console.error('API call failed:', error);
        }
    };

    /**
     * Находит или создает приватный чат и загружает его.
     * @param {number} userId
     */
    const findOrCreatePrivateChat = async (userId) => {
        // Предотвращаем создание чата с самим собой
        if (userId === state.currentUserId) {
            console.warn('Attempted to create a chat with oneself. Aborting.');
            return;
        }

        try {
            const response = await UspCore.api.post('/chat/private', { user_id: userId });
            if (response.chat_id) {
                await loadChatList(); // Перезагружаем список, чтобы новый чат появился
                await switchToChat(response.chat_id);
            } else {
                console.error('Error creating or finding chat:', response.message);
            }
        } catch (error) {
            console.error('API call failed:', error);
        }
    };

    /**
     * Загружает и отображает список чатов.
     */
    const loadChatList = async () => {
        try {
            const response = await UspCore.api.get('/chat/list');
            if(response)
                renderChatList(response);
        } catch (error) {
            console.error('Failed to load chat list:', error);
            elements.chatList.innerHTML = `<p>${l10n.errorLoadingChats}</p>`;
        }
    };

    /**
     * Рендерит список чатов в сайдбаре.
     * @param {Array} chats
     */
    const renderChatList = (chats) => {
        if (chats.length === 0) {
            // Если нет чатов, но мы в чужом профиле, не показываем "No chats yet"
            const initialUserId = parseInt(chatContainer.dataset.userId, 10);
            if (!isNaN(initialUserId) && initialUserId > 0) {
                elements.chatList.innerHTML = '';
                return;
            }
            elements.chatList.innerHTML = `<p>${l10n.noChats}</p>`;
            return;
        }
        elements.chatList.innerHTML = chats.map(chat => {
            const isOnline = isUserOnline(chat.last_activity_timestamp, chat.contact_user_id);
            const statusClass = isOnline ? 'usp-chat-contact-status--online' : '';

            return `
            <div class="usp-chat-list-item" data-chat-id="${chat.chat_id}" data-user-id="${chat.contact_user_id}" data-chat-title="${escapeHtml(chat.title || l10n.defaultChatTitle)}">
                 <div class="usp-chat-list-item-title">
                    <span class="usp-chat-contact-status ${statusClass}"></span>
                    <span>${escapeHtml(chat.title || l10n.defaultChatTitle)}</span>
                </div>
                <span class="usp-chat-notification-badge" style="display: none;"></span>
            </div>
        `}).join('');

        // Обновляем активный чат в списке, если он есть
        if (state.currentChatId) {
            const activeItem = elements.chatList.querySelector(`.usp-chat-list-item[data-chat-id="${state.currentChatId}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
        }
    };

    /**
     * Проверяет, онлайн ли пользователь, на основе времени последней активности.
     * @param {string|null} lastActivityTimestamp - UNIX-время последней активности.
     * @param {string|number|null} userId - ID пользователя.
     * @returns {boolean}
     */
    const isUserOnline = (lastActivityTimestamp, userId = null) => {
        if (!lastActivityTimestamp) return false;
        const lastActivity = parseInt(lastActivityTimestamp, 10) || 0;
        const now = Math.floor(Date.now() / 1000);
        return (now - lastActivity) < 300; // Считаем онлайн, если активность была в течение 5 минут
    };

    /**
     * Переключается на указанный чат и загружает его сообщения.
     * @param {number} chatId
     * @param {string|null} title
     */
    const switchToChat = async (chatId, title = null) => {
        state.currentChatId = chatId;

        // Управление активным классом в списке чатов
        const chatItem = elements.chatList.querySelector(`.usp-chat-list-item[data-chat-id="${chatId}"]`);
        if (chatItem) {
            // Сбрасываем и скрываем счетчик при открытии чата
            const badge = chatItem.querySelector('.usp-chat-notification-badge');
            badge.textContent = '';
            badge.style.display = 'none';
        }

        elements.chatList.querySelector('.usp-chat-list-item.active')?.classList.remove('active');
        const activeChatItem = elements.chatList.querySelector(`.usp-chat-list-item[data-chat-id="${chatId}"]`);
        if (activeChatItem) activeChatItem.classList.add('active');

        elements.chatTitle.textContent = title || l10n.defaultChatTitle;
        elements.messagesWindow.innerHTML = `<p>${l10n.loadingMessages}</p>`;
        elements.messageInput.disabled = false;
        elements.messageForm.querySelector('button').disabled = false;

        // Инициализируем состояние для нового чата
        if (!state.chats[chatId]) {
            state.chats[chatId] = { offset: 0, has_more: true, messages: [] };
        }

        try {
            // При переключении чата всегда запрашиваем первую страницу сообщений
            const response = await UspCore.api.get(`/chat/messages/${chatId}/offset/0`);
            if (response.messages) {
                state.chats[chatId].messages = response.messages;
                state.chats[chatId].has_more = response.has_more;
                state.chats[chatId].offset = response.messages.length;
                renderMessages(response.messages, false);
            } else {
                elements.messagesWindow.innerHTML = `<p>${response.message}</p>`;
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
            elements.messagesWindow.innerHTML = `<p>${l10n.errorLoadingMessages}</p>`;
        }
    };

    /**
     * Обработчик клика по элементу списка чатов (делегирование).
     * @param {Event} e
     */
    const onChatListItemClick = (e) => {
        const chatItem = e.target.closest('.usp-chat-list-item');
        if (!chatItem) {
            return;
        }

        const chatId = parseInt(chatItem.dataset.chatId, 10);
        switchToChat(chatId, chatItem.dataset.chatTitle);
    };

    /**
     * Обработчик отправки нового сообщения.
     * @param {Event} e
     */
    const onSendMessage = async (e) => {
        e.preventDefault();
        const content = elements.messageInput.value.trim();

        if (!content || !state.currentChatId) {
            return;
        }

        // 1. Оптимистично создаем и отображаем сообщение
        const optimisticMessage = {
            id: Date.now(), // Временный ID
            chat_id: state.currentChatId,
            sender_id: state.currentUserId,
            sender_name: UspCore.userName,
            content: content,
            created_at: new Date().toISOString(),
        };
        appendMessages([optimisticMessage]);
        elements.messageInput.value = '';

        // 2. Отправляем сообщение на сервер в фоновом режиме
        try {
            const response = await UspCore.api.post('/chat/message', {
                chat_id: state.currentChatId,
                content: content,
            });

            if (!response.message_id) {
                // Если произошла ошибка на сервере, можно показать уведомление
                console.error('Error sending message:', response.message);
                // TODO: Можно добавить логику для пометки сообщения как "не отправлено"
            }
        } catch (error) {
            // Обработка ошибок сети
            console.error('API call failed:', error);
        }
    };

    /**
     * Инициализирует SSE-клиент и подписывается на события чата.
     */
    const initChatSse = () => {
        UspCore.sse.addEventListener('chat_messages', 'private_message', (message) => {
            // Игнорируем собственное сообщение, которое пришло по SSE,
            // так как мы уже отобразили его оптимистично.
            if (parseInt(message.sender_id, 10) === parseInt(state.currentUserId, 10)) {
                return;
            }

            // Отображаем сообщение, если оно для текущего чата
            if (parseInt(message.chat_id, 10) === state.currentChatId) {
                appendMessages([message]);
            } else {
                // Иначе показываем уведомление в списке чатов
                const chatItem = elements.chatList.querySelector(`.usp-chat-list-item[data-chat-id="${message.chat_id}"]`);
                if (chatItem) {
                    const badge = chatItem.querySelector('.usp-chat-notification-badge');
                    const currentCount = parseInt(badge.textContent, 10) || 0;
                    badge.textContent = currentCount + 1;
                    badge.style.display = 'flex';
                }
            }
        });

        // Отдельный канал для обновления статусов активности
        UspCore.sse.addEventListener('chat_activity', 'activity_update', (activityData) => {
            for (const userId in activityData) {
                const contactItem = elements.chatList.querySelector(`.usp-chat-list-item[data-user-id="${userId}"]`);
                if (contactItem) {
                    const statusIndicator = contactItem.querySelector('.usp-chat-contact-status');
                    const isOnline = activityData[userId] === 'online';
                    statusIndicator.classList.toggle('usp-chat-contact-status--online', isOnline); // true добавит класс, false - удалит
                }
            }
        });
    };

    /**
     * Создает HTML-разметку для одного сообщения.
     * @param {object} msg - Объект сообщения.
     * @param {string|null} prevSenderId - ID предыдущего отправителя для группировки.
     * @returns {string} HTML-строка сообщения.
     */
    const createMessageHtml = (msg, prevSenderId = null) => {
        // Сравниваем ID отправителя с ID текущего пользователя, приводя их к числовому типу
        const messageClass = parseInt(msg.sender_id, 10) === parseInt(state.currentUserId, 10) ? 'usp-chat-message--sent' : 'usp-chat-message--received';
        const groupClass = String(prevSenderId) === String(msg.sender_id) ? 'usp-chat-message--grouped' : '';

        return `
             <div class="usp-chat-message ${messageClass} ${groupClass}" data-sender-id="${msg.sender_id}">
                 <div class="usp-chat-message-sender">${escapeHtml(msg.sender_name)}</div>
                 <div class="usp-chat-message-content">${escapeHtml(msg.content)}</div>
                 <div class="usp-chat-message-time">${formatMessageTime(msg.created_at)}</div>
             </div>
         `;
    };

    /**
     * Полностью перерисовывает окно сообщений.
     * @param {Array} messages - Массив объектов сообщений.
     */
    const renderMessages = (messages) => {
        if (messages.length === 0) {
            elements.messagesWindow.innerHTML = `<p>${l10n.noMessages}</p>`;
            return;
        }

        let lastSenderId = null;
        const messagesHtml = messages.map(msg => {
            const html = createMessageHtml(msg, lastSenderId);
            lastSenderId = msg.sender_id;
            return html;
        }).join('');

        elements.messagesWindow.innerHTML = messagesHtml;

        // При первоначальной загрузке - скроллим вниз
        setTimeout(() => {
            elements.messagesWindow.scrollTop = elements.messagesWindow.scrollHeight;
        }, 0);
    };

    /**
     * Добавляет сообщения в конец списка.
     * @param {Array} messages - Массив объектов сообщений.
     */
    const appendMessages = (messages) => {
        let lastSenderId = elements.messagesWindow.querySelector('.usp-chat-message:last-child')?.dataset.senderId ?? null;

        const messagesHtml = messages.map(msg => {
            const html = createMessageHtml(msg, lastSenderId);
            lastSenderId = msg.sender_id;
            return html;
        }).join('');

        elements.messagesWindow.insertAdjacentHTML('beforeend', messagesHtml);
        elements.messagesWindow.scrollTop = elements.messagesWindow.scrollHeight;
    };

    /**
     * Добавляет старые сообщения в начало списка.
     * @param {Array} messages - Массив объектов сообщений.
     */
    const prependMessages = (messages) => {
        if (messages.length === 0) return;

        const oldScrollHeight = elements.messagesWindow.scrollHeight;
        const oldScrollTop = elements.messagesWindow.scrollTop;

        // Проверяем, нужно ли сгруппировать первое из *уже существующих* сообщений
        const firstExistingMessage = elements.messagesWindow.querySelector('.usp-chat-message:first-child');
        const lastPrependingMessage = messages[messages.length - 1]; // Самое "новое" из подгружаемых
        if (firstExistingMessage && String(firstExistingMessage.dataset.senderId) === String(lastPrependingMessage.sender_id)) {
            firstExistingMessage.classList.add('usp-chat-message--grouped');
        }

        let lastSenderId = null;
        // Рендерим в обратном порядке для правильной группировки
        const messagesHtml = [...messages].reverse().map(msg => {
            const html = createMessageHtml(msg, lastSenderId);
            lastSenderId = msg.sender_id;
            return html;
        }).reverse().join('');

        elements.messagesWindow.insertAdjacentHTML('afterbegin', messagesHtml);

        // Восстанавливаем позицию скролла
        elements.messagesWindow.scrollTop = elements.messagesWindow.scrollHeight - oldScrollHeight + oldScrollTop;
    };

    const onScrollMessages = async (e) => {
        if (state.isLoadingMore) return;

        if (e.target.scrollTop === 0) {
            const chatState = state.chats[state.currentChatId];
            if (!chatState || !chatState.has_more) {
                return; // Больше нечего загружать
            }

            state.isLoadingMore = true;
            const response = await UspCore.api.get(`/chat/messages/${state.currentChatId}/offset/${chatState.offset}`);
            if (response.messages && response.messages.length > 0) {
                chatState.has_more = response.has_more;
                chatState.offset += response.messages.length;
                // Сообщения приходят отсортированными от новых к старым, для prepend их нужно развернуть
                prependMessages(response.messages.reverse());
            } else {
                chatState.has_more = false;
            }
            state.isLoadingMore = false;
        }
    };

    const escapeHtml = (unsafe) => {
        return unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    };

    /**
     * Форматирует время сообщения.
     * @param {string} dateString - Строка даты/времени.
     * @returns {string} - Локализованное время.
     */
    const formatMessageTime = (dateString) => {
        // '2023-10-27 15:04:05' -> '2023-10-27T15:04:05'
        return new Date(dateString.replace(' ', 'T')).toLocaleTimeString();
    };

    init(); // Запускаем основную логику
};

// Запускаем при первоначальной загрузке страницы
document.addEventListener('DOMContentLoaded', initializeChat);
// Запускаем после загрузки контента вкладки через AJAX (предполагаемое имя события)
document.addEventListener('usp:tabContentLoaded', initializeChat);