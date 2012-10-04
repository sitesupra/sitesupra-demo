/**
 * Plugin to add file upload functionality for MediaList
 */
YUI.add('supra.medialibrary-upload', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var IO = Supra.IOUpload,
		IOLegacy = Supra.IOUploadLegacy,
		MediaLibraryList = Supra.MediaLibraryList,
		FILE_API_SUPPORTED = typeof FileReader !== 'undefined';
	
	//File name black list
	var FILE_BLACKLIST = [".DS_Store"];
	
	//Number of files which can uploaded simulteniously
	var MAX_SIMULTENIOUS_UPLOADS = 1;
	
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
		},
		
		/**
		 * File input
		 * @type {Object}
		 */
		'input': {
			value: null
		},
		
		/**
		 * Form element for adding new file
		 * Used only if File API is not supported
		 * @type {Object}
		 */
		'form': {
			value: null
		},
		
		/**
		 * Iframe element
		 * Used only if File API is not supported
		 * @type {Object}
		 */
		'iframe': {
			value: null
		},
		
		/**
		 * File input for replacing file
		 * Used only if File API is not supported
		 * @type {Object}
		 */
		'input_replace': {
			value: null
		},
		
		/**
		 * Form element for replacing file
		 * Used only if File API is not supported
		 * @type {Object}
		 */
		'form_replace': {
			value: null
		},
		
		/**
		 * Iframe element for replacing file
		 * Used only if File API is not supported
		 * @type {Object}
		 */
		'iframe_replace': {
			value: null
		},
		
		/**
		 * Display type: all, images or files
		 * @type {Number}
		 */
		'displayType': {
			value: Supra.MediaLibraryList.DISPLAY_ALL
		},
		
		/**
		 * Number of max simultenious uploads
		 * @type {Number}
		 */
		'maxSimulteniousUploads': {
			value: MAX_SIMULTENIOUS_UPLOADS
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
		 * Drag element is valid
		 * @type {Boolean}
		 */
		valid_drag: true,
		
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			this.subscribers = [];
			
			var input = null,
				node_id = null,
				uri = null,
				form = null,
				iframe = null;
			
			if (FILE_API_SUPPORTED) {
				this.createInput();
			} else {
				// Create form and iframe where form will be submitted to For IE
				var button = Supra.Manager.PageToolbar.getActionButton('mlupload');
				if (button) {
					this.createLegacyInput(button, false);
				}
			}
			
			//Enable
			if (!this.get('disabled')) {
				this.set('disabled', false);
			}
		},
		
		/**
		 * Create input for file upload
		 * 
		 * @private
		 */
		createInput: function () {
			var container = this.get('host').get('contentBox'),
				input = null;
			
			//Create invisible input which will be used for "Browse file" window
			input = Y.Node.create('<input class="offscreen" type="file" multiple="multiple" />');
			input.on('change', this.onFileBrowse, this);
			container.append(input);
			
			this.set('input', input);
		},
		
		/**
		 * Create input for legacy browsers
		 * File insert and file replace will be two different forms
		 * inserted inside "Upload" and "Replace" buttons to capture mouse click
		 * 
		 * @private
		 */
		createLegacyInput: function (button, for_replace) {
			var container = button.get('boundingBox'),
				uri = document.location.protocol + '//' + document.location.hostname + '/cms/lib/supra/build/io/blank.html',
				node_id = Y.guid(),
				iframe,
				form,
				input;
			
			iframe = Y.Node.create('<iframe class="offscreen" id="' + node_id + '" name="' + node_id + '" src="' + uri + '" />');
			form   = Y.Node.create('<form target="' + node_id + '" class="legacy-file-upload-form" method="post" action="" enctype="multipart/form-data">' +
										'<input suIgnore="true" type="file" name="file" class="upload-file-input" />' +
										'<button suIgnore="true" type="submit">Upload</button>' +
								  '</form>');
			
			input = form.one('input');
			input.on('change', function () {
				if (for_replace) {
					var item = this.get('host').getSelectedItem();
					this.replaceFileLegacy(item.id);
				} else {
					this.onFileBrowse();
				}
			}, this);
			
			Y.one('body').append(iframe);
			container.append(form);
			container.addClass('legacy-file-upload-container');
			
			if (!for_replace) {
				this.set('form', form);
				this.set('iframe', iframe);
				this.set('input', input);
			} else {
				if (this.get('iframe_replace')) this.get('iframe_replace').remove();
				if (this.get('form_replace')) this.get('form_replace').remove();
				if (this.get('input_replace')) this.get('input_replace').remove();
				
				this.set('form_replace', form);
				this.set('iframe_replace', iframe);
				this.set('input_replace', input);
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
		
		dragStart: function (evt) {
			//Drag start is called only for elements, not files from outside
			//the document
			this.valid_drag = false;
		},
		
		dragEnd: function (evt) {
			this.valid_drag = true;
		},
		
		dragOver: function (evt) {
			if (!this.valid_drag) return;
			
			evt.halt();
			
			var target = evt.target,
				node = target.closest('li.type-folder'),
				folder_node = null,
				folder_id = null;
			
			if (node) {
				folder_id = node.getData('itemId');
				folder_node = node;
			} else {
				node = target.closest('div.su-multiview-slide, div.su-slide');
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
			if (!this.valid_drag) return;
			evt.halt();
			
			if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				
				var folder = this.last_drop_id;
				
				this.getDragDropFiles(evt._event.dataTransfer, function (files) {
					//Sync position
					this.get('host').slideshow.syncUI();
					
					//Validate files
					files = this.testFiles(files);
					
					if (files.length) {
						//Upload all files
						this.uploadFiles(folder, files);
					} else {
						//No files detected
						Supra.Manager.executeAction('Confirmation', {
						    'message': '{#medialibrary.validation_error.invalid_drop#}',
						    'useMask': true,
						    'buttons': [
						        {'id': 'delete', 'label': 'Ok'}
						    ]
						});
					}
				});
			}
			
			this.last_drop_target = null;
			this.last_drop_id = null;
		},
		
		/**
		 * Returns all files from drop
		 * 
		 * @param {Object} data Data transfer object
		 * @param {Function} callback 
		 * @return {Array} Files list
		 * @private
		 */
		getDragDropFiles: function (data, callback) {
			if (!data.items) {
				return callback.call(this, data.files);
			}
			
			var entry = null,
				
				items = data.items,
				item  = null,
				i     = 0,
				ii    = items.length,
				
				files  = data.files,
				output = [],
				wait   = 0,
				
				self   = this;
			
			for (; i<ii; i++) {
				item = items[i];
				
				if (item.webkitGetAsEntry) {
					entry = item.webkitGetAsEntry();
				} else if (entry = item.mozGetAsEntry()) {
					entry = item.mozGetAsEntry();
				} else if (entry = item.getAsEntry()) {
					entry = item.getAsEntry();
				}
				
				if (entry) {
					wait++;
					this.traverseFileTree(entry, "", function (files) {
						output = output.concat(files);
						
						if (!--wait && i == ii) {
							callback.call(self, output);
						}
					});
				} else {
					//We can't get entry for files or folders, assume it's ok
					output.push(files[i]);
				}
			}
			
			if (!wait) {
				callback.call(this, output);
			}
		},
		
		/**
		 * 
		 */
		traverseFileTree: function (item, path, callback) {
			var self = this;
			path = path || "";
			
			if (item.isFile) {
				item.file(function (file) {
					file.path = path;
					callback([file]);
				});
			} else if (item.isDirectory) {
				var dirReader = item.createReader(),
					output = [];
				
				var readEntries = function () {
					dirReader.readEntries(function(entries) {
						if (entries.length) {
							var wait = 0;
							for (var i=0, ii=entries.length; i<ii; i++) {
								wait++;
								self.traverseFileTree.call(self, entries[i], path + (path ? "/" : "") + item.name, function (files) {
									output = output.concat(files);
									if (!--wait && i == ii) {
										//Last entry, try again
										readEntries();
									}
								});
							}
							//Keep reading until there are no more files/folder
							if (!wait) readEntries();
						} else {
							//That's it
							callback(output);
						}
					});	
				};
				
				readEntries();
			} else {
				//Is this even possible?
				callback([]);
			}
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
				if (FILE_API_SUPPORTED) input.removeAttribute('multiple');
				input.setData('fileId', file_id);
			} else {
				if (FILE_API_SUPPORTED) input.setAttribute('multiple', 'multiple');
				input.setData('fileId', null);
			}
			
			node.click();
		},
		
		
		/**
		 * When files are browsed start uploading them
		 * @private
		 */
		onFileBrowse: function () {
			//Get files
			var files = Y.Node.getDOMNode(this.get('input')).files;
			
			if (!FILE_API_SUPPORTED) {
				//Will use default form submit without progress support
				files = false;
			} else if  (!files.length) {
				//No files were selected
				return;
			}
			
			//Find folder
			var file_id = this.get('input').getData('fileId'),
				folder = this.get('host').getSelectedFolder();
			
			folder = folder ? folder.id : this.get('host').get('rootFolderId');
			
			if (!file_id) {
				//Upload new files
				if (FILE_API_SUPPORTED) {
					this.uploadFiles(folder, files);
				} else {
					this.uploadFilesLegacy(folder);
				}
			} else {
				//Replace file
				if (FILE_API_SUPPORTED) {
					this.replaceFile(file_id, files);
				} else {
					this.replaceFileLegacy(file_id);
				}
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
				file = null,
				file_name = null,
				node = null,
				queue = [];
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If validation fails, then skip this one
				if (!this.testFile(files[i])) continue;
				
				file = files[i];
				file_name = file.fileName || file.name;
				
				//Create temporary item
				file_id = this.get('host').addFile(folder, {'title': file_name, 'filename': file_name});
				
				node = this.get('host').getItemNode(file_id);
				
				//Set folder path
				data.folderPath = file.path || "";
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'folder': folder,
					'folderPath': file.path || null,
					'file_id': file_id,
					'file_name': file_name,
					'node': node
				};
				
				io = new IO({
					'file': file,
					'requestUri': uri,
					'data': data,
					'eventData': event_data
				});
				
				//Abort upload on X click
				var cancel = node.one('.cancel');
				if (cancel) cancel.on('click', io.abort, io);
				
				//Add event listeners
				io.on('progress', this.onFileProgress, this);
				io.on('load', function (evt) {
					this.onFileComplete(evt);
					this.uploadFilesNext(queue);
				}, this);
				
				//Add file to queue				
				queue.push(io);
			}
			
			for (var i=0, ii=this.get('maxSimulteniousUploads'); i<ii; i++) {
				//Start uploading
				this.uploadFilesNext(queue);
			}
		},
		
		/**
		 * Upload next file from the list
		 */
		uploadFilesNext: function (queue) {
			if (queue.length) {
				var io = queue.shift();
				io.start();
			}
		},
		
		/**
		 * Upload files using standart form submit, instead of File API
		 * 
		 * @param {Number} folder Folder ID into which file will be uploaded
		 * @private
		 */
		uploadFilesLegacy: function (folder /* Folder ID */) {
			var file_name = this.get('input').getDOMNode().value || '';
			
			file_name = file_name.replace(/.*(\\|\/)/, '');
			
			//If only images are displayed, then only images can be uploaded. Same with files
			if (!file_name || !this.testFileExtension(file_name)) return;
			
			//Find folder
			var folder = folder ? folder : this.get('host').get('rootFolderId'),
				data = {'folder': folder},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null;
			
			//Create temporary item
			file_id = this.get('host').addFile(folder, {'title': file_name, 'filename': file_name});
			
			//Event data will be passed to 'load' and 'progress' event listeners
			event_data = {
				'folder': folder,
				'file_id': file_id,
				'file_name': file_name,
				'node': this.get('host').getItemNode(file_id)
			};
			
			io = new IOLegacy({
				'form': this.get('form'),
				'iframe': this.get('iframe'),
				'requestUri': uri,
				'data': data,
				'eventData': event_data
			});
			
			//Add event listeners
			io.on('load', this.onFileComplete, this);
			
			//Start uploading
			io.start();
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
				if (!this.testFileType(files[i])) continue;
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'file_id': file_id
				};
				
				io = new IO({
					'file': files[i],
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
		 * Replace file using standart form submit, instead of File API
		 * 
		 * @param {Number} file_id File ID which will be replaced
		 * @private
		 */
		replaceFileLegacy: function (file_id /* File ID */) {
			var file_name = this.get('input_replace').getDOMNode().value || '';
			file_name = file_name.replace(/.*(\\|\/)/, '');
			
			//If only images are displayed, then only images can be uploaded. Same with files
			if (!file_name || !this.testFileExtension(file_name)) return;
			
			//Find folder
			var data = {'file_id': file_id},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				img_node = this.get('host').getImageNode();
			
			if (img_node) {
				img_node.ancestor().addClass('loading');
			}
			
			//Event data will be passed to 'load' and 'progress' event listeners
			event_data = {
				'file_id': file_id
			};
			
			io = new IOLegacy({
				'form': this.get('form_replace'),
				'iframe': this.get('iframe_replace'),
				'requestUri': uri,
				'data': data,
				'eventData': event_data
			});
			
			//Add event listeners
			io.on('load', this.onFileComplete, this);
			
			//Start uploading
			io.start();
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
		 * This is called also if file upload failed
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
				temp_file = (typeof file_id == 'number' && file_id < 0), 
				img_node = host.getImageNode();
				
			if (img_node) {
				img_node.ancestor().removeClass('loading');
			}
			
			if (data) {
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
					//If request was for replace and image is still opened then
					//reload image source
					var item = this.get('host').getSelectedItem();
					if (item && file_id == item.id) {
						this.get('host').reloadImageSource(data);
					}
					
					return;
				}
				
				//Add file
				var new_file_id = this.get('host').addFile(folder, data),
					new_file_node = this.get('host').getItemNode(new_file_id);
								
				//Place it before temporary item
				if (new_file_node && node) {
					node.insert(new_file_node, 'before');
				}
				
				//Remove temporary data and node
				if (file_id) data_object.removeData(file_id, true);
				if (node) node.remove();
				
			} else {
				Y.log('Failed to upload "' + evt.file_name + '"', 'debug');
				if (node) node.remove();
			}		
		},
		
		/**
		 * Checks if file type is allowed to be uploaded
		 * Testing is based on file mime type (extension)
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
					var file_name = file.fileName || file.name;
					
					//SWF is data and image
					if (file_name.match(/\.swf$/)) return true;
					return !!file.type.match(/^image\//);
				case MediaLibraryList.DISPLAY_FILES:
					return !!(!file.type.match(/^image\//));
				default:
					return true;
			}
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
		testFileExtension: function (file_name) {
			switch(this.get('host').get('displayType')) {
				case MediaLibraryList.DISPLAY_ALL:
					return true;
				case MediaLibraryList.DISPLAY_IMAGES:
					return !!file_name.match(/\.(swf|jpeg|jpg|gif|png|bmp|tiff|iff)$/);
				case MediaLibraryList.DISPLAY_FILES:
					return !file_name.match(/\.(jpeg|jpg|gif|png|bmp|tiff|iff)$/);
				default:
					return true;
			}
		},
		
		/**
		 * Test for valid file name
		 * 
		 * @param {String} file_name File name
		 * @return {Boolean} True if file name is valid, otherwise false
		 * @private
		 */
		testValidFileName: function (file_name) {
			return Y.Array.indexOf(FILE_BLACKLIST, file_name) === -1;
		},
		
		/**
		 * Test file
		 * 
		 * @param {File} file
		 * @return {Boolean} True if file type is allowed, otherwise false
		 * @private
		 */
		testFile: function (file) {
			if (!file.size) {
				//Folders doesn't have size in FF
				return false;
			} else if (!this.testFileType(file)) {
				//If only images are displayed, then only images can be uploaded. Same with files
				return false;
			} else if (!this.testValidFileName(file.fileName || file.name)) {
				//Probablly system file
				return false;
			} else {
				return true;
			}
		},
		
		/**
		 * Tests all files and returns only those which pass validation
		 * 
		 * @param {Array} files File list
		 * @return {Array} File list with only those files which pass validation
		 * @private
		 */
		testFiles: function (files) {
			var i = 0,
				ii = files.length,
				output = [];
			
			for (; i<ii; i++) {
				if (this.testFile(files[i])) {
					output.push(files[i]);
				}
			}
			
			return output;
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
				if (FILE_API_SUPPORTED) {
					this.subscribers.push(node.on('dragstart', this.dragStart, this));
					this.subscribers.push(node.on('dragend',   this.dragEnd, this));
					this.subscribers.push(node.on('dragenter', this.dragEnter, this));
					this.subscribers.push(node.on('dragexit', this.dragExit, this));
					this.subscribers.push(node.on('dragover', this.dragOver, this));
					this.subscribers.push(node.on('drop', this.dragDrop, this));
				}
			}
			
			return !!disabled;
		}
		
	});
	
	
	Supra.MediaLibraryList.Upload = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io-upload', 'plugin']});