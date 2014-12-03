/**
 * jQuery plugin for limiting input allowed characters or patterns
 * 
 * @version 1.0.1
 * @description
 * $(...).valueMask prevents user from entering characters which are not in the list
 * $(...).maxLength adds support for limiting maximum length for textarea
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	/**
	 * Escape special characters to use string in regular expression
	 * 
	 * @param {String} str
	 * @return Escaped string
	 * @type {String}
	 */
	$.strToRegExp = function (str) {
	    if (typeof str == 'object' || typeof str == 'function') return str;
	    str = new String(str);
	    str = str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
	    return RegExp('[' + str + ']');
	};
	
	$.getSelectionStart = function (inp) {
		if (inp.createTextRange) {
			var r = document.selection.createRange().duplicate();
				r.moveEnd('character', inp.value.length);
			
			return r.text ? inp.value.lastIndexOf(r.text) : inp.value.length;
		} else {
			return inp.selectionStart;
		}
	};
	
	$.getSelectionEnd = function (inp) {
		if (inp.createTextRange) {
			var r = document.selection.createRange().duplicate();
				r.moveStart('character', - inp.value.length);
				
			return r.text.length;
		} else {
			return inp.selectionEnd;
		}
	};
	
	/**
	 * Plugin to disable typing if value doesn't matches regular expression
	 * @param {Object} characters
	 */
	$.fn.valueMask = function (characters) {
		var complex = typeof characters != 'string',
			regex = complex ? characters : $.strToRegExp(characters),
			input_event = 'oninput' in document.createElement('INPUT'),
			last_value = $(this).val();
		
		$(this).data('input_value', regex);
		$(this).data('input_value_complex', !!complex);
		
		if (input_event) {
			$(this)
			  .bind('input', function (ev) {
				var value = $(this).val();
				if (complex) {
					if (!regex.test(value)) {
						$(this).val(last_value);
					} else {
						last_value = value;
					}
				} else {
					var out = '',
						character = '';
					
					for (var i=0,ii=value.length; i<ii; i++) {
						character = value.charAt(i);
						if (regex.test(character)) {
							out += character;
						}
					}
					
					if (out != value) {
						$(this).val(out);
						last_value = out;
					}
				}
			  });
		} else {
			//Fallback for browsers not supporting "input" event
			$(this)
			  .keypress(function (ev) {
				var key = (typeof ev.charCode != 'undefined' ? ev.charCode : ev.keyCode);
				
				if (key) {
					var s = String.fromCharCode(key);
					var self = $(this);
					var val = self.val();
					var sel_start = $.getSelectionStart(this);
					var sel_end = $.getSelectionEnd(this);
					var val = val.substr(0, sel_start) + s + val.substr(sel_end).replace(/^\s*|\s*$/, '');
	
					if (ev.ctrlKey && key == 118) return;
					if (!regex.test(val)) return false;
				}
			});
		}
		
		$(this)
		  .bind('focus', function (ev) {
		  	if (complex) {
		  		last_value = $(this).val();
		  	}
		  })
		  .change(function () {
		  	if (complex) {
		  		last_value = $(this).val();
		  	} else {
			  	var value = '',
					current = $(this).val(),
					character;
				
				for(var i=0,ii=current.length; i<ii; i++) {
					character = current.charAt(i);
					if (regex.test(character)) value += character;
				}
				
				if (value != current) {
					$(this).val(value);
				}
			}
		  });
		
		return $(this);
	};
	
	/**
	 * Max length for textarea
	 */
	$.fn.maxLength = function () {
		var self = $(this);
		self.each(function () {
			var self = $(this),
				maxlength = parseInt(self.attr('maxlength'));
			
			if (!maxlength || maxlength < 0) return;
			
			self.unbind('keypress.maxlength').bind('keypress.maxlength', function (event) {
				var key = event.charCode || event.which;
				if (key && self.val().length >= maxlength) return false;
			});
			self.unbind('blur.maxlength').bind('blur.maxlength', function (event) {
				var val = self.val();
				if (val > maxlength) self.val(val.substr(0, maxlength));
			});
		});
		return self;
	};
	
}));