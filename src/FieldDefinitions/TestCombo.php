<?php

namespace bdk\Form\FieldDefinitions;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\BuildControl;
use bdk\Form\Field;

/**
 * Telephone Number
 */
class TestCombo extends Field
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribsControls' =>array(
                    'class' => 'form-inline',
                ),
            ),
            $props,
        ));
        parent::__construct($props, $buildControl, $form);
    }

    /**
     * Build field control
     *
     * @return string
     */
    public function doBuild()
    {
        $buildControl = $this->form->buildControl;
        $defaultFieldProps = $this->form->cfg['field'];
        $val = $this->val();
        $valParts = \explode('-', $val);
        $nameBase = $this->attribs['name'];
        $inputs = $buildControl->build(ArrayUtil::mergeDeep($defaultFieldProps, array(
            'name' => $nameBase.'A',
            'value' => $valParts[0],
            'tagOnly' => true,
        )));
        $inputs .= ' - ';
        $inputs .= $buildControl->build(ArrayUtil::mergeDeep($defaultFieldProps, array(
            'name' => $nameBase.'B',
            'value' => $valParts[1],
            'tagOnly' => true,
        )));
        $props = $this->props;
        $props['input'] = $inputs;
        $output = $buildControl->build($props);
        return $output;
    }

    /**
     * Get formated value
     *
     * @param object $field instance
     *
     * @return string
     */
    public function getValRaw($field)
    {
        $debug = \bdk\Debug::getInstance();
        $debug->warn('getValRaw');
        $nameBase = $field->attribs['name'];
        $val = $field->form->getValue($nameBase.'A')
            .'-'
            .$field->form->getValue($nameBase.'B');
        return $val;
    }

    /**
     * [setValRaw description]
     *
     * @param string $field field object
     * @param mixed  $value field's value
     *
     * @return void
     */
    public function setValRaw($field, $value)
    {
        list($valA, $valB) = \explode('-', $value);
        $nameBase = $field->attribs['name'];
        $field->form->setValue($nameBase.'A', $valA);
        $field->form->setValue($nameBase.'B', $valB);
    }
}
