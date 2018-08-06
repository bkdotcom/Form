<?php

namespace bdk\Form;

interface FieldDefinitionInterface
{

	/**
	 * Returns default properties & attributes
	 *
	 * @return array
	 */
	public function getDefaultProps();

	/**
	 * Get formated value
	 *
	 * @param object $field instance
	 *
	 * @return string
	 */
	public function getValFormatted($field);

	/**
	 * Validate field
	 *
	 * @param object $field instance
	 *
	 * @return boolean
	 */
	public function validate($field);
}
