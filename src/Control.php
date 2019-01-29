<?php

namespace bdk\Form;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\ControlBuilder;
use bdk\Form\ControlFactory;

class Control
{

    public $form;
    protected $debug;
    protected $controlFactory;
    protected $callStack = array();
    protected $props = array();
    protected $propKeys = array();

    /**
     * @var $idCounts keep track of ids generated across all forms
     */
    private static $idCounts = array();

    /**
     * Constructor
     *
     * @param array          $props          Control properties/definition
     * @param Form           $form           parent form object]
     * @param ControlFactory $controlFactory ControlFactory instance
     *                                          passed as it contains/maintains default properties
     */
    public function __construct($props = array(), Form $form = null, ControlFactory $controlFactory = null)
    {
        $this->debug = \bdk\Debug::getInstance();
        $this->controlFactory = $controlFactory;
        $this->form = $form;
        $this->setProps($props);
    }

    /**
     * Get select private properties
     * form, props
     *
     * @param string $key property to get
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (\method_exists($this, 'get'.\ucfirst($key))) {
            $method = 'get'.\ucfirst($key);
            return $this->{$method}();
        } elseif (\array_key_exists($key, $this->props['attribs'])) {
            return $this->props['attribs'][$key];
        } elseif (\array_key_exists($key, $this->props)) {
            if (\strpos($key, 'attribs') === 0) {
                $this->{$key} = &$this->props[$key];
            }
            return $this->props[$key];
        } elseif (\in_array($key, array('form','props'))) {
            return $this->{$key};
        }
        return null;
    }

    /**
     * Magic isset
     *
     * @param string $key [description]
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->props[$key]);
    }

    /**
     * Magic set
     *
     * @param string $key   key
     * @param mixed  $value new value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        if (\in_array($key, $this->propKeys)) {
            // known property
            $this->props[$key] = $value;
        } else {
            // assume attribute
            $this->props['attribs'][$key] = $value;
        }
    }

    /**
     * Build html control
     *
     * @param array $propOverride properties to override
     *
     * @return string
     */
    public function build($propOverride = array())
    {
        // $this->debug->info(__METHOD__, $this->attribs['name']);
        if ($propOverride == 'tagOnly') {
            $propOverride = array('tagOnly' => true);
        } elseif (!\is_array($propOverride)) {
            $this->debug->warn('invalid propOverride', $propOverride, 'expect array or "tagOnly"');
            $propOverride = array();
        }
        if ($propOverride) {
            $this->debug->warn('propOverride');
            $this->props = $this->mergeProps(array(
                $this->props,
                $propOverride,
            ));
        }
        // $this->debug->log('build props', $this->props);
        $return = $this->controlFactory->controlBuilder->build($this);
        return $return;
    }

    public static function classAdd(&$attribs, $classNames)
    {
        ControlBuilder::classAdd($attribs, $classNames);
    }

    public static function classRemove(&$attribs, $classNames)
    {
        ControlBuilder::classRemove($attribs, $classNames);
    }

    /**
     * Clear the static ID counter that ensures controls have unique IDs
     *
     * @return void
     */
    public static function clearIdCounts()
    {
        self::$idCounts = array();
    }

    /**
     * Extend me to build custom html
     *
     * @return string
     */
    public function doBuild()
    {
        $return = '';
        if (\is_callable($this->props['build'])) {
            $return = $this->props['build']($this);
        }
        return $return;
    }

    /**
     * Extend me to perform custom validation
     *
     * @return boolean valid?
     */
    public function doValidate()
    {
        $isValid = true;
        if ($this->props['validate']) {
            $isValid = \call_user_func($this->props['validate'], $this);
        }
        return $isValid;
    }

    /**
     * Flag control as invalid
     *
     * @param string $reason The reason that will be given to the user
     *
     * @return void
     */
    public function flag($reason = null)
    {
        $this->props['isValid'] = false;
        if ($this->attribs['type'] == 'file' && isset($this->form->currentValues[ $this->attribs['name'] ])) {
            $fileInfo = $this->form->currentValues[ $this->attribs['name'] ];
            \unlink($fileInfo['tmp_name']);
            unset($this->form->currentValues[ $this->attribs['name'] ]);
        }
        if (!\is_null($reason)) {
            $this->props['invalidReason'] = $reason;
        }
        return;
    }

