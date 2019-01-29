/**
**    Original File: password-meter.js
**    Created by: Rene Schwietzke (mail@03146f06.net)
**    Created on: 2008-12-01
**    Last modified: 2010-05-03
**    Version: 1.0.2
**
**    License Information:
**    -------------------------------------------------------------------------
**    Copyright (C) 2008 Rene Schwietzke
**
**    This program is free software; you can redistribute it and/or modify it
**    under the terms of the GNU General Public License as published by the
**    Free Software Foundation; either version 2 of the License, or (at your
**    option) any later version.
**
**    This program is distributed in the hope that it will be useful, but
**    WITHOUT ANY WARRANTY; without even the implied warranty of
**    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
**    General Public License for more details.
**
**    You should have received a copy of the GNU General Public License along
**    with this program; if not, write to the Free Software Foundation, Inc.,
**    59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
**
**    Based on original work by Jeff Todnem published under GPL 2.
**
**    Original File: pwd_meter.js (http://www.passwordmeter.com/)
**    Created by: Jeff Todnem (http://www.todnem.com/)
**
**    History:
**    -------------------------------------------------------------------------
**    v1.0.0 : initial version
**    v1.0.1 : fixed rounding problem by adjusting float2str
**    v1.0.2 : fixed influence of redundancy on long passwords. More bad
**             characters after a good start should not be punished
**    v1.1.0 : Introduced significance to cover the case of old system, where you
**             can type in long passwords, but only the first 8 characters are
**             considered. So now, the first characters are more important
**             and a good end cannot fix the password when it started bad.
**
**    ToDo:
**    -------------------------------------------------------------------------
**    * Punish first or last letter uppercase characters only
**    * Punish special characters only at the end
**    * Punish numbers only at the end
**    * Filter common patterns, such as 12.12.2008 12/20/2009 2008-12-13
**    * Seem to contain a year 19XX or 20XX
**/

