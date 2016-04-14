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
                if($this->deleteMessage($connection, $event['data'])) {
                    $topic->broadcast(['message_delete' => $event['data']['id']]);
                }
                break;
            case 'message.like';
                $likes = $this->likeMessage($connection, $event['data']);

                if(is_int($likes)) {
                    $topic->broadcast(['message_like' => $event['data']['id'], 'likes' => $likes]);
                }

                break;
        }
    }

    public function getName()
    {
        return 'app.twach';
    }

    /**
     * Метод возвращает объект авторизованного юзера или false.
     *
     * @param ConnectionInterface $connection
     * @return false|UserInterface
     */
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
            ->setText($data['message']);

        // Задаем аттачи.
        foreach($data['attachments'] as $attachment) {
            $message->setAttachment($attachment['type'], $attachment['resource']);
        }

        // Сохраняем и возвращаем сообщение.
        return $message->saveMessage();
    }

    /**
     * Метод удаляет сообщение по ID.
     *
     * @param ConnectionInterface $connection
     * @param $data
     * @return bool
     */
    private function deleteMessage(ConnectionInterface $connection, array $data)
    {
        $user = $this->getUser($connection);
        if(!$user instanceof User) {
            return false;
        }

        if($this->getMessageService()->deleteMessage($data['id'], $user)) {
            return $data['id'];
        }

        return false;
    }

    /**
     * Метод добавляет или снимает лайк с записи.
     *
     * @param ConnectionInterface $connection
     * @param array $data
     * @return bool|int
     */
    private function likeMessage(ConnectionInterface $connection, array $data)
    {
        $user = $this->getUser($connection);
        if(!$user instanceof User) {
            return false;
        }

        return $this->getMessageService()->likeMessage($data['id'], $user);
    }
}
