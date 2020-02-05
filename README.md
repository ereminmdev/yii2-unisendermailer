Unisender mailer Extension for Yii 2
====================================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ereminmdev/yii2-unisendermailer
```

or add

```json
"ereminmdev/yii2-unisendermailer": "~1.0"
```

to the require section of your composer.json.

Usage
-----

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'uniMailer' => [
            'class' => 'ereminmdev\yii2\unisendermailer\Mailer',
            'viewPath' => '@common/mail',
            'platform' => 'Yii',
            'apiKey' => '...',
            'listId' => ...,
            'maxSimpleCount' => 0,
            'senderName' => '...',
            'senderEmail' => '...',
            'smsSenderName' => '...',
        ],
    ],
];
```

You can then send an e-mail as follows:

```php
Yii::$app->uniMailer->compose('contact/html')
    ->setTo($form->email)
    ->setSubject($form->subject)
    ->send(); // or ->sendEmail();
```

To send sms:

```php
Yii::$app->uniMailer->compose('contact/html')
    ->setTo($form->phone)
    ->setSubject($form->subject)
    ->setType(Message::TYPE_SMS)
    ->send(); // or ->sendSms();
```

To set sender name and e-mail:

```php
$mailer = Yii::$app->uniMailer;
// for e-mail
$mailer->senderName = 'Sender Name';
$mailer->senderEmail = 'sender@mail.com';
// for sms
$mailer->smsSenderName = 'Sender';
```

For further instructions refer to the [related section in the Yii Definitive Guide](http://www.yiiframework.com/doc-2.0/guide-tutorial-mailing.html).
