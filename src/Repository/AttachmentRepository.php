<?php

declare(strict_types=1);

namespace UserSpace\Chat\Repository;

use UserSpace\Core\Database\DatabaseConnectionInterface;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $db)
    {
    }

    protected function getTableName(): string
    {
        return $this->db->getPrefix() . 'userspace_chat_attachments';
    }
}