    /**
     * Sets autofocus attribute
     *
     * @return void
     */
    public function focus()
    {
        if (\in_array($this->attribs['type'], array('checkbox', 'radio'))) {
            $keys = \array_keys($this->props['options']);
            $this->props['options'][ $keys[0] ]['autofocus'] = true;
        } else {
            $this->props['attribs']['autofocus'] = true;
        }
    }

    /**
     * Returns ID attribute
     * If ID attribute is non-existant, ID will be derived from name and prefix
     *
     * @return string|null
     */
    public function getId()
    {
        $id = $this->props['attribs']['id'];
        $sepChar = '_'; // character to join prefix and id
        $repChar = '_'; // replace \W with this char
        if (!$id && $this->props['attribs']['name']) {
            $id = \preg_replace('/\W/', $repChar, $this->props['attribs']['name']);
            $id = \preg_replace('/'.$repChar.'+/', $repChar, $id);
            $id = \trim($id, $repChar);
            if ($id && $this->props['idPrefix']) {
                $prefix = $this->props['idPrefix'];
                if (\preg_match('/[a-z]$/i', $prefix)) {
                    $prefix = $prefix.$sepChar;
                }
                $id = $prefix.$id;
            }
            if ($id) {
                $this->props['attribs']['id'] = $id;
            }
        }
        return $id;
    }

    /**
     * Returns unique ID.
     *
     * Checks if ID already used... Increments static counter
     *
     * @param boolean $increment increment static counter?
     *
     * @return string|null Unique ID
     */
    public function getUniqueId($increment = true)
    {
        $id = $this->getId();
        if ($id) {
            $sepChar = '_';
            if (isset(self::$idCounts[$id])) {
                if ($increment) {
                    self::$idCounts[$id] ++;
                }
                if (self::$idCounts[$id] > 1) {
                    $id .= $sepChar.self::$idCounts[$id];
                }
            } else {
                self::$idCounts[$id] = 1;
            }
        }
        return $id;
    }

    /**
     * Is control required?
     *
     * @return boolean
     */
    public function isRequired()
    {
        $isRequired = $this->attribs['required'];
        if (\is_string($isRequired)) {
            $replaced = \preg_replace('/{{(.*?)}}/', '$this->form->getControl("$1")->val()', $isRequired);
            $evalString = '$isRequired = (bool) '.$replaced.';';
            eval($evalString);
        }
        return $isRequired;
    }

    /**
     * Merge control properties
     *
     * @param array   $mergeStack array of property arrays
     * @param boolean $options    options passed to ArrayUtil::mergeDeep
     *
     * @return array merged properties
     */
    public function mergeProps($mergeStack, $options = array())
    {
        $merged = array();
        /*
            tricky:  array may contain references...  which we don't want to affect
        */
        // $mergeStack = \json_decode(\json_encode($mergeStack), true); // borks objects
        while ($mergeStack) {
            $props = \array_shift($mergeStack);
            $props = $this->moveAttribs($props);
            $props = self::mergeClassesPrep($merged, $props, !$mergeStack);
            $merged = ArrayUtil::mergeDeep($merged, $props, $options);
        }

        if (isset($merged['attribs']) && isset($merged['template'])) {
            \preg_match_all('#{{([^}]+)}}#', $merged['template'], $matches);
            $moveBack = \array_intersect_key($merged['attribs'], \array_flip($matches[1]));
            $merged['attribs'] = \array_diff_key($merged['attribs'], $moveBack);
            $merged += $moveBack;
        }

        return $merged;
    }

    /**
     * Move attribs to 'attribs' array
     *
     * @param array $props properties
     *
     * @return array
     */
    protected function moveAttribs($props)
    {
        /*
        $attribShortcuts = array(
            'checked',
            'disabled',
            'name',
            'required',
            'selected',
            'type',
            'value',
        );
        */
        if (!$this->propKeys) {
            return $props;
        }
        if (!isset($props['attribs'])) {
            $props['attribs'] = array();
        }

        if (isset($props['attributes'])) {
            $props['attribs'] = \array_merge($props['attribs'], $props['attributes']);
            unset($props['attributes']);
        }


        $attribsMove = \array_diff_key($props, \array_flip($this->propKeys));

        /*
        $attribsMove = \array_diff_key($props, \array_flip($nonAttribKeys))
            + \array_intersect_key($props, \array_flip($attribShortcuts));
        $props = \array_diff_key($props, $attribsMove);
        $props['attribs'] = \array_merge($attribsMove, $props['attribs']);
        */
        /*
        foreach ($attribShortcuts as $k) {
            if (\array_key_exists($k, $props) && !isset($props['attribs'][$k])) {
                $props['attribs'][$k] = $props[$k];
            }
            unset($props[$k]);
        }
        */
        $props = \array_diff_key($props, $attribsMove);

        // not using array_merge as we don't want to overwrite with null
        foreach ($attribsMove as $k => $v) {
            if (\strpos($k, 'attribs') === 0) {
                $props[$k] = $v;
            } elseif (!isset($props['attribs'][$k])) {
                $props['attribs'][$k] = $v;
            }
        }
        return $props;
    }

