<?php

declare(strict_types=1);

namespace UserSpace\Chat\Grid;

use UserSpace\Common\Module\Grid\Src\Domain\AbstractListContentGrid;
use UserSpace\Common\Module\User\Src\Domain\UserApiInterface;
use UserSpace\Core\Asset\AssetRegistryInterface;
use UserSpace\Core\Database\DatabaseConnectionInterface;
use UserSpace\Core\String\StringFilterInterface;

class ContactListGrid extends AbstractListContentGrid
{
    private UserApiInterface $userApi;

    public function __construct(
        DatabaseConnectionInterface $db,
        StringFilterInterface       $str,
        AssetRegistryInterface      $assetRegistry,
        UserApiInterface            $userApi,
    )
    {
        parent::__construct($db, $str, $assetRegistry);
        $this->userApi = $userApi;
    }

    protected function getTableName(): string
    {
        return $this->db->getPrefix() . 'users';
    }

    protected function getTableAlias(): string
    {
        return 'u';
    }

    protected function getSelectColumns(): array
    {
        return ['u.ID', 'u.display_name', 'u.user_email'];
    }

    protected function getJoins(): array
    {
        $currentUser = $this->userApi->getCurrentUser();
        if (!$currentUser) {
            // Возвращаем невыполнимое условие, если пользователь не авторизован
            return [
                ['type' => 'INNER', 'table' => 'users', 'alias' => 'dummy', 'condition' => '1=0']
            ];
        }

        $participantsTable = $this->db->getPrefix() . 'userspace_chat_participants';
        $chatsTable = $this->db->getPrefix() . 'userspace_chats';

        // Находим чаты, в которых участвует текущий пользователь
        $subQuery = $this->db->queryBuilder()
            ->select('DISTINCT p_sub.chat_id')
            ->from($participantsTable, 'p_sub')
            ->where('p_sub.user_id', '=', $currentUser->getId())
            ->buildQuery();

        return [
            [
                'type' => 'INNER',
                'table' => $participantsTable,
                'alias' => 'p',
                'condition' => 'u.ID = p.user_id'
            ],
            [
                'type' => 'INNER',
                'table' => $chatsTable,
                'alias' => 'c',
                'condition' => 'p.chat_id = c.id AND c.type = "private"'
            ],
            [
                'type' => 'WHERE_IN',
                'column' => 'p.chat_id',
                'subQuery' => $subQuery
            ],
            [
                'type' => 'WHERE_NOT_IN',
                'column' => 'u.ID',
                'values' => [$currentUser->getId()]
            ]
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['u.display_name', 'u.user_email'];
    }

    public function getEndpointPath(): string
    {
        return 'chat/contacts';
    }

    public function getItemTemplatePath(): string
    {
        // Используем стандартный шаблон вывода пользователя из базового плагина
        return USERSPACE_PLUGIN_DIR . 'views/grid/user-item.php';
    }
}