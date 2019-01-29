/**
 * Uses pwdMeter to provide visual feedback for
 *   password / repeat-password inputs
 */

function PasswordFeedback(opts) {
	this.pm = new PasswordMeter();
	this.$inputNodes = $();	// init func will set
	this.opts = $.extend({
		selector : 'input[type=password]',
		criteria : ['alpha','length','numeric'],
		/**
		 * use to set initial password meter options
		 */
		init : function(pm) {
			pm.PasswordLength.minimum = 8;
		},
		/**
		 * called after password changes, and evaluated
		 */
		onchange : function(pm, pwd, matchOpacity) {
			var checkmarks = {
					alpha	: pm.LowercaseLetters.status || pm.UppercaseLetters.status,
					length	: pm.PasswordLength.status,
					match	: matchOpacity,
					numeric	: pm.Numerics.status
				},
				k = '',
				v = '',
				$node;
			for (k in checkmarks) {
				v = checkmarks[k];
				$node = $('#criteria .'+k);
				if ( v ) {
					$node.addClass('checked');
					$node.find('.checkbox').css({opacity: v});
				} else {
					$node.removeClass('checked');
				}
			}
		}
	}, opts);
	this.opts.init(this.pm);
	this.init();
	console.log('this', this);
}

/**
 * called on keyup.. check's password score
 */
PasswordFeedback.prototype.evalPasswords = function(index) {
	console.group('PasswordFeedback.evalPasswords');
	var pm			= this.pm,
		init		= index === undefined,
		index_other,
		val = '',
		val_other = '',
		$nodeComplexity = $('#complexity'),
		p = 0,		// match opacity / how closly the two input fields match
		tmp = '';
	if ( init ) {
		index = 0;
	}
	index_other = index === 0 ? 1 : 0;
	val			= this.$inputNodes.eq(index).val();
	val_other	= this.$inputNodes.eq(index_other).val();
	if ( init ) {
		console.log('determine longest');
		if ( val_other.length > val.length ) {
			console.log('val_other is longer');
			tmp = val;
			val = val_other;
			val_other = tmp;
		}
	}
	//
	if ( !val ) {
		console.log('no value, use other');
		val = val_other;
	} else if ( !val_other ) {
		//
	} else if ( val === val_other ) {
		console.log('vals are the same');
		p = 1;
	} else if ( val_other.startsWith(val) || val.startsWith(val_other) ) {
		console.log('vals begin the same');
		p = val.length / val_other.length;
		if ( p > 1 ) {
			p = 1/p;
		}
		p = p * 0.5 + 0.2;
		if ( !init && val_other.length > val.length ) {
			console.log('eval the other (it\'s longer)');
			val = val_other;
		}
	}
	pm.checkPassword(val);
	$('#score').html(pm.int2str(pm.Score.adjusted) + "%");
	$('#scorebar').css({
		backgroundPosition:	'-' + parseInt(pm.Score.adjusted * 4, 10) + 'px 0px'
	});
	if ( pm.Complexity.value === pm.COMPLEXITY.VERYWEAK ) {
		$nodeComplexity.html("Very Weak");
	} else if ( pm.Complexity.value === pm.COMPLEXITY.WEAK ) {
		$nodeComplexity.html("Weak");
	} else if ( pm.Complexity.value === pm.COMPLEXITY.GOOD ) {
		$nodeComplexity.html("Good");
	} else if ( pm.Complexity.value === pm.COMPLEXITY.STRONG ) {
		$nodeComplexity.html("Strong");
	} else if ( pm.Complexity.value === pm.COMPLEXITY.VERYSTRONG ) {
		$nodeComplexity.html("Very Strong");
	}
	this.opts.onchange.call(this, pm, val, p);
	console.groupEnd();
};

PasswordFeedback.prototype.init = function() {
	var self = this;
	$(function() {
		console.log('passwordFeedback.init');
		//var $inputNodes = this.$inputNodes;
		self.$inputNodes = $(self.opts.selector);
		self.evalPasswords();
		self.$inputNodes.each( function(i, node) {
			var $node = $(node);
			$node.on('keyup', function(evt) {
				self.evalPasswords(i);
				var criteria_met = true;
				$.each(self.opts.criteria, function(i, className) {
					//console.log(i, className);
					var $nodeCrit = $('#criteria').find('.'+className);
					if ( !$nodeCrit.length ) {
						return;	// continue
					}
					if ( !$nodeCrit.hasClass('checked') ) {
						criteria_met = false;
						return;
					}
				});
				console.log('criteria_met', criteria_met);
				if ( typeof $node[0].setCustomValidity !== 'undefined' ) {
					$node[0].setCustomValidity(criteria_met ?
						'' :
						'Password criteria not met'
					);
				}
				if ( criteria_met ) {
					$node.removeClass('invalid');
					$node.removeClass('error');
				} else {
					$node.addClass('invalid');
					$node.addClass('error');
				}
			});
		});
	});
};