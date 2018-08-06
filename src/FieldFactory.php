<?php

namespace bdk\Form;

use bdk\Form;
use bdk\Form\BuildControl;

class FieldFactory extends FieldBase
{

	private $buildControl;
	private $form;

	/**
	 * Constructor
	 *
	 * @param BuildControl $buildControl [description]
	 * @param Form         $form         [description]
	 * @param array        $defaultProps [description]
	 */
	public function __construct(BuildControl $buildControl = null, Form $form = null, $defaultProps = array())
	{
		$this->buildControl = $buildControl;
		$this->form = $form;
		/*
		$this->props = self::mergeProps(array(
			$this->props,
			$defaultProps,
		), array('int_keys'=>'replace'));
		*/
		$this->props = $defaultProps;
	}

	public function build($props)
	{
		// $props = $this->moveShortcuts($props);
		/*
		$type = isset($props['attribs']['type']) ? $props['attribs']['type'] : null;
		$typeChanging = $type && (!isset($this->attribs['type']) || $type !== $this->attribs['type']);
		$propsType = $typeChanging && isset($this->defaultPropsType[$type])
			? $this->defaultPropsType[$type]
			: array();
		*/
		$definition = $this->getDefinitionClass($props);
		$class = '\\bdk\\Form\\Field';
		if ($definition) {
			$classes = array(
				$definition,
				'\\bdk\\Form\\fieldDefinitions\\'.\ucfirst($definition),
			);
			foreach ($classes as $classCheck) {
				if (\class_exists($classCheck)) {
					// return new $class($props, $buildControl, $form);
					$class = $classCheck;
					break;
				}
			}
			// $class = \get_called_class();
		}
		/*
		$props = self::mergeProps(array(
			$this->props,
			$propsType,
			$props,
		));
		*/
		$field = new $class($this->props, $this->buildControl, $this->form);
		$field->setProps($props);
		return $field;
	}

	protected function getDefinitionClass($props)
	{
		$props = self::moveShortcuts($props);
		$type = isset($props['attribs']['type']) ? $props['attribs']['type'] : null;
		$propsType = isset($this->defaultPropsType[$type])
			? $this->defaultPropsType[$type]
			: array();
		$definition = null;
		if (isset($props['definition'])) {
			$definition = $props['definition'];
		} elseif (isset($propsType['definition'])) {
			$definition = $propsType['definition'];
		}
		return $definition;
	}
}
