<?php

namespace ereminmdev\yii2\unisendermailer;

use Unisender\ApiWrapper\UnisenderApi;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\mail\BaseMailer;
use yii\mail\MessageInterface;

/**
 * Class Mailer
 * @package ereminmdev\yii2\unisendermailer
 *
 * @link https://www.unisender.com/ru/support/integration/api/
 *
 * @property UnisenderApi $api
 * @property array $errors
 */
class Mailer extends BaseMailer
{
    /**
     * @var string Unisender api key
     */
    public $apiKey;
    /**
     * @var int Unisender list id
     */
    public $listId;
    /**
     * @var string
     */
    public $senderName;
    /**
     * @var string
     */
    public $senderEmail;
    /**
     * @var string Sender name from 3 up to 11 symbols of latin characters and digits.
     */
    public $smsSenderName;
    /**
     * @var string encoding charset
     */
    public $encoding;
    /**
     * @var string ru, it, ua и en (will translate to english: da, de, es, fr, nl, pl, pt, tr)
     */
    public $messageLang = 'en';
    /**
     * @var int timeout
     */
    public $timeout;
    /**
     * @var bool use bzip2 compression
     */
    public $compression;
    /**
     * @var int retry count
     */
    public $retryCount = 4;
    /**
     * @var int maximum message count to use simple sending
     */
    public $maxSimpleCount = 4;
    /**
     * @var bool add error to session addFlash
     */
    public $flashError = true;
    /**
     * @var string platform name
     */
    public $platform = 'Yii2';
    /**
     * @var string message default class name
     */
    public $messageClass = 'ereminmdev\yii2\unisendermailer\Message';
    /**
     * @var bool importing contacts before sending
     */
    public $isImportContacts = true;
    /**
     * @var string prefix for error messages and logs
     */
    public $errorPrefix = 'Unisender: ';

    /**
     * @var UnisenderApi
     */
    protected $_api;
    /**
     * @var array of error messages
     */
    protected $_errors = [];

    /**
     * @param null $view
     * @param array $params
     * @return Message|MessageInterface
     */
    public function compose($view = null, array $params = [])
    {
        return parent::compose($view, $params);
    }

    /**
     * @return UnisenderApi
     * @throws InvalidConfigException
     */
    public function getApi()
    {
        if ($this->_api === null) {
            $encoding = $this->encoding !== null ? $this->encoding : Yii::$app->charset;

            if (!$this->apiKey) {
                throw new InvalidConfigException('"' . get_class($this) . '::apiKey" should be specified.');
            }
            if (!$this->senderName) {
                throw new InvalidConfigException('"' . get_class($this) . '::senderName" should be specified.');
            }
            if (!$this->senderEmail) {
                throw new InvalidConfigException('"' . get_class($this) . '::senderEmail" should be specified.');
            }

            if (!$this->smsSenderName) {
                throw new InvalidConfigException('"' . get_class($this) . '::smsSenderName" should be specified.');
            }
            if (!preg_match('/^[a-zA-Z0-9]{3,11}$/', $this->smsSenderName)) {
                throw new InvalidConfigException('"' . get_class($this) . '::smsSenderName" should be from 3 up to 11 symbols of latin characters and digits.');
            }

            $this->_api = new UnisenderApi($this->apiKey, $encoding, $this->retryCount, $this->timeout, $this->compression, $this->platform);
        }
        return $this->_api;
    }

    /**
     * Get Unisender message template.
     * @see https://www.unisender.com/ru/support/integration/api/gettemplate
     *
     * @param array $params
     * @return mixed|false
     */
    public function getTemplate($params = [])
    {
        return $this->p($this->api->getTemplate($params));
    }

    /**
     * Send simple e-mail message.
     * @see https://www.unisender.com/ru/support/integration/api/sendemail
     *
     * @param array $params
     * @return bool
     */
    public function sendEmail($params = [])
    {
        return $this->p($this->api->sendEmail($params));
    }

    /**
     * Send simple sms message.
     * @see https://www.unisender.com/ru/support/integration/api/sendsms
     *
     * @param array $params
     * @return bool
     */
    public function sendSms($params = [])
    {
        return $this->p($this->api->sendSms($params));
    }

    /**
     * Return existing list id or create new one.
     *
     * @param Message $message
     * @return int
     */
    public function getListId($message)
    {
        $listId = $message->listId ? $message->listId : $this->listId;

        if (!$listId) {
            $result = $this->p($this->api->createList([
                'title' => $message->getSubject(),
            ]));

            if ($result !== false) {
                return $result->id;
            }
        }
        return $listId;
    }

