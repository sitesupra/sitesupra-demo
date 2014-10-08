YUI.add('supra.manager-loader', function (Y) {
	//Invoke strict mode
	"use strict";
	
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
		
		/**
		 * Path to static files
		 * @type {String}
		 */
		static_path: null,
		
		/**
		 * Path to dynamic files
		 * @type {String}
		 */
		dynamic_path: null,
		
		/**
		 * Path to current manager
		 * @type {String}
		 */
		base: null,
		
		/**
		 * Paths to external managers
		 * @type {Object}
		 */
		paths: {},
		
		/**
		 * List of loaded actions
		 * @type {Object}
		 */
		loaded: {},
		
		/**
		 * List of actions which are currently loading
		 * @type {Object}
		 */
		loading: {},
		
		/**
		 * Action info cache
		 * @type {Object}
		 */
		action_info_cache: {},
		
		/**
		 * Action template cache
		 * @type {Object}
		 */
		template_cache: {},
		
		/**
		 * List of templates which are loading
		 * @type {Object}
		 */
		template_loading: {},
		
		/**
		 * Action dependancies
		 * @type Object}
		 */
		dependancies: {},
		
		/**
		 * Set manager base path
		 * 
		 * @param {Object} path
		 */
		setBasePath: function (path) {
			//Remove trailing slash from folder
			this.base = String(path).replace(/\/+$/, '');
		},
		
		/**
		 * Get manager base path
		 * 
		 * @return Path
		 * @type {String}
		 */
		getBasePath: function () {
			return this.base;
		},
		
		/**
		 * Set manager static file path
		 * 
		 * @param {String} path
		 */
		setStaticPath: function (path) {
			//Remove trailing slash from folder
			this.static_path = String(path).replace(/\/+$/, '');
		},
		
		/**
		 * Returns static file path
		 * 
		 * @return Path
		 * @type {String}
		 */
		getStaticPath: function () {
			return this.static_path;
		},
		
		/**
		 * Set manager dynamic file path
		 * 
		 * @param {String} path
		 */
		setDynamicPath: function (path) {
			//Remove trailing slash from folder
			this.dynamic_path = String(path).replace(/\/+$/, '');
		},
		
		/**
		 * Returns dynamic file path
		 * 
		 * @return Path
		 * @type {String}
		 */
		getDynamicPath: function () {
			return this.dynamic_path;
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
		 * Returns if action is loading
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
			
			delete(this.loading[action_name]);
			delete(this.loaded[action_name]);
			delete(this.template_cache[info.path_template]);
			delete(this.template_loading[info.path_template]);
			delete(this.action_info_cache[action_name]);
			
		},
		
		/**
		 * Load actions
		 *
		 * @param {Array} action_names Array of action names
		 * @return True if any action stated loading, false if all actions already loading or loading
		 * @type {Boolean}
		 */
		loadActions: function (action_names) {
			var load_list = [],
				paths = [],
				info = null;
			
			for(var i=0,ii=action_names.length; i<ii; i++) {
				if (!this.isLoaded(action_names[i]) && !this.isLoading(action_names[i])) {
					load_list.push(action_names[i]);
				}
			}
			
			//All action already loaded or loading?
			if (!load_list.length) return false;
			
			//If internationalized data not loaded, wait till it is
			var base = this.static_path + (this.paths[load_list[0]] || this.getBasePath());
			if (!Supra.Intl.isLoaded(base)) {
				Supra.Intl.loadAppData(base, function () {
					//Call loadAction again
					this.loadActions(action_names);
				}, this);
				return;
			}
			
			//
			for(i=0,ii=load_list.length; i<ii; i++) {
				//
				info = this.getActionInfo(load_list[i]);
				paths.push(info.path_script);
				
				this.loading[load_list[i]] = {
					'script': true,
					'style': false,
					'template': false,
					'dependancies': false
				};
			}
			
			var path = Supra.YUI_BASE.groups.website.comboBase + paths.join('&');
			
			//Get SCRIPT
			Y.Get.js(path, {
				onSuccess: function (o) {
					//Script is loaded, but template and stylesheet is not
					//rest is handled by Supra.Managet.Action
				},
				async: true,
				context: this,
				data: load_list
			});
			
			return true;
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
			return this.loadActions([action_name]);
		},
		
		/**
		 * Load stylesheet and template
		 * 
		 * @private
		 */
		loadExtras: function (action_name) {
			var self = this,
				action = Manager.getAction(action_name);
			
			//Script finished loading
			if (action_name in this.loading) {
				this.loading[action_name].script = false;
				
				this.loadTemplate(action_name, function (template) {
					
					//Locale strings were already replaced by Supra.IO
					action.template = template || action.template;
					
					if (!(action_name in self.dependancies)) {
						//There are no depedancies, fire loaded
						action._fireLoaded();
						Manager.runExecutionQueue();
					}
					
				});
			} else {
				//Action was created, but is was not loaded using Supra.Manager.Loader.loadAction()
				//so there is no this.loading[action_name] for given action
				action._fireLoaded();
				Manager.runExecutionQueue();
			}
		},
		
		/**
		 * Check if dependancies are resolved
		 * 
		 * @param {String} action_name Action name
		 */
		checkDependancies: function (action_name) {
			var dependancies = this.dependancies,
				list = [],
				index = 0,
				is_loaded = false;
			
			for(var id in this.dependancies) {
				list = this.dependancies[id];
				
				index = Y.Array.indexOf(list, action_name);
				if (index !== -1) {
					list.splice(index, 1);
				}
				
				//No more dependancies
				if (!list.length) {
					delete(this.dependancies[id]);
					
					this.loading[id].dependancies = false;
					
					if (!this.loading[id].style && !this.loading[id].template) {
						//Only if style and template is loaded
						Manager.getAction(id)._fireLoaded();
						is_loaded = true;
					}
				}
			}
			
			if (is_loaded) {
				Manager.runExecutionQueue();
			}
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
				
				Manager.Loader.loading[action_name].style = true;
				stylesheetLoaded = false;
				
				//CSS are loaded synchronously
				Supra.io.css(stylesheetPath, {
					'onSuccess': function () {
						stylesheetLoaded = true;
						Manager.Loader.loading[action_name].style = false;
						
						//If template request already completed, call callback
						if (templateLoaded) callback(template);
					}
				});
				
				//If there is no need to load template then return
				if (templateLoaded) {
					return;
				}
			}
			
			//Load template if needed
			if (templatePath && hasTemplate) {
				var cache = this.template_cache;
				var loading = this.template_loading;
				
				if (!(templatePath in cache)) {

					Manager.Loader.loading[action_name].template = true;
					loading[templatePath] = true;
					templateLoaded = false;
					
					Supra.io(templatePath, {
						'type': 'html',
						'on': {
							'success': function (html, status) {
								templateLoaded = true;
								Manager.Loader.loading[action_name].template = false;
								
								delete(loading[templatePath]);
								cache[templatePath] = html;
								template = html;
								
								//If stylesheet request already completed, call callback
								if (stylesheetLoaded) callback(html);
							},
							'failure': function () {
								//@TODO Handle failure
								templateLoaded = true;
								Manager.Loader.loading[action_name].template = false;
								
								delete(loading[templatePath]);
								cache[templatePath] = template;
								
								//If stylesheet request already completed, call callback
								if (stylesheetLoaded) callback(template);
							}
						}
					}, this);
				} else {
					template = cache[templatePath];
				}
			}
			
			//Set loaded states
			this.loading[action_name].template = !templateLoaded;
			this.loading[action_name].style = !stylesheetLoaded;
			
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
			var folder_static = this.getActionFolder(action_name, false);
			var folder_dynamic = this.getActionFolder(action_name, true);
			var info = {
				'folder': folder_static,
				'folder_data': folder_dynamic,
				'path_data': folder_dynamic + file + this.EXTENSION_DATA,
				'path_script': folder_static + file + this.EXTENSION_SCRIPT,
				'path_template': folder_static + file + this.EXTENSION_TEMPLATE,
				'path_stylesheet': folder_static + file + this.EXTENSION_STYLE
			};
			
			this.action_info_cache[action_name] = info;
			return info;
		},
		
		/**
		 * Returns action folder path
		 * 
		 * @param {String} action_name Action name
		 * @param {String} dynamic Return dynamic folder path instead of static
		 * @return URL where action files can be found
		 * @type {String}
		 * @private
		 */
		getActionFolder: function (action_name /* Action name */, dynamic /* Return dynamic folder path */) {
			if (!action_name) return null;
			var base = '';
			
			//Is action from another manager?
			if (action_name in this.paths) {
				base = this.paths[action_name];
			}
			
			base = base || this.getBasePath() || '';
			
			var action_file = this.getActionFileFromName(action_name);
			if (!action_file) return null;

			// @FIXME: use route names instead
			var applicationData = Supra.data.get('application');
            var applicationPath = '/';
            if (applicationData.route) {
                applicationPath = Supra.Url.generate(applicationData.route);
            }

			var prefix = dynamic ? applicationPath : (this.getStaticPath() + base);

			return prefix + '/' + action_file + '/';
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
		 * Returns external action base path
		 *
		 * @param {String} id Action name
		 * @return Action base path
		 * @type {String}
		 */
		getActionBasePath: function (action_name /* Action name */) {
			return this.paths[action_name] || this.base;
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