    protected function getDefaultProps($type)
    {
        $propsDefaultType = array();
        if (isset($this->controlFactory->defaultPropsPerType[$type])) {
            $propsDefaultType = $this->controlFactory->defaultPropsPerType[$type];
        }
        return $propsDefaultType;
    }

    /**
     * Set control properties
     *
     * @param array $props [description]
     *
     * @return void
     */
    public function setProps($props = array())
    {
        // $this->debug->log('setProps', $props);
        $type = 'text';
        if (isset($props['attribs']['type'])) {
            $type = $props['attribs']['type'];
        } elseif (isset($props['type'])) {
            $type = $props['type'];
        } elseif (isset($this->props['attribs']['type'])) {
            $type = $this->props['attribs']['type'];
        } else {
            $type = 'text';
        }
        $isTypeChanging = !isset($this->props['attribs']['type']) || $type !== $this->props['attribs']['type'];

        $propsDefault = array();
        $propsDefaultType = array();
        if ($isTypeChanging) {
            $propsDefault = $this->props ?: $this->controlFactory->defaultProps;
            $propsDefaultType = $this->getDefaultProps($type);
            if ($type == 'submit' && empty($props['label']) && !empty($props['attribs']['value'])) {
                $props['label'] = $props['attribs']['value'];
            }
            $this->setPropKeys($type);
        }
        $this->props = $this->mergeProps(array(
            $propsDefault,
            $propsDefaultType,
            $props,
        ));
        if (empty($this->props['attribs']['x-moz-errormessage']) && !empty($this->props['invalidReason'])) {
            $this->props['attribs']['x-moz-errormessage'] = $this->props['invalidReason'];
        }
        foreach (\array_keys($this->props) as $k) {
            if (\strpos($k, 'attribs') === 0) {
                $this->{$k};
            }
        }
        $this->getId();
        if (\in_array($type, array('checkbox','radio','select'))) {
            $this->normalizeCrs();
        }
    }

    /**
     * Get property keys..
     * Any non-recognized key will be treated as a control attribute
     *
     * @param string $type input type
     *
     * @return void
     */
    private function setPropKeys($type)
    {
        $propsDefault = $this->controlFactory->defaultProps;
        $propsDefault += $this->getDefaultProps($type);
        $this->propKeys = \array_merge(
            $this->propKeys,
            \array_keys($propsDefault),
            array('pageI')
        );
    }

    /**
     * Set control's value
     *
     * @param mixed   $value value
     * @param boolean $store store the value?
     *
     * @return void
     */
    private function setValue($value, $store)
    {
        if (\in_array($this->attribs['type'], array('checkbox','select'))) {
            if (\is_null($value)) {
                $value = array();
            }
            $this->props['values'] = (array) $value;
        } else {
            $this->props['attribs']['value'] = $value;
        }
        if ($store) {
            if ($this->props['setValRaw']) {
                \call_user_func($this->props['setValRaw'], $this, $value);
            } elseif ($this->form) {
                $this->form->setValue($this->attribs['name'], $value, $this->props['pageI']);
            }
        }
    }

    /**
     * Get or set control's value
     * If getting, will return
     *    + formatted value if control has getValFormatted callable
     *    + raw value otherwise
     *
     * @param mixed   $val   (optional) new value
     * @param boolean $store (true) store value?
     *
     * @return mixed
     */
    public function val($val = null, $store = true)
    {
        if (\func_num_args()) {
            // setting
            $this->setValue($val, $store);
        } else {
            if ($this->props['getValFormatted'] && !\in_array('getValFormatted', $this->callStack)) {
                $this->callStack[] = 'getValFormatted';
                $return = \call_user_func($this->props['getValFormatted'], $this);
                ArrayUtil::removeVal($this->callStack, 'getValFormatted');
                return $return;
            } else {
                return $this->valRaw();
            }
        }
    }

