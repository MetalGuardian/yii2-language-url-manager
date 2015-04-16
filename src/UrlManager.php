<?php
/**
 * Author: metal
 * Email: metal
 */

namespace metalguardian\language;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\UrlRule;

/**
 * Class UrlManager
 *
 * @package frontend\components
 */
class UrlManager extends \yii\web\UrlManager
{

    /**
     * Excluded routes
     *
     * @var array
     */
    public $exclude = ['gii', 'debug'];

    /**
     * @inheritdoc
     */
    public $enablePrettyUrl = true;

    /**
     * Available languages
     *
     * ```
     * ['en', 'ru', 'uk']
     * or
     * ['en' => 'en_US', 'ru', 'ua' => 'uk']
     * 'code_in_url' => 'locale'
     * or
     * function () {
     *    return ['en', 'ua'];
     * }
     * ```
     *
     * @var array|\Closure
     */
    public $languages = [];

    /**
     * Default language (code)
     *
     * @var string
     */
    public $defaultLanguage = 'en';

    /**
     * Language query param
     *
     * @var string
     */
    public $languageParam = 'language';

    /**
     * @var array
     */
    protected $languageRules = [];

    /**
     * Show default language in url
     *
     * @var bool
     */
    public $showDefault = false;

    /**
     * Auto generate language rules
     *
     * @var bool
     */
    public $autoLanguageRules = true;

    /**
     * Current language (code)
     *
     * @var
     */
    protected $current;

