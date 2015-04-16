<?php
/**
 * MainTest.php
 * @author Revin Roman http://phptime.ru
 */

namespace unit\url;

use metalguardian\language\UrlManager;
use unit\TestCase;
use yii\web\Request;

/**
 * Class MainTest
 * @package rmrevin\yii\fontawesome\tests\unit\fontawesome
 */
class MainTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $config = $this->loadConfig();
        $this->mockWebApplication($config);
        \Yii::$app->get('realCache')->flush();
    }

    public function testLanguagesNotSet()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::languages have to contains at least 1 item.');
        new UrlManager();
    }

    public function testLanguagesEmpty()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::languages have to contains at least 1 item.');
        new UrlManager([
            'languages' => [],
        ]);
    }

    public function testLanguagesDoNotContainDefaultLanguage()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::defaultLanguage have to be exist in UrlManager::languages.');
        new UrlManager([
            'languages' => ['ru'],
        ]);
    }

    public function testEnablePrettyUrlSetTrue()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::enablePrettyUrl need to be true for using language url manager.');
        new UrlManager([
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'enablePrettyUrl' => false,
        ]);
    }

    public function testRulesNotSpecified()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::rules required to be specified.');
        new UrlManager([
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
    }

    public function testLanguagesNotArray()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::languages have to be array.');
        new UrlManager([
            'languages' => 'ru, ua, en',
        ]);
    }

    public function testLanguagesNotArrayClosure()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'UrlManager::languages have to be array.');
        new UrlManager([
            'languages' => function () {
                return 'ru, en, ua';
            },
        ]);
    }

    public function testLanguageClosure()
    {
        new UrlManager([
            'languages' => function () {
                return ['en', 'ua' => 'uk'];
            },
            'rules' => ['' => 'site/index'],
            'showDefault' => true,
        ]);
    }

    public function testDefaultLanguageClosure()
    {
        new UrlManager([
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
            'defaultLanguage' => function () {
                return 'en';
            },
        ]);
    }

    public function testGetFromCache()
    {
        new UrlManager([
            'showScriptName' => false,
            'cache' => 'realCache',
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                'post/view' => 'post/view',
            ],
        ]);
        $manager = new UrlManager([
            'showScriptName' => false,
            'cache' => 'realCache',
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                'post/view' => 'post/view',
            ],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/en/post/view?id=1&title=sample+post', $url);

        $manager = new UrlManager([
            'showScriptName' => false,
            'cache' => 'realCache',
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru', 'it'],
            'defaultLanguage' => 'it',
            'rules' => [
                'post/view' => 'post/view',
            ],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/it/post/view?id=1&title=sample+post', $url);
    }

    public function testCreateUrlWithVerbs()
    {
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                    'POST post/<id>/<title>' => 'post/view',
                    'DELETE post/<id>/<title>' => 'post/view',
                    'PUT,PATCH post/<id>/<title>' => 'post/view',
                    'GET,HEAD,OPTIONS blog/<id>/<title>' => 'blog/view',
            ],
            'showScriptName' => false,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/post/view?id=1&title=sample+post', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/post/index?page=1', $url);
        $url = $manager->createUrl(['/post', 'page' => 1]);
        $this->assertEquals('/post?page=1', $url);
        $url = $manager->createUrl(['blog/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/blog/1/sample+post', $url);
    }

    public function testCreateUrlWrongClass()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException', 'URL rule class must implement UrlRuleInterface.');
        new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'class' => '\yii\web\Request',
                ],
            ],
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
    }

    public function testCreateUrlExcluded()
    {
        $manager = new UrlManager([
            'cache' => null,
            'showDefault' => true,
            'showScriptName' => false,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                '' => 'site/index',
            ],
        ]);
        $url = $manager->createUrl(['gii/model']);
        $this->assertEquals('/gii/model', $url);
        $url = $manager->createUrl(['debug/index']);
        $this->assertEquals('/debug/index', $url);
        $url = $manager->createUrl(['/site/index']);
        $this->assertEquals('/en', $url);
    }

    public function testShowDefaultLanguage()
    {
        $request = new Request();
        $_SERVER['SERVER_NAME'] = 'servername';

        $manager = new UrlManager([
            'cache' => null,
            'baseUrl' => '/test/',
            'showScriptName' => true,
            'scriptUrl' => '/test/index.php',
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'defaultLanguage' => 'en',
            'rules' => ['' => 'site/index'],
        ]);
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['/test/index.php/en', []], $result);

        $manager = new UrlManager([
            'cache' => null,
            'showScriptName' => false,
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['/en', []], $result);
    }

    public function testLanguageParsing()
    {
        $request = new Request();

        $manager = new UrlManager([
            'cache' => null,
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'defaultLanguage' => 'en',
            'rules' => [
                '' => 'site/index'
            ],
        ]);
        $request->pathInfo = '/en';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', ['language' => 'en']], $result);
        $this->assertEquals('en', $manager->getCurrent());
        $this->assertEquals('en', $manager->getCurrentLocale());

        $request->pathInfo = '/ua';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', ['language' => 'ua']], $result);
        $this->assertEquals('ua', $manager->getCurrent());
        $this->assertEquals('uk', $manager->getCurrentLocale());
    }

    public function testWrongLanguageParsing()
    {
        $request = new Request();

        $manager = new UrlManager([
            'cache' => null,
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'defaultLanguage' => 'en',
            'enableStrictParsing' => true,
            'rules' => [
                '' => 'site/index'
            ],
        ]);
        $request->pathInfo = '/it';
        $request = $manager->parseRequest($request);
        $this->assertEquals(false, $request);
    }

    public function testDefaultLanguageParsing()
    {
        $this->setExpectedException('\yii\web\NotFoundHttpException', 'You select default language. Remove it from URL.');

        $request = new Request();

        $manager = new UrlManager([
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'defaultLanguage' => 'en',
            'rules' => [
                '' => 'site/index'
            ],
        ]);
        $request->pathInfo = '/en';
        $manager->parseRequest($request);
    }

    public function testCreateLanguageUrl()
    {
        // default setting with '/' as base url
        $manager = new UrlManager([
            'showScriptName' => false,
            'showDefault' => true,
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                'post' => 'post/view',
            ],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/en/post?id=1&title=sample+post', $url);

        $manager = new UrlManager([
            'baseUrl' => '/test/',
            'scriptUrl' => '/test',
            'showDefault' => true,
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                'post' => 'post/view',
            ],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/test/en/post?id=1&title=sample+post', $url);

        $manager = new UrlManager([
            'baseUrl' => '/test',
            'scriptUrl' => '/test/index.php',
            'showDefault' => true,
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => [
                'post' => 'post/view',
            ],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/test/index.php/en/post?id=1&title=sample+post', $url);

        // pretty URL with rules
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'showScriptName' => false,
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/en/post/1/sample+post', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/post/index?page=1&language=en', $url);
        // rules with defaultAction
        $url = $manager->createUrl(['/post', 'page' => 1]);
        $this->assertEquals('/post?page=1&language=en', $url);

        // pretty URL with rules and suffix
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'showScriptName' => false,
            'showDefault' => true,
            'suffix' => '.html',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/en/post/1/sample+post.html', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/post/index.html?page=1&language=en', $url);

        // pretty URL with rules that have host info
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                    'host' => 'http://<language>.example.com',
                ],
            ],
            'defaultLanguage' => 'ru',
            'baseUrl' => '/test',
            'scriptUrl' => '/test',
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('http://ru.example.com/test/post/1/sample+post', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/test/post/index?page=1&language=ru', $url);
    }

    public function testParseLanguageRequest()
    {
        $request = new Request();
        $_SERVER['SERVER_NAME'] = 'servername';
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
                '<module>/<controller>/<action>' => '<module>/<controller>/<action>',
                '<controller>/<action>' => '<controller>/<action>',
            ],
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = 'en/post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample', 'language' => 'en']], $result);
        // trailing slash is significant
        $request->pathInfo = 'en/post/123/this+is+sample/';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['en/post/123/this+is+sample/', []], $result);
        // empty pathinfo
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['/index.php/en', []], $result);
        // normal pathinfo
        $request->pathInfo = 'en/site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', ['language' => 'en']], $result);
        // pathinfo with module
        $request->pathInfo = 'en/module/site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['module/site/index', ['language' => 'en']], $result);
        // pretty URL rules
        $manager = new UrlManager([
            'suffix' => '.html',
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = '/en/post/123/this+is+sample.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample', 'language' => 'en']], $result);
        // matching pathinfo without suffix
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
        // empty pathinfo
        $request->pathInfo = '/en';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
        // normal pathinfo
        $request->pathInfo = 'site/index.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', []], $result);
        // pathinfo without suffix
        $request->pathInfo = 'site/index';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
        // strict parsing
        $manager = new UrlManager([
            'enableStrictParsing' => true,
            'suffix' => '.html',
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'showDefault' => true,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = 'en/post/123/this+is+sample.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample', 'language' => 'en']], $result);
        // unmatching pathinfo
        $request->pathInfo = 'site/index.html';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
    }

    /** Yii2 tests url manager - updated */
    public function testCreateUrl()
    {
        // default setting with '/' as base url
        $manager = new UrlManager([
            'baseUrl' => '/',
            'scriptUrl' => '',
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/post/view?id=1&title=sample+post', $url);

        $manager = new UrlManager([
            'baseUrl' => '/test/',
            'scriptUrl' => '/test',
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/test/post/view?id=1&title=sample+post', $url);

        $manager = new UrlManager([
            'baseUrl' => '/test',
            'scriptUrl' => '/test/index.php',
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/test/index.php/post/view?id=1&title=sample+post', $url);

        // pretty URL with rules
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'baseUrl' => '/',
            'scriptUrl' => '',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/post/1/sample+post', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/post/index?page=1', $url);
        // rules with defaultAction
        $url = $manager->createUrl(['/post', 'page' => 1]);
        $this->assertEquals('/post?page=1', $url);

        // pretty URL with rules and suffix
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'baseUrl' => '/',
            'scriptUrl' => '',
            'suffix' => '.html',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('/post/1/sample+post.html', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/post/index.html?page=1', $url);

        // pretty URL with rules that have host info
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                    'host' => 'http://<lang:en|fr>.example.com',
                ],
            ],
            'baseUrl' => '/test',
            'scriptUrl' => '/test',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post', 'lang' => 'en']);
        $this->assertEquals('http://en.example.com/test/post/1/sample+post', $url);
        $url = $manager->createUrl(['post/index', 'page' => 1]);
        $this->assertEquals('/test/post/index?page=1', $url);
    }
    /**
     * https://github.com/yiisoft/yii2/issues/6717
     */
    public function testCreateUrlWithEmptyPattern()
    {
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                '' => 'front/site/index',
            ],
            'baseUrl' => '/',
            'scriptUrl' => '',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['front/site/index']);
        $this->assertEquals('/', $url);
        $url = $manager->createUrl(['/front/site/index']);
        $this->assertEquals('/', $url);
        $url = $manager->createUrl(['front/site/index', 'page' => 1]);
        $this->assertEquals('/?page=1', $url);
        $url = $manager->createUrl(['/front/site/index', 'page' => 1]);
        $this->assertEquals('/?page=1', $url);
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                '' => '/front/site/index',
            ],
            'baseUrl' => '/',
            'scriptUrl' => '',
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        $url = $manager->createUrl(['front/site/index']);
        $this->assertEquals('/', $url);
        $url = $manager->createUrl(['/front/site/index']);
        $this->assertEquals('/', $url);
        $url = $manager->createUrl(['front/site/index', 'page' => 1]);
        $this->assertEquals('/?page=1', $url);
        $url = $manager->createUrl(['/front/site/index', 'page' => 1]);
        $this->assertEquals('/?page=1', $url);
    }

    public function testCreateAbsoluteUrl()
    {
        $manager = new UrlManager([
            'baseUrl' => '/',
            'scriptUrl' => '',
            'hostInfo' => 'http://www.example.com',
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        $url = $manager->createAbsoluteUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        $this->assertEquals('http://www.example.com/post/view?id=1&title=sample+post', $url);
        $url = $manager->createAbsoluteUrl(['post/view', 'id' => 1, 'title' => 'sample post'], 'https');
        $this->assertEquals('https://www.example.com/post/view?id=1&title=sample+post', $url);
        $manager->hostInfo = 'https://www.example.com';
        $url = $manager->createAbsoluteUrl(['post/view', 'id' => 1, 'title' => 'sample post'], 'http');
        $this->assertEquals('http://www.example.com/post/view?id=1&title=sample+post', $url);
    }

    public function testParseRequest()
    {
        $request = new Request();
        // pretty URL without rules
        $manager = new UrlManager([
            'cache' => null,
            'languages' => ['ua' => 'uk', 'en', 'ru'],
            'rules' => ['' => 'site/index'],
        ]);
        // empty pathinfo
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', []], $result);
        // normal pathinfo
        $request->pathInfo = 'site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', []], $result);
        // pathinfo with module
        $request->pathInfo = 'module/site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['module/site/index', []], $result);
        // pathinfo with trailing slashes
        $request->pathInfo = '/module/site/index/';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['module/site/index/', []], $result);
        // pretty URL rules
        $manager = new UrlManager([
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample']], $result);
        // trailing slash is significant
        $request->pathInfo = 'post/123/this+is+sample/';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/123/this+is+sample/', []], $result);
        // empty pathinfo
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['', []], $result);
        // normal pathinfo
        $request->pathInfo = 'site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', []], $result);
        // pathinfo with module
        $request->pathInfo = 'module/site/index';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['module/site/index', []], $result);
        // pretty URL rules
        $manager = new UrlManager([
            'suffix' => '.html',
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = 'post/123/this+is+sample.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample']], $result);
        // matching pathinfo without suffix
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
        // empty pathinfo
        $request->pathInfo = '';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['', []], $result);
        // normal pathinfo
        $request->pathInfo = 'site/index.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['site/index', []], $result);
        // pathinfo without suffix
        $request->pathInfo = 'site/index';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
        // strict parsing
        $manager = new UrlManager([
            'enableStrictParsing' => true,
            'suffix' => '.html',
            'cache' => null,
            'rules' => [
                [
                    'pattern' => 'post/<id>/<title>',
                    'route' => 'post/view',
                ],
            ],
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo
        $request->pathInfo = 'post/123/this+is+sample.html';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample']], $result);
        // unmatching pathinfo
        $request->pathInfo = 'site/index.html';
        $result = $manager->parseRequest($request);
        $this->assertFalse($result);
    }

    public function testParseRESTRequest()
    {
        $request = new Request();
        // pretty URL rules
        $manager = new UrlManager([
            'showScriptName' => false,
            'cache' => null,
            'rules' => [
                'PUT,POST post/<id>/<title>' => 'post/create',
                'DELETE post/<id>' => 'post/delete',
                'post/<id>/<title>' => 'post/view',
                'POST/GET' => 'post/get',
            ],
            'languages' => ['ua' => 'uk', 'en', 'ru'],
        ]);
        // matching pathinfo GET request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/view', ['id' => '123', 'title' => 'this+is+sample']], $result);
        // matching pathinfo PUT/POST request
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/create', ['id' => '123', 'title' => 'this+is+sample']], $result);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->pathInfo = 'post/123/this+is+sample';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/create', ['id' => '123', 'title' => 'this+is+sample']], $result);
        // no wrong matching
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->pathInfo = 'POST/GET';
        $result = $manager->parseRequest($request);
        $this->assertEquals(['post/get', []], $result);
        // createUrl should ignore REST rules
        $this->mockApplication([
            'components' => [
                'request' => [
                    'hostInfo' => 'http://localhost/',
                    'baseUrl' => '/app'
                ]
            ]
        ], \yii\web\Application::className());
        $this->assertEquals('/app/post/delete?id=123', $manager->createUrl(['post/delete', 'id' => 123]));
        $this->destroyApplication();
        unset($_SERVER['REQUEST_METHOD']);
    }

}