    /**
     * @param array $field_names
     * @param array $data
     * @return bool
     */
    public function importContacts($field_names, $data)
    {
        $result = false;
        $chunks = array_chunk($data, 500, true);
        foreach ($chunks as $chunk) {
            $result = $result || $this->p($this->api->importContacts([
                    'field_names' => $field_names,
                    'data' => $chunk,
                ]));
        }
        return $result;
    }

    /**
     * @param Message $message
     * @return bool
     */
    public function createEmailMessage($message)
    {
        $address = $this->getAddress($message);
        $listId = $this->getListId($message);

        $params = [
            'email' => $address,
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'subject' => $message->getSubject(),
            'body' => $message->getHtmlBody(),
            'list_id' => $listId,
            'lang' => $this->messageLang,
        ];

        $trackParams = [
            'track_read' => 1,
            'track_links' => 1,
        ];

        if (count($address) <= $this->maxSimpleCount) {
            return $this->sendEmail(ArrayHelper::merge($trackParams, $params));
        }

        if ($this->isImportContacts) {
            $contacts = array_map(function ($email) use ($listId) {
                return [$email, $listId];
            }, $address);

            $result = $this->importContacts(['email', 'email_list_ids'], $contacts);
        } else {
            $result = true;
        }

        if ($result !== false) {
            $data = $params;
            $data['list_id'] = $listId;
            unset($data['email']);

            $result = $this->p($this->api->createEmailMessage($data));

            if ($result !== false) {
                $messageId = $result->message_id;

                $result = $this->p($this->api->createCampaign(ArrayHelper::merge($trackParams, [
                    'message_id' => $messageId,
                    'contacts' => implode(',', $address),
                    'defer' => 1,
                ])));

                return $result !== false ? $result : false;
            }
        }
        return false;
    }

    /**
     * @param Message $message
     * @return bool
     */
    public function createSmsMessage($message)
    {
        $address = $this->getAddress($message);
        $listId = $this->getListId($message);

        $params = [
            'phone' => implode(',', $address),
            'sender' => $this->smsSenderName,
            'text' => mb_substr($message->getTextBody(), 0, 1000),
        ];

        if (count($address) <= $this->maxSimpleCount) {
            return $this->sendSms($params);
        }

        if ($this->isImportContacts) {
            $contacts = array_map(function ($phone) use ($listId) {
                return [$phone, $listId];
            }, $address);

            $result = $this->importContacts(['phone', 'phone_list_ids'], $contacts);
        } else {
            $result = true;
        }

        if ($result !== false) {
            $result = $this->p($this->api->createSmsMessage([
                'sender' => $params['sender'],
                'body' => $params['text'],
                'list_id' => $listId,
            ]));

            if ($result !== false) {
                $messageId = $result->message_id;

                $result = $this->p($this->api->createCampaign([
                    'message_id' => $messageId,
                    'contacts' => implode(',', $address),
                    'defer' => 1,
                ]));

                return $result !== false ? $result : false;
            }
        }
        return false;
    }

    /**
     * @param Message $message
     * @return array
     */
    protected function getAddress($message)
    {
        $address = [];

        $to = (array)$message->getTo();
        foreach ($to as $key => $value) {
            $address[] = is_string($key) ? $key : $value;
        }

        return $address;
    }

    /**
     * Process result.
     *
     * @param string $result
     * @return mixed|false
     */
    public function p($result)
    {
        if ($result) {
            $jsonObj = json_decode($result);
            if (null === $jsonObj) {
                $this->addError(Yii::t('app', 'Invalid JSON'));
            } elseif (!empty($jsonObj->error)) {
                $this->addError(Yii::t('app', 'An error occured: {error}(code: {code})', ['error' => $jsonObj->error, 'code' => $jsonObj->code]));
            } else {
                return $jsonObj->result;
            }
        } else {
            $this->addError(Yii::t('app', 'API access error'));
        }
        return false;
    }

    /**
     * @param string $message
     */
    public function addError($message)
    {
        $message = $this->errorPrefix . $message;

        $this->_errors[] = $message;

        Yii::error($message, __METHOD__);

        if ($this->flashError) {
            Yii::$app->session->addFlash('error', $message);
        }
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    /**
     * @return array of error messages
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors($errors)
    {
        $this->_errors = $errors;
    }

    /**
     * @param Message $message
     * @return bool
     */
    protected function sendMessage($message)
    {
        $address = $message->getTo();
        if (is_array($address)) {
            $address = implode(', ', array_keys($address));
        }

        Yii::info($this->errorPrefix . 'Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);

        if ($message->type == Message::TYPE_EMAIL) {
            return $this->createEmailMessage($message);
        } elseif ($message->type == Message::TYPE_SMS) {
            return $this->createSmsMessage($message);
        }
        return false;
    }
}
