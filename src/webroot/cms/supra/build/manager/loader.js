//Invoke strict mode
"use strict";

YUI.add('supra.manager-loader', function (Y) {
	
	var Manager = Supra.Manager;
	
	/*
	 * Manager loader loads action JS / HTML / CSS
	 */
	Manager.Loader = {
		
		//Extensions
		EXTENSION_SCRIPT: '.js',
		EXTENSION_TEMPLATE: '.html',
		EXTENSION_STYLE: '.css',
		EXTENSION_DATA: '.json',
		
		/*
		 * Path to current manager
		 */
		base: null,
		
		/*
		 * Paths to external managers
		 */
		paths: {},
		
		/*
		 * List of loaded actions
		 */
		loaded: {},
		
		/*
		 * List of actions which are currently loading
		 */
		loading: {},
		
		/*
		 * Action info cache
		 */
		action_info_cache: {},
		
		/*
		 * Action template cache
		 */
		template_cache: {},
		
		/*
		 * List of templates which are loading
		 */
		template_loading: {},
		
		/**
		 * Set manager base path
		 * @param {Object} path
		 */
		setBasePath: function (path) {
			//Remove trailing slash from folder
			this.base = String(path).replace(/\/+$/, '');
		},
		
		/**
		 * Get manager base path
		 */
		getBasePath: function () {
			return this.base;
		},
		
		/**
		 * Returns if action is loaded
		 * 
		 * @param {String} action_name Action name
		 * @return True if action is in loaded action list or in manager
		 * @type {Boolean}
		 */
		isLoaded: function (action_name) {
			return action_name in this.loaded ||
				   action_name in Manager;
		},
		
		/**
		 * Returns if action is loaded
		 * 
		 * @param {String} action_name Action name
		 * @return True if action is in loading action list
		 * @type {Boolean}
		 */
		isLoading: function (action_name) {
			return action_name in this.loading;
		},
		
		/**
		 * Remove all information about action
		 * 
		 * @param {String} action_name
		 */
		destroyAction: function (action_name) {
			var info = this.getActionInfo(action_name);
			
			delete(this.paths[action_name]);
			delete(this.loading[action_name]);
			delete(this.loaded[action_name]);
			delete(this.template_cache[info.path_template]);
			delete(this.template_loading[info.path_template]);
			delete(this.action_info_cache[action_name]);
			
		},
		
		/**
		 * Load action
		 * 
		 * @param {String} action_name
		 * @return True if action started loading, false if it is already loading or loaded
		 * @type {Boolean}
		 */
		loadAction: function (action_name) {
			if (this.isLoaded(action_name) || this.isLoading(action_name)) return false;
			
			var info = this.getActionInfo(action_name);
			this.loading[action_name] = true;
			
			//Get SCRIPT
			Y.Get.script(info.path_script, {
				onSuccess: function (o) {
					delete(this.loading[o.data]);
					this.loaded[o.data] = true;
				},
				context: this,
				data: action_name
			});
			
			return true;
		},
		
		/**
		 * Loads action template
		 * @param {Object} path
		 * @param {Object} callback
		 */
		loadTemplate: function (action_name, callback) {
			var action = Manager.getAction(action_name);
			
			var templatePath = action.getTemplatePath();
			var hasTemplate = action.getHasTemplate();
			
			//Prevent multiple requests for same action
			if (templatePath && hasTemplate && this.template_loading[templatePath]) {
				return;
			}
			
			var stylesheetPath = action.getStylesheetPath();
			var hasStylesheet = action.getHasStylesheet();
			var stylesheetLoaded = true;
			var templateLoaded = !templatePath || !hasTemplate || (templatePath in this.template_cache) ? true : false;
			var template = '';
			
			//Load stylesheet if needed
			if (stylesheetPath && hasStylesheet) {
				stylesheetLoaded = false;
				Y.Get.css(stylesheetPath, {
					'onSuccess': function () {
						stylesheetLoaded = true;
						
						//If template request already completed, call callback
						if (templateLoaded) callback(template);
					}
				});
			}
			
			//Load template if needed
			if (templatePath && hasTemplate) {
				var cache = this.template_cache;
				var loading = this.template_loading;
				
				if (!(templatePath in cache)) {

					loading[templatePath] = true;
					templateLoaded = false;
					
					Supra.io(templatePath, {
						'type': 'html',
						'on': {
							'success': Y.bind(function (o, html) {
								templateLoaded = true;
								delete(loading[templatePath]);
								cache[templatePath] = html;
								template = html;
								
								//If stylesheet request already completed, call callback
								if (stylesheetLoaded) callback(html);
							}, this),
							'failure': Y.bind(function () {
								//@TODO Handle failure
								templateLoaded = true;
								delete(loading[templatePath]);
								cache[templatePath] = template;
								
								//If stylesheet request already completed, call callback
								if (stylesheetLoaded) callback(template);
							}, this)
						}
					});
				} else {
					template = cache[templatePath];
				}
			}
			
			if (templateLoaded && stylesheetLoaded) {
				//Action doesn't have stylesheet and template
				callback(template);
			}
		},
		
		/**
		 * Returns information about action
		 * 
		 * @param {String} action_name Action name
		 * @return Info about where action files can be found 
		 * @type {Object}
		 */
		getActionInfo: function (action_name /* Action name */) {
			if (action_name in this.action_info_cache) return this.action_info_cache[action_name];
			
			var file = this.getActionFileFromName(action_name);
			var folder = this.getActionFolder(action_name);
			var info = {
				'folder': folder,
				'path_data': folder + file + this.EXTENSION_DATA,
				'path_script': folder + file + this.EXTENSION_SCRIPT,
				'path_template': folder + file + this.EXTENSION_TEMPLATE,
				'path_stylesheet': folder + file + this.EXTENSION_STYLE
			};
			
			this.action_info_cache[action_name] = info;
			return info;
		},
		
		/**
		 * Returns action folder path
		 * 
		 * @param {String} action_name Action name
		 * @param {String} base Optional, base path to use instead of default
		 * @return URL where action files can be found
		 * @type {String}
		 * @private
		 */
		getActionFolder: function (action_name /* Action name */) {
			if (!action_name) return null;
			var base = '';
			
			//Is action from another manager?
			if (action_name in this.paths) {
				base = this.paths[action_name];
			}
			
			base = base || this.getBasePath() || '';
			
			var action_file = this.getActionFileFromName(action_name);
			if (!action_file) return null;
			
			return base + '/' + action_file + '/';
		},
		
		/**
		 * Returns file name from action name
		 * 
		 * @param {String} action_name Action name
		 * @return Action file name
		 * @type {String}
		 * @private
		 */
		getActionFileFromName: function (action_name /* Action name */) {
			return String(action_name).replace(/[^a-z0-9\-\_]*/ig, '').toLowerCase();
		},
		
		/**
		 * Set external action base path
		 * 
		 * @param {String} id Action name 
		 * @param {String} path Path to action manager
		 */
		setActionBasePath: function (action_name  /* Action name */, path /* Path */) {
			//Remove trailing slash
			var path = path.replace(/[\/\\]*$/g, '');
			this.paths[action_name] = path;
			return this;
		},
		
		/**
		 * Set multiple action base paths 
		 * 
		 * @param {Object} actions
		 */
		setActionBasePaths: function (actions) {
			if (Y.Lang.isObject(actions)) {
				for(var action_name in actions) {
					this.setActionBasePath(action_name, actions[action_name]);
				}
			}
			return this;
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});