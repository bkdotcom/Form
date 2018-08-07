<?php

namespace bdk\Form;

use bdk\ArrayUtil;
use bdk\Email;
use bdk\FileIo;
use bdk\Html;

/**
 * Default completion methods
 */
class Complete
{

    private $form;
    private $emailHeaders;
    private $collected = array();

    /**
     * Constructor
     *
     * @param object $form form instance
     */
    public function __construct($form)
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->form = $form;
    }

    /**
     * Complete form: email, log, or other
     *
     * @return mixed
     */
    public function complete()
    {
        $cfg = $this->form->cfg;
        $return = true;
        if ($cfg['onComplete'] == 'email') {
            $return = $this->email();
        } elseif ($cfg['onComplete'] == 'log') {
            $return = $this->log();
        } elseif (\is_callable($cfg['onComplete'])) {
            $return = \call_user_func($cfg['onComplete'], $this->form);
        }
        return $return;
    }

    /**
     * Email form results
     *
     * @return boolean
     */
    public function email()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg = $this->form->cfg;
        // $persist = &$this->persist;
        $this->emailHeaders = $cfg['email']['headers'];
        $this->debug->log('emailHeaders', $this->emailHeaders);
        $emailBody = '';
        $this->collected = array(
            'email' => '',
            'name'  => '',
        );
        $dbWas = $this->debug->log('collect', false);
        $pages = $this->form->persist->get('pages');
        foreach ($pages as $i => $page) {
            $this->form->setCurrentFields($i);
            if (\count($pages) > 1) {
                $emailBody .= '*** '.\str_replace('_', ' ', $page['name']).' ***'."\n\n";
            }
            $emailBody .= $this->emailGetPage();
        }
        $this->debug->log('collect', $dbWas);
        if (!$cfg['email']['inclMetaInfo']) {
            $emailBody = $this->emailGetMetaInfo()."\n"
                .$emailBody;
        }
        $this->emailHeaders['Body'] = $emailBody;
        if (empty($this->emailHeaders['Reply-To']) && !empty($this->collected['email'])) {
            $this->emailHeaders['Reply-To'] = $this->collected['name'].'<'.$this->collected['email'].'>';
        }
        $email = new Email();
        $return = $email->send($this->emailHeaders);
        $this->debug->groupEnd();
        return $return;
    }

    /**
     * [emailGetPage description]
     *
     * @return string
     */
    private function emailGetPage()
    {
        $string .= "\n";
        $typesSkip = array('button','image','reset','submit','newPage');
        $namesMeta = array('screen_width','screen_height','screen_colorDepth');
        foreach ($this->form->currentFields as $field) {
            $props = $field->props;
            $name = $props['attribs']['name'];
            $type = $props['attribs']['type'];
            $label = \is_int($name) && !empty($field['label'])
                ? $field['label']
                : \ucwords(\strtr($name, '_', ' '));
            $value = $field->val();
            if (\in_array($type, $typesSkip)) {
                continue;
            } elseif (\in_array($name, $namesMeta)) {
                $this->collected[$name] = $value;
            } elseif ($type == 'file' && !empty($value)) {
                $this->emailHeaders['attachments'][] = array(
                    'content_type'  => $value['type'],
                    'filename'      => $value['name'],
                    'file'          => $value['tmp_name'],
                );
                $value = $value['name'];
            } elseif (\is_array($value)) {  // checkbox, and multi-select
                $value = \array_values($value);
                $value = \count($value) > 1
                    ? "\n     ".\implode("\n".\str_repeat(' ', 5), $value)
                    : $value[0];
            } elseif ($type == 'textarea' && !empty($value) || \strpos($value, "\n")) {
                $value = "\n     ".\implode("\n".\str_repeat(' ', 5), \explode("\n", $value));
            }
            if ($props['userName'] && !empty($value)) {
                $this->collected['name'] .= $value.' ';
            } elseif ($props['userEmail'] && empty($this->collected['email'])) {
                $this->collected['email'] = $value;
            }
            $string .= $label.': '.$value."\n";
        }
        return $string;
    }

    /**
     * Build Meta Info
     *
     * @return string
     */
    private function emailGetMetaInfo()
    {
        $cfg = $this->from->cfg;
        $meta = array(
            'Submitted on '.\date('F j, Y, g:ia'),  // March 10, 2001, 5:16pm
            'URL' => Html::getSelfUrl(array(), array('fullUrl'=>true,'chars'=>false)),
            !empty($_COOKIE['referer'])
                ? 'Came to '.$_SERVER['HTTP_HOST'].' via: '.$_COOKIE['referer']
                : null,
            'Form processor ver' => $cfg['version'],
            'User Agent' => !empty($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : 'unknown',
            'User\'s IP address' => $_SERVER['REMOTE_ADDR'],
            'Session ID' => \session_id(),
        );
        $metaCollect = $this->collected;    // 'screen_width','screen_height','screen_colorDepth'
        unset($metaCollect['name']);
        unset($metaCollect['email']);
        $meta = \array_merge($meta, $metaCollect, $cfg['email']['addMetaInfo']);
        $str = '';
        foreach ($meta as $k => $v) {
            if (\is_int($k) && empty($v)) {
                continue;
            }
            $str = $k.': '.$v."\n";
        }
        return $str;
    }

    /**
     * Log to form values to file
     *
     * @return boolean
     */
    public function log()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->columns = array(
            array('key' => 'Date', 'value' => \date('F j, Y')),
            array('key' => 'Time', 'value' => \date('g:ia')),
            array('key' => 'IP Address', 'value' => $_SERVER['REMOTE_ADDR']),
        );
        $typesSkip = array('button','image','reset','submit');
        $pages = $this->logGetAllPages();
        foreach (\array_keys($pages) as $i) {
            $this->setCurrentFields($i);
            foreach ($this->form->currentFields as $field) {
                $props = $field->props;
                $type = $props['attribs']['type'];
                $name = $props['attribs']['name'];
                if (\in_array($type, $typesSkip)) {
                    continue;           // not interested in these types o fields
                }
                $label = (\is_int($name) && !empty($props['label']))
                    ? $props['label']
                    : \ucwords(\strtr($name, '_', ' '));
                $value = $field->val();
                // do not use $field['return_array']... we're wanting a column for every possible value
                if (\in_array($type, array('checkbox','select','radio'))) {
                    if (\is_string($value)) {
                        $value = array($value);
                    }
                    foreach ($props['options'] as $opt) {
                        $this->logAddColumn($opt['value'], \in_array($opt['value'], $value) ? 1 : '');
                    }
                } else {
                    $value = \strtr($value, array("\n"=>' ',"\r"=>''));
                    $this->logAddColumn($label, $value);
                }
            }
        }
        $return = $this->logWriteFile();
        return $return;
    }

    /**
     * Add column/value
     *
     * @param string $key   string
     * @param string $value value
     *
     * @return void
     */
    private function logAddColumn($key, $value)
    {
        $this->columns[] = array(
            'key' => $key,
            'value' => $value,
        );
    }

    private function logGetAllPages()
    {
        /*
        persist pages only has the pages that the user saw
        need to insert the other pages into this structure in the order they appear in form array
        */
        $cfg = $this->form->cfg;
        $pages = $this->form->persist->get('pages');
        $allPageNames = \array_keys($cfg['pages']);
        $this->debug->log('allPageNames', $allPageNames);
        $i = 0;
        foreach ($allPageNames as $name) {
            if (isset($pages[$i]) && $name === $pages[$i]['name']) {
                continue;
            }
            $this->debug->log($name.' != $persist[pages]['.$i.'][name]');
            $names = \array_column($pages, 'name');
            $found = \array_keys($names, $name);
            if ($found) {
                $this->debug->log('moving');
                // not thuroughly tested !!
                $this->debug->log('found '.$name.' in pages', $found);
                foreach ($found as $i2) {
                    $page = $pages[$i2];
                    unset($pages[$i2]);
                    \array_splice($pages, $i, 0, $page);
                    $i++;
                }
                // $i += \count($found)-1;
            } else {
                $this->debug->log('inserting');
                // \array_splice($persist['forms'], $i, 0, $k);
                // \array_splice($persist['completed'], $i, 0, array(array()));
                \array_splice($pages, $i, 0, array(
                    'name' => $name,
                    'values' => array(),
                ));
                $i++;
            }
        }
        return $pages;
    }

    /**
     * Write log to file
     *
     * @return boolean
     */
    private function logWriteFile()
    {
        $cfg = $this->form->cfg;
        $file = $cfg['logFile'];
        $keys = \array_column($this->columns, 'key');
        $values = \array_column($this->columns, 'values');
        $appendFile = false;
        $keyCounts = array();       // for indexing keys of same name
        foreach ($keys as $k => $key) {
            $key = \trim(\strtolower($key));
            if (!isset($keyCounts[$key])) {
                $keyCounts[$key] = 1;
            } else {
                $keyCounts[$key]++;
                $key .= ' '.$keyCounts[$key];
            }
            $keys[$k] = $key;
        }
        if (\file_exists($file)) {
            $this->debug->log('log exists');
            // check that the keys in file match the data we're writing
            $handle = \fopen($file, 'rb');
            $keysExisting = \fgetcsv($handle, 4096, $cfg['logDelimitter']);
            \fclose($handle);
            if ($keysExisting === $keys) {
                $appendFile = true;
            }
        } else {
            $this->debug->log($file.' does not exist');
        }
        if ($appendFile) {
            $appendString = ArrayUtil::implodeDelim($values, $cfg['logDelimitter']);
            $return = FileIo::safeFileAppend($file, $appendString);
        } else {
            $rows = FileIo::readDelimFile($file);
            $rows[] = \array_combine($keys, $values);
            $return = FileIo::writeDelimFile($file, $rows, array(
                'char' => $cfg['log_delimitter']
            ));
        }
        return $return;
    }
}
