//Invoke strict mode
"use strict";

YUI.add("supra.input-file-upload", function (Y) {
	
	function Fileupload (config) {
		Fileupload.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Fileupload.NAME = "input-file-upload";
	Fileupload.CLASS_NAME = Y.ClassNameManager.getClassName(Fileupload.NAME);
	Fileupload.ATTRS = {
		"multipleFiles": {
			value: true
		},
		"buttonNode": {
			value: null
		},
		"progressNode": {
			value: null
		},
		"fileFilters": {
			value: [
				{'description': 'All files', 'extensions': '*.png;*.jpg;*.jpeg;*.gif;*.pdf;*.doc;*.docx;*.xls;*.xlsx;*.swf'},
				{'description': 'Images', 'extensions': '*.png;*.jpg;*.jpeg;*.gif'},
				{'description': 'Documents', 'extensions': '*.pdf;*.doc;*.docx;*.xls;*.xlsx;*.swf'}
			]
		},
		"uploadUrl": {
			value: "/cms/sample-manager/popup/upload.json"
		},
		"uploader": {
			value: null
		},
		"value": {
			value: [],
			setter: "_setValue",
			getter: "_getValue"
		}
	};
	
	Fileupload.HTML_PARSER = {
		"multipleFiles": function (srcNode) {
			var input = this.get("inputNode"),
				multiple = input.getAttribute('multiple');
				
			if (multiple == "false" || multiple == "1") {
				multiple = false;
			} else {
				multiple = true;
			}
			
			this.set('multipleFiles', multiple);
			return multiple;
		},
		"buttonNode": function (srcNode) {
			var input = this.get("inputNode"),
				button = input.next();
			if (!button || !button.test("button,input")) {
				button = null;
			}
			this.set("nodeButton", button);
			return button;
		}
	};
	
	Y.extend(Fileupload, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" readonly="readonly" value="" />',
		LABEL_TEMPLATE: '<button type="button"></button>',
		
		files: {},
		files_total: 0,
		files_success: [],
		files_error: [],
		
		bindUI: function () {
			var input = this.get('inputNode');
			var label = this.get('labelNode');
			
			input.on("focus", this._onFocus, this);
			input.on("blur", this._onBlur, this);
			label.on("focus", this._onFocus, this);
			label.on("blur", this._onBlur, this);
		},
		
		renderUI: function () {
			Fileupload.superclass.renderUI.apply(this, arguments);
			
			//Input is replaced with status message
				var input = this.get('inputNode');
				input.addClass('hidden')
				
				/* @TODO Replace progress node with Y.ProgressBar */
				var node = this.get('progressNode');
				if (!node) {
					node = Y.Node.create('<input type="text" readonly="readonly" class="' + Y.ClassNameManager.getClassName(Fileupload.NAME, 'progress') + '" />')
					input.insert(node, 'after');
					this.set('progressNode', node);
				}
				
			//Create button
				var button = this.get('buttonNode');
				if (!button) {
					var button = Y.Node.create('<button type="button">Browse</button>');
					this.set('buttonNode', button);
				}
				
				node.insert(button, 'after');
				
				button = new Supra.Button({'srcNode': button});
				button.render();
			
			//Create button overlay, which will be used for flash
				var overlay = Y.Node.create('<span class="' + Y.ClassNameManager.getClassName(Fileupload.NAME, 'overlay') + '"><span></span></span>');
				button.get('boundingBox').insert(overlay, 'before');
			
			//Set up uploader
				var uploader = new Y.Uploader({
					'boundingBox': overlay.one('span'),
					'swfURL': Y.config.base + 'uploader/assets/uploader.swf'
				});
				
				//When "browse" is clicked remove previous files, otherwise they will
				//be uploaded once more
				uploader.on("click", this.clearUploaderFileList, this);
				uploader.on("uploaderReady", this.setupUploader, this);
				uploader.on("fileselect", this.onFileSelect, this);
				uploader.on("uploadprogress", this.onProgress, this);
				uploader.on("uploadcompletedata", this.onComplete, this);
				uploader.on("uploaderror", this.onError, this);
			
				this.set('uploader', uploader);
		},
		
		/**
		 * Set uploader configuration
		 * @private
		 */
		setupUploader: function () {
			var uploader = this.get('uploader');
			
			//Allow uploading multiple files
			uploader.set("multiFiles", this.get('multipleFiles'));
			
			var file_filters = this.get('fileFilters');
			if (file_filters) {
				uploader.set("fileFilters", file_filters);
			}
		},
		
		/**
		 * Handle file selection
		 * 
		 * @param {Event} event
		 * @private
		 */
		onFileSelect: function (event) {
			var file_data = event.fileList,
				count = 0,
				id;	
			
			this.files = {};
			this.files_success = [];
			this.files_error = [];
			
			for (var key in file_data) {
				id = file_data[key].id;
				this.files[id] = {
					'id': id,
					'size': file_data[key].size,
					'name': file_data[key].name,
					'loaded': 0
				};
				count++;
			}
			
			this.files_total = count;
			
			if (count > 0) {
				this.get('progressNode').set('value', '0%');
				this.startUpload();
			}
		},
		
		/**
		 * Handle upload progress change
		 * 
		 * @param {Event} event
		 * @private
		 */
		onProgress: function (event) {
			var total = 0, loaded = 0;
			this.files[event.id].loaded = event.bytesLoaded;
			
			for(var i in this.files) {
				total += this.files[i].size;
				loaded += this.files[i].loaded;
			}
			
			var progress = Math.round(100 * loaded / total);
			this.get('progressNode').set('value', progress + '%');
		},
		
		/**
		 * Handle file upload error event
		 * 
		 * @param {Object} event
		 */
		onError: function (event) {
			this.files_error.push(this.files[event.id].name);
			this.files_total--;
			this.setCompleteMessage();
		},
		
		/**
		 * Handle file upload complete event
		 * This is called for each file
		 * 
		 * @param {Event} event
		 * @private
		 */
		onComplete: function (event) {
			var data = event.data;
			try {
				//Data is in JSON format
				data = Y.JSON.parse(data);
				
				//Add to file list
				var value = this.get('value');
				value.push(data.id);
				this.set('value', value);
			} catch (e) {}
			
			this.files_success.push(this.files[event.id].name);
			this.files_total--;
			this.setCompleteMessage();
		},
		
		/**
		 * Set message to complete
		 */
		setCompleteMessage: function () {
			//If no more files left
			if (!this.files_total) {
				var msg = 'All files were sucessfully uploaded';
				
				if (this.files_error.length) {
					msg = 'Couldn\'t upload "' + this.files_error.join('", "') + '"';
				}
				
				this.get('progressNode').set('value', msg);
			}
		},
		
		/**
		 * Remove all files from uploader
		 * @private
		 */
		clearUploaderFileList: function () {
			var uploader = this.get('uploader');
			uploader.clearFileList();
		},
		
		/**
		 * Start selected file upload
		 * @private
		 */
		startUpload: function () {
			var params = {};
			
			//Add session ID to request parameters
			var sid_name = Supra.data.get('session_name', null),
				sid_id = Supra.data.get('session_id', null);
			
			if (sid_name && sid_id) {
				params[sid_name] = sid_id;
			}
			
			this.get('uploader').uploadAll(this.get('uploadUrl'), "GET", params);
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass("yui3-input-focused")) return;
			this.get('boundingBox').addClass("yui3-input-focused");
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass("yui3-input-focused");
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
			} else if (!SU.Y.Lang.isArray(value)) {
				value = [];
			}
			
			//Input "value" is a string of ids separated by comma
			this.get("inputNode").set("value", value.join(','));
			
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
			if (SU.Y.Lang.isArray(value)) {
				return value;
			} else {
				return [];
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
	
	Supra.Input.Fileupload = Fileupload;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "uploader"]});