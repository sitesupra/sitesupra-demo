YUI.add('supra.form', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var QueryString = Y.QueryString;
	
	//Input configuration defaults
	var INPUT_DEFINITION = {
		'id': null,
		'name': null,
		'label': '',
		'type': 'String',
		'srcNode': null,
		'containerNode': null,
		'labelNode': null,
		'value': '',
		'disabled': false
	};
	
	/**
	 * Class for handling inputs, saving, loading and deleting data 
	 * 
	 * @alias Supra.Form
	 * @param {Object} config Configuration
	 */
	function Form (config) {
		//Fix 'value' references for inputs
		this.fixInputConfigValueReferences(config || {});
		
		Form.superclass.constructor.apply(this, [config || {}]);
		
		this.inputs = {};
		this.inputConfiguration = {};
		this.processAttributes();
	}
	
	Form.NAME = 'form';
	Form.CLASS_NAME = Form.CSS_PREFIX = 'su-' + Form.NAME;
	
	Form.ATTRS = {
		'inputs': {
			value: null
		},
		'autoDiscoverInputs': {
			value: true
		},
		'urlLoad': {
			value: null
		},
		'urlSave': {
			value: null
		},
		'urlDelete': {
			value: null
		},
		'style': {
			value: ''
		},
		/**
		 * Values which user was trying to set, but didn't had inputs
		 * for them
		 */
		'plainValues': {
			value: {}
		},
		'disabled': {
			value: false
		},
		
		/**
		 * Parent widget, could be empty
		 */
		'parent': {
			value: null
		},
		/**
		 * Root parent widget, usually same as parent attribute
		 */
		'root': {
			value: null
		},
		
		/**
		 * Slideshow widget, optional
		 */
		'slideshow': {
			value: null
		},
		
		/**
		 * Supra.Form.PropertyElementLocator widget, optional
		 */
		'propertyElementLocator': {
			value: null
		}
	};
	Form.HTML_PARSER = {
		'urlLoad': function (srcNode) {
			var value = this.get('urlLoad');
			if (value === null && srcNode.test('form')) {
				value = srcNode.getAttribute('action');
			}
			return value ? value : null;
		}
	};
	
	Y.extend(Form, Y.Widget, {
		
		CONTENT_TEMPLATE: "<form autocomplete=\"off\"></form>",
		
		/*
		 * List of input definitions
		 */
		inputConfiguration: null,
		
		/*
		 * List of input fields
		 */
		inputs: null,
		
		/*
		 * New slideshow was created instead of using existing parent slideshow
		 * @protected
		 */
		slideshowCreated: false,
		
		/**
		 * Search for inputs in DOM
		 * 
		 * @private
		 * @return Object with input definitions
		 * @type {Object}
		 */
		discoverInputs: function () {
			var inputs = this.get('srcNode').all('input,textarea,select');
			var config = {};
			
			for(var i=0,ii=inputs.size(); i<ii; i++) {
				var input = inputs.item(i);
				
				//data-supra-ignore allows to skip inputs
				if (input.getAttribute('data-supra-ignore')) continue;
				
				var id = input.getAttribute('id') || input.getAttribute('name');
				var name = input.getAttribute('name') || input.getAttribute('id');
				var value = input.get('value');
				var disabled = input.getAttribute('disabled') ? true : false;
				var label = '';
				
				//If there is no name or id, then input can't be identified
				if (!id || !name) continue;
				
				var tagName = input.get('tagName').toLowerCase();
				var tagType = input.getAttribute('type').toLowerCase();
				var type = 'String';
				
				//Get label
				var labelNode = this.get('srcNode').one('label[for="' + id + '"]');
				if (labelNode) {
					label = labelNode.get('innerHTML');
				}
				
				//Detect type
				var typeAttribute = input.getAttribute('data-type');
				if (typeAttribute) {
					type = typeAttribute;
				} else {
					switch(tagName) {
						case 'textarea':
							type = 'Text'; break;
						case 'select':
							type = 'Select'; break;
						case 'input':
							switch(tagType) {
								case 'hidden':
									type = 'Hidden'; break;
								case 'checkbox':
									type = 'Checkbox'; break;
								case 'radio':
									type = 'Radio'; break;
								case 'file':
									type = 'FileUpload'; break;
							}
							break;
					}
				}
				
				var srcNode = input.ancestor('div.field') || input;
				
				config[id] = {
					'id': id,
					'label': label,
					'name': name,
					'type': type,
					'srcNode': srcNode,
					'labelNode': labelNode,
					'value': value,
					'disabled': disabled
				};
			}
			
			return config;
		},
		
		/**
		 * Fix input config value references to prevent two inputs with
		 * same type (like Hidden) having same raw value
		 * 
		 * @param {Object} config
		 */
		fixInputConfigValueReferences: function (config) {
			if (config.inputs) {
				var empty = {},
					value = null;
				
				for(var i=0,ii=config.inputs.length; i < ii; i++) {
					value = config.inputs[i].value;
					if (Y.Lang.isObject(value)) {
						empty = (Y.Lang.isArray(value) ? [] : {});
						config.inputs[i].value = Supra.mix(empty, config.inputs[i].value, true);
					}
				}
			}
		},
		
		/**
		 * Normalize input config
		 * 
		 * @private
		 * @param {Object} config
		 * @return Normalized input config
		 * @type {Object}
		 */
		normalizeInputConfig: function () {
			//Convert arguments into
			//[{}, INPUT_DEFINITION, argument1, argument2, ...]
			var args = [].slice.call(arguments,0);
				args = [{'parent': this, 'root': this.get('root') || this}, INPUT_DEFINITION].concat(args);
			
			//Mix them together
			return Supra.mix.apply(Supra, args);
		},
		
		/**
		 * Create Input instance from configuration
		 * 
		 * @param {Object} config
		 * @return Input instance
		 * @type {Object}
		 */
		factoryField: function (config) {
			var type = config.type,
				locator,
				selector,
				input;
				
			type = type.substr(0,1).toUpperCase() + type.substr(1);
			
			if (config.value && !config.defaultValue) {
				config.defaultValue = config.value;
			} else if (!config.value && config.defaultValue) {
				config.value = config.defaultValue;
			}
			
			if (Supra.Input.isInline(config.type)) {
				locator = this.get('propertyElementLocator');
				
				if (locator) {
					if (config.parent && config.parent.isInstanceOf('input')) {
						selector = locator.getDOMNodeId(config.parent) + '_' + config.name;
					} else {
						selector = locator.getDOMNodeId(config.name || config.id);
					}
					
					config.targetNode = locator.one('#' + selector);
					config.doc = locator.getDoc();
					config.win = locator.getWin();
					
					if (!Supra.Input.isContained(config.type)) {
						config.boundingBox = config.srcNode = config.targetNode;
						
						if (!config.srcNode) {
							// Can't find node, don't create
							return null;
						}
					} else if (!config.targetNode) {
						return null;
					}
				} else {
					// Can't find node, don't create
					return null;
				}
			}
			
			if (type in Supra.Input) {
				input = new Supra.Input[type](config);
				
				input.after('render', function (e, config, input) {
					this.fire('input:add', {'config': config, 'input': input});
				}, this, config, input);
				
				return input;
			} else {
				return null;
			}
		},
		
		/**
		 * Test if inline input target node is valid or not
		 */
		testInlineInputValidity: function (input) {
			var type = input.constructor.NAME;
			
			if (Supra.Input.isInline(input)) {
				var locator = this.get('propertyElementLocator'),
					doc = locator.getDoc(),
					node;
				
				node = input.get('srcNode');
				if (node && node.inDoc(doc)) return true;
				
				node = input.get('targetNode');
				if (node && node.inDoc(doc)) return true;
				
			} else {
				// If not an inline, then assume it's ok
				return true;
			}
			
			return false;
		},
		
		/**
		 * Add input
		 * 
		 * config - input configuration object:
		 *     id         - unique field ID
		 *     name       - input name, will be sent as name on save request. Optional
		 *     label      - label text. Optional
		 *     type       - field type. Possible values: "String", "Path", "Template", "Checkbox", "Select", "Text", "Html". Optional, default is "String"
		 *     srcNode    - input node or field (input,label,etc.) node. Optional
		 *     labelNode  - label node, label property will be set to text content of labelNode. Optional
		 *     value      - input value. Optional, default is empty string,
		 *     disabled   - input disabled or not. Optional, if input has disabled attribute, then it will be used
		 * 
		 * @param {Object} config
		 */
		addInput: function (config, definition) {
			var id;
			
			if (config.isInstanceOf && config.isInstanceOf('input') && definition) {
				//Add input to the list of form inputs
				config.set('parent', this);
				
				id = config.get('id');
				this.inputs[id] = config;
				this.inputConfiguration[id] = definition;
			} else if (this.get('rendered')) {
				
				//Create input and append
				var input = null,
					node = null,
					contentBox = this.getContentNode(),
					srcNode = this.get('srcNode');
				
				config = this.normalizeInputConfig(config);
				id = config.id || config.name;
				this.inputConfiguration[id] = config;
				
				input = this.factoryField(config);
				if (input) {
					this.inputs[id] = input;
					
					if (config.srcNode) {
						input.render();
					} else if (config.replaceInput && Supra.Input.isContained(config.type)) {
						node = config.replaceInput.get('boundingBox');
						input.render(node.get('parentNode'));
						input.get('boundingBox').insertBefore(node);
						
						config.replaceInput.destroy(true);
					} else if (config.containerNode) {
						//If input doesn't exist but has container node, then create
						//input inside it
						input.render(config.containerNode);
					} else {
						//If input doesn't exist, then create it
						input.render(contentBox);
					}
				}
				
			} else {
				id = ('id' in config && config.id ? config.id : ('name' in config ? config.name : ''));
				if (!id) {
					Y.log('Input configuration must specify ID or NAME', 'debug');
					return this;
				}
				
				var conf = (id in this.inputConfiguration ? this.inputConfiguration[id] : {});
				this.inputConfiguration[id] = Supra.mix(conf, config);
			}
			
			return this;
		},
		
		/**
		 * Returns form contentBox or slideshow main slide content
		 * 
		 * @returns {Object} Content node
		 */
		getContentNode: function () {
			var slideshow = this.get('slideshow'),
				slide,
				key;
			
			if (slideshow && slideshow.getSlide) {
				slide = slideshow.getSlide('propertySlideMain');
				if (!slide) {
					for (key in slideshow.slides) {
						slide = slideshow.getSlide(key);
					}
				}
				if (slide) {
					return slide.one('.su-slide-content');
				}
			}
			
			return this.get('contentBox');
		},
		
		
		/**
		 * Bind even listeners
		 * @private
		 */
		bindUI: function () {
			Form.superclass.bindUI.apply(this, arguments);
			
			//Find button with 'form' attribute which could be in the footer
			//and add support for IE
			if (Y.UA.ie) {
				var form = this.get('srcNode'),
					form_id = form.get('id'),
					button = Y.one('button[form="' + form_id + '"]');
				
				if (button && !button.closest(form)) {
					//On submit call 'save'
					button.on('click', this.submit, this);
					
					//On input return key call 'save'
					form.all('input[type="text"],input[type="password"]').on('keyup', function (event) {
						if (event.keyCode == 13) { //Return key
							this.submit();
							event.preventDefault();
						}
					}, this);
				}
			}
			
			//On submit call 'save'
			this.get('srcNode').on('submit', function (event) {
				//Use ajax
				event.preventDefault();
				
				//Save form
				this.save();
			}, this);
			
			//Attributes
			this.on('visibleChange', this._onVisibleAttrChange, this);
			this.on('disabledChange', this._onDisabledAttrChange, this);
			this.on('inputsChange', this._onPropertiesChange, this);
		},
		
		/**
		 * Submit form
		 */
		submit: function () {
			this.fire('submit');
			this.save();
		},
		
		/**
		 * Process Input attribute
		 */
		processAttributes: function () {
			var inputs = this.get('inputs');
			
			if (Y.Lang.isArray(inputs)) {
				var obj = {},
					id = null,
					i = 0,
					ii = inputs.length;
				
				for(; i < ii; i++) {
					id = (('id' in inputs[i]) ? inputs[i].id : ('name' in inputs[i] ? inputs[i].name : null));
					if (id) {
						obj[id] = inputs[i];
					}
				}
				inputs = obj;
			}
			
			Supra.mix(this.inputConfiguration, inputs || {});
		},
		
		/**
		 * Render form and inputs
		 * @private
		 */
		renderUI: function () {
			Form.superclass.renderUI.apply(this, arguments);
			
			var srcNode = this.get('srcNode');
			var contentBox = this.get('contentBox');
			
			var inputs = this.inputs || {};
			var definitions = this.inputConfiguration || {};
			
			//Find all inputs
			if (this.get('autoDiscoverInputs')) {
				definitions = Supra.mix(this.discoverInputs(), definitions, true);
			}
			
			//Normalize definitions
			//by adding missing parameters
			var definition = null,
				id = null,
				node = null,
				input = null,
				slide = null,
				slideshow = this.get('slideshow');
			
			//Change content box to slideshow content
			if (slideshow) {
				if (slideshow === true) {
					slideshow = new Supra.Slideshow();
					slideshow.render(contentBox);
					this.set('slideshow', slideshow);
					this.slideshowCreated = true;
					slide = slideshow.addSlide('propertySlideMain');
				}
				contentBox = this.getContentNode();
			}
			
			// Style
			var style = this.get('style') || this.get('srcNode').getAttribute('data-style') || 'default';
			this.setStyle(style);
			
			//Create Inputs
			for(var i in definitions) {
				//If input already exists, then don't create it
				if (definitions[i].id in inputs) continue;
				
				definition = definitions[i] = this.normalizeInputConfig(definitions[i]);
				id = definition.id;
				
				//Try finding input
				if (!definition.srcNode) {
							    node = srcNode.one('#' + id);
					if (!node)  node = srcNode.one('*[name="' + definition.name + '"]');
					if (!node)  node = srcNode.one('*[data-input-id="' + id + '"]');
					
					if (node) {
						definition.srcNode = node;
					}
				}
				
				input = this.factoryField(definition);
				if (input) {
					inputs[id] = input;
					
					if (definition.srcNode) {
						input.render();
					} else if (definition.containerNode) {
						//If input doesn't exist but has container node, then create
						//input inside it
						input.render(definition.containerNode);
					} else {
						//If input doesn't exist, then create it
						input.render(contentBox);
					}
				}
			}
			
			this.inputs = inputs;
			this.inputConfiguration = definitions;
			
			// Trigger change if needed
			if (this.get('disabled')) {
				this._onDisabledAttrChange({'newVal': true, 'prevVal': false});
			}
			if (!this.get('visible')) {
				this._onVisibleAttrChange({'newVal': false, 'prevVal': true});
			}
		},
		
		/**
		 * Change form style
		 * 
		 * @param {String} style Style values, valid values are 'default', 'vertical' and 'default vertical'
		 */
		setStyle: function (style) {
			//Style value can be also 'default vertical'
			style = style.split(' ');
			
			var i = 0,
				ii = style.length;
			
			for(; i < ii; i++) {
				this.get('srcNode').addClass(this.getClassName(style[i]));
			}
		},
		
		/**
		 * Add classname to the form bounding box
		 * 
		 * @param {String} c Classname 
		 */
		addClass: function (c) {
			this.get('boundingBox').addClass(c);
			return this;
		},
		
		/**
		 * Remove classname from the form bounding box
		 * 
		 * @param {String} c Classname 
		 */
		removeClass: function (c) {
			this.get('boundingBox').removeClass(c);
			return this;
		},
		
		/**
		 * Returns true if forms bounding box has this classname, otherwise false
		 * 
		 * @param {String} c Classname 
		 */
		hasClass: function (c) {
			return this.get('boundingBox').hasClass(c);
		},
		
		/**
		 * Add/remove classname from the form bounding box
		 * 
		 * @param {String} c Classname
		 * @param {Boolean} v Add or remove, if true classname will be added, otherwise removed
		 */
		toggleClass: function (c, v) {
			this.get('boundingBox').toggleClass(c, v);
			return this;
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		destructor: function () {
			//Destroy all input widgets
			var inputs = this.inputs,
				config,
				slideshow;
			
			for(var key in inputs) {
				config = this.inputConfiguration[key];
				this.fire('input:remove', {'config': config, 'input': inputs[key]});
				inputs[key].destroy();
			}
			
			this.inputs = null;
			this.inputConfiguration = null;
			
			if (this.slideshowCreated) {
				slideshow = this.get('slideshow');
				if (slideshow) {
					slideshow.destroy(true);
				}
			}
		},
		
		/**
		 * Serialize multi-dimensional object into one dimensional object
		 * 
		 * @param {Object} obj
		 * @param {String} prefix
		 * @return One dimensional object
		 * @type {Object}
		 * @private
		 */
		serializeObject: function (obj, _prefix, skip_encode) {
			var prefix = _prefix || '';
			var out = {};
			
			for(var id in obj) {
				var name = skip_encode ? id : encodeURIComponent(id);
					name = prefix ? prefix + '[' + name + ']' : name;
				
				if (Y.Lang.isObject(obj[id])) {
					out = Y.mix(this.serializeObject(obj[id], name, skip_encode) ,out);
				} else {
					out[name] = skip_encode ? obj[id] : encodeURIComponent(obj[id]);
				}
			}
			
			return out;
		},
		
		/**
		 * Convert one-dimensional value object into multi-dimensional changing
		 * 'key1[key2][key3]' = 3 into {key1: {key2: {key3: 3}}}
		 * 
		 * @param {Object} obj
		 * @return Multi dimensional object
		 * @type {Object}
		 * @private
		 */
		unserializeObject: function (obj, skip_decode) {
			var out = {},
				id,
				m,
				name,
				is_string,
				regex_id = /([^\[]+)\[([^\]]+)\](.*)/;
			
			for(id in obj) {
				//If value is not string, then no need to decode
				is_string = typeof obj[id] == 'string';
				
				if (String(id).indexOf('[') != -1) {
					if ((m = id.match(regex_id))) {
						try {
							name = skip_decode || !is_string ? m[1] : QueryString.unescape(String(m[1]));
						} catch (e) {
							name = m[1];
						}
						if (!(name in out)) out[name] = {};
						this.unserializeItem(m[2] + m[3], obj[id], out[name], skip_decode);
					}
				} else {
					try {
						out[id] = skip_decode || !is_string ? obj[id] : QueryString.unescape(obj[id]);
					} catch (e) {
						out[id] = obj[id];
					}
				}
			}
			
			return out;
		},
		
		/**
		 * Parse ID string and set data on out object
		 * 
		 * @param {String} id
		 * @param {Object} value
		 * @param {Object} out
		 * @private
		 */
		unserializeItem: function (id, value, out, skip_decode) {
			var m, name, is_string,
				regex_id = /([^\[]+)\[([^\]]+)\](.*)/;
			
			if (String(id).indexOf('[') != -1) {
				if ((m = id.match(regex_id))) {
					try {
						name = skip_decode ? m[1] : QueryString.unescape(String(m[1]));
					} catch (e) {
						name = m[1];
					}
					if (!(name in out)) out[name] = {};
					this.unserializeItem(m[2] + m[3], value, out[name], skip_decode);
				}
			} else {
				is_string = typeof value == 'string';
				try {
					out[id] = skip_decode || !is_string ? value : QueryString.unescape(value);
				} catch (e) {
					out[id] = value;
				}
			}
		},
		
		/**
		 * Returns serialize values ready for use in query string
		 * 
		 * @param {String} key Name of the property, which will be used for key, default is 'name'
		 * @return Form input values
		 * @type {Object}
		 */
		getSerializedValues: function (key) {
			var values = this.getValues(key);
				values = this.serializeObject(values);
			
			return values;
		},
		
		/**
		 * Returns values parsing input names and changing into
		 * multi-dimension object
		 * 
		 * @param {String} key Name of the property, which will be used for key, default is 'name'
		 * @return Form input values
		 * @type {Object}
		 */
		getValuesObject: function (key) {
			var values = this.getValues(key);
			return this.unserializeObject(values);
		},
		
		/**
		 * Returns input name => value pairs
		 * Optionally other attribute can be used instead of 'name'
		 * 
		 * @param {String} key
		 * @param {Boolean} save Return save value
		 * @return Form input values
		 * @type {Object}
		 */
		getValues: function (_key, save) {
			var key = _key || 'name';
			var values = Supra.mix({}, this.get('plainValues'));
			var definitions = this.inputConfiguration;
			var prop = save ? 'saveValue' : 'value';
			
			for(var id in this.inputs) {
				var input = this.inputs[id];
				var val = input.get(prop);
				if (val !== undefined) {
					values[key == 'id' || key == 'name' ? (id in definitions ? definitions[id][key] : id) : input.getAttribute(key)] = val;
				}
			}
			
			return values;
		},
		
		/**
		 * Returns save values as input name => value pairs
		 * Optionally other attribute can be used instead of 'name'
		 * 
		 * @param {String} key
		 * @return Form input values
		 * @type {Object}
		 */
		getSaveValues: function (key) {
			return this.getValues(key, true);
		},
		
		/**
		 * Set input values
		 * 
		 * @param {Object} data
		 * @param {Object} key
		 */
		setValues: function (_data, _key, skip_encode) {
			var key = _key || 'name',
				definitions = this.inputConfiguration,
				input = null,
				key_value = null,
				data = skip_encode ? _data : this.serializeObject(_data, null, true),
				plainValues = this.get('plainValues'),
				id;
			
			data = data || {};
			
			for (id in this.inputs) {
				input = this.inputs[id];
				key_value = (key == 'id' || key == 'name' ? (id in definitions ? definitions[id][key] : id) : input.getAttribute(key));
				
				if (key_value in data) {
					input.set('value', data[key_value]);
				}
			}
			
			for (id in data) {
				if (!this.inputs[id]) {
					plainValues[id] = data[id];
				}
			}
			
			return this;
		},
		
		/**
		 * Set input values without converting names {'a': {'b': 'c'}} into {'a[b]': 'c'}
		 * 
		 * @param {Object} data
		 * @param {Object} key
		 */
		setValuesObject: function (data, key) {
			return this.setValues(data, key, true);
		},
		
		/**
		 * Reset input values to defaults
		 * 
		 * @param {Array} inputs Optional. Array of input ids which should be reseted, if not set then all inpts
		 */
		resetValues: function (list) {
			var inputs = this.inputs;
			
			if (Y.Lang.isArray(list)) {
				for(var i=0,ii=list.length; i < ii; i++) {
					if (list[i] in inputs) {
						inputs[list[i]].resetValue();
						inputs[list[i]].set('error', false);
					}
				}
			} else {
				for(var id in inputs) {
					inputs[id].resetValue();
					inputs[id].set('error', false);
				}
			}
			
			this.set('plainValues', {});
			
			return this;
		},
		
		/**
		 * Returns inputs
		 *  
		 * @returns {Object} All inputs
		 */
		getInputs: function (key) {
			var obj,
				inputs = this.inputs,
				i = null;
			
			if (!key || key == 'id') {
				return inputs;
			} else if (key == 'array') {
				obj = [];
				
				for (i in inputs) obj.push(inputs[i]);
				return obj;
			} else {
				obj = {};
				
				for(i in inputs) obj[inputs[i].getAttribute(key)] = inputs[i];
				return obj;
			}
		},
		
		/**
		 * Returns all inputs, including Collection and Set child inputs
		 * If key is 'array', then array is returned, otherwise object by input names
		 * 
		 * @returns {Array|Object} Inputs
		 */
		getAllInputs: function (key) {
			var inputs = this.inputs,
				obj,
				i;
			
			if (key === 'array') {
				obj = [];
				
				for (i in inputs) {
					obj.push(inputs[i]);
					
					if (inputs[i].getAllInputs) {
						obj = obj.concat(inputs[i].getAllInputs(key));
					}
				}
			} else {
				obj = {};
				
				for (i in inputs) {
					obj[inputs[i].getHierarhicalName()] = inputs[i];
					
					if (inputs[i].getAllInputs) {
						Supra.mix(obj, inputs[i].getAllInputs(key));
					}
				}
			}
			
			return obj;
		},
		
		/**
		 * Returns input by ID, name, index or by filter function
		 * 
		 * @param {String|Number} id Input ID, name, index or filter function
		 * @return {Object} Input instance
		 */
		getInput: function (id) {
			var type = typeof id,
				i = 0,
				key,
				inputs = this.inputs || {},
				definitions;
			
			if (type === 'function') {
				for (key in inputs) {
					if (id(inputs[key], key, inputs)) {
						return inputs[key];
					}
				}
			} else if (type === 'number' && !this.inputs[id]) {
				for (key in inputs) {
					if (i == id) return inputs[key];
					i++;
				}
			} else if (id in this.inputs) {
				return this.inputs[id];
			} else {
				//Search by 'name'
				definitions = this.inputConfiguration;
				
				for(i in definitions) {
					if (definitions[i].name == id) return this.inputs[i];
				}
			}
			return null;
		},
		
		/**
		 * Returns input configuration/definition by id or by name if
		 * there were no matches by id or null if name also didn't matches
		 * any input. If id is not passed, then returns all configuration
		 * 
		 * @param {String} [id] Input id
		 * @return Input configuration/definition
		 * @type {Object}
		 */
		getConfig: function (id) {
			var definitions = this.inputConfiguration;
			
			if (id) {
				if (id in definitions) {
					return definitions[id];
				} else {
					for(var i in definitions) {
						if (definitions[i].name == id) return definitions[i];
					}
				}
				
				return null;
			} else {
				return definitions;
			}
		},
		
		/**
		 * Set if form should search for inputs
		 * 
		 * @param {Boolean} value
		 */
		setAutoDiscoverInputs: function (value) {
			this.set('autoDiscoverInputs', !!value);
			return this;
		},
		
		/**
		 * Set load request url
		 * 
		 * @param {String} url
		 */
		setURLLoad: function (url) {
			this.set('urlLoad', url);
			return this;
		},
		
		/**
		 * Set delete request url
		 * 
		 * @param {String} url
		 */
		setURLDelete: function (url) {
			this.set('urlDelete', url);
			return this;
		},
		
		/**
		 * Set save request url
		 * 
		 * @param {String} url
		 */
		setURLSave: function (url) {
			this.set('urlSave', url);
			return this;
		},
		
		/**
		 * Validate form values
		 * 
		 * @return True on success, false on failure
		 * @type {Boolean}
		 */
		validate: function () {
			//@TODO
			return true;
		},
		
		/**
		 * Validate and execute save request if url is set and user is authorized to save data
		 */
		save: function (callback, context) {
			if (!this.get('disabled') && this.validate()) {
				var uri = this.get('urlSave'),
					values = null;
				
				if (uri) {
					values = this.getSaveValues(this.get('inputs') ? 'id' : 'name');
					
					Supra.io(uri, {
						'method': 'post',
						'data': values,
						'context': context || this,
						'on': {
							'success': callback,
							'failure': callback
						}
					});
					
				} else {
					if (callback) {
						callback.call(context = context || this, null, 1);
					}
				}
				
				this.fire('save');
			} else {
				if (callback) {
					callback.call(context = context || this, null, 0);
				}
			}
		},
		
		/**
		 * Load data and populate form if url is set and user is authorized to load data
		 */
		load: function () {
			//@TODO
			
			this.fire('load');
		},
		
		/**
		 * Execute delete request if url is set, form has ID field and user is authorized to delete record
		 */
		'delete': function () {
			//@TODO
			
			this.fire('delete');
		},
		
		
		/* ------------------------------ ATTRIBUTE CHANGE HANDLERS -------------------------------- */
		
		
		/**
		 * When input list changes check diff and recreate inputs
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		_onPropertiesChange: function (e) {
			var properties = e.newVal,
				prevProperties = e.prevVal,
				locator = this.get('propertyElementLocator'),
				element,
				i = 0,
				ii = properties.length,
				newInputIds = {},
				add = [], remove = [], replace = [],
				
				inputConfiguration = this.inputConfiguration,
				inputs = this.inputs,
				input,
				config;
			
			for (; i < ii; i++) {
				if (Supra.Input.isInline(properties[i].type)) {
					element = locator.getInputElement(properties[i].id);
					if (element) {
						newInputIds[properties[i].id] = 1;
						input = this.getInput(properties[i].id);
						
						// Iframe DOM element exists
						if (input) {
							if (!element.compareTo(input.get('targetNode')) && !element.compareTo(input.get('srcNode'))) {
								// Replace existing inline input
								replace.push(properties[i]);
							}
						} else {
							// Add inline input
							add.push(properties[i]);
						}
					} else {
						// Iframe DOM element missing
						if (this.getInput(properties[i].id)) {
							// Remove existing inline input
							remove.push(properties[i]);
						}
					}
				} else {
					newInputIds[properties[i].id] = 1;
					
					if (!this.getInput(properties[i].id)) {
						// Add contained input
						add.push(properties[i]);
					}
				}
			}
			
			for (i=0, ii=prevProperties.length; i < ii; i++) {
				if (!newInputIds[prevProperties[i].id]) {
					if (this.getInput(prevProperties[i].id)) {
						// Remove input
						remove.push(prevProperties[i]);
					}
				}
			}
			
			// Remove inputs
			for (i=0, ii=remove.length; i < ii; i++) {
				inputs[remove[i].id].destroy(true);
				delete(inputs[remove[i].id]);
				delete(inputConfiguration[remove[i].id]);
			}
			
			// Add inputs
			for (i=0, ii=add.length; i < ii; i++) {
				config = Supra.mix({}, add[i], {
					'containerNode': this.getContentNode()
				}, true);
				
				this.addInput(config);
			}
			
			// Replace inputs
			for (i=0, ii=replace.length; i < ii; i++) {
				input = this.getInput(replace[i].id);
				config = Supra.mix({}, replace[i], true);
				
				if (Supra.Input.isContained(config.type)) {
					config.containerNode = this.getContentNode();
					this.addInput(config);
					
					// Insert into correct place
					input.get('boundingBox').insert(this.inputs[replace[i].id].get('boundingBox'), 'before');
				} else {
					// Inline
					this.addInput(config);
				}
				
				// Remove old input
				input.destroy(true);
			}
		},
		
		/**
		 * Disabled attribute change event listener
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onDisabledAttrChange: function (e) {
			if (e.newVal !== e.prevVal) {
				var inputs = this.getInputs();
				for(var id in inputs) inputs[id].set('disabled', e.newVal);
			}
		},
		
		/**
		 * Visible attribute change event listener
		 * Show/hide form when widget is shown or hidden
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onVisibleAttrChange: function (e) {
			if (e.newVal !== e.prevVal) {
				this.get('boundingBox').toggleClass('hidden', !e.newVal);
			}
		}
	});
	
	/**
	 * Validation functions 
	 */
	Form.validate = {
		/**
		 * Regular expression to test email validity
		 * @type {Object}
		 */
		REGEX_EMAIL: /^\s*([a-z0-9]([a-z0-9\+\.\-\_]*[a-z0-9])?@[a-z0-9]+([\-\_][a-z0-9]+)*(\.[a-z0-9]+([\-\_][a-z0-9]+)*)+)\s*$/i,
		
		/**
		 * Returns true if str parammeter is not empty
		 * 
		 * @param {String} str String to validate
		 * @returns {Boolean} True if string is not empty, otherwise false
		 */
		required: function (str) {
			var type = typeof str,
				typeu;
			
			if (type === 'number') {
				return str !== 0;
			} else if (type === 'boolean') {
				return str;
			} else if (str === null || str === typeu) {
				return false;
			} else if (type === 'string') {
				return !!Y.Lang.trim(str).length;
			} else {
				return !!str;
			}
		},
		
		/**
		 * Validate email address validity
		 * 
		 * @param {String} str String to validate
		 * @returns {Boolean} True if string is valid email address, otherwise false
		 */
		email: function (str) {
			if (Form.validate.REGEX_EMAIL.test(String(str)) === false) {
				return false;
			} else {
				return true;
			}
		}
	};
	
	/**
	 * Returns lipsum data for inputs
	 * 
	 * @param {Object} inputs List of input definitions
	 * @returns {Object} Lipsum data for inputs
	 */
	Form.lipsum = function (inputs, overwrite_defaults) {
		var properties = inputs,
			property = null,
			i = 0,
			ii = properties.length,
			lipsum = null,
			input = null,
			generated = {};
		
		for (; i < ii; i++) {
			property = properties[i];
			lipsum = Form.lipsumProperty(property, overwrite_defaults);
			if (lipsum) {
				generated[property.id] = lipsum;
			}
		}
		
		return generated;
	};
	
	Form.lipsumProperty = function (property, overwrite_defaults) {
		var input = Supra.Input[property.type],
			i = 0,
			ii = 0,
			k = 0,
			kk = 0,
			lipsum = '',
			items = null,
			item = null;
		
		if (input && input.lipsum) {
			// Input
			if (overwrite_defaults) {
				lipsum = input.lipsum();
			}
		} else if (property.type == 'Gallery') {
			// Gallery block
			items = [];
			
			// Create 4 items
			if (property.properties) {
				for (k=0, kk=4; k < kk; k++) {
					item = {'id': Y.guid(), 'image': null, 'properties': {}};
					
					for (i=0, ii=property.properties.length; i < ii; i++) {
						lipsum = Form.lipsumProperty(property.properties[i], overwrite_defaults);
						item.properties[property.properties[i].id] = lipsum || property.properties[i].value || '';
					}
					items.push(item);
				}
			}
			
			lipsum = items.length ? items : null;
		} else if (property.type == 'Slideshow') {
			// Slideshow block
		}
		
		return lipsum;
	};
	
	
	Supra.Form = Form;
	Supra.Form.normalizeInputConfig = Form.prototype.normalizeInputConfig;
	Supra.Form.factoryField = Form.prototype.factoryField;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
