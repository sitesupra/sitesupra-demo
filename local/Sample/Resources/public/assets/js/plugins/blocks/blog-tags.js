/**
 * Blog tag block
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/blocks/tabs'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var substitute = function (tpl, data) {
		var key = null;
		
		for (key in data) {
			tpl = tpl.replace('{{ ' + key + ' }}', data[key]);
		}
		
		return tpl;
	};
	
	// Escape HTML attribute characters to be safe to use in 
	var escapeHTMLAttr = function (matches) {
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
	    
		hex = ('00' + ord.toString(16)).toUpperCase();
		hex = hex.substr(-4);
		return '&#x' + hex + ';';
	};
	
	// Escape string
	var escapeString = function (str, type) {
		if (!type || type === 'html') {
			return (''+str).replace(/&/g, '&amp;')
						   .replace(/</g, '&lt;')
						   .replace(/>/g, '&gt;')
						   .replace(/"/g, '&quot;')
						   .replace(/'/g, '&#39;');
		} else if (type == 'html_attr') {
			return (''+str).replace(/[^a-zA-Z0-9,\.\-_]/g, escapeHTMLAttr);
		} else if (type == 'url') {
			// Match php urlencode
			return encodeURIComponent('' + str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
		}
		
		return str;
	};
	
	
	$.fn.blogTags = function () {
		$(this).tabs();
		
		$(this).on('tabChange', function (e, data) {
			var tabs     = $(this).data('tabs'),
				heading  = tabs.heading(data.newVal),
				content  = null,
				template = null,
				active   = null,
				url      = heading.find('a').data('href');
			
			if (url) {
				template = $(this).closest('.block').find('script[type="text/template"]').html();
				active = $(this).data('currentTag');
				content = tabs.content(data.newVal);
				
				// Prevent from loading again when clicked
				heading.find('a').data('href', '');
				
				// Load content
				$.getJSON(url).done(function (data) {
					var tags  = data.tags,
						i     = 0,
						ii    = tags.length,
						html  = '',
						nodes = null,
						url   = '';
					
					for (; i<ii; i++) {
						if (tags[i].name) {
							url = escapeString(tags[i].name, 'url');
							
							html += substitute(template, {
								'active': active == url ? 'active' : '',
								'tag.name': escapeString(tags[i].name, 'html'),
								'tag.url': url
							});
						}
					}
					
					content.css('opacity', 0).html(html).animate({'opacity': 1}, 'fast');
				});
			}
		});
	};
	
}));