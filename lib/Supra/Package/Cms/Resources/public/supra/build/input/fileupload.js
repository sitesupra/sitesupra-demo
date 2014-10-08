YUI.add("supra.input-file-upload", function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Fileupload (config) {
		Fileupload.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Fileupload.IS_INLINE = false;
	
	// Input is inside form
	Fileupload.IS_CONTAINED = true;
	
	Fileupload.NAME = "input-file-upload";
	Fileupload.CLASS_NAME = Y.ClassNameManager.getClassName(Fileupload.NAME);
	Fileupload.ATTRS = {
		
		/**
		 * File upload request URI
		 * @type {String}
		 */
		'requestUri': {
			value: null
		},
		
		/**
		 * Allow selecting multiple files
		 * @type {Boolean}
		 */
		'multiple': {
			value: false
		},
		
		/**
		 * Comma separated mime types which are allowed, eq. "image/*"
		 * @type {String}
		 */
		'accept': {
			value: false
		},
		
		/**
		 * File validation function
		 * @type {Function}
		 */
		'validateFile': {
			value: null
		},
		
		/**
		 * Additional data which will be added to the POST body
		 * @type {Object}
		 */
		'data': {
			value: null
		},
		
		/**
		 * Button node
		 * @type {Object}
		 */
		'buttonNode': {
			value: null
		},
		
		/**
		 * Button label
		 * @type {String}
		 */
		'buttonLabel': {
			value: null
		},
		
		/**
		 * Text node
		 */
		'textNode': {
			value: null
		},
		
		/**
		 * Text for drag and drop
		 */
		'textDragDrop': {
			value: 'or drag and drop file here'
		},
		
		/**
		 * Text for file count
		 */
		'textUploaded': {
			value: '<a>{count} file(s)</a> uploaded'
		}
	};
	
	Fileupload.HTML_PARSER = {
		'multiple': function (srcNode) {
			var input = this.get('inputNode'),
				multiple = input.getAttribute('multiple');
				
			if (multiple == 'false' || multiple == '0') {
				multiple = false;
			} else {
				multiple = true;
			}
			
			return multiple;
		},
		
		/**
		 * Check for accept attribute
		 * 
		 * @param {Object} srcNode Source node
		 * @return New value
		 * @private
		 */
		'accept': function (srcNode) {
			var input = this.get('inputNode'),
				accept = input.getAttribute('accept');
			
			return accept;
		},
		
		/**
		 * Upload request parameters
		 * 
		 * @param {Object} srcNode Source node
		 * @return New data
		 * @private
		 */
		'data': function (srcNode) {
			var input = this.get('inputNode'),
				data = input.getAttribute('data-request-parameters');
				
			if (data) {
				return Y.QueryString.parse(data);
			} else {
				return data;
			}
		},
		
		/**
		 * File upload request URI
		 * 
		 * @param {Object} srcNode Source node
		 * @return New request uri
		 * @private
		 */
		'requestUri': function (srcNode) {
			var input = this.get('inputNode'),
				uri = this.get('requestUri'),
				attr = null;
			
			if (input && (attr = input.getAttribute('data-request-uri'))) {
				return attr;
			} else {
				return uri;
			}
		},
		
		/**
		 * Button label
		 * 
		 * @param {Object} srcNode Source node
		 * @return New button label
		 * @private
		 */
		'buttonLabel': function (srcNode) {
			var input = this.get('inputNode'),
				label = this.get('buttonLabel');
			
			if (!label && input) {
				label = input.getAttribute('value');
			}
			
			return label;
		},
		
		/**
		 * Get or create button node
		 * 
		 * @param {Object} srcNode Source node
		 * @return New button node
		 * @private
		 */
		'buttonNode': function (srcNode) {
			var input = this.get('inputNode'),
				button = input.next(),
				selector = 'button,input[type="button"],input[type="submit"]',
				label = null;
			
			if (button && button.test(selector)) {
				//Set label
				if (label = this.get('buttonLabel')) {
					button.set(button.test('button') ? 'text' : 'value', label);
				}
				
				return button;	
			} else {
				//Create button
				label = this.get('buttonLabel') || this.get('inputNode').getAttribute('value');
				button = Y.Node.create('<button type="button"></button>');
				button.set('text', label);
				input.insert(button, 'after');
				
				return button;
			}
			
			return null;
		},
		
		/**
		 * Get or create text node
		 * 
		 * @param {Object} srcNode Source node
		 * @return New text node
		 * @private
		 */
		'textNode': function (srcNode) {
			var input = this.get('inputNode'),
				node = input.next(),
				selector = 'em,b,i,strong,p,div',
				label = null;
			
			if (!node.test(selector)) {
				node = node.next();
				
				if (!node.test(selector)) {
					//Create node
					label = this.get('textDragDrop') || '';
					node = Y.Node.create('<em class="yui3-input-file-upload-text"></em>');
					node.insert(input.next(), 'after');
				}
			}
			
			if (label = this.get('textDragDrop')) {
				node.set('text', label);
			}
			
			if (node) {
				node.addClass(Y.ClassNameManager.getClassName(Fileupload.NAME, 'text'));
			}
			
			return node;
		}
	};
	
	Y.extend(Fileupload, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="file" value="' + Supra.Intl.get(['buttons', 'browse']) + '" />',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Browse button
		 * @type {Object}
		 * @private
		 */
		button: null,
		
		/**
		 * Text node
		 * @type {Object}
		 * @private
		 */
		text: null,
		
		/**
		 * Uploader widget
		 * @type {Object}
		 * @private
		 */
		uploader: null,
		
		/**
		 * File count
		 * @type {Number}
		 * @private
		 */
		uploading_count: 0,
		
		/**
		 * File titles
		 * @type {Array}
		 * @private
		 */
		titles: [],
		
		/**
		 * Tooltip
		 * @see Supra.Tooltip
		 * @type {Object}
		 * @private
		 */
		tooltip: null,
		
		/**
		 * Attach even listeners
		 */
		bindUI: function () {
			Fileupload.superclass.bindUI.apply(this, arguments);
			
			this.uploader.on('file:upload', this.onFileUpload, this);
			this.uploader.on('file:abort', this.onFileAbort, this);
			this.uploader.on('file:complete', this.onFileUploadComplete, this);
			
			var text = this.get('textNode');
			if (text) {
				text.delegate('mouseenter', this.showTooltip, 'a', this);
				text.delegate('mouseleave', this.hideTooltip, 'a', this);
			}
		},
		
		/**
		 * Create required nodes
		 */
		renderUI: function () {
			Fileupload.superclass.renderUI.apply(this, arguments);
			
			var button = this.get('buttonNode'),
				input = this.get('inputNode');
			
			//Move button node
			if (button) {
				input.insert(button, 'after');
			}
			
			//Move text node
			var text = this.get('textNode');
			if (text) {
				this.text = text;
				(button || input).insert(text, 'after');
			}
			
			//Create Browse button
			if (button) {
				this.button = new Supra.Button({
					'srcNode': button
				});
				this.button.render();
			}
			
			//Hide input, because button and text node is used instead
			var input = this.get('inputNode');
			input.hide();
			
			//Uploader
			this.uploader = new Supra.Uploader({
				'requestUri': this.get('requestUri'),
				'allowMultiple': this.get('multiple'),
				'data': this.get('data'),
				'validateFile': this.get('validateFile'),
				'clickTarget': this.get('buttonNode'),
				'dropTarget': this.get('boundingBox')
			});
		},
		
		/**
		 * Value setter
		 * Takes value and tries to convert it into array
		 * 
		 * @param {Array} value
		 * @return Value as array
		 * @type {Array}
		 */
		_setValue: function (value) {
			//Convert value into array
			if (typeof value == 'number') {
				value = [value];
			} else if (typeof value == 'string') {
				if (value) {
					//If value is a string "12,57" split into ["12","57"]
					value = value.split(',');
				} else {
					value = [];
				}
			} else if (!Supra.Y.Lang.isArray(value)) {
				value = [];
			}
			
			if (!value.length) {
				var text = this.get('textNode');
				if (text) {
					text.set('text', this.get('textDragDrop') || '');
				}
			}
			
			this._original_value = value;
			return value;
		},
		
		/**
		 * Value getter
		 * 
		 * @param {Array} value
		 * @return Value
		 * @type {Array}
		 */
		_getValue: function (value) {
			if (Supra.Y.Lang.isArray(value)) {
				return value;
			} else {
				return [];
			}
		},
		
		/**
		 * On file upload start
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onFileUpload: function (event) {
			var multiple = this.get('multiple');
			if (!multiple) {
				if (this.button) this.button.set('disabled', true);
				if (this.text) this.text.addClass('hidden');
			}
			
			this.uploading_count++;
			this.get('boundingBox').addClass(Y.ClassNameManager.getClassName(Fileupload.NAME, 'uploading'));
		},
		
		/**
		 * On file abort
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onFileAbort: function (event) {
			//Updates UI
			this.uploading_count--;
			if (!this.uploading_count) {
				if (this.button) this.button.set('disabled', false);
				if (this.text) this.text.removeClass('hidden');
				this.get('boundingBox').removeClass(Y.ClassNameManager.getClassName(Fileupload.NAME, 'uploading'));
			}
		},
		
		/**
		 * On file upload complete update value
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onFileUploadComplete: function (event) {
			var data = event.details[0];
			if (data) {
				//Update value
				var value = this.get('value'),
					multiple = this.get('multiple');
				
				if (multiple) {
					value.push(data.id);
					this.titles.push(data.title);
				} else {
					value = [data.id];
					this.titles = [data.title];
				}
				
				this.set('value', value);
				
				var text = this.get('textNode');
				if (text) {
					var lbl = Y.substitute(this.get('textUploaded') || '', {
						'count': this.titles.length
					});
					text.set('innerHTML', lbl);
				}
			}
			
			//Updates UI
			this.uploading_count--;
			if (!this.uploading_count) {
				if (this.button) this.button.set('disabled', false);
				if (this.text) this.text.removeClass('hidden');
				this.get('boundingBox').removeClass(Y.ClassNameManager.getClassName(Fileupload.NAME, 'uploading'));
			}
		},
		
		showTooltip: function () {
			var text = this.get('textNode');
			
			if (!this.tooltip && text) {
				this.tooltip = new Supra.Tooltip({
					'alignTarget': text.one('a'),
					'alignPosition': 'T',
					'zIndex': 10
				});
				this.tooltip.render();
				this.tooltip.get('contentBox').append('<p></p>');
			}
			
			if (this.tooltip) {
				var p = this.tooltip.get('contentBox').one('p'),
					titles = this.titles,
					titles_escaped = [];
				
				for(var i=0,ii=titles.length; i<ii; i++) titles_escaped.push(Y.Escape.html(titles[i]));
				p.set('innerHTML', titles_escaped.join('<br />'));
				
				this.tooltip.set('alignTarget', text.one('a'));
				this.tooltip.set('alignPosition', 'T');
				this.tooltip.show();
				this.tooltip.syncUI();
			}
		},
		
		hideTooltip: function () {
			if (this.tooltip) {
				this.tooltip.hide();
			}
		},
		
		/**
		 * Returns value as a string
		 * 
		 * @return Value
		 * @type {String}
		 */
		toString: function () {
			return this.value.join(',');
		}
	});
	
	Supra.Input.FileUpload = Fileupload;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.uploader", "supra.tooltip"]});