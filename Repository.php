<?php

namespace Chat2Desk;

use Cache\Adapter\Filesystem\FilesystemCachePool;

class Repository
{
    /**
     * @var FilesystemCachePool
     */
    private $cache;

    public function __construct(FilesystemCachePool $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param $client_id
     * @param array|object $conversationData
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function saveClientConversation($client_id, $conversationData)
    {
        $item = $this->cache->getItem('chat2desk_conversation_by_user_' . $client_id)->set(json_encode($conversationData));
        $this->cache->save($item);
    }

    public function getClientConversation($client_id)
    {
        $item = $this->cache->getItem('chat2desk_conversation_by_user_' . $client_id);
        return json_decode($item->get());
    }

    public function existsUser($userId): bool
    {
        return $this->cache->hasItem('chat2desk_user_' . $userId);
    }

    public function getOperatorIdByUser($userId)
    {
        return $this->cache->get('chat2desk_user_' . $userId);
    }

    public function saveOperatorIdByUser($userId, $operatorId)
    {
        $this->cache->set('chat2desk_user_' . $userId, $operatorId);
    }
}
