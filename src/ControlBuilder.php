<?php

namespace bdk\Form;

use bdk\Html;
use bdk\Str;
use bdk\Form\Control;
use bdk\Form\ControlBase;
// use bdk\Form\ControlFactory;
use bdk\PubSub\Event;
use bdk\PubSub\Manager as EventManager;

/**
 * Build Input
 */
class ControlBuilder extends ControlBase
{

    public $eventManager;
    protected $control;
    protected $building = array();
    // protected $controlFactory;

    /**
     * Constructor
     *
     * @param EventManager   $eventManager   event manager instance
     * @param ControlFactory $controlFactory controlFactory instance
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
        // $this->controlFactory = $controlFactory;
        $this->eventManager->subscribe('form.buildControl', array($this, 'onBuildControl'));
    }

    /**
     * Build a form input control
     *
     * @param Control|array $control Control or control properties
     *
     * @return string html
     */
    public function build($control)
    {
        $return = '';
        if (\is_array($control)) {
            $control = $this->controlFactory->build($control);
        }
        \bdk\Debug::_group(__METHOD__, $control->attribs['name']);
        $this->control = $control;
        $this->props = $control->props;
        $id = $control->getId();
        $isBuilding = \in_array($id, $this->building);
        $uniqueId = $control->getUniqueId(!$isBuilding);
        $this->setIds($uniqueId);
        $this->addAttributes();
        if (!$isBuilding) {
            $this->building[] = $id;
            $return = $this->eventManager->publish('form.buildControl', $control, array(
                'return' => $return,
            ))['return'];
        }
        $key = \array_search($id, $this->building);
        if ($key !== false) {
            unset($this->building[$key]);
        }
        \bdk\Debug::_groupEnd();
        return $return;
    }

    public function onBuildControl(Event $event)
    {
        // see if control has a doBuild method that returns something or a callable 'build' property
        $return = $event->getSubject()->doBuild();
        if (!$return) {
            $return = $this->doBuild();
        }
        $return = $this->removeEmptyTags($return);
        $event['return'] = $return;
    }

    /**
     * [addAttributes description]
     *
     * @return void
     */
    protected function addAttributes()
    {
        if ($this->props['tagOnly']) {
            return;
        }
        if ($this->props['attribs']['required']) {
            $this->classAdd($this->props['attribsContainer'], 'required');
        }
        if (!$this->props['isValid']) {
            $this->classAdd($this->props['attribsContainer'], 'has-error');
            if ($this->props['invalidReason']) {
                $this->props['helpBlock'] = $this->props['invalidReason'];
            }
        } else {
            $this->classRemove($this->props['attribsContainer'], 'has-error');
        }
        if ($this->props['helpBlock']) {
            // we don't want aria-describedby attrib when tagOnly
            $this->props['attribs']['aria-describedby'] = $this->props['attribsHelpBlock']['id'];
        }
    }

    /**
     * Build attribute strings
     *
     * @param array $props property array
     *
     * @return array
     */
    protected function buildAttribStrings($props)
    {
        $attribStrings = array();
        foreach ($props as $k => $v) {
            if (\strpos($k, 'attribs') === 0) {
                $attribStrings[$k] = \trim(Html::buildAttribString($v));
            }
        }
        return $attribStrings;
    }

    /**
     * Build a button control
     *
     * @return string
     */
    protected function buildButton()
    {
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        if (empty($props['input'])) {
            if (empty($props['label']) && !empty($this->props['attribs']['value'])) {
                $props['label'] = $this->props['attribs']['value'];
            }
            $props['input'] = Html::buildTag(
                $props['tagname'],
                $props['attribs'],
                $props['label']
            );
        }
        $props['label'] = null; // so <label></label> will be empty and get stripped
        return $props['tagOnly']
            ? $props['input']
            : Str::quickTemp($props['template'], $props);
    }

