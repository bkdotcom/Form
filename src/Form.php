<?php

namespace bdk;

use bdk\ArrayUtil;
use bdk\Str;
use bdk\Form\Alerts;
use bdk\Form\Complete;
use bdk\Form\Control;
use bdk\Form\ControlFactory;
use bdk\Form\Output;
use bdk\Form\Persist;
use bdk\PubSub\Manager as EventManager;

/**
 * Form
 *
 * @property Alerts  $alerts   lazy-loaded Alerts instance
 * @property Persist $persist] lazy-loaded Persist instance
 */
class Form
{

    public $version = '2.0a1';

    protected $cfg = array();
    protected $status = array(
        'additionalPages'   => array(),
        'completed'         => false,
        'currentPageName'   => '',          // string - name of current set of controls
        'error'             => false,
        'idCounts'          => array(),
        // 'invalidControls'=> array(),     // control names
        // 'keyVerified'    => false,
        'multipart'         => false,       // becomes true if there are file-upload controls(s)
        'postMaxExceeded'   => false,
        'submitted'         => false,
    );
    public $currentControls = array();
    public $currentValues = array();
    public $eventManager;

    /**
     * Constructor
     *
     * @param array        $cfg          form configuration
     * @param EventManager $eventManager event manager
     */
    public function __construct($cfg = array(), EventManager $eventManager = null)
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->debug->groupCollapsed(__METHOD__);
        $this->eventManager = $eventManager ?: $this->debug->eventManager;
        $event = $this->eventManager->publish('form.construct', $this, array(
            'cfgDefault' => array(
                // pages                => array        // pass one or the other
                // contnrols            => array        //
                'name'                  => 'myform',
                'buildAlerts'           => true,    // or callable
                'buildOutput'           => true,    // or callable
                'onComplete'            => array($this, 'onComplete'),  // ('email'|'log'|false|null|callable)
                                                            // if returns string, used as output string
                                                            // if returns false, error occured
                'onRedirect'            => array($this, 'onRedirect'),
                'pre'                   => array($this, 'pre'), // callable to be called before controls are prepped
                                                            // if returns false, error occured
                'post'                  => array($this, 'post'),    // callable to be called after form has been submitted, controls prepped/checked
                                                            // if returns false, error occured
                // 'showUndefinedControls'=> false,
                'logFile'               => null,        // used when on_complete = 'log', default is 'name'.csv
                'logDelimitter'         => ',',
                'validate'              => true,        // validate form controls
                'verifyKey'             => true,
                'prg'                   => true,        // post redirect get
                'trashCollect'          => true,        // should older non-current form data be cleaned up
                'trashCollectable'      => true,        // should other forms be allowed to trash-collect this form?
                'trashOnComplete'       => true,
                'persist'               => array(),     // session data associated with form
                'headersCache'          => 'auto',      // true, false, or 'auto'
                'output' => array(
                    'autofocus'     => true,        // will set autofocus attribute on first form element
                    'filepathScript'=> __DIR__.'/js/Form.jquery.js', // false or null to not include
                    'formWrap'      => true,        // <form></form>
                    'inputKey'      => true,        // <input name="_key_" />
                    'reqDepJs'      => true,
                ),
                'email' => array(
                    'headers' => array(             // for action = 'email'
                        'from'  => isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : null,
                        // 'to'
                        // 'subject'
                    ),
                    'inclMetaInfo' => true,         // whether to include "meta" (date,url,ip,etc) info when emailing results
                    'addMetaInfo' => array(),       // additional "meta" info to add to emailed results
                ),
                'attribs' => array(
                    // 'id'     => 'myform',    // will get set to name
                    // 'name'   => 'myform',    // will get set to name
                    'method' => 'post',
                    'class' => 'enhance-submit',
                ),
                'controlDefaults' => array(
                    'flagNonReqInvalid' => true,
                    'idPrefix' => !empty($cfg['name']) ? $cfg['name'] : null,   // will get set to "formName"
                ),
                'controlDefaultsPerType' => array(),
                'messages' => array(
                    'completed'         => null,
                    'completedAlert'    => null,
                    'error'             => 'We apologize for the inconvenience.',
                    'errorAlert'        => 'Error processing your request',
                    'invalidAlert'      => 'Please check your answers.',
                    'unansweredAlert'   => 'Please make sure you answer all required questions.',
                ),
                'services' => $this->getDefaultServices(),
            ),
            'cfgPassed' => ArrayUtil::mergeDeep($this->cfg, $cfg),
        ));
        $this->cfg = ArrayUtil::mergeDeep(
            $event->getValue('cfgDefault'),
            $event->getValue('cfgPassed'),
            array('int_keys'=>'overwrite')
        );
        $this->debug->log('cfg', $this->cfg);
        $this->debug->groupEnd();
    }

    /**
     * Get protected properties
     *
     * @param string $property property name
     *
     * @return mixed
     */
    public function &__get($property)
    {
        $getter = 'get'.\ucfirst($property);
        if (isset($this->cfg['services'][$property])) {
            $val = $this->cfg['services'][$property];
            if (\is_object($val) && \method_exists($val, '__invoke')) {
                $val = $val($this);
            }
            $this->{$property} = $val;
            return $val;
        } elseif (\in_array($property, array('cfg','status'))) {
            return $this->{$property};
        } elseif (\method_exists($this, $getter)) {
            $return = $this->{$getter}();
            return $return;
        } elseif (isset($this->status[$property])) {
            return $this->status[$property];
        } else {
            return $this->{$property};
        }
    }

    /**
     * store asset (ie script/css)
     *
     * @param mixed $asset filepath string or array
     *
     * @return string path to asset
     */
    public function asset($asset)
    {
        if (\is_string($asset)) {
            $hash = \md5($asset);
            $ext = \pathinfo($asset, PATHINFO_EXTENSION);
            $mimetypes = array(
                'bmp'   => 'image/bmp',
                'css'   => 'text/css',
                'gif'   => 'image/gif',
                'jpg'   => 'image/jpeg',
                'js'    => 'text/javascript',
                'png'   => 'image/png',
                'tif'   => 'image/tiff',
            );
            $asset = array(
                'filepath' => $asset,
                'delete' => false,
                'headers' => array(
                    'Content-Type' => isset($mimetypes[$ext]) ? $mimetypes[$ext] : null,
                    'Content-Disposition' => 'inline; filename='.\basename($asset).';',
                ),
            );
        } else {
            $hash = md5($asset['data']);
            $asset = \array_merge(array(
                'data' => null,
                'delete' => true,
                'headers' => array(),
            ), $asset);
        }
        $this->persist->set('global/assets/'.$hash, $asset);
        return '/formAsset/?hash='.$hash;
    }

    public function assetGet($hash)
    {
        $asset = $this->persist->get('global/assets/'.$hash);
        if ($asset['delete'] && !empty($asset['data'])) {
            $this->persist->set('global/assets/'.$hash, null);
        }
        return $asset;
    }

    /**
     * Extend me for custom output, or set cfg['buildAlerts'] to a callable
     *
     * @return string
     */
    public function buildAlerts()
    {
        if (!$this->cfg['buildAlerts']) {
            return '';
        }
        if (\is_callable($this->cfg['buildAlerts'])) {
            return \call_user_func($this->cfg['buildAlerts'], $this->form);
        }
        return $this->alerts->buildAlerts();
    }

    /**
     * Extend me for custom output, or set cfg['buildOutput'] to a callable
     *
     * If nothing is returned, default output will be used
     *
     * @return string
     */
    public function buildOutput()
    {
        $html = '';
        if (\is_callable($this->cfg['buildOutput'])) {
            $html = \call_user_func($this->cfg['buildOutput'], $this->form);
        }
        if (!$html) {
            $html = $this->output->buildOutputDefault();
        }
        return $html;
    }

    /**
     * Get form control
     *
     * @param string $controlName control's name
     *
     * @return Control
     */
    public function getControl($controlName)
    {
        $this->debug->group(__METHOD__, $controlName);
        $control = false;
        if (\preg_match('|^(.*?)[/.](.*)$|', $controlName, $matches)) {
            $pageName = $matches[1];
            $controlName = $matches[2];
        } else {
            $pageName = $this->status['currentPageName'];
        }
        if ($pageName == $this->status['currentPageName'] && isset($this->currentControls[$controlName])) {
            $control = &$this->currentControls[$controlName];
            if (!\is_object($control)) {
                $control['pageI'] = $this->persist->get('i');
                $this->currentControls[$controlName] = $this->buildControl($control, $controlName);
            }
        } elseif (isset($this->cfg['pages'][$pageName])) {
            $pages = $this->persist->get('pages');
            foreach ($pages as $pageI => $page) {
                if ($page['name'] != $pageName) {
                    continue;
                }
                foreach ($this->cfg['pages'][$pageName] as $k => $controlProps) {
                    if ($k === $controlName
                        || isset($controlProps['attribs']['name']) && $controlProps['attribs']['name'] == $controlName
                        || isset($controlProps['name']) && $controlProps['name'] == $controlName
                    ) {
                        $this->debug->info('found control');
                        $controlProps['pageI'] = $pageI;
                        $control = $this->buildControl($controlProps, $k);
                        $val = $this->getValue($controlName, $pageI);
                        $control->val($val, false);
                        break 2;
                    }
                }
                break;
            }
        }
        $this->debug->groupEnd();
        return $control;
    }

    /**
     * Get list of invalid controls
     *
     * @return Control[]
     */
    public function getInvalidControls()
    {
        $invalidControls = array();
        foreach ($this->currentControls as $control) {
            if (!$control->isValid) {
                $invalidControls[] = $control;
            }
        }
        return $invalidControls;
    }

    /**
     * Get value
     *
     * @param string  $key   key/name
     * @param integer $pageI page index
     *
     * @return mixed
     */
    public function getValue($key, $pageI = null)
    {
        $this->debug->groupCollapsed(__METHOD__, $key, $pageI);
        if (\preg_match('#(.+)[\./](.+)$#', $key, $matches)) {
            $pageName = $matches[1];
            $key = $matches[2];
            $pages = $this->persist->get('pages');
            foreach ($pages as $i => $pageData) {
                if ($pageData['name'] == $pageName) {
                    $pageI = $i;
                    break;
                }
            }
        }
        if ($pageI === null) {
            $pageI = $this->persist->get('i');
        }
        $val = $this->persist->get('pages/'.$pageI.'/values/'.$key);
        $this->debug->groupEnd($val);
        return $val;
    }

    /**
     * Output form
     *
     * @return string
     */
    public function output()
    {
        return $this->output->build();
    }

    /**
     * Process the form
     *
     * @return void
     */
    public function process()
    {
        $this->debug->groupCollapsed(__METHOD__);
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'HEAD') {
            $this->debug->warn('head request - do not process form');
            $this->debug->groupEnd();
            return;
        }
        $this->alerts->clear();
        $this->configNormalize();
        $this->initPersist();
        $this->status['submitted'] = $this->persist->get('submitted');
        $this->storeValues();
        $this->redirectPost();
        $this->setCurrentControls();
        // $this->debug->log('persist', $this->persist);
        // $this->debug->log('status', $this->status);
        $headers = $this->getHeaders();
        $this->processSubmit();
        $this->trashCollect();
        if (!\headers_sent()) {
            $this->debug->log('headers', $headers);
            foreach ($headers as $header) {
                \header($header);
            }
        }
        $this->debug->groupEnd();
        return;
    }

    /**
     * Set configuration value(s)
     *
     * @param string|array $path key or key-=value array
     * @param mixed        $val  value
     *
     * @return void
     */
    public function setCfg($path, $val = null)
    {
        if (\is_array($path)) {
            $cfgPassed = $path;
        } else {
            $cfgPassed = array();
            ArrayUtil::path($cfgPassed, $path, $val);
        }
        $this->cfg = ArrayUtil::mergeDeep($this->cfg, $cfgPassed);
    }

    /**
     * Set current "page"
     *
     * @param integer $i index of forms/controls to use
     *
     * @return void
     */
    public function setCurrentControls($i = null)
    {
        $this->debug->group(__METHOD__, $i);
        $cfg        = &$this->cfg;
        $status     = &$this->status;
        if (isset($i)) {
            $this->status['submitted'] = false;
            $this->persist->set('i', $i);
        }
        $this->resetStatus();
        // $this->debug->warn('currentPageName', $status['currentPageName']);
        // $this->debug->log('persist', $this->persist);
        // $this->debug->info('status', $status);
        // $this->debug->log('cfg', $cfg);
        $this->currentControls = isset($cfg['pages'][ $status['currentPageName'] ])
            ? $cfg['pages'][ $status['currentPageName'] ]
            : array();
        $this->debug->log('count(currentControls)', \count($this->currentControls));
        $this->currentValues = $this->persist->get('currentPage.values');
        // $this->debug->warn('currentValues', $this->currentValues);
        if (\is_callable($cfg['pre'])) {
            $return = \call_user_func($cfg['pre'], $this);
            if ($return === false) {
                $status['error'] = 'pre error';
            }
        }
        $this->buildControls();
        $this->debug->groupEnd();
        return;
    }

    public function setStatus($key, $val = null)
    {
        if (\is_array($key)) {
            $this->status = \array_merge($this->status, $key);
        } else {
            $this->status[$key] = $val;
        }
    }

    /**
     * Set/Store value
     *
     * @param string       $key   key/name
     * @param string|array $value value
     * @param integer      $pageI page index
     *
     * @return void
     */
    public function setValue($key, $value, $pageI = null)
    {
        $this->debug->group(__METHOD__);
        if ($pageI === null) {
            $pageI = $this->persist->get('i');
        }
        $this->persist->set('pages/'.$pageI.'/values/'.$key, $value);
        $this->debug->groupEnd();
        return;
    }

    /**
     * [addRemovePages description]
     *
     * @return void
     */
    private function addRemovePages()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $pages = $this->persist->get('pages');
        $iCur = $this->persist->get('i');
        $currentPage = &$pages[ $iCur ];
        $pagesAdd = array();
        $pagesNext = array();
        $pagesEnd = array();
        /*
        currentPage[addPages] is a list of currentPage index/keys
        step1:  determine what pageNames these correspond to
        */
        $currentAddPagesNames = array();
        foreach ($currentPage['addPages'] as $i) {
            $currentAddPagesNames[] = $pages[ $i ]['name'];
        }
        $isRevisit = !empty($currentPage['addPages']);
        foreach ($this->status['additionalPages'] as $control) {
            $pageName = $control->attribs['value'];
            $pagesAdd[] = $pageName;
            // insert default = next
            if ($isRevisit && \in_array($pageName, $currentAddPagesNames)) {
                $this->debug->warn('already inserted', $pageName);
                continue;
            }
            if ($control->insert === null || $control->insert == 'next') {
                $pagesNext[] = $pageName;
            } else {
                $pagesEnd[] = $pageName;
            }
        }
        $pagesRem = \array_diff($currentAddPagesNames, $pagesAdd);
        $this->debug->log('pagesRem', '['.\implode(', ', $pagesRem).']');
        $this->debug->log('pagesNext', '['.\implode(', ', $pagesNext).']');
        $this->debug->log('pagesEnd', '['.\implode(', ', $pagesEnd).']');
        if ($pagesRem) {
            $this->pagesRemove($pagesRem);
        }
        if ($pagesNext) {
            $this->pagesAddNext($pagesNext);
        }
        if ($pagesEnd) {
            $this->pagesAddEnd($pagesEnd);
        }
        $this->debug->groupEnd();
        return;
    }

    /**
     * Build control object
     *
     * @param array  $controlProps control properties
     * @param string $nameDefault  default name
     *
     * @return object
     */
    protected function buildControl($controlProps, $nameDefault = null)
    {
        $this->debug->group(__METHOD__, $nameDefault);
        if (!isset($controlProps['attribs']['name']) && !isset($controlProps['name'])) {
            $controlProps['attribs']['name'] = $nameDefault;
        }
        if (!empty($controlProps['newPage'])) {
            $controlProps['attribs']['type'] = 'newPage';
            $controlProps['attribs']['value'] = $controlProps['newPage'];
            unset($controlProps['newPage']);
        }
        $control = $this->controlFactory->build($controlProps);
        if ($this->status['submitted'] && $control->attribs['type'] != 'newPage') {
            $pageI = $this->persist->get('i');
            $value = $this->persist->get('pages/'.$pageI.'/values/'.$control->attribs['name']);
            $control->val($value, false);
        }
        $this->debug->groupEnd();
        return $control;
    }

    /**
     * Build current controls
     *
     * @return void
     */
    protected function buildControls()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $status = &$this->status;
        $persist = $this->persist;
        $pageI = $this->persist->get('i');
        $submitControlCount = 0;
        $keys = \array_keys($this->currentControls);
        foreach ($keys as $k) {
            $control = $this->currentControls[$k];
            unset($this->currentControls[$k]);
            if (!\is_object($control)) {
                $control = $this->buildControl($control, $k);
            }
            $control->pageI = $pageI;
            if ($control->attribs['type'] == 'newPage') {
                $this->debug->info('possible new page', $control->attribs['value']);
                if ($control->isRequired()) {
                    $this->debug->log('adding new page', $control->attribs['value']);
                    $status['additionalPages'][] = $control;
                }
                continue;
            } elseif ($control->attribs['type'] == 'submit') {
                $submitControlCount++;
            } elseif ($control->attribs['type'] == 'file') {
                $status['multipart'] = true;
            }
            $keyBase = \is_int($k)
                ? $control->attribs['name']
                : $k;
            $k = $keyBase;
            $i = 1;
            while (isset($this->currentControls[$k])) {
                $k = $keyBase.'_'.$i;
                $i++;
            }
            $this->currentControls[$k] = $control;
        }
        if ($submitControlCount < 1) {
            $this->debug->info('submit control not set');
            $controlArray = array(
                'type'  => 'submit',
                'label' => !empty($status['additionalPages']) || $persist->pageCount() > $persist->pageCount(true)+1
                            ? 'Continue'
                            : 'Submit',
                'attribs' => array('class' => array('btn btn-primary', 'replace')),
                'tagOnly' => true,
            );
            $this->currentControls['submit'] = $this->controlFactory->build($controlArray);
            $this->debug->log('array_keys(currentControls)', \array_keys($this->currentControls));
        }
        if ($status['multipart']) {
            $this->debug->log('<a target="_blank" href="http://www.php.net/manual/en/ini.php">post_max_size</a> = '.Str::getBytes(\ini_get('post_max_size')));
            $this->debug->log('<a target="_blank" href="http://www.php.net/manual/en/ini.php">upload_max_filesize</a> = '.Str::getBytes(\ini_get('upload_max_filesize')));
        }
        $this->debug->groupEnd();
    }

    /**
     * Normalize config
     *
     * @return void
     */
    private function configNormalize()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg = &$this->cfg;
        if (!isset($cfg['attribs']['id'])) {
            $cfg['attribs']['id'] = 'form-'.\str_replace(' ', '_', $cfg['name']);
        }
        if (!isset($cfg['attribs']['name'])) {
            $cfg['attribs']['name'] = \str_replace(' ', '_', $cfg['name']);
        }
        if (isset($cfg['controls'])) {
            $cfg['pages'] = array(
                $cfg['controls'],
            );
            unset($cfg['controls']);
        } elseif (!isset($cfg['pages'])) {
            $cfg['pages'] = array( array() );
        }
        if ($cfg['onComplete'] == 'log' && empty($cfg['logFile'])) {
            $callerInfo = Php::getCallerInfo();
            $dirname = \dirname($callerInfo['file']);
            $cfg['logFile'] = $dirname.DIRECTORY_SEPARATOR.$cfg['name'].'_log.csv';
        }
        $this->debug->groupEnd();
    }

    protected function getDefaultServices()
    {
        return array(
            'alerts' => function () {
                return new Alerts();
            },
            'controlFactory' => function (Form $form) {
                return new ControlFactory($form, $form->cfg['controlDefaults'], $form->cfg['controlDefaultsPerType']);
            },
            'debug' => function () {
                return \bdk\Debug::getInstance();
            },
            'output' => function ($form) {
                return new Output($form);
            },
            'persist' => function (Form $form) {
                return new Persist(array(
                    'formName'          => $form->cfg['name'],
                    'persist'           => $form->cfg['persist'],
                    'trashCollectable'  => $form->cfg['trashCollectable'],
                    'verifyKey'         => $form->cfg['verifyKey'],
                ));
            }
        );
    }

    /**
     * get headers
     *
     * @return array
     */
    private function getHeaders()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg = &$this->cfg;
        $status = &$this->status;
        $this->debug->log('headersCache', $cfg['headersCache']);
        $headers = array();
        if ($cfg['headersCache'] === 'auto') {
            $iCur = $this->persist->get('i');
            $this->debug->log('iCur', $iCur);
            $cfg['headersCache'] = true;
            if ($iCur == 0 && !$status['submitted']) {
                $this->debug->log('First Page && not submitted');
                $cfg['headersCache'] = false;
            }
            if (isset($_SERVER['HTTP_USER_AGENT']) && \preg_match('/ MSIE 6\.\d;/', $_SERVER['HTTP_USER_AGENT'])) {
                $this->debug->log('IE6: do not cache or: will ALWAYS returned cached content.  side-effects: blank controls on back-button & page has expired message');
                $cfg['headersCache'] = false;
            }
            $this->debug->log('headersCache', $cfg['headersCache']);
        }
        if ($cfg['headersCache']) {
            $headers = array(
                // 'Pragma: public',
                'Cache-Control: private, max-age='.(60*30), // .', must-revalidate',
                'Last-Modified: '.\gmdate('D, d M Y H:i:s \G\M\T'),
                'Expires: '.\gmdate('D, d M Y H:i:s \G\M\T', \time()+60*30),
            );
        } else {
            $headers = array(
                // 'Pragma: no-cache',
                'Cache-Control: private, no-cache, no-store',   // HTTP/1.1
                'Expires: Mon, 26 Jul 1997 05:00:00 GMT',       // Date in the past
            );
        }
        $this->debug->groupEnd();
        return $headers;
    }

    /**
     * Initialize persist object
     *
     * @return void
     */
    private function initPersist()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->persist->initFormData(array(
            'userKey' => isset($_REQUEST['_key_'])
                ? $_REQUEST['_key_']
                : null,
        ));
        if (!$this->persist->pageCount()) {
            $this->debug->info('persist just created... add first page');
            $firstPageName = \key($this->cfg['pages']);
            $this->persist->appendPages(array($firstPageName));
        }
        $this->debug->groupEnd();
    }

    /**
     * Extend me
     *
     * @return string|boolean
     */
    protected function onComplete()
    {
        return true;
    }

    /**
     * Handle redirect
     *
     * @param string $location redirect url
     *
     * @return void
     */
    protected function onRedirect($location)
    {
        $this->debug->warn('onRedirect', $location);
        $this->debug->log('%cRedirect%c location: <a href="%s">%s</a>', 'font-weight:bold;', '', $location, $location);
        \header('Location: '.$location);
        exit;
    }

    /**
     * Add pages to end of form
     *
     * @param array $pagesEnd list of pagenames
     *
     * @return void
     */
    private function pagesAddEnd($pagesEnd = array())
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->persist->appendPages($pagesEnd);
        $this->debug->groupEnd();
    }

    /**
     * Add pages to be seen immediately after current page
     *
     * @param array $pagesNext list of page names
     *
     * @return void
     */
    private function pagesAddNext($pagesNext = array())
    {
        $this->debug->groupCollapsed(__METHOD__);
        $pagesNext = \array_values($pagesNext);
        $pages = $this->persist->get('pages');
        $iCur = $this->persist->get('i');
        $currentPage = $pages[ $iCur ];
        // offset is where we will insert pagesNext
        $offset = $iCur + \count($currentPage['addPages']) + 1;
        foreach ($pages as $k => $page) {
            foreach ($page['addPages'] as $k2 => $i) {
                if ($i >= $offset) {
                    $pages[$k]['addPages'][$k2] += \count($pagesNext);
                }
            }
        }
        foreach ($pagesNext as $i => $pageName) {
            $pages[$k]['addPages'][] = $offset + $i;
            \array_splice($pages, $offset + $i, 0, array(
                array(
                    'name' => $pageName,
                    'completed' => false,
                    'values' => array(),
                    'addPages' => array(),
                )
            ));
        }
        $this->persist->set('pages', $pages);
        $this->debug->groupEnd();
    }

    /**
     * [removePages description]
     *
     * @param array $pagesRem list of page names
     *
     * @return void
     */
    private function pagesRemove($pagesRem = array())
    {
        $this->debug->groupCollapsed(__METHOD__);
        /*
        a) remove from currentPage['addPages']
        b) remove from pages[] and any pages they add, etc
        c) remove from each pages's addPages
        */
        $pages = $this->persist->get('pages');
        $iCur = $this->persist->get('i');
        $persistAddPages = $pages[$iCur]['addPages'];
        // convert pagesRem to a list of indexes
        foreach ($pagesRem as $k => $pageName) {
            foreach ($persistAddPages as $k2 => $iAdd) {
                if ($pages[$iAdd]['name'] == $pageName) {
                    $pagesRem[$k] = $iAdd;
                    unset($persistAddPages[$k2]);
                    break;
                }
            }
        }
        $pages[$iCur]['addPages'] = \array_values($persistAddPages);
        while (!empty($pagesRem)) {
            $iRem = \array_shift($pagesRem);
            $this->debug->log('removing', $iRem, $pages[$iRem]['name']);
            \array_merge($pagesRem, $pages[$iRem]['addPages']);
            unset($pages[$iRem]);
            foreach ($pages as $k => $page) {
                foreach ($page['addPages'] as $k2 => $iPage) {
                    if ($iPage > $iRem) {
                        $pages[$k]['addPages'][$k2]--;
                    }
                }
            }
        }
        $pages = \array_values($pages);
        $this->persist->set('pages', $pages);
        $this->debug->groupEnd();
    }

    /**
     * Extend me
     *
     * @return boolean
     */
    protected function post()
    {
        return true;
    }

    /**
     * Extend me
     *
     * @return boolean
     */
    protected function pre()
    {
        return true;
    }

    /**
     * Process submitted values
     *
     * @return void
     */
    private function processSubmit()
    {
        $this->debug->group(__METHOD__);
        $this->debug->groupUncollapse();
        $cfg = &$this->cfg;
        $status = &$this->status;
        if (!$status['submitted']) {
            $this->debug->groupEnd();
            return;
        }
        $this->validate();
        if (\is_callable($cfg['post'])) {
            $return = \call_user_func($cfg['post'], $this);
            if ($return === false) {
                $status['error'] = true;
            }
            // $this->updateInvalid();
        }
        if ($status['error']) {
            // $this->debug->log('status[error]', $status['error']);
            $this->alerts->add($cfg['messages']['errorAlert']);
        } elseif ($invalidControls = $this->getInvalidControls()) {
            // $this->debug->log('invalidControls', $invalidControls);
            $alert = $cfg['messages']['invalidAlert'];
            foreach ($invalidControls as $control) {
                if (!\strlen($control->attribs['value'])) {
                    $alert = $cfg['messages']['unansweredAlert'];
                    break;
                }
            }
            $this->alerts->add($alert);
        } else {
            $this->debug->info('completed ', $status['currentPageName']);
            $this->persist->set('currentPage.completed', true);
            $this->addRemovePages();
            if ($this->persist->pageCount(true) == $this->persist->pageCount()) {
                $this->debug->info('completed all pages');
                $status['completed'] = true;
                $complete = new Complete($this);
                $return = $complete->complete();
                if ($return === false) {
                    $status['error'] = true;
                } elseif (\is_string($return)) {
                    $cfg['messages']['completed'] = $return;
                }
                if ($status['error']) {
                    $this->alerts->add($this->cfg['messages']['errorAlert']);
                } else {
                    $this->alerts->add($this->cfg['messages']['completedAlert']);
                }
                if ($this->status['completed'] && $cfg['trashOnComplete']) {
                    $this->debug->warn('removing persist data');
                    $this->persist->remove();
                }
            } else {
                $this->debug->info('more pages to go');
                $nextI = $this->persist->get('nextI');
                $this->setCurrentControls($nextI);
            }
        }
        $this->debug->groupEnd();
    }

    /**
     * Stores submitted values in persistence
     * Redirects if submoitted via POST
     *
     * @return void
     * @throws \Exception Rather than exit.
     */
    private function redirectPost()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
            return;
        }
        if (!$this->cfg['prg']) {
            return;
        }
        if (!$this->status['submitted']) {
            return;
        }
        $this->debug->log('redirecting...');
        if (\headers_sent($file, $line)) {
            $this->debug->warn('headers alrady sent from '.$file.' line '.$line);
            return;
        }
        /*
            Subscribe to form.redirect to change location, or stop propagation
        */
        $this->persist->set('PRGState', 'R');
        $event = $this->eventManager->publish(
            'form.redirect',
            $this,
            array(
                'location' => $_SERVER['REQUEST_URI'],
            )
        );
        if (!$event->isPropagationStopped()) {
            \call_user_func($this->cfg['onRedirect'], $event['location']);
        }
    }

    /**
     * Reset form status
     *
     * @return void
     */
    private function resetStatus()
    {
        $this->debug->info('resetStatus');
        $this->status = \array_merge($this->status, array(
            'currentPageName'   => $this->persist->get('currentPage.name'),
            // 'keyVerified'    => false,   // don't reset
            // 'submitted'      => false,   // don't reset
            'completed'         => false,
            'error'             => false,
            'postMaxExceeded'   => false,
            'multipart'         => false,   // multipart form (file controls)?
            'additionalPages'   => array(),
            // 'invalidControls'=> array(),
            'idCounts'          => array(),
        ));
    }

    /**
     * Copies request params to persist for storage
     *
     * @return void
     */
    private function storeValues()
    {
        $this->debug->groupCollapsed(__METHOD__);
        // if (!$this->status['keyVerified']) {
        if (!$this->status['submitted']) {
            // $this->debug->warn('not submitted');
            $this->debug->groupEnd();
            return;
        }
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->debug->warn('POST method');
            if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > Str::getBytes(\ini_get('post_max_size'), true)) {
                $this->debug->warn('post_max_size exceeded');
                $this->alerts->add('Your upload has exceeded the '.Str::getBytes(\ini_get('post_max_size')).' limit');
                $this->status['postMaxExceeded'] = true;
            }
            $values = $_POST;
            foreach ($_FILES as $k => $file) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($file['error'] === UPLOAD_ERR_OK) {
                    // by calling move_uploaded_file, we prevent its automatic deletion
                    \move_uploaded_file($file['tmp_name'], $file['tmp_name']);
                }
                $values[$k] = $file;
                /*
                errors:
                    UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini ('.\ini_get('upload_max_filesize').')',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                */
            }
            $this->status['submitted'] = true;
            $this->persist->set('PRGState', 'P');
            $this->persist->set('currentPage.values', $values);
        } elseif (\strtolower($this->cfg['attribs']['method']) == 'get') {
            $this->debug->warn('storing GET vals');
            $this->persist->set('currentPage.values', $_GET);
        }
        $this->debug->groupEnd();
    }

    /**
     * [trashCollect description]
     *
     * @return void
     */
    private function trashCollect()
    {
        if ($this->cfg['trashCollect']) {
            if (!empty($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '404') {
                // this is a 404 page... likely casued by a missing asset on the "calling" page
                $this->debug->warn('404 - not trash collecting');
                return;
            }
            $this->persist->trashCollect();
        }
    }

    /**
     * Validate form
     *
     * @return boolean
     */
    private function validate()
    {
        if (!$this->cfg['validate']) {
            return true;
        }
        $formIsValid = true;
        foreach ($this->currentControls as $control) {
            $isValid = $control->validate();
            $formIsValid = $formIsValid && $isValid;
        }
        return $formIsValid;
    }
}
