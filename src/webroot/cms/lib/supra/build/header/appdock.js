//Invoke strict mode
"use strict";

YUI.add('supra.header.appdock', function(Y) {
	
	//Icon size constant
	var ICON_SIZES = {
		'32': '_32x32.png',
		'64': '_64x64.png'
	};
	
	//Blank icon image
	var ICON_BLANK = '/cms/lib/supra/img/px.gif';
	
	//Templates
	var TEMPLATE_CURRENT = '<img src="{icon}" alt="" />';
	var TEMPLATE_ITEM = '<li><a href="{path}"><img src="{icon}" alt="" /><span>{title}</span></a></li>';
	var TEMPLATE_ITEM_LOGOUT = '<li class="logout"><a href="{path}"><div></div><span>{title}</span></a></li>';
	
	//Shortcuts
	var getClassName = Y.ClassNameManager.getClassName;
	
	/**
	 * Application dock bar
	 *
	 * @alias Supra.AppDock
	 * @param {Object} config Configuration
	 */
	function AppDock (config) {
		AppDock.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	AppDock.NAME = "app";
	AppDock.CLASS_NAME = getClassName(AppDock.NAME);
	
	AppDock.ATTRS = {
		
		/**
		 * Current application data
		 * @type {Object}
		 */
		'data': {
			'value': null
		},
		
		/**
		 * All application data
		 * @type {Array}
		 */
		'applications': {
			'value': null
		},
		
		'stats': {
			'value': null
		},
		
		/**
		 * All application request URI
		 * @type {String}
		 */
		'requestUri': {
			'value': null
		},
		
		/**
		 * App node
		 * @type {Object}
		 */
		'nodeApp': {
			'value': null
		},
		
		/**
		 * App dock bar node
		 * @type {Object}
		 */
		'nodeDock': {
			'value': null
		}
	};
	
	AppDock.HTML_PARSER = {
		/**
		 * Find existing node
		 */
		'nodeApp': function (srcNode) {
			return srcNode.one('.' + getClassName(AppDock.NAME, 'current'));
		}
	};
	
	
	Y.extend(AppDock, Y.Widget, {
		
		/**
		 * Dock bar visiblity state
		 * @type {Boolean}
		 * @private
		 */
		dock_visible: false,
		
		/**
		 * Render dock bar
		 * @private
		 */
		renderUI: function () {
			AppDock.superclass.renderUI.apply(this, arguments);
			
			//Create node app
			var node_app = this.get('nodeApp');
			if (!node_app) {
				node_app = Y.Node.create('<a class="' + getClassName(AppDock.NAME, 'current') + '"></a>');
				this.get('contentBox').append(node_app);
				this.set('nodeApp', node_app);
			}
		},
		
		/**
		 * Bind event listeners
		 * @private
		 */
		bindUI: function () {
			AppDock.superclass.bindUI.apply(this, arguments);
			
			this.get('nodeApp').on('click', this.toggleAppDockBar, this);
		},
		
		/**
		 * Update UI
		 * @private
		 */
		syncUI: function () {
			var node_app = this.get('nodeApp'),
				data_app = this.get('data');
			
			var node_img = node_app.one('img'),
				node_title = node_app.one('span');
			
			if (!node_img) {
				var node_data = {
					'icon': this.getAppIcon(data_app, '32'),
					'title': data_app.title
				};
				node_img = Y.Node.create(Y.substitute(TEMPLATE_CURRENT, node_data));
				node_app.append(node_img);
			} else {
				node_img.setAttribute('src', this.getAppIcon(data_app, '32'));
				node_title.set('text', data_app.title); 
			}
		},
		
		/**
		 * Returns app icon URI
		 * 
		 * @param {Object} data Application data
		 * @param {String} size Icon size ID
		 * @return Application icon URI
		 * @type {String}
		 */
		getAppIcon: function (data, size) {
			if (data && data.icon && size in ICON_SIZES) {
				return data.icon + ICON_SIZES[size];
			} else {
				return ICON_BLANK;
			}
		},
		
		/**
		 * Open application dock bar
		 */
		toggleAppDockBar: function (event) {
			//Stop event
			if (event) event.halt();
			
			//Hide if it's already visible
			var node_dock = this.get('nodeDock');
			if (this.dock_visible) {
				
				//Hide using CSS3 animation and then hide container
				this.get('boundingBox').removeClass(getClassName(AppDock.NAME, 'dock', 'visible'));
				Y.later(500, this, function () {
					node_dock.addClass('hidden');
				});
				
				this.dock_visible = false;
				return;
			}
			
			//Dock panel
			if (!node_dock) {
				node_dock = Y.Node.create('<div class="' + getClassName(AppDock.NAME, 'dock') + '"><ul></ul></div>');
				this.set('nodeDock', node_dock);
				this.get('contentBox').append(node_dock);
				
				node_dock.append('<div class="yui3-app-dock-stats">\n\
							<div class="stat-content"></div>\n\
					</div>');
				
			} else {
				node_dock.removeClass('hidden');
			}
			
			this.dock_visible = true;
			
			//Show using CSS3 animation, delay is needed to make sure animation runs in FF
			Y.later(50, this, function () {
				this.get('boundingBox').addClass(getClassName(AppDock.NAME, 'dock', 'visible'));
			});
			
			//Load applications
			if (!this.get('applications')) {
				this.loadApplications();
			}
			
			if (!this.get('stats')) {
				this.loadStats();
			}
		},
		
		/**
		 * Load application list
		 * 
		 * @param {Function} callback Callback function
		 * @private
		 */
		loadApplications: function (callback) {
			var node_dock = this.get('nodeDock'),
				classname = getClassName(AppDock.NAME, 'dock', 'loading'),
				uri = this.get('requestUri');
			
			node_dock.addClass(classname);
			
			Supra.io(uri, {
				'context': this,
				'on': {
					'complete': function (data) {
						node_dock.removeClass(classname);
						this.renderApplications(data || []);
						
						if (data) {
							this.set('applications', data);
						}
					}
				}
			});
		},
		
		loadStats: function (callback) {
			var node_dock_stats = this.get('nodeDock').one('.yui3-app-dock-stats'),
				classname = getClassName(AppDock.NAME, 'dock', 'loading'),
				uri = '/cms/dashboard/stats/list.json';
			
			node_dock_stats.addClass(classname);
			
			Supra.io(uri, {
				'context': this,
				'on': {
					'complete': function (data) {
						node_dock_stats.removeClass(classname);
						this.renderStats(data || []);
						if (data) {
							this.set('stats', data);
						}
					}
				}
			});
		},
		
		renderStats: function (data) {
			var node_layer = this.get('nodeDock')
				.one('.yui3-app-dock-stats')
				.one('.stat-content');
				
			node_layer.empty();
			
			for (var date in data) {
				node_layer.append('<p>' + date + '| Visitors: ' + data[date].visitors + '\
							 Pageviews: ' + data[date].pageviews + '\
							 Visits: ' + data[date].visits + '</p>');
			}

		},
		
		/**
		 * Render application list
		 * 
		 * @param {Array} data Application list
		 * @private
		 */
		renderApplications: function (data) {
			var node_list = this.get('nodeDock').one('ul'),
				node_item = null,
				node_data = null,
				current = Supra.data.get(['application', 'id'], '');
			
			node_list.empty();
			
			for(var i=0,ii=data.length; i<ii; i++) {
				node_data = {
					'path': data[i].path,
					'icon': this.getAppIcon(data[i], '64'),
					'title': data[i].title	
				};
				
				node_item = Y.Node.create(Y.substitute(TEMPLATE_ITEM, node_data));
				
				if (data[i].id == current) {
					node_item.on('click', this.toggleAppDockBar, this);
				}
				
				node_list.append(node_item);
			}
			
			node_data = {
				'path': Supra.Manager.Loader.getDynamicPath() + '/logout/',
				//TODO: localize
				'title': 'Logout'
			};
			
			node_item = Y.Node.create(Y.substitute(TEMPLATE_ITEM_LOGOUT, node_data));
			node_item.addClass('logout');
			node_list.append(node_item);
		}
		
	});
	
	
	//Add to Supra namespace
	Supra.AppDock = AppDock;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tooltip']});