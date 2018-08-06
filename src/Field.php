<?php

namespace bdk\Form;

use bdk\ArrayUtil;
use bdk\Form;
use bdk\Form\BuildControl;

class Field extends FieldBase
{

	protected $debug;
	protected $buildControl;
	protected $form;
	protected $callStack = array();

	/**
	 * @var $idCounts keep track of ids generated across all forms
	 */
	private static $idCounts = array();

	/**
	 * Constructor
	 *
	 * @param array        $props        field properties/definition
	 * @param BuildControl $buildControl BuildControl instance
	 * @param Form   	   $form         parent form object]
	 */
	public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
	{
		$this->debug = \bdk\Debug::getInstance();
		$this->buildControl = $buildControl;
		$this->form = $form;
		$this->setProps($props);
		$this->attribs = &$this->props['attribs'];
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
		} elseif (\in_array($key, array('form','props'))) {
			return $this->{$key};
		} elseif (\array_key_exists($key, $this->props)) {
			return $this->props[$key];
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
		$this->props[$key] = $value;
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
		$this->debug->info(__METHOD__, $this->attribs['name']);
		if ($propOverride == 'tagOnly') {
			$propOverride = array('tagOnly' => true);
		} elseif (!\is_array($propOverride)) {
			$this->debug->warn('invalid propOverride', $propOverride, 'expect array or "tagOnly"');
			$propOverride = array();
		}
		if ($propOverride) {
			$this->props = $this->mergeProps(array(
				$this->props,
				$propOverride,
			));
		}
		/*
		if (\is_callable($this->props['build'])) {
			return $this->props['build']($this);
		} else {
			// $props = \unserialize(\serialize($this->props));	// copy sans references
			// $props['attribs']['required'] = $this->isRequired();
			// $props['attribs']['id'] = $this->getId();	// get id here... else static counter updated but field is not
			$this->debug->log('this', $this);
		}
		*/
		// $this->debug->log('this', $this);
		/*
		$opts = array(
			'doBuild' => !\in_array('build', $this->callStack),
		);
		*/
		$return = $this->buildControl->build($this);
		return $return;
	}

	/**
	 * Clear the static ID counter that ensures fields have unique IDs
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
	 * Build a field object
	 * Instanciates proper field class
	 *
	 * @param array        $props        field properties/definition
	 * @param BuildControl $buildControl BuildControl instance
	 * @param Form 		   $form		 Form instance
	 *
	 * @return Field
	 */
	/*
	public static function factory($props, BuildControl $buildControl = null, Form $form = null)
	{
	}
	*/

	/**
	 * Flag a field as invalid
	 *
	 * @param string $reason The reason that will be given to the user
	 *
	 * @return void
	 */
	public function flag($reason = null)
	{
		$this->props['isValid'] = false;
		$this->classAdd($this->props['attribsContainer'], 'has-error');
		if ($this->attribs['type'] == 'file' && isset($this->form->currentValues[ $this->attribs['name'] ])) {
			// $this->debug->log('unset the file');
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
			$this->attribs['autofocus'] = true;
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
	 * @return string|null u\Unique ID
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
	 * Is field required?
	 *
	 * @return boolean
	 */
	public function isRequired()
	{
		$isRequired = $this->attribs['required'];
		if (\is_string($isRequired)) {
			// $this->debug->warn('isRequired', $isRequired);
			$replaced = \preg_replace('/{{(.*?)}}/', '$this->form->getField("$1")->val()', $isRequired);
			$evalString = '$isRequired = (bool) '.$replaced.';';
			eval($evalString);
		}
		return $isRequired;
	}

	/**
	 * Set field properties
	 *
	 * @param array $props [description]
	 *
	 * @return void
	 */
	public function setProps($props = array())
	{
		// $this->debug->groupCollapsed(__METHOD__);
		$props = $this->moveShortcuts($props);
		// $this->debug->warn('props', $props);
		$type = isset($props['attribs']['type']) ? $props['attribs']['type'] : null;
		$typeChanging = $type && (!isset($this->attribs['type']) || $type !== $this->attribs['type']);
		$propsType = $typeChanging && isset($this->defaultPropsType[$type])
			? $this->defaultPropsType[$type]
			: array();
		/*
		$definition = null;
		if (isset($props['definition'])) {
			$definition = $props['definition'];
		} elseif (isset($propsType['definition'])) {
			$definition = $propsType['definition'];
		}
		*/
		// $this->debug->warn('props', $props);
		$this->props = $this->mergeProps(array(
			$this->props,
			$propsType,
			// $this->getDefinitionProps($definition),
			$props,
		));
		if (empty($this->props['attribs']['x-moz-errormessage']) && !empty($this->props['invalidReason'])) {
			$this->props['attribs']['x-moz-errormessage'] = $this->props['invalidReason'];
		}
		$this->attribs = &$this->props['attribs'];
		$this->getId();
		if (\in_array($type, array('checkbox','radio','select'))) {
			$this->normalizeCrs();
		} elseif ($type == 'submit' && empty($props['label']) && !empty($props['attribs']['value'])) {
			$props['label'] = $props['attribs']['value'];
		}
		// unset($this->props['definition']);
		// $this->debug->groupEnd();
	}

	/**
	 * Set field's value
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
			$this->attribs['value'] = $value;
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
	 * Get or set field's value
	 * If getting, will return
	 *    + formatted value if field has getValFormatted callable
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
	 * Return field's raw (user input) value
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
	 * @return [type] [description]
	 */
	public function validate()
	{
		$this->debug->groupCollapsed(__METHOD__, $this->attribs['name']);
		$this->props['isValid'] = true;
		$this->classRemove($this->props['attribsContainer'], 'has-error');
		$attribs = $this->attribs;
		// $this->debug->log('props', $this->props);
		if (\in_array($attribs['type'], array('checkbox','radio','select'))) {	// these types use the 'options' array
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
		if ($this->props['isValid']
			// && ($attribs['value'] || \is_string($attribs['value']) && \strlen($attribs['value']))
		) {
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
	 * Normalize Checkbox/Radio/Select properties
	 *
	 * @return void
	 */
	protected function normalizeCrs()
	{
		$type = $this->props['attribs']['type'];
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
				$option = $this->moveShortcuts($option);
				if (!isset($option['label']) && isset($option['attribs']['value'])) {
					$option['label'] = $option['attribs']['value'];
				}
				$this->props['options'][$key] = $option;
			}
		}
		if (\in_array($type, array('checkbox','select')) && !isset($this->props['values'])) {
			/*
				values may be passed as an alternate way of specifying which values are checked/selected
				single checkbox:
					passing 'value' is considered the option (not user supplied value) unless options array is non-empty
			*/
			$this->props['values'] = isset($this->attribs['value'])
				? (array) $this->attribs['value']
				: array();
			unset($this->attribs['value']);
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
		$nonEmpty	= array();
		$enabled	= array();
		$required	= array();
		$optsValid	= array();
		foreach ($this->props['options'] as $opt) {
			if (empty($opt['attribs']['disabled']) && !isset($opt['optgroup'])) {
				// $this->debug->log('opt', $opt);
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
		if (($this->isRequired() || !empty($required) ) && !empty($enabled) && empty($nonEmpty)) {
			$this->debug->log('required, enabled, & no value!');
			$this->props['isValid'] = false;
		}
	}
}
