//Invoke strict mode
"use strict";

SU('dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', function (Y) {

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
	new Action(Action.PluginContainer, {
		
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
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': Supra.data.get(['gallerymanager', 'properties'], DEFAULT_PROPERTIES)
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
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.applyChanges();
				}
			}]);
			
			this.bindDragDrop();
		},
		
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
				var target = evt.target.closest('LI');
				if (target) {
					this.showImageSettings(target);
				}
			}, this);
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
		
		/**
		 * Show image settings bar
		 */
		showImageSettings: function (target) {
			
			if (this.settings_form && this.settings_form.get('visible')) {
				this.settingsFormApply(true);
			}
			
			if (target.test('.gallery')) return false;
			
			var data = this.getImageDataFromNode(target);
			
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
					data = this.data;
				
				for(var i=0,ii=data.images.length; i<ii; i++) {
					if (data.images[i].image.id == image_data.image.id) {
						data.images[i] = image_data;
						break;
					}
				}
				
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
			
			//Add new items
			for(var i=0,ii=images.length; i<ii; i++) {
				src = null;
				if (images[i].image.sizes && preview_size in images[i].image.sizes) {
					src = images[i].image.sizes[preview_size].external_path;
				}
				
				if (src) {
					item = Y.Node.create('<li class="yui3-dd-drop gallery-item" data-id="' + images[i].image.id + '"><img src="' + src + '" alt="" /></li>');
					item.setData('imageId', images[i].image.id);
					list.append(item);
				} else {
					item = Y.Node.create('<li class="yui3-dd-drop gallery-item gallery-item-empty" data-id="' + images[i].image.id + '"></li>');
					item.setData('imageId', images[i].image.id);
					list.append(item);
				}
			}
			
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
		},
		
		getImageDataFromNode: function (node) {
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
		}
		
	});
	
});