    /**
     * Build a checkbox control (or checkbox group)
     *
     * @return string html
     */
    protected function buildCheckbox()
    {
        /*
        checkbox:
            if option(s) passed via options array, checkbox assumed to be a "checkbox group"
                each option may contain value and attributes
                may also pass values array to specify initially checked values
            a single checkbox may be specified by setting
                label the checkbox's label
                value (the value attribute)
                checked (boolean - whether or not initially checked)
        */
        if (\count($this->props['options']) > 1) {
            if (\substr($this->props['attribs']['name'], -2) !== '[]') {
                $this->props['attribs']['name'] .= '[]';
            }
        }
        if ($this->props['useFieldset'] === 'auto') {
            $this->props['useFieldset'] = \count($this->props['options']) > 1 || $this->props['label'];
        }
        if ($this->props['useFieldset']) {
            $this->props['template'] = \preg_replace('#^<div([^>]*)>(.+)</div>$#s', '<fieldset$1>$2</fieldset>', $this->props['template']);
            $this->props['template'] = \preg_replace('#<label([^>]*)>(.+)</label>#s', '<legend>$2</legend>', $this->props['template']);
        }
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        $props['input'] = $this->buildCheckboxRadioGroup();
        if ($this->props['tagOnly']) {
            return $this->props;
        } else {
            return Str::quickTemp($props['template'], $props);
        }
    }

    /**
     * Builds checkbox / radio controls
     *
     * @return string html
     */
    protected function buildCheckboxRadioGroup()
    {
        $optHtml = '';
        $props = &$this->props;
        $optTemplate = '<div {{attribsPair}}>'
            .'<label {{attribsLabel}}>'
                .'{{input}}'
                .'{{label}}'
            .'</label>'
            .'</div>'."\n";
        $values = $props['attribs']['type'] == 'radio'
            ? (array) $props['attribs']['value']
            : $props['values'];
        $isMultiple = \count($props['options']) > 1;
        foreach ($props['options'] as $i => $optProps) {
            // unset($optProps['attribs']['tagname']);
            $optProps = ControlBase::mergeProps(array(
                array(
                    'attribs' => $props['attribs'], // name, type, & other "global" attributes
                ),
                array(
                    'attribs' => array(
                        'checked' => \in_array($optProps['attribs']['value'], $values),
                    ),
                    'attribsLabel' => array(),
                    'attribsPair' => array( 'class' => $props['attribs']['type'] ),
                ),
                $optProps,
                array(
                    'attribs' => array(
                        'id' => $props['attribs']['id']
                            ? $props['attribs']['id'].($isMultiple ? '_'.($i+1) : '')
                            : null,
                    ),
                ),
            ));
            if (!empty($optProps['attribs']['disabled'])) {
                $this->classAdd($optProps['attribsPair'], 'disabled');
            }
            $attribStrings = $this->buildAttribStrings($optProps);
            $optProps['input'] = '<input '.$attribStrings['attribs'].' />';
            $props['options'][$i] = $optProps;
            $optProps = \array_merge($optProps, $attribStrings);
            $optProps['label'] = \htmlspecialchars($optProps['label']);
            $optHtml .= Str::quickTemp($optTemplate, $optProps);
        }
        return $optHtml;
    }

    /**
     * Builds <input> and <textarea> type controls
     *
     * @return string html
     */
    protected function buildDefault()
    {
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        if (empty($props['input'])) {
            // $props['attribs'] is already a string, but that's OK
            $this->props['input'] = Html::buildTag($props['tagname'], $props['attribs']);
            $props['input'] = $this->buildInputGroup();
        }
        return $props['tagOnly']
            ? $props['input']
            : Str::quickTemp($props['template'], $props);
    }

