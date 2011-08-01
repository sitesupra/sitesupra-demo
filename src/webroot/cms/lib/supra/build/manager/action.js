//Invoke strict mode
"use strict";

YUI.add('supra.manager-action', function (Y) {
	
	var Manager = Supra.Manager;
	
	/**
	 * Create new action
	 * Last argument is action object
	 * 
	 */
	function ManagerAction () {
		var args = [].slice.call(arguments, 0);
		var object = args.pop();
		var plugins = args;
		
		/*
		 * If not an object or NAME property is missing, then throw error
		 */
			if (!Y.Lang.isObject(object)) {
				Y.log('Action() last parameter must be an object (action body)', 'error');
				return;
			}
			if (!('NAME' in object)) {
				Y.log('Action is missing NAME property', 'error');
				return;
			}
		
		/*
		 * Get action info
			 */
			var name = object.NAME;
			var action = Manager.getAction(name);
			var action_info = Manager.Loader.getActionInfo(name);
		
		/*
		 * Change placeholder if it's defined in Action
		 */
			if ('PLACE_HOLDER' in object && object.PLACE_HOLDER) {
				action.set('placeHolderNode', object.PLACE_HOLDER);
			}
		
		/*
		 * Set stylesheet/template
		 */
			if ('HAS_STYLESHEET' in object && object.HAS_STYLESHEET !== null) {
				action.set('hasStylesheet', !!object.HAS_STYLESHEET);
			}
			if ('HAS_TEMPLATE' in object && object.HAS_TEMPLATE !== null) {
				action.set('hasTemplate', !!object.HAS_TEMPLATE);
			}
		
		/*
		 * Extend with properties, etc.
		 */
			action = Supra.mix(action, object, {
				'plugins': new Manager.Action.PluginManager(action, plugins)
			});
			
			if (action.get('templatePath') === null) {
				action.set('templatePath', 'templatePath' in object ? object.templatePath : action_info.path_template);
			}
			if (action.get('stylesheetPath') === null) {
				action.set('stylesheetPath', 'stylesheetPath' in object ? object.stylesheetPath : action_info.path_stylesheet);
			}
			if (action.get('dataPath') === null) {
				action.set('dataPath', 'dataPath' in object ? object.dataPath : action_info.path_data);
			}
			
			action.set('actionPath', action_info.folder);
		
		/*
		 * Overwrite execute function
		 */
			if (action.execute !== SU.Manager.Action.Base.prototype.execute) {
				action._originalExecute = action.execute;
			} else {
				action._originalExecute = function () {};
			}
			action.execute = action._execute;
			
			delete(Manager.temporaries[name]);
			Manager.actions[name] = action;
			Manager[name] = action;
			
			//On first 'execute' make sure Action is initialized
			action.once('execute', action._preExecute, action);
			
			//After execute do call plugins
			action.before('execute', action._postExecute, action);
			
			//On execute call original execute method
			action.on('execute', function (event) {
				return this._originalExecute.apply(this, event.details[0]);
			}, action);
		
		
		/*
		 * Action script is loaded,
		 * load template and stylesheet if needed
		 */
		action._loadTemplate();
		
		return action;
	};
	
	Manager.Action = ManagerAction;
    
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});