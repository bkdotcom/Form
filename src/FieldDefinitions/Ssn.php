<?php

namespace bdk\Form\FieldDefinitions;

use bdk\Form;
use bdk\Form\BuildControl;
use bdk\Form\Field;

/**
 * Social Security Number
 */
class Ssn extends Field
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribs' => array(
                    'autocomplete'  => 'off',
                    'pattern'       => '\d{3}[\. -]?\d{2}[\. -]?\d{4}',
                    'placeholder'   => 'nnn-nn-nnnn',
                    'size'          => 11,
                    'title'         => 'SSN: nnn-nnnn',
                ),
                'invalidReason' => 'Must be formatted nnn-nn-nnnn',
            ),
            $props,
        ));
        parent::__construct($props, $buildControl, $form);
    }

    /**
     * Validate field
     *
     * @return boolean
     */
    public function doValidate()
    {
        $isValid = false;
        $answer = $this->valRaw();
        // http://en.wikipedia.org/wiki/Social_Security_number#Valid_SSNs
        // http://www.irs.gov/businesses/small/international/article/0,,id=96696,00.html#itin
        if (\preg_match('|^(\d{3})[\. -]?(\d{2})[\. -]?(\d{4})$|', $answer, $matches)) {
            list($area, $group, $serial) = \array_slice($matches, 1);
            if ($area >= 734 && $area <= 749 || $area > 772 && $area < 900 || $area == '666') {
                $isValid = false;
            } elseif ($area == '000' || $group == '00' || $serial == '0000') {
                $isValid = false;
            } elseif ($area == 987 && $group == 65 && \in_array($serial, \range(4320, 4329))) {
                $isValid = false;
            } else {
                $isValid = true;
            }
        }
        return $isValid;
    }

    /**
     * Get formated value
     *
     * @param object $field instance
     *
     * @return string
     */
    public function getValFormatted($field)
    {
        $val = $field->valRaw();
        if (\preg_match('|^(\d{3})[\. -]?(\d{2})[\. -]?(\d{4})$|', $val, $matches)) {
            list($area, $group, $serial) = \array_slice($matches, 1);
            return $area.'-'.$group.'-'.$serial;
        }
        return null;
    }
}
