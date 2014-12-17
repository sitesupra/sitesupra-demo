/**
 * Main action, handles all Crud instances
 */
Supra('supra.crud', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Manager.Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Crud',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Header', 'PageToolbar', 'PageButtons'],
		
		/**
		 * Template
		 * @type {String}
		 */
		template: '<div class="ui-light ui-light-background"></div>',
		
		/**
		 * List of all instances
		 * @type {Object}
		 */
		instances: {},
		
		/**
		 * List of instance IDs in which they were opened
		 * @type {Array}
		 */
		instances_stack: [],
		
		/**
		 * Active instance
		 * @type {Object}
		 */
		instance: null,
		
		/**
		 * Instance onclose callbacks
		 * @type {Object}
		 */
		callbacks: {},
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
		},
		
		/**
		 * Handle Crud close event
		 *
		 * @private
		 */
		onclose: function () {
			var id,
				instance = this.instance,
				id = instance.get('providerId'),
				callbacks = this.callbacks[id],
				i = 0,
				ii = 0;
			
			this.instance = null;
			this.instances_stack.pop();
			delete(this.instances[id]);
			instance.destroy();
			
			if (callbacks) {
				delete(this.callbacks[id]);
				
				for (ii=callbacks.length; i<ii; i++) {
					callbacks[i]();
				}
			}
			
			// Open previous instance
			id = this.instances_stack[this.instances_stack.length - 1];
			
			if (id) {
				this.instance = instance = this.instances[id];
				instance.show();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function (_configuration) {
			// Check arguments
			var type = typeof _configuration,
				id,
				configuration;
			
			if (type === 'string') {
				configuration = {'providerId': _configuration};
				id = _configuration;
			} else if (_configuration && type === 'object' && 'providerId' in _configuration) {
				configuration = _configuration;
				id = _configuration.providerId;
			}
			
			if (!id) {
				throw new Error("Crud manager ID not specified while calling Crud action");	
			}
			
			// Save 'onclose' callbacks
			if (!this.callbacks[id]) this.callbacks[id] = [];
			if (configuration && typeof configuration.onclose === 'function') {
				this.callbacks[id].push(configuration.onclose);
			}
			
			// Check for loop, one manager instance can't be open more than once at a time
			if (id in this.instances) {
				if (this.instance.get('providerId') !== id) {
					// Already was opened, shouldn't open 2 instances of same manager
					throw new Error("Crud manager tried openeing \"' + configuration.providerId + '\" while it is already opened");
				} else {
					// It's already open, skip
					return;
				}
			}
			
			if (this.instance) {
				this.instance.hide();
			}
			
			this.instance = new Supra.Crud.Base(configuration);
			this.instances[id] = this.instance;
			this.instances_stack.push(id);
			
			this.instance.on('close', this.onclose, this);
			this.instance.render(this.getContainer());
		}
	});
	
});
