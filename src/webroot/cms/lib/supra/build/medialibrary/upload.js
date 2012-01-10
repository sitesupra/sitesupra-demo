//Invoke strict mode
"use strict";

/**
 * Plugin to add file upload functionality for MediaList
 */
YUI.add('supra.medialibrary-upload', function (Y) {
	
	//Shortcuts
	var IO = Supra.IOUpload,
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
		},
		
		/**
		 * File upload disabled
		 * @type {Boolean}
		 */
		'disabled': {
			value: false,
			setter: '_setDisabled'
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
		 * Event subscribers
		 * @type {Array}
		 */
		subscribers: [],
		
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			
			this.subscribers = [];
			
			//Create invisible input which will be used for "Browse file" window
			var input = Y.Node.create('<input class="offscreen" type="file" multiple="multiple" />');
			input.on('change', this.onFileBrowse, this);
			this.get('host').get('contentBox').append(input);
			this.set('input', input); 
			
			//Enable
			if (!this.get('disabled')) {
				this.set('disabled', false);
			}
		},
		
		/**
		 * Returns container node for drag and drop
		 * 
		 * @return Container node
		 * @type {Object}
		 */
		getNode: function () {
			return this.get('dragContainer') || this.get('host').get('boundingBox');
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
				folder_id = node.getData('itemId');
				folder_node = node;
			} else {
				node = target.closest('div.yui3-slideshow-multiview-slide, div.yui3-slideshow-slide');
				if (node) {
					node = node.one('ul.folder,div.empty');
					if (node) {
						folder_id = node.getAttribute('data-id');
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
				
				//Sync position
				this.get('host').slideshow.syncUI();
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
				
				//Sync position
				this.get('host').slideshow.syncUI();
				
				//Upload all files
				this.uploadFiles(folder, files);
			}
			
			this.last_drop_target = null;
			this.last_drop_id = null;
		},
		
		/**
		 * Open "Browse file" window
		 * 
		 * @param {Number} file_id Optional. File ID which will be replaced
		 */
		openBrowser: function (file_id /* File ID */) {
			//Open file browse window
			var input = this.get('input');
			var node = Y.Node.getDOMNode(input);
			
			if (file_id) {
				input.removeAttribute('multiple');
				input.setData('fileId', file_id);
			} else {
				input.setAttribute('multiple', 'multiple');
				input.setData('fileId', null);
			}
			
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
			
			if (!files) {
				//File API is not supported
				//@TODO
				return;
			}
			
			if (!files.length) return;
			
			//Find folder
			var file_id = this.get('input').getData('fileId'),
				folder = this.get('host').getSelectedFolder();
			
			folder = folder ? folder.id : this.get('host').get('rootFolderId');
			
			if (!file_id) {
				//Upload new files
				this.uploadFiles(folder, files);
			} else {
				//Replace file
				this.replaceFile(file_id, files);
			}
		},
		
		/**
		 * Upload files
		 * 
		 * @param {Number} folder Folder ID into which file will be uploaded
		 * @param {FileList} files File list
		 * @private
		 */
		uploadFiles: function (folder /* Folder ID */, files /* File list */) {
			if (!files || !files.length) return;
			
			//Find folder
			var folder = folder ? folder : this.get('host').get('rootFolderId'),
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
		 * Replace file
		 * 
		 * @param {Number} file_id File ID which will be replaced
		 * @param {FileList} files File list
		 * @private
		 */
		replaceFile: function (file_id /* File ID */, files /* File list */) {
			if (!files || !files.length) return;
			
			//Find folder
			var data = {'file_id': file_id},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				img_node = this.get('host').getImageNode();
			
			if (img_node) {
				img_node.ancestor().addClass('loading');
			}
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If only images are displayed, then only images can be uploaded. Same with files
				if (!this.testFileType(files.item(i))) continue;
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'file_id': file_id
				};
				
				io = new IO({
					'file': files.item(i),
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
			if (evt.node) {
				evt.node.one('em').setStyle('width', ~~(evt.percentage) + '%');
			}
		},
		
		/**
		 * On file upload complete change temporary item into real item
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onFileComplete: function (evt) {
			var host = this.get('host'),
				data_object = host.get('dataObject'),
				data = evt.data,
				file_id = evt.file_id,
				node = evt.node,
				folder = evt.folder,
				temp_file = (typeof file_id == 'number' && file_id < 0);
			
			if (temp_file) {
				//Mix temporary and loaded data
				var old_data = data_object.getData(file_id);
				data = Supra.mix({}, old_data, data);
			} else if (file_id) {
				//If request was replace then update data
				var old_data = data_object.getData(file_id);
				if (old_data) {
					Supra.mix(old_data, data);
				}
				
				//Fire event on media list
				this.get('host').fire('replace', {'file_id': file_id});
			}
			
			if (!evt.node) {
				//If request was for replace and image is till opened then
				//reload image source
				var item = this.get('host').getSelectedItem();
				if (item && file_id == item.id) {
					this.get('host').reloadImageSource(data);
				}
				
				return;
			}
			
			if (data) {
				//Add file
				var new_file_id = this.get('host').addFile(folder, data),
					new_file_node = this.get('host').getItemNode(new_file_id);
								
				//Place it before temporary item
				if (new_file_node && node) {
					node.insert(new_file_node, 'before');
				}
			} else {
				Y.log('Failed to upload "' + evt.file_name + '"', 'error');
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
		},
		
		/**
		 * Disabled attribute setter
		 */
		_setDisabled: function (disabled) {
			//Enable HTML5 drag & drop
			var node = this.getNode();
			
			//Remove listeners
			for(var i=0,ii=this.subscribers.length; i<ii; i++) {
				this.subscribers[i].detach();
			}
			this.subscribers = [];
			
			//Add listeners
			if (!disabled) {
				this.subscribers.push(node.on('dragenter', this.dragEnter, this));
				this.subscribers.push(node.on('dragexit', this.dragExit, this));
				this.subscribers.push(node.on('dragover', this.dragOver, this));
				this.subscribers.push(node.on('drop', this.dragDrop, this));
			}
			
			return !!disabled;
		}
		
	});
	
	
	Supra.MediaLibraryList.Upload = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io-upload', 'plugin']});