    /**
     * Handles pressence of addonAfter or addonBefore
     *
     * @return string html
     */
    protected function buildInputGroup()
    {
        $props = $this->props;
        if ($props['tagOnly']) {
            return $props['input'];
        }
        if (!$props['addonBefore'] && !$props['addonAfter']) {
            return $props['input'];
        }
        if ($props['addonBefore']) {
            $addonClass = \preg_match('#<(a|button)\b#i', $props['addonBefore'])
                ? 'input-group-btn'
                : 'input-group-addon';
            $props['addonBefore'] = '<span class="'.$addonClass.'">'.$props['addonBefore'].'</span>';
        }
        if ($props['addonAfter']) {
            $addonClass = \preg_match('#<(a|button)\b#i', $props['addonAfter'])
                ? 'input-group-btn'
                : 'input-group-addon';
            $props['addonAfter'] = '<span class="'.$addonClass.'">'.$props['addonAfter'].'</span>';
        }
        // since default attribsInputGroup didn't exist it's a bit cumbersome to merge here
        $propsInputGroup = $this->mergeProps(array(
            array(
                'attribs' => array( 'class' => 'input-group' ), // name, type, & other "global" attributes
            ),
            array(
                'attribs' => isset($props['attribsInputGroup'])
                    ? $props['attribsInputGroup']
                    : array(),
            ),
        ));
        $attribsInputGroup = $propsInputGroup['attribs'];
        return '<div'.Html::buildAttribString($attribsInputGroup).'>'."\n"
                .$props['addonBefore']."\n"
                .$props['input']."\n"
                .$props['addonAfter']."\n"
            .'</div>';
    }

    /**
     * Builds radio controls
     *
     * @return string html
     */
    protected function buildRadio()
    {
        if ($this->props['useFieldset']) {
            // 'auto' || true
            $this->props['template'] = \preg_replace('#^<div([^>]*)>(.+)</div>$#s', '<fieldset$1>$2</fieldset>', $this->props['template']);
            $this->props['template'] = \preg_replace('#<label([^>]*)>(.+)</label>#s', '<legend>$2</legend>', $this->props['template']);
        }
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        $props['input'] = $this->buildCheckboxRadioGroup();
        if ($this->props['tagOnly']) {
            return $this->props;
        } else {
            return Str::quickTemp($props['template'], $props);
        }
    }

    /**
     * [buildSelect description]
     *
     * @return string html
     */
    protected function buildSelect()
    {
        if (!empty($this->props['attribs']['multiple']) && \substr($this->props['attribs']['name'], -2) !== '[]') {
            $this->props['attribs']['name'] .= '[]';
        }
        if ($this->props['attribs']['required'] && !$this->props['attribs']['multiple'] && $this->props['options']) {
            /*
                Check for an empty-value "Select" option
                If no empty option, add one
            */
            $firstValue = $this->props['options'][0]['attribs']['value'];
            $emptyValues = array('','--','select');
            if (!\in_array(\strtolower($firstValue), $emptyValues)) {
                $selectOption = array(
                    'attribs' => array(
                        'value' => '',
                        'disabled' => true,
                        'selected' => empty($this->props['values']),
                    ),
                    'label' => 'Select',
                );
                \array_unshift($this->props['options'], $selectOption);
            }
        }
        // build select input
        $this->props['attribs']['type'] = null;
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        if (empty($props['input'])) {
            $this->props['input'] = Html::buildTag(
                $props['tagname'],
                $props['attribs'],
                "\n".$this->buildSelectOptions($this->props['options'], $this->props['values'])
            );
            $props['input'] = $this->buildInputGroup();
        }
        return $this->props['tagOnly']
            ? $props['input']
            : Str::quickTemp($props['template'], $props);
    }

    /**
     * Build selects option list
     *
     * @param array $options        select options
     * @param array $selectedValues selected options
     *
     * @return string
     */
    protected function buildSelectOptions($options, $selectedValues = array())
    {
        $str = '';
        $inOptgroup = false;
        foreach ($options as $opt) {
            // $str = '';
            if (isset($opt['optgroup'])) {
                if ($inOptgroup) {
                    $str .= '</optgroup>'."\n";
                    $inOptgroup = false;
                }
                if ($opt['optgroup']) {
                    // open an optgroup
                    $attribs = $opt['attribs'];
                    $attribs['label'] = $opt['label'];
                    $str .= '<optgroup'.Html::buildAttribString($attribs).'>'."\n";
                    $inOptgroup = true;
                }
            } else {
                $optAttribs = \array_merge(array(
                    // 'cname' => 'option',
                    // 'innerhtml' => \htmlspecialchars($opt['label']),
                    'selected' => \in_array($opt['attribs']['value'], $selectedValues),
                ), $opt['attribs']);
                // unset($optAttribs['label']);
                $str .= Html::buildTag('option', $optAttribs, \htmlspecialchars($opt['label']))."\n";
            }
            // $this->props['attribs']['innerhtml'] .= $str;
        }
        if ($inOptgroup) {
            // $this->props['attribs']['innerhtml'] .= '</optgroup>';
            $str .= '</optgroup>'."\n";
        }
        return $str;
    }

