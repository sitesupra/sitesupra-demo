/**
 * MediaLibraryExtendedList adds wider layout, folder creating, folder renaming, sorting, image/file editing
 */
YUI.add('supra.medialibrary-list-extended', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var List = Supra.MediaLibraryList,
		Template = Supra.Template;
	
	/*
	 * HTML5 support
	 */
	var FILE_API_SUPPORTED = typeof FileReader !== 'undefined';
	
	/**
	 * Extended media list
	 * Handles data loading, scrolling, selection
	 */
	function Extended (config) {
		Extended.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Extended.NAME = 'medialist-extended';
	Extended.CLASS_NAME = Y.ClassNameManager.getClassName(Extended.NAME);
	
	/**
	 * Constant, file template
	 * @type {String}
	 */
	Extended.TEMPLATE_FILE = Template.compile('\
		<div class="file">\
			<div class="preview">\
				<img src="/cms/lib/supra/build/medialibrary/assets/skins/supra/images/icons/{%if broken %}broken{% else %}file{% if known_extension %}-{{ known_extension }}{% endif %}{% endif %}-large.png" {% if known_extension %}class="known-extension"{% endif %} alt="" />\
			</div>\
			\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" data-value-mask=\'^[^\\\\._][^\\\\\\\\\\\\/\\\\|:\\\\?\\\\*<>\\\\s\\"]*$\' data-use-replacement="true" />\
			</span>\
			\
			{% if metaProperties|length %}\
				<div class="meta yui3-form-vertical"></div>\
			{% endif %}\
			\
			<div class="group">\
				<div class="info">\
					{% if extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ extension|upper }} {{ "medialibrary.file"|intl }}</span>\
						</div>\
					{% endif %}\
					<div>\
						<span class="info-label">{{ "medialibrary.size"|intl }}</span>\
						<span class="info-data" data-update="size">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\\n\
					<div {% if !modified or created == modified %}class="hidden"{% endif %}>\\n\
                        <span class="info-label">{{ "medialibrary.modified"|intl }}</span>\
						<span class="info-data" data-update="modified">{% if modified %}{{ modified|datetime_short|default("&nbsp;") }}{% endif %}</span>\
					</div>\
				</div>\
				\
				<div class="input-group"><button type="button" class="download">{{ "medialibrary.download"|intl }}</button></div>\
				<div class="input-group"><button type="button" class="replace">{{ "buttons.replace"|intl }}</button></div>\
			</div>\
		</div>');
	
	/**
	 * Constant, image template
	 * @type {String}
	 */
	Extended.TEMPLATE_IMAGE = Template.compile('\
		<div class="image">\
			<div class="preview">\
				{% if broken %}\
					<img src="/cms/lib/supra/build/medialibrary/assets/skins/supra/images/icons/broken-large.png" class="known-extension" alt="" />\
				{% else %}\
					<img src="{{ preview|escape }}?t={{ timestamp }}" alt="" />\
				{% endif %}\
			</div>\
			\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" data-value-mask=\'^[^\\\\._][^\\\\\\\\\\\\/\\\\|:\\\\?\\\\*<>\\\\s\\"]*$\' data-use-replacement="true" />\
			</span>\
			\
			{% if metaProperties|length %}\
				<div class="meta yui3-form-vertical"></div>\
			{% endif %}\
			\
			<div class="group">\
				<div class="info">\
					{% if extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ extension|upper }} {{ "medialibrary.image"|intl }}</span>\
						</div>\
					{% endif %}\
					<div>\
						<span class="info-label">{{ "medialibrary.size"|intl }}</span>\
						<span class="info-data" data-update="size">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\\n\
					<div {% if !modified or created == modified %}class="hidden"{% endif %}>\\n\
                        <span class="info-label">{{ "medialibrary.modified"|intl }}</span>\
						<span class="info-data" data-update="modified">{% if modified %}{{ modified|datetime_short|default("&nbsp;") }}{% endif %}</span>\
					</div>\
					{% if sizes %}\
						<div>\
							<span class="info-label">{{ "medialibrary.dimensions"|intl }}</span>\
							<span class="info-data" data-update="dimensions">{{ sizes.original.width }} x {{ sizes.original.height }}</span>\
						</div>\
					{% endif %}\
				</div>\
				\
				<div class="input-group"><button type="button" class="download">{{ "medialibrary.download"|intl }}</button></div>\
				<div class="input-group"><button type="button" class="replace">{{ "buttons.replace"|intl }}</button></div>\
				<div class="input-group"><button type="button" class="edit">{{ "medialibrary.edit"|intl }}</button></div>\
			</div>\
		</div>');
	
	/**
	 * Constant, folder item template for temporary file
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_TEMP = Template.compile('\
		<li class="type-temp' + (FILE_API_SUPPORTED ? '' : ' type-temp-legacy') + '" data-id="{{ id }}">\
			<span class="title">{{ filename|escape }}</span>\
			<a class="cancel"></a>\
			<span class="progress"><em></em></span>\
		</li>');
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_IMAGE = Template.compile('\
		<li class="type-image {% if broken or !thumbnail %}type-broken{% endif %}" data-id="{{ id }}">\
			<a>{% if !broken and thumbnail %}<img src="{{ thumbnail|escape }}?t={{ timestamp }}" alt="" />{% endif %}</a>\
			<span>{{ filename|escape }}</span>\
		</li>');
		
	
	
	
	Extended.ATTRS = {
		/**
		 * Slideshow class
		 * @type {Function}
		 */
		'slideshowClass': {
			'value': Supra.SlideshowMultiView
		},
		
		/**
		 * Request URI for image rotate
		 * @type {String}
		 */
		'imageRotateURI': {
			value: null
		},
		/**
		 * Request URI for image crop
		 * @type {String}
		 */
		'imageCropURI': {
			value: null
		},
		
		/**
		 * Meta data properties
		 * @type {Array}
		 */
		'metaProperties': {
			value: []
		},
		
		/**
		 * Templates
		 */
		'templateFile': {
			value: Extended.TEMPLATE_FILE
		},
		'templateImage': {
			value: Extended.TEMPLATE_IMAGE
		},
		'templateFolderItemTemp': {
			value: Extended.TEMPLATE_FOLDER_ITEM_TEMP
		},
		'templateFolderItemImage': {
			value: Extended.TEMPLATE_FOLDER_ITEM_IMAGE
		}
	};
	
	
	Y.extend(Extended, List, {
		
		
		/**
		 * Add folder to the parent.
		 * Chainable
		 * 
		 * @param {Number} parent Parent ID
		 * @param {String} title Folder title
		 */
		addFolder: function (parent, title) {
			var parent_id = null,
				parent_data = null;
			
			if (parent === "0") {
				parent = 0;
			}
			
			if (parent) {
				parent_id = parent;
				parent_data = this.getItemData(parent_id);
				
				if (!parent_data || parent_data.type != List.TYPE_FOLDER) {
					return false;
				}
			} else {
				parent_data = this.getSelectedFolder();
				if (parent_data) {
					parent_id = parent_data.id;
				} else {
					parent_id = this.get('rootFolderId');
					parent_data = {'id': parent_id};
				}
			}
			
			if (parent_data) {
				var data_object = this.get('data');
				
				if (data_object.cache.one(-1)) {
					return;
				} else if (parent_data && !this.isOpened(parent_id)) {
					this.open(parent_id).done(function () {
						this.addFolder(parent_id, title);
					}, this);
					return; 
				}
				
				//Don't have an ID for this item yet, using -1
				var data = {
					'id': -1,
					'parent': parent_id,
					'type': List.TYPE_FOLDER,
					'title': title || '',
					'children_count': 0,
					'private': this.isFolderPrivate(parent_id),
					'children': []
				};
				
				//Add item to the folder list
				this.renderItem(data.parent, [data], true);
				this.get('data').cache.add(data);
				
				//Start editing
				var slide = this.slideshow.getSlide('slide_' + data.parent),
					node = slide.all('li.type-folder');
				
				node = node.item(node.size() - 1);
				
				this.edit.renameFolder(node);
			}
			
			return this;
		},
		
		moveFolder: function (id /* Folder ID */, parent /* New parent folder ID */) {
			var data_object = this.get('data'),
				previous_parent = data_object.cache.one(id).parent,
				moved = true,
				node = this.getItemNode(id),
				new_slide = null,
				new_slide_content = null,
				prev_slide = null,
				prev_slide_content = null,
				position = null,
				was_empty = false;
			
			//Folder was moved in data
			new_slide = this.slideshow.getSlide('slide_' + (parent || 0));
			prev_slide_content = node.closest('.su-slide-content, .su-multiview-slide-content');
			
			if (previous_parent.toString() === parent.toString()) {
				return false;
			}
			
			//Remove item
			position = Y.DOM.removeFromDOM(node);
			
			//Update previous slide scrollbars
			prev_slide_content.fire('contentResize');
			
			if (new_slide) {
				new_slide_content = new_slide.one('.su-slide-content, .su-multiview-slide-content');
				
				if (new_slide.one('div.empty')) {
					//Currently folder is not rendered into slide, create empty folder
					var temp = this.renderTemplate({'id': parent}, this.get('templateFolder'));
					temp.setData('itemId', parent);
					new_slide_content.empty().append(temp);
					was_empty = true;
				}
				
				//new_slide.one('ul.folder').append(node);
				//Place node into correct position (kinda)
				var ul = new_slide.one('ul.folder'),
					li = ul.all('li.type-folder');
				
				if (li.size()) {
					li.item(li.size() - 1).insert(node, 'after');
				} else {
					ul.prepend(node);
				}
				
				new_slide.setData('itemId', parent);
				node.setData('itemId', id); //data was lost for some reason
				
				//Update new slide scrollbars
				new_slide_content.fire('contentResize');
			}
			
			//Trigger folder move
			this.fire('folderMove', {
				'id': id,
				'newParent': parent,
				'prevParent': previous_parent
			});
			
			data_object.save({'id': id, 'parent': parent})
				.done(function () {
					//
					if (previous_parent && previous_parent !== '0' && !data_object.cache.one(previous_parent).children.length) {
						//If previous parent is now empty render empty template
						var temp = this.renderTemplate({'id': previous_parent}, this.get('templateEmpty'));
						temp.setData('itemId', parent);
						prev_slide_content.empty().append(temp);
					}
					
					//Node no longer needed
					node.destroy();
					
					//Trigger folder move
					this.fire('folderMoveComplete', {
						'id': id,
						'newParent': parent,
						'prevParent': previous_parent
					});
					
				}, this)
				.fail(function () {
					//Place node back into previous position
					Y.DOM.restoreInDOM(position);
					node.setData('itemId', id); //data was lost for some reason
					
					//If folder was empty before insert then restore empty template
					if (was_empty) {
						var temp = this.renderTemplate({'id': parent}, this.get('templateEmpty'));
						temp.setData('itemId', parent);
						new_slide_content.empty().append(temp);
					}
					
					//Update slide scrollbars
					if (prev_slide_content) prev_slide_content.fire('contentResize');
					if (new_slide_content) new_slide_content.fire('contentResize');
					
					//Trigger folder move
					this.fire('folderMove', {
						'id': id,
						'newParent': previous_parent,
						'prevParent': parent
					});
					
					//Trigger folder move
					this.fire('folderMoveComplete', {
						'id': id,
						'newParent': previous_parent,
						'prevParent': parent
					});
					
				}, this);
			
			return moved;
		},
		
		/**
		 * Deletes selected item.
		 * Chainable
		 * 
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		deleteSelectedItem: function () {
			var item = this.getSelectedItem(),
				deferred = null;
			
			if (item) {
				var data = this.get('data'),
					id = item.id,
					node = this.getItemNode(id),
					parent_id = item.parent;
				
				//Only hide item, because request may fail
				node.addClass('hidden');
				
				//If item is opened, then open parent and redraw list
				if (this.slideshow.isInHistory('slide_' + id)) {
					this.open(parent_id);
				}
				
				deferred = data.remove(id);
				deferred
					.done(function () {
						var node = this.getItemNode(id),
							parent_slide = this.slideshow.getSlide('slide_' + parent_id),
							parent_data = null;
						
						if (parent_slide) {
							parent_data = data.cache.one(parent_id);
							
							if (!parent_data || !parent_data.children_count) {
								this.renderItem(parent_id);
							} else {
								//Remove node
								if (node) {
									node.remove();
								}
								
								//Update scrollbars
								parent_slide.one('.su-slide-content, .su-multiview-slide-content').fire('contentResize');
							}
						}
						
					}, this)
					.fail(function () {
						var node = this.getItemNode(id);
						node.removeClass('hidden');
					}, this);
				
			} else {
				deferred = new Supra.Deferred();
				deferred.reject();
			}
			
			return deferred.promise();
		},
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			var container = this.get('boundingBox');
			container.addClass(Y.ClassNameManager.getClassName(Extended.NAME, 'extended'));
			
			Extended.superclass.renderUI.apply(this, arguments);
			
			//Add plugin for editing files and folders
			this.plug(List.Edit, {
				'data': this.get('data')
			});
			
			//Add plugin for folder drag and drop
			this.plug(List.FolderDD, {});
			
			//Add plugin for editing images
			this.plug(List.ImageEditor, {
				'data': this.get('data'),
				'rotateURI': this.get('imageRotateURI'),
				'cropURI': this.get('imageCropURI')
			});
			
			this.open(this.get('rootFolderId'));
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var content = this.get('contentBox'),
				container = this.get('boundingBox');
			
			//On folder click start rename
			content.delegate('click', this.edit.handleRenameClick, 'ul.folder > li.type-folder', this.edit);
			
			//On list click close folder
			content.delegate('click', this.handleCloseFolderClick, 'div.su-multiview-slide', this);
			
			//On item render set up form
			this.on('itemRender', this.handleItemRender, this);
			
			Extended.superclass.bindUI.apply(this, arguments);
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			Extended.superclass.syncUI.apply(this, arguments);
		},
		
		/**
		 * Handle click outside folder, close sub-folders
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleCloseFolderClick: function (event) {
			var target = event.target;
			
			// Click on folder item is already handled 
			if (target.closest('ul.folder')) return;
			
			// Get slide
			target = target.closest('div.su-multiview-slide');
			if (!target) return;
			
			var id = target.getData('itemId');
			if (!id && id !== 0) return;
			
			//Style element
			target.all('li').removeClass('selected');
			
			//Scroll to slide
			if (this.getOpenedItem().id != id) {
				this.open(id);
			}
		},
		
		/**
		 * Handle download click
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		handleDownloadClick: function (event /* Event */) {
			var uri = this.get('data').get('downloadURI'),
				item = this.getSelectedItem();
			
			//Add 'id' to the uri
			uri += (uri.indexOf('?') !== -1 ? '&' : '?') + 'id=' + item.id;
			
			var pathnameEnd = uri.indexOf('?');
			var filename = encodeURIComponent(item.filename);
			uri = uri.substring(0, pathnameEnd) + '/' + filename + uri.substring(pathnameEnd);
			
			//Open in new tab
			window.open(uri);
		},
		
		/**
		 * Handle replace button click
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		handleReplaceClick: function (event /* Event */) {
			var item = this.getSelectedItem(); 
			this.upload.openBrowser(item.id);
		},
		
		/**
		 * Reload image source
		 * 
		 * @param {Object} data Image data
		 * @private
		 */
		reloadImageSource: function (data) {
			var img_node = this.getImageNode(),
				src = null;
			
			// Reload image source in preview
			if (img_node) {
				var preview_size = this.get('previewSize');
				
				if (data.sizes && preview_size in data.sizes) {
					// Image preview
					src = data.sizes[preview_size].external_path;
					
					img_node.ancestor().addClass('loading');
					img_node.once('load', function () {
						img_node.ancestor().removeClass('loading');
					});
					img_node.setAttribute('src', src + '?r=' + (+new Date()));
				} else if (data.type === List.TYPE_FILE) {
					// File icon
					img_node.ancestor().addClass('loading');
					img_node.once('load', function () {
						img_node.ancestor().removeClass('loading');
					});
					
					src = "/cms/lib/supra/build/medialibrary/assets/skins/supra/images/icons/file";
					if (data.known_extension) {
						src += '-' + data.known_extension;
					}
					src += "-large.png";
					
					img_node.setAttribute('src', src);
				}
			}
			
			// Reload image source in file list
			if (data.type === List.TYPE_IMAGE) {
				var item_node = this.getItemNode(data.id);
				if (item_node) {
					item_node.removeClass('type-broken');
					item_node.one('a').set('innerHTML', '<img src="' + data.thumbnail + '?r=' + (+new Date()) + '" " alt="" />');
				}
			} else if (data.type === List.TYPE_FILE) {
				var item_node = this.getItemNode(data.id);
				if (item_node) {
					item_node.removeClass('type-broken');
					
					if (data.known_extension) {
						item_node.addClass('type-file-' + data.known_extension);
					}
				}
				
			}
			
		},
		
		/**
		 * Reload folder data
		 *
		 * @param {String} folder Folder ID
		 */
		reloadFolder: function (folder) {
			//Reload file list
			var data = this.get('data'),
				parent = data.cache.one(folder).parent,
				children = data.cache.all(folder);
			
			data.purge(folder);
			
			//Remove slide
			this.slideshow.removeSlide('slide_' + folder);
			
			//Load slide
			var old_value = this.slideshow.get('noAnimations');
			this.slideshow.set('noAnimations', true);
			
			this.open(parent);
			this.open(folder, Y.bind(function () {
				this.slideshow.set('noAnimations', old_value);
			}, this));
		},
		
		/**
		 * When image or file is rendered create inputs, etc.
		 * 
		 * @param {Object} event Event
		 * @private
		 */
		handleItemRender: function (event /* Event */) {
			if (event.type == List.TYPE_IMAGE || event.type == List.TYPE_FILE) {
				var node = event.node,
					inp = {};
				
				//Clean up
				this.removePropertyWidgets();
				
				//Create buttons
				var buttons = node.all('button'),
					btn_download = new Supra.Button({ 'srcNode': buttons.filter('.download').item(0) }),
					btn_replace  = new Supra.Button({ 'srcNode': buttons.filter('.replace').item(0) });
				
				btn_download.render();
				btn_replace.render();
				
				inp.btn_download = btn_download;
				inp.btn_replace = btn_replace;
				
				btn_download.on('click', this.handleDownloadClick, this);
				
				//Replace button
				if (FILE_API_SUPPORTED) {
					//
					btn_replace.on('click', this.handleReplaceClick, this);
				} else {
					//Create file upload form
					this.upload.createLegacyInput(btn_replace, true);
				}
				
				//Create form
				node.all('input,textarea,select').each(function (item) {
					if (item.getAttribute('data-supra-ignore')) return;
					
					var tag = item.get('tagName').toLowerCase(),
						name = item.getAttribute('name'),
						props = {
							'srcNode': item,
							'useReplacement': !!item.getAttribute('data-use-replacement'),
							'type': (tag == 'input' ? 'String' : (tag == 'textarea' ? 'Text' : 'SelectList')),
							'value': item.get('value')
						};
					
					var obj = new Supra.Input[props.type](props);
					obj.render();
					
					obj.on('change', this.edit.onItemPropertyChange, this.edit, {'data': event.data, 'input': obj});
					
					inp[item.get('name')] = obj;
				}, this);
								
				//Save input instances to destroy them when re-rendered
				this.property_widgets = inp;
				
				//Meta properties
				this.renderMetaDataProperties(event, node);
			}
		},
		
		/**
		 * Returns true if item is already opened
		 * 
		 * @param {String} id Item id
		 * @returns {Boolean} True if item is opened
		 */
		isOpened: function (id) {
			return this.slideshow.isInHistory('slide_' + id);
		},
		
		
		/* ------------------------- Meta data ------------------------- */
		
		
		/**
		 * Returns data for rendering template
		 * 
		 * @param {Object} data
		 * @return Transformed data
		 * @type {Object}
		 * @private
		 */
		getRenderData: function (data) {
			var item_data = Extended.superclass.getRenderData.apply(this, arguments);
			
			// Add meta data properties
			item_data.metaProperties = this.get('metaProperties');
			
			return item_data;
		},
		
		/**
		 * Render meta data properties
		 * 
		 * @param {Object} event itemRender event
		 * @param {Object} node Slide node
		 */
		renderMetaDataProperties: function (event, node) {
			var meta_properties = this.get('metaProperties'),
				i = 0,
				ii = meta_properties.length,
				
				inputs = this.property_widgets,
				input,
				
				container = node.one('.meta'),
				
				data = event.data,
				name;
			
			for (; i<ii; i++) {
				name = meta_properties[i].name || meta_properties[i].id;
				
				input = Supra.Form.factoryField(Supra.mix({
					// On change for save is used name, not id attribute
					'name': name,
					'value': data.metaData ? data.metaData[name] : null
				}, meta_properties[i]));
				
				input.render(container);
				
				// Handle value change
				input.on('change', this.edit.onItemPropertyChange, this.edit, {'data': event.data, 'input': input});
				
				// Save into input list, these inputs are destroyed when slide closes
				inputs[meta_properties[i].id] = input;
			}
		}
		
	}, {});
	
	
	Supra.MediaLibraryExtendedList = Extended;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': [
	'supra.input',
	'supra.medialibrary-list',
	'supra.slideshow-multiview',
	'supra.medialibrary-list-edit',
	'supra.medialibrary-image-editor',
	'supra.medialibrary-list-folder-dd'
]});