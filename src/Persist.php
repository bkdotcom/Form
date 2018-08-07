<?php

namespace bdk\Form;

use bdk\Session;

/**
 *
 */
class Persist
{

    private $ver = '1.0';
    private $debug;
    private $data = array();
    private $cfg = array(
        'persist'           => array(), // values passed throughout form, similar to hidden fields, tamper resistant
        'trashCollectable'  => true,    // should other forms be allowed to trash-collect this form
    );

    /**
     * Constructor
     *
     * @param string $formName form name
     * @param array  $cfg      configuration
     */
    public function __construct($formName, $cfg = array())
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->debug->groupCollapsed(__METHOD__, $formName);
        $this->cfg = \array_merge($this->cfg, $cfg);
        $this->debug->warn('starting session!');
        Session::start();
        $this->debug->log('SESSION[form_persist]', isset($_SESSION['form_persist']) ? $_SESSION['form_persist'] : null);
        if (isset($_SESSION['form_persist'][$formName])) {
            $this->debug->info('_SESSION[form_persist]['.$formName.'] exists');
            $this->data = &$_SESSION['form_persist'][$formName];
            // $this->debug->log('persist', $_SESSION['form_persist'][$formName]);
            $_SESSION['form_persist'][$formName]['timestamp'] = \microtime(true);
            if ($_SESSION['form_persist'][$formName]['submitted']) {
                $this->debug->log('submitted... came from redirect');
            } elseif (\array_key_exists('userKey', $cfg)) {
                $this->debug->log('userKey', $cfg['userKey']);
                if ($cfg['userKey'] === false) {
                    // don't need to validate key
                    $isSubmitted = true;
                } else {
                    $isSubmitted = $this->verifyKey($cfg['userKey']);
                }
                if ($isSubmitted) {
                    $_SESSION['form_persist'][$formName]['submitted'] = true;
                } else {
                    unset($_SESSION['form_persist'][$formName]);
                }
            } else {
                $this->debug->info('we don\'t yet know if submitted');
            }
        }
        if (!isset($_SESSION['form_persist'][$formName])) {
            $this->debug->warn('initializing persist/session');
            $_SESSION['form_persist'][$formName] = array(
                'name'      => $formName,
                'pages'     => array(
                    /*
                    array(
                        name        => page_name
                        completed   => boolean
                        values      => array()
                        addPages    => array()  // list of page indexes
                    ),
                    ...
                    ...
                    */
                ),
                'i'         => 0,       // index to current page in pages
                'submitted' => false,
                'key'       => \md5(\uniqid(\rand(), true)),
                'persist'   => $this->cfg['persist'],
                'timestamp' => \microtime(true),
                'trashCollectable' => $this->cfg['trashCollectable'],
                'ver' => $this->ver,
            );
        }
        $this->data = &$_SESSION['form_persist'][$formName];
        // $this->debug->log('persist data', $this->data);
        $this->debug->groupEnd();
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * [get description]
     *
     * @param string $path key/path of value to retrieve
     *
     * @return mixed (null if not set)
     */
    public function get($path)
    {
        $return = null;
        $path = \preg_split('#[./]#', $path);
        $depth = 0;
        while ($path) {
            $key = \array_shift($path);
            if ($depth == 0) {
                if ($key == 'currentPage') {
                    $return = $this->data['pages'][ $this->data['i'] ];
                } elseif ($key == 'nextI') {
                    foreach ($this->data['pages'] as $i => $page) {
                        if (!$page['completed']) {
                            return $i;
                        }
                    }
                // } elseif ($key == 'submitted') {
                    // return $this->data['submitted'];
                } elseif (isset($this->data[$key])) {
                    $return = $this->data[$key];
                } elseif (isset($this->data['persist'][$key])) {
                    $return = $this->data['persist'][$key];
                } else {
                    return null;
                }
            } elseif (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
            $depth++;
        }
        return $return;
    }

    /**
     * [set description]
     *
     * @param string $path key/path of values to set
     * @param mixed  $val  values
     *
     * @return void
     */
    public function set($path, $val = null)
    {
        $this->debug->groupCollapsed(__METHOD__, $path);
        $path = \preg_split('#[./]#', $path);
        $depth = 0;
        $pointer = &$this->data;
        while ($path) {
            $key = \array_shift($path);
            if ($depth == 0) {
                if ($key == 'currentPage') {
                    $pointer = &$this->data['pages'][ $this->data['i'] ];
                } elseif (isset($pointer[$key])) {
                    $pointer = &$pointer[$key];
                } else {
                    if (!isset($pointer['persist'][$key])) {
                        $pointer['persist'][$key] = null;
                    }
                    $pointer = &$pointer['persist'][$key];
                }
            } elseif (isset($pointer[$key])) {
                $pointer = &$pointer[$key];
            } else {
                $this->debug->warn('no key', $key);
                $pointer[$key] = null;
                $pointer = &$pointer[$key];
                // $this->debug->groupEnd();
                // return;
            }
            $depth++;
        }
        $pointer = $val;
        $this->debug->groupEnd();
    }

    /**
     * Append additional pages to the end
     *
     * @param array $pages array/list of page names
     *
     * @return void
     */
    public function appendPages($pages = array())
    {
        $iCur = $this->data['i'];
        if (isset($this->data['pages'][$iCur])) {
            $pageCount = \count($this->data['pages']);
            $range = \range($pageCount, $pageCount+\count($pages)-1);
            $this->data['pages'][$iCur]['addPages'] = \array_merge($this->data['pages'][$iCur]['addPages'], $range);
        }
        foreach ($pages as $pageName) {
            $this->data['pages'][] = array(
                'name' => $pageName,
                'completed' => false,
                'values' => array(),
                'addPages' => array(),
            );
        }
    }

    /**
     * [getPageCount description]
     *
     * @param boolean $onlyCompleted count only completed
     *
     * @return integer
     */
    public function pageCount($onlyCompleted = false)
    {
        if ($onlyCompleted) {
            $count = 0;
            foreach ($this->data['pages'] as $page) {
                if ($page['completed']) {
                    $count++;
                }
            }
        } else {
            $count = \count($this->data['pages']);
        }
        return $count;
    }

    /**
     * remove current form's perist data
     *
     * @return void
     */
    public function remove()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $name = $this->data['name'];
        $this->trashCollectFiles($this->data);
        $this->data = array();
        unset($_SESSION['form_persist'][$name]);
        if (empty($_SESSION['form_persist'])) {
            unset($_SESSION['form_persist']);
        }
        $this->debug->groupEnd();
    }

