<?php

namespace bdk\Form;

/**
 * Generate javascript to enforce/update requirement dependencies
 */
class OutputScript
{

    private $debug;
    private $form;
    private $regExOther = '#{{([^}\s]+[\.\/][^}\s]+)}}#';
    private $regExThis = '#{{([^}\s\.\/]+)}}#';
    private $trigs = array();

    /**
     * Constructor
     *
     * @param \bdk\Form $form form instance
     */
    public function __construct($form)
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->form = $form;
    }

    /**
     * Build form form-specific javascript
     * Script updates require attribute if req is dependant on other fields
     *
     * @return string
     */
    public function build()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $str = '';
        $strFunctions   = '';
        $strListen      = '';
        $strOnload      = '';
        $trigs = $this->getTriggers();
        $this->debug->log('trigs', $trigs);
        foreach ($trigs as $trigName => $trig) {
            $strFunc = $this->buildOnChangeFunc($trig);
            $strFunctions .= $strFunc."\n";
            $strOnload .= "\t".$trig['funcName']."();\n";
            $trigFields = $this->getFieldsWithName($trigName);
            foreach ($trigFields as $field) {
                // Listen to this field && call func when changed
                $strListen .= $this->buildListen($field, $trig['funcName'])."\n";
            }
        }
        if (!empty($strFunctions) || !empty($strListen)) {
            $str = '<script type="text/javascript">'."\n"
                .'//<![CDATA['."\n"
                .'$(function(){'."\n"
                    .'BDKForm.init("#'.$this->form->cfg['attribs']['id'].'");'."\n"
                    .$strFunctions
                    .$strListen
                    .'$(window).on("focus", function() {'."\n"
                        // .'console.log("window focused... re calculating required");'
                        .$strOnload
                    .'});'."\n"
                .'});'."\n"
                .'//]]>'."\n"
                .'</script>';
        }
        $this->debug->log('str!!', $str);
        $this->debug->groupEnd();
        return $str;
    }

    /**
     * Buiild isRequired script for given trigger field
     *
     * @param string            $strJs     php string to be converted to JS
     * @param \bdk\Form\Control $trigField Control instance
     *
     * @return string javascirpt snippet
     */
    private function buildIsReq($strJs, $trigField)
    {
        $trigName = $trigField->attribs['name'];
        $strJs = \str_replace('{{'.$trigName.'}}', 'val', $strJs);
        $strJs = \preg_replace_callback($this->regExOther, array($this, 'replaceOther'), $strJs);
        $strJs = \preg_replace_callback($this->regExThis, array($this, 'replaceCurrent'), $strJs);
        $strJs = \preg_replace(
            '#in_array\('
                .'(.*?),\s*array\((.*?)\)'
                .'\)#',
            'BDKForm.inArray($1, [$2])',
            $strJs
        );
        $strJs = \str_replace('is_numeric', 'parseFloat', $strJs);
        if ($trigField->returnArray) {
            // not checking for a specific value...
            $strJs = \preg_replace('/(^|[\s!])val(\s|$)/', '$1val.length$2', $strJs);
        }
        return $strJs;
    }

    /**
     * [buildListen description]
     *
     * @param \bdk\Form\Control $field    Control instance
     * @param string            $funcName onChange function name
     *
     * @return string
     */
    private function buildListen($field, $funcName)
    {
        $strJs = '';
        $fieldId = $field->id;
        if (\in_array($field->attribs['type'], array('checkbox','radio'))) {
            foreach (\array_keys($field->props['options']) as $k) {
                // just capture click (not change) because IE 7 & below doesn't fire change until blur
                $strJs = '$("#'.$fieldId.'_'.$k.'").on("click", function(){ '
                    .'document.getElementById("'.$fieldId.'_'.$k.'").blur();'
                    .$funcName.'();'
                .'});';
            }
        } else {
            $event = 'change';
            if ($field->attribs['type'] == 'submit') {
                $event = 'click';
            } elseif ($field->attribs['type'] == 'text') {
                $event = 'keyup';
            }
            $strJs .= '$("#'.$fieldId.'").on("'.$event.'", '.$funcName.');';
        }
        return $strJs;
    }

    /**
     * [trigJsFunction description]
     *
     * @param array $info trigger info
     *
     * @return string
     */
    private function buildOnChangeFunc($info)
    {
        $strFunc = 'function '.$info['funcName'].'() {'."\n";
        $strFunc .= "\t".'var val = BDKForm.getValue("'.$info['selector'].'"),'."\n"
            ."\t\t".'req;'."\n";
        foreach ($info['check'] as $check) {
            $strFunc .= "\t".'req = ( '.$check['isReq'].' );'."\n"
                // ."\t".'console.log("req", "'.$check['selector'].'", req);'."\n"
                ."\t".'BDKForm.setRequired("'.$check['selector'].'", req);'."\n";
        }
        $strFunc .= '}';
        return $strFunc;
    }

    /**
     * Get all form fields having given name
     *
     * @param string $name field name
     *
     * @return array
     */
    private function getFieldsWithName($name)
    {
        $trigFields = array();
        $fields = &$this->form->currentFields;
        if (isset($fields[$name])) {
            $trigFields[] = $fields[$name];
        } else {
            foreach ($fields as $f) {
                if ($f->attribs['name'] == $name) {
                    $trigFields[] = $f;
                }
            }
        }
        return $trigFields;
    }

    /**
     * [getSelector description]
     *
     * @param string|object $field field name or field obj
     *
     * @return string css selector
     */
    private function getSelector($field)
    {
        if (\is_string($field)) {
            if (isset($this->trigs[$field]['selector'])) {
                return $this->trigs[$field]['selector'];
            }
            $fields = $this->getFieldsWithName($field);
            $field = $fields[0];
        }
        if (\in_array($field->attribs['type'], array('checkbox','radio','submit'))) {
            $formId = $this->form->cfg['attribs']['id'];
            $selector = '#'.$formId.' input[name=\"'.$field->attribs['name'].'\"]';
        } else {
            $fieldId = $field->id;
            $selector = '#'.$fieldId;
        }
        return $selector;
    }

    /**
     * [getTriggers description]
     *
     * @return [type] [description]
     */
    private function getTriggers()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $formId = $this->form->cfg['attribs']['id'];
        $fields = &$this->form->currentFields;
        $trigs = array();
        foreach ($fields as $field) {
            if (\is_string($field->attribs['required'])) {
                $this->debug->info($field->attribs['name'].' is required when', $field->attribs['required']);
                $fieldName = $field->attribs['name'];
                $str = $field->attribs['required'];
                \preg_match_all($this->regExThis, $str, $matches);
                $trigNames = \array_unique($matches[1]);
                /*
                    "copy" the require-if string to each of the trigger-names to be evaled when
                    the trigger value changes
                */
                foreach ($trigNames as $trigName) {
                    $this->debug->log('trigName', $trigName);
                    $trigFields = $this->getFieldsWithName($trigName);
                    $trigField = $trigFields[0];
                    if (!isset($trigs[$trigName])) {
                        $trigs[$trigName] = array(
                            'type' => $trigField->attribs['type'],
                            'selector' => $this->getSelector($trigField),
                            'funcName' => \preg_replace('/\W+/', '_', 'changed_'.$formId.'_'.$trigName),
                            'check' => array(),
                        );
                    }
                    $trigs[$trigName]['check'][$fieldName] = array(
                        'selector' => $this->getSelector($field),
                        'isReq' => $this->buildIsReq($str, $trigField),
                    );
                }
            }
        }
        $this->trigs = $trigs;
        $this->debug->groupEnd();
        return $trigs;
    }

    /**
     * replace field tokens occuring in current form page
     *
     * @param array $matches matched strings
     *
     * @return string
     */
    private function replaceCurrent($matches)
    {
        $fieldName = $matches[1];
        return 'BDKForm.getValue("'.$this->getSelector($fieldName).'")';
    }

    /**
     * replace field tokens occring in non-current form page
     *
     * @param array $matches matched strings
     *
     * @return string
     */
    private function replaceOther($matches)
    {
        return '"'.\addslashes($this->form->getValue($matches[1])).'"';
    }
}
