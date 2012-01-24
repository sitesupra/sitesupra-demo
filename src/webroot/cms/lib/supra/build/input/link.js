//Invoke strict mode
"use strict";

YUI.add('supra.input-link', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-link';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'label_set': {
			'value': '{#form.set_link#}'
		},
		'mode': {
			'value': 'link'
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
		 * Open link manager for redirect
		 */
		openLinkManager: function () {
			var value = this.get('value');
			
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
			
			Manager.executeAction('LinkManager', value, {
				'mode': this.get('mode'),
				'hideToolbar': true
			}, this.onLinkManagerClose, this);
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onLinkManagerClose: function (data) {
			this.set('value', data);
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		renderUI: function () {
			//Create button
			this.button = new Supra.Button({'label': this.get('label_set')});
			this.button.render(this.get('contentBox'));
			this.button.on('click', this.openLinkManager, this);
			
			Input.superclass.renderUI.apply(this, arguments);
			
			this.set('value', this.get('value'));
		},
		
		_setValue: function (data) {
			if (!data || (!data.href && !data.page_id && !data.file_id)) {
				data = '';
			}
			
			var title = (data && data.href ? data.title || data.href : SU.Intl.replace(this.get('label_set')));
			this.button.set('label', title);
			
			return data;
		},
		
		_getValue: function (data) {
			if (!data || (!data.href && !data.page_id && !data.file_id)) {
				return '';
			} else {
				return data;
			}
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		}
		
	});
	
	Supra.Input.Link = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});