//Invoke strict mode
"use strict";

Supra('dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', function (Y) {

	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Default properties if none is set in configuration
	var DEFAULT_PROPERTIES = [{
			'id': 'title',
			'type': 'String',
			'label': Supra.Intl.get(['gallerymanager', 'label_title']),
			'value': ''
	}];
	
	//Add as child, when EditorToolbar will be hidden GalleryManager will be hidden also (page editing is closed)
	Manager.getAction('EditorToolbar').addChildAction('GalleryManager');
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'GalleryManager',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Image preview size
		 * @type {String}
		 * @private
		 */
		PREVIEW_SIZE: '200x200',
		
		/**
		 * Gallery data
		 * @type {Object}
		 * @private
		 */
		data: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 * @private
		 */
		callback: null,
		
		/**
		 * Last drag X position
		 * @type {Number}
		 * @private
		 */
		lastDragX: 0,
		dragGoingUp: false,
		
		/**
		 * Settings form
		 * @type {Object}
		 * @private
		 */
		settings_form: null,
		
		/**
		 * Selected image data
		 * @type {Object}
		 * @private
		 */
		selected_image_data: null,
		
		/**
		 * Image inputs
		 * @type {Object}
		 * @private
		 */
		inputs: {},
		
		/**
		 * Settings form is changing values
		 * @type {Boolean}
		 * @private
		 */
		settings_form_changing: false,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			
			//On visibility change update container class and disable/enable toolbar
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.one().removeClass('hidden');
				} else {
					this.one().addClass('hidden');
					this.callback = null;
					this.data = null;
				}
				
				if (this.settings_form && this.settings_form.get('visible')) {
					Manager.PageContentSettings.hide();
				}
				Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
			}, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'gallery_manager_insert',
				'type': 'button',
				'title': Supra.Intl.get(['gallerymanager', 'insert']),
				'icon': '/cms/lib/supra/img/htmleditor/icon-image.png',
				'action': this,
				'actionFunction': 'openMediaLibrary'
			}]);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.applyChanges();
				}
			}]);
			
			//Bind inline editables
			this.one('.list').delegate('click', this.createInlineEditable, 'p.inline', this);
			
			this.bindDragDrop();
		},
		
		/**
		 * Returns image properties, these are not any specific image
		 * property values, but only properties
		 * 
		 * @return Image properties
		 * @private
		 */
		getImageProperties: function () {
			return Supra.data.get(['gallerymanager', 'properties'], DEFAULT_PROPERTIES);
		},
		
		/**
		 * Returns image property by ID or null
		 * 
		 * @param {String} id Property ID
		 * @return Property info
		 * @type {Object}
		 * @private
		 */
		getImageProperty: function (id) {
			var properties = this.getImageProperties(),
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (properties[i].id == id) return properties[i];
			}
			
			return null;
		},
		
		/**
		 * Returns image data by list node
		 * 
		 * @param {Object} node Y.Node instance
		 * @return Image data
		 * @type {Object}
		 * @private
		 */
		getImageDataByNode: function (node) {
			var data = this.data,
				image_data = null,
				image_id = node.getData('imageId');
			
			for (var i=0,ii=data.images.length; i<ii; i++) {
				if (data.images[i].image.id == image_id) {
					image_data = data.images[i];
					break;
				}
			}

			return image_data;
		},
		
		
		/*
		 * ---------------------------------- IMAGE SETTINGS FORM ------------------------------------
		 */
		
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': this.getImageProperties()
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
						
			//Delete button
			var btn = new Supra.Button({'label': Supra.Intl.get(['buttons', 'delete']), 'style': 'small-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('su-button-delete');
				btn.on('click', this.removeSelectedImage, this);
			
			this.settings_form = form;
			
			return form;
		},
		
		/**
		 * Remove selected image
		 */
		removeSelectedImage: function () {
			var images = this.data.images,
				selected = this.selected_image_data;
			
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i] === selected) {
					this.one('.list li[data-id="' + selected.id + '"]').remove();
					this.data.images.splice(i,1);
					this.settingsFormCancel();
					return this;
				}
			}
			
			Y.log('GalleryManager image which was supposed to be selected is not in image list');
		},
		
		/**
		 * Show image settings bar
		 */
		showImageSettings: function (target) {
			
			if (this.settings_form && this.settings_form.get('visible')) {
				this.settingsFormApply(true);
			}
			
			if (target.test('.gallery')) return false;
			
			var data = this.getImageDataByNode(target);
			
			if (!data) {
				Y.log('Missing image data for image ' + target.getAttribute('src'), 'debug');
				return false;
			}
			
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createSettingsForm(),
				action = Manager.getAction('PageContentSettings');
			
			if (!form) {
				if (action.get('loaded')) {
					if (!action.get('created')) {
						action.renderAction();
						this.showImageSettings(target);
					}
				} else {
					action.once('loaded', function () {
						this.showImageSettings(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			action.execute(form, {
				'doneCallback': Y.bind(this.settingsFormApply, this),
				
				'title': Supra.Intl.get(['htmleditor', 'image_properties']),
				'scrollable': true
			});
			
			this.selected_image_data = data;

			this.settings_form.resetValues()
							  .setValues(data, 'id');
			
			return true;
		},
		
		/**
		 * Hide properties form
		 */
		settingsFormApply: function (dont_hide) {
			if (this.settings_form.get('visible')) {
				var image_data = Supra.mix(this.selected_image_data, this.settings_form.getValuesObject('id')),
					data = this.data,
					inputs = this.inputs,
					
					properties = this.getImageProperties(),
					node_item = null,
					node_label = null,
					label = null;
				
				this.settings_form_changing = true;
				
				for (var i=0,ii=data.images.length; i<ii; i++) {
					if (data.images[i].image.id == image_data.image.id) {
						data.images[i] = image_data;
						
						inputs = inputs[image_data.image.id];
						if (inputs) {
							for (var key in inputs) {
								inputs[key].set('value', image_data[key]);
								inputs[key].fire('blur');
							}
						}
						
						//Set <p class="inline" /> text
						node_item = this.one('ul.list li[data-id="' + image_data.image.id + '"]');
						if (node_item) {
							for (var p=0, pp=properties.length; p<pp; p++) {
								if (properties[p].type == 'String') {
									node_label = node_item.one('p.inline.' + properties[p].id);
									if (node_label) {
										if (image_data[properties[p].id]) {
											node_label.removeClass('empty').set('text', image_data[properties[p].id]);
										} else {
											label = Supra.Intl.get(['gallerymanager', 'click_here']).replace('{label}', properties[p].label.toLowerCase()),
											node_label.addClass('empty').set('text', label);
										}
									}
								}
							}
						}
						
						break;
					}
				}
				
				this.settings_form_changing = false;
				
				if (dont_hide !== true) {
					this.settingsFormCancel();
				}
			}
		},
		
		settingsFormCancel: function () {
			if (this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
				this.selected_image_data = null;
			}
		},
		
		
		/*
		 * ---------------------------------- DRAG AND DROP ------------------------------------
		 */
		
		
		/**
		 * Bind drag & drop event listeners
		 */
		bindDragDrop: function () {
			//Initialize drag and drop
			Manager.PageContent.initDD();
			
			var fnDragDrag = Y.bind(this.onDragDrag, this),
				fnDragStart = Y.bind(this.onDragStart, this),
				fnDropOver = Y.bind(this.onDropOver, this);
			
			var del = this.dragDelegate = new Y.DD.Delegate({
				container: '#galleryManagerList',
				nodes: 'li',
				target: {},
				dragConfig: {
					haltDown: false
				}
			});
			del.dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});

			del.on('drag:drag', fnDragDrag);
			del.on('drag:start', fnDragStart);
			del.on('drag:over', fnDropOver);
			
			//On list click check if actually item was clicked
			Y.one('#galleryManagerList').on('click', function (evt) {
				var ignore = evt.target.closest('p'),
					target = evt.target.closest('LI');
				
				if (!ignore && target) {
					this.showImageSettings(target);
				}
			}, this);
			
			//Drop from media library, add image or images
			var srcNode = this.one();
			srcNode.on('dataDrop', this.onImageDrop, this);
			
			//Enable drag & drop
			this.drop = new Manager.PageContent.PluginDropTarget({
				'srcNode': srcNode,
				'doc': document
			});
		},
		
		/**
		 * On image or folder drop add images to the list
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onImageDrop: function (e) {
			var item_id = e.drag_id,
				item_data = Manager.MediaSidebar.getData(item_id),
				image = null,
				dataObject = Manager.MediaSidebar.medialist.get('dataObject');
			
			if (item_data.type == Supra.MediaLibraryData.TYPE_IMAGE) {
				
				//Add single image
				if (item_data.sizes) {
					this.addImage(item_data);
				} else {
					dataObject.once('load:complete:' + item_data.id, function(event) {
						if (event.data) {
							this.onImageDrop(e);
						}
					}, this);
				}
				
			} else if (item_data.type == Supra.MediaLibraryData.TYPE_FOLDER) {
				
				if ( ! dataObject.hasData(item_data.id) 
					|| (item_data.children && item_data.children.length != item_data.children_count)) {
					dataObject.once('load:complete:' + item_data.id, function(event) {
						if (event.data) {
							this.onImageDrop(e);
						}
					}, this);
					
					return;
					
				} else {
					
					var folderHasImages = false;

					//Add all images from folder
					for(var i in item_data.children) {
						image = item_data.children[i];
						if (image.type == Supra.MediaLibraryData.TYPE_IMAGE) {
							this.addImage(item_data.children[i]);
							folderHasImages = true;
						}
					}

					//folder was without images
					if ( ! folderHasImages) {
						Supra.Manager.executeAction('Confirmation', {
							'message': '{#medialibrary.validation_error.empty_folder_drop#}',
							'useMask': true,
							'buttons': [
								{'id': 'delete', 'label': 'Ok'}
							]
						});

						return;
					}
				}
			}
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			return false;
		},
		
		/**
		 * Handle drag:drag event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragDrag: function (evt) {
			var x = evt.target.lastXY[0];
			
			this.dragGoingUp = (x < this.lastDragX);
		    this.lastDragX = x;
		},
		
		/**
		 * Handle drag:start event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragStart: function (evt) {
			//Get our drag object
	        var drag = evt.target;
			
	        //Set some styles here
	        drag.get('dragNode').addClass('gallery-item-proxy');
		},
		
		/**
		 * Handle drop:over event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDropOver: function (evt) {
			//Get a reference to our drag and drop nodes
		    var drag = evt.drag.get('node'),
		        drop = evt.drop.get('node');
			
		    //Are we dropping on a li node?
		    if (drop.get('tagName').toLowerCase() === 'li' && drop.hasClass('gallery-item')) {
			    //Are we not going up?
		        if (!this.dragGoingUp) {
		            drop = drop.get('nextSibling');
		        }
				if (!this.dragGoingUp && !drop) {
			        evt.drop.get('node').get('parentNode').append(drag);
				} else {
			        evt.drop.get('node').get('parentNode').insertBefore(drag, drop);
				}
				
		        //Resize this nodes shim, so we can drop on it later.
		        evt.drop.sizeShim();
		    }
		},
		
		
		/*
		 * ---------------------------------- INLINE EDITABLE ------------------------------------
		 */
		
		
		/**
		 * When p.inline is clicked replace it with inline editable input
		 * 
		 * @private
		 */
		createInlineEditable: function (e) {
			var node = e.target.closest('p'),
				data = this.getImageDataByNode(node.closest('LI')),
				image_id = node.getAttribute('data-image-id'),
				property_id = node.getAttribute('data-property-id'),
				property = this.getImageProperty(property_id),
				label = Supra.Intl.get(['gallerymanager', 'click_here']).replace('{label}', property.label.toLowerCase()),
				
				input = new Supra.Input.String({
					'useReplacement': true,
					'value': data[property_id]
				});
			
			//Save input
			this.inputs[image_id] = this.inputs[image_id] || {};
			this.inputs[image_id][property_id] = input;
			
			//On blur set default label if value is empty
			input.on('blur', function () {
				var value = this.get('value');
				
				if (!value) {
					this.get('replacementNode').set('text', label).addClass('empty');
				} else {
					this.get('replacementNode').removeClass('empty');
				}
			});
			
			//On change update data
			input.on('change', function () {
				if (!this.settings_form_changing) {
					var images = this.data.images,
						i = 0,
						ii = images.length;
					
					for (; i<ii; i++) {
						if (images[i].id == image_id) {
							images[i][property_id] = input.get('value');
						}
					}
				}
			}, this);
			
			node.set('text', '');
			node.removeClass('inline');
			input.render(node);
			input._onFocus();
			
			if (!data[property_id]) {
				input.get('replacementNode').set('text', label).addClass('empty');
			}
			
			e.halt();
		},
		
		
		/*
		 * ---------------------------------- EXTERNAL INTERFACES ------------------------------------
		 */
		
		
		/**
		 * Open media library sidebar
		 * @private
		 */
		openMediaLibrary: function () {
			Manager.getAction('MediaSidebar').execute({
				'onselect': Y.bind(function (event) {
					this.addImage(event.image);
				}, this),
				'onclose': Y.bind(function () {
					
				}, this)
			});
			
		},
		
		
		/*
		 * ---------------------------------- IMAGE LIST ------------------------------------
		 */
		
		
		/**
		 * Render image list
		 * 
		 * @param {Object} data Data
		 * @private
		 */
		renderData: function () {
			var list = this.one('.list'),
				images = this.data.images,
				preview_size = this.PREVIEW_SIZE,
				src,
				item;
			
			//Remove old data
			list.all('LI').remove();
			
			//Remove old inputs
			var inputs = this.inputs,
				key = null,
				name = null;
			
			if (inputs) {
				for (key in inputs) {
					for (name in inputs[key]) {
						inputs[key][name].destroy();
					}
				}
			}
			
			this.inputs = {};
			
			//Add new items
			for(var i=0,ii=images.length; i<ii; i++) {
				this.renderItem(images[i]);
			}
			
			this.dragDelegate.syncTargets();
		},
		
		/**
		 * Render image item
		 * 
		 * @param {Object} data Image data
		 * @private
		 */
		renderItem: function (data) {
			var src = null,
				preview_size = this.PREVIEW_SIZE,
				list = this.one('.list'),
				item = null,
				properties = this.getImageProperties(),
				html = '',
				label = Supra.Intl.get(['gallerymanager', 'click_here']),
				value = null;
			
			if (data.image.sizes && preview_size in data.image.sizes) {
				src = data.image.sizes[preview_size].external_path;
			}
			
			//HTML for inline editable inputs
			for (var i=0, ii=properties.length; i<ii; i++) {
				if (properties[i].type == 'String') {
					if (data[properties[i].id]) {
						html += '<p class="inline ' + properties[i].id + '" data-property-id="' + properties[i].id + '" data-image-id="' + data.image.id + '">' + Y.Escape.html(data[properties[i].id]) + '<p>';
					} else {
						value = label.replace('{label}', properties[i].label.toLowerCase());
						html += '<p class="inline empty ' + properties[i].id + '" data-property-id="' + properties[i].id + '" data-image-id="' + data.image.id + '">' + value + '<p>';
					}
				}
			}
			
			if (src) {
				item = Y.Node.create('<li class="yui3-dd-drop gallery-item" data-id="' + data.image.id + '"><span><img src="' + src + '" alt="" />' + html + '</li>');
				item.setData('imageId', data.image.id);
				list.append(item);
			} else {
				item = Y.Node.create('<li class="yui3-dd-drop gallery-item gallery-item-empty" data-id="' + data.image.id + '">' + html + '</li>');
				item.setData('imageId', data.image.id);
				list.append(item);
			}
		},
		
		/**
		 * Add image
		 * 
		 * @param {Object} image_data Image data
		 * @private
		 */
		addImage: function (image_data) {
			var images = this.data.images,
				properties = this.getImageProperties(),
				property = null,
				image  = {'image': image_data, 'id': image_data.id};
			
			//Check if image doesn't exist in data already
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i].image.id == image_data.id) return;
			}
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				property = properties[i].id;
				image[property] = image_data[property] || properties[i].value || '';
			}
			
			image.title = image_data.filename;
			
			images.push(image);
			this.renderItem(image);
			
			this.dragDelegate.syncTargets();
		},
		
		/**
		 * Apply changes
		 * 
		 * @private
		 */
		applyChanges: function () {
			var items = this.all('li'),
				order = {},
				data = this.data,
				images = [];
			
			//Get image order
			if (items.size()) {
				items.each(function (item, index) {
					order[this.getData('imageId')] = index;
				});
			}
			
			//Sort images array
			data.images.sort(function (a, b) {
				var oa = order[a.image.id],
					ob = order[b.image.id];
				
				return oa > ob;
			});
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			if (this.callback) {
				this.callback(data, true);
			}
			
			this.hide();
		},
		
		/**
		 * Cancel changes
		 * 
		 * @private
		 */
		cancelChanges: function () {
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			if (this.callback) {
				this.callback(this.data, false);
			}
			
			this.hide();
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Gallery data
		 * @param {Function} callback Callback function
		 */
		execute: function (data, callback) {
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			this.data = data;
			this.callback = callback;
			this.renderData();
			this.show();
		}
		
	});
	
});