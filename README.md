Yii2 language url manager
==========================
Extension manage urls with language in it

[![Latest Stable Version](https://poser.pugx.org/metalguardian/yii2-language-url-manager/v/stable.svg)](https://packagist.org/packages/metalguardian/yii2-language-url-manager) 
[![Total Downloads](https://poser.pugx.org/metalguardian/yii2-language-url-manager/downloads.svg)](https://packagist.org/packages/metalguardian/yii2-language-url-manager) 
[![Latest Unstable Version](https://poser.pugx.org/metalguardian/yii2-language-url-manager/v/unstable.svg)](https://packagist.org/packages/metalguardian/yii2-language-url-manager) 
[![License](https://poser.pugx.org/metalguardian/yii2-language-url-manager/license.svg)](https://packagist.org/packages/metalguardian/yii2-language-url-manager)

Code Status
-----------


[![Build Status](https://travis-ci.org/MetalGuardian/yii2-language-url-manager.svg?branch=master)](https://travis-ci.org/MetalGuardian/yii2-language-url-manager)
[![Dependency Status](https://www.versioneye.com/user/projects/54e517dad1ec573c9900060f/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54e517dad1ec573c9900060f)

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
        ....
        or 
        'languages' => function () {
            return \app\models\Language::find()->select(['code'])->column();
        },
        ....
        'rules' => [ // rules are required
            '<module>/<controller>/<action>' => '<module>/<controller>/<action>',
            '<controller>/<action>' => '<controller>/<action>',
            '' => 'site/index',
        ],
    ],
```

You need to specify rules (required). In other way generated links will not be correct.

Strongly recommended to set `UrlManager::enableStrictParsing` in `true`
