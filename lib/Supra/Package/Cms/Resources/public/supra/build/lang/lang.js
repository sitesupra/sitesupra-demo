YUI().add("supra.lang", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//If already defined, then exit
	if (Y.Lang.toArray) return;
	
	//Shortcuts
	var hasOwnProperty = Object.prototype.hasOwnProperty;
	var WINDOW = window.constructor;
	var DOCUMENT = document.constructor;
	
	
	
	/**
	 * Convert Object into Array
	 * 
	 * @param {Object} obj
	 * @return Array
	 * @type {Array}
	 */
	Y.Lang.toArray = function (obj) {
		if ('length' in obj) {
			return [].slice.call(obj, 0);
		} else {
			var arr = [], ii=0;
			for(var i in obj) {
				if (obj.hasOwnProperty(i)) arr[ii++] = obj[i];
			}
			return arr;
		}
	};
	
	/**
	 * Returns true if obj is Object and not Array, Function, document or window
	 * 
	 * @param {Object} obj
	 * @return True if obj is plain object
	 * @type {Boolean}
	 */
	Y.Lang.isPlainObject = function (obj) {
		if (obj &&									//not empty
			Y.Lang.isObject(obj, true) &&			//is object and not function
			!obj.nodeType &&						//not HTMLElement
			!Y.Lang.isArray(obj) &&					//not array
			!(obj instanceof DOCUMENT) &&			//not document
			!(obj instanceof WINDOW)) {				//not window
			
			// Not own constructor property must be Object
			if ( obj.constructor &&
				!hasOwnProperty.call(obj, "constructor") &&
				!hasOwnProperty.call(obj.constructor.prototype, "isPrototypeOf") ) {
				return false;
			}
			
			return true;
			
		}
		return false;
	};
	
	/**
	 * Returns true if obj is widget instance
	 * 
	 * @param {Object} obj Object to check
	 * 
	 */
	Y.Lang.isWidget = function (obj, classname) {
		if (obj && Y.Lang.isObject(obj) && !Y.Lang.isPlainObject(obj) && obj.isInstanceOf) {
			
			if (classname) {
				return obj.isInstanceOf(classname);
			}
			
			return true;
		}
		
		return false;
	};
	
	/**
	 * Compare if two objects has all the same properties
	 * 
	 * @param {Object} o1
	 * @param {Object} o2
	 * @private
	 */
	Y.Lang.compareObjects = function (o1, o2) {
		var o1_size = 0,
			o2_size = 0,
			v1 = null,
			v2 = null;
			key = null;
		
		for(key in o2) o2_size++;
		
		for(key in o1) {
			if (!(key in o2)) return false;
			v1 = o1[key];
			v2 = o2[key];
			
			if ((Y.Lang.isArray(v1) && Y.Lang.isArray(v2)) || (Y.Lang.isObject(v1) && Y.Lang.isObject(v2))) {
				if (!Y.Lang.compareObjects(v1, v2)) return false;
			} else {
				if (v1 !== v2) return false;
			}
			
			o1_size++;
		}
		
		return o1_size == o2_size;
	};
	
	/**
	 * Convert all invalid characters into url compatible form
	 * 
	 * @param {String} str String to convert
	 * @returns {String} Converted string
	 */
	Y.Lang.toPath = function (str) {
		str = String(str || '');
		
		// Convert non-ASCII characters
		var map = Y.Lang.toPath.map,
			i = 0,
			ii = str.length,
			out = '',
			chr = '';
		
		for (; i<ii; i++) {
			chr = str[i];
			out += (chr in map ? map[chr] : chr);
		}
		
		str = out.toLowerCase();
		
		// Replace invalid characters with dash
		str = str.replace(/[^a-z0-9\-]/g, '-');
		
		// Remove invalid first and last characters
		str = str.replace(/(^[^a-z0-9]+|[^a-z0-9]+$)/g, '');
		
		// remove repeated characters
		str = str.replace(/([\-\_])[\-\_]+/g, '$1');
		
		return str;
	};
	
	/**
	 * Non-ASCII to ASCII character map
	 * @type {Object}
	 * @private
	 */
	Y.Lang.toPath.map = {'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'Ae', 'Å': 'A', 'Æ': 'A', 'Ā': 'A', 'Ą': 'A', 'Ă': 'A', 'Ç': 'C', 'Ć': 'C', 'Č': 'C', 'Ĉ': 'C', 'Ċ': 'C', 'Ď': 'D', 'Đ': 'D', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ē': 'E', 'Ę': 'E', 'Ě': 'E', 'Ĕ': 'E', 'Ė': 'E', 'Ĝ': 'G', 'Ğ': 'G', 'Ġ': 'G', 'Ģ': 'G', 'Ĥ': 'H', 'Ħ': 'H', 'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ī': 'I', 'Ĩ': 'I', 'Ĭ': 'I', 'Į': 'I', 'İ': 'I', 'Ĳ': 'IJ', 'Ĵ': 'J', 'Ķ': 'K', 'Ľ': 'K', 'Ĺ': 'K', 'Ļ': 'K', 'Ŀ': 'K', 'Ł': 'L', 'Ñ': 'N', 'Ń': 'N', 'Ň': 'N', 'Ņ': 'N', 'Ŋ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'Oe', 'Ø': 'O', 'Ō': 'O', 'Ő': 'O', 'Ŏ': 'O', 'Œ': 'OE', 'Ŕ': 'R', 'Ř': 'R', 'Ŗ': 'R', 'Ś': 'S', 'Ş': 'S', 'Ŝ': 'S', 'Ș': 'S', 'Š': 'S', 'Ť': 'T', 'Ţ': 'T', 'Ŧ': 'T', 'Ț': 'T', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'Ue', 'Ū': 'U', 'Ů': 'U', 'Ű': 'U', 'Ŭ': 'U', 'Ũ': 'U', 'Ų': 'U', 'Ŵ': 'W', 'Ŷ': 'Y', 'Ÿ': 'Y', 'Ý': 'Y', 'Ź': 'Z', 'Ż': 'Z', 'Ž': 'Z', 'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'ā': 'a', 'ą': 'a', 'ă': 'a', 'å': 'a', 'æ': 'ae', 'ç': 'c', 'ć': 'c', 'č': 'c', 'ĉ': 'c', 'ċ': 'c', 'ď': 'd', 'đ': 'd', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ē': 'e', 'ę': 'e', 'ě': 'e', 'ĕ': 'e', 'ė': 'e', 'ƒ': 'f', 'ĝ': 'g', 'ğ': 'g', 'ġ': 'g', 'ģ': 'g', 'ĥ': 'h', 'ħ': 'h', 'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i', 'ī': 'i', 'ĩ': 'i', 'ĭ': 'i', 'į': 'i', 'ı': 'i', 'ĳ': 'ij', 'ĵ': 'j', 'ķ': 'k', 'ĸ': 'k', 'ł': 'l', 'ľ': 'l', 'ĺ': 'l', 'ļ': 'l', 'ŀ': 'l', 'ñ': 'n', 'ń': 'n', 'ň': 'n', 'ņ': 'n', 'ŉ': 'n', 'ŋ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o', 'ō': 'o', 'ő': 'o', 'ŏ': 'o', 'œ': 'oe', 'ŕ': 'r', 'ř': 'r', 'ŗ': 'r', 'ś': 's', 'š': 's', 'ť': 't', 'ù': '', 'ú': '', 'û': '', 'ü': 'ue', 'ū': '', 'ů': '', 'ű': '', 'ŭ': '', 'ũ': '', 'ų': '', 'ŵ': 'w', 'ÿ': 'y', 'ý': 'y', 'ŷ': 'y', 'ż': 'z', 'ź': 'z', 'ž': 'z', 'ß': 'ss', 'ſ': 'ss', 'Α': 'A', 'Ά': 'A', 'Ἀ': 'A', 'Ἁ': 'A', 'Ἂ': 'A', 'Ἃ': 'A', 'Ἄ': 'A', 'Ἅ': 'A', 'Ἆ': 'A', 'Ἇ': 'A', 'ᾈ': 'A', 'ᾉ': 'A', 'ᾊ': 'A', 'ᾋ': 'A', 'ᾌ': 'A', 'ᾍ': 'A', 'ᾎ': 'A', 'ᾏ': 'A', 'Ᾰ': 'A', 'Ᾱ': 'A', 'Ὰ': 'A', 'Ά': 'A', 'ᾼ': 'A', 'Β': 'B', 'Γ': 'G', 'Δ': 'D', 'Ε': 'E', 'Έ': 'E', 'Ἐ': 'E', 'Ἑ': 'E', 'Ἒ': 'E', 'Ἓ': 'E', 'Ἔ': 'E', 'Ἕ': 'E', 'Έ': 'E', 'Ὲ': 'E', 'Ζ': 'Z', 'Η': 'I', 'Ή': 'I', 'Ἠ': 'I', 'Ἡ': 'I', 'Ἢ': 'I', 'Ἣ': 'I', 'Ἤ': 'I', 'Ἥ': 'I', 'Ἦ': 'I', 'Ἧ': 'I', 'ᾘ': 'I', 'ᾙ': 'I', 'ᾚ': 'I', 'ᾛ': 'I', 'ᾜ': 'I', 'ᾝ': 'I', 'ᾞ': 'I', 'ᾟ': 'I', 'Ὴ': 'I', 'Ή': 'I', 'ῌ': 'I', 'Θ': 'TH', 'Ι': 'I', 'Ί': 'I', 'Ϊ': 'I', 'Ἰ': 'I', 'Ἱ': 'I', 'Ἲ': 'I', 'Ἳ': 'I', 'Ἴ': 'I', 'Ἵ': 'I', 'Ἶ': 'I', 'Ἷ': 'I', 'Ῐ': 'I', 'Ῑ': 'I', 'Ὶ': 'I', 'Ί': 'I', 'Κ': 'K', 'Λ': 'L', 'Μ': 'M', 'Ν': 'N', 'Ξ': 'KS', 'Ο': 'O', 'Ό': 'O', 'Ὀ': 'O', 'Ὁ': 'O', 'Ὂ': 'O', 'Ὃ': 'O', 'Ὄ': 'O', 'Ὅ': 'O', 'Ὸ': 'O', 'Ό': 'O', 'Π': 'P', 'Ρ': 'R', 'Ῥ': 'R', 'Σ': 'S', 'Τ': 'T', 'Υ': 'Y', 'Ύ': 'Y', 'Ϋ': 'Y', 'Ὑ': 'Y', 'Ὓ': 'Y', 'Ὕ': 'Y', 'Ὗ': 'Y', 'Ῠ': 'Y', 'Ῡ': 'Y', 'Ὺ': 'Y', 'Ύ': 'Y', 'Φ': 'F', 'Χ': 'X', 'Ψ': 'PS', 'Ω': 'O', 'Ώ': 'O', 'Ὠ': 'O', 'Ὡ': 'O', 'Ὢ': 'O', 'Ὣ': 'O', 'Ὤ': 'O', 'Ὥ': 'O', 'Ὦ': 'O', 'Ὧ': 'O', 'ᾨ': 'O', 'ᾩ': 'O', 'ᾪ': 'O', 'ᾫ': 'O', 'ᾬ': 'O', 'ᾭ': 'O', 'ᾮ': 'O', 'ᾯ': 'O', 'Ὼ': 'O', 'Ώ': 'O', 'ῼ': 'O', 'α': 'a', 'ά': 'a', 'ἀ': 'a', 'ἁ': 'a', 'ἂ': 'a', 'ἃ': 'a', 'ἄ': 'a', 'ἅ': 'a', 'ἆ': 'a', 'ἇ': 'a', 'ᾀ': 'a', 'ᾁ': 'a', 'ᾂ': 'a', 'ᾃ': 'a', 'ᾄ': 'a', 'ᾅ': 'a', 'ᾆ': 'a', 'ᾇ': 'a', 'ὰ': 'a', 'ά': 'a', 'ᾰ': 'a', 'ᾱ': 'a', 'ᾲ': 'a', 'ᾳ': 'a', 'ᾴ': 'a', 'ᾶ': 'a', 'ᾷ': 'a', 'β': 'b', 'γ': 'g', 'δ': 'd', 'ε': 'e', 'έ': 'e', 'ἐ': 'e', 'ἑ': 'e', 'ἒ': 'e', 'ἓ': 'e', 'ἔ': 'e', 'ἕ': 'e', 'ὲ': 'e', 'έ': 'e', 'ζ': 'z', 'η': 'i', 'ή': 'i', 'ἠ': 'i', 'ἡ': 'i', 'ἢ': 'i', 'ἣ': 'i', 'ἤ': 'i', 'ἥ': 'i', 'ἦ': 'i', 'ἧ': 'i', 'ᾐ': 'i', 'ᾑ': 'i', 'ᾒ': 'i', 'ᾓ': 'i', 'ᾔ': 'i', 'ᾕ': 'i', 'ᾖ': 'i', 'ᾗ': 'i', 'ὴ': 'i', 'ή': 'i', 'ῂ': 'i', 'ῃ': 'i', 'ῄ': 'i', 'ῆ': 'i', 'ῇ': 'i', 'θ': 'th', 'ι': 'i', 'ί': 'i', 'ϊ': 'i', 'ΐ': 'i', 'ἰ': 'i', 'ἱ': 'i', 'ἲ': 'i', 'ἳ': 'i', 'ἴ': 'i', 'ἵ': 'i', 'ἶ': 'i', 'ἷ': 'i', 'ὶ': 'i', 'ί': 'i', 'ῐ': 'i', 'ῑ': 'i', 'ῒ': 'i', 'ΐ': 'i', 'ῖ': 'i', 'ῗ': 'i', 'κ': 'k', 'λ': 'l', 'μ': 'm', 'ν': 'n', 'ξ': 'ks', 'ο': 'o', 'ό': 'o', 'ὀ': 'o', 'ὁ': 'o', 'ὂ': 'o', 'ὃ': 'o', 'ὄ': 'o', 'ὅ': 'o', 'ὸ': 'o', 'ό': 'o', 'π': 'p', 'ρ': 'r', 'ῤ': 'r', 'ῥ': 'r', 'σ': 's', 'ς': 's', 'τ': 't', 'υ': 'y', 'ύ': 'y', 'ϋ': 'y', 'ΰ': 'y', 'ὐ': 'y', 'ὑ': 'y', 'ὒ': 'y', 'ὓ': 'y', 'ὔ': 'y', 'ὕ': 'y', 'ὖ': 'y', 'ὗ': 'y', 'ὺ': 'y', 'ύ': 'y', 'ῠ': 'y', 'ῡ': 'y', 'ῢ': 'y', 'ΰ': 'y', 'ῦ': 'y', 'ῧ': 'y', 'φ': 'f', 'χ': 'x', 'ψ': 'ps', 'ω': 'o', 'ώ': 'o', 'ὠ': 'o', 'ὡ': 'o', 'ὢ': 'o', 'ὣ': 'o', 'ὤ': 'o', 'ὥ': 'o', 'ὦ': 'o', 'ὧ': 'o', 'ᾠ': 'o', 'ᾡ': 'o', 'ᾢ': 'o', 'ᾣ': 'o', 'ᾤ': 'o', 'ᾥ': 'o', 'ᾦ': 'o', 'ᾧ': 'o', 'ὼ': 'o', 'ώ': 'o', 'ῲ': 'o', 'ῳ': 'o', 'ῴ': 'o', 'ῶ': 'o', 'ῷ': 'o', '¨': '', '΅': '', '᾿': '', '῾': '', '῍': '', '῝': '', '῎': '', '῞': '', '῏': '', '῟': '', '῀': '', '῁': '', '΄': '', '΅': '', '`': '', '῭': '', 'ͺ': '', '᾽': '', 'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Е': 'E', 'Ё': 'E', 'Ж': 'ZH', 'З': 'Z', 'И': 'I', 'Й': 'I', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N', 'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T', 'У': 'U', 'Ф': 'F', 'Х': 'KH', 'Ц': 'TS', 'Ч': 'CH', 'Ш': 'SH', 'Щ': 'SHCH', 'Ы': 'Y', 'Э': 'E', 'Ю': 'YU', 'Я': 'YA', 'а': 'A', 'б': 'B', 'в': 'V', 'г': 'G', 'д': 'D', 'е': 'E', 'ё': 'E', 'ж': 'ZH', 'з': 'Z', 'и': 'I', 'й': 'I', 'к': 'K', 'л': 'L', 'м': 'M', 'н': 'N', 'о': 'O', 'п': 'P', 'р': 'R', 'с': 'S', 'т': 'T', 'у': 'U', 'ф': 'F', 'х': 'KH', 'ц': 'TS', 'ч': 'CH', 'ш': 'SH', 'щ': 'SHCH', 'ы': 'Y', 'э': 'E', 'ю': 'YU', 'я': 'YA', 'Ъ': '', 'ъ': '', 'Ь': '', 'ь': '', 'ð': 'd', 'Ð': 'D', 'þ': 'th', 'Þ': 'TH', 'ა': 'a', 'ბ': 'b', 'გ': 'g', 'დ': 'd', 'ე': 'e', 'ვ': 'v', 'ზ': 'z', 'თ': 't', 'ი': 'i', 'კ': 'k', 'ლ': 'l', 'მ': 'm', 'ნ': 'n', 'ო': 'o', 'პ': 'p', 'ჟ': 'zh', 'რ': 'r', 'ს': 's', 'ტ': 't', 'უ': '', 'ფ': 'p', 'ქ': 'k', 'ღ': 'gh', 'ყ': 'q', 'შ': 'sh', 'ჩ': 'ch', 'ც': 'ts', 'ძ': 'dz', 'წ': 'ts', 'ჭ': 'ch', 'ხ': 'kh', 'ჯ': 'j', 'ჰ': 'h' };

}, YUI.version);