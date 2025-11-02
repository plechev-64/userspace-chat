<?php


use UserSpace\Chat\Repository\AttachmentRepository;
use UserSpace\Chat\Grid\ContactListGrid;
use UserSpace\Chat\Repository\AttachmentRepositoryInterface;
use UserSpace\Chat\Repository\ChatRepository;
use UserSpace\Chat\Repository\ChatRepositoryInterface;
use UserSpace\Chat\Repository\MessageRepository;
use UserSpace\Chat\Repository\MessageRepositoryInterface;
use UserSpace\Chat\Repository\ParticipantRepository;
use UserSpace\Chat\Repository\ParticipantRepositoryInterface;
use UserSpace\Core\Container\ContainerInterface;

return [
    'parameters' => [
        'app.templates' => [
            'chat-main' => __DIR__ . '/../views/chat-main.php',
        ],
        'app.grids' => [
            'chat-contacts' => ContactListGrid::class,
        ],
    ],
    'definitions' => [
        // --- Привязка интерфейсов к реализациям ---
        ChatRepositoryInterface::class => fn(ContainerInterface $c) => $c->get(ChatRepository::class),
        ParticipantRepositoryInterface::class => fn(ContainerInterface $c) => $c->get(ParticipantRepository::class),
        MessageRepositoryInterface::class => fn(ContainerInterface $c) => $c->get(MessageRepository::class),
        AttachmentRepositoryInterface::class => fn(ContainerInterface $c) => $c->get(AttachmentRepository::class),
    ],
];