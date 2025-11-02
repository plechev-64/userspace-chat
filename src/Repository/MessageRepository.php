<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

use UserSpace\Core\Database\DatabaseConnectionInterface;
use UserSpace\Core\Database\QueryBuilderInterface;

class MessageRepository implements MessageRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $db)
    {
    }

    protected function getTableName(): string
    {
        return $this->db->getPrefix() . 'userspace_chat_messages';
    }

    public function createMessage(int $chatId, int $senderId, string $content): int
    {
        $this->db->insert($this->getTableName(), [
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'content' => $content,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $this->db->getInsertId();
    }

    public function getMessagesByChatId(int $chatId, int $limit = 50, int $offset = 0): array
    {
        $usersTable = $this->db->getPrefix() . 'users';

        return $this->db->queryBuilder()
            ->select('m.*', 'u.display_name as sender_name')
            ->from($this->getTableName(), 'm')
            ->addJoin('INNER', $usersTable, 'u', 'm.sender_id = u.ID')
            ->where('m.chat_id', '=', $chatId) // Убедитесь, что $chatId - это int
            ->orderBy('m.id', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    public function getNewMessagesForUser(int $userId, int $lastId): array
    {

        $participantsTable = $this->db->getPrefix() . 'userspace_chat_participants';
        $usersTable = $this->db->getPrefix() . 'users';

        return $this->db->queryBuilder()
            ->select('m.id, m.chat_id, m.sender_id, m.content, m.created_at', 'u.display_name as sender_name')
            ->from($this->getTableName(), 'm')
            ->addJoin('INNER', $usersTable, 'u', 'm.sender_id = u.ID')
            ->where('m.id', '>', $lastId)
            ->where('m.chat_id', 'IN', function (QueryBuilderInterface $query) use ($participantsTable, $userId) {
                $query->select('p.chat_id')
                    ->from($participantsTable, 'p')
                    ->where('p.user_id', '=', $userId);
            })
            ->get();
    }

    public function getLastMessageForUser(int $userId): ?object
    {
        $participantsTable = $this->db->getPrefix() . 'userspace_chat_participants';
        $usersTable = $this->db->getPrefix() . 'users';

        // 1. Получаем ID всех чатов, в которых состоит пользователь
        $chatIdRows = $this->db->queryBuilder()
            ->select('chat_id')
            ->from($participantsTable)
            ->where('user_id', '=', $userId)
            ->get();

        if (empty($chatIdRows)) {
            return null;
        }

        $chatIds = array_map(fn($row) => (int)$row->chat_id, $chatIdRows);

        // 2. Получаем последнее сообщение из этих чатов
        return $this->db->queryBuilder()
            ->select('m.id, m.chat_id, m.sender_id, m.content, m.created_at', 'u.display_name as sender_name')
            ->from($this->getTableName(), 'm')
            ->addJoin('INNER', $usersTable, 'u', 'm.sender_id = u.ID')
            ->where('m.chat_id', 'IN', $chatIds)
            ->orderBy('m.id', 'DESC')
            ->first();
    }
}