<?php

namespace bdk\Form\FieldDefinitions;

use bdk\Form;
use bdk\Form\Field;
use bdk\Form\BuildControl;

/**
 * Credit Card
 */
class Creditcard extends Field
{

	/**
	 * {@inheritDoc}
	 */
	public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
	{
		$props = $this->mergeProps(array(
			array(
				'attribs' => array(
					'autocomplete'	=> 'off',
					'data-lpignore'	=> true,	// no LastPass icon
					'maxlength'		=> 19,
					'pattern'		=> '((4\d{3}|5[1-5]\d{2}|6011)([- ]?\d{4}){3}|3[47]\d{2}[- ]?\d{6}[- ]?\d{5})',	// visa/mastercard/discover & AmEx
					'placeholder'	=> 'nnnn-nnnn-nnnn-nnnn',
					'size'			=> 18,
					'title'			=> 'nnnn-nnnn-nnnn-nnnn',
				),
				'invalidReason' => 'Must be a valid credit card #',
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
		$this->debug->warn(__METHOD__);
		$isValid = false;
		$digits = \preg_replace('/\D/', '', $this->attribs['value']);	// remove all non-digits
		$digits = \str_split($digits);
		// $this->debug->log('digits', \implode('', $digits));
		$weight = \count($digits)%2 == 0
			? 2
			: 1;
		$sum = 0;
		while ($digits) {
			// $digit = \substr($digits, 0, 1);
			// $digits = \substr($digits, 1);
			$digit = \array_shift($digits);
			// $this->debug->log('digits', \implode('', $digits));
			$val = $digit * $weight;
			if ($val>9) {
				$val -= 9;
			}
			$sum += $val;
			$weight = ($weight==2) ? 1 : 2;
		}
		if ($sum%10 == 0) {
			$isValid = true;
		}
		$this->debug->log('isValid', $isValid);
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
		$digits = \preg_replace('/\D/', '', $field->attribs['value']);	// remove all non-digits
		return \implode('-', \str_split($digits, 4));
		// $return = \implode('-', \sscanf($answer, '%4s%4s%4s%4s'));
	}
}
