/**
 * Plugin to add file upload functionality for MediaList
 */
YUI.add('supra.medialibrary-upload', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var IO = Supra.IOUpload,
		IOLegacy = Supra.IOUploadLegacy;
	
	/**
	 * File upload
	 * Handles standard file upload, HTML5 drag & drop, simple input fallback
	 */
	function Uploader (config) {
		Uploader.superclass.constructor.apply(this, arguments);
	}
	
	Uploader.NAME = 'medialibrary-uploader';
	Uploader.CLASS_NAME = 'uploader';
	
	Uploader.ATTRS = {
		'medialist': {
			value: null
		}
	};
	
	Y.extend(Uploader, Supra.Uploader, {
		
		/**
		 * When files are browsed start uploading them
		 * Handler for legacy browsers
		 * @private
		 */
		onLegacyFileBrowse: function (for_replace) {
			if (for_replace) {
				var item = this.get('medialist').getSelectedItem();
				this.replaceFileLegacy(item.id);
			} else {
				this.onFileBrowse();
			}
		},
		
		/**
		 * Returns folder into which files should be uploaded to
		 * 
		 * @returns {String} Folder ID into which files should be uploaded to
		 * @private
		 */
		getUploadFolder: function () {
			var folder = this.get('medialist').getSelectedFolder();
			return folder ? folder.id : this.get('medialist').get('rootFolderId');
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
			 * If you are updating this file, please update also uploader/uploader.js if needed
			 */
			if (!files || !files.length) return;
			
			//Find folder
			var folder = folder ? folder : this.get('medialist').get('rootFolderId'),
				data = Supra.mix({'folder': folder}, this.get('data') || {}),
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null,
				file_name = null,
				node = null,
				queue = [],
				
				count = 0,
				loaded = 0;
			
			for(var i=0,ii=files.length; i<ii; i++) {
				//If validation fails, then skip this one
				if (!this.testFile(files[i])) continue;
				
				file = files[i];
				file_name = file.fileName || file.name;
				
				//Set folder path
				data = Supra.mix({}, data, {
					'folderPath': file.path || ''
				});
				
				//Create temporary item
				file_id = this.get('medialist').addFile(folder, {'title': file_name, 'filename': file_name, 'folderPath': data.folderPath});
				
				node = this.get('medialist').getItemNode(file_id);
				
				//Show loading icon on folder if uploading into a folder
				if (data.folderPath && node) {
					node.addClass('loading');
				}
				
				//Event data will be passed to 'load' and 'progress' event listeners
				event_data = {
					'folder': folder,
					'file_id': file_id,
					'file_name': file_name,
					'node': node,
					'folderPath': data.folderPath
				};
				
				io = new IO({
					'file': file,
					'requestUri': uri,
					'data': data,
					'eventData': event_data
				});
				
				//Abort upload on X click
				if (node) {
					var cancel = node.one('.cancel');
					if (cancel) {
						cancel.on('click', io.abort, io);
					}
				}
				
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
		 * Upload files using standart form submit, instead of File API
		 * 
		 * @param {Number} folder Folder ID into which file will be uploaded
		 * @private
		 */
		uploadFilesLegacy: function (folder /* Folder ID */) {
			/*
			 * !IMPORTANT
			 * If you are updating this file, please update also uploader/uploader.js if needed
			 */
			var file_name = this.get('input').getDOMNode().value || '';
			
			file_name = file_name.replace(/.*(\\|\/)/, '');
			
			//If only images are displayed, then only images can be uploaded. Same with files
			if (!file_name || !this.testFileTypeExtension(file_name)) return;
			
			//Find folder
			var folder = folder ? folder : this.get('medialist').get('rootFolderId'),
				data = {'folder': folder},
				event_data = null,
				io = null,
				uri = this.get('requestUri'),
				file_id = null,
				file = null;
			
			//Create temporary item
			file_id = this.get('medialist').addFile(folder, {'title': file_name, 'filename': file_name, 'folderPath': ''});
			
			//Event data will be passed to 'load' and 'progress' event listeners
			event_data = {
				'folder': folder,
				'file_id': file_id,
				'file_name': file_name,
				'node': this.get('medialist').getItemNode(file_id),
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
			if (Uploader.superclass.replaceFile.apply(this, arguments) !== false) {
				
				//Find image
				var img_node = this.get('medialist').getImageNode();
				if (img_node) {
					img_node.ancestor().addClass('loading');
				}
				
			}
		},
		
		/**
		 * Replace file using standart form submit, instead of File API
		 * 
		 * @param {Number} file_id File ID which will be replaced
		 * @private
		 */
		replaceFileLegacy: function (file_id /* File ID */) {
			if (Uploader.superclass.replaceFileLegacy.apply(this, arguments) !== false) {
				
				//Find folder
				var img_node = this.get('medialist').getImageNode();
				if (img_node) {
					img_node.ancestor().addClass('loading');
				}
				
			}
		},
		
		/**
		 * Returns data for file replace
		 * 
		 * @param {String} file_id File ID
		 * @returns {Object} Upload data
		 * @private
		 */
		getReplaceFileData: function (file_id) {
			var medialist = this.get('medialist'),
				data = {'file_id': file_id, 'sizes': []},
				size = null;
			
			// Thumbnail image size
			size = medialist.get('thumbnailSize').split('x');
			data.sizes.push({
				'width': size[0],
				'height': size[1],
				'crop': true
			});
			
			// Preview image size
			size = medialist.get('previewSize').split('x');
			data.sizes.push({
				'width': size[0],
				'height': size[1],
				'crop': false
			});
			
			return Supra.mix(data, this.get('data'));
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
			if (evt.node) {
				var progress_node = evt.node.one('em');
				if (progress_node) {
					progress_node.setStyle('width', ~~(evt.percentage) + '%');
				}
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
			var host = this.get('medialist'),
				data_object = host.get('data'),
				data = evt.data,
				file_id = evt.file_id,
				node = evt.node,
				folder = evt.folder,
				path = evt.folderPath,
				temp_file = (typeof file_id == 'number' && file_id < 0), 
				img_node = host.getImageNode();
				
			if (img_node) {
				img_node.ancestor().removeClass('loading');
			}
			
			if (path && data) {
				// File was uploaded within a folder, eg. uploaded a folder inside which was this file
				
				if (file_id) {
					var old_data = data_object.cache.one(file_id);
					
					if (old_data && temp_file) {
						//Remove temporary data and node
						data_object.cache.remove(file_id);
												
						//Add item to the folder list
						host.addFile(folder, data.folder);
					} else if (old_data) {
						data_object.cache.save(data.folder);
					} else if (all_files_completed) {
						// Files were inside folder which was inside another folder
						// Update children count
						data_object.cache.one(folder).children_count++;
					}
				}
				if (node && temp_file) {
					node.remove();
				} else if (node && all_files_completed) {
					node.removeClass('loading');
				}
				
				this.fire('file:complete', Supra.mix({}, data));
			} else if (data) {
				if (temp_file) {
					//Mix temporary and loaded data
					var old_data = data_object.cache.one(file_id);
					data = Supra.mix({}, old_data, data);
				} else if (file_id) {
					//If request was replace then update data
					var old_data = data_object.cache.one(file_id);
					if (old_data) {
						data_object.cache.save(data);
						data = data_object.cache.one(file_id);
					}
					
					//Fire event on media list
					host.fire('replace', {'file_id': file_id});
				}
				
				if (!evt.node) {
					if (temp_file) {
						//Item doesn't have a node, just add data
						data_object.cache.add(data);
					} else {
						//If request was for replace and image is still opened then
						//reload image source
						var item = host.getOpenedItem();
						if (item && file_id == item.id) {
							host.reloadImageSource(data);
						}
					}
					
					return;
				}
				
				//Add file
				var new_file_id = host.addFile(folder, data),
					new_file_node = host.getItemNode(new_file_id);
				
				//Place it before temporary item
				if (new_file_node && node) {
					node.insert(new_file_node, 'before');
				}
				
				//Remove temporary data and node
				if (file_id) data_object.cache.remove(file_id, true);
				if (node) node.remove();
				
				this.fire('file:complete', Supra.mix({
					old_id: file_id
				}, data));
			} else {
				//Remove temporary data and node
				if (file_id) data_object.cache.remove(file_id, true);
				if (node) node.remove();
				
				this.fire('file:error', {
					id: path ? null : file_id
				});
				
				Y.log('Failed to upload "' + evt.file_name + '"', 'debug');
				if (node) node.remove();
			}		
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
				this.get('medialist').slideshow.syncUI();
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
					this.get('medialist').slideshow.syncUI();
					
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
			
			this.last_drop_target = null;
			this.last_drop_id = null;
		}
	});
	
	
	Supra.MediaLibraryList.Uploader = Uploader;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.uploader']});