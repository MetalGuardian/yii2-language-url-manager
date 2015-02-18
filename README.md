Yii2 language url manager
==========================
Extension manage urls with language in it

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist metalguardian/yii2-language-url-manager "*"
```

or add

```
"metalguardian/yii2-language-url-manager": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your config by  :

```php
    'urlManager' => [
        'class' => '\metalguardian\language\UrlManager',
        'languages' => ['ua' => 'uk', 'en', 'ru'],
        'rules' => [
            '<module>/<controller>/<action>' => '<module>/<controller>/<action>',
            '<controller>/<action>' => '<controller>/<action>',
            '' => 'site/index',
        ],
    ],
```