    /**
     * Return control's raw (user input) value
     *
     * @return mixed
     */
    public function valRaw()
    {
        if ($this->props['getValRaw'] && !\in_array('getValRaw', $this->callStack)) {
            $this->callStack[] = 'getValRaw';
            $return = \call_user_func($this->props['getValRaw'], $this);
            ArrayUtil::removeVal($this->callStack, 'getValRaw');
        } else {
            $return = \in_array($this->attribs['type'], array('checkbox','select'))
                ? $this->props['values']
                : $this->attribs['value'];
        }
        if ($this->attribs['type'] == 'checkbox' && !$this->props['checkboxGroup']
            || $this->attribs['type'] == 'select' && empty($this->attribs['multiple'])) {
            $return = \array_pop($return);
        }
        return $return;
    }

    /**
     * [validate description]
     *
     * @return boolean
     */
    public function validate()
    {
        $this->debug->groupCollapsed(__METHOD__, $this->attribs['name']);
        $this->props['isValid'] = true;
        $attribs = $this->attribs;
        if (\in_array($attribs['type'], array('checkbox','radio','select'))) {  // these types use the 'options' array
            $this->validateCrs();
        } elseif (\in_array($attribs['type'], array('submit','reset','image'))) {
            $this->debug->log('not checking');
        } elseif (!empty($this->props['newPage'])) {
            $this->debug->log('not checking newPage');
        } elseif (\in_array($attribs['type'], array('file'))) {
            $this->debug->log($attribs['name'].' is a file type');
            if ($this->isRequired() && empty($attribs['disabled']) && empty($attribs['value'])) {
                $this->flag();
            }
        } elseif ($this->isRequired() || $this->props['flagNonReqInvalid']) {
            if (!empty($attribs['placeholder']) && $attribs['value'] === $attribs['placeholder']) {
                $this->debug->log('value == placeholder');
                $attribs['value'] = null;
            }
            $this->validateFromAttributes();
        }
        if ($this->props['isValid']) {
            $isValid = $this->doValidate();
            // validate callback may call flag() directly
            if ($this->props['isValid'] && !$isValid) {
                $this->flag();
            }
        }
        $this->debug->groupEnd();
        return $this->props['isValid'];
    }

    /**
     * Utility class to merge classnames
     * Doesn't actually merge...
     *   removes default classnames if replacing
     *
     * classnames may be defined as:
     *      'string'                            (space separated classnames)
     *      array()                             (array -o- classnames)
     *      array('string', 'merge'|'replace')
     *      array(array(), 'merge'|'replace')
     *      array(null, 'replace')
     *
     * @param array   $propsDefault         default control properties
     * @param array   $props                control properties
     * @param boolean $keepClassReplaceFlag should we retain merge/replace flag?
     *
     * @return array
     */
    private static function mergeClassesPrep(&$propsDefault, $props, $keepClassReplaceFlag = false)
    {
        foreach ($props as $k => $v) {
            if (!\is_array($v) || !\array_key_exists('class', $v)) {
                continue;
            }
            $merge = true;         // merge is default behavior
            $class = $v['class'];
            if (\is_array($class)) {
                if (\count($class) == 2 && \in_array($class[1], array('merge','replace'))) {
                    // merge or replace specified
                    $merge = $class[1] === 'merge';
                    $class = $class[0]; // string | array | null
                    if (!\is_array($class)) {
                        $class = \array_filter(\explode(' ', $class), 'strlen');
                    }
                } elseif (\count($class) > 2 && \in_array(\end($class), array('merge','replace'))) {
                    $merge = \end($class) === 'merge';
                    $class = \array_slice($class, 0, -1);
                }
            } else {
                $class = \array_filter(\explode(' ', $class), 'strlen');
                if (\count($class) > 1 && \in_array(\end($class), array('merge','replace'))) {
                    $merge = \end($class) === 'merge';
                    $class = \array_slice($class, 0, -1);
                }
            }
            if (!$merge) {
                // replace... remove the default class
                if ($keepClassReplaceFlag) {
                    $class[] = 'replace';
                }
                unset($propsDefault[$k]['class']);
            }
            $props[$k]['class'] = $class;
        }
        return $props;
    }