    /**
     * Remove session data
     *
     * @return void
     */
    public function trashCollect()
    {
        $this->debug->groupCollapsed(__METHOD__);
        // $cfg = $this->cfg;
        $tsNow = \microtime(true);
        // $this->debug->log('this->data', $this->data);
        if (isset($_SESSION['form_persist']) && \is_array($_SESSION['form_persist'])) {
            foreach ($_SESSION['form_persist'] as $formName => $data) {
                if (!isset($data['ver'])) {
                    continue;
                }
                $tsDiff = $tsNow - $data['timestamp'];
                $this->debug->log($formName.' created '.\number_format($tsDiff, 4).' sec ago');
                // && ( empty($_SERVER['HTTP_USER_AGENT']) || \strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') )
                /*
                primarily a Safari issue
                <img src="" />, imgNode.setAttribute('src', ''), etc
                will get interepreted as <img src=" {request_uri} " />...
                these requests can be seen in the browser's development console and network requests as
                "Resource interpreted as Image but transferred with MIME type text/html."
                which will effectevely come in as a GET request after the page is loaded.
                this GET request will end up here triggering trash collection!!
                */
                $remove = false;
                if (isset($this->data['name']) && $formName == $this->data['name']) {
                    $this->debug->warn('not trashing current form');
                    continue;
                } elseif ($data['trashCollectable'] && $tsDiff > 10) {
                    $remove = true;
                }
                if ($remove) {
                    $this->debug->warn('removing', $formName);
                    $this->trashCollectFiles($data);
                    unset($_SESSION['form_persist'][$formName]);
                }
            }
        }
        if (empty($_SESSION['form_persist'])) {
            unset($_SESSION['form_persist']);
        }
        $this->debug->groupEnd();
    }

    /**
     * remove temporary upload files
     *
     * @param array $data persist data
     *
     * @return void
     */
    private function trashCollectFiles($data = null)
    {
        $this->debug->groupCollapsed(__METHOD__);
        $data = isset($data)
            ? $data
            : $this->data;
        foreach ($data['pages'] as $info) {
            foreach ($info['values'] as $a) {
                if (\is_array($a) && !empty($a['tmp_name']) && \file_exists($a['tmp_name'])) {
                    $this->debug->log('deleting '.$a['tmp_name']);
                    \unlink($a['tmp_name']);
                }
            }
        }
        $this->debug->groupEnd();
        return;
    }

    /**
     * Validate/Verify user key/token
     *
     * @param string $userKey user's posted key/token
     *
     * @return boolean
     */
    public function verifyKey($userKey)
    {
        $return = false;
        $userI = null;
        if (\preg_match('/^(.+)_(\d+)?$/', $userKey, $matches)) {
            $userKey = $matches[1];
            $userI = (int) $matches[2];
        }
        if ($userKey == $this->get('key')) {
            $this->debug->log('keys match');
            $return = true;
            if (isset($userI)) {
                $this->data['i'] = $userI;
            }
        } else {
            $this->debug->info('keys don\'t match.. perhaps different form', array(
                'userKey' => $userKey,
                'stored key' => $this->get('key'),
            ));
        }
        return $return;
    }
}
