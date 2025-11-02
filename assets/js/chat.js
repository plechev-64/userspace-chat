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

    const state = {
        currentChatId: null,
        currentUserId: UspCore.userId, // Получаем ID текущего пользователя
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

        // Если в шорткод передан user_id, инициируем приватный чат
        const initialUserId = parseInt(chatContainer.dataset.userId, 10);
        if (!isNaN(initialUserId) && initialUserId > 0) {
            await findOrCreatePrivateChat(initialUserId);
        } else {
            // Иначе просто загружаем список чатов
            await loadChatList();
        }

        // Подключаемся к SSE
        initChatSse();
    };

    /**
     * Находит или создает приватный чат и загружает его.
     * @param {number} userId
     */
    const findOrCreatePrivateChat = async (userId) => {
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
            if (response) {
                renderChatList(response);
            }
        } catch (error) {
            console.error('Failed to load chat list:', error);
            elements.chatList.innerHTML = '<p>Error loading chats.</p>';
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
            elements.chatList.innerHTML = '<p>No chats yet.</p>';
            return;
        }
        elements.chatList.innerHTML = chats.map(chat => `
            <div class="usp-chat-list-item" data-chat-id="${chat.chat_id}" data-chat-title="${escapeHtml(chat.title)}">
                ${escapeHtml(chat.title)}
            </div>
        `).join('');

        // Навешиваем обработчики кликов
        elements.chatList.querySelectorAll('.usp-chat-list-item').forEach(item => {
            item.addEventListener('click', () => {
                const chatId = parseInt(item.dataset.chatId, 10);
                switchToChat(chatId, item.dataset.chatTitle);
            });
        });
    };

    /**
     * Переключается на указанный чат и загружает его сообщения.
     * @param {number} chatId
     * @param {string|null} title
     */
    const switchToChat = async (chatId, title = null) => {
        state.currentChatId = chatId;

        // Управление активным классом в списке чатов
        elements.chatList.querySelectorAll('.usp-chat-list-item.active').forEach(el => el.classList.remove('active'));
        const activeChatItem = elements.chatList.querySelector(`.usp-chat-list-item[data-chat-id="${chatId}"]`);
        if (activeChatItem) activeChatItem.classList.add('active');

        elements.chatTitle.textContent = title || 'Chat';
        elements.messagesWindow.innerHTML = '<p>Loading messages...</p>';
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
                renderMessages(response.messages);
            } else {
                elements.messagesWindow.innerHTML = `<p>${response.message}</p>`;
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
            elements.messagesWindow.innerHTML = '<p>Error loading messages.</p>';
        }
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
        renderMessages([optimisticMessage], true);
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
                renderMessages([message], true);
            }
        });

        UspCore.sse.addEventListener('chat_messages', 'last_message', (message) => {
            console.log('Received last_message to sync SSE state:', message);
        });
    };

    /**
     * Рендерит сообщения в окне чата.
     * @param {Array} messages Массив объектов сообщений.
     * @param {boolean} append Добавить в конец или переписать.
     */
    const renderMessages = (messages, append = false) => {
        if (!append && messages.length === 0 && !state.chats[state.currentChatId]?.messages.length) {
            elements.messagesWindow.innerHTML = '<p>No messages in this chat yet.</p>';
            return;
        }
        let lastSenderId = append ? (elements.messagesWindow.querySelector('.usp-chat-message:last-child')?.dataset.senderId ?? null) : null;

        const messagesHtml = messages.map(msg => {
            const messageClass = parseInt(msg.sender_id) === parseInt(state.currentUserId) ? 'usp-chat-message--sent' : 'usp-chat-message--received';
            let groupClass = '';

            // Если отправитель тот же, что и у предыдущего сообщения, добавляем класс для группировки
            if (String(lastSenderId) === String(msg.sender_id)) {
                groupClass = 'usp-chat-message--grouped';
            }

            lastSenderId = msg.sender_id; // Обновляем ID последнего отправителя

            return `
             <div class="usp-chat-message ${messageClass} ${groupClass}" data-sender-id="${msg.sender_id}">
                 <div class="usp-chat-message-sender">${escapeHtml(msg.sender_name)}</div>
                 <div class="usp-chat-message-content">${escapeHtml(msg.content)}</div>
                 <div class="usp-chat-message-time">${new Date(msg.created_at.replace(' ', 'T')).toLocaleTimeString()}</div>
             </div>`}).join('');

        if (append) {
            elements.messagesWindow.insertAdjacentHTML('beforeend', messagesHtml);
        } else {
            elements.messagesWindow.innerHTML = messagesHtml;
        }

        if (append) {
            // При добавлении нового сообщения (SSE) - скроллим вниз
            elements.messagesWindow.scrollTop = elements.messagesWindow.scrollHeight;
        } else {
            // При первоначальной загрузке - скроллим вниз
            setTimeout(() => {
                elements.messagesWindow.scrollTop = elements.messagesWindow.scrollHeight;
            }, 0);
        }
    };

    const prependMessages = (messages) => {
        const oldScrollHeight = elements.messagesWindow.scrollHeight;
        const oldScrollTop = elements.messagesWindow.scrollTop;

        // Проверяем, нужно ли сгруппировать первое из *уже существующих* сообщений
        const firstExistingMessage = elements.messagesWindow.querySelector('.usp-chat-message:first-child');
        const lastPrependingMessage = messages[0]; // Самое новое из подгружаемых
        if (firstExistingMessage && String(firstExistingMessage.dataset.senderId) === String(lastPrependingMessage.sender_id)) {
            firstExistingMessage.classList.add('usp-chat-message--grouped');
        }

        let lastSenderId = null;
        const messagesHtml = messages.map(msg => {
            const messageClass = parseInt(msg.sender_id) === parseInt(state.currentUserId) ? 'usp-chat-message--sent' : 'usp-chat-message--received';
            let groupClass = '';

            if (String(lastSenderId) === String(msg.sender_id)) {
                groupClass = 'usp-chat-message--grouped';
            }

            lastSenderId = msg.sender_id;

            return `
             <div class="usp-chat-message ${messageClass} ${groupClass}" data-sender-id="${msg.sender_id}">
                 <div class="usp-chat-message-sender">${escapeHtml(msg.sender_name)}</div>
                 <div class="usp-chat-message-content">${escapeHtml(msg.content)}</div>
                 <div class="usp-chat-message-time">${new Date(msg.created_at.replace(' ', 'T')).toLocaleTimeString()}</div>
             </div>
         `}).join('');

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
            chatState.has_more = response.has_more;
            chatState.offset += response.messages.length;
            prependMessages(response.messages);

            state.isLoadingMore = false;
        }
    };

    const escapeHtml = (unsafe) => {
        return unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    };

    init(); // Запускаем основную логику
};

// Запускаем при первоначальной загрузке страницы
document.addEventListener('DOMContentLoaded', initializeChat);
// Запускаем после загрузки контента вкладки через AJAX (предполагаемое имя события)
document.addEventListener('usp:tabContentLoaded', initializeChat);