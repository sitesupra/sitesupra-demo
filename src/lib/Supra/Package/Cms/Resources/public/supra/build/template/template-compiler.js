YUI.add('supra.template-compiler', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Template compiler
	 * Syntax based on http://www.twig-project.org/
	 */
	var C = {
		
		/**
		 * Add custom filter
		 * 
		 * @param {String} name Filter name
		 * @param {Function} fn Filter function
		 */
		'addFilter': function (name, fn) {
			C.filters[name] = fn;
		},
		
		/**
		 * Compile template string into function
		 */
		'compile': function (tpl, opt) {
			
			//Initialize
			var opt = opt || {};
			opt = Options = {
				//Remove CDATA tags
				'stripCDATA': !!opt.stripCDATA,
				
				//Basic validation if all tags are closed. Performance penalty!
				'validate': !!opt.validate
			};
			
			Stack = [];
			
			//Data is saved in _d variable, output is in p
			var body = 'var _c=Supra.TemplateCompiler,_d=Supra.mix({Math:Math,Date:Date,Y:Supra.Y,Supra:Supra},data||{}),t=null,_f=_c.filters,_fn=_c.functions,' 
					 + 'p=\'';
			
			//Remove tab and new line whitespaces
			tpl = tpl.replace(REG_WHITESPACE, ' ');
			
			//Replace single quotes with new line which was previously removed
			tpl = tpl.replace(REG_TEXT, function (all, start, text, end) {
				return start + text.split('\'').join('\r') + end;
			});
			
			//Strip CDATA
			if (opt.stripCDATA) {
				tpl = tpl.replace(REG_CDATA_OPEN, '').replace(REG_CDATA_CLOSE, '');
			}
			
			//Parse variables, expressions, blocks
			tpl = this.compileString(tpl);
			
			//Restore quotes
			tpl = tpl.split('\r').join('\\\'');
			
			//Finalize function body
			body += tpl + '\';return p;';
			
			//Remove extra spaces
			body = body.replace(/\s+/g, ' ');
			
			//Validate
			if (opt.validate && Stack.length) {
				throw new Error('Error_Syntax: missing closing tags for "' + Stack.join('", "') + '"');
			}
			
			//Clean up
			Options = null;
			Stack = null;
			
			return new Function('data', body);
		},
		
		/**
		 * Compile part of the template
		 */
		'compileString': function (tpl) {
			var compileVar = this.compileVar,
				compileExpression = this.compileExpression,
				compilerExpressions = this.compilerExpressions;
			
			//Strip comments
			tpl = tpl.replace(REG_COMMENTS, '');
			
			//Compile variables
			tpl = tpl.replace(REG_VAR, function (all, expression) {
				return '\'+(' + compileVar(expression, true) + ')+\'';
			});
			
			//Compile expressions
			tpl = tpl.replace(REG_EXPR, function (all, identifier, expression) {
				all = compileExpression(identifier, expression || '', compilerExpressions[identifier]);
				return (all ? '\';' + all + 'p+=\'' : '');
			});
			
			return tpl;
		},
		
		/**
		 * Convert variables into JS compatible format
		 * 
		 * @param {String} variable Variable string
		 * @param {Boolean} output Prevent 'undefined' or 'null' in output
		 * @return Converted variable string
		 * @type {String}
		 * @private
		 */
		'compileVar': function (variable, output) {
			var _variable = variable = variable.trim();
			
			//Extract strings
			var strings = variable.match(REG_VAR_STRING), len = strings ? strings.length : 0;
			for(var i=0; i<len; i++) variable = variable.replace(strings[i], '\'$_' + i + '\'');
			
			//Trim whitespaces around , : { } ( )
			//because of { "x": 12, "y": $_s1 }
			variable = variable.replace(REG_VAR_TRIM, '$1');
			
			//Convert functions
			if (variable.indexOf('(') != -1) {
				variable = variable.replace(REG_VAR_FN, '$1_fn.$2');
			}
			
			//Add data object namespace to variables, KEY -> _d.KEY
			variable = variable.replace(REG_VAR_DATA, '$1_d.$2');
			
			//Fix filters, KEY|FILTER -> _f.FILTER(KEY)
			var limit = 100;
			while (limit && REG_CHECK_MODIFIERS.test(variable)) {
				variable = variable.replace(REG_VAR_MODIFIERS, function (all, variable, filter, params_, params) {
					return '_f.' + filter + '(' + variable + (params ? ',' + params : '') + ')';
				});
				
				//Debug information
				if (Options.validate) {
					limit--;
					if (!limit) {
						throw new Error('Error_Syntax: can\'t parse filters in "' + _variable + '"');
						break;
					}
				}
			}
			
			//Restore strings
			for(var i=0; i<len; i++) variable = variable.replace('\'$_' + i + '\'', strings[i]);
			
			if (output) {
				//Check if need to protect from outputing undefined or null
				return '(t=' + variable + ')||t===0?t:\'\'';
			} else {
				return variable;
			}
		},
		
		/**
		 * Convert expression into JS compatible format
		 * 
		 * @param {String} expr Expression string
		 * @return Converted expression string
		 * @type {String}
		 * @private
		 */
		'compileExpression': function (identifier, expr, fn) {
			if (typeof fn == 'function') {
				//Convert " and " to " && ", " not " to "!", " or " to " || ", " ~ " to concatanation
				expr = expr.replace(REG_AND, ' && ').replace(REG_OR, ' || ').replace(REG_NOT, '!').replace(REG_CAT, ' + "" + ');
				return fn(expr);
			}
			return '';
		},
		
		/**
		 * Generates variable name
		 * 
		 * @return Variable name
		 * @type {String}
		 */
		'generateVariable': function () {
			return '_' + ~~(Math.random() * 64000);
		},
		
		/**
		 * Output value
		 * Used in compilerExpressions
		 * 
		 * @param {String} expression Expression to output
		 */
		'output': function (str) {
			return 'p+=' + str + ';';
		},
		
		/**
		 * Debug value
		 * 
		 * @param {Object} object Object to debug
		 */
		'debug': function (obj) {
			if (window.console && console.log) {
				console.log(obj);
			}
		},
		
		/**
		 * Add filter function
		 * 
		 * @param {String} id Filter ID
		 * @param {Function} fn Filter function
		 */
		'registerFilter': function (id, fn) {
			C.filters[id] = fn;
		},
		
		/**
		 * Add function
		 * 
		 * @param {String} id Function ID
		 * @param {Function} fn Function
		 */
		'registerFunction': function (id, fn) {
			C.functions[id] = fn;
		},
		
		/**
		 * Filter functions
		 */
		'filters': {
			/**
			 * Replace substring in string
			 * 
			 * @param {String} str String
			 * @param {Object} replacements Replacements
			 * @return New string
			 * @type {String}
			 */
			'replace': function (str, replacements) {
				str = (''+str);
				for(var i in replacements) {
					str = str.split(i).join(replacements[i]);
				}
				
				return str;
			},
			
			/**
			 * Encode string into URL component
			 * 
			 * @param {String} str String
			 * @return Encoded string
			 * @type {String}
			 */
			'url_encode': function (str) {
				return encodeURIComponent((''+str));
			},
			
			/**
			 * Decode string
			 * 
			 * @param {String} str String
			 * @return Decoded string
			 * @type {String}
			 */
			'url_decode': function (str) {
				try {
					return QueryString.unescape((''+str));
				} catch (e) {
					return '' + str;
				}
			},
			
			/**
			 * Capitalizes all words
			 * 
			 * @param {String} str String
			 * @return Capitalized string
			 * @type {String}
			 */
			'title': function (str) {
				return (''+str).replace(REG_FILTER_TITLE, function (letter) {
			        return letter.toUpperCase();
			    });
			},
			
			/**
			 * Capitalizes first word in string
			 * 
			 * @param {String} str String
			 * @return Capitalized string
			 * @type {String}
			 */
			'capitalize': function (str) {
				str = (''+str);
				return str.length ? str[0].toUpperCase() + str.substr(1).toLowerCase() : '';
			},
			
			/**
			 * Changes all letters to upper case
			 * 
			 * @param {String} str String
			 * @return Upper cased string
			 * @type {String}
			 */
			'upper': function (str) {
				return (''+str).toUpperCase();
			},
			
			/**
			 * Changes all letters to lower case
			 * 
			 * @param {String} str String
			 * @return Lower cased string
			 * @type {String}
			 */
			'lower': function (str) {
				return (''+str).toLowerCase();
			},
			
			/**
			 * Strips tags and replaces adjacent whitespace by one space
			 * 
			 * @param {String} str String
			 * @return Stripped string
			 * @type {String}
			 */
			'striptags': function (str) {
				return (''+str).replace(REG_STRIP_TAGS, '').replace(REG_WHITESPACE, ' ');
			},
			
			/**
			 * Joins array or object values
			 * 
			 * @param {Object} arr Array or object
			 * @return Joined string
			 * @type {String}
			 */
			'join': function (arr, sep) {
				if (typeof arr == 'object' && !FN_IS_ARRAY(arr)) {
					arr = FN_TO_ARRAY(arr);
				}
				if (FN_IS_ARRAY(arr)) {
					return arr.join(sep || '');
				} else {
					return '';
				}
			},
			
			/**
			 * Reverse order of array or string
			 * 
			 * @param {Object} arr Array or string
			 * @return Reversed array or string
			 * @type {Object}
			 */
			'reverse': function (arr) {
				if (arr && arr.reverse) return arr.reverse();
				if (typeof arr == 'string') return arr.split('').reverse().join('');
				return arr;
			},
			
			/**
			 * Returns length of the array or object
			 * 
			 * @param {Object} arr Array or object
			 * @return Length
			 * @type {Number}
			 */
			'length': function (arr) {
				if (FN_IS_ARRAY(arr)) {
					return arr.length;
				} else if (typeof arr == 'object') {
					var len = 0;
					for(var i in arr) if (arr.hasOwnProperty(i)) len++;
					return len;
				} else {
					return 0;
				}
			},
			
			/**
			 * Sort array, if not an array then it's converted to one
			 * and then sorted
			 * 
			 * @param {Object} arr Array or object
			 * @return Sorted array
			 * @type {Array}
			 */
			'sort': function (arr) {
				if (typeof arr == 'object' && !FN_IS_ARRAY(arr)) {
					arr = FN_TO_ARRAY(arr);
				}
				return (arr && arr.sort ? [].concat(arr.sort()) : arr);
			},
			
			/**
			 * If string is null or empty return default value
			 * 
			 * @param {String} str String
			 * @param {String} val Default value
			 * @return String or default value if string is empty or null
			 * @type {String}
			 */
			'default': function (str, val) {
				return str || (val === undefined || val === null ? '' : val);
			},
			
			/**
			 * Returns array or object keys
			 * 
			 * @param {Object} obj Array or object
			 * @return Keys
			 * @type {Array}
			 */
			'keys': function (obj) {
				if (FN_IS_ARRAY(obj)) {
					var keys = new Array(obj.length);
					for(var i=0,ii=obj.length; i<ii; i++) keys[i] = i;
					return keys;
				} else if (typeof obj == 'object') {
					var keys = [], len = 0;
					for(var i in obj) keys[len++] = i;
					return keys;
				} else {
					return [];
				}
			},
			
			/**
			 * Converts <, >, &, ", ' symbols into HTML-safe sequences
			 * 
			 * @param {String}
			 * @return Escaped string
			 * @type {String} 
			 */
			'escape': function (str, type) {
				if (!type || type === 'html') {
					return (''+str).replace(/&/g, '&amp;')
								   .replace(/</g, '&lt;')
								   .replace(/>/g, '&gt;')
								   .replace(/"/g, '&quot;')
								   .replace(/'/g, '&#39;');
				} else if (type == 'html_attr') {
					return (''+str).replace(/[^a-zA-Z0-9,\.\-_]/g, escape_html_attr);
				} else if (type == 'js') {
					return (''+str).replace(/\\/g, '\\\\')
								   .replace(/"/g, '\\"')
								   .replace(/'/g, '\\\'')
								   .replace(/\r/g, '\\r')
								   .replace(/\n/g, '\\n')
								   .replace(/\t/g, '\\t')
				} else {
					return str;
				}
			},
			
			/**
			 * Merge arrays or objects
			 * 
			 * @param {Object} o1 Object
			 * @param {Object} o2 Object
			 * @return Merged object or array if o1 was array
			 * @type {Object}
			 */
			'merge': function (o1, o2) {
				var dest = {}, dest_is_array = false;
				if (typeof o1 == 'object') {
					if (FN_IS_ARRAY(o1)) {
						dest_is_array = true;
						dest = [].concat(o1);
					} else {
						for(var i in o1) dest[i] = o1[i];
					}
				}
				if (typeof o2 == 'object') {
					if (FN_IS_ARRAY(o2)) {
						if (dest_is_array) {
							dest.concat(o2);
						} else {
							for(var i=0,ii=o2.length; i<ii; i++) dest[i] = o2[i];
						}
					} else {
						if (dest_is_array) {
							for(var i in o2) dest.push(o2[i]);
						} else {
							for(var i in o2) dest[i] = o2[i];
						}
					}
				}
				
				return dest;
			},
			
			/**
			 * Raw value output
			 * Currently automatic escaping is not supported, so there is no need
			 * for this, we have it only for compatibility
			 * 
			 * @param {Object} obj Object
			 */
			'raw': function (obj) {
				return obj;
			},
			
			/**
			 * Supra.Intl filter
			 * Returns internationalized string
			 * 
			 * @param {String} ns Namespace
			 * @return Internationalized string
			 * @type {String}
			 */
			'intl': function (ns) {
				return Supra.Intl.get(ns.split('.'));
			},
			
			/**
			 * Y.DataType.Date.reformat
			 * Returns formated date
			 * 
			 * @param {String} date Date
			 * @return Formated date
			 * @type {String}
			 */
			'date': function (date) {
				return Y.DataType.Date.reformat(date, 'in_date', 'out_date');
			},
			'datetime': function (date) {
				return Y.DataType.Date.reformat(date, 'in_datetime', 'out_datetime');
			},
			'datetime_short': function (date) {
				return Y.DataType.Date.reformat(date, 'in_datetime_short', 'out_datetime_short');
			},
			'time': function (date) {
				return Y.DataType.Date.reformat(date, 'in_time', 'out_time');
			},
			'time_short': function (date) {
				return Y.DataType.Date.reformat(date, 'in_time_short', 'time_short');
			}
		},
		
		/**
		 * Functions
		 */
		'functions': {
			
			/**
			 * Cycle on an array of values
			 * 
			 * @param {Array} arr Array
			 * @param {Number} index Offset index
			 * @return Array value
			 * @type {Object}
			 */
			'cycle': function (arr, index) {
				var len = arr.length;
				index = parseInt(index, 10) || 0;
				return arr[index % len];
			},
			
			/**
			 * Returns a list containing an arithmetic progression of integers
			 * 
			 * @param {Number} from
			 * @param {Number} to
			 * @param {Number} step Optional. Default is 1
			 * @return Array of integers
			 * @type {Array}
			 */
			'range': function (from, to, step) {
				step = step || 1;
				from = parseInt(from, 10) || 0;
				to = parseInt(to, 10) || 0;
				step = parseInt(step, 10) || 0;
				
				//Prevent infinite loop
				if ((from < to && step < 0) || (from > to && step > 0)) return [];
				
				var arr = [];
				for(var i=from; i<=to; i+=step) {
					arr.push(i);
				}
				return arr;
			}
		},
		
		/**
		 * Expressions
		 */
		'compilerExpressions': {
			// {% set x = 10 %}
			'set': function (expression) {
				return C.compileVar(expression) + ';';
			},
			
			// {% debug x %}
			'debug': function (expression) {
				return '_c.debug(' + C.compileVar(expression) + ');';
			},
			
			// {% if x > 10 %}
			'if': function (expression) {
				if (Options.validate) {
					//Error validation
					Stack.unshift('if');
				}
				return 'if(' + C.compileVar(expression) + '){';
			},
			// {% elseif x < 10 %}
			'elseif': function (expression) {
				if (Options.validate) {
					//Error validation
					if (Stack[0] != 'if') throw new Error('Error_Syntax: missing opening "if" expression for "elseif"');
				}
				return '}else if(' + C.compileVar(expression) + '){';
			},
			// {% else %}
			'else': function (expression) {
				if (Options.validate) {
					//Error validation
					if (Stack[0] != 'if') throw new Error('Error_Syntax: missing opening "if" expression for "else"');
				}
				return '}else{';
			},
			// {% endif %}
			'endif': function (expression) {
				if (Options.validate) {
					//Error validation
					if (Stack[0] != 'if') throw new Error('Error_Syntax: missing opening "if" expression for "endif"');
					Stack.shift();
				}
				return '}';
			},
			
			// {% for key, value in variable %}
			'for': function (expression) {
				var var_name_tmp = C.generateVariable(),
					var_key_tmp = C.generateVariable(),
					var_key,
					var_val,
					var_name,
					out;
				
				//Find key, value and array variables
				expression.replace(REG_FOR, function (all, key, value_, val, name) {
					if (val) {
						var_key = C.compileVar(key);
						var_val = C.compileVar(val);
					} else {
						var_val = C.compileVar(key);
					}
					var_name = C.compileVar(name);
				});
				
				out = 'var ' + var_name_tmp + '=' + var_name + ';';
				out+= 't=_f.length(' + var_name_tmp + ');_d.loop={first:true,last:false,length:t,index:1,index0:0,revindex0:t-1,revindex:t};'
				out+= 'for(var ' + var_key_tmp + ' in ' + var_name_tmp + '){';
				out+= 'if(' + var_name_tmp + '.hasOwnProperty(' + var_key_tmp + ')){';
				out+= var_val + '=' + var_name_tmp + '[' + var_key_tmp + '];'
				
				if (var_key) {
					out+= var_key + '=' + var_key_tmp + ';';
				}
				
				//Error validation
				if (Options.validate) Stack.unshift('for');
				
				return out;
			},
			// {% endfor %}
			'endfor': function (expression) {
				if (Options.validate) {
					//Error validation
					if (Stack[0] != 'for') throw new Error('Error_Syntax: missing opening "for" expression for "endfor"');
					Stack.shift();
				}
				
				return '_d.loop.index++;_d.loop.index0++;_d.loop.revindex--;_d.loop.revindex0--;_d.loop.first=false;_d.loop.last=_d.loop.index==_d.loop.length;} }';
			},
			// {% include "TemplateId" %}
			'include': function (template) {
				return 'p+=Supra.Template(' + C.compileVar(template) + ', _d);';
			}
		}
	};
	
	var Options				= null,	//Compiler options
		Stack				= null,	//Validation stack
		
		TAG_VAR_OPEN 		= '{{',
		TAG_VAR_CLOSE 		= '}}',
		TAG_EXPR_OPEN 		= '{%',
		TAG_EXPR_CLOSE 		= '%}',
		TAG_COMMENT_OPEN	= '{#',
		TAG_COMMENT_CLOSE	= '#}',
		
		ESC_VAR_OPEN		= Y.Escape.regex(TAG_VAR_OPEN),
		ESC_VAR_CLOSE		= Y.Escape.regex(TAG_VAR_CLOSE),
		ESC_EXPR_OPEN 		= Y.Escape.regex(TAG_EXPR_OPEN),
		ESC_EXPR_CLOSE 		= Y.Escape.regex(TAG_EXPR_CLOSE),
		ESC_COMMENT_OPEN	= Y.Escape.regex(TAG_COMMENT_OPEN),
		ESC_COMMENT_CLOSE	= Y.Escape.regex(TAG_COMMENT_CLOSE),
		
		REG_CDATA_OPEN		= /(\/\/|\/\*)?\s*<\!\[CDATA\[\s*(\*\/)?/,
		REG_CDATA_CLOSE		= /(\/\/|\/\*)?\s*\]\]>\s*(\*\/)?/,
		REG_COMMENTS		= new RegExp(ESC_COMMENT_OPEN + '.*?' + ESC_COMMENT_CLOSE, 'g'),
		
		REG_ESCAPE			= new RegExp("[.*+?|()\\[\\]{}\\\\]", "g"), // .*+?|()[]{}\;
		REG_FILTER_TITLE	= /^([a-z])|\s+([a-z])/g,
		REG_WHITESPACE		= /[\r\n\t\s]+/g,
		REG_TEXT			= new RegExp('(^|' + ESC_VAR_CLOSE + '|' + ESC_EXPR_CLOSE + ')(.*?)($|' + ESC_VAR_OPEN + '|' + ESC_EXPR_OPEN + ')', 'g'),
		REG_TAG_WHITESPACE	= new RegExp('(' + ESC_VAR_OPEN + ')\\s+|\\s+(' + ESC_VAR_CLOSE + ')|(' + ESC_EXPR_OPEN + ')\\s+|\\s+(' + ESC_EXPR_CLOSE + ')', 'g'),
		REG_STRIP_TAGS		= /<[^>]+>/g,
		
		REG_VAR				= new RegExp(ESC_VAR_OPEN + '(.*?)' + ESC_VAR_CLOSE, 'g'),
		REG_VAR_STRING		= /("[^"]*"|'[^']*')/g,
		REG_VAR_TRIM		= /\s*(,|:|{|}|\(|\))\s*/g,
		REG_VAR_DATA		= /(^|\s|\[|\(|\!|\&|\-|\+|\*|\/|\%|:|,)([a-z])/gi,
		REG_VAR_FN			= /(^|\s|\[|\(|\!|\&)([a-z0-9_]+\()/gi,
		REG_VAR_MODIFIERS	= /([a-z0-9\$_'"\.\,\[\]\(\)\:\{\}]+)\|([a-z0-9_]+)(\(([^)]+)\))?/i,
		REG_EXPR			= new RegExp(ESC_EXPR_OPEN + '\\s*([a-z0-9\\\_]+)(\\s(.*?))?' + TAG_EXPR_CLOSE, 'g'),
		
		REG_CHECK_MODIFIERS	= /[a-z0-9\$_'"\.\,\[\]\(\)\:\{\}]+\|[a-z0-9_]/i,
		
		REG_CAT				= /\s*~\s*/g,
		REG_AND				= /\s+and\s+/g,
		REG_NOT				= /\s+not\s+/g,
		REG_OR				= /\s+or\s+/g,
		REG_FOR				= /\s*([a-z0-9_]+)(\s*,\s*([a-z0-9_]+))?\s+in\s+(.*)/i,
		
		FN_IS_ARRAY			= function (arr) { return arr && arr instanceof Array; },
		FN_TO_ARRAY			= function (obj) { var arr = []; for(var i in obj) if (obj.hasOwnProperty(i)) arr.push(obj[i]); return arr; },
		
		// Escape HTML attribute character
		escape_html_attr    = function (matches) {
			var chr = matches[0],
				ord = chr.charCodeAt(0),
				hex = '',
				entities = {34: 'quot', 38: 'amp', 60: 'lt', 62: 'gt'};
			
			if (entities[ord]) {
				return '&' + entities[ord] + ';';
			}
			
			// Characters undefined in HTML
			if ((ord <= 0x1f && chr != "\t" && chr != "\n" && chr != "\r") || (ord >= 0x7f && ord <= 0x9f)) {
				return '&#xFFFD;';
			}
		    
			hex = ('00' + ord.toString(16)).substr(-2).toUpperCase();
			return '&#x' + hex + ';';
		};
	
	C.filters.e = C.filters.escape;
	
	Supra.TemplateCompiler = C;
	
	/*
	 * @TODO Support for:
	 * 		{% for i in 1..10 %}
	 */
	
}, YUI.version);