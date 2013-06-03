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
		'label_set': {
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
				'dndEnabled': false,
				'onselect': Y.bind(this.onMediaSidebarImage, this),
				'onclose': Y.bind(this.onMediaSidebarClose, this)
			});
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
			if (!this.image_was_selected) {
				this.set('value', '');
			}
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		renderUI: function () {
			//Create button
			this.button = new Supra.Button({'label': this.get('label_set')});
			this.button.render(this.get('contentBox'));
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
				title = Supra.Intl.replace(this.get('label_set'));
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
	
}, YUI.version, {requires:['supra.input-proto']});