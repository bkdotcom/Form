<?php

namespace bdk\Form;

use bdk\Html;

/**
 * Form output stuph
 */
class Output
{

    private $debug;
    private $form;
    private $keyValue;

    /**
     * Constructor
     *
     * @param object $form form object
     */
    public function __construct($form)
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->form = $form;
    }

    private function getSelfPath()
    {
        $selfPath = \realpath(__FILE__);
        $docRoot = \realpath($_SERVER['DOCUMENT_ROOT']);
        $this->debug->log('selfPath', $selfPath);
        $this->debug->log('docRoot', $docRoot);
        if (\strpos($selfPath, $docRoot) !== 0) {
            $this->debug->log('selfPath not in doc root?!...');
            // convoluted solution to sym_link problem
            $ds = DIRECTORY_SEPARATOR;
            $exploded = \explode($ds, $selfPath);
            $rel = \array_pop($exploded);
            while ($last = \array_pop($exploded)) {
                $rel = $last . $ds . $rel;
                $selfPath = $docRoot . $ds . $rel;
                // $this->debug->log('trying ', $selfPath);
                if (\realpath($selfPath) == __FILE__) {
                    break;
                }
            }
        }
        $selfPath = \str_replace('\\', '/', \substr($selfPath, \strlen($docRoot)));
        return $selfPath;
    }

    /**
     * Output form
     *
     * @return string
     */
    public function build()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $str = '';
        $cfg = $this->form->cfg;
        $status = $this->form->status;
        $strAlerts = $this->form->buildAlerts();
        if ($status['error']) {
            $str = $strAlerts
                . $cfg['messages']['error'];
        } elseif ($status['completed']) {
            $str = $strAlerts
                . $cfg['messages']['completed'];
        } elseif ($cfg['buildOutput']) {
            // $this->debug->warn('have buildOutput');
            $this->outputSetup();
            $str = $this->form->eventManager->publish(
                'form.buildOutput',
                $this->form,
                array(
                    'output' => $strAlerts
                        . $this->buildHiddenControls()
                        . $this->buildOutput(),
                )
            )->getValue('output');
            if ($cfg['output']['formWrap']) {
                $attribs = $cfg['attribs'];
                if (empty($attribs['action'])) {
                    $attribs['action'] = Html::getSelfUrl();
                }
                if ($status['multipart']) {
                    $attribs['enctype'] = 'multipart/form-data';
                }
                if (\strtolower($attribs['method']) == 'get') {
                    $urlParts = \parse_url(\html_entity_decode($attribs['action']));
                    $attribs['action'] = $urlParts['path'];
                }
                #$this->debug->log('form attribs', $attribs);
                $str = '<form' . Html::buildAttribString($attribs) . '>' . "\n"
                    . $str
                    . '</form>' . "\n";
            }
            if ($cfg['output']['filepathScript'] && \file_exists($cfg['output']['filepathScript'])) {
                $str .= '<script>'
                    . \file_get_contents($cfg['output']['filepathScript'])
                    . '</script>';
            }
            if ($cfg['output']['reqDepJs']) {
                $outputScript = new OutputScript($this->form);
                $str .= $outputScript->build() . "\n";
            }
        }
        $this->debug->groupEnd();
        return $str;
    }

    /**
     * Default controls builder
     *
     * @return string html
     */
    public function buildOutput()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->debug->groupUncollapse();
        $html = $this->form->buildOutput();
        if (!$html) {
            $html = $this->buildOutputDefault();
        }
        $this->debug->groupEnd();
        return $html;
    }

    /**
     * Default controls builder
     *
     * @return string html
     */
    public function buildOutputDefault()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->debug->groupUncollapse();
        $str = '';
        foreach ($this->form->currentControls as $control) {
            $str .= $control->build() . "\n";
        }
        $this->debug->groupEnd();
        return $str;
    }

    /**
     * Generate hidden controls not related to inputs
     *
     * @return string
     */
    private function buildHiddenControls()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg = $this->form->cfg;
        $printOpts = &$cfg['output'];
        $hiddenControls = '';
        if ($printOpts['inputKey']) {   //  && $cfg['persist_method'] != 'none'
            $hiddenControls .= '<input type="hidden" name="_key_" value="' . \htmlspecialchars($this->keyValue) . '" />';
        }
        if (\strtolower($cfg['attribs']['method']) == 'get') {
            $this->debug->warn('get method');
            $urlParts = \parse_url(\html_entity_decode($cfg['attribs']['action']));
            // $attribs['action'] = $urlParts['path'];
            if (!empty($urlParts['query'])) {
                \parse_str($urlParts['query'], $params);
                $controlNames = array();
                foreach ($this->form->currentControls as $control) {
                    $controlNames[] = $control->attribs['name'];
                }
                foreach ($params as $k => $v) {
                    if (\in_array($k, $controlNames)) {
                        continue;
                    }
                    $hiddenControls .= '<input type="hidden" name="' . \htmlspecialchars($k) . '" value="' . \htmlspecialchars($v) . '" />' . "\n";
                }
            }
        }
        $this->debug->log('hiddenControls', $hiddenControls);
        $this->debug->groupEnd();
        return $hiddenControls;
    }

    /**
     * [outputSetup description]
     *
     * @return void
     */
    private function outputSetup()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg        = $this->form->cfg;
        $persist    = $this->form->persist;
        $this->keyValue = $persist->get('key') . '_' . $persist->get('i');
        $this->debug->info('keyValue', $this->keyValue);
        if ($cfg['output']['autofocus']) {
            $autofocusControl = null;
            $invalidControls = $this->form->getInvalidControls();
            if ($invalidControls) {
                $this->debug->warn('autofocusing invalid');
                $autofocusControl = \current($invalidControls);
            } else {
                foreach ($this->form->currentControls as $k => $control) {
                    // $this->debug->log('control->attribs', $control->attribs);
                    $autofocusable = \in_array($control->tagname, array('input','textarea','select'))
                        && !\in_array($control->attribs['type'], array('button','hidden','reset','submit'));
                    if (isset($control->attribs['autofocus'])) {
                        if ($control->attribs['autofocus']) {
                            $this->debug->log('autofocus attrib explicitly set for', $k);
                            $autofocusControl = $control;
                            break;
                        }
                    } elseif (!$autofocusControl && $autofocusable) {
                        $autofocusControl = $control;
                        // don't break... may find a control that's explicitly autofocused
                    }
                }
            }
            if ($autofocusControl) {
                $autofocusControl->focus();
            }
        }
        $this->debug->groupEnd();
    }
}
