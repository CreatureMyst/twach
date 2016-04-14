<?php

namespace AppBundle\Service;

use AppBundle\Entity\Message;
use AppBundle\Entity\MessageAttachment;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class MessageService
{
    private $em;

    /** @var Message */
    private $message;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Метод создает сообщение и указывает автора.
     *
     * @param User $user
     * @return $this
     */
    public function createMessage(User $user)
    {
        // Особый костыль. Почему-то в объект $user, который передается в метод
        // воспринимается доктриной как NotPersistent, хотя это не так.
        // Такой костыль помогает исправить траблу.
        $user = $this->getEm()->find('AppBundle\Entity\User', $user->getId());

        $this->message = new Message();
        $this->getMessage()->setUser($user);

        return $this;
    }

    /**
     * Метод добавляет текст сообщения.
     *
     * @param $text
     * @return $this
     */
    public function setText($text)
    {
        $this->getMessage()->setText($text);
        return $this;
    }

    /**
     * Метод добавляет аттач к сообщению.
     *
     * @param int $type
     * @param $resource
     * @return $this
     */
    public function setAttachment($type = MessageAttachment::TYPE_LINK, $resource)
    {
        if(!array_key_exists($type, MessageAttachment::$types)) {
            return $this;
        }

        if(empty($resource)) {
            return $this;
        }

        switch ($type) {
            case MessageAttachment::TYPE_YOUTUBE:
                $resource = $this->formatResourceYouTube($resource);
                break;

            case MessageAttachment::TYPE_IMAGE:
                $resource = $this->formatResourceImage($resource);
                break;

            case MessageAttachment::TYPE_LINK:
                $resource = $this->formatResourceLink($resource);
                break;
        }

        $attachment = new MessageAttachment();
        $attachment
            ->setMessage($this->getMessage())
            ->setType($type)
            ->setResource($resource);

        $this->getMessage()->addAttachment($attachment);
        return $this;
    }

    /**
     * Метод сохраняет сообщение в базу и возвращает его же.
     *
     * @return Message
     */
    public function saveMessage()
    {
        $this->getEm()->persist($this->getMessage());
        $this->getEm()->flush();

        return $this->getMessage();
    }

    /**
     * Метод форматирует YouTube ресурс.
     *
     * @param $resource
     * @return bool
     */
    private function formatResourceYouTube($resource)
    {
        $url = parse_url($resource);

        if($url['host'] != 'www.youtube.com') {
            return false;
        }

        return substr($url['query'], 2, strlen($url['query']));
    }

    private function formatResourceImage($resource)
    {
        return $resource;
    }

    /**
     * Метод форматирует Link ресурс.
     *
     * @param $resource
     * @return mixed
     */
    private function formatResourceLink($resource)
    {
        $url = parse_url($resource);
        if(!array_key_exists('scheme', $url)) {
            $resource = 'http://' . $resource;
        }

        return $resource;
    }
}