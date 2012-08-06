//Invoke strict mode
"use strict";

/**
 * MediaLibraryExtendedList adds wider layout, folder creating, folder renaming, sorting, image/file editing
 */
YUI.add('supra.medialibrary-list-extended', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Data = Supra.MediaLibraryData,
		List = Supra.MediaLibraryList,
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
	
	Extended.NAME = 'medialist';
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
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask=\'^[^\\\\._][^\\\\\\\\\\\\/\\\\|:\\\\?\\\\*<>\\\\s\\"]*$\' suUseReplacement="true" />\
			</span>\
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
						<span class="info-data">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\
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
					<img src="{{ preview|escape }}?r={{ Math.random() }}" alt="" />\
				{% endif %}\
			</div>\
			\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask=\'^[^\\\\._][^\\\\\\\\\\\\/\\\\|:\\\\?\\\\*<>\\\\s\\"]*$\' suUseReplacement="true" />\
			</span>\
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
						<span class="info-data">{{ Math.round(size/1000)|default("0") }} KB</span>\
					</div>\
					{% if created %}\
						<div>\
							<span class="info-label">{{ "medialibrary.created"|intl }}</span>\
							<span class="info-data">{{ created|datetime_short|default("&nbsp;") }}</span>\
						</div>\
					{% endif %}\
					{% if sizes %}\
						<div>\
							<span class="info-label">{{ "medialibrary.dimensions"|intl }}</span>\
							<span class="info-data">{{ sizes.original.width }} x {{ sizes.original.height }}</span>\
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
		<li class="type-temp" data-id="{{ id }}">\
			<span class="title">{{ filename|escape }}</span>\
			<span class="progress"><em></em></span>\
		</li>');
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_IMAGE = Template.compile('\
		<li class="type-image {% if broken or !thumbnail %}type-broken{% endif %}" data-id="{{ id }}">\
			<a>{% if !broken and thumbnail %}<img src="{{ thumbnail|escape }}?r={{ Math.random() }}" alt="" />{% endif %}</a>\
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
		 * Sorting
		 * @type {String}
		 */
		'sortBy': {
			value: 'filename'
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
		 * Image and file property inputs
		 * @type {Object}
		 * @private
		 */
		property_widgets: {},
		
		
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
			
			if (parent) {
				parent_id = parent;
				parent_data = this.getItemData(parent_id);
				
				if (!parent_data || parent_data.type != Data.TYPE_FOLDER) {
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
				var data_object = this.get('dataObject');
				
				//Don't have an ID for this item yet, using -1
				var data = {
					'id': -1,
					'parent': parent_data.id,
					'type': Supra.MediaLibraryData.TYPE_FOLDER,
					'title': title || '',
					'children_count': 0,
					'private': data_object.isFolderPrivate(parent_data.id),
					'children': []
				};
				
				//Add item to the folder list
				this.renderItem(data.parent, [data], true);
				this.get('dataObject').addData(data.parent, [data], true);
				
				//Start editing
				var slide = this.slideshow.getSlide('slide_' + data.parent),
					node = slide.all('li.type-folder');
				
				node = node.item(node.size() - 1);
				
				this.edit.renameFolder(node);
			}
			
			return this;
		},
		
		/**
		 * Move folder
		 * 
		 * @param {String} id Folder ID
		 * @param {String} parent New parent folder ID
		 */
		moveFolder: function (id /* Folder ID */, parent /* New parent folder ID */) {
			var data_object = this.get('dataObject'),
				previous_parent = data_object.getData(id).parent,
				moved = false,
				node = this.getItemNode(id),
				new_slide = null,
				new_slide_content = null,
				prev_slide = null,
				prev_slide_content = null,
				position = null,
				was_empty = false;
			
			moved = data_object.moveFolder(id, parent, function (data, success) {
				if (moved) {
					//Failed to move folder, revert changes
					if (!success) {
						
						data_object.moveFolder(id, previous_parent, true);
						
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
					} else {
						if (previous_parent && previous_parent !== '0' && !data_object.getData(previous_parent).children.length) {
							//If previous parent is now empty render empty template
							var temp = this.renderTemplate({'id': previous_parent}, this.get('templateEmpty'));
							temp.setData('itemId', parent);
							prev_slide_content.empty().append(temp);
						}
						
						//Node no longer needed
						node.destroy();
					}
					
					//Trigger folder move
					this.fire('folderMoveComplete', {
						'id': id,
						'newParent': success ? parent : previous_parent,
						'prevParent': success ? previous_parent : parent
					});
				}
			}, this);
			
			//Folder was moved in data
			if (moved) {
				new_slide = this.slideshow.getSlide('slide_' + (parent || 0));
				prev_slide_content = node.closest('.su-slide-content, .su-multiview-slide-content');
				
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
					
					new_slide.one('ul.folder').append(node);
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
			}
			
			return moved;
		},
		
		/**
		 * Deletes selected item.
		 * Chainable
		 */
		deleteSelectedItem: function () {
			var item = this.getSelectedItem();
			if (item) {
				var data_object = this.get('dataObject'),
					item_id = item.id,
					node = this.getItemNode(item_id),
					parent_id = item.parent,
					parent_slide = null,
					parent_data = null;
				
				//Send request to server
				data_object.saveDeleteData(item_id, Y.bind(function () {
					
					//Remove all data
					data_object.removeData(item_id, true);
					
					//If item is opened, then open parent and redraw list
					if (this.slideshow.isInHistory('slide_' + item_id)) {
						this.open(parent_id);
					}
					
					parent_slide = this.slideshow.getSlide('slide_' + parent_id)
					if (parent_slide) {
						parent_data = data_object.getData(parent_id);
						
						if (!parent_data || !parent_data.children_count) {
							this.renderItem(parent_id);
						} else {
							//Remove node
							node.remove();
							
							//Update scrollbars
							parent_slide.one('.su-slide-content, .su-multiview-slide-content').fire('contentResize');
						}
					}
					
				}, this));
			}
			return this;
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
			
			//Initialize drag and drop to prevent DD from being attached to iframe
			if ('Manager' in Supra && 'PageContent' in Supra.Manager) {
				Supra.Manager.PageContent.initDD();
			}
			
			//Add plugin for editing files and folders
			this.plug(List.Edit, {
				'dataObject': this.get('dataObject')
			});
			
			//Add plugin for folder drag and drop
			this.plug(List.FolderDD, {});
			
			//Add plugin for editing images
			this.plug(List.ImageEditor, {
				'dataObject': this.get('dataObject'),
				'rotateURI': this.get('imageRotateURI'),
				'cropURI': this.get('imageCropURI')
			});
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
			
			//On sort change redraw lists
			this.after('sortByChange', this.handleSortingChange, this);
			
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
		 * Sort or filter data
		 * 
		 * @param {Array} data
		 * @return Sorted and filtered data
		 * @type {Array}
		 * @private
		 */
		sortData: function (data) {
			var sort_by = this.get('sortBy');
			
			//Duplicate
			data = [].concat(data);
			
			//Sort
			data.sort(function (a, b) {
				//Folder always first
				if (a.type != b.type && (a.type == Data.TYPE_FOLDER || b.type == Data.TYPE_FOLDER)) {
					return a.type < b.type ? -1 : 1;
				}
				
				var val_a = a[sort_by],
					val_b = b[sort_by];
				
				if (typeof val_a == 'string') val_a = val_a.toLowerCase();
				if (typeof val_b == 'string') val_b = val_b.toLowerCase();
				
				return val_a < val_b ? -1 : 1;
			});
			
			return data;
		},
		
		/**
		 * Change sorting
		 * @param {Object} value
		 */
		handleSortingChange: function (evt) {
			if (evt.newVal == evt.oldVal) return;
			
			var value = evt.newVal,
				item = this.getSelectedItem(),
				root_folder_id = this.get('rootFolderId'),
				path = null,
				slides = this.slideshow.slides;
			
			if (item) {
				path = item.path.slice(1);
				path.push(item.id);
			} else {
				path = [root_folder_id];
			}
			
			this.set('noAnimations', true);
			this.open(root_folder_id);
			
			for(var id in slides) {
				if (id != 'slide_' + root_folder_id) {
					this.slideshow.removeSlide(id);
				}
			}
			
			//Render items
			this.renderItem(root_folder_id);
			this.open(path);
			this.set('noAnimations', false);
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
			this.open(id);
		},
		
		/**
		 * Handle download click
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		handleDownloadClick: function (event /* Event */) {
			var uri = this.get('downloadURI'),
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
			
			if (img_node) {
				var preview_size = this.get('previewSize');
				
				if (data.sizes && preview_size in data.sizes) {
					src = data.sizes[preview_size].external_path;
					
					img_node.ancestor().addClass('loading');
					img_node.once('load', function () {
						img_node.ancestor().removeClass('loading');
					});
					img_node.setAttribute('src', src + '?r=' + (+new Date()));
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
			var data_object = this.get('dataObject'),
				parent = data_object.getData(folder).parent,
				children = data_object.getChildrenData(folder);
			
			for(var i=0,ii=children.length; i<ii; i++) {
				data_object.removeData(children[i].id);
			}
			
			//Remove children data
			delete(data_object.dataIndexed[folder].children);
			
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
		 * Returns image preview node
		 * 
		 * @private
		 */
		getImageNode: function () {
			var item = this.getSelectedItem(),
				slide = null;
			
			if (item) {
				slide = this.slideshow.getSlide('slide_' + item.id);
				if (slide) {
					return slide.one('div.preview img');
				}
			}
			
			return null;
		},
		
		/**
		 * When image or file is rendered create inputs, etc.
		 * 
		 * @param {Object} event Event
		 * @private
		 */
		handleItemRender: function (event /* Event */) {
			if (event.type == Data.TYPE_IMAGE || event.type == Data.TYPE_FILE) {
				var node = event.node,
					inp = this.property_widgets;
				
				for(var i in inp) {
					inp[i].destroy();
				}
				inp = {};
				
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
					if (item.getAttribute('suIgnore')) return;
					
					var tag = item.get('tagName').toLowerCase(),
						name = item.getAttribute('name'),
						props = {
							'srcNode': item,
							'useReplacement': !!item.getAttribute('suUseReplacement'),
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
			}
		},
		
		/**
		 * Get file or image form property widgets
		 * 
		 * @return Property widgets
		 */
		getPropertyWidgets: function () {
			return this.property_widgets;
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