    /**
     * Normalize Checkbox/Radio/Select properties
     *
     * @return void
     */
    protected function normalizeCrs()
    {
        $type = $this->props['attribs']['type'];
        // $this->debug->info('normalizeCrs', $type);
        if (empty($this->props['options'])) {
            $this->props['options'] = array();
            if ($type == 'checkbox') {
                $this->props['options'][] = array(
                    'value' => 'on',
                    'label' => $this->props['label'],
                    'attribs' => $this->props['attribs'],
                );
                $this->props['label'] = null;
                unset($this->props['attribs']['value']);
            }
        }
        if ($type == 'checkbox' && $this->props['checkboxGroup'] === 'auto') {
            $this->props['checkboxGroup'] = \count($this->props['options']) > 1;
        }
        foreach ($this->props['options'] as $key => $option) {
            if (!\is_array($option)) {
                $this->props['options'][$key] = array(
                    'label' => $option,
                    'attribs' => array(
                        'value' => \is_string($key)
                            ? $key
                            : $option,
                    ),
                );
            } else {
                $option = $this->moveAttribs($option);
                if (!isset($option['label']) && isset($option['attribs']['value'])) {
                    $option['label'] = $option['attribs']['value'];
                }
                $this->props['options'][$key] = $option;
            }
        }
        if (\in_array($type, array('checkbox','select')) && !$this->props['values']) {
            /*
                values may be passed as an alternate way of specifying which values are checked/selected
                single checkbox:
                    passing 'value' is considered the option (not user supplied value) unless options array is non-empty
            */
            $this->props['values'] = isset($this->attribs['value'])
                ? (array) $this->attribs['value']
                : array();
            unset($this->props['attribs']['value']);
        }
    }

    /**
     * Validate based on HTML-5 attributes (pattern, min, max, maxlength)
     *
     * @return void
     */
    protected function validateFromAttributes()
    {
        $value = $this->attribs['value'];
        if (\strlen($value) == 0) {
            $this->debug->log('empty value');
            if ($this->isRequired()) {
                $this->flag();
            }
        } elseif (isset($this->attribs['pattern']) && !\preg_match('/^'.\str_replace('/', '\/', $this->attribs['pattern']).'$/', $value)) {
            $this->debug->log('pattern mismatch');
            $this->flag('invalid format');
        } elseif (isset($this->attribs['max']) && \preg_replace('/[^-\d\.]/', '', $value) > $this->attribs['max']) {
            $this->debug->log('too big');
            $this->flag('value is too large');
        } elseif (isset($this->attribs['min']) && \preg_replace('/[^-\d\.]/', '', $value) < $this->attribs['min']) {
            $this->debug->log('too small');
            $this->flag('value is too small');
        } elseif (isset($this->attribs['maxlength']) && \mb_strlen($value) > $this->attribs['maxlength']) {
            $this->debug->log('too long');
            if (empty($this->props['invalidReason'])) {
                $this->props['invalidReason'] = 'Value is too long';
            }
            $this->flag($this->props['invalidReason']);
        }
    }

    /**
     * Validate Checkbox, Radio, Select
     *
     * @return void
     */
    protected function validateCrs()
    {
        $nonEmpty   = array();
        $enabled    = array();
        $required   = array();
        $optsValid  = array();
        foreach ($this->props['options'] as $opt) {
            if (empty($opt['attribs']['disabled']) && !isset($opt['optgroup'])) {
                $enabled[] = $opt['attribs']['value'];
                $optsValid[] = $opt['attribs']['value'];
            }
            if (!empty($opt['attribs']['required'])) {
                $required[] = $opt['attribs']['value'];
            }
        }
        $values = $this->attribs['type'] == 'radio'
            ? (array) $this->attribs['value']
            : $this->props['values'];
        if (!empty($values)) {
            $this->debug->log('values', $values);
            foreach ($values as $k => $val) {
                if (!\in_array($val, $optsValid)) {
                    $this->debug->warn('invalid answer!', $val);
                    unset($values[$k]);
                } elseif ($val != '') {
                    $nonEmpty[] = $val;
                }
            }
        }
        if (($this->isRequired() || !empty($required)) && !empty($enabled) && empty($nonEmpty)) {
            $this->debug->log('required, enabled, & no value!');
            $this->props['isValid'] = false;
        }
    }
}
