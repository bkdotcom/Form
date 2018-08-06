console.warn('Form.jquery.js');

/**
 * enhanceSubmit
 *   + adds working "spinner" and disables buttons when submitting
 *   + prevents double submitting
 *
 *   $("form").enhanceSubmit();
 */
$.fn.enhanceSubmit = function(method) {
	method = method || "init";
	console.warn('enhanceSubmit', method);
	var fontAwesomeCss = "//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css";
	var submitActor = null;	// which submit btn was clicked (if any)
	var methods = {
		addFontAwesome: function() {
			var span = document.createElement('span');
			span.className = 'fa';
			span.style.display = 'none';
			document.body.insertBefore(span, document.body.firstChild);
			if (window.getComputedStyle(span).getPropertyValue("font-family") !== 'FontAwesome') {
				console.log("adding fontAwesome");
				$("<link/>", { rel: "stylesheet", href: fontAwesomeCss }).appendTo("head");
			}
			document.body.removeChild(span);
		},
		onBtnClick: function() {
			console.info('onBtnClick', this);
			var supportsValidity = "checkValidity" in this.form;
			// console.log('supportsValidity', supportsValidity);
			submitActor = null;
			if ($(this).prop("type") == "submit") {
				submitActor = this;
				if (supportsValidity && !this.form.checkValidity()) {
					// invalid form
					submitActor = null;
				}
			}
			// @todo set a timer to clear submitActor ?
		},
		/**
		 * Note: onSubmit is not triggered if form is invalid
		 */
		onSubmit: function(evt, data) {
			console.info('onSubmit');
			var $hiddenField,
				$form = $(this),
				data = data || {};
			console.info("form submitted");
			if ($(this).hasClass("submitting") && !data.force) {
				evt.preventDefault();
			}
			$form.addClass("submitting");
			if (submitActor && $(submitActor).prop("name")) {
				// store btn's value in hidden field
				console.log("store clicked submit button's value in hidden field named " + $(submitActor).prop("name"));
				$hiddenField = $form.find("input[type=hidden][name="+ $(submitActor).prop("name") +"]");
				if (!$hiddenField.length) {
					console.info('creating hidden field');
					$hiddenField = $("<input>", {
						id: $form.prop('id')+'-submit-actor',
						type: "hidden",
						name: $(submitActor).prop("name")
					});
					console.log("hiddenField", $hiddenField);
					$hiddenField.appendTo($form);
				}
				$hiddenField.val( $(submitActor).val() );
			}
			$form.find("button, input[type=submit]").each(function(){
				if ($(this).prop("disabled")) {
					// already disabled
					return;
				}
				$(this).prop("disabled", true);
				$(this).data("tempSubmitDisabled", true);
				if (this == submitActor) {
					console.log('submitActor', submitActor);
					methods.spinShow(this);
				}
			});
			submitActor = null;
		},
		spinShow: function (node) {
			console.log('spinShow', node);
			$(node).find("i").hide();
			$(node).prepend('<i class="fa fa-spinner fa-pulse"></i> ');
		},
		spinHide: function (node) {
			$(node).find(".fa-spinner").remove();
			$(node).find("i").show();
		},
		unSubmit: function($form) {
			console.info('unsubmitting');
			$form.removeClass("submitting");
			$form.find("button, input[type=submit]").each(function(){
				if (!$(this).prop("disabled")) {
					return;
				}
				if (!$(this).data('tempSubmitDisabled')) {
					return;
				}
				$(this).prop("disabled", false);
				$(this).data('tempSubmitDisabled', false);
				if ($(this).find('.fa-spinner').length) {
					methods.spinHide(this);
				}
			});
		},
		init: function ($form) {
			console.warn('enhancesubmit(init) form listening for submit');
			$form.on("submit", this.onSubmit);
			$form.on("click", "button, input[type=submit]", this.onBtnClick);
			this.addFontAwesome();
		}
	}
	if (typeof methods[method] == "function") {
		methods[method]( this );
	} else {
		console.warn("no such method", method);
	}
	return this;
};

BDKForm = {};

BDKForm = (function(module, $) {

	module.init = function($form) {
		$form = $($form);
		console.info("BDKForm.init");
		// console.log('this', this);
		// console.log('module', module);
		this.useHiddenSubmit($form);
	};

	module.useHiddenSubmit = function($form) {
		console.info("useHiddenSubmit", $form);
		var self = this;
			names = [];
		// console.log('$form', $form);
		$form = $($form);
		$form.on("click", "button, input[type=submit]", function(){
			var $node = $(this),
				name = $node.prop("name") || $node.data("name"),
				val = $node.prop("value"),
				type = $node.prop("type"),
				$hidden;
			if (type != "submit" || !name || !val) {
				return;	// no-name field -> who cares
			}
			$hidden = $form.find("input[type=hidden]").filter(function(){
				return this.name == name;
			});
			if (!$hidden.length) {
				console.log('adding hidden field', name);
				$hidden = $('<input />', {
					type: "hidden",
					name: name
				});
				$form.append($hidden);
			}
			// move name attribute to data so value doesn't get submitted
			$node.prop("name", "").data("name", name);
			$hidden.val(val);
		});
	}

	/**
	 * Get value of specified input
	 *
	 * checkbox groups return array/list of checked values
	 * single checkbox returns string | null
	 * radio group returns string | null
	 * other field types string | null
	 *
	 * @param jquery object/selector selector
	 *
	 * @return mixed
	 */
	module.getValue = function(selector) {
		// console.log('getValue(' + selector + ')');
		var $node = $(selector),
			type = $node.prop('type'),
			val = null;
		if (type == 'checkbox') {
			val = [];
			$node.each(function(){
				if (this.checked) {
					val.push(this.value);
				}
			});
			if ($node.length == 1) {
				val = val.length ? val[0] : null;
			}
		} else if (type == 'radio') {
			$node = $node.filter(":checked");
			if ($node.length) {
				val = $node.val();
			}
		} else {
			val = $node.val();
		}
		// console.log('val', val);
		return val;
	};

	/*
	module.setValue = function(selector, value) {
		var $node = $(selector),
			type = $node.prop('type');
		if (type == 'checkbox') {
			console.log('checkbox');
			val = [];
			$node.each(function(){
				if (this.checked) {
					val.push(this.value);
				}
			});
		}
	};
	*/

	module.inArray = function(val, array) {
		return $.inArray(val, array) > -1;
	};

	module.setCaretTo = function(node, pos) {
		if (node.createTextRange) {
			var range = node.createTextRange();
			range.move("character", pos);
			range.select();
		} else if (typeof node.selectionStart === 'number') {
			node.focus();
			node.setSelectionRange(pos, pos);
		}
	};

	/**
	 * set required property
	 *
	 * @todo update class names
	 */
	module.setRequired = function(selector, isReq) {
		//console.log('set_required',form_id,name,req);
		$(selector).prop('required', isReq);
	};

	return module;

}(BDKForm || {}, jQuery));

/*
$(function(){
	console.info('Form.jquery domLoaded');
	// BDKForm.init();
});
*/
$("form.enhance-submit").enhanceSubmit();
