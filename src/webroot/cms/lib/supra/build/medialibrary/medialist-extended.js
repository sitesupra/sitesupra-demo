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
				<img src="/cms/lib/supra/img/medialibrary/icon-file-large.png" alt="" />\
			</div>\
			<span class="inp-title" title="{{ "medialibrary.label_title"|intl }}">\
				<input type="text" name="title" value="{{ title|escape }}" />\
			</span>\
			<span class="inp-description" title="{{ "medialibrary.label_description"|intl }}">\
				<input type="text" name="description" value="{{ description|escape }}" />\
			</span>\
			<div class="localize"><button type="button">{{ "medialibrary.localize"|intl }}</button></div>\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask="^[a-zA-Z0-9\\-\\_\\.]*$" />\
			</span>\
			<div class="center"><button type="button">{{ "medialibrary.download"|intl }}</button></div>\
			<div class="center"><button type="button">{{ "buttons.replace"|intl }}</button></div>\
		</div>');
	
	/**
	 * Constant, image template
	 * @type {String}
	 */
	Extended.TEMPLATE_IMAGE = Template.compile('\
		<div class="image">\
			<div class="preview">\
				<img src="{{ previewUrl|escape }}?r={{ Math.random() }}" alt="" />\
			</div>\
			<span class="inp-title" title="{{ "medialibrary.label_title"|intl }}">\
				<input type="text" name="title" value="{{ title|escape }}" />\
			</span>\
			<span class="inp-description" title="{{ "medialibrary.label_description"|intl }}">\
				<input type="text" name="description" value="{{ description|escape }}" />\
			</span>\
			<div class="localize"><button type="button">{{ "medialibrary.localize"|intl }}</button></div>\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask="^[a-zA-Z0-9\\-\\_\\.]*$" />\
			</span>\
			<div class="center"><button type="button">{{ "medialibrary.download"|intl }}</button></div>\
			<div class="center"><button type="button">{{ "buttons.replace"|intl }}</button></div>\
			<div class="center"><button type="button" class="edit">{{ "medialibrary.edit"|intl }}</button></div>\
		</div>');
	
	/**
	 * Constant, folder item template for temporary file
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_TEMP = Template.compile('\
		<li class="type-temp" data-id="{{ id }}">\
			<span class="title">{{ title|escape }}</span>\
			<span class="progress"><em></em></span>\
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
			value: 'title'
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
		 * Slider widget instance
		 * @type {Object}
		 * @private
		 */
		slider: null,
		
		
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
				this.get('dataObject').addData(data.parent, [data]);
				
				//Start editing
				var slide = this.slideshow.getSlide('slide_' + data.parent),
					node = slide.all('li.type-folder');
				
				node = node.item(node.size() - 1);
				
				this.edit.renameFolder(node);
			}
			
			return this;
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
					parent_id = item.parent;
				
				//Send request to server
				data_object.saveDeleteData(item_id, Y.bind(function () {
					
					//Remove all data
					data_object.removeData(item_id, true);
					
					//If item is opened, then open parent and redraw list
					if (this.slideshow.isInHistory('slide_' + item_id)) {
						this.open(parent_id);
					}
					if (this.slideshow.getSlide('slide_' + parent_id)) {
						this.renderItem(parent_id);
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
			
			//Create slider
			var slider = this.slider = new Y.Slider({
				'length': container.get('offsetWidth') - 16,	//16px margin
				'value': 1000,	//At the end
				'max': 1000,	//for better precision
				'thumbUrl': Y.config.base + '/slider/assets/skins/supra/thumb-x.png'
			});
			slider.render(container);
			
			//Add plugin for editing files and folders
			this.plug(List.Edit, {
				'dataObject': this.get('dataObject')
			});
			
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
			content.delegate('click', this.handleCloseFolderClick, 'div.yui3-slideshow-multiview-slide', this);
			
			//On item render set up form
			this.on('itemRender', this.handleItemRender, this);
			
			//On sort change redraw lists
			this.after('sortByChange', this.handleSortingChange, this);
			
			//On slide update slideshow
			this.slider.after('valueChange', this.syncScrollPosition, this);
			
			//After resize update slider width
			Y.on('resize', Y.throttle(Y.bind(this.updateScroll, this), 50), window);
			
			this.slideshow.on('slideChange', this.updateScroll, this);
			
			Extended.superclass.bindUI.apply(this, arguments);
		},
		
		updateScroll: function () {
			var w = this.get('boundingBox').get('offsetWidth');
			this.slider.set('length', w - 16);		//16px margin
			this.syncScrollPosition(w);
		},
		
		/**
		 * Sync scroll position
		 */
		syncScrollPosition: function (width) {
			var pos = this.slider.get('value'),
				content_width = this.slideshow.history.length * this.slideshow._getWidth(),
				container_width = typeof width == 'number' ? width : this.get('boundingBox').get('offsetWidth'),
				offset = 0;
			
			if (container_width < content_width) {
				offset = Math.round((content_width - container_width) * pos / 1000);
			}
			
			this.slideshow.get('contentBox').setStyle('left', - offset + 'px');
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
			target = target.closest('div.yui3-slideshow-multiview-slide');
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
					btn_localize = new Supra.Button({'srcNode': buttons.item(0), 'style': 'small'}),
					btn_download = new Supra.Button({'srcNode': buttons.item(1)}),
					btn_replace = new Supra.Button({'srcNode': buttons.item(2)});
				
				btn_localize.render();
				btn_download.render();
				btn_replace.render();
				inp.btn_localize = btn_localize;
				inp.btn_download = btn_download;
				inp.btn_replace = btn_replace;
				
				btn_download.on('click', this.handleDownloadClick, this);
				btn_replace.on('click', this.handleReplaceClick, this);
				
				//Create form
				node.all('input').each(function (item) {
					var props = {
						'srcNode': item,
						'useReplacement': true,
						'value': item.get('value')
					};
					
					var obj = new Supra.Input.String(props);
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
	'slider',
	'supra.form',
	'supra.medialibrary-list',
	'supra.slideshow-multiview',
	'supra.medialibrary-list-edit',
	'supra.medialibrary-image-editor'
]});