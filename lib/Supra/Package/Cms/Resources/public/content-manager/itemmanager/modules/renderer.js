YUI.add('itemmanager.renderer', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Manager = Supra.Manager,
	
		BROKEN_IMAGE_URL = '/public/cms/supra/img/medialibrary/icon-broken-plain.png',
		
		REGEX_INTERNAL_CLASSNAME = /(^|\s)(yui3\-[a-zA-Z0-9\-_]+|su\-[a-zA-Z0-9\-_]+|editing)/g,
		REGEX_TAGNAME = /^\s*<([a-z]+)(.*)/i,
		REGEX_CLASSNAME = /^\s*<[^>]+\sclass=("[^"]+"|'[^']+'|[^>\s]+)/i,
		
		DEFAULT_WRAPPER_TEMPLATE = '<ul>{{ items }}</ul>',
		DEFAULT_ITEM_TEMPLATE = '<li>{{ image }}<p>{{ title }}</p></li>',
		
		NEW_ITEM_TEMPLATE_CONTENT =
			'<span class="supra-itemmanager-new-wrapper">' +
				'<span class="supra-itemmanager-new-first su-inline-box su-box-reset">{{ "itemmanager.empty"|intl }}</span>' +
				'<span class="supra-itemmanager-new-text su-inline-box su-box-reset"><span class="su-inline-box su-box-reset"></span>{{ "itemmanager.add_item"|intl }}</span>' +
			'</span>',
		
		TAG_NAME_LIST = {
			// Lists
			'ul': 'li', 'ol': 'li',
			// Table
			'tr': 'td',
			'tbody': 'tr', 'table': 'tr',
			// Inline
			'span': 'span', 'em': 'span', 'i': 'span', 'b': 'span', 'strong': 'span',
			'small': 'span', 'a': 'span', 'u': 'span', 's': 'span', 'q': 'span',
			'sub': 'span', 'sup': 'span'
		};
	
	
	function Renderer () {
		Renderer.superclass.constructor.apply(this, arguments);
	}
	
	Renderer.NAME = 'itemmanager-renderer';
	Renderer.NS = 'renderer';
	
	Renderer.ATTRS = {
		// Element, from which page structure should be recreated
		'contentElement': {
			value: null
		},
		
		// Item template
		'itemTemplate': {
			value: null
		},
		
		// Wrapper template
		'wrapperTemplate': {
			value: null
		},
		
		// Item properties
		'properties': {
			value: null
		}
	};
	
	Y.extend(Renderer, Y.Plugin.Base, {
		
		/**
		 * Templates compiled into function
		 * @type {Object|Null}
		 */
		templateFunctions: null,
		
		
		// Creates HTML for each input type
		propertyRenderers: {
			'InlineString': function (id, property, value) {
				var escaped = Y.Escape.html(value || '');	
				return '<span id="' + id + '" class="su-content-inline su-input-string-inline">' + escaped + '</span>';
			},
			'InlineText': function (id, property, value) {
				var escaped = Y.Escape.html(value || '');	
				return '<span id="' + id + '" class="su-content-inline su-input-text-inline">' + escaped + '</span>';
			},
			'InlineHtml': function (id, property, value) {
				// @TODO Can't output value.html, because of the macros
				return '<div id="' + id + '" class="su-content-inline su-input-html-inline"></div>';
			},
			'InlineImage': function (id, property, value) {
				var imageHTML = '',
					image = Y.DataType.Image.parse(value),
					path;
				
				if (value && (path = Supra.getObjectValue(value, ['image', 'sizes', 'original', 'external_path']))) {
					imageHTML = '<span id="' + id + '" class="supra-image" style="' +
									'width: ' + image.crop_width + 'px;' +
									'height: ' + image.crop_height + 'px;' +
								'">' +
									'<img src="' + path + '" alt="" style="' +
										'width: ' + image.size_width + 'px;' +
										'height: ' + image.size_height + 'px;' +
										'margin-left: ' + (-image.crop_left) + 'px;' +
										'margin-top: ' + (-image.crop_top) + 'px;' +
									'" />' +
								'</span>';
				} else {
					imageHTML = '<span id="' + id + '" class="supra-image">' +
									'<img src="/public/cms/supra/img/px.gif" alt="" />' +
								'</span>';
				}
				
				return '<span class="su-content-inline su-input-inline-image">' + imageHTML + '</span>';
			},
			'Default': function (id, property, value) {
				var type = typeof value;
				
				if (type === 'string') {
					return Y.Escape.html(value);
				} else if (type === 'number') {
					return value;
				} else {
					return '';
				}
			}
		},
		
		/**
		 * 
		 */
		initializer: function () {
		},
		
		destructor: function () {
		},
		
		/**
		 * Returns all HTML needed to display list of items
		 *
		 * @param {Array} data All data
		 */
		getCompleteHTML: function (data) {
			var html = [],
				outterHTML = this.getOutterHTML(),
				templates  = this.getTemplateFunctions(),
				i = 0,
				ii;
			
			// New item
			html.push(this.getTemplateFunctions().newItem());
			
			// Wrapper
			html = templates.wrapper({'items': html.join('')});
			
			return outterHTML[0].concat(html, outterHTML[1]).join('');
		},
		
		/**
		 * Returns item HTML
		 *
		 * @param {Object} data Item data
		 * @returns {String} Item HTML
		 */
		getItemHTML: function (data, index) {
			var properties	= this.get('properties'),
				templates	= this.getTemplateFunctions(),
				i			= 0,
				ii			= properties.length,
				values		= {},
				id,
				property;
			
			if (!data.__suid) {
				// Generate random ID
				data.__suid = Y.guid();
			}
			
			values.__suid = data.__suid;
			
			for (; i < ii; i++) {
				property = properties[i];
				id = property.name || property.id;
				
				if (property.type in this.propertyRenderers) {
					values[id] = this.propertyRenderers[property.type]('collection_' + index + '_' + id, property, data[id]);
				} else {
					values[id] = this.propertyRenderers.Default('collection_' + index + '_' + id, property, data[id]);
				}
			}
			
			return templates.item(values);
		},
		
		/**
		 * Returns outter HTML for item manager
		 *
		 * @returns {Array} Array where first item is string with HTML before
		 * container and second item is string with HTML after container
		 */
		getOutterHTML: function () {
			var element = this.get('contentElement').getDOMNode(),
				html_before = [],
				html_after  = [],
				
				regexClassName = REGEX_INTERNAL_CLASSNAME,
				
				elementTagName,
				elementClassName,
				elementAttrId,
				
				first = true;
			
			while (element && element.tagName) {
				elementTagName = element.tagName.toLowerCase();
				elementClassName = (element.getAttribute('class') || '').replace(regexClassName, ' ').trim();
				
				elementAttrId = element.getAttribute('id') || '';
				if (elementAttrId && elementAttrId.indexOf('yui_') !== -1) {
					elementAttrId = '';
				}
				
				if (elementTagName === 'body') {
					// Add wrapper for content inside body, which will be 100% height of content
					// it's needed for HTML5 drag and drop events to work correctly
					html_before.unshift('<div class="yui3-inline-reset yui3-box-reset supra-itemmanager-wrapper">\n');
					html_after.push('</div>\n');
					
					// Add class to the body
					elementClassName += ' supra-itemmanager';
				} else if (first) {
					// Add class to the wrapper
					first = false;
					elementClassName += ' editing';
				}
				
				html_before.unshift(
					'<' + elementTagName + 
						(elementTagName === 'html' ? ' lang=' + (element.getAttribute('lang') || '') : '') +
						(elementClassName ? ' class="' + elementClassName + '"' : '') +
						(elementAttrId ? ' id="' + elementAttrId + '"' : '') + '>\n');
				
				html_after.push('</' + elementTagName + '>\n');
				
				element = element.parentNode;
			}
			
			// Add stylesheets
			html_before[0] += '<head>\n' + this.getOutterHTMLLinks() + '</head>\n';
			
			return [html_before, html_after];
		},
		
		/**
		 * Returns HTML for link tags
		 * 
		 * @returns {String} HTML for link tags
		 * @private
		 */
		getOutterHTMLLinks: function () {
			// Recreate styles
			var doc = this.getOriginalDocument(),
				fontNodes = null,
				
				// <link />
				links = Y.Node(doc).all('link[rel="stylesheet"]'),
				i = 0,
				ii = links.size(),
				
				linkHref = '',
				linkMedia = '',
				
				linkHrefExtra  = '',
				
				// <style />
				styles = Y.Node(doc).all('style[type="text/css"]'),
				s = 0,
				ss = styles.size(),
				
				styleMedia = '',
				
				stylesheets = [];
			
			for (; i < ii; i++) {
				linkHref = links.item(i).getAttribute('href') || '';
				linkMedia = links.item(i).getAttribute('media') || 'all';
				
				stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHref + '" media="' + linkMedia + '" />');
			}
			
			for (; s < ss; s++) {
				styleMedia = styles.item(s).getAttribute('media') || 'all';
				stylesheets.push('<style type="text/css" media="' + linkMedia + '">' + styles.item(s).get('innerHTML') + '</style>');
			}
			
			// Item Manager stylesheet for new item, drag and drop, etc. styles
			linkHrefExtra = Manager.Loader.getActionInfo('ItemManager').folder + 'modules/itemlist.css';
			stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHrefExtra + '" />');
			
			// Google fonts
			fontNodes = Supra.GoogleFonts.getLinkNodes(doc);
			
			for (i=0, ii=fontNodes.length; i < ii; i++) {
				stylesheets.push('<link rel="stylesheet" type="text/css" href="' + fontNodes[i].getAttribute('href') + '" />');
			}
			
			return stylesheets.join('');
		},
		
		/**
		 * Returns original iframe document
		 * 
		 * @returns {Object} Original iframe document in which is block which is being edited
		 */
		getOriginalDocument: function () {
			return this.get('contentElement').getDOMNode().ownerDocument;
		},
		
		
		/* ------------------------- Attributes ------------------------- */
		
		
		/**
		 * Returns template function
		 *
		 * @returns {String} Template function
		 */
		getTemplateFunctions: function () {
			if (this.templateFunctions) {
				return this.templateFunctions;
			} else {
				var itemTemplate = this.get('itemTemplate') || DEFAULT_ITEM_TEMPLATE,
					wrapperTemplate = this.get('wrapperTemplate') || DEFAULT_WRAPPER_TEMPLATE,
					newItemTemplate,
					tagNameContainer = this.get('contentElement').get('tagName').toLowerCase(),
					tagMatch,
					tagName = TAG_NAME_LIST[tagNameContainer] || 'div',
					className;
				
				// Wrapper
				tagMatch = wrapperTemplate.match(REGEX_TAGNAME);
				if (!tagMatch) {
					wrapperTemplate = DEFAULT_WRAPPER_TEMPLATE;
					tagMatch = wrapperTemplate.match(REGEX_TAGNAME);
				}
				
				wrapperTemplate = '<' + tagMatch[1] + ' data-wrapper="true"' + tagMatch[2];
				
				// Item
				tagMatch = itemTemplate.match(REGEX_TAGNAME);
				if (!tagMatch) {
					itemTemplate = DEFAULT_ITEM_TEMPLATE;
					tagMatch = itemTemplate.match(REGEX_TAGNAME);
				}
				
				itemTemplate = '<' + tagMatch[1] + ' data-item="{{ __suid }}"' + tagMatch[2];
				
				// Create new item template
				className = itemTemplate.match(REGEX_CLASSNAME);
				newItemTemplate = '<' + tagMatch[1] + ' data-new-item="true" class="supra-itemmanager-new su-inline-box su-box-reset' + (className ? ' ' + className[1].replace(/['"]/g, '') : '') + '">' + NEW_ITEM_TEMPLATE_CONTENT + '</' + tagMatch[1] + '>';
				
				this.templateFunctions = {
					'item': Supra.Template.compile(itemTemplate),
					'wrapper': Supra.Template.compile(wrapperTemplate),
					'newItem': Supra.Template.compile(newItemTemplate)
				};
				
				return this.templateFunctions;
			}
		}
		
	});
	
	
	Supra.ItemManagerRenderer = Renderer;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.template', 'supra.google-fonts', 'plugin']});