    /**
     * Additional suffix for cache key, if you use component in different parts of application
     *
     * @var string
     */
    public $cacheKeySuffix;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->enablePrettyUrl) {
            throw new InvalidConfigException(
                'UrlManager::enablePrettyUrl need to be true for using language url manager.'
            );
        }

        $this->fillLanguages();

        if (!is_array($this->languages)) {
            throw new InvalidConfigException('UrlManager::languages have to be array.');
        }

        if (empty($this->languages)) {
            throw new InvalidConfigException('UrlManager::languages have to contains at least 1 item.');
        }

        $this->normalizeLanguages();

        if (!isset($this->languages[$this->defaultLanguage])) {
            throw new InvalidConfigException('UrlManager::defaultLanguage have to be exist in UrlManager::languages.');
        }

        $this->languageRules = $this->rules;

        if (empty($this->languageRules)) {
            throw new InvalidConfigException('UrlManager::rules required to be specified.');
        }

        parent::init();

        if ($this->autoLanguageRules) {
            $this->setUpLanguageUrls();
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function setUpLanguageUrls()
    {
        if ($this->cache instanceof Cache) {
            $cacheKey = __CLASS__ . $this->cacheKeySuffix;
            $hash = md5(json_encode($this->languageRules) . json_encode($this->languages));
            if (($data = $this->cache->get($cacheKey)) !== false && isset($data[1]) && $data[1] === $hash) {
                $this->languageRules = $data[0];
            } else {
                $this->languageRules = $this->buildLanguageRules($this->languageRules);
                $this->cache->set($cacheKey, [$this->languageRules, $hash]);
            }
        } else {
            $this->languageRules = $this->buildLanguageRules($this->languageRules);
        }
        if ($this->showDefault) {
            $this->rules = $this->languageRules;
        } else {
            $this->rules = ArrayHelper::merge($this->languageRules, $this->rules);
        }
    }

    /**
     * @param array $rules
     *
     * @return array
     * @throws InvalidConfigException
     */
    protected function buildLanguageRules($rules)
    {
        $compiledRules = [];
        foreach ($rules as $key => $rule) {
            if (is_string($rule)) {
                $rule = $this->createRuleFromString($rule, $key);
            }
            if (is_array($rule)) {
                $rule = $this->createRuleFromArray($rule);
            }
            $compiledRules[] = $rule;
        }

        return $compiledRules;
    }

    /**
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $params = (array)$params;

        $route = trim($params[0], '/');
        $routeArray = explode('/', $route);
        if (isset($routeArray[0]) && in_array($routeArray[0], $this->exclude, true)) {
            return parent::createUrl($params);
        }

        if (!isset($params[$this->languageParam])) {
            $params[$this->languageParam] = $this->getCurrent();
        }

        if ($params[$this->languageParam] === false || (!$this->showDefault && $this->isCurrentDefault())) {
            unset($params[$this->languageParam]);
        }

        return parent::createUrl($params);
    }

    /**
     * @param $code
     */
    public function setCurrent($code)
    {
        $this->current = $code;
    }

    /**
     * @return string
     */
    public function getCurrent()
    {
        if (!$this->current) {
            $this->current = $this->defaultLanguage;
        }

        return $this->current;
    }

    public function getCurrentLocale()
    {
        return $this->languages[$this->getCurrent()];
    }

    /**
     * @return bool
     */
    public function isCurrentDefault()
    {
        return $this->getCurrent() === $this->defaultLanguage;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $url = $this->checkRedirectToLanguage($request);
        if ($url !== false) {
            Yii::$app->response->redirect($url);
            return [$url, []];
        }

        $request = parent::parseRequest($request);

        if (!$request) {
            return $request;
        }

        if (isset($request[1][$this->languageParam])) {
            $this->setCurrent($request[1][$this->languageParam]);
            if (!$this->showDefault && $this->isCurrentDefault()) {
                throw new NotFoundHttpException(\Yii::t('app', 'You select default language. Remove it from URL.'));
            }
        }

        \Yii::$app->language = $this->getCurrentLocale();

        return $request;
    }

    public function fillLanguages()
    {
        if ($this->languages instanceof \Closure) {
            $this->languages = call_user_func($this->languages);
        }

        if ($this->defaultLanguage instanceof \Closure) {
            $this->defaultLanguage = call_user_func($this->defaultLanguage);
        }
    }

    public function normalizeLanguages()
    {
        $languages = [];
        foreach ($this->languages as $key => $value) {
            if (is_string($key)) {
                $languages[$key] = $value;
            } else {
                $languages[$value] = $value;
            }
        }
        $this->languages = $languages;
    }

    /**
     * @param string $rule
     * @param $key
     *
     * @return array
     */
    protected function createRuleFromString($rule, $key)
    {
        $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';

        $rule = ['route' => $rule];
        if (preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $key, $matches)) {
            $rule['verb'] = explode(',', $matches[1]);
            // rules that do not apply for GET requests should not be use to create urls
            if (!in_array('GET', $rule['verb'], true)) {
                $rule['mode'] = UrlRule::PARSING_ONLY;
            }
            $key = $matches[4];
        }
        $rule['pattern'] = $key;

        return $rule;
    }

    /**
     * @param $rule
     *
     * @return object
     * @throws InvalidConfigException
     */
    protected function createRuleFromArray($rule)
    {
        $languages = implode('|', array_keys($this->languages));

        if (isset($rule['pattern']) &&
            !preg_match("/<{$this->languageParam}:?([^>]+)?>/", $rule['pattern']) &&
            strpos($rule['pattern'], '://') === false &&
            !(isset($rule['host']) && preg_match("/<{$this->languageParam}:?([^>]+)?>/", $rule['host']))
        ) {
            $rule['pattern'] = "<{$this->languageParam}:({$languages})>/" . $rule['pattern'];
        }
        $rule = Yii::createObject(array_merge($this->ruleConfig, $rule));

        return $rule;
    }

    /**
     * @param $request
     *
     * @return array|false
     */
    public function checkRedirectToLanguage($request)
    {
        $pathInfo = trim($request->pathInfo, '/');
        if ($this->showDefault && empty($pathInfo)) {
            if ($this->showScriptName) {
                $before = $this->getScriptUrl() . '/';
            } else {
                $before = $this->getBaseUrl() . '/';
            }

            $url = $before . $this->defaultLanguage;

            return $url;
        }
        return false;
    }
}
