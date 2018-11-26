<?php

namespace bdk\Form\ControlDefinitions;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlBuilder;
use bdk\PubSub\Event;

/**
 * Combo control
 */
class Combo extends Control
{

    protected static $isSubscribed = false;
    // protected static

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), ControlBuilder $controlBuilder = null, Form $form = null)
    {
        if (!self::$isSubscribed) {
            $controlBuilder->eventManager->subscribe('form.buildControl', array(\get_class($this), 'onBuildControl'));
            self::$isSubscribed = true;
        }
        $props = $this->mergeProps(array(
            array(
                'attribsControls' =>array(
                    'class' => 'form-inline',
                ),
                'comboTemplate' => ''
            ),
            $props,
        ));
        parent::__construct($props, $controlBuilder, $form);
    }

    /**
     * Build control
     *
     * @return string
     */
    public function doBuild()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $controlBuilder = $this->form->controlBuilder;
        /*
        $defaultFieldProps = $this->form->cfg['field'];
        $val = $this->val();
        $valParts = \explode('-', $val);
        $nameBase = $this->attribs['name'];
        $inputs = $controlBuilder->build(ArrayUtil::mergeDeep($defaultFieldProps, array(
            'name' => $nameBase.'A',
            'value' => $valParts[0],
            'tagOnly' => true,
        )));
        $inputs .= ' - ';
        $inputs .= $controlBuilder->build(ArrayUtil::mergeDeep($defaultFieldProps, array(
            'name' => $nameBase.'B',
            'value' => $valParts[1],
            'tagOnly' => true,
        )));
        */

        $firstId = null;
        $inputs = \preg_replace_callback(
            '/{{\s*(.*?)\s*}}/',
            function ($matches) use (&$firstId) {
                $this->debug->log('build combo '.$matches[1]);
                $control = $this->form->getControl($matches[1]);
                if (!$firstId) {
                    $firstId = $control->id;
                }
                return $control->build();
            },
            $this->comboTemplate
        );

        $props = $this->props;
        $props['attribsLabel']['for'] = $firstId;
        $props['input'] = $inputs;
        $this->debug->groupEnd();
        return $controlBuilder->build($props);
    }

    /**
     * Get formated value
     *
     * @param Control $control instance
     *
     * @return string
     */
    public function getValRaw(Control $control)
    {
        $debug = \bdk\Debug::getInstance();
        $debug->warn('getValRaw');
        $nameBase = $control->attribs['name'];
        $val = $control->form->getValue($nameBase.'A')
            .'-'
            .$control->form->getValue($nameBase.'B');
        return $val;
    }

    public static function onBuildControl(Event $event)
    {
        // \bdk\Debug::_warn(__METHOD__, $event->getSubject()->attribs['name']);
    }

    /**
     * [setValRaw description]
     *
     * @param Control $control control instance
     * @param mixed   $value control's value
     *
     * @return void
     */
    public function setValRaw(Control $control, $value)
    {
        list($valA, $valB) = \explode('-', $value);
        $nameBase = $control->attribs['name'];
        $control->form->setValue($nameBase.'A', $valA);
        $control->form->setValue($nameBase.'B', $valB);
    }
}
