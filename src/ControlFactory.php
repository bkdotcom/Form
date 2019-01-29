<?php

namespace bdk\Form;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\ControlBuilder;

/**
 * Control Factory
 */
class ControlFactory
{

    public $defaultProps = array(
        'addonAfter' => null,
        'addonBefore' => null,
        'attribs' => array(
            'name' => null,
            'id' => null,
            'value' => null,
            'type' => 'text',
            'required' => false,
            'class' => 'form-control',
        ),
        'attribsContainer' => array(
            'class' => 'form-group',
        ),
        'attribsLabel' => array(
            'class' => 'control-label',
        ),
        'attribsControls' => array(
            'class' => 'controls',
        ),
        'attribsHelpBlock' => array(
            'class' => 'help-block',
        ),
        'definition' => null,   // class that extends Control
            // doBuild
            // doValidate
            // getValFormatted
            // getValRaw
            // setValRaw
        'helpBlock' => '',
        'idPrefix' => null,
        'invalidReason' => null,
        'isValid' => true,
        'label' => '',
        'tagname' => 'input',
        'tagOnly' => false,
        'template' => '<div {{attribsContainer}}>
                <label {{attribsLabel}}>{{label}}</label>
                <div {{attribsControls}}>
                    {{input}}
                    <span {{attribsHelpBlock}}>{{helpBlock}}</span>
                </div>
            </div>',
        'build' => null,            // callable
        'getValFormatted' => null,  // callable
        'getValRaw' => null,        // callable
        'setValRaw' => null,        // callable
        'validate' => null,         // callable
    );

    public $defaultPropsPerType = array(
        'button' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
            'tagname' => 'button',
        ),
        'checkbox' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'checkboxGroup' => 'auto',  // single option returns a scalar, more than one returns an array
            'options' => array(),
            'useFieldset' => 'auto',
            'values' => array(),
        ),
        'date' => array(
            'definition' => 'typeDate',
        ),
        'datetime-local' => array(
            'attribs' => array(
                'class' => 'hide-spinbtns',
                'placeholder' => 'yyyy-mm-ddThh:mm:ss',
            ),
        ),
        'email' => array(
            'definition' => 'typeEmail',
        ),
        'file' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
        ),
        'hidden' => array(
            'tagOnly' => true,
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
        ),
        'newPage' => array(
            'attribs' => array(
                'required' => true,
            ),
        ),
        'number' => array(
            'attribs' => array(
                // 'pattern' => '-?\d+(\.\d+)?(e[-+]?\d+)?',
                'step' => 'any',
            ),
        ),
        'password' => array(
            'attribs' => array(
                'autocapitalize' => 'none',
                'autocomplete' => 'off',        // see also:  "current-password" & "new-password"
                'autocorrect' => 'off',
                // 'data-lpignore'  => true,    // no LastPass icon
            ),
        ),
        'radio' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'options' => array(),
            'useFieldset' => 'auto',
        ),
        'range' => array(
            'attribs' => array(
                // 'pattern' => '-?\d+(\.\d+)?(e[-+]?\d+)?',
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'class' => array(null, 'replace'), // don't want 'form-control', 'input-sm', etc
            ),
        ),
        'reset' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
            'label' => 'Reset',
            'tagname' => 'button',
        ),
        'search' => array(
            'attribs' => array(
                'placeholder' => 'search',
            ),
        ),
        'select' => array(
            'attribs' => array(
                'multiple' => false,
            ),
            'addSelectOpt' => true,  // whether to automaticaly add an empty/"Select" option
            'optgroup' => false,     // a "property" of each option
            'options' => array(),
            'tagname' => 'select',
            'values' => array(),
        ),
        'static' => array(
            'attribs' => array(
                'class' => array('form-control-static', 'replace'),
            ),
            'tagname' => 'div',
        ),
        'submit' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
            'label' => 'Submit',
            'tagname' => 'button',
        ),
        'tel' => array(
            'definition' => 'typeTel',
        ),
        'textarea' => array(
            'attribs' => array(
                'rows' => '4',
            ),
            'tagname' => 'textarea',
        ),
        'url' => array(
            'attribs' => array(
                'pattern'       => 'https?://([-\w\.]+)+(:\d+)?(/([-\w/\.]*(\?\S+)?)?)?',
                'placeholder'   => 'http://',
            ),
        ),
    );

    public $controlBuilder;
    public $form;

    /**
     * Constructor
     *
     * @param Form  $form                Form instance
     * @param array $defaultProps        Default control properties
     * @param array $defaultPropsPerType Default control properties per input type
     */
    public function __construct(Form $form = null, $defaultProps = array(), $defaultPropsPerType = array())
    {
        $this->form = $form;
        $this->defaultProps = ArrayUtil::mergeDeep(
            $this->classnamesToArray($this->defaultProps),
            $this->classnamesToArray($defaultProps)
        );
        foreach ($defaultPropsPerType as $type => $props) {
            if (!isset($this->defaultPropsPerType[$type])) {
                $this->defaultPropsPerType[$type] = $props;
                continue;
            }
            $this->defaultPropsPerType[$type] = ArrayUtil::mergeDeep(
                $this->classnamesToArray($this->defaultPropsPerType[$type]),
                $this->classnamesToArray($props)
            );
        }
        $this->controlBuilder = new ControlBuilder($this);
    }

    /**
     * Build control object
     *
     * @param array $props control properties
     *
     * @return Control
     */
    public function build($props)
    {
        $definition = $this->getDefinitionClass($props);
        $class = '\\bdk\\Form\\Control';
        if ($definition) {
            $classes = array(
                $definition,
                '\\bdk\\Form\\ControlDefinitions\\'.\ucfirst($definition),
            );
            foreach ($classes as $classCheck) {
                if (\class_exists($classCheck)) {
                    $class = $classCheck;
                    break;
                }
            }
        }
        $control = new $class($props, $this->form, $this);
        return $control;
    }

    /**
     * Convert classname string to array
     *
     * @param array $props properties
     *
     * @return array
     */
    protected static function classnamesToArray($props)
    {
        foreach ($props as $k => $v) {
            if (\strpos($k, 'attribs') === 0 && isset($v['class']) && !\is_array($v['class'])) {
                $props[$k]['class'] = \explode(' ', $v['class']);
            }
        }
        return $props;
    }

    /**
     * Get control definition class name
     *
     * @param array $props control properties
     *
     * @return string class name
     */
    protected function getDefinitionClass($props)
    {
        $type = 'text';
        if (isset($props['attribs']['type'])) {
            $type = $props['attribs']['type'];
        } elseif (isset($props['type'])) {
            $type = $props['type'];
        }
        $propsType = isset($this->defaultPropsPerType[$type])
            ? $this->defaultPropsPerType[$type]
            : array();
        $definition = null;
        if (isset($props['definition'])) {
            $definition = $props['definition'];
        } elseif (isset($propsType['definition'])) {
            $definition = $propsType['definition'];
        }
        return $definition;
    }
}
