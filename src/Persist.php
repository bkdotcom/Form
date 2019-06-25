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
    private $formData = array();    // pointer points to data[formName]
    private $cfg = array(
        'formName'          => null,
        'persist'           => array(), // values passed throughout form, similar to hidden controls, tamper resistant
        'rootKey'           => 'form_persist',
        'trashCollectable'  => true,    // should other forms be allowed to trash-collect this form
        'userKey'           => null,    // user supplied key
        'verifyKey'         => true,
    );

    /**
     * Constructor
     *
     * @param array $cfg configuration
     */
    public function __construct($cfg = array())
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->debug->groupCollapsed(__METHOD__);
        $this->cfg = \array_merge($this->cfg, $cfg);
        Session::start();
        if (!isset($_SESSION[$this->cfg['rootKey']])) {
            $_SESSION[$this->cfg['rootKey']] = array(
                'global' => array(),
            );
        }
        $this->data = &$_SESSION[$this->cfg['rootKey']];
        $this->debug->log('data', $this->data);
        $this->debug->groupEnd();
    }

    /**
     * Magic getter
     *
     * @param string $key property name to get
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic isset
     *
     * @param string $key property name to check
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->formData[$key]) || isset($this->formData['persist'][$key]);
    }

    /**
     * Magic setter
     *
     * @param string $key   property name to set
     * @param mixed  $value property value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
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
        $iCur = $this->formData['i'];
        if (isset($this->formData['pages'][$iCur])) {
            $pageCount = \count($this->formData['pages']);
            $this->formData['pages'][$iCur]['addPages'] = \array_merge(
                $this->formData['pages'][$iCur]['addPages'],
                \range($pageCount, $pageCount+\count($pages)-1)
            );
        }
        foreach ($pages as $pageName) {
            $this->formData['pages'][] = array(
                'name' => $pageName,
                'completed' => false,
                'values' => array(),
                'addPages' => array(),
            );
        }
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
        $path = \preg_split('#[./]#', $path);
        $depth = 0;
        $return = $this->formData;
        while ($path) {
            $key = \array_shift($path);
            if ($depth == 0) {
                if ($key == 'global') {
                    $return = $this->data['global'];
                } elseif ($key == 'currentPage') {
                    $return = $return['pages'][ $return['i'] ];
                } elseif ($key == 'nextI') {
                    foreach ($return['pages'] as $i => $page) {
                        if (!$page['completed']) {
                            return $i;
                        }
                    }
                } elseif (\array_key_exists($key, $return)) {
                    $return = $return[$key];
                } elseif (isset($return['persist'][$key])) {
                    $return = $return['persist'][$key];
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

    public function initFormData($cfg = array())
    {
        $this->cfg = \array_merge($this->cfg, $cfg);
        $formName = $this->cfg['formName'];
        if (isset($this->data[$formName])) {
            $this->debug->info('data['.$formName.'] exists');
            $this->formData = &$this->data[$formName];
            $this->formData['timestamp'] = \microtime(true);
            $this->formData['submitted'] = false;
            if ($this->formData['PRGState'] === 'R') {
                $this->debug->log('submitted... came from redirect');
                $this->formData['PRGState'] = 'G';
                $this->formData['submitted'] = true;
            } else {
                $isSubmitted = true;
                if ($this->cfg['verifyKey']) {
                    $this->debug->log('verifyKey', $this->cfg['userKey']);
                    $isSubmitted = $this->verifyKey($this->cfg['userKey']);
                }
                if ($isSubmitted) {
                    $this->debug->info('isSubmitted');
                    $this->formData['submitted'] = true;
                } else {
                    unset($this->data[$formName]);
                }
            }
        }
        if (!isset($this->data[$formName])) {
            $this->debug->warn('initializing formData');
            $this->data[$formName] = array(
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
                'PRGState' => null,     // P, R, or G
                'submitted' => false,
                'key'       => \md5(\uniqid(\rand(), true)),
                'persist'   => $this->cfg['persist'],
                'timestamp' => \microtime(true),
                'trashCollectable' => $this->cfg['trashCollectable'],
                'ver' => $this->ver,
            );
            $this->formData = &$this->data[$formName];
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
            foreach ($this->formData['pages'] as $page) {
                if ($page['completed']) {
                    $count++;
                }
            }
        } else {
            $count = \count($this->formData['pages']);
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
        $name = $this->formData['name'];
        $this->trashCollectFiles($this->formData);
        unset($this->data[$name]);
        $this->formData = array();
        if (\array_keys($this->data) == array('global')) {
            // empty sans global
            unset($_SESSION[$this->cfg['rootKey']]);
        }
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
        $path = \preg_split('#[./]#', $path);
        $pathI = 0;
        $lastI = \count($path) - 1;
        $pointer = &$this->formData;
        while ($path) {
            $key = \array_shift($path);
            if ($pathI == $lastI && $val === null) {
                // don't set null value.. actually delete
                if (\array_key_exists($key, $pointer)) {
                    unset($pointer[$key]);
                }
                return;
            }
            if ($pathI == 0) {
                // special case for first key
                if ($key == 'global') {
                    $pointer = &$this->data['global'];
                } elseif ($key == 'currentPage') {
                    $pointer = &$pointer['pages'][ $this->data['i'] ];
                } elseif (\array_key_exists($key, $pointer)) {
                    $pointer = &$pointer[$key];
                } else {
                    // unknown first level key -> treat as generit persist val
                    if (!isset($pointer['persist'][$key])) {
                        $pointer['persist'][$key] = null;
                    }
                    $pointer = &$pointer['persist'][$key];
                }
            } elseif (isset($pointer[$key])) {
                $pointer = &$pointer[$key];
            } else {
                $pointer[$key] = null;
                $pointer = &$pointer[$key];
            }
            $pathI++;
        }
        $pointer = $val;
    }

    /**
     * Remove session data
     *
     * @return void
     */
    public function trashCollect()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $tsNow = \microtime(true);
        // $this->debug->log('this->data', $this->data);
        foreach ($this->data as $key => $val) {
            if (!isset($val['ver'])) {
                continue;
            }
            if (isset($this->data['name']) && $key == $this->formData['name']) {
                $this->debug->warn('not trashing current form');
                continue;
            }
            $tsDiff = $tsNow - $val['timestamp'];
            $this->debug->log($key.' created '.\number_format($tsDiff, 4).' sec ago');
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
            if ($val['trashCollectable'] && $tsDiff > 10) {
                $remove = true;
            }
            if ($remove) {
                $this->debug->warn('removing', $key);
                $this->trashCollectFiles($val);
                unset($this->data[$key]);
            }
        }
        if (\array_keys($_SESSION[$this->cfg['rootKey']]) == array('global')) {
            // empty sans global
            unset($_SESSION[$this->cfg['rootKey']]);
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
        } elseif ($userKey) {
            $this->debug->info('keys don\'t match.. perhaps different form', array(
                'userKey' => $userKey,
                'persistKey' => $this->get('key'),
            ));
        } else {
            $this->debug->info('No user key supplied / not submitted');
        }
        return $return;
    }
}
