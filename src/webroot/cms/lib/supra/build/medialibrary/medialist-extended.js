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
				<img src="/cms/lib/supra/img/medialibrary/icon-{% if broken %}broken{% else %}file{% if known_extension %}-{{ known_extension }}{% endif %}{% endif %}-large.png" alt="" />\
			</div>\
			\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask="^[a-zA-Z0-9\\-\\_\\.\s]*$" suUseReplacement="true" />\
			</span>\
			\
			<div class="group">\
				<div class="input-group"><button type="button" class="localize">{{ "medialibrary.localize"|intl }}</button></div>\
				\
				<a class="more">{{ "medialibrary.more_info"|intl }}</a>\
				<a class="less hidden">{{ "medialibrary.less_info"|intl }}</a>\
				<div class="info hidden">\
					{% if known_extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ known_extension|upper }} {{ "medialibrary.file"|intl }}</span>\
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
			</div>\
			\
			<div class="group hidden">\
				<div class="inp-locale">\
					<select name="locale">\
						{% set contexts = Supra.data.get("contexts") %}\
						{% set current_locale = null %}\
						{% for context in contexts %}\
							{% for locale in context.languages %}\
								{% if !current_locale %}\
									{% set current_locale = locale.id %}\
								{% endif %}\
								<option value="{{ locale.id }}">{{ locale.title|e }}</option>\
							{% endfor %}\
						{% endfor %}\
					</select>\
				</div>\
				<div class="inp-title" title="{{ "medialibrary.label_title"|intl }}">\
					<input type="text" name="title" value="{% if title && title[current_locale] %}{{ title[current_locale]|default("")|escape }}{% endif %}" />\
				</div>\
				<div class="inp-description" title="{{ "medialibrary.label_description"|intl }}">\
					<textarea name="description">{% if description && description[current_locale] %}{{ description[current_locale]|default("")|escape }}{% endif %}</textarea>\
				</div>\
				\
				<button type="button" class="done">{{ "buttons.done"|intl }}</button>\
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
					<img src="/cms/lib/supra/img/medialibrary/icon-broken-large.png" alt="" />\
				{% else %}\
					<img src="{{ previewUrl|escape }}?r={{ Math.random() }}" alt="" />\
				{% endif %}\
			</div>\
			\
			<span class="inp-filename" title="{{ "medialibrary.label_filename"|intl }}">\
				<input type="text" name="filename" value="{{ filename|escape }}" suValueMask="^[a-zA-Z0-9\\-\\_\\.\s]*$" suUseReplacement="true" />\
			</span>\
			\
			<div class="group">\
				<div class="input-group"><button type="button" class="localize">{{ "medialibrary.localize"|intl }}</button></div>\
				\
				<a class="more">{{ "medialibrary.more_info"|intl }}</a>\
				<a class="less hidden">{{ "medialibrary.less_info"|intl }}</a>\
				<div class="info hidden">\
					{% if known_extension %}\
						<div>\
							<span class="info-label">{{ "medialibrary.kind"|intl }}</span>\
							<span class="info-data">{{ known_extension|upper }} {{ "medialibrary.image"|intl }}</span>\
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
			\
			<div class="group hidden">\
				<div class="inp-locale">\
					<select name="locale">\
						{% set contexts = Supra.data.get("contexts") %}\
						{% set current_locale = null %}\
						{% for context in contexts %}\
							{% for locale in context.languages %}\
								{% if !current_locale %}\
									{% set current_locale = locale.id %}\
								{% endif %}\
								<option value="{{ locale.id }}">{{ locale.title|e }}</option>\
							{% endfor %}\
						{% endfor %}\
					</select>\
				</div>\
				<div class="inp-title" title="{{ "medialibrary.label_title"|intl }}">\
					<input type="text" name="title" value="{% if title && title[current_locale] %}{{ title[current_locale]|default("")|escape }}{% endif %}" />\
				</div>\
				<div class="inp-description" title="{{ "medialibrary.label_description"|intl }}">\
					<textarea name="description">{% if description && description[current_locale] %}{{ description[current_locale]|default("")|escape }}{% endif %}</textarea>\
				</div>\
				\
				<button type="button" class="done">{{ "buttons.done"|intl }}</button>\
			</div>\
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
	
	/**
	 * Constant, folder item template for image
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_IMAGE = Template.compile('\
		<li class="type-image {% if broken or !thumbnailUrl %}type-broken{% endif %}" data-id="{{ id }}">\
			<a>{% if !broken and thumbnailUrl %}<img src="{{ thumbnailUrl|escape }}?r={{ Math.random() }}" alt="" />{% endif %}</a>\
			<span>{{title|escape }}</span>\
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
			/*
			var slider = this.slider = new Y.Slider({
				'length': container.get('offsetWidth') - 16,	//16px margin
				'value': 1000,	//At the end
				'max': 1000,	//for better precision
				'thumbUrl': Y.config.base + '/slider-base/assets/skins/supra/thumb-x.png'
			});
			slider.render(container);
			*/
			
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
			content.delegate('click', this.handleCloseFolderClick, 'div.su-multiview-slide', this);
			
			//On item render set up form
			this.on('itemRender', this.handleItemRender, this);
			
			//On sort change redraw lists
			this.after('sortByChange', this.handleSortingChange, this);
			
			//On slide update slideshow
			/*
			this.slider.after('valueChange', this.syncScrollPosition, this);
			*/
			
			//After resize update slider width
			Y.on('resize', Y.throttle(Y.bind(this.updateScroll, this), 50), window);
			
			this.slideshow.on('slideChange', this.updateScroll, this);
			
			Extended.superclass.bindUI.apply(this, arguments);
		},
		
		updateScroll: function () {
			/*
			var w = this.get('boundingBox').get('offsetWidth');
			this.slider.set('length', w - 16);		//16px margin
			this.syncScrollPosition(w);
			*/
		},
		
		/**
		 * Sync scroll position
		 */
		syncScrollPosition: function (width) {
			/*
			var pos = this.slider.get('value'),
				content_width = this.slideshow.history.length * this.slideshow._getWidth(),
				container_width = typeof width == 'number' ? width : this.get('boundingBox').get('offsetWidth'),
				offset = 0;
			
			if (container_width < content_width) {
				offset = Math.round((content_width - container_width) * pos / 1000);
			}
			
			this.slideshow.get('contentBox').setStyle('left', - offset + 'px');
			*/
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
		 * 
		 */
		handleInfoToggleClick: function (event /* Event */) {
			var node = event.target.closest('.group');
			
			node.one('div.info').toggleClass('hidden');
			node.all('a.more, a.less').toggleClass('hidden');
			
			//Scrollbars
			var content = node.closest('.su-scrollable-content');
			content.fire('contentResize');
			
			event.halt();
		},
		
		/**
		 * Handle localize button click
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		handleLocalizeClick: function (event /* Event */) {
			var node = event.target.get('boundingBox').closest('.su-scrollable-content');
			
			node.all('div.group').toggleClass('hidden');
			
			//Scrollbars
			node.fire('contentResize');
		},
		
		/**
		 * Handle locale change
		 * 
		 * @param {Event} event Event
		 */
		handleLocaleChange: function (event /* Event */) {
			var item = this.getSelectedItem(),
				widgets = this.getPropertyWidgets(),
				
				title = Y.Lang.isObject(item.title) ? item.title[event.value] || '' : '',
				description = Y.Lang.isObject(item.description) ? item.description[event.value] || '' : '';
			
			widgets.title.set('value', title || '');
			widgets.description.set('value', description || '');
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
					btn_localize = new Supra.Button({ 'srcNode': buttons.filter('.localize').item(0) }),
					btn_download = new Supra.Button({ 'srcNode': buttons.filter('.download').item(0) }),
					btn_replace  = new Supra.Button({ 'srcNode': buttons.filter('.replace').item(0) }),
					btn_done     = new Supra.Button({ 'srcNode': buttons.filter('.done').item(0) });
				
				btn_localize.render();
				btn_download.render();
				btn_replace.render();
				btn_done.render();
				
				inp.btn_localize = btn_localize;
				inp.btn_download = btn_download;
				inp.btn_replace = btn_replace;
				inp.btn_done = btn_done;
				
				btn_download.on('click', this.handleDownloadClick, this);
				
				//Localization
				btn_localize.on('click', this.handleLocalizeClick, this);
				btn_done.on('click', this.handleLocalizeClick, this);
				
				//More / less
				node.all('a.more, a.less').on('click', this.handleInfoToggleClick, this);
				
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
					
					if (name != 'locale') {
						//Locale is not used for actual data, but only to switch locales
						obj.on('change', this.edit.onItemPropertyChange, this.edit, {'data': event.data, 'input': obj});
					}
					
					inp[item.get('name')] = obj;
				}, this);
				
				//Handle locale change
				inp.locale.after('change', this.handleLocaleChange, this);
				
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
	'supra.input',
	'supra.medialibrary-list',
	'supra.slideshow-multiview',
	'supra.medialibrary-list-edit',
	'supra.medialibrary-image-editor'
]});