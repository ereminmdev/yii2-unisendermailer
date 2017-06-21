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

To use this extension,  simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'uniMailer' => [
            'class' => 'ereminmdev\yii2\unisendermailer\Mailer',
            'viewPath' => '@common/mail',
            'apiKey' => '...',
            'listId' => ...,
            'senderName' => '...',
            'senderEmail' => '...',
        ],
    ],
];
```

You can then send an email as follows:

```php
Yii::$app->uniMailer->compose('contact/html')
     ->setFrom('from@domain.com')
     ->setTo($form->email)
     ->setSubject($form->subject)
     ->send();
```

or to send sms:

```php
Yii::$app->uniMailer->compose('contact/html')
     ->setFrom('from@domain.com')
     ->setTo($form->phone)
     ->setSubject($form->subject)
     ->sendSms();
```

For further instructions refer to the [related section in the Yii Definitive Guide](http://www.yiiframework.com/doc-2.0/guide-tutorial-mailing.html).
