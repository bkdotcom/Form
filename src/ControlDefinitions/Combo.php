<?php

namespace bdk\Form\ControlDefinitions;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlFactory;
use bdk\PubSub\Event;

/**
 * Combo control
 */
class Combo extends Control
{

    protected static $isSubscribed = array();
    protected static $comboed = array();
    protected static $allow = array();
    protected $propKeys = array('comboTemplate');

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), Form $form = null, ControlFactory $controlFactory = null)
    {
        parent::__construct($props, $form, $controlFactory);
        \preg_match_all('/{{\s*(.*?)\s*}}/', $this->props['comboTemplate'], $matches);
        self::$comboed = \array_merge(self::$comboed, $matches[1]);

        $controlBuilder = $controlFactory->controlBuilder;
        $builderHash = \spl_object_hash($controlBuilder);
        if (!\in_array($builderHash, self::$isSubscribed)) {
            $controlBuilder->eventManager->subscribe(
                'form.buildControl',
                array(\get_class($this), 'onBuildControlBefore'),
                1
            );
            $controlBuilder->eventManager->subscribe(
                'form.buildControl',
                array(\get_class($this), 'onBuildControlAfter'),
                0
            );
            self::$isSubscribed[] = $builderHash;
        }
    }

    /**
     * Build control
     *
     * @return string
     */
    public function doBuild()
    {
        $this->debug->groupCollapsed(__METHOD__);
        $firstId = null;
        $inputs = \preg_replace_callback(
            '/{{\s*(.*?)\s*}}/',
            function ($matches) use (&$firstId) {
                $control = $this->form->getControl($matches[1]);
                if (!$firstId) {
                    $firstId = $control->id;
                }
                return $control->build();
            },
            $this->comboTemplate
        );
        $this->props['attribsLabel']['for'] = $firstId;
        $this->props['input'] = $inputs;
        $return = $this->build();
        $this->debug->groupEnd();
        return $return;
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
        \bdk\Debug::_warn('getValRaw');
        $nameBase = $control->attribs['name'];
        $val = $control->form->getValue($nameBase.'A')
            .'-'
            .$control->form->getValue($nameBase.'B');
        return $val;
    }

    /**
     * form.buildControl event subscriber
     *
     * @param Event $event Event instance
     *
     * @return void
     */
    public static function onBuildControlBefore(Event $event)
    {
        // \bdk\Debug::_info('definition', $event->getSubject()->definition);
        if ($event->getSubject() instanceof self) {
            \preg_match_all('/{{\s*(.*?)\s*}}/', $event->getSubject()->comboTemplate, $matches);
            self::$allow = \array_merge(self::$allow, $matches[1]);
        }
    }

    /**
     * form.buildControl event subscriber
     *
     * @param Event $event Event instance
     *
     * @return void
     */
    public static function onBuildControlAfter(Event $event)
    {
        $name = $event->getSubject()->attribs['name'];
        if (\in_array($name, self::$comboed)) {
            if (\in_array($name, self::$allow)) {
                $key = \array_search($name, self::$allow);
                unset(self::$allow[$key]);
            } else {
                $event['return'] = '';
            }
        }
    }

    /**
     * [setValRaw description]
     *
     * @param Control $control control instance
     * @param mixed   $value   control's value
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

    /**
     * {@inheritdoc}
     */
    protected function getDefaultProps($type)
    {
        return array(
            'attribsControls' =>array(
                'class' => 'form-inline',
            ),
            'comboTemplate' => '',
        );
    }
}
