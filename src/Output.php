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
                $rel = $last.$ds.$rel;
                $selfPath = $docRoot.$ds.$rel;
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
        $this->debug->groupUncollapse();
        $str = '';
        $cfg = $this->form->cfg;
        $status = $this->form->status;
        $strAlerts = \is_callable($cfg['buildAlerts'])
            ? \call_user_func_array($cfg['buildAlerts'], array($this->form))
            : '';
        if ($status['error']) {
            $str = $strAlerts
                .$cfg['messages']['error'];
        } elseif ($status['completed']) {
            $str = $strAlerts
                .$cfg['messages']['completed'];
        } elseif ($cfg['buildOutput']) {
            // $this->debug->warn('have buildOutput');
            $this->outputSetup();
            if (\is_callable($cfg['buildOutput'])) {
                $str = \call_user_func_array($cfg['buildOutput'], array($this->form));
            }
            if (empty($str)) {
                $this->debug->info('buildOutput callback didn\'t generate anything');
                $str = $this->buildOutput($this->form);
            }
            $str = $strAlerts
                .$this->buildHiddenFields()
                .$str;
            $str = $this->form->eventManager->publish(
                'form.buildOutput',
                $this->form,
                array('output'=>$str)
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
                $str = '<form'.Html::buildAttribString($attribs).'>'."\n"
                    .$str
                    .'</form>'."\n";
            }
            if ($cfg['output']['filepathScript'] && \file_exists($cfg['output']['filepathScript'])) {
                $str .= '<script type="text/javascript">'
                    .\file_get_contents($cfg['output']['filepathScript'])
                    .'</script>';
            }
            if ($cfg['output']['reqDepJs']) {
                $outputScript = new OutputScript($this->form);
                $str .= $outputScript->build()."\n";
            }
        }
        $this->debug->groupEnd();
        return $str;
    }

    /**
     * Default fields builder
     *
     * @param Form $form form instance
     *
     * @return string html
     */
    public function buildOutput($form)
    {
        $this->debug->groupCollapsed(__METHOD__);
        $this->debug->groupUncollapse();
        $str = '';
        foreach ($form->currentFields as $field) {
            $str .= $field->build()."\n";
        }
        $this->debug->groupEnd();
        return $str;
    }

    /**
     * Generate hidden fields not related to inputs
     *
     * @return string
     */
    private function buildHiddenFields()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $cfg = $this->form->cfg;
        $printOpts = &$cfg['output'];
        $hiddenFields = '';
        if ($printOpts['inputKey']) {   //  && $cfg['persist_method'] != 'none'
            $hiddenFields .= '<input type="hidden" name="_key_" value="'.\htmlspecialchars($this->keyValue).'" />';
        }
        if (\strtolower($cfg['attribs']['method']) == 'get') {
            $this->debug->warn('get method');
            $urlParts = \parse_url(\html_entity_decode($cfg['attribs']['action']));
            // $attribs['action'] = $urlParts['path'];
            if (!empty($urlParts['query'])) {
                \parse_str($urlParts['query'], $params);
                $fieldNames = array();
                foreach ($this->form->currentFields as $field) {
                    $fieldNames[] = $field->attribs['name'];
                }
                foreach ($params as $k => $v) {
                    if (\in_array($k, $fieldNames)) {
                        continue;
                    }
                    $hiddenFields .= '<input type="hidden" name="'.\htmlspecialchars($k).'" value="'.\htmlspecialchars($v).'" />'."\n";
                }
            }
        }
        $this->debug->log('hiddenFields', $hiddenFields);
        $this->debug->groupEnd();
        return $hiddenFields;
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
        $this->keyValue = $persist->get('key').'_'.$persist->get('i');
        $this->debug->info('keyValue', $this->keyValue);
        if ($cfg['output']['autofocus']) {
            $autofocusField = null;
            $invalidFields = $this->form->getInvalidFields();
            if ($invalidFields) {
                $this->debug->warn('autofocusing invalid');
                $autofocusField = \current($invalidFields);
            } else {
                foreach ($this->form->currentFields as $k => $field) {
                    // $this->debug->log('field->attribs', $field->attribs);
                    $autofocusable = \in_array($field->tagname, array('input','textarea','select'))
                        && !\in_array($field->attribs['type'], array('button','hidden','reset','submit'));
                    if (isset($field->attribs['autofocus'])) {
                        if ($field->attribs['autofocus']) {
                            $this->debug->log('autofocus attrib explicitly set for', $k);
                            $autofocusField = $field;
                            break;
                        }
                    } elseif (!$autofocusField && $autofocusable) {
                        $autofocusField = $field;
                        // don't break... may find a field that's explicitly autofocused
                    }
                }
            }
            if ($autofocusField) {
                $autofocusField->focus();
            }
        }
        $this->debug->groupEnd();
    }
}
