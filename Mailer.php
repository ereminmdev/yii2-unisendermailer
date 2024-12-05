<?php

namespace ereminmdev\yii2\unisendermailer;

use Unisender\ApiWrapper\UnisenderApi;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
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
     * @var string Unisender api language
     */
    public $apiLanguage;
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
     * @var string Two-letter language code for the string with the unsubscribe link that is added to each letter automatically.
     */
    public $messageLanguage;
    /**
     * @var string encoding charset
     */
    public $encoding;
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
     * @var int maximum sms count to use simple sending
     */
    public $maxSmsSimpleCount = 10;
    /**
     * @var int size of contacts chunk for import contacts and create campaign
     */
    public $chunkSize = 500;
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
     * @var string path to store contacts
     */
    public $contacts_path = '@webroot/files/unisender/contacts/{message_id}.txt';
    /**
     * @var string url to stored contacts
     */
    public $contacts_url = '@web/files/unisender/contacts/{message_id}.txt';

    /**
     * @var UnisenderApi
     */
    protected $_api;
    /**
     * @var array of error messages
     */
    protected $_errors = [];

    /**
     * Initializes the object.
     */
    public function init()
    {
        parent::init();

        $currentLanguage = mb_substr(Yii::$app->language, 0, 2);
        $this->apiLanguage = $this->apiLanguage ?: $currentLanguage;
        $this->messageLanguage = $this->messageLanguage ?: $currentLanguage;
    }

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
            $encoding = $this->encoding ?? Yii::$app->charset;

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

            $api = new UnisenderApi($this->apiKey, $encoding, $this->retryCount, $this->timeout, $this->compression, $this->platform);
            $api->setApiHostLanguage($this->apiLanguage);

            $this->_api = $api;
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
        return $this->callApi('getTemplate', $params);
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
        return $this->callApi('sendEmail', $params);
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
        return $this->callApi('sendSms', $params);
    }

    /**
     * Return existing list id or create new one.
     *
     * @param Message $message
     * @return int
     */
    public function getListId($message)
    {
        $listId = $message->listId ?: $this->listId;

        if (!$listId) {
            $result = $this->callApi('createList', [
                'title' => $message->getSubject(),
            ]);

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
        $chunks = array_chunk($data, $this->chunkSize);
        foreach ($chunks as $chunk) {
            $res = $this->callApi('importContacts', [
                'field_names' => $field_names,
                'data' => $chunk,
                'overwrite_tags' => 1,
            ]);
            if ($res) {
                foreach ($res->log as $err) {
                    $this->addError($err->message);
                }
                $result = $result || $res->total;
            } else {
                $result = false;
            }
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
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'subject' => $message->getSubject(),
            'body' => $message->getHtmlBody(),
            'list_id' => $listId,
            'lang' => $this->messageLanguage,
        ];

        $trackParams = [
            'track_read' => 1,
            'track_links' => 1,
        ];

        if (count($address) <= $this->maxSimpleCount) {
            $result = false;
            foreach ($address as $email) {
                $params['email'] = $email;
                $result = $this->sendEmail(array_merge($trackParams, $params)) || $result;
            }
            return $result;
        }

        if ($this->isImportContacts) {
            $contacts = array_map(fn($email) => [$email, $listId], $address);
            $result = $this->importContacts(['email', 'email_list_ids'], $contacts);
        } else {
            $result = true;
        }

        if ($result !== false) {
            $result = $this->callApi('createEmailMessage', $params);

            if ($result !== false) {
                return $this->createCampaign($result->message_id, $message, $address, $trackParams);
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
        $text = mb_substr($message->getTextBody(), 0, 1000);

        if (count($address) <= $this->maxSmsSimpleCount) {
            return $this->sendSms([
                'phone' => implode(',', $address),
                'sender' => $this->smsSenderName,
                'text' => $text,
            ]);
        }

        if ($this->isImportContacts) {
            $contacts = array_map(fn($phone) => [$phone, $listId], $address);
            $result = $this->importContacts(['phone', 'phone_list_ids'], $contacts);
        } else {
            $result = true;
        }

        if ($result !== false) {
            $result = $this->callApi('createSmsMessage', [
                'sender' => $this->smsSenderName,
                'body' => $text,
                'list_id' => $listId,
            ]);
            if ($result !== false) {
                return $this->createCampaign($result->message_id, $message, $address);
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
     * @param string $message
     */
    public function addError($message)
    {
        $message = $this->errorPrefix . $message;

        $this->_errors[] = $message;

        Yii::error($message);

        if ($this->flashError && Yii::$app->has('session')) {
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
     * @param array|string $from set sender as array [email => name] or as string 'name'
     * @param bool $isEmail set to (senderName,senderEmail) if true, otherwise to smsSenderName
     */
    public function setFrom($from, $isEmail = true)
    {
        if (empty($from)) {
            return;
        }

        $email = is_string($from) ? $from : '';
        $name = is_string($from) ? $from : '';

        if (is_array($from)) {
            foreach ($from as $email => $name) {
                break;
            }
        }

        if ($isEmail) {
            $this->senderName = $name;
            $this->senderEmail = $email;
        } else {
            $this->smsSenderName = $name;
        }
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

        $this->setFrom($message->getFrom(), $message->type == Message::TYPE_EMAIL);

        Yii::info($this->errorPrefix . 'Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);

        if ($message->type == Message::TYPE_EMAIL) {
            return $this->createEmailMessage($message);
        } elseif ($message->type == Message::TYPE_SMS) {
            return $this->createSmsMessage($message);
        }
        return false;
    }

    /**
     * @param int $messageId
     * @param Message $message
     * @param array $address
     * @param array $params
     * @return bool
     */
    protected function createCampaign($messageId, $message, $address, $params = [])
    {
        $params['message_id'] = $messageId;

        if ($startTime = $message->getStartTime()) {
            $params['start_time'] = $startTime;
        }

        if (count($address) < 100000) {
            $params['contacts'] = implode(',', $address);
        } else {
            $path = Yii::getAlias(strtr($this->contacts_path, ['{message_id}' => $messageId]));
            $url = Yii::getAlias(strtr($this->contacts_url, ['{message_id}' => $messageId]));

            @mkdir(dirname($path), 0777, true);
            $result = @file_put_contents($path, implode("\n", $address));

            if ($result === false) {
                return false;
            }

            $params['contacts_url'] = Url::to('/' . $url, true);
        }

        return $this->callApi('createCampaign', $params);
    }

    /**
     * @param string $name
     * @param array $params
     * @return \stdClass|false
     */
    public function callApi($name, $params = [])
    {
        $this->setErrors([]);

        $result = $this->api->$name($params);

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
}
