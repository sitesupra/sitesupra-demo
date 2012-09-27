YUI.add('supra.manager-action', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Manager = Supra.Manager,
		Loader = Manager.Loader;
	
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
			var action_info = Loader.getActionInfo(name);
		
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
			if (action.get('dataFolder') === null) {
				action.set('dataFolder', 'dataFolder' in object ? object.dataFolder : action_info.folder_data);
			}
			if (action.get('dataPath') === null) {
				action.set('dataPath', 'dataPath' in object ? object.dataPath : action_info.path_data);
			}
			
			action.set('actionPath', action_info.folder);
		
		/*
		 * When everything is loaded overwrite execute function
		 */
			function onLoadReady () {
				//Remove reference
				delete(action._beforeLoaded);
				
				//
				if (action.execute !== Supra.Manager.Action.Base.prototype.execute) {
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
				
				//Before execute do call plugins
				action.on('execute', action._postExecute, action);
				
				//On execute call original execute method
				action.on('execute', function (event) {
					return this._originalExecute.apply(this, event.details[0]);
				}, action);
				
				//Set loaded state
				delete(Loader.loading[name]);
				Loader.loaded[name] = true;
				
				//Run queued execute requests
				Manager.runExecutionQueue();
			}
			
			action._beforeLoaded = onLoadReady;
			
		/*
		 * Set dependancies
		 */
		if (object.DEPENDANCIES && object.DEPENDANCIES.length) {
			var dependancies = object.DEPENDANCIES,
				load_list = [];
			
			for(var i=0,ii=dependancies.length; i<ii; i++) {
				if (!Loader.isLoaded(dependancies[i])) {
					load_list.push(dependancies[i]);
				}
			}
			
			if (load_list.length) {
				if (!(name in Loader.dependancies)) Loader.dependancies[name] = [];
				Loader.dependancies[name] = load_list;
				Loader.loading[name].dependancies = true;
				
				Manager.loadActions(load_list);
			}
		}
		
		/*
		 * Action script is loaded,
		 * load template and stylesheet if needed
		 */
		Loader.loadExtras(name);
		
		return action;
	};
	
	Manager.Action = ManagerAction;
    
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});