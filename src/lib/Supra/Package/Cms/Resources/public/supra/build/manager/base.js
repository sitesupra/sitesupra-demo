YUI.add('supra.manager-base', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Class for managing Actions, loading and executing them
	 *
	 * @class ManagerHost
	 */
	function ManagerHost () {
		ManagerHost.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	ManagerHost.NAME = 'ManagerHost';

        /**
         * Manage Supra Action loading, execution
         */
        Supra.Manager = {

		/**
		 * List of all actions which are loaded
		 * @type {Object}
                 * @private
		 */
		actions: {},

		/**
		 * List of all actions which are not loaded and
		 * have temporary objects
		 * @type {Object}
                 * @private
		 */
		temporaries: {},

		/**
		 * Queued actions
		 * @type {Array}
                 * @private
		 */
		executionQueue: [],

		/**
		 * Manager container node
		 * @type {Object}
                 * @private
		 */
		containerNode: null,

		/**
		 * Returns action object by name
		 * If action isn't loaded yet, then returns temporary
		 * action object
		 *
		 * @alias Supra.action
		 * @param {String} action_name
		 * @return Action object
		 * @type {ActionBase Object}
		 */
		getAction: function (action_name) {
			if (typeof action_name === 'object' && action_name.isInstanceOf) {
				return action_name;
			} else if (action_name in this.actions) {
				return this.actions[action_name];
			} else if (!(action_name in this.temporaries)) {
				this.temporaries[action_name] = new Supra.Manager.Action.Base(action_name);
			}

			return this.temporaries[action_name];
		},

		/**
		 * Load action
		 *
		 * @param {String} action_name
		 * @return True if action started loading, false if it is already loading or loaded
		 * @type {Boolean}
		 */
		loadAction: function (action_name) {
			return Supra.Manager.Loader.loadAction(action_name);
		},
		
		/**
		 * Load list of actions
		 *
		 * @param {Array} action_names
		 * @type {Boolean}
		 */
		loadActions: function (action_names) {
			return Supra.Manager.Loader.loadActions(action_names);
		},

		/**
		 * Add action to execution queue
		 *
		 * @param {String} action_name
		 * @param {Array} args
		 * @private
		 */
		addActionToQueue: function (action_name, args) {
			var args = args || [];
			
			this.executionQueue.push({
				"action_name": action_name,
				"args": args
			});
		},

		/**
		 * Run queued actions which are ready now
		 */
		runExecutionQueue: function ()  {
			var queue = this.executionQueue;
			var index = 0;
			
			while(index < queue.length) {
				var exec_info = queue[index];
				var action_name = exec_info.action_name;

				if (action_name in this.actions) {
					var action = this.actions[action_name];
					
					//Only if loaded
					if (action.get('loaded')) {
						//Remove item from queue
						this.executionQueue = queue = queue.slice(0,index).concat(queue.slice(index+1));
						
						//Execute
						if (Supra.data.get('catchNativeErrors')) {
							try {
								action.execute.apply(action, exec_info.args);
							} catch (e) {
								Y.error(e);
							}
						} else {
							action.execute.apply(action, exec_info.args);
						}
						
						//Executing an action could change executionQueue array, reset it
						index = 0;
						queue = this.executionQueue;
					} else {
						index++;
					}
				} else {
					return;
				}
			}
		},

		/**
		 * Execute action
		 *
		 * @alias Supra.exec
		 * @param {String} action_name
		 */
		executeAction: function (action_name) {
			var args = [].slice.call(arguments, 1);
			var Loader = Supra.Manager.Loader;
			
			if (action_name in this.actions) {
				if (this.actions[action_name].isLoaded()) {
					
					//Execute
					if (Supra.data.get('catchNativeErrors')) {
						try {
							var action = this.actions[action_name];
							action.execute.apply(action, args);
						} catch (e) {
							Y.error(e);
						}
					} else {
						var action = this.actions[action_name];
						action.execute.apply(action, args);
					}
					
				} else {
					//If not loaded then add to queue
					this.addActionToQueue(action_name, args);
				}
				return true;
			} else if (!Loader.isLoaded(action_name)) {
				this.addActionToQueue(action_name, args);

				if (!Loader.isLoading(action_name)) {
					Loader.loadAction(action_name);
				}

				return false;

			} else {
				this.addActionToQueue(action_name, args);
				
				//Action is loaded, but object wasn't created
				Y.log('Action ' + action_name + ' was loaded, but action object wasn\'t found', 'error');
			}
		},
		
		/**
		 * Destroy action
		 * 
		 * @param {String} action_name
		 */
		destroyAction: function (action_name) {
			if (action_name in this) {
				this[action_name].destroy();
				delete(this[action_name]);
				delete(this.actions[action_name]);
				Supra.Manager.Loader.destroyAction(action_name);
			}
		},

		/**
		 * Returns root container node for actions
		 *
		 * @return Container node
		 * @type {Object}
		 */
		getContainerNode: function () {
			if (!this.containerNode) this.containerNode = Y.one('#cmsContent');
			return this.containerNode;
		}

	};

	Y.extend(ManagerHost, Y.Base, Supra.Manager);
	
	Supra.Manager = new ManagerHost();
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});