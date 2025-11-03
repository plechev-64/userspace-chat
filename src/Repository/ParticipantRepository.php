<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

use UserSpace\Core\Database\DatabaseConnectionInterface;

class ParticipantRepository implements ParticipantRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $db)
    {
    }

    public function findParticipantByChatAndUser(int $chatId, int $userId): ?array
    {
        $result = $this->db->queryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('chat_id', '=', $chatId)
            ->where('user_id', '=', $userId)
            ->first();

        return $result ? (array)$result : null;
    }

    public function createParticipant(int $chatId, int $userId, string $role = 'member'): int
    {
        $this->db->insert($this->getTableName(), [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $this->db->getInsertId();
    }

    protected function getTableName(): string
    {
        return $this->db->getPrefix() . 'userspace_chat_participants';
    }
}