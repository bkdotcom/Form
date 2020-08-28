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

    public $cfg = array(
        'theme' => 'bootstrap4',
    );
    public $controlBuilder;
    public $defaultProps = array(
        'default' => array(
            'addonAfter' => null,
            'addonBefore' => null,
            'attribs' => array(
                'name' => null,
                'id' => null,
                'value' => null,
                'type' => 'text',
                'required' => false,
            ),
            'attribsContainer' => array(),
            'attribsLabel' => array(),
            'attribsControls' => array(),
            'attribsHelpBlock' => array(),
            'attribsInputGroup' => array(),
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
            'userEmail' => false,
            'userName' => false,
            'build' => null,            // callable
            'getValFormatted' => null,  // callable
            'getValRaw' => null,        // callable
            'setValRaw' => null,        // callable
            'validate' => null,         // callable
        ),
        'button' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'tagname' => 'button',
        ),
        'checkbox' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'attribsInputLabel' => array(),
            'attribsLabel' => array(),
            'checkboxGroup' => 'auto',  // single option returns a scalar, more than one returns an array
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '<label {{attribsLabel}}>'
                    . '{{input}}'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
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
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                // 'data-lpignore'  => true,    // no LastPass icon
            ),
        ),
        'radio' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'attribsInputLabel' => array(),
            'attribsLabel' => array(),
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '<label {{attribsLabel}}>'
                    . '{{input}}'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
            'options' => array(),
            'useFieldset' => 'auto',
        ),
        'range' => array(
            'attribs' => array(
                // 'pattern' => '-?\d+(\.\d+)?(e[-+]?\d+)?',
                'class' => array(null, 'replace'), // don't want 'form-control', 'input-sm', etc
                'max' => 100,
                'min' => 0,
                'step' => 1,
            ),
        ),
        'reset' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
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
                'class' => array(null, 'replace'),
            ),
            'tagname' => 'div',
        ),
        'submit' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
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

    public $form;

    /**
     * Constructor
     *
     * @param Form  $form Form instance
     * @param array $cfg  Configuration options (incl defaultProps)
     */
    public function __construct(Form $form = null, $cfg = array())
    {
        $defaultProps = isset($cfg['defaultProps'])
            ? $cfg['defaultProps']
            : array();
        unset($cfg['defaultProps']);

        $this->form = $form;
        $this->cfg = \array_merge($this->cfg, $cfg);

        if (\is_string($this->cfg['theme'])) {
            $this->cfg['theme'] = require __DIR__ . '/theme/' . $this->cfg['theme'] . '.php';
        }

        $this->setDefaultProps($defaultProps);
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
                '\\bdk\\Form\\ControlDefinitions\\' . \ucfirst($definition),
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
    /*
    protected static function classnamesToArray($props)
    {
        foreach ($props as $k => $v) {
            if (\strpos($k, 'attribs') === 0 && isset($v['class']) && !\is_array($v['class'])) {
                $props[$k]['class'] = \explode(' ', $v['class']);
            }
        }
        return $props;
    }
    */

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
        $propsType = isset($this->defaultProps[$type])
            ? $this->defaultProps[$type]
            : array();
        $definition = null;
        if (isset($props['definition'])) {
            $definition = $props['definition'];
        } elseif (isset($propsType['definition'])) {
            $definition = $propsType['definition'];
        }
        return $definition;
    }

    /**
     * [setDefaultProps description]
     *
     * @param array $defaultProps [description]
     *
     * @return void
     */
    protected function setDefaultProps($defaultProps)
    {
        $keys = \array_unique(\array_merge(
            \array_keys($this->defaultProps),
            \array_keys($this->cfg['theme']['defaultProps']),
            \array_keys($defaultProps)
        ));
        foreach ($keys as $key) {
            $stack = \array_filter(array(
                isset($this->defaultProps[$key])
                    ? $this->defaultProps[$key]
                    : array(),
                isset($this->cfg['theme']['defaultProps'][$key])
                    ? $this->cfg['theme']['defaultProps'][$key]
                    : array(),
                isset($defaultProps[$key])
                    ? $defaultProps[$key]
                    : array()
            ));
            $this->defaultProps[$key] = $stack
                ? \array_shift($stack)
                : array();
            while ($stack) {
                $props = \array_shift($stack);
                Control::mergeClassesPrep($this->defaultProps[$key], $props, true);
                $this->defaultProps[$key] = ArrayUtil::mergeDeep($this->defaultProps[$key], $props);
            }
        }
    }
}
