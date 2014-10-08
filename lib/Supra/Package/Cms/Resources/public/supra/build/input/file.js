YUI.add('supra.input-file', function (Y) {
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
	
	Input.NAME = 'input-file';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'labelSet': {
			'value': '{#form.set_file#}'
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Button is used instead of input
		 */
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
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
		 * File was selected last time media library was closed
		 * @type {Boolean}
		 * @private
		 */
		file_was_selected: false,
		
		/**
		 * Open link manager for redirect
		 */
		openMediaSidebar: function () {
			var value = this.get('value'),
				path = value ? [].concat(value.path).concat(value.id) : 0;
			
			this.file_was_selected = false;
			
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
				'displayType': 0, // 0 == all
				'onselect': Y.bind(this.onMediaSidebarFile, this),
				'onclose': Y.bind(this.onMediaSidebarClose, this)
			});
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onMediaSidebarFile: function (data) {
			this.set('value', data.image);
			
			this.file_was_selected = true;
			
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
			if (!this.file_was_selected) {
				this.set('value', '');
			}
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		renderUI: function () {
			//Create button
			this.button = new Supra.Button({'label': this.get('labelSet')});
			this.button.render(this.get('contentBox'));
			this.button.on('click', this.openMediaSidebar, this);
			
			Input.superclass.renderUI.apply(this, arguments);
			
			//Insert button before input
			this.get('inputNode') .insert(this.button.get('boundingBox'), 'before');
			
			this.set('value', this.get('value'));
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
		}
		
	});
	
	Supra.Input.File = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});