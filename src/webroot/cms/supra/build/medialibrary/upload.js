//Invoke strict mode
"use strict";

/**
 * Plugin to add file upload functionality for MediaList
 */
YUI.add('supra.medialibrary-upload', function (Y) {
	
	//Shortcuts
	var IO = Supra.MediaLibraryList.UploadIO,
		MediaLibraryList = Supra.MediaLibraryList;
	
	/**
	 * File upload
	 * Handles standard file upload, HTML5 drag & drop, simple input fallback
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'medialist-upload';
	Plugin.NS = 'upload';
	
	Plugin.ATTRS = {
		/**
		 * Upload request URI
		 * @type {String}
		 */
		'requestUri': {
			value: null
		},
		
		/**
		 * HTML5 Drag & Drop container node
		 * @type {Object}
		 */
		'dragContainer': {
			value: null
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Last drop target element
		 * @type {Object}
		 */
		last_drop_target: null,
		
		/**
		 * Last drop target folder ID
		 * @type {Number}
		 */
		last_drop_id: null,
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			
			//Create invisible input which will be used for "Browse file" window
			var input = Y.Node.create('<input class="offscreen" type="file" multiple="multiple" />');
			input.on('change', this.onFileBrowse, this);
			this.get('host').get('contentBox').append(input);
			this.set('input', input); 
			
			//Set up HTML5 DD
			var node = this.get('dragContainer') || this.get('host').get('boundingBox');
			
			node.on('dragenter', this.dragEnter, this);
			node.on('dragexit', this.dragExit, this);
			node.on('dragover', this.dragOver, this);
			node.on('drop', this.dragDrop, this);
		},
		
		dragEnter: function (evt) {
			evt.halt();
		},
		
		dragExit: function (evt) {
			evt.halt();
		},
		
		dragOver: function (evt) {
			evt.halt();
			var target = evt.target,
				node = target.closest('li.type-folder'),
				folder_node = null,
				folder_id = null;
			
			if (node) {
				folder_id = parseInt(node.getData('itemId'), 10);
				folder_node = node;
			} else {
				node = target.closest('div.yui3-ml-slideshow-slide');
				if (node) {
					node = node.one('ul.folder,div.empty');
					if (node) {
						folder_id = parseInt(node.getAttribute('data-id'), 10);
						folder_node = node.ancestor();
					}
				}
			}
			
			if (folder_id !== this.last_drop_id) {
				if (this.last_drop_target) {
					this.last_drop_target.removeClass('yui3-html5-dd-target')
				}
				if (folder_node) {
					folder_node.addClass('yui3-html5-dd-target')
				}
				
				this.last_drop_target = folder_node;
				this.last_drop_id = folder_id;
			}
			
			if (folder_id || folder_id === 0) {
				evt._event.dataTransfer.dropEffect = 'copy';
			} else {
				evt._event.dataTransfer.dropEffect = 'none';
			}
		},
		
		dragDrop: function (evt) {
			evt.halt();
			
			if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				var files = evt._event.dataTransfer.files,
					folder = this.last_drop_id;
				
				this.uploadFiles(folder, files);
			}
			
			this.last_drop_target = null;
			this.last_drop_id = null;
		},
		
		/**
		 * Open "Browse file" window
		 */
		openBrowser: function () {
			//Open file browse window
			var input = this.get('input');
			var node = Y.Node.getDOMNode(input);
			node.click();
		},
		
		
		/**
		 * When files are browsed start uploading them
		 * 
		 * @private
		 */
		onFileBrowse: function () {
			//Get files
			var files = Y.Node.getDOMNode(this.get('input')).files;
			if (!files.length) return;
			
			//Find folder
			var folder = this.get('host').getSelectedFolder();
				folder = folder ? folder.id : this.get('host').get('rootFolderId');
			
			this.uploadFiles(folder, files);
		},
		
		/**
		 * Upload files
		 * 
		 * @param {FileList} files
		 * @private
		 */
		uploadFiles: function (folder, files) {
			if (!files || !files.length) return;
			
			//Find folder
			var folder = folder ? folder : this.get('host').get('rootFolderId');
				data = {'folder': folder},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null;
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If only images are displayed, then only images can be uploaded. Same with files
				if (!this.testFileType(files.item(i))) continue;
				
				//Create temporary item
				file = files.item(i);
				file_id = this.get('host').addFile(folder, {'title': file.fileName || file.name});
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'folder': folder,
					'file_id': file_id,
					'file_name': file.fileName || file.name,
					'node': this.get('host').getItemNode(file_id)
				};
				
				io = new IO({
					'file': file,
					'requestUri': uri,
					'data': data,
					'eventData': event_data
				});
				
				//Add event listeners
				io.on('load', this.onFileComplete, this);
				io.on('progress', this.onFileProgress, this);
				
				//Start uploading
				io.start();
			}
		},
		
		/**
		 * On file upload progress update progress bar
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onFileProgress: function (evt) {
			evt.node.one('em').setStyle('width', ~~(evt.percentage) + '%');
		},
		
		/**
		 * On file upload complete change temporary item into real item
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onFileComplete: function (evt) {
			var host = this.get('host'),
				data = evt.data,
				node = evt.node,
				folder = evt.folder,
				file_id = evt.file_id,
				data_object = host.get('dataObject');
			
			if (data) {
				//Add file
				var new_file_id = this.get('host').addFile(folder, data),
					new_file_node = this.get('host').getItemNode(new_file_id);
				
				//Place it before temporary item
				if (new_file_node && node) {
					node.insert(new_file_node, 'before');
				}
			} else {
				Y.log('Failed to upload "' + evt.file_name + '"');
			}
			
			//Remove temporary data and node
			if (file_id) data_object.removeData(file_id, true);
			if (node) node.remove();
		},
		
		/**
		 * Checks if file type is allowed to be uploaded
		 * Testing is based on file extension
		 * 
		 * @param {File} file
		 * @return True if file type is allowed, otherwise false
		 * @type {Boolean}
		 * @private
		 */
		testFileType: function (file) {
			switch(this.get('host').get('displayType')) {
				case MediaLibraryList.DISPLAY_ALL:
					return true;
				case MediaLibraryList.DISPLAY_IMAGES:
					return !!file.type.match(/^image\//);
				case MediaLibraryList.DISPLAY_FILES:
					return !!(!file.type.match(/^image\//));
				default:
					return true;
			}
		}
		
	});
	
	
	Supra.MediaLibraryList.Upload = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.medialibrary-upload-io', 'plugin']});