<?php

namespace bdk\Form;

use bdk\ArrayUtil;

/**
 * default field properties
 */
class FieldBase
{

    protected $props = array(
        'label' => '',
        'helpBlock' => '',
        'addonBefore' => null,
        'addonAfter' => null,
        // 'checkboxGroup' => 'auto'    // single option returns a scalar, more than one returns an array
        'idPrefix' => null,
        'tagOnly' => false,
        'invalidReason' => null,
        'isValid' => true,
        'build' => null,            // callable
        'getValFormatted' => null,  // callable
        'getValRaw' => null,        // callable
        'setValRaw' => null,        // callable
        'validate' => null,         // callable
        'definition' => null,   // class that extends Field
            // doBuild
            // doValidate
            // getValFormatted
            // getValRaw
            // setValRaw
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
        'tagname' => 'input',
        'template' => '<div {{attribsContainer}}>
                <label {{attribsLabel}}>{{label}}</label>
                <div {{attribsControls}}>
                    {{input}}
                    <span {{attribsHelpBlock}}>{{helpBlock}}</span>
                </div>
            </div>',
    );
    protected $attribs;

    protected $defaultPropsType = array(
        'button' => array(
            'tagname' => 'button',
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
        ),
        'checkbox' => array(
            'attribs' => array(
                'class' => array(null, 'replace'),
            ),
            'checkboxGroup' => 'auto',  // single option returns a scalar, more than one returns an array
            'options' => array(),
            'useFieldset' => 'auto',
        ),
        'date' => array(
            'definition' => 'typeDate',
        ),
        'datetime-local' => array(
            'attribs' => array(
                'placeholder' => 'yyyy-mm-ddThh:mm:ss',
                'class' => 'hide-spinbtns',
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
                'autocomplete' => 'off',        // see also:  "current-password" & "new-password"
                'autocapitalize' => 'none',
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
            'tagname' => 'button',
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
            'label' => 'Reset',
        ),
        'search' => array(
            'attribs' => array(
                'placeholder' => 'search',
            ),
        ),
        'select' => array(
            'tagname' => 'select',
            'attribs' => array(
                'multiple' => false,
            ),
            'options' => array(),
        ),
        'static' => array(
            'tagname' => 'div',
            'attribs' => array(
                'class' => array('form-control-static', 'replace'),
            ),
        ),
        'submit' => array(
            'tagname' => 'button',
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
            'label' => 'Submit',
        ),
        'tel' => array(
            'definition' => 'typeTel',
        ),
        'textarea' => array(
            'tagname' => 'textarea',
            'attribs' => array(
                'rows' => '4',
            ),
        ),
        'url' => array(
            'attribs' => array(
                'pattern'       => 'https?://([-\w\.]+)+(:\d+)?(/([-\w/\.]*(\?\S+)?)?)?',
                'placeholder'   => 'http://',
            ),
        ),
    );

    /**
     * Merge field properties
     *
     * @param array   $mergeStack array of property arrays
     * @param boolean $options    options passwed to ArrayUtil::mergeDeep
     *
     * @return array merged properties
     */
    public static function mergeProps($mergeStack, $options = array())
    {
        $merged = array();
        while ($mergeStack) {
            $props = \array_shift($mergeStack);
            $props = self::moveShortcuts($props);
            $props = self::mergeClassesPrep($merged, $props);
            $props = self::classnamesToArray($props);
            $merged = ArrayUtil::mergeDeep($merged, $props, $options);
        }
        return $merged;
    }

    /**
     * Move common attributes to 'attribs' array
     *
     * @param array $props   properties
     * @param array $addKeys additional keys to treat as shortcuts
     *
     * @return array
     */
    public static function moveShortcuts($props, $addKeys = array())
    {
        $attribShortcuts = \array_merge(array(
            'checked',
            'disabled',
            'name',
            'required',
            'selected',
            'type',
            'value',
        ), $addKeys);
        if (isset($props['attributes'])) {
            $props['attribs'] = $props['attributes'];
            unset($props['attributes']);
        }
        if (!isset($props['attribs'])) {
            $props['attribs'] = array();
        }
        foreach ($attribShortcuts as $k) {
            if (\array_key_exists($k, $props) && !isset($props['attribs'][$k])) {
                $props['attribs'][$k] = $props[$k];
            }
            unset($props[$k]);
        }
        return $props;
    }

    /**
     * Add classname(s)
     *
     * @param array        $attribs    [description]
     * @param string|array $classNames [description]
     *
     * @return void
     */
    protected function classAdd(&$attribs, $classNames)
    {
        $classNamesCur = isset($attribs['class'])
            ? $attribs['class']
            : array();
        if (!\is_array($classNamesCur)) {
            $classNamesCur = \explode(' ', $classNamesCur);
        }
        if (!\is_array($classNames)) {
            $classNames = \explode(' ', $classNames);
        }
        $classNames = \array_merge($classNamesCur, $classNames);
        $classNames = \array_unique($classNames);
        $attribs['class'] = $classNames;
    }

    /**
     * remove classname(s)
     *
     * @param array        $attribs    [description]
     * @param string|array $classNames [description]
     *
     * @return void
     */
    protected function classRemove(&$attribs, $classNames)
    {
        $classNamesCur = isset($attribs['class'])
            ? $attribs['class']
            : array();
        if (!\is_array($classNamesCur)) {
            $classNamesCur = \explode(' ', $classNamesCur);
        }
        if (!\is_array($classNames)) {
            $classNames = \explode(' ', $classNames);
        }
        $attribs['class'] = \array_diff($classNamesCur, $classNames);
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
            if (\strpos($k, 'attribs') === 0 && isset($v['class'])) {
                if (!\is_array($v['class'])) {
                    $props[$k]['class'] = \explode(' ', $v['class']);
                }
            }
        }
        return $props;
    }

    /**
     * Utility class to merge classnames
     * Doesn't actually merge...
     *   removes default if replacing
     *   removes the append/replace modifier from classes
     *
     * classnames may be defined as:
     *      'string'                            (space separated classnames)
     *      array()                             (array -o- classnames)
     *      array('string', 'append'|'replace')
     *      array(array(), 'append'|'replace')
     *
     * @param array $propsDefault default field properties
     * @param array $props        field properties
     *
     * @return array
     */
    protected static function mergeClassesPrep(&$propsDefault, $props)
    {
        foreach ($props as $k => $v) {
            if (!\is_array($v) || !\array_key_exists('class', $v) || !isset($propsDefault[$k]['class'])) {
                continue;
            }
            $append = true;         // append is default behavior
            $class = $v['class'];
            if (\is_array($class) && \count($class) == 2 && \in_array($class[1], array('append','replace'))) {
                // array with append or replace specified
                $append = $class[1] === 'append';
                $class = $class[0]; // string or array
            } elseif (isset($propsDefault[$k]['class']) &&
                \is_array($propsDefault[$k]['class']) &&
                \count($propsDefault[$k]['class']) == 2 &&
                \in_array($propsDefault[$k]['class'][1], array('append','replace'))
            ) {
                $append = $propsDefault[$k]['class'][1] === 'append';
            }
            $props[$k]['class'] = $class;
            if (!$append) {
                // replace... remove the default class
                unset($propsDefault[$k]['class']);
            }
        }
        return $props;
    }
}
