<?php

namespace bdk\Form;

/**
 * Generate javascript to enforce/update requirement dependencies
 */
class OutputScript
{

	private $debug;
	private $form;
	private $regExOther = '#{{([^}\s]+[\.\/][^}\s]+)}}#';
	private $regExThis = '#{{([^}\s\.\/]+)}}#';
	private $trigs = array();

	/**
	 * Constructor
	 *
	 * @param \bdk\Form $form form instance
	 */
	public function __construct($form)
	{
		$this->debug = \bdk\Debug::getInstance();
		$this->form = $form;
	}

	/**
	 * Build form form-specific javascript
	 * Script updates require attribute if req is dependant on other fields
	 *
	 * @return string
	 */
	public function build()
	{
		$this->debug->groupCollapsed(__METHOD__);
		$str = '';
		$strFunctions	= '';
		$strListen		= '';
		$strOnload		= '';
		// $formId = $this->form->cfg['attribs']['id'];
		/*
		foreach ($fields as $field) {
			if (\in_array($field->attribs['type'], array('submit', 'image'))
				&& !empty($field->attribs['name'])
				&& $field->attribs['name'] != 'submit'
				&& !empty($field->attribs['value'])
			) {
				$strObserve .= "\t".'addEvent("'.$field->attribs['id'].'", "click", function(evt){'."\n"
					."\t\t".'node = evt.srcElement ? evt.srcElement : evt.target;'."\n"
					.( $field->attribs['type'] == 'image'
						? ''
							."\t\t".'var pos_x = evt.offsetX ? evt.offsetX : evt.pageX-node.offsetLeft;'."\n"
							."\t\t".'var pos_y = evt.offsetY ? evt.offsetY : evt.pageY-node.offsetTop;'."\n"
							."\t\t".'if ( pos_x >= 0 )'."\n"
							."\t\t".'{'."\n"
							."\t\t\t".'set_value("'.$field->attribs['name'].'.x", pos_x, node.form);'."\n"
							."\t\t\t".'set_value("'.$field->attribs['name'].'.y", pos_y, node.form);'."\n"
							."\t\t".'}'."\n"
							.''
						: ''
					)
					."\t\t".'set_value("'.$field->attribs['name'].'", node.value, node.form);'."\n"
					."\t\t".'submitTimeout = setTimeout(function(){'."\n"
						."\t\t\t".'set_value("'.$field->attribs['name'].'", "", node.form);'."\n"	// reset
					."\t\t".'}, 2000);'."\n"
				."\t".'});'."\n";
			}
		}
		*/
		$trigs = $this->getTriggers();
		$this->debug->log('trigs', $trigs);
		foreach ($trigs as $trigName => $trig) {
			$strFunc = $this->buildOnChangeFunc($trig);
			$strFunctions .= $strFunc."\n";
			$strOnload .= "\t".$trig['funcName']."();\n";
			$trigFields = $this->getFieldsWithName($trigName);
			foreach ($trigFields as $field) {
				// Listen to this field && call func when changed
				$strListen .= $this->buildListen($field, $trig['funcName'])."\n";
			}
		}
		if (!empty($strFunctions) || !empty($strListen)) {
			$str = '<script type="text/javascript">'."\n"
				.'//<![CDATA['."\n"
				.'$(function(){'."\n"
					.'BDKForm.init("#'.$this->form->cfg['attribs']['id'].'");'."\n"
					.$strFunctions
					.$strListen
					.'$(window).on("focus", function() {'."\n"
						// .'console.log("window focused... re calculating required");'
						.$strOnload
					.'});'."\n"
				.'});'."\n"
				.'//]]>'."\n"
				.'</script>';
		}
		$this->debug->log('str!!', $str);
		$this->debug->groupEnd();
		return $str;
	}

	/**
	 * Buiild isRequired script for given trigger field
	 *
	 * @param string          $strJs     php string to be converted to JS
	 * @param \bdk\Form\Field $trigField field instance
	 *
	 * @return string javascirpt snippet
	 */
	private function buildIsReq($strJs, $trigField)
	{
		$trigName = $trigField->attribs['name'];
		$strJs = \str_replace('{{'.$trigName.'}}', 'val', $strJs);
		$strJs = \preg_replace_callback($this->regExOther, array($this, 'replaceOther'), $strJs);
		$strJs = \preg_replace_callback($this->regExThis, array($this, 'replaceCurrent'), $strJs);
		$strJs = \preg_replace(
			'#in_array\('
				.'(.*?),\s*array\((.*?)\)'
				.'\)#',
			'BDKForm.inArray($1, [$2])',
			$strJs
		);
		$strJs = \str_replace('is_numeric', 'parseFloat', $strJs);
		if ($trigField->returnArray) {
			// not checking for a specific value...
			$strJs = \preg_replace('/(^|[\s!])val(\s|$)/', '$1val.length$2', $strJs);
		}
		return $strJs;
	}

	/**
	 * [buildListen description]
	 *
	 * @param \bdk\Form\Field $field    field instance
	 * @param string          $funcName onChange function name
	 *
	 * @return string
	 */
	private function buildListen($field, $funcName)
	{
		$strJs = '';
		$fieldId = $field->id;
		if (\in_array($field->attribs['type'], array('checkbox','radio'))) {
			foreach (\array_keys($field->props['options']) as $k) {
				// just capture click (not change) because IE 7 & below doesn't fire change until blur
				$strJs = '$("#'.$fieldId.'_'.$k.'").on("click", function(){ '
					.'document.getElementById("'.$fieldId.'_'.$k.'").blur();'
					.$funcName.'();'
				.'});';
			}
		} else {
			$event = 'change';
			if ($field->attribs['type'] == 'submit') {
				$event = 'click';
			} elseif ($field->attribs['type'] == 'text') {
				$event = 'keyup';
			}
			$strJs .= '$("#'.$fieldId.'").on("'.$event.'", '.$funcName.');';
		}
		return $strJs;
	}

