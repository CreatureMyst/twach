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

    public function createMessage(User $user)
    {
        $this->message = new Message();
        $this->getMessage()->setUser($user);

        return $this;
    }

    public function setText($text)
    {
        $this->getMessage()->setText($text);
        return $this;
    }

    public function setAttachment($type = MessageAttachment::TYPE_LINK, $resource)
    {
        // TODO: Attachments
        return $this;
    }

    public function saveMessage()
    {
        $this->getEm()->merge($this->getMessage());
        $this->getEm()->flush();

        // Костыль, т.к. EntityManager::Merge работает как-то криво.
        $message = $this->getMessage();
        $this->message = null;

        return $message;
    }
}