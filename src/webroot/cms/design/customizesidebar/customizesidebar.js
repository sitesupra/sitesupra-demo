//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.input-pattern', {
	path: 'input/pattern.js',
	requires: ['supra.input-select-list']
});
SU.addModule('website.input-fonts', {
	path: 'input/fonts.js',
	requires: ['website.input-pattern']
});

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	'website.input-pattern',
	'website.input-fonts',
	'supra.datatype-color',

function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Color = Y.DataType.Color;
	
	//Additional property information
	var PROPERTIES = {
		"buttons": {
			"buttonColor": {
				"id": "buttonColor",
				"type": "Color",
				
				"property": "backgroundGradient",		//custom function will be used in this case
				"target": ".btn"						//css property which will be changed
			},
			"buttonStyle": {
				"id": "buttonStyle",
				"type": "Pattern",
				
				"property": "classname",
				"target": "body"
			}
		},
		"background": {
			"backgroundColor": {
				"id": "backgroundColor",
				"type": "Color",
				
				"property": "backgroundColor",			//css property which will be changed
				"target": ".su-customize-background"	//css classname which will be changed
			},
			"backgroundPattern": {
				"id": "backgroundPattern",
				"type": "Pattern",
				
				"property": "backgroundImage",			//css property which will be changed
				"target": ".su-customize-background"	//css classname which will be changed
			}
		},
		"menu": {
			"menu": {
				"id": "menu",
				"type": "Pattern",
				
				"property": "classname",				//attribute which will be changed
				"target": "body"						//css selector for element which will be changed
			}
		},
		"fonts": {
			"headingColor": {
				"id": "headingColor",
				"type": "Color",
				
				"property": "color",					//css property which will be changed
				"target": ".su-customize-font-heading"	//css classname which will be changed
			},
			"headingFont": {
				"id": "headingFont",
				"type": "Fonts",
				
				"property": "fontFamily",				//css property which will be changed
				"target": ".su-customize-font-heading"	//css classname which will be changed
			},
			"bodyColor": {
				"id": "bodyColor",
				"type": "Color",
				
				"property": "color",					//css property which will be changed
				"target": ".su-customize-font-body"		//css classname which will be changed
			},
			"bodyFont": {
				"id": "bodyFont",
				"type": "Fonts",
				
				"property": "fontFamily",				//css property which will be changed
				"target": ".su-customize-font-body"		//css classname which will be changed
			}
		}
	};
	
	var PROPERTIES_TO_DATA_MAP = {
		'backgroundPattern': 'backgrounds',
		'menu': 'menus',
		'buttonStyle': 'buttons',
		'headingFont': 'headingFonts',
		'bodyFont': 'bodyFonts'
	};
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'CustomizeSidebar',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Layout container action NAME,
		 * This is PluginLayoutSidebar property
		 */
		LAYOUT_CONTAINER: 'LayoutRightContainer',
		
		
		
		/**
		 * Currently selected customization group ID
		 * @type {String}
		 * @private
		 */
		group_id: null,
		
		/**
		 * Form list
		 * @type {Object}
		 * @private
		 */
		forms: {},
		
		/**
		 * Slideshow list
		 * @type {Object}
		 * @private
		 */
		slideshows: {},
		
		/**
		 * Design data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Loading state
		 * @type {Boolean}
		 * @private
		 */
		loading: false,
		
		/**
		 * Font link node
		 * @type {Object}
		 * @private
		 */
		fontLinkNode: null,
		
		/**
		 * CSS property value cache for gradients
		 * @type {Object}
		 * @private
		 */
		rulesPropertyCache: {},
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Set buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Throttle updatePreview
			this.updatePreview = Supra.throttle(this.updatePreview, 100, this);
		},
		
		/**
		 * Render action widgets, attach event listeners
		 */
		render: function () {
			//Control "Done" button, when clicked we will hide sidebar
			this.get('controlButton').on('click', this.hide, this);
			
			//Back button
			this.get('backButton').on('click', this.onBackButton, this);
			
			//Set font request URI
			this.updateFontRequestURI();
			
			//On iframe fonts change reload font list
			var iframe = Manager.Preview.iframe;
			iframe.after('fontsChange', this.updateFontRequestURI, this);
		},
		
		/**
		 * Update font request URI
		 * 
		 * @private
		 */
		updateFontRequestURI: function () {
			var uri = Manager.Preview.iframe.getFontRequestURI(),
				link = this.fontLinkNode;
			
			if (uri) {
				if (link) {
					link.setAttribute("href", uri);
				} else {
					link = this.fontLinkNode = Y.Node.create('<link href="' + uri + '" rel="stylesheet" type="text/css" />');
					Y.one('head').append(link);
				}
			} else if (link) {
				link.remove();
				this.fontLinkNode = null;
			}
		},
		
		/**
		 * Create form
		 * 
		 * @param {String} group_id Group ID
		 * @private
		 */
		createForm: function (group_id) {
			var srcNode = this.one('#' + group_id + 'Form'),
				properties = PROPERTIES[group_id];
			
			srcNode.removeClass('hidden');
				
			var form = this.forms[group_id] = new Supra.Form({
					'srcNode': srcNode,
					'style': 'vertical'
				});
			
			form.render();
			
			//Inputs
			var inputs = form.getInputs(),
				definition = null,
				input = null,
				id = null;
			
			for(id in inputs) {
				input = inputs[id];
				definition = properties[id];
				
				if (input.isInstanceOf('input-color')) {
					input.on(['valueChange', 'colorChange'], this.updatePreview, this, definition, input);
				} else if (input.isInstanceOf('input-pattern')) {
					input.on('valueChange', this.updatePreview, this, definition, input);
				}
			}
			
			//Slideshow
			var slideshow = this.slideshows[group_id] = new Supra.Slideshow({
				'srcNode': srcNode.one('.slideshow')
			})
			
			slideshow.on('slideChange', this.onSlideChange, this);
			slideshow.render();
			
			//
			if (group_id == 'fonts') {
				slideshow.on('slideChange', function (evt) {
					if (evt.newVal != evt.prevVal && evt.newVal == 'fontsMain') {
						this.styleFontButtons(this.forms.fonts);
					}
				}, this);
			}
			
			return form;
		},
		
		/**
		 * Update preview after one of the input values changes
		 * 
		 * @private
		 */
		updatePreview: function (e, definition, input) {
			if (this.loading) return;
			
			var value = e.newVal,
				values = [],
				iframe = Manager.Preview.iframe,
				styles = {},
				findFn = function (item) { return item.id == value ? item : false; };
			
			if (definition.target && definition.property) {
				if (definition.property == 'backgroundImage') {
					
					//Background image
					var prop = this.data ? Y.Array.find(this.data[PROPERTIES_TO_DATA_MAP[definition.id]], findFn) : [];
					styles[definition.property] = prop ? 'url(' + prop.icon + ')' : 'none';
					
				} else if (definition.property == 'backgroundGradient') {
					
					iframe.updateBackgroundGradient(definition.target, value);
					styles = null;
					
				} else {
					
					//Other properties
					styles[definition.property] = value;
					
				}
				
				if (definition.property == 'classname') {
					
					//Class name
					var target = iframe.one(definition.target),
						values = input.get('values');
					
					for(var i=0,ii=values.length; i<ii; i++) {
						if (values[i].id == value) {
							target.addClass(values[i].id);
						} else {
							target.removeClass(values[i].id);
						}
					}
					
				} else if (styles) {
					
					//CSS style
					iframe.setStylesBySelector(definition.target, styles);
					
				}
			}
			
			this.data.customize[definition.id] = value;
		},
		
		/**
		 * Returns value from data list by input ID and input value
		 * 
		 * @param {String} id Input ID
		 * @param {String} value Input value
		 * @return Item from data list for this input matching value
		 * @type {Object}
		 */
		getDataListValue: function (id, value) {
			var list  = PROPERTIES_TO_DATA_MAP[id],
				array = list ? this.data[list] : [];
			
			return Y.Array.find(array, function (item) {
				return item.id == value ? item : false;
			});
		},
		
		/**
		 * Style font buttons
		 * 
		 * @param {Object} form Form
		 * @private
		 */
		styleFontButtons: function (form) {
			form.get('contentBox').all('a.button-section').each(function (node) {
				var source = null,
					input  = null,
					item   = null,
					color  = null;
				
				//Set font-family
				if ((source = node.getAttribute('data-family-source'))) {
					input = form.getInput(source);
					if (input) {
						item = this.getDataListValue(input.get('id'), input.get('value'));
						if (item) {
							node.one('span span')
								.set('text', item.title)
								.setStyle('fontFamily', item.family);
						}
					}
				}
				
				//Set color
				if ((source = node.getAttribute('data-color-source'))) {
					input = form.getInput(source);
					if (input) {
						color = input.get('value');
						
						//Color
						node.one('span span').setStyle('color', color);
						
						//Background to prevent invisible text
						color = Color.parse(color);
						if (color && (color.red + color.green + color.blue) / 3 > 128) {
							//font is light
							color = '#000';
						} else {
							//font is dark
							color = '#FFF';
						}
						
						node.one('span span').setStyle('backgroundColor', color);
					}
				}
			}, this);
		},
		
		/*
		 * ------------------------------- SLIDESHOW --------------------------------
		 */
		
		/**
		 * On slide change show/hide buttons and call callback function
		 * 
		 * @param {Object} evt
		 * @private
		 */
		onSlideChange: function (evt) {
			var slide_id = evt.newVal,
				slideshow = evt.target;
			
			if (slideshow.isRootSlide()) {
				this.get('backButton').hide();
			} else {
				this.get('backButton').show();
			}

			//Update header title and icon
			var node  = (slide_id ? this.one('#' + slide_id) : null);
			
			if (node) {
				var title = node.getAttribute('data-title'),
					icon  = node.getAttribute('data-icon');
				
				if (title) {
					this.set('title', title);
				}
				if (icon) {
					this.set('icon', icon);
				}
			}
		},
		
		/**
		 * On "Back" button click slide slideshow back
		 * 
		 * @private
		 */
		onBackButton: function () {
			var slideshow = this.slideshows[this.group_id];
			if (slideshow) {
				slideshow.scrollBack();
			}
		},
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		/**
		 * Hide
		 */
		hide: function () {
			this.set('visible', false);
			
			var slideshow = this.slideshows[this.group_id];
			if (slideshow) {
				slideshow.scrollBack();
			}
			
			var form = this.forms[this.group_id];
			if (form) {
				form.hide();
			}
		},
		
		/**
		 * Set form
		 * Called by PageToolbar
		 * 
		 * @param {String} group_id Group ID
		 */
		setForm: function (group_id) {
			this.show();
			
			if (!group_id) return this.hide();
			
			//Loading state enabling to prevent change event from triggering
			//UI change
			this.loading = true;
			
			this.show();
			
			this.group_id = group_id;
			this.data = Manager.getAction('DesignOverview').getData();
			
			//Create form
			if (!this.forms[group_id]) {
				this.createForm(group_id);
			}
			
			this.setFormValues();
			
			//Update title, icon, etc.
			var slideshow = this.slideshows[group_id];
			
			this.onSlideChange({
				'target': slideshow,
				'newVal': slideshow.get('slide')
			});
			
			//
			this.loading = false;
		},
		
		/**
		 * Convert font data into lists
		 * 
		 * @param {Object} fonts Font data
		 * @private
		 */
		fontDataToList: function () {
			var fonts = this.data.fonts,
				key = null,
				list = null,
				l = 0,
				ll = 0,
				out = [];
				
			for(key in fonts) {
				list = fonts[key];
				out = [];
				l = 0;
				ll = list.length;
				
				for(; l<ll; l++) {
					out.push(Supra.mix({
						'id': list[l].title || list[l].family
					}, list[l]));
				}
				
				this.data[key + 'Fonts'] = out;
			}
		},
		
		/**
		 * Set form values
		 */
		setFormValues: function () {
			var form = this.forms[this.group_id],
				lists = PROPERTIES_TO_DATA_MAP,
				input = null,
				data = this.data;
			
			//Set headingFonts and bodyFonts data
			this.fontDataToList();
			
			//Set pattern, menu and button lists
			for(var i in lists) {
				input = form.getInput(i);
				if (input) input.set('values', data[lists[i]]);
			}
			
			//Show form and update values
			form.show();
			form.setValues(data.customize, 'id');
			
			//Set button styles
			this.styleFontButtons(form);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});