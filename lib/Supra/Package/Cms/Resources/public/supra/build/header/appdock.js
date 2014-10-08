YUI.add('supra.header.appdock', function(Y) {
	//Invoke strict mode
	"use strict";
	
	//Icon size constant
	var ICON_SIZES = {
		'32': '_32x32.png',
		'64': '_64x64.png'
	};
	
	//Blank icon image
	var ICON_BLANK = '/public/cms/supra/img/px.gif';
	
	//Templates
	var TEMPLATE_CURRENT = '<span class="icon-dashboard"><img src="/public/cms/supra/img/toolbar/buttons-dashboard.png" /></span><img src="{icon}" alt="" class="icon" /><span class="title">{title}</span>';
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
			this.after('dataChange', this.syncUI, this);
		},
		
		/**
		 * Update UI
		 * @private
		 */
		syncUI: function () {
			var node_app = this.get('nodeApp'),
				data_app = this.get('data');
			
			var node_img = node_app.one('img.icon'),
				node_title = node_app.one('span.title');
			
			if (!node_img) {
				var node_data = {
					'icon': this.getAppIcon(data_app, '32'),
					'title': data_app ? data_app.title : ''
				};
				node_img = Y.Node.create(Y.substitute(TEMPLATE_CURRENT, node_data));
				node_app.append(node_img);
				node_title = node_app.one('span.title');
			} else {
				node_img.setAttribute('src', this.getAppIcon(data_app, '32'));
				node_title.set('text', data_app ? data_app.title : ''); 
			}
			
			// Hide title element if it's empty
			node_title.toggleClass("hidden", !data_app || !data_app.title);
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
			if (this.get('disabled')) return;
			
			//Hide if it's already visible
			var dashboard = Supra.Manager.getAction("Applications"),
				sites = Supra.Manager.getAction("Sites");
			
			if (dashboard.get("visible")) {
				if (sites.get("visible")) {
					sites.hide();
				} else {
					dashboard.hide();
				}
			} else {
				dashboard.execute();
			}
		}
	});
	
	
	//Add to Supra namespace
	Supra.AppDock = AppDock;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tooltip']});