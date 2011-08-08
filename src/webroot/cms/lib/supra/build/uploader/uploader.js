//Invoke strict mode
"use strict";

/**
 * Plugin to add file upload functionality for MediaList
 */
YUI.add('supra.uploader', function (Y) {
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 * 
	 * Events:
	 * 		file:add
	 * 		file:remove
	 * 		file:upload
	 * 		file:progress
	 * 		file:abort
	 * 		file:complete
	 */
	function Uploader (config) {
		var attrs = {
			//Upload request URI
			'requestUri': {value: ''},
			
			// Additional data which will be added to the POST body
			'data': {
				value: null
			},
			
			// Additional data which will be added to event
			'eventData': {
				value: null
			},
			
			// HTML5 file drop target
			'dropTarget': {
				value: null,
				setter: '_addDropTarget'
			},
			
			// Click target 'Browse' window
			'clickTarget': {
				value: null,
				setter: '_setClickTarget'
			},
			
			// Validate file before uploading
			'validateFile': {
				value: null
			},
			
			// Allow selecting multiple files
			'multiple': {
				value: false
			},
			
			// Start uploading when file is selected
			'autoStart': {
				value: true
			}
		};
		
		this.files = {};
		this.io = {};
		this.listeners = [];
		
		this.addAttrs(attrs, config || {});
		this.renderUI();
		this.bindUI();
	}
	
	Uploader.prototype = {
		
		/**
		 * File list
		 * @type {Object}
		 * @private
		 */
		files: {},
		
		/**
		 * Attached listeners
		 * @type {Array}
		 * @private
		 */
		listeners: [],
		
		/**
		 * File input node
		 * @type {Object}
		 * @private
		 */
		input: null,
		
		/**
		 * Upload IO objects
		 * @type {Array}
		 * @private
		 */
		io: {},
		
		/**
		 * Add needed nodes
		 * 
		 * @private
		 */
		renderUI: function () {
			
			//Create upload node
			var multiple = this.get('multiple'),
				input = Y.Node.create('<input class="offscreen" type="file" />');
			
			Y.one('body').append(input);
			input.on('change', this.onFileBrowse, this);
			this.input = input;
			
			//Enable
			if (!this.get('disabled')) {
				this.set('disabled', false);
			}
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var click_target = this.get('clickTarget'),
				drop_target = this.get('dropTarget');
			
			if (click_target) {
				this.listeners.push(click_target.on('click', this.openFileBrowser, this));
			}
			
			if (drop_target) {
				
			}
		},
		
		/**
		 * Open file browsing window
		 */
		openFileBrowser: function () {
			//Open file browse window
			var input = this.input;
			var node = Y.Node.getDOMNode(input);
			
			if (this.get('multiple')) {
				input.setAttribute('multiple', 'multiple');
			} else {
				input.removeAttribute('multiple');
			}
			
			node.click();
		},
		
		/**
		 * When file is selected start uploading
		 */
		onFileBrowse: function () {
			//Get files
			var files = Y.Node.getDOMNode(this.input).files;
			if (!files.length) return;
			
			if (this.get('multiple')) {
				for(var i=0,ii=files.length; i<ii; i++) this.addFile(files[i]);
			} else {
				this.addFile(files[0]);
			}
		},
		
		/**
		 * Returns all files
		 * 
		 * @return File list
		 * @type {Array}
		 */
		getFiles: function () {
			return this.files;
		},
		
		/**
		 * Returns file by ID or null if file not found
		 * 
		 * @param {String} id File ID
		 * @return File
		 * @type {File}
		 */
		getFile: function (id /* File ID */) {
			return id in this.files ? this.files[id] : null;
		},
		
		/**
		 * Returns file IOUpload object
		 * 
		 * @param {String} id File ID
		 * @return File ID
		 * @type {String} 
		 */
		getIO: function (id /* File ID */) {
			return id in this.io ? this.io[id] : null;
		},
		
		/**
		 * Remove file
		 * 
		 * @param {String} id File ID
		 */
		removeFile: function (id /* File ID */) {
			if (id in this.files) {
				//File remove event
				this.fireEvent('file:remove', id);
				
				delete(this.files[id]);
			}
			if (id in this.io) {
				this.io[id].abort();
				delete(this.io[id]);
			}
		},
		
		/**
		 * Add file to the file list
		 * 
		 * @param {File} file File
		 * @return File ID
		 * @type {String}
		 */
		addFile: function (file /* File */) {
			var id = this.getFileId(file);
			
			if (!(id in this.files)) {
				var validate = this.get('validateFile');
				if (validate && !validate(file, this)) return;
				
				this.files[id] = file;
				
				//File add event
				this.fireEvent('file:add', id);
				
				//Start file upload
				if (this.get('autoStart')) {
					this.uploadFile(id);
				}
			}
			
			return id;
		},
		
		/**
		 * Start uploading file
		 * 
		 * @param {String} id File ID
		 */
		uploadFile: function (id /* File ID */) {
			if (!(id in this.io) && id in this.files) {
				
				var io = this.io[id] = new Supra.IOUpload({
					'file': this.files[id],
					'requestUri': this.get('requestUri'),
					'data': this.get('data'),
					'eventData': {
						'fileId': id
					}
				});
				
				//Upload start event
				this.fireEvent('file:upload', id);
				
				io.on('abort', function (event) {
					//Upload abort event
					this.fireEvent('file:abort', id, event);
				}, this);
				
				io.on('progress', function (event) {
					//Upload abort event
					this.fireEvent('file:progress', id, event);
				}, this);
				
				io.on('complete', function (event) {
					//Upload abort event
					this.fireEvent('file:complete', id, event);
				}, this);
				
				io.start();
				
			}
		},
		
		/**
		 * Returns file ID
		 * 
		 * @param {File} file File
		 * @return File ID
		 * @type {String}
		 */
		getFileId: function (file /* File */) {
			if (file._uploader_file_id) {
				return file._uploader_file_id;
			} else {
				return file._uploader_file_id = Y.guid();
			}
		},
		
		/**
		 * Fire event
		 * 
		 * @param {String} event_name Event name
		 * @param {String} id File ID
		 * @param {Object} data Optional. Additional data
		 * @private
		 */
		fireEvent: function (event_name /* Event name */, id /* File ID */, data /* Additional data */) {
			var event_data = this.get('eventData') || {};
			this.fire(event_name, Supra.mix({'fileId': id}, event_data, data || {}, {'target': this}));
		},
		
		/**
		 * Destroy data object
		 * 
		 * @private
		 */
		destroy: function () {
			//Remove nodes
			this.input.remove();
			
			//Remove event listeners
			for(var i=0,ii=this.listeners.length; i<ii; i++) {
				this.listeners[i].detach();
			}
			
			this.detachAll();
			
			//Remove references
			delete(this.input);
			delete(this.listeners);
			delete(this.files);
		}
		
	};
	
	Y.augment(Uploader, Y.Attribute);
	
	Supra.Uploader = Uploader;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io-upload']});