/**
 * Form block - In CMS on property change update input label, button label, etc.
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var field_template = null,
		field_target   = null;
	
	function isFieldDifferent (a, b) {
		for (var key in b) {
			if (a[key] != b[key]) return true;
		}
		return false;
	}
	function render (index, data) {
		var node = $(field_template({'loop': {'index': index}, 'field': data}));
		node.attr('data-index', index);
		return node;
	}
	
	$.refresh.on('update/form', function (event, info) {
		
		switch (info.propertyName) {
			case "submit":
				// Update submit button label
				var node = info.target.find('div.form-footer');
				
				if (info.propertyValue) {
					node.removeClass('hidden').find('button').text(info.propertyValue);
				} else {
					node.addClass('hidden');
				}
				break;
			case "error":
				var escaped = $('<div />').text(info.propertyValue).html();
				info.target.data('error-message', escaped);
				break;
			case "captcha":
				// Show / hide captcha field
				var node = info.target.find('span.input-captcha').parent();
				
				if (info.propertyValue) {
					node.removeClass('hidden');
				} else {
					node.addClass('hidden');
				}
				break;
			case "captcha_label":
				// Show / hide captcha field
				var node = info.target.find('span.input-captcha'),
					label = node.find('label'),
					input = node.find('input'),
					children = label.children().clone(true);
				
				label.text(info.propertyValue).append(children);
				input.attr('placeholder', info.propertyValue);
				break;
			case "fields":
				// Update fields for live preview of the form
				var fields = info.propertyValue,
					old_fields = null,
					i      = 0,
					ii     = fields.length,
					node   = null,
					html   = '',
					tpl    = null,
					container = null;
				
				// Find template and render target
				if (!field_template) {
					var script = info.target.find('script[type="text/supra-template"]');
					field_target = script.attr('data-supra-container-selector');
					field_template = script.html();
					field_template = info.supra.Template.compile(field_template);
				}
				
				// 
				container = info.target.find(field_target);
				old_fields = container.data('fields');
				
				if (!old_fields) {
					// Client-side rendering for first time
					container.empty();
					for (; i<ii; i++) {
						container.append( render(i, fields[i]) );
					}
				} else {
					// Check difference and re-render changed
					for (; i<ii; i++) {
						if (old_fields.length - 1 < i) {
							// Add new field
							container.append( render(i, fields[i]) );
						} else if (isFieldDifferent(fields[i], old_fields[i])) {
							// Update existing field
							container.find('[data-index="' + i + '"]').replaceWith( render(i, fields[i]) );
						}
					}
					
					// A field was removed
					for (i=fields.length, ii=old_fields.length; i<ii; i++) {
						container.find('[data-index="' + i + '"]').remove();
					}
				}
				
				container.data('fields', fields);
				break;
		}
		
	});
	
}));