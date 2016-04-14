<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TwachTopic implements TopicInterface
{
    protected $clientManipulator;
    protected $messageService;
    protected $security;

    public function __construct(ClientManipulatorInterface $clientManipulator, MessageService $service, SecurityContextInterface $securityContext)
    {
        $this->clientManipulator = $clientManipulator;
        $this->messageService = $service;
        $this->security = $securityContext;
    }

    /**
     * @return ClientManipulatorInterface
     */
    public function getClientManipulator()
    {
        return $this->clientManipulator;
    }

    /**
     * @return MessageService
     */
    public function getMessageService()
    {
        return $this->messageService;
    }

    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $topic->broadcast(['msg' => $this->getUsername($connection) . ' was joined']);
    }

    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $topic->broadcast(['msg' => $this->getUsername($connection) . ' offline']);
    }

    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        if(is_array($event)) {
            if(array_key_exists('message[text]', $event)) {
                $message = $this->createMessage($connection, $event['message[text]']);
                $topic->broadcast(['message' => $message->serialize()]);
            }
        }
    }

    public function getName()
    {
        return 'app.twach';
    }

    /**
     * Метод возвращает имя пользователя.
     *
     * @param ConnectionInterface $connection
     * @return false|string|UserInterface
     */
    private function getUsername(ConnectionInterface $connection)
    {
        $user = $this->getClientManipulator()->getClient($connection);
        return ($user instanceof UserInterface) ? $user->getUsername() : $user;
    }

    public function getUser(ConnectionInterface $connection)
    {
        $user = $this->getClientManipulator()->getClient($connection);
        return ($user instanceof UserInterface) ? $user : false;
    }

    private function createMessage(ConnectionInterface $connection, $text)
    {
        $user = $this->getUser($connection);
        if(!$user instanceof User) {
            return false;
        }

        return $this->getMessageService()
            ->createMessage($user)
            ->setText($text)
            ->saveMessage();
    }
}
