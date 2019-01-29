var pm = new PasswordMeter();
var pwd_meter_selector = 'input[type=password]';
var pwd_criteria = ['alpha','length','numeric'];
var pwdOnChange = function(pwd,match_opacity) {
	console.debug('pwdOnChange');
	var checkmarks = $H({
		length	: pm.PasswordLength.status,
		alpha	: pm.UppercaseLetters.status||pm.LowercaseLetters.status,
		numeric	: pm.Numerics.status,
		match	: match_opacity
	});
	checkmarks.each( function(pair) {
		var node = $$('#criteria .'+pair.key)[0];
		if ( pair.value ) {
			node.addClassName('checked');
			node.down('.checkbox').setOpacity(pair.value);
		}
		else
			node.removeClassName('checked');
	});
};

document.observe('dom:loaded',function() {
	console.debug('dom:loaded');
	var password_nodes = $$(pwd_meter_selector);
	console.debug('password_nodes',password_nodes);
	evalPasswords(password_nodes);
	password_nodes.each( function(node,index) {
		node.observe('keyup',function(evt) {
			evalPasswords(password_nodes,index);
			var criteria_met = true;
			pwd_criteria.each( function(className) {
				var nodeCrit = $('criteria').down('.'+className);
				if ( !nodeCrit )
					return;	// continue
				if ( !nodeCrit.hasClassName('checked') ) {
					criteria_met = false;
					return;
				}
			});
			node.setCustomValidity(criteria_met
				? ''
				: 'Password criteria not met'
			);
			if ( criteria_met ) {
				node.removeClassName('invalid');
				node.removeClassName('error');
			} else {
				node.addClassName('invalid');
				node.addClassName('error');
			}
		});
	});
});

function evalPasswords(password_nodes,index) {
	//debug('evalPasswords');
	var index_passed = index !== undefined,
		index		= index_passed ? index : 0,
		val			= password_nodes[index].getValue(),
		index_other	= $A($R(0,password_nodes.length-1)).without(index)[0],
		val_other	= password_nodes[index_other].getValue(),
		match_opacity = 0,
		nodeComplexity = $('complexity');
	if ( !index_passed ) {
		console.debug('determine longest');
		if ( val_other.length > val.length ) {
			console.debug('val_other is longer');
			var tmp = val;
			val = val_other;
			val_other = tmp;
		}
	}
	//
	if ( !val ) {
		console.debug('no value, use other');
		val = val_other;
	} else if ( !val_other ) {
		//
	} else if ( val == val_other ) {
		console.debug('vals are the same');
		match_opacity = 1;
	} else if ( val_other.startsWith(val) || val.startsWith(val_other) ) {
		console.debug('vals begin the same');
		var p = val.length / val_other.length;
		if ( p > 1 )
			p = 1/p;
		p = p * .5 + .2;
		match_opacity = p;
		if ( index_passed && val_other.length > val.length ) {
			console.debug('eval the other (it\'s longer)');
			val = val_other;
		}
	}
	pm.checkPassword(val);
	$('score').update(pm.int2str(pm.Score.adjusted) + "%");
	$('scorebar').setStyle({
		backgroundPosition:	'-' + parseInt(pm.Score.adjusted * 4) + 'px 0px'
	});
	pwdOnChange(val,match_opacity);
	if ( pm.Complexity.value == pm.COMPLEXITY.VERYWEAK ) {
		nodeComplexity.update("Very Weak");
	} else if ( pm.Complexity.value == pm.COMPLEXITY.WEAK ) {
		nodeComplexity.update("Weak");
	} else if ( pm.Complexity.value == pm.COMPLEXITY.GOOD ) {
		nodeComplexity.update("Good");
	} else if ( pm.Complexity.value == pm.COMPLEXITY.STRONG ) {
		nodeComplexity.update("Strong");
	} else if ( pm.Complexity.value == pm.COMPLEXITY.VERYSTRONG ) {
		nodeComplexity.update("Very Strong");
	}
 }
