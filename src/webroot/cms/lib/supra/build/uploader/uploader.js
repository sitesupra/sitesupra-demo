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
			
			// Validate file before uploading
			'validateFile': {
				value: null
			},
			
			// Start uploading when file is selected
			'autoStart': {
				value: true
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
			
			// Allow selecting multiple files
			'multiple': {
				value: false
			},
			
			// File types which are accepted
			'accept': {
				value: null
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
				accept = this.get('accept'),
				input = Y.Node.create('<input class="offscreen" type="file" ' + (accept ? 'accept="' + accept + '"' : '') + ' />');
			
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
			
			//On input click prevent propagation, because click opens file window
			//which is not part of this document
			this.input.on('click', function (event) { event.stopPropagation(); });
			
			if (click_target) {
				this.listeners.push(click_target.on('click', this.openFileBrowser, this));
			}
			
			if (drop_target) {
				//Add all subscribers to array for easy removal on destroy 
				this.listeners.push(drop_target.on('dragenter', this.dragEnter, this));
				this.listeners.push(drop_target.on('dragexit', this.dragExit, this));
				this.listeners.push(drop_target.on('dragover', this.dragOver, this));
				this.listeners.push(drop_target.ancestor().on('dragover', this.dragOver, this));
				this.listeners.push(drop_target.on('drop', this.dragDrop, this));
			}
		},
		
		/**
		 * Open file browsing window
		 */
		openFileBrowser: function (event) {
			//Open file browse window
			var input = this.input;
			var node = Y.Node.getDOMNode(input);
			
			if (this.get('multiple')) {
				input.setAttribute('multiple', 'multiple');
			} else {
				input.removeAttribute('multiple');
			}
			
			node.click();
			
			if (event) {
				//Prevent default because file window is not part of this document
				event.stopPropagation();
			}
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
			var id = this.getFileId(file),
				accept = this.get('accept'),
				reg = null;
			
			if (!(id in this.files)) {
				
				//Match file type against accept attribute
				if (accept) {
					accept = accept.split(',');
					for(var i=0,ii=accept.length; i<ii; i++) {
						 reg = accept[i].replace(/\*/g, '.*');
						 reg = new RegExp('^' + Y.Lang.trim(reg) + '$', 'i');
						 
						 if (!file.type.match(reg)) {
						 	this.fireEvent('file:validationerror', id);
						 	return false;
						 }
					}
				}
				
				var validate = this.get('validateFile');
				if (validate && !validate(file, this)) {
					this.fireEvent('file:validationerror', id);
					return false;
				}
				
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
				
				io.on('load', function (event) {
					//Upload complete event
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
		 * Handle drag enter event
		 * 
		 * @param {Event} evt Event
		 * @private
		 */
		dragEnter: function (evt) {
			evt.halt();
		},
		
		/**
		 * Handle drag exit event
		 * 
		 * @param {Event} evt Event
		 * @private
		 */
		dragExit: function (evt) {
			evt.halt();
			
			if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				this.last_drop_target = null;
			}
		},
		
		/**
		 * Handle drag over event
		 * 
		 * @param {Event} evt Event
		 * @private
		 */
		dragOver: function (evt) {
			evt.halt();
			
			var target = evt.target.closest(this.get('dropTarget'));
			if (target) {
				//If dragged on drop target then highlight it
				if (this.last_drop_target) {
					if (target.compareTo(this.last_drop_target)) {
						//Nothing changed
						return;
					} else {
						this.last_drop_target.removeClass('yui3-html5-dd-target');
						this.last_drop_target = null;	
					}
				}
				
				target.addClass('yui3-html5-dd-target');
				this.last_drop_target = target;
			} else if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				this.last_drop_target = null;
			}
			
			if (target) {
				//If on drop target then allow drop
				evt._event.dataTransfer.dropEffect = 'copy';
			} else {
				//If outside drop target (parent node) disallow drop
				evt._event.dataTransfer.dropEffect = 'none';
			}
		},
		
		/**
		 * Handle drag drop event
		 * 
		 * @param {Event} evt Event
		 * @private
		 */
		dragDrop: function (evt) {
			evt.halt();
			
			if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				this.last_drop_target = null;
			}
			
			var files = evt._event.dataTransfer.files;
			if (files.length) {
				var i = 0,
					ii = files.length;
				
				if (!this.get('multiple')) ii = 1;
				for(; i<ii; i++) {
					this.addFile(files[i]);
				}
			}
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