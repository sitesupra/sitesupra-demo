YUI.add('supra.input-select-visual', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Vertical button list for selecting value
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-select-visual';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Input.ATTRS = {
		/**
		 * Icon background color
		 */
		'backgroundColor': {
			value: 'transparent',
			setter: '_setBackgroundColor'
		},
		
		/**
		 * Style:
		 * "" or "no-labels", "mid"
		 */
		'style': {
			value: '',
			setter: '_setStyle'
		},
		
		/**
		 * Icon image style:
		 * "center", "fill" or "button", "html"
		 */
		'iconStyle': {
			value: 'center',
			setter: '_setIconStyle'
		},
		
		/**
		 * Loading state
		 */
		'loading': {
			value: false,
			setter: '_setLoading'
		},
		
		/**
		 * Loading icon
		 */
		'nodeLoading': {
			value: null
		},
		
		/**
		 * Additional CSS if icon style is HTML
		 */
		"css": {
			value: null,
			setter: '_setCSS'
		},
		"cssNode": {
			value: null
		},
		
		/**
		 * Render widget into separate slide and add
		 * button to the place where this widget should be
		 */
		"separateSlide": {
			value: false
		}
	};
	
	Input.HTML_PARSER = {
		'backgroundColor': function (srcNode) {
			return srcNode.getAttribute('suBackgroundColor') || 'transparent';
		},
		'iconStyle': function (srcNode) {
			if (srcNode.getAttribute('suIconStyle')) {
				return srcNode.getAttribute('suIconStyle') || '';
			}
		}
	};
	
	Y.extend(Input, Supra.Input.SelectList, {
		
		widgets: null,
		
		
		/**
		 * On desctruction life cycle remove created slides
		 * and inputs
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.widgets) {
				var slideshow = this.get('slideshow'),
					inputs = this.widgets.inputs,
					slides = this.widgets.slides,
					key = null;
				
				if (slideshow) {
					
					for (key in inputs) {
						inputs[key].destroy();
					}
					for (key in slides) {
						slideshow.removeSlide(key);
					}
					
				}
				
				this.widgets = null;
			}
		},
		
		renderUI: function () {
			this.widgets = {
				// Separate slide
				'slide': null,
				'button': null,
				
				// Values slides and inputs
				'slides': {},
				'inputs': {}
			};
			
			Input.superclass.renderUI.apply(this, arguments);
			
			//Classnames, etc.
			var boundingBox = this.get("boundingBox"),
				classname;
			
			boundingBox.removeClass(Supra.Input.SelectList.CLASS_NAME);
			
			if (this.get('style')) {
				classname = Y.ClassNameManager.getClassName(Input.NAME, this.get('style'));
				boundingBox.addClass(classname);
			}
			
			if (this.get('iconStyle')) {
				classname = this.getClassName(this.get('iconStyle'));
				boundingBox.addClass(classname);
				
				if (this.get('iconStyle') == 'html') {
					this.set('css', this.get('css'));
				}
			}
			
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow(),
					slide = null,
					button = null;
				
				if (slideshow) {
					this.widgets.button = button = new Supra.Button({
						'label': this.get('label')
					});
					
					this.widgets.slide = slide = slideshow.addSlide('propertySlide' + this.get('id'));
					slide = slide.one('.su-slide-content');
					
					button.render();
					button.addClass('button-section');
					button.on('click', this._slideshowChangeSlide, this);
					this.get('boundingBox').insert(button.get('boundingBox'), 'before');
					
					slide.append(this.get('boundingBox'));
				} else {
					this.set('separateSlide', false);
				}
			}
		},
		
		renderButton: function (input, definition, first, last, button_width) {
			var contentBox = this.get('contentBox'),
				button = new Supra.Button({'label': definition.title, 'type': definition.values ? 'button' : 'toggle', 'style': 'group'}),
				value = this._getInternalValue(),
				has_value_match = false,
				
				slideshow = this.getSlideshow(),
				slide = null,
				subinput = null,
				button_value_map = this.button_value_map;
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			button.ICON_TEMPLATE = '<span class="img"><img src="" alt="" /></span>';
			button.LABEL_TEMPLATE = this.getButtonLabelTemplate(definition);
			this.buttons[definition.id] = button;
			
			if (first) {
				button.get('boundingBox').addClass('su-button-first');
			}
			if (last) {
				button.get('boundingBox').addClass('su-button-last');
			}
			
			if (definition.values && slideshow) {
				button.get('boundingBox').addClass('button-section');
				slide = slideshow.addSlide('propertySlide' + this.get('id') + definition.id);
				
				// Create input (self)
				subinput = new Input(
					Supra.mix({
						'values': definition.values,
						'label': definition.title
					}, this.getAttrs(['value', 'backgroundColor', 'css', 'cssNode', 'defaultValue', 'iconStyle', 'multiple', 'renderer', 'showEmptyValue', 'style', 'value']))
				);
				
				subinput.render(slide.one('.su-slide-content'));
				subinput.set('value', this.get('value'));
				
				this.widgets.slides[definition.id] = slide;
				this.widgets.inputs[definition.id] = subinput;
				
				subinput.after('valueChange', this._afterDescendantValueChange, this, definition.id);
				
				// Add sub values to the value list
				if (input && input.options) {
					for (var i=0, ii=definition.values.length; i<ii; i++) {
						input.options[input.options.length] = new Option(definition.values[i].title, definition.values[i].id);
						button_value_map[definition.values[i].id] = definition.id;
					}
				} else {
					for (var i=0, ii=definition.values.length; i<ii; i++) {
						button_value_map[definition.values[i].id] = definition.id;
					}
				}
			}
			
			if (input && input.options) {
				//Add options to allow selecting value
				input.options[input.options.length] = new Option(definition.title, definition.id);
				if (value == definition.id) input.value = value;
			}
			
			if (definition.id == value) {
				//Mark value as found
				has_value_match = true;
			}
			
			button.render(contentBox);
			
			//Set button width
			button.get('boundingBox').setStyle('width', button_width + '%');
			
			//On click update input value
			if (definition.values && slideshow) {
				button.on('click', this._slideshowChangeSlide, this, definition.id);
			} else {
				button.on('click', this._onClick, this, definition.id);
			}
			
			return has_value_match;
		},
		
		/**
		 * Returns button label template
		 * 
		 * @return Label template
		 * @type {String}
		 * @private
		 */
		getButtonLabelTemplate: function (definition) {
			if (this.get('iconStyle') == 'html') {
				return '<div class="su-button-bg"><div>' + (definition.html || '') + '</div><p></p></div>';
			} else {
				return '<div class="su-button-bg"><div style="' + this.getButtonBackgroundStyle(definition) + '"></div><p></p></div>';
			}
		},
		
		/**
		 * Returns button background style
		 * 
		 * @param {Object} definition Button definition
		 * @return Background CSS style
		 * @type {String}
		 * @private
		 */
		getButtonBackgroundStyle: function (definition) {
			var style = 'background-color: ' + this.get('backgroundColor') +';';
			
			if (definition.icon) {
				if (this.get('iconStyle') == 'button') {
					style += 'background-image: url(' + definition.icon + '), url(' + definition.icon + '), url(' + definition.icon + ');';
				} else {
					style += 'background-image: url(' + definition.icon + ');';
				}
			}
			
			return style;
		},
		
		
		/*
		 * ---------------------------------------- EVENT LISTENERS ----------------------------------------
		 */
		
		
		/**
		 * Change slideshow slide to values list
		 * 
		 * @private
		 */
		_slideshowChangeSlide: function (event, id) {
			var slideshow = this.getSlideshow(),
				slide_id  = 'propertySlide' + this.get('id');
			
			if (id) {
				slide_id += id;
			}
			
			slideshow.set('slide', slide_id);
		},
		
		/**
		 * After value change
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
				
				var inputs = this.widgets.inputs,
					id = null;
				
				for (id in inputs) {
					if (inputs[id].get('value') != evt.newVal) {
						inputs[id].set('value', evt.newVal);
					}
				}
			}
		},
		
		/**
		 * After sub-input value change
		 * 
		 * @param {Object} evt Event facade object
		 * @param {Object} id Descendant id
		 * @private
		 */
		_afterDescendantValueChange: function (evt, id) {
			if (evt.prevVal != evt.newVal) {
				if (this.get('value') != evt.newVal) {
					this.set('value', evt.newVal);
				}
			}
		},
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Returns parent widget by class name
		 * 
		 * @param {String} classname Parent widgets class name
		 * @return Widget instance or null if not found
		 * @private
		 */
		getParentWidget: function (classname) {
			var parent = this.get("parent");
			while (parent) {
				if (parent.isInstanceOf(classname)) return parent;
				parent = parent.get("parent");
			}
			return null;
		},
		
		/**
		 * Returns slideshow
		 * 
		 * @return Slideshow
		 * @type {Object}
		 * @private
		 */
		getSlideshow: function () {
			var form = this.getParentWidget("form");
			return form ? form.get("slideshow") : null;
		},
		
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Background color attribute setter
		 * 
		 * @param {String} value Background color
		 * @return New background color attribute value
		 * @type {String}
		 * @private
		 */
		_setBackgroundColor: function (value) {
			var nodes = this.get('boundingBox').all('.su-button-bg div');
			
			nodes.setStyle('backgroundColor', value);
			
			return value;
		},
		
		/**
		 * Icon style attribute setter
		 * 
		 * @param {String} value Style value
		 * @return New icon style attribute value
		 * @type {String}
		 * @private
		 */
		_setIconStyle: function (value) {
			var prev = this.get('iconStyle'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = this.getClassName(prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = this.getClassName(value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		},
		
		/**
		 * Loading attribute setter
		 * 
		 * @param {Boolean} loading Loading attribute value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setLoading: function (loading) {
			var box = this.get('contentBox');
			
			if (box) {
				if (loading && !this.get('nodeLoading')) {
					var node = Y.Node.create('<span class="loading-icon"></span>');
					box.append(node);
					this.set('nodeLoading', node);
				}
				
				box.toggleClass(this.getClassName('loading'), loading);
			}
			
			this.set('disabled', loading);
			return loading;
		},
		
		/**
		 * CSS attribute setter
		 * 
		 * @param {String} css CSS styles
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setCSS: function (css) {
			var box = this.get('boundingBox'),
				node = this.get('cssNode'),
				id = null;
			
			if (css && this.get('iconStyle') == 'html') {
				id = this.get('id');
				
				//Prepend styles with ID
				css = css.replace(/[^\{\}]+[\{]/g, function (match) {
					match = match.split(',');
					return '#' + id + ' ' + match.join(', #' + id + ' ');
				});
				
				if (!node) {
					node = Y.Node.create('<style></style>');
				}
				
				// Set style content
				var domNode = node.getDOMNode();
				if ('innerText' in domNode) {
					// Chrome
					node.getDOMNode().innerText = css;
				} else {
					// FF
					node.getDOMNode().innerHTML = css;
				}
				
				box.append(node);
				this.set('cssNode', node);
				
				//We want to set only once
				return '';
			}
			
			return css;
		},
		
		/**
		 * Style attribute setter
		 * We overwrite select list setter, because we don't want extended classes to
		 * have 'style' classnames prefixed with their names which would break
		 * existing styles
		 * 
		 * @param {String} value Style value
		 * @returns {String} New style attribute value
		 * @private
		 */
		_setStyle: function (value) {
			var prev = this.get('style'),
				classname = null;
			
			if (prev != value) {
				if (prev) { 
					classname = Y.ClassNameManager.getClassName(Input.NAME, prev);
					this.get('boundingBox').removeClass(classname);
				}
				if (value) {
					classname = Y.ClassNameManager.getClassName(Input.NAME, value);
					this.get('boundingBox').addClass(classname);
				}
			}
			
			return value;
		}
		
	});
	
	Supra.Input.SelectVisual = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-select-list']});