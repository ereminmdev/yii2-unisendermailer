<?php

namespace ereminmdev\yii2\unisendermailer;

use yii\mail\BaseMessage;

/**
 * Class Message
 * @package ereminmdev\yii2\unisendermailer
 *
 * @see Mailer
 *
 * @property int $type
 * @property int $listId
 * @property mixed $cc
 * @property mixed $charset
 * @property mixed $bcc
 * @property string $htmlBody
 * @property array $attachments
 * @property mixed $subject
 * @property mixed $replyTo
 * @property string $textBody
 * @property mixed $from
 * @property mixed $to
 */
class Message extends BaseMessage
{
    const TYPE_EMAIL = 1;
    const TYPE_SMS = 2;

    /**
     * @var int type of message. Defaults to e-mail.
     */
    protected $_type = 1;
    /**
     * @var int Unisender list id
     */
    protected $_listId;

    protected $_charset;
    protected $_from;
    protected $_replyTo;
    protected $_to = [];
    protected $_cc;
    protected $_bcc;
    protected $_subject;
    protected $_textBody;
    protected $_htmlBody;
    protected $_attachments = [];

    /**
     * @return int
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->_type = in_array($type, [self::TYPE_EMAIL, self::TYPE_SMS]) ? $type : $this->_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getListId()
    {
        return $this->_listId;
    }

    /**
     * @param int $listId
     */
    public function setListId($listId)
    {
        $this->_listId = $listId;
    }

    /**
     * @inheritdoc
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * @param array|string $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->_from = $from;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    /**
     * @param array|string $replyTo
     * @return $this
     */
    public function setReplyTo($replyTo)
    {
        $this->_replyTo = $replyTo;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * @param array|string $to
     * @return $this
     */
    public function setTo($to)
    {
        if (is_string($to)) {
            $to = mb_strtolower($to);
        } elseif (is_array($to)) {
            $to = array_unique($to);
        }

        $this->_to = $to;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * @param array|string $cc
     * @return $this
     */
    public function setCc($cc)
    {
        $this->_cc = $cc;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * @param array|string $bcc
     * @return $this
     */
    public function setBcc($bcc)
    {
        $this->_bcc = $bcc;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->_textBody;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setTextBody($text)
    {
        $this->_textBody = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->_htmlBody;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function setHtmlBody($html)
    {
        $this->_htmlBody = $html;
        return $this;
    }

    /**
     * @param string $fileName
     * @param array $options
     * @return $this
     */
    public function attach($fileName, array $options = [])
    {
        $this->addAttachment('attach', $fileName, $options);
        return $this;
    }

    /**
     * @param string $content
     * @param array $options
     * @return $this
     */
    public function attachContent($content, array $options = [])
    {
        $this->addAttachment('attachContent', $content, $options);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function embed($fileName, array $options = [])
    {
        $this->addAttachment('embed', $fileName, $options);
        return count($this->getAttachments()) - 1;
    }

    /**
     * @inheritdoc
     */
    public function embedContent($content, array $options = [])
    {
        $this->addAttachment('embedContent', $content, $options);
        return count($this->getAttachments()) - 1;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments($attachments)
    {
        $this->_attachments = $attachments;
    }

    /**
     * @param string $type
     * @param string $content
     * @param array $options
     */
    public function addAttachment($type, $content, $options = [])
    {
        $this->_attachments[] = [$type, $content, $options];
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return $this->getSubject();
    }

    /**
     * @param Mailer|null $mailer
     * @return bool
     */
    public function sendEmail(Mailer $mailer = null)
    {
        $this->type = self::TYPE_EMAIL;
        return parent::send($mailer);
    }

    /**
     * @param Mailer|null $mailer
     * @return bool
     */
    public function sendSms(Mailer $mailer = null)
    {
        $this->type = self::TYPE_SMS;
        return parent::send($mailer);
    }
}
