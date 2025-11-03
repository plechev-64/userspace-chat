<?php

declare(strict_types=1);

namespace UserSpace\Chat\Controller;

use UserSpace\Chat\UseCase\FindOrCreatePrivateChat\FindOrCreatePrivateChatRequest;
use UserSpace\Chat\UseCase\FindOrCreateTopicChat\FindOrCreateTopicChatRequest;
use UserSpace\Chat\UseCase\FindOrCreateTopicChat\FindOrCreateTopicChatUseCase;
use UserSpace\Chat\UseCase\FindOrCreatePrivateChat\FindOrCreatePrivateChatUseCase;
use UserSpace\Chat\UseCase\GetChatMessages\GetChatMessagesRequest;
use UserSpace\Chat\UseCase\GetChatMessages\GetChatMessagesUseCase;
use UserSpace\Chat\UseCase\GetUserChats\GetUserChatsUseCase;
use UserSpace\Chat\UseCase\SendMessage\SendMessageRequest;
use UserSpace\Chat\UseCase\SendMessage\SendMessageUseCase;
use UserSpace\Common\Module\Settings\Src\Domain\PluginSettingsInterface;
use UserSpace\Common\Module\User\Src\Domain\UserApiInterface;
use UserSpace\Core\Exception\UspException;
use UserSpace\Core\Http\JsonResponse;
use UserSpace\Core\Http\Request;
use UserSpace\Core\Rest\Abstract\AbstractController;
use UserSpace\Core\Rest\Attributes\Route;
use UserSpace\Core\Sanitizer\SanitizerInterface;
use UserSpace\Core\Sanitizer\SanitizerRule;

#[Route(path: '/chat')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly SanitizerInterface $sanitizer,
        private readonly UserApiInterface   $userApi
    )
    {
    }

    /**
     * Находит или создает приватный чат между текущим пользователем и указанным.
     */
    #[Route(path: '/private', method: 'POST')]
    public function findOrCreatePrivate(
        Request                        $request,
        FindOrCreatePrivateChatUseCase $findOrCreatePrivateChatUseCase
    ): JsonResponse
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            return $this->error(['message' => 'User not authenticated.'], 401);
        }

        $sanitizationConfig = ['user_id' => SanitizerRule::INT];
        $clearedData = $this->sanitizer->sanitize($request->getPostParams(), $sanitizationConfig)->all();

        $targetUserId = $clearedData['user_id'];
        if ($targetUserId === 0) {
            return $this->error(['message' => 'Invalid user_id provided.'], 400);
        }

        $useCaseRequest = new FindOrCreatePrivateChatRequest(
            $currentUser->getId(),
            $targetUserId
        );

        try {
            $chatId = $findOrCreatePrivateChatUseCase->handle($useCaseRequest);
            return $this->success(['chat_id' => $chatId]);
        } catch (UspException $e) {
            return $this->error(['message' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Находит или создает чат по строковому идентификатору (теме).
     */
    #[Route(path: '/topic', method: 'POST')]
    public function findOrCreateTopic(
        Request                      $request,
        FindOrCreateTopicChatUseCase $findOrCreateTopicChatUseCase
    ): JsonResponse
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            return $this->error(['message' => 'User not authenticated.'], 401);
        }

        $sanitizationConfig = [
            'topic-id' => SanitizerRule::SLUG,
            'title' => SanitizerRule::TEXT_FIELD,
        ];
        $clearedData = $this->sanitizer->sanitize($request->getPostParams(), $sanitizationConfig);

        $topicId = $clearedData->get('topic-id');
        if (empty($topicId)) {
            return $this->error(['message' => 'Invalid topic_id provided.'], 400);
        }

        $useCaseRequest = new FindOrCreateTopicChatRequest(
            $topicId,
            $currentUser->getId(),
            $clearedData->get('title', 'Topic Chat')
        );

        try {
            $chatId = $findOrCreateTopicChatUseCase->handle($useCaseRequest);
            return $this->success(['chat_id' => $chatId]);
        } catch (UspException $e) {
            return $this->error(['message' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Отправляет сообщение в чат.
     */
    #[Route(path: '/message', method: 'POST')]
    public function sendMessage(
        Request            $request,
        SendMessageUseCase $sendMessageUseCase
    ): JsonResponse
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            return $this->error(['message' => 'User not authenticated.'], 401);
        }

        $sanitizationConfig = [
            'chat_id' => SanitizerRule::INT,
            'content' => SanitizerRule::KSES_POST
        ];
        $clearedData = $this->sanitizer->sanitize($request->getPostParams(), $sanitizationConfig)->all();

        $useCaseRequest = new SendMessageRequest(
            $clearedData['chat_id'],
            $currentUser->getId(),
            $clearedData['content']
        );

        try {
            $messageId = $sendMessageUseCase->handle($useCaseRequest);
            return $this->success(['message_id' => $messageId]);
        } catch (UspException|\RuntimeException $e) {
            return $this->error(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Получает список чатов текущего пользователя.
     */
    #[Route(path: '/list', method: 'GET')]
    public function getChatList(GetUserChatsUseCase $getUserChatsUseCase): JsonResponse
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            return $this->error(['message' => 'User not authenticated.'], 401);
        }

        $chats = $getUserChatsUseCase->handle($currentUser->getId());
        return $this->success($chats);
    }


    /**
     * Получает сообщения для указанного чата.
     */
    #[Route(path: '/messages/(?P<chatId>[0-9]+)/offset/(?P<offset>[0-9]+)', method: 'GET')]
    public function getMessages(
        Request                 $request,
        GetChatMessagesUseCase  $getChatMessagesUseCase,
        PluginSettingsInterface $pluginSettings
    ): JsonResponse
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            return $this->error(['message' => 'User not authenticated.'], 401);
        }

        /** @todo создать enum опций */
        $messagesPerPage = $pluginSettings->get('chat_messages_per_page', 20);

        $clearedData = $this->sanitizer->sanitize([
            'chatId' => $request->getRouteParam('chatId'),
            'offset' => $request->getRouteParam('offset')
        ], [
            'chatId' => SanitizerRule::INT,
            'offset' => SanitizerRule::INT
        ]);

        $useCaseRequest = new GetChatMessagesRequest(
            $currentUser->getId(),
            $clearedData->get('chatId'),
            $messagesPerPage,
            $clearedData->get('offset', 0)
        );

        try {
            $messages = $getChatMessagesUseCase->handle($useCaseRequest);
            return $this->success($messages);
        } catch (UspException $e) {
            return $this->error(['message' => $e->getMessage()], $e->getCode());
        }
    }
}