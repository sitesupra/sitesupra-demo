/**
 * File uploader
 */
YUI.add('supra.uploader', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var IO = Supra.IOUpload,
		IOLegacy = Supra.IOUploadLegacy,
		MediaLibraryList = Supra.MediaLibraryList;
	
	//Feature testing
	var FILE_API_SUPPORTED = typeof FileReader !== 'undefined';
	
	//File name black list
	var FILE_BLACKLIST = ['.DS_Store'];
	
	//File ID counter
	var FILE_COUNTER = 0;
	
	/**
	 * File upload
	 * Handles standard file upload, HTML5 drag & drop, simple input fallback
	 */
	function Uploader (config) {
		Uploader.superclass.constructor.apply(this, arguments);
	}
	
	Uploader.NAME = 'uploader';
	Uploader.CLASS_NAME = 'uploader';
	
	/**
	 * Constant, all data
	 * @type {Number}
	 */
	Uploader.TYPE_ALL = 0;
	
	/**
	 * Constant, only images
	 * @type {Number}
	 */
	Uploader.TYPE_IMAGES = 2;
	
	/**
	 * Constant, only files
	 * @type {Number}
	 */
	Uploader.TYPE_FILES = 3;
	
	
	Uploader.ATTRS = {
		/**
		 * Upload request URI
		 * @type {String}
		 */
		'requestUri': {
			value: null
		},
		
		/**
		 * Additional data which will be added to the POST body
		 */
		'data': {
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
		 * Number of max simultenious uploads
		 * @type {Number}
		 */
		'maxSimulteniousUploads': {
			value: 1
		},
		
		/**
		 * Create input for "Browse" functionality
		 * @type {Boolean}
		 */
		'allowBrowse': {
			value: true
		},
		
		/**
		 * Allow selecting and uploading multiple files
		 * @type {Boolean}
		 */
		'allowMultiple': {
			value: true
		},
		
		/**
		 * File upload folder id
		 * @type {String}
		 */
		'uploadFolderId': {
			value: 0
		},
		
		// ----- Validation -----
		
		/**
		 * File type: all, images or files
		 * @type {Number}
		 */
		'fileType': {
			value: Uploader.TYPE_ALL
		},
		
		/**
		 * File types which are accepted, mime type
		 * @type {String}
		 */
		'accept': {
			value: null
		},
		
		/**
		 * Custom file validation
		 * @type {Function}
		 */
		'validateFile': {
			value: null
		},
		
		// ----- Elements -----
		
		/**
		 * HTML5 Drag & Drop container node
		 * @type {Object}
		 */
		'dropTarget': {
			value: null
		},
		
		/**
		 * Click target
		 * @type {Object}
		 */
		'clickTarget': {
			value: null
		},
		
		/**
		 * File input
		 * @type {Object}
		 */
		'input': {
			value: null
		},
		
		// ----- Legacy browser support -----
		
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
		}
	};
	
	Y.extend(Uploader, Y.Base, {
		
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
				iframe = null,
				click_target = null;
			
			if (this.get('allowBrowse')) {
				if (FILE_API_SUPPORTED) {
					this.createInput();
					
					//Handle click on target
					click_target = this.get('clickTarget');
					if (click_target) {
						click_target.on('click', function () {
							this.openBrowser();
						}, this);
					}
				} else {
					var node = this.getClickTargetNode();
					if (node) {
						this.createLegacyInput(node, false);
					}
				}
			}
			
			//Enable
			if (!this.get('disabled')) {
				this.set('disabled', false);
			}
			
			//Load MediaLibrary localizations
			var Loader = Supra.Manager.Loader,
				Intl   = Supra.Intl,
				path   = Loader.getStaticPath() + Loader.getActionBasePath('MediaLibrary');
			
			Intl.loadAppData(path);
		},
		
		/**
		 * Create input for file upload
		 * 
		 * @private
		 */
		createInput: function () {
			var container = Y.one('body'),
				accept = this.get('accept'),
				multiple = this.get('allowMultiple'),
				input = null;
			
			//Create invisible input which will be used for "Browse file" window
			input = Y.Node.create('<input class="offscreen" type="file" ' + (multiple ? 'multiple="multiple" ' : '') + (accept ? 'accept="' + accept + '" ' : '') + '/>');
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
		createLegacyInput: function (container, for_replace) {
			var uri = document.location.protocol + '//' + document.location.hostname + '/cms/lib/supra/build/io/blank.html',
				node_id = Y.guid(),
				iframe,
				form,
				input;
			
			iframe = Y.Node.create('<iframe class="offscreen" id="' + node_id + '" name="' + node_id + '" src="' + uri + '" />');
			form   = Y.Node.create('<form target="' + node_id + '" class="legacy-file-upload-form" method="post" action="" enctype="multipart/form-data">' +
										'<input data-supra-ignore="true" type="file" name="file" class="upload-file-input" />' +
										'<button data-supra-ignore="true" type="submit">Upload</button>' +
								  '</form>');
			
			input = form.one('input');
			input.on('change', function () {
				this.onLegacyFileBrowse(for_replace);
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
		getDropTargetNode: function () {
			return this.get('dropTarget');
		},
		
		/**
		 * Returns container node for input nodes
		 * 
		 * @return Container node
		 * @type {Object}
		 */
		getClickTargetNode: function () {
			var target = this.get('clickTarget'),
				node = null;
			
			if (target) {
				node = target;
				if (node.isInstanceOf && node.isInstanceOf('Widget')) {
					node = node.get('boundingBox');
				}
			}
			
			return node;
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
			
			var folder_node = this.getDropTargetNode(),
				folder_id = this.getUploadFolder();
			
			if (folder_id !== this.last_drop_id) {
				if (this.last_drop_target) {
					this.last_drop_target.removeClass('yui3-html5-dd-target');
				}
				if (folder_node) {
					folder_node.addClass('yui3-html5-dd-target');
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
			if (!this.valid_drag) return;
			
			if (evt._event.dataTransfer.files.length) {
				evt.halt();
			}
			
			if (this.last_drop_target) {
				this.last_drop_target.removeClass('yui3-html5-dd-target');
				
				var folder = this.last_drop_id;
				
				if (evt._event.dataTransfer.files.length) {
					this.getDragDropFiles(evt._event.dataTransfer, function (files) {
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
							        {'id': 'delete', 'label': 'OK'}
							    ]
							});
						}
					});
				}
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
				} else if (entry = item.mozGetAsEntry) {
					entry = item.mozGetAsEntry();
				} else if (entry = item.getAsEntry) {
					entry = item.getAsEntry();
				}
				
				if (entry) {
					wait++;
					this.traverseFileTree(entry, '', function (files) {
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
			path = path || '';
			
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
								self.traverseFileTree.call(self, entries[i], path + (path ? '/' : '') + item.name, function (files) {
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
				if (FILE_API_SUPPORTED && this.get('allowMultiple')) input.setAttribute('multiple', 'multiple');
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
				folder = this.getUploadFolder();
			
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
		 * When files are browsed start uploading them
		 * Handler for legacy browsers
		 * @private
		 */
		onLegacyFileBrowse: function (for_replace) {
			this.onFileBrowse();
		},
		
		/**
		 * Returns folder into which files should be uploaded to
		 * 
		 * @returns {String} Folder ID into which files should be uploaded to
		 * @private
		 */
		getUploadFolder: function () {
			return this.get('uploadFolderId');
		},
		
		/**
		 * Upload files
		 * 
		 * @param {Number} folder Folder ID into which file will be uploaded
		 * @param {FileList} files File list
		 * @private
		 */
		uploadFiles: function (folder /* Folder ID */, files /* File list */) {
			/*
			 * !IMPORTANT
			 * If you are updating this file, please update also medialibrary/upload.js
			 */
			if (!files || !files.length) return;
			
			//Find folder
			var folder = folder ? folder : this.get('uploadFolderId'),
				data = Supra.mix({'folder': folder}, this.get('data') || {}),
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null,
				file_name = null,
				queue = [],
				
				count = 0,
				loaded = 0;
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If validation fails, then skip this one
				if (!this.testFile(files[i])) continue;
				
				file = files[i];
				file_name = file.fileName || file.name;
				
				//Create temporary item
				FILE_COUNTER++;
				file_id = -FILE_COUNTER;
				
				//Set folder path
				data = Supra.mix({}, data, {
					'folderPath': file.path || ''
				});
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'folder': folder,
					'file_id': file_id,
					'file_name': file_name,
					'folderPath': data.folderPath
				};
				
				io = new IO({
					'file': file,
					'requestUri': uri,
					'data': data,
					'eventData': event_data
				});
				
				//Fire event
				this.fire('file:upload', {'title': file_name, 'filename': file_name, 'id': file_id, 'folderPath': data.folderPath});
				
				//Add event listeners
				count++;
				
				io.on('progress', this.onFileProgress, this);
				io.on('load', function (evt) {
					loaded++;
					var completed = count == loaded;
					
					this.onFileComplete(evt, completed);
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
			/*
			 * !IMPORTANT
			 * If you are updating this file, please update also medialibrary/upload.js
			 */
			var file_name = this.get('input').getDOMNode().value || '';
			
			file_name = file_name.replace(/.*(\\|\/)/, '');
			
			//If only images are displayed, then only images can be uploaded. Same with files
			if (!file_name || !this.testFileTypeExtension(file_name)) return;
			
			//Find folder
			var folder = folder ? folder : this.get('uploadFolderId'),
				data = {'folder': folder},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null;
			
			//Create temporary item
			FILE_COUNTER++;
			file_id = -FILE_COUNTER;
			
			//Event data will be passed to 'load' and 'progress' event listeners
			event_data = {
				'folder': folder,
				'file_id': file_id,
				'file_name': file_name,
				'folderPath': ''
			};
			
			io = new IOLegacy({
				'form': this.get('form'),
				'iframe': this.get('iframe'),
				'requestUri': uri,
				'data': data,
				'eventData': event_data
			});
			
			//Fire event
			this.fire('file:upload', {'title': file_name, 'filename': file_name, 'id': file_id, 'folderPath': ''});
				
			//Add event listeners
			io.on('load', function (evt) {
				this.onFileComplete(evt, true);
			}, this);
			
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
			if (!files || !files.length) return false;
			
			//Find folder
			var data = this.getReplaceFileData(file_id),
				event_data = null,
				io = null,
				uri = this.get('requestUri');
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If only images are displayed, then only images can be uploaded. Same with files
				if (!this.testFileType(files[i])) continue;
				
				//If only specific types are allowed, then file must match one of them
				if (!this.testFileAccept(files[i])) continue;
				
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
				
				//Fire event
				this.fire('file:replace', event_data);
				
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
			if (!file_name || !this.testFileTypeExtension(file_name)) return false;
			
			//Find folder
			var data = this.getReplaceFileData(file_id),
				event_data = null,
				io = null,
				uri = this.get('requestUri');
			
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
			
			//Fire event
			this.fire('file:replace', event_data);
			
			//Add event listeners
			io.on('load', this.onFileComplete, this);
			
			//Start uploading
			io.start();
		},
		
		/**
		 * Returns data for file replace
		 * 
		 * @param {String} file_id File ID
		 * @returns {Object} Upload data
		 * @private
		 */
		getReplaceFileData: function (file_id) {
			return Supra.mix({'file_id': file_id}, this.get('data'));
		},
		
		/**
		 * On file upload progress update progress bar
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onFileProgress: function (evt) {
			if (evt.file_id) {
				this.fire('file:progress', {'id': evt.file_id, 'percentage': evt.percentage});
			}
		},
		
		/**
		 * On file upload complete change temporary item into real item
		 * This is called also if file upload failed
		 * 
		 * @param {Event} evt
		 * @param {Boolean} all_files_completed All file has been uploaded
		 * @private
		 */
		onFileComplete: function (evt, all_files_completed) {
			var data = evt.data,
				file_id = evt.file_id,
				folder = evt.folder;
			
			if (data) {
				this.fire('file:complete', Supra.mix({
					old_id: file_id
				}, data));
			} else {
				this.fire('file:error', {
					id: file_id
				});
				Y.log('Failed to upload "' + evt.file_name + '"', 'debug');
			}
		},
		
		/**
		 * Checks if file type is allowed to be uploaded for set fileType
		 * Testing is based on file mime type (extension)
		 * 
		 * @param {File} file
		 * @return True if file type is allowed, otherwise false
		 * @type {Boolean}
		 * @private
		 */
		testFileType: function (file) {
			switch(this.get('fileType')) {
				case Uploader.TYPE_IMAGES:
					var file_name = file.fileName || file.name;
					
					//SWF is data and image
					if (file_name.match(/\.swf$/)) return true;
					return !!file.type.match(/^image\//);
				case Uploader.TYPE_FILES:
					return !!(!file.type.match(/^image\//));
				default:
					return true;
			}
		},
		
		/**
		 * Checks if file type is allowed to be uploaded for set fileType
		 * Testing is based on file extension, because older browsers doesn't support
		 * file type
		 * 
		 * @param {File} file
		 * @return True if file type is allowed, otherwise false
		 * @type {Boolean}
		 * @private
		 */
		testFileTypeExtension: function (file_name) {
			switch(this.get('fileType')) {
				case Uploader.TYPE_ALL:
					return true;
				case Uploader.TYPE_IMAGES:
					return !!file_name.match(/\.(swf|jpeg|jpg|gif|png|bmp|tiff|iff)$/);
				case Uploader.TYPE_FILES:
					return !file_name.match(/\.(jpeg|jpg|gif|png|bmp|tiff|iff)$/);
				default:
					return true;
			}
		},
		
		/**
		 * Checks if file type is allowed to be uploaded for set 'accept'
		 * 
		 * @param {File} file
		 * @return True if file type is allowed, otherwise false
		 * @type {Boolean}
		 * @private
		 */
		testFileAccept: function (file) {
			var accept = this.get('accept'),
				reg = null;
			
			if (accept) {
				accept = accept.split(',');
				
				for(var i=0,ii=accept.length; i<ii; i++) {
					 reg = accept[i].replace(/\*/g, '.*');
					 reg = new RegExp('^' + Y.Lang.trim(reg) + '$', 'i');
					 
					 if (!file.type.match(reg)) {
					 	return false;
					 }
				}
			}
			
			return true;
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
			if (!file) {
				this.fire('file:validationerror');
				return false;
			} else if (!file.size) {
				//Folders doesn't have size in FF
				this.fire('file:validationerror');
				return false;
			} else if (!this.testFileType(file)) {
				//If only images are displayed, then only images can be uploaded. Same with files
				this.fire('file:validationerror');
				return false;
			} else if (!this.testFileAccept(file)) {
				//If only specific types are allowed, then file must match one of the types
				this.fire('file:validationerror');
				return false;
			} else if (!this.testValidFileName(file.fileName || file.name)) {
				//Probablly system file
				this.fire('file:validationerror');
				return false;
			} else {
				var fn = this.get('validateFile');
				if (fn && !fn(file, this)) {
					this.fire('file:validationerror');
					return false;
				}
				
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
			var node = this.getDropTargetNode();
			if (!node) return !!disabled;
			
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
	
	
	Supra.Uploader = Uploader;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io-upload', 'base']});