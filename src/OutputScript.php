<?php

namespace bdk\Form;

use bdk\Form\Control;

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
     * Script updates require attribute if req is dependant on other controls
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
            $trigControls = $this->getControlsWithName($trigName);
            foreach ($trigControls as $control) {
                // Listen to this control && call func when changed
                $strListen .= $this->buildListen($control, $trig['funcName'])."\n";
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
     * Buiild isRequired script for given trigger control
     *
     * @param string  $strJs       php string to be converted to JS
     * @param Control $trigControl Control instance
     *
     * @return string javascirpt snippet
     */
    private function buildIsReq($strJs, Control $trigControl)
    {
        $trigName = $trigControl->attribs['name'];
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
        if ($trigControl->returnArray) {
            // not checking for a specific value...
            $strJs = \preg_replace('/(^|[\s!])val(\s|$)/', '$1val.length$2', $strJs);
        }
        return $strJs;
    }

    /**
     * [buildListen description]
     *
     * @param Control $control    Control instance
     * @param string  $funcName onChange function name
     *
     * @return string
     */
    private function buildListen(Control $control, $funcName)
    {
        $strJs = '';
        $controlId = $control->id;
        if (\in_array($control->attribs['type'], array('checkbox','radio'))) {
            foreach (\array_keys($control->props['options']) as $k) {
                // just capture click (not change) because IE 7 & below doesn't fire change until blur
                $strJs = '$("#'.$controlId.'_'.$k.'").on("click", function(){ '
                    .'document.getElementById("'.$controlId.'_'.$k.'").blur();'
                    .$funcName.'();'
                .'});';
            }
        } else {
            $event = 'change';
            if ($control->attribs['type'] == 'submit') {
                $event = 'click';
            } elseif ($control->attribs['type'] == 'text') {
                $event = 'keyup';
            }
            $strJs .= '$("#'.$controlId.'").on("'.$event.'", '.$funcName.');';
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
     * Get all form controls having given name
     *
     * @param string $name control name
     *
     * @return array
     */
    private function getControlsWithName($name)
    {
        $trigControls = array();
        $controls = &$this->form->currentControls;
        if (isset($controls[$name])) {
            $trigControls[] = $controls[$name];
        } else {
            foreach ($controls as $control) {
                if ($control->attribs['name'] == $name) {
                    $trigControls[] = $control;
                }
            }
        }
        return $trigControls;
    }

    /**
     * [getSelector description]
     *
     * @param string|Control $control control name or control instance
     *
     * @return string css selector
     */
    private function getSelector($control)
    {
        if (\is_string($control)) {
            if (isset($this->trigs[$control]['selector'])) {
                return $this->trigs[$control]['selector'];
            }
            $controls = $this->getControlsWithName($control);
            $control = $controls[0];
        }
        if (\in_array($control->attribs['type'], array('checkbox','radio','submit'))) {
            $formId = $this->form->cfg['attribs']['id'];
            $selector = '#'.$formId.' input[name=\"'.$control->attribs['name'].'\"]';
        } else {
            $controlId = $control->id;
            $selector = '#'.$controlId;
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
        $controls = &$this->form->currentControls;
        $trigs = array();
        foreach ($controls as $control) {
            if (\is_string($control->attribs['required'])) {
                $this->debug->info($control->attribs['name'].' is required when', $control->attribs['required']);
                $controlName = $control->attribs['name'];
                $str = $control->attribs['required'];
                \preg_match_all($this->regExThis, $str, $matches);
                $trigNames = \array_unique($matches[1]);
                /*
                    "copy" the require-if string to each of the trigger-names to be evaled when
                    the trigger value changes
                */
                foreach ($trigNames as $trigName) {
                    $this->debug->log('trigName', $trigName);
                    $trigControls = $this->getControlsWithName($trigName);
                    $trigControl = $trigControls[0];
                    if (!isset($trigs[$trigName])) {
                        $trigs[$trigName] = array(
                            'type' => $trigControl->attribs['type'],
                            'selector' => $this->getSelector($trigControl),
                            'funcName' => \preg_replace('/\W+/', '_', 'changed_'.$formId.'_'.$trigName),
                            'check' => array(),
                        );
                    }
                    $trigs[$trigName]['check'][$controlName] = array(
                        'selector' => $this->getSelector($control),
                        'isReq' => $this->buildIsReq($str, $trigControl),
                    );
                }
            }
        }
        $this->trigs = $trigs;
        $this->debug->groupEnd();
        return $trigs;
    }

    /**
     * replace control tokens occuring in current form page
     *
     * @param array $matches matched strings
     *
     * @return string
     */
    private function replaceCurrent($matches)
    {
        $controlName = $matches[1];
        return 'BDKForm.getValue("'.$this->getSelector($controlName).'")';
    }

    /**
     * replace control tokens occring in non-current form page
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