function PasswordMeter()
{

	this.version = "1.1.0";

	this.COMPLEXITY = {
		VERYWEAK:	0,
		WEAK:		1,
		GOOD:		2,
		STRONG:		3,
		VERYSTRONG:	4
	};

	this.STATUS = {
		FAILED:		0,
		PASSED:		1,
		EXCEEDED:	2
	};

	this.props = [
		'PasswordLength','UppercaseLetters','LowercaseLetters','Numerics','Symbols','RecommendedPasswordLength','MiddleNumerics','MiddleSymbols',
		'SequentialLetters','SequentialNumerics','KeyboardPatterns','RepeatedSequences','MirroredSequences',
		'BasicRequirements'
	];
	// not in props:   Redundancy, SplitPassword

	// the complexity index
	this.Complexity = {
		limits	: [20, 50, 60, 80, 100],
		value	: this.COMPLEXITY.VERYWEAK
	};

	this.Score = {
		count			: 0,
		adjusted		: 0,
		beforeRedundancy: 0
	};

	// Basic requirements are:
	this.BasicRequirements = {
		count	: 0,
		minimum	: 3, // have to be matched to get the bonus
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 1,
		bonus	: 10,
		penalty	: -10
	};

	// the length of the password
	this.PasswordLength = {
		count	: 0,
		minimum	: 6,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		basic	: true,
		rating	: 0,
		factor	: 0.5, // per character bonus
		bonus	: 10, // minimum reached? Get a bonus.
		penalty	: -20 // if we stay under minimum, we get punished
	};

	// recommended password length
	this.RecommendedPasswordLength = {
		count	: 0,
		minimum	: 8,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 1.2,
		bonus	: 10,
		penalty	: -10
	};

	// number of uppercase letters, such as A-Z
	this.UppercaseLetters = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		basic	: true,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// number of lowercase letters, such as a-z
	this.LowercaseLetters = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		basic	: true,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// number of numeric characters
	this.Numerics = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		basic	: true,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// number of symbol characters
	this.Symbols = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		basic	: true,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// number of dedicated symbols in the middle
	this.MiddleSymbols = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// number of dedicated numbers in the middle
	this.MiddleNumerics = {
		count	: 0,
		minimum	: 1,
		formula	: "TBD",
		states	: 3,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 0,
		bonus	: 10,
		penalty	: -10
	};

	// how many sequential characters should be checked
	// such as "abc" or "MNO" to be not part of the password
	this.SequentialLetters = {
		data	: "abcdefghijklmnopqrstuvwxyz",
		length	: 3,
		count	: 0,
		formula	: "TBD",
		states	: 2,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: -1,
		bonus	: 0,
		penalty	: -10
	};

	// how many sequential characters should be checked
	// such as "123" to be not part of the password
	this.SequentialNumerics = {
		data	: "0123456789",
		length	: 3,
		count	: 0,
		formula	: "TBD",
		states	: 2,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: -1,
		bonus	: 0,
		penalty	: -10
	};

	// keyboard patterns to check, typical sequences from your
	// keyboard
	this.KeyboardPatterns = {
		// german and english keyboard text
		data	: [	"qwertzuiop", "asdfghjkl", "yxcvbnm", "!\"§$%&/()=", // de
				"1234567890", // de numbers
				"qaywsxedcrfvtgbzhnujmik,ol.pö-üä+#", // de up-down
				"qwertyuiop", "asdfghjkl", "zyxcvbnm", "!@#$%^&*()_", // en
				"1234567890", // en numbers
				"qazwsxedcrfvtgbyhnujmik,ol.p;/[']\\" // en up-down
		],
		length	: 4,	// how long is the pattern to check and blame for?
		count	: 0,	// how much of these pattern can be found
		formula	: "TBD",
		states	: 2,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: -1, // each occurence is punished with that factor
		bonus	: 0,
		penalty	: -10
	};

	// check for repeated sequences, like in catcat
	this.RepeatedSequences = {
		length	: 3,
		count	: 0,
		formula	: "TBD",
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 0,
		bonus	: 0,
		penalty	: -10
	};

	// check for repeated sequences, like in catcat
	this.MirroredSequences = {
		length	: 3,
		count	: 0,
		formula	: "TBD",
		states	: 2,
		status	: this.STATUS.FAILED,
		rating	: 0,
		factor	: 0,
		bonus	: 0,
		penalty	: -10
	};

	// how much redundancy is permitted, if the password is
	// long enough. we will skip the redudancy penalty if this
	// number is not exceeded (meaning redundancy < this number)
	this.Redundancy = {
		value		: 1,		// 1 means, not double characters, default to start
		permitted	: 2.0,		// 2 means, in average every character can occur twice
		formula		: "TBD",
		status		: this.STATUS.FAILED,
		rating		: 0
	};

	// split password data. This means we will split the password
	// at the position and evaluate both parts independently again.
	// The final password score is composed of all three components.
	// (full * weightFull + part1 * weight1 + part2 * weight2).
	// The sum of the weight should be 1 aka 100% to work good.
	this.SplitPassword = {
		splitPosition: this.RecommendedPasswordLength.minimum,
		weight1		: 0.80,	// weight of part1
		// weight2	: 0.20,	// weight of part2
		weightFull	: 0.20,	// the weight applied to the total score
		part1		: "",	// the split password part1
		// part2	: "",	// the split password part2
		part1Score	: 0
		// part2Score	: 0
	};

	// check our password and sets all object properties accordingly
	this.checkPassword = function(password, splitPassword) {
		//debug('checkPassword', password, splitPassword);

		// check the password and set all values
		var lowercasedPassword = '',
			nTmpAlphaUC = -1,
			nTmpAlphaLC = -1,
			nTmpNumber  = -1,
			nTmpSymbol  = -1,
			part1 = null,
			propName = '',
			passwordArray = [],
			uniqueCharacters = [],
			patternsMatched = [],
			pattern = '',
			found = false,
			sFwd = '',
			sRev = '',
			result = 0,
			i = 0,
			j = 0;

		if ( password === undefined )
			password = '';
		if ( splitPassword === undefined )
			splitPassword = true;

		lowercasedPassword = password.toLowerCase();

		// initialize counts
		for ( i=0; i < this.props.length; i++ ) {
			propName = this.props[i];
			this[propName].count = 0;
		}
		this.PasswordLength.count				= password.length;
		this.RecommendedPasswordLength.count	= password.length;
		this.Score.count						= this.PasswordLength.count * this.PasswordLength.factor;	// Initial score based on length

		// split it, all characters are permitted so far
		passwordArray = password.split("");

		// Loop through password to check for Symbol, Numeric, Lowercase
		// and Uppercase pattern matches
		for ( i = 0; i < passwordArray.length; i++ ) {
			// check uppercase letters
			if ( passwordArray[i].match(/[A-Z]/g) ) {
				if ( nTmpAlphaUC != -1 ) {
					// check last uppercase position, when the previous one, store
					// the information
					if ( (nTmpAlphaUC + 1) == i ) {
						this.nConsecutiveUppercaseLetters++;
						this.nConsecutiveLetters++;
					}
				}
				// store the last uppercase position
				nTmpAlphaUC = i;
				this.UppercaseLetters.count++;
			} else if ( passwordArray[i].match(/[a-z]/g) ) {
				// check lowercase
				if ( nTmpAlphaLC != -1 ) {
					if ( (nTmpAlphaLC + 1) == i ) {
						this.nConsecutiveLowercaseLetters++;
						this.nConsecutiveLetters++;
					}
				}
				nTmpAlphaLC = i;
				this.LowercaseLetters.count++;
			} else if ( passwordArray[i].match(/[0-9]/g) ) {
				// check numeric
				if ( i > 0 && i < (passwordArray.length - 1) ) {
					this.MiddleNumerics.count++;
				}
				if ( nTmpNumber != -1 ) {
					if ( (nTmpNumber + 1) == i ) {
						this.nConsecutiveNumbers++;
						this.nConsecutiveLetters++;
					}
				}
				nTmpNumber = i;
				this.Numerics.count++;
			} else if ( passwordArray[i].match(new RegExp(/[^a-zA-Z0-9]/g)) ) {
				// check all extra characters
				if ( i > 0 && i < (passwordArray.length - 1) ) {
					this.MiddleSymbols.count++;
				}
				if ( nTmpSymbol != -1 ) {
					if ( (nTmpSymbol + 1) == i ) {
						this.nConsecutiveSymbols++;
						this.nConsecutiveLetters++;
					}
				}
				nTmpSymbol = i;
				this.Symbols.count++;
			}
		}

		// check the variance of symbols or better the redundancy
		// makes only sense for at least two characters
		if ( passwordArray.length > 1 ) {
			for ( i = 0; i < passwordArray.length; i++ ) {
				found = false;
				for ( j = i + 1; j < passwordArray.length; j++ ) {
					if ( passwordArray[i] == passwordArray[j] ) {
						found = true;
					}
				}
				if ( found === false ) {
					uniqueCharacters.push(passwordArray[i]);
				}
			}
			// calculate a redundancy number
			this.Redundancy.value = passwordArray.length / uniqueCharacters.length;
		}

		// Check for sequential alpha string patterns (forward and reverse) but only, if the string
		// has already a length to check for, does not make sense to check the password "ab" for the
		// sequential data "abc"
		if ( this.PasswordLength.count >= this.SequentialLetters.length ) {
			for ( i = 0; i < this.SequentialLetters.data.length - this.SequentialLetters.length; i++ ) {
				sFwd = this.SequentialLetters.data.substring(i, i + this.SequentialLetters.length);
				sRev = this.strReverse(sFwd);
				if ( lowercasedPassword.indexOf(sFwd) != -1 )
					this.SequentialLetters.count++;
				if ( lowercasedPassword.indexOf(sRev) != -1 )
					this.SequentialLetters.count++;
			}
		}

		// Check for sequential numeric string patterns (forward and reverse)
		if ( this.PasswordLength.count >= this.SequentialNumerics.length ) {
			for ( i = 0; i < this.SequentialNumerics.data.length - this.SequentialNumerics.length; i++ ) {
				sFwd = this.SequentialNumerics.data.substring(i, i + this.SequentialNumerics.length);
				sRev = this.strReverse(sFwd);
				if ( lowercasedPassword.indexOf(sFwd) != -1 )
					this.SequentialNumerics.count++;
				if ( lowercasedPassword.indexOf(sRev) != -1 )
					this.SequentialNumerics.count++;
			}
		}

		// Check common keyboard patterns
		if ( this.PasswordLength.count >= this.KeyboardPatterns.length ) {
			for ( i = 0; i < this.KeyboardPatterns.data.length; i++ ) {
				pattern = this.KeyboardPatterns.data[i];
				for ( j = 0; j < pattern.length - this.KeyboardPatterns.length; j++ ) {
					sFwd = pattern.substring(j, j + this.KeyboardPatterns.length);
					sRev = this.strReverse(sFwd);
					if ( lowercasedPassword.indexOf(sFwd) != -1 && patternsMatched[sFwd] === undefined ) {
						this.KeyboardPatterns.count++;
						patternsMatched[sFwd] = sFwd;
					}
					if ( lowercasedPassword.indexOf(sRev) != -1 && patternsMatched[sRev] === undefined ) {
						this.KeyboardPatterns.count++;
						patternsMatched[sRev] = sRev;
					}
				}
			}
		}

		// Try to find repeated sequences of characters.
		if ( this.PasswordLength.count > this.RepeatedSequences.length ) {
			for ( i = 0; i < lowercasedPassword.length - this.RepeatedSequences.length; i++ ) {
				sFwd = lowercasedPassword.substring(i, i + this.RepeatedSequences.length);
				result = lowercasedPassword.indexOf(sFwd, i + this.RepeatedSequences.length);
				if ( result != -1 )
					this.RepeatedSequences.count++;
			}
		}

		// Try to find mirrored sequences of characters.
		if ( this.PasswordLength.count > this.MirroredSequences.length ) {
			for ( i = 0; i < lowercasedPassword.length - this.MirroredSequences.length; i++ ) {
				sFwd = lowercasedPassword.substring(i, i + this.MirroredSequences.length);
				sRev = this.strReverse(sFwd);
				result = lowercasedPassword.indexOf(sRev, i + this.MirroredSequences.length);
				if ( result != -1 )
					this.MirroredSequences.count++;
			}
		}

		for ( i=0; i < this.props.length; i++ ) {
			propName = this.props[i];
			this.determineStatus(propName);
		}

		// save value before redundancy
		this.Score.beforeRedundancy = this.Score.count;

		// apply the redundancy
		// is the password length requirement fulfilled?
		if ( this.RecommendedPasswordLength.status != this.STATUS.EXCEEDED ) {
			// full penalty, because password is not long enough, only for a positive score
			if ( this.Score.count > 0 )
				this.Score.count = this.Score.count * (1 / this.Redundancy.value);
		}

		// level it out
		if ( this.Score.count > 100 )
			this.Score.adjusted = 100;
		else if ( this.Score.count < 0 )
			this.Score.adjusted = 0;
		else
			this.Score.adjusted = this.Score.count;

		// the final twist. The first part (recommended length for now) has to have a good meaning,
		// because some legacy system only evaluate the beginning and do not use the rest.
		if ( this.PasswordLength.count > this.SplitPassword.splitPosition && splitPassword ) {
			part1 = new PasswordMeter();
			this.SplitPassword.part1 =  password.substr(0, this.SplitPassword.splitPosition);
			part1.checkPassword(this.SplitPassword.part1, false);
			this.SplitPassword.part1Score = part1.Score.adjusted;

			/*
			var part2 = new PasswordMeter();
			this.SplitPassword.part2 = password.substr(this.SplitPassword.splitPosition);
			part2.checkPassword(this.SplitPassword.part2, false);
			this.SplitPassword.part2Score = part2.Score.adjusted;
			*/

			// ok, the final score is composed of score one and score two

			//var old = this.Score.count;

			// do this only, if the first part is not 100%
			if ( this.SplitPassword.part1Score < 100 ) {
				this.Score.count =
					this.Score.count * this.SplitPassword.weightFull +
					this.SplitPassword.part1Score * this.SplitPassword.weight1;
					// this.SplitPassword.part2Score * this.SplitPassword.weight2;
				/*
				alert("Changed\n" +
				password + ": " + old + "\n" +
				this.SplitPassword.part1 + ": " + this.SplitPassword.part1Score + "\n" +
				//this.SplitPassword.part2 + ": " + this.SplitPassword.part2Score + "\n" +
				"New: " + this.Score.count
				);*/
			} else {
				this.SplitPassword.part1Score = this.Score.count;
				/*alert("Unchanged\n" +
				password + ": " + old + "\n" +
				this.SplitPassword.part1 + ": " + this.SplitPassword.part1Score + "\n" +
				//this.SplitPassword.part2 + ": " + this.SplitPassword.part2Score + "\n" +
				"New: " + this.Score.count
				);*/
			}
		} else {
			this.SplitPassword.part1Score = this.Score.count;
		}

		// level it out again
		if ( this.Score.count > 100 )
			this.Score.adjusted = 100;
		else if ( this.Score.count < 0 )
			this.Score.adjusted = 0;
		else
			this.Score.adjusted = this.Score.count;
		// judge it
		for ( i = 0; i < this.Complexity.limits.length; i++ ) {
			if ( this.Score.adjusted <= this.Complexity.limits[i] ) {
				this.Complexity.value = i;
				break;
			}
		}
		return this.Complexity.value;
	};

	// helper for the status
	// 3 states:
	//	<0 failed
	//	0  passed
	//	>0 exceeded
	// 2 states
	//	0  passed
	//	!=0 failed
	// rate,status,score
	this.determineStatus = function(propertyName) {
		var prop = this[propertyName],
			val = prop.states == 3 ? prop.count - prop.minimum : prop.count;
		//console.debug('val',val);
		prop.status = val === 0 ? this.STATUS.PASSED : this.STATUS.FAILED;
		if ( prop.states == 3 && val > 0 )
			prop.status = this.STATUS.EXCEEDED;
		if ( prop.basic === true && prop.status != this.STATUS.FAILED )
			// this is a basic requirement & requirement met
			this.BasicRequirements.count++;
		//console.debug('status',prop.status);
		switch ( propertyName ) {
		case 'RecommendedPasswordLength':
			// Credit reaching the recommended password length or put a penalty on it
			prop.rating = prop.count >= prop.minimum ?
				prop.bonus + (prop.count - prop.minimum) * prop.factor :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'PasswordLength':
			// credit additional length or punish "under" length
			prop.rating = prop.count >= prop.minimum ?
				prop.bonus + (prop.count - prop.minimum) * prop.factor :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'UppercaseLetters':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'LowercaseLetters':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'Numerics':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'Symbols':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'MiddleNumerics':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'MiddleSymbols':
			prop.rating = prop.count > 0 ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		case 'SequentialLetters':
			prop.rating = prop.count === 0 ?
				prop.bonus :
				prop.penalty + (prop.count * prop.factor);
			this.Score.count += prop.rating;
			break;
		case 'SequentialNumerics':
			prop.rating = prop.count === 0 ?
				prop.bonus :
				prop.penalty + (prop.count * prop.factor);
			this.Score.count += prop.rating;
			break;
		case 'KeyboardPatterns':
			prop.rating = prop.count === 0 ?
				prop.bonus :
				prop.penalty + (prop.count * prop.factor);
			this.Score.count += prop.rating;
			break;
		case 'RepeatedSequences':
			// apply only if the length is not awesome ;)
			if ( this.RecommendedPasswordLength.status != this.STATUS.EXCEEDED ) {
				prop.rating = prop.count === 0 ?
					prop.bonus :
					prop.penalty + (prop.count * prop.factor);
				this.Score.count += prop.rating;
			}
			break;
		case 'MirroredSequences':
			// apply only if the length is not awesome ;)
			if ( this.RecommendedPasswordLength.status != this.STATUS.EXCEEDED ) {
				prop.rating = prop.count === 0 ?
					prop.bonus :
					prop.penalty + (prop.count * prop.factor);
				this.Score.count += prop.rating;
			}
			break;
		case 'BasicRequirements':
			prop.rating = prop.status != this.STATUS.FAILED ?
				prop.bonus + (prop.count * prop.factor) :
				prop.penalty;
			this.Score.count += prop.rating;
			break;
		default:
			break;
		}
	};

	// little string helper to reverse a string
	this.strReverse = function(str) {
		var newstring = "",
			i;
		for ( i = 0; i < str.length; i++ )
			newstring = str.charAt(i) + newstring;
		return newstring;
	};

	this.int2str = function(aNumber) {
		if ( aNumber === 0 )
			return "0";
		else
			return parseInt(aNumber, 10);
	};

	this.float2str = function(aNumber) {
		if ( aNumber === 0 )
			return "0.00";
		else
			return parseFloat(aNumber.toFixed(2));
	};

}

