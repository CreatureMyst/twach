<?php

namespace AppBundle\Service;

use AppBundle\Entity\MessageAttachment;
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
//        $topic->broadcast(['msg' => $this->getUsername($connection) . ' was joined']);
    }

    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
//        $topic->broadcast(['msg' => $this->getUsername($connection) . ' offline']);
    }

    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        if(!is_array($event) || !array_key_exists('event', $event)) {
            return false;
        }

        switch ($event['event']) {
            case 'message.create':
                $message = $this->createMessage($connection, $event['data']);
                $topic->broadcast(['message_create' => $message->serialize()]);
                break;
            case 'message.delete';
                // Delete a message;
                break;
            case 'message.like';
                // Like a message;
                break;
        }

//        if(is_array($event)) {
//            if(array_key_exists('message[text]', $event)) {
//                $message = $this->createMessage($connection, $event['message[text]']);
//                $topic->broadcast(['message' => $message->serialize()]);
//            }
//        }
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

    /**
     * Метод создает сообщение.
     *
     * @param ConnectionInterface $connection
     * @param $data
     * @return \AppBundle\Entity\Message|bool
     */
    private function createMessage(ConnectionInterface $connection, $data)
    {
        $user = $this->getUser($connection);
        if(!$user instanceof User) {
            return false;
        }

        // Обращаемся к специальному сервису для сообщений.
        $message = $this->getMessageService()
            ->createMessage($user)
            ->setText($data['message[text]']);

        // Задаем аттачи.
        foreach($data['attachments'] as $attachment) {
            $message->setAttachment($attachment['type'], $attachment['resource']);
        }

        // Сохраняем и возвращаем сообщение.
        return $message->saveMessage();
    }
}