    /**
     * Build "static" control
     *
     * @return string
     *
     * @link( https://getbootstrap.com/docs/3.3/css/#forms-controls-static
     */
    protected function buildStatic()
    {
        $innerhtml = \htmlspecialchars($this->props['attribs']['value']);
        $this->props['attribs'] = \array_diff_key($this->props['attribs'], \array_flip(array('name','type','value')));
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        if (empty($props['input'])) {
            $props['input'] = Html::buildTag(
                $props['tagname'],
                $props['attribs'],  // already a string
                $innerhtml
            );
            // we don't call $this->buildInputGroup() for static
        }
        return $props['tagOnly']
            ? $props['input']
            : Str::quickTemp($props['template'], $props);
    }

    /**
     * Build a <textarea> control
     *
     * @return string html
     */
    protected function buildTextarea()
    {
        $innerhtml = \htmlspecialchars($this->props['attribs']['value']);
        $this->props['attribs']['type'] = null;
        $this->props['attribs']['value'] = null;
        $attribStrings = $this->buildAttribStrings($this->props);
        $props = \array_merge($this->props, $attribStrings);
        if (empty($props['input'])) {
            $this->props['input'] = Html::buildTag(
                $props['tagname'],
                $props['attribs'],
                $innerhtml
            );
            $props['input'] = $this->buildInputGroup();
        }
        return $props['tagOnly']
            ? $props['input']
            : Str::quickTemp($props['template'], $props);
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
     * Build the html for the given
     *
     * @return string html
     */
    protected function doBuild()
    {
        switch ($this->props['attribs']['type']) {
            case 'checkbox':
                $return = $this->buildCheckbox();
                break;
            case 'html':
                $return = $this->props['attribs']['value'];
                break;
            case 'radio':
                $return = $this->buildRadio();
                break;
            case 'select':
                $return = $this->buildSelect();
                break;
            case 'textarea':
                $return = $this->buildTextarea();
                break;
            case 'button':
            case 'reset':
            case 'submit':
                $return = $this->buildButton();
                break;
            case 'static':
                $return = $this->buildStatic();
                break;
            default:
                $return = $this->buildDefault();
        }
        return $return;
    }

    /**
     * Remove empty tags from string
     *
     * @param string $html html
     *
     * @return string html
     */
    protected function removeEmptyTags($html)
    {
        if (!\is_string($html)) {
            return $html;
        }
        /*
            Toss empty tags such as label or legend
            keep empty textarea
        */
        $html = \preg_replace('#^\s*<(div|label|legend|span)\b[^>]*>\s*</\1>\n#m', '', $html);
        $html = \preg_replace('#^\s*\n#m', '', $html);  // toss empty lines
        $html = \preg_replace('#<(\w+) >#', '<$1>', $html);   // <tab > ->  <tab>
        return $html;
    }

    /**
     * Set ID attributes
     *
     * @param string $id ID
     *
     * @return void
     */
    protected function setIds($id)
    {
        if (!$id) {
            return;
        }
        $this->props = $this->mergeProps(array(
            $this->props,
            array(
                'attribsContainer' => array(
                    'id' => $id.'_container',
                ),
                'attribs' => array(
                    'id' => $id,
                ),
                'attribsLabel' => array(
                    'for' => isset($this->props['attribsLabel']['for'])
                        ? $this->props['attribsLabel']['for']
                        : $id,
                ),
                'attribsHelpBlock' => array(
                    'id' => $id.'_help_block',
                ),
            ),
        ));
    }
}