	/**
	 * [trigJsFunction description]
	 *
	 * @param array $info trigger info
	 *
	 * @return string
	 */
	private function buildOnChangeFunc($info)
	{
		// $funcName = \preg_replace('/\W+/', '_', 'changed_'.$formId.'_'.$trigName);

		// $fieldName = $field->attribs['name'];
		/*
		if ($field->returnArray && \strpos($fieldName, '[]') == false) {
			$fieldName .= '[]';
		}
		*/
		$strFunc = 'function '.$info['funcName'].'() {'."\n";
		$strFunc .= "\t".'var val = BDKForm.getValue("'.$info['selector'].'"),'."\n"
			."\t\t".'req;'."\n";
		// $strFunc .= "\t".'console.log("'.$info['funcName'].'()", val);'."\n";
		// $strFunc .= "\t".'console.log("req/changed:", "'.$field_name.'");'."\n";
		// $strFunc .= "\t".'console.log("val", val);'."\n";
		foreach ($info['check'] as $check) {
			$strFunc .= "\t".'req = ( '.$check['isReq'].' );'."\n"
				// ."\t".'console.log("req", "'.$check['selector'].'", req);'."\n"
				."\t".'BDKForm.setRequired("'.$check['selector'].'", req);'."\n";
		}
		$strFunc .= '}';
		return $strFunc;
	}

	/**
	 * Get all form fields having given name
	 *
	 * @param string $name field name
	 *
	 * @return array
	 */
	private function getFieldsWithName($name)
	{
		$trigFields = array();
		$fields = &$this->form->currentFields;
		if (isset($fields[$name])) {
			$trigFields[] = $fields[$name];
		} else {
			foreach ($fields as $f) {
				if ($f->attribs['name'] == $name) {
					$trigFields[] = $f;
				}
			}
		}
		return $trigFields;
	}

	/**
	 * [getSelector description]
	 *
	 * @param string|object $field field name or field obj
	 *
	 * @return string css selector
	 */
	private function getSelector($field)
	{
		// $this->debug->warn(__METHOD__, $field);
		if (\is_string($field)) {
			if (isset($this->trigs[$field]['selector'])) {
				return $this->trigs[$field]['selector'];
			}
			// $this->debug->warn('getSelector... finding fieldsWithName('.$field.')');
			$fields = $this->getFieldsWithName($field);
			$field = $fields[0];
		}
		if (\in_array($field->attribs['type'], array('checkbox','radio','submit'))) {
			$formId = $this->form->cfg['attribs']['id'];
			$selector = '#'.$formId.' input[name=\"'.$field->attribs['name'].'\"]';
		} else {
			$fieldId = $field->id;
			$selector = '#'.$fieldId;
		}
		return $selector;
	}

	/**
	 * [getTriggers description]
	 *
	 * @return [type] [description]
	 */
	private function getTriggers()
	{
		$this->debug->groupCollapsed(__METHOD__);
		$formId = $this->form->cfg['attribs']['id'];
		$fields = &$this->form->currentFields;
		$trigs = array();
		foreach ($fields as $field) {
			if (\is_string($field->attribs['required'])) {
				$this->debug->info($field->attribs['name'].' is required when', $field->attribs['required']);
				// $fieldId = $field->id;
				$fieldName = $field->attribs['name'];
				$str = $field->attribs['required'];
				\preg_match_all($this->regExThis, $str, $matches);
				$trigNames = \array_unique($matches[1]);
				/*
					"copy" the require-if string to each of the trigger-names to be evaled when
					the trigger value changes
				*/
				foreach ($trigNames as $trigName) {
					$this->debug->log('trigName', $trigName);
					$trigFields = $this->getFieldsWithName($trigName);
					$trigField = $trigFields[0];
					if (!isset($trigs[$trigName])) {
						$trigs[$trigName] = array(
							'type' => $trigField->attribs['type'],
							/*
							'id' => $trigField->attribs['type'] == 'submit'
								? $this->form->cfg['attribs']['name'].'_'.$trigName.'_value'
								: $trigField->id,
							*/
							'selector' => $this->getSelector($trigField),
							'funcName' => \preg_replace('/\W+/', '_', 'changed_'.$formId.'_'.$trigName),
							'check' => array(),
						);
					}
					$trigs[$trigName]['check'][$fieldName] = array(
						// 'id' => $fieldId,
						'selector' => $this->getSelector($field),
						'isReq' => $this->buildIsReq($str, $trigField),
					);
				}
			}
		}
		$this->trigs = $trigs;
		$this->debug->groupEnd();
		return $trigs;
	}

	/**
	 * replace field tokens occring in current form page
	 *
	 * @param array $matches matched strings
	 *
	 * @return string
	 */
	private function replaceCurrent($matches)
	{
		$fieldName = $matches[1];
		/*
		$formId = $this->form->cfg['attribs']['id'];
		if (isset($this->form->currentFields[$fieldName])) {
			$selector = '#'.$formId.' '.$this->form->currentFields[$fieldName]->id;
		} else {
			$selector = '#'.$formId.' '.$this->form->cfg['attribs']['name'].'_'.$fieldName.'_value';
		}
		*/
		return 'BDKForm.getValue("'.$this->getSelector($fieldName).'")';
	}

	/**
	 * replace field tokens occring in non-current form page
	 *
	 * @param array $matches matched strings
	 *
	 * @return string
	 */
	private function replaceOther($matches)
	{
		return '"'.\addslashes($this->form->getValue($matches[1])).'"';
	}
}
