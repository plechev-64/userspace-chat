<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

use UserSpace\Core\Database\DatabaseConnectionInterface;

class ChatRepository implements ChatRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $db)
    {
    }

    public function findPrivateChatIdBetweenUsers(int $userOneId, int $userTwoId): ?int
    {
        $participantTable = $this->db->getPrefix() . 'userspace_chat_participants';

        // Для консистентности сортируем ID пользователей
        $users = [$userOneId, $userTwoId];
        sort($users);

        $result = $this->db->queryBuilder()
            ->select('p1.chat_id')
            ->from($participantTable, 'p1')
            ->addJoin('INNER', $participantTable, 'p2', 'p1.chat_id = p2.chat_id')
            ->addJoin('INNER', $this->getTableName(), 'c', 'p1.chat_id = c.id')
            ->where('c.type', '=', 'private')
            ->where('p1.user_id', '=', $users[0])
            ->where('p2.user_id', '=', $users[1])
            ->first();

        return $result ? (int)$result->chat_id : null;
    }

    public function getUserPrivateChats(int $userId): array
    {
        $participantsTable = $this->db->getPrefix() . 'userspace_chat_participants';
        $usersTable = $this->db->getPrefix() . 'users';

        return $this->db->queryBuilder()
            ->select(
                'c.id as chat_id',
                'c.type',
                // Для приватных чатов выбираем имя собеседника, для групповых - название чата
                'IF(c.type = "private", u.display_name, c.title) as title'
            )
            ->from($this->getTableName(), 'c')
            // Присоединяем участников, чтобы найти чаты текущего пользователя
            ->addJoin('INNER', $participantsTable, 'p_current', 'c.id = p_current.chat_id')
            // Для приватных чатов присоединяем участников еще раз, чтобы найти собеседника
            ->addJoin('LEFT', $participantsTable, 'p_other', 'c.id = p_other.chat_id AND p_other.user_id != ' . (int)$userId)
            ->addJoin('LEFT', $usersTable, 'u', 'p_other.user_id = u.ID')
            ->where('p_current.user_id', '=', $userId)
            ->where('c.type', '=', 'private') // Добавляем фильтр только для приватных чатов
            ->groupBy('c.id')
            ->orderBy('c.id', 'DESC') // Пример сортировки
            ->get();
    }

    public function createPrivateChat(int $creatorId): int
    {
        $this->db->insert($this->getTableName(), [
            'type' => 'private',
            'creator_id' => $creatorId,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $this->db->getInsertId();
    }

    public function findChatByTopicId(string $topicId): ?int
    {
        $result = $this->db->queryBuilder()
            ->select('id')
            ->from($this->getTableName())
            ->where('topic_id', '=', $topicId)
            ->first();

        return $result ? (int)$result->id : null;
    }

    public function createTopicChat(string $topicId, int $creatorId, string $title = ''): int
    {
        $this->db->insert($this->getTableName(), [
            'type' => 'topic',
            'topic_id' => $topicId,
            'title' => $title,
            'creator_id' => $creatorId,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $this->db->getInsertId();
    }

    protected function getTableName(): string
    {
        return $this->db->getPrefix() . 'userspace_chats';
    }
}