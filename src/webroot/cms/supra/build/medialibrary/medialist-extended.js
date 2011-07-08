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
		List = Supra.MediaLibraryList;
	
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
	Extended.TEMPLATE_FILE = '\
		<div class="file">\
			<div class="preview">\
				<img src="/cms/supra/img/medialibrary/icon-file-large.png" alt="" />\
			</div>\
			<span class="inp-title" title="Title">\
				<input type="text" name="title" value="{title_escaped}" />\
			</span>\
			<span class="inp-description" title="Description">\
				<input type="text" name="description" value="{description_escaped}" />\
			</span>\
			<div class="localize"><button type="button">Localize</button></div>\
			<span class="inp-filename" title="Filename">\
				<input type="text" name="filename" value="{filename_escaped}" suValueMask="^[a-zA-Z0-9\\-\\_\\.]*$" />\
			</span>\
			<div class="center"><button type="button">Download</button></div>\
			<div class="center"><button type="button">Replace</button></div>\
		</div>';
	
	/**
	 * Constant, image template
	 * @type {String}
	 */
	Extended.TEMPLATE_IMAGE = '\
		<div class="image">\
			<div class="preview">\
				<img src="{previewUrl}" alt="" />\
			</div>\
			<span class="inp-title" title="Title">\
				<input type="text" name="title" value="{title_escaped}" />\
			</span>\
			<span class="inp-description" title="Description">\
				<input type="text" name="description" value="{description_escaped}" />\
			</span>\
			<div class="localize"><button type="button">Localize</button></div>\
			<span class="inp-filename" title="Filename">\
				<input type="text" name="filename" value="{filename_escaped}" suValueMask="^[a-zA-Z0-9\\-\\_\\.]*$" />\
			</span>\
			<div class="center"><button type="button">Download</button></div>\
			<div class="center"><button type="button">Replace</button></div>\
		</div>';
	
	/**
	 * Constant, folder item template for temporary file
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_TEMP = '\
		<li class="type-temp" data-id="{id}">\
			<span class="title">{title_escaped}</span>\
			<span class="progress"><em></em></span>\
		</li>';
	
	
	Extended.ATTRS = {
		/**
		 * Slideshow class
		 * @type {Function}
		 */
		'slideshowClass': {
			'value': Supra.MediaLibrarySlideshow
		},
		
		/**
		 * Sorting
		 * @type {String}
		 */
		'sortBy': {
			value: 'title'
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
		 * @type {Array}
		 * @private
		 */
		property_widgets: [],
		
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
				//Don't have an ID for this item yet, using -1
				var data = {
					id: -1,
					parent: parent_data.id,
					type: Supra.MediaLibraryData.TYPE_FOLDER,
					title: title || '',
					children_count: 0,
					children: []
				};
				
				//Add item to the folder list
				this.renderItem(data.parent, [data], true);
				this.get('dataObject').addData(data.parent, [data]);
				
				//Start editing
				var slide = this.slideshow.getSlide('slide_' + data.parent),
					node = slide.all('li.type-folder');
				
				node = node.item(node.size() - 1);
				
				this.renameFolder(node);
			}
			
			return this;
		},
		
		/**
		 * Add file
		 */
		addFile: function (parent, data) {
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
				//Don't have an ID for this item yet, generate random number
				var file_id = -(~~(Math.random() * 64000));
				
				//If there is no slide, then skip
				var slide = this.slideshow.getSlide('slide_' + parent_id);
				if (!slide) {
					return file_id;
				}
				
				var data = Supra.mix({
					id: file_id,
					parent: parent_id,
					type: Supra.MediaLibraryData.TYPE_TEMP,
					title: ''
				}, data || {});
				
				data[this.get('thumbnailSize') + '_url'] = null;
				
				//Add item to the file list
				this.renderItem(parent_id, [data], true);
				this.get('dataObject').addData(parent_id, [data]);
				
				return file_id;
			}
			
			return null;
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
				
				//Remove all data
				data_object.removeData(item_id, true);
				
				//If item is opened, then open parent and redraw list
				if (this.slideshow.isInHistory('slide_' + item_id)) {
					this.open(parent_id);
				}
				if (this.slideshow.getSlide('slide_' + parent_id)) {
					this.renderItem(parent_id);
				}
			}
			return this;
		},
		
		/**
		 * Returns currently selected folder
		 * 
		 * @return Selected folder data
		 * @type {Object}
		 */
		getSelectedFolder: function () {
			var history = this.slideshow.getHistory(),
				item_id = String(history[history.length - 1]).replace('slide_', ''),
				folder_data = this.getItemData(item_id);
			
			while(folder_data) {
				if (folder_data.type == Data.TYPE_FOLDER) return folder_data;
				folder_data = this.getItemData(folder_data.parent);
			}
			
			return null;
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
			content.delegate('click', this.handleRenameClick, 'ul.folder > li.type-folder', this);
			
			//On list click close folder
			content.delegate('click', this.handleCloseFolderClick, 'div.yui3-ml-slideshow-slide', this);
			
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
		 * Handle click on folder, show rename controls
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleRenameClick: function (event) {
			var target = event.target.closest('li.type-folder');
			
			if (!target || !target.hasClass('selected') || target.hasClass('renaming')) return;
			this.renameFolder(target);
			
			event.halt();
		},
		
		/**
		 * Rename folder
		 * 
		 * @param {Object} target Folder node
		 */
		renameFolder: function (target /* Folder node */) {
			var id = target.getData('itemId'),
				data = this.get('dataObject').getData(id) || {};
			
			//Create input
			var input = Y.Node.create('<input type="text" value="" />');
			input.setAttribute('value', data.title);
			
			target.one('span').insert(input, 'after');
			target.addClass('renaming');
			
			var string = new Supra.Input.String({
				'srcNode': input,
				'value': data.title
			});
			string.render();
			Y.Node.getDOMNode(input).focus();
			
			//On blur confirm changes
			input.on('blur', this.handleRenameComplete, this, {
				'data': data,
				'node': target,
				'object': string,
				'id': id
			});
		},
		
		/**
		 * Handle renaming confirm/cancel
		 * 
		 * @param {Object} event Event
		 * @param {Object} obj Item data
		 * @private
		 */
		handleRenameComplete: function (event /* Event */, obj /* Item data */) {
			var value = obj.object.get('value'),
				id = obj.id,
				post_data = null;
			
			if (obj.data.title != value && value) {
				obj.data.title = value;
				obj.node.one('span').set('innerHTML', Y.Lang.escapeHTML(value));
				
				post_data = {
					'title': value
				};
				
				if (obj.id == -1) {
					//For new item add also parent ID
					post_data.parent = obj.data.parent;
				}
				
				this.get('dataObject').saveData(obj.id, post_data, function (data, id) {
					if (id == -1) {
						//Update node itemId
						obj.node.setData('itemId', data);
					}
				});
			} else if (id == -1) {
				this.get('dataObject').removeData(obj.id, true);
				obj.node.remove();
				
				//Redraw parent
				this.renderItem(obj.data.parent);
			}
			
			obj.node.removeClass('renaming');
			obj.object.destroy();
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
			target = target.closest('div.yui3-ml-slideshow-slide');
			if (!target) return;
			
			var id = target.getData('itemId');
			if (!id && id !== 0) return;
			
			//Style element
			target.all('li').removeClass('selected');
			
			//Scroll to slide
			this.open(id);
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
				
				if (inp.length) {
					for(var i=0,ii=inp.length; i<ii; i++) {
						inp[i].destroy();
					}
					inp = [];
				}
				
				//Create buttons
				var buttons = node.all('button'),
					btn_localize = new Supra.Button({'srcNode': buttons.item(0), 'style': 'small'}),
					btn_download = new Supra.Button({'srcNode': buttons.item(1)}),
					btn_replace = new Supra.Button({'srcNode': buttons.item(2)});
				
				btn_localize.render();
				btn_download.render();
				btn_replace.render();
				inp.push(btn_localize);
				inp.push(btn_download);
				inp.push(btn_replace);
				
				//Create form
				node.all('input').each(function (item) {
					var props = {
						'srcNode': item,
						'useReplacement': true,
						'value': item.get('value')
					};
					
					var obj = new Supra.Input.String(props);
					obj.render();
					obj.on('change', this.onItemPropertyChange, this, {'data': event.data, 'input': obj});
					inp.push(obj);
				}, this);
				
				this.property_widgets = inp;
			}
		},
		
		/**
		 * When image or file property is changed save them
		 * 
		 * @param {Object} event Event
		 * @param {Object} data Item data
		 * @private
		 */
		onItemPropertyChange: function (event /* Event */, data /* Item data*/) {
			var name = data.input.getAttribute('name'),
				value = data.input.get('value');
			
			if (value && value != data.data[name]) {
				var data_object = this.get('dataObject'),
					item_data = data_object.getData(data.data.id),
					props = {};
				
				props[name] = item_data[name] = value;
				
				data_object.saveData(data.data.id, props);
				
				//Update title in folder list
				if (name == 'title') {
					var parent_id = this.getItemData(data.data.id).parent,
						li = this.slideshow.getSlide('slide_' + parent_id).one('li[data-id="' + data.data.id + '"]');
					
					li.one('span').set('innerHTML', Y.Lang.escapeHTML(value));
				}
			} else if (!value) {
				data.input.set('value', data.data[name]);
			}
		}
		
	}, {});
	
	
	Supra.MediaLibraryExtendedList = Extended;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['slider', 'supra.form', 'supra.medialibrary-list', 'supra.medialibrary-slideshow']});