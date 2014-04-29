YUI.add('supra.input-image', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-image';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'labelSet': {
			'value': '{#form.set_image#}'
		},
		'allowRemoveImage': {
			value: true,
			setter: "_setAllowRemoveImage"
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Button is used instead of input
		 */
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Image preview size
		 * @type {String}
		 * @private
		 */
		PREVIEW_SIZE: '200x200',
		
		/**
		 * Button node
		 * @type {Object}
		 * @private
		 */
		button: null,
		
		/**
		 * Right container action settings to it restore after link
		 * manager is closed
		 * @type {Object}
		 * @private
		 */
		restore_action: null,
		
		/**
		 * Image was selected last time media library was closed
		 * @type {Boolean}
		 * @private
		 */
		image_was_selected: false,
		
		/**
		 * Button to remove image
		 * @type {Object}
		 * @private
		 */
		button_remove: null,
		
		/**
		 * Opened media sidebar
		 * @type {Boolean}
		 * @private
		 */
		opened_media_sidebar: false,
		
		/**
		 * Value was changed using drag and drop
		 * while sidebar was opened
		 * @type {Boolean}
		 * @private
		 */
		drag_drop_value_changed: false,
		
		
		/**
		 * Open link manager for redirect
		 */
		openMediaSidebar: function () {
			var value = this.get('value'),
				path = value ? [].concat(value.path).concat(value.id) : 0;
			
			this.image_was_selected = false;
			
			//Save previous right layout container action to restore
			//it after 
			this.restore_action = null;
			if (Manager.Loader.isLoaded('LayoutRightContainer')) {
				
				var action_name = Manager.LayoutRightContainer.getActiveAction();
				if (action_name && Manager.Loader.isLoaded(action_name)) {
					var action = Manager.getAction(action_name);
					
					if (action_name == 'PageContentSettings') {
						this.restore_action = {
							'action': action,
							'args': [action.form, action.options]
						};
					} else if (action_name == 'PageSettings') {
						this.restore_action = {
							'action': action,
							'args': [true]
						};
					}
					
				}
			}
			
			Manager.executeAction('MediaSidebar', {
				'item': path,
				'dndEnabled': true,
				'onselect': Y.bind(this.onMediaSidebarImage, this),
				'onclose': Y.bind(this.onMediaSidebarClose, this)
			});
			
			this.opened_media_sidebar = true;
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onMediaSidebarImage: function (data) {
			this.set('value', data.image);
			
			this.image_was_selected = true;
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onMediaSidebarClose: function () {
			if (!this.image_was_selected && !this.drag_drop_value_changed) {
				this.set('value', '');
			}
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
			
			this.opened_media_sidebar = false;
			this.drag_drop_value_changed = false;
		},
		
		renderUI: function () {
			// Content and bounding boxes
			var contentBox = this.get('contentBox');
			
			if (contentBox.test('input')) {
				var className = this.getClassName('content');
				
				contentBox.removeClass(className);
				
				contentBox = Y.Node.create(this.CONTENT_TEMPLATE);
				contentBox.addClass(className);
				
				this.get('boundingBox').append(contentBox);
				this.set('contentBox', contentBox);
			}
			
			//Create button
			this.button = new Supra.Button({'label': this.get('labelSet')});
			this.button.render(contentBox);
			this.button.on('click', this.openMediaSidebar, this);
			
			//Remove button
			var button = this.button_remove = new Supra.Button({
				"label": Supra.Intl.get(["form", "block", "remove_image"]),
				"style": "small-red"
			});
			button.on("click", function () { this.set('value', null)}, this);
			button.addClass("su-button-fill");
			button.set("disabled", !this._hasImage());
			button.set("visible", this.get('allowRemoveImage'));
			button.render(this.get('boundingBox'));
			
			this.button.get('boundingBox').insert(button.get('boundingBox'), 'after');
			
			Input.superclass.renderUI.apply(this, arguments);
			
			this.set('value', this.get('value'));
			
			this.addDropListeners();
		},
		
		destructor: function () {
			this.removeDropListeners();
		},
		
		
		/* ------------------------------ Drag and drop from Media Library -------------------------------- */
		
		
		/**
		 * Add drop listeners
		 */
		addDropListeners: function () {
			if (!this.drop_target) {
				var node = this.get('boundingBox'),
					target = new Supra.DragDropTarget({'srcNode': node, 'doc': document});
				
				this.drop_target = target;
				
				node.on('dataDrop', function (e) {
					var image_id = e.drag_id;
					if (!image_id) return;
					
					//Only if dropped from gallery
					if (image_id.match(/^\d[a-z0-9]+$/i) && e.drop) {
						var dataObject = Manager.MediaSidebar.dataObject(),
							image_data = dataObject.cache.one(image_id);
						
						if (image_data.type != Supra.MediaLibraryList.TYPE_IMAGE) {
							//Only handling images; folders should be handled by gallery plugin 
							return false;
						}
						
						this.set('value', image_data);
						
						if (this.opened_media_sidebar) {
							this.drag_drop_value_changed = true;
							Manager.MediaSidebar.close();
						}
					}
				}, this);
			}
		},
		
		/**
		 * Remove drop listeners
		 */
		removeDropListeners: function () {
			if (this.drop_target) {
				this.drop_target.destroy();
				this.drop_target = null;
			}
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Returns true if image is selected, otherwise false
		 * 
		 * @return True if image is selected
		 * @type {Boolean}
		 * @private
		 */
		_hasImage: function () {
			var value = this.get("value");
			return value && value.image;
		},
		
		_setValue: function (data) {
			var title = '';
			
			if (!data || !data.id) {
				data = '';
				title = Supra.Intl.replace(this.get('labelSet'));
			} else {
				title = data.filename;
			}
			
			this.button.set('label', title);
			
			if (this.button_remove) {
				this.button_remove.set('disabled', !data || !data.id);
			}
			
			return data;
		},
		
		_getValue: function (data) {
			if (!data || !data.id) {
				return '';
			} else {
				return data;
			}
		},
		
		/**
		 * Return only ID, all other information is already known on server
		 * 
		 * @return Data which will be sent to server, image ID or empty string
		 * @type {String}
		 * @private
		 */
		_getSaveValue: function () {
			var value = this.get('value');
			return value ? value.id : '';
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		/**
		 * Allow removing image / allow having no image
		 * @param {Boolean} value Attribute value
		 * @return {Boolean} New attribute value
		 */
		_setAllowRemoveImage: function (value) {
			var button = this.button_remove;
			if (button) {
				button.set("visible", value);
			}
			return value;
		}
		
	});
	
	Supra.Input.Image = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.dd-drop-target']});