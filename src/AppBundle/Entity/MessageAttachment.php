<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageAttachment
 *
 * @ORM\Table(name="message_attachment")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MessageAttachmentRepository")
 */
class MessageAttachment
{
    const TYPE_LINK = 1;        // Ссылка
    const TYPE_IMAGE = 2;       // Изображение
    const TYPE_YOUTUBE = 3;     // Ролик с YouTube

    public static $types = [
        self::TYPE_LINK => 'Ссылка',
        self::TYPE_IMAGE => 'Изображение',
        self::TYPE_YOUTUBE => 'YouTube',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="message_id", type="integer")
     */
    private $messageId;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="resource", type="string", length=255)
     */
    private $resource;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="attachments")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     */
    private $message;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set messageId
     *
     * @param integer $messageId
     * @return MessageAttachment
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return integer
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return MessageAttachment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set resource
     *
     * @param string $resource
     * @return MessageAttachment
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return MessageAttachment
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set message
     *
     * @param \AppBundle\Entity\Message $message
     * @return MessageAttachment
     */
    public function setMessage(\AppBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return \AppBundle\Entity\Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
