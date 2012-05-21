//Invoke strict mode
"use strict";
 
YUI.add("website.app-favourites", function (Y) {
 	
 	
 	var TEMPLATE_CONTAINER = Supra.Template.compile('\
 			<div class="app-list-empty app-list-outter">\
				<ul class="app-list clearfix">\
					<li class="empty"></li>\
				</ul>\
			</div>');
 	
 	var TEMPLATE_APPLICATION = Supra.Template.compile('\
			<li data-id="{{ id|escape }}">\
				<span><img src="{{ icon|escape }}" alt="" /></span>\
				<label>{{ title|escape }}</label>\
			</li>');
 	
	
	/**
	 * Application list
	 */
	function AppFavourites (config) {
		AppFavourites.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	AppFavourites.NAME = "app-favourites";
	AppFavourites.CSS_PREFIX = 'su-' + AppFavourites.NAME;
	AppFavourites.CLASS_NAME = Y.ClassNameManager.getClassName(AppFavourites.NAME);
 
	AppFavourites.ATTRS = {
		//Title
		"title": {
			"value": "",
			"setter": "_setTitle"
		},
		
		//Application list 
		"data": {
			"value": [],
			"setter": "_setData"
		},
		
		//Number of columns
		"columns": {
			"value": 4
		},
		
		//Single item width
		"itemWidth": {
			"value": 150
		},
		
		//Single item width
		"itemHeight": {
			"value": 150
		}
	};
	
	AppFavourites.HTML_PARSER = {
		"title": function (srcNode) {
			var attr = srcNode.getAttribute("suTitle");
			if (attr) return attr;
		}
	};
 
	Y.extend(AppFavourites, Y.Widget, {
		
		/**
		 * Application template function
		 * @type {Function}
		 * @private
		 */
		"TEMPLATE_APPLICATION": TEMPLATE_APPLICATION,
		
		/**
		 * Application container template function
		 * @type {Function}
		 * @private
		 */
		"TEMPLATE_CONTAINER": TEMPLATE_CONTAINER,
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		"widgets": null,
		
		/**
		 * Application nodes
		 * @type {Array}
		 * @private
		 */
		"applications": null,
		
		/**
		 * Application info
		 * @type {Array}
		 * @private
		 */
		"applications_info": null,
		
		/**
		 * Application data
		 * @type {Object}
		 * @private
		 */
		"data": null,
		
		/**
		 * Application list container
		 * @type {Object}
		 * @private
		 */
		"list": null,
		
		/**
		 * Draggable delegation object
		 * @type {Object}
		 * @private
		 */
		"draggable": null,
		
		/**
		 * Draggable target
		 * @type {Object}
		 * @private
		 */
		"target": null,
		
		/**
		 * List height
		 * @type {Object}
		 * @private
		 */
		"listHeight": 0,
		
		/**
		 * Column count changed due to resize
		 */
		"columnCountChanged": false,
		
 
		/**
		 * Create/add nodes, render widgets
		 *
		 * @private
		 */
		renderUI: function () {
			AppFavourites.superclass.renderUI.apply(this, arguments);
			
			var container = this.get("contentBox");
			
			//Heading
			var h2 = Y.Node.create("<h2></h2>").set("text", this.get("title"));
			container.append(h2);
			
			//Container
			var list = Y.Node.create(this.TEMPLATE_CONTAINER({}));
			container.append(list);
			this.list = list.one("ul");
			
			//Set initial column count
			this.set("columns", this.getColumnCount());
			
			//Set initial data
			this.data = [];
			this.applications = [];
			this.applications_info = [];
		},
 
		/**
		 * Attach event listeners
		 *
		 * @private
		 */
		bindUI: function () {
			AppFavourites.superclass.bindUI.apply(this, arguments);
			
			//On resize update column count
			Y.on('resize', Y.throttle(Y.bind(this.checkColumnsCount, this), 50), window);
			
			//On column count change move items
			this.after("columnsChange", this.updateColumnsCount, this);
			
			//Drag and drop
			var draggable = this.draggable = new Y.DD.Delegate({
				"container": this.list,
				"nodes": "li",
				"target": false,
				"dragConfig": {
					"haltDown": false
				}
			});
			
			draggable.dd.plug(Y.Plugin.DDProxy, {
				"moveOnEnd": false,
				"cloneNode": true
			});
			
			var target = this.target = new Y.DD.Drop({
				"node": this.list
			});
			
			draggable.on('drag:start', this.onDragStart, this);
			target.on('drop:hit', this.onDrop, this);
		},
 
		/**
		 * Clean up
		 *
		 * @private
		 */
		destructor: function () {
			var apps = this.applications;
			
			for (var i=0, ii=apps.length; i<ii; i++) {
				apps[i].remove();
			}
			
			this.applications = null;
			this.applications_info = null;
			this.data = null;
			this.draggable.destroy();
		},
		
		
		/**
		 * ---------------------------- DRAG AND DROP -------------------------
		 */
		
		
		/**
		 * Fires when dragable item from this list is dragged
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDragStart: function (e) {
			//Add classname to proxy element
	        var proxy = e.target.get("dragNode");
			proxy.addClass("app-list-proxy");
			
			Y.one('body').append(proxy);
		},
		
		/**
		 * Fires when dragable item from this list is droped
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDragEnd: function (e) {
			var node = e.target.get("node"),
				id = node.getAttribute("data-id");
			
			if (id) {
				this.removeApplication(id);
			}
		},
		
		/**
		 * Fires when dragable item is droped on this list
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDrop: function (e) {
			var node = e.drag.get("node");
			if (node) {
				var data = node.getData("app");
				if (data) {
					this.addApplication(data);
				}
			}
		},
		
		
		/**
		 * ---------------------------- APPLICATION LIST -------------------------
		 */
		
		
		/**
		 * Fill application list
		 * 
		 * @param {Array} data Application data
		 * @private
		 */
		fillApplicationList: function (data) {
			var applications = null,
				info = [];
			
			applications = Y.Array.map(data, function (app, index) {
				info[index] = {"ready": false, "index": index};
				var node = Y.Node.create(this.TEMPLATE_APPLICATION(app));
				node.setData("app", app);
				return node;
			}, this);
			
			this.data = data;
			this.applications = applications;
			this.applications_info = info;
			
			this.moveApplications();
		},
		
		/**
		 * Move applications into correct places
		 * 
		 * @private
		 */
		moveApplications: function () {
			this.resizeContainer();
			if (!this.data.length) return;
			
			var applications = this.applications,
				info = this.applications_info,
				list = this.list,
				itemIndex = 0,
				position = null,
				columnCountChanged = this.columnCountChanged;
			
			for (var i=0, ii=applications.length; i<ii; i++) {
				itemIndex = i;
				position = this.getItemPosition(itemIndex);
				
				if (info[i].ready) {
					//If in DOM
					
					//Only if index changed
					if (info[i].index != itemIndex || columnCountChanged) {
						this.appMove(applications[i], position);
					}
				} else {
					//If not already in DOM
					if (info[i].added) {
						this.appFadeIn(applications[i], list, position);
					} else {
						this.appPlace(applications[i], list, position);
					}
				}
				
				//Save info
				info[i] = {"ready": true, "index": itemIndex};
			}
		},
		
		/**
		 * Returns item position
		 * 
		 * @param {Number} index Index
		 * @private
		 */
		getItemPosition: function (index) {
			var width = this.get("itemWidth"),
				height = this.get("itemHeight"),
				columns = this.get("columns");
			
			return [
				index % columns * width,		//x
				~~(index / columns) * height	//y
			];
		},
		
		/**
		 * On column count change update item position
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		updateColumnsCount: function (e) {
			if (e.prevVal != e.newVal) {
				this.moveApplications();
			}
		},
		
		/**
		 * On resize update column count
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		checkColumnsCount: function (e) {
			var prevColumns = this.get("columns"),
				newColumns = this.getColumnCount();
			
			if (prevColumns != newColumns) {
				this.columnCountChanged = true;
				this.set("columns", newColumns);
				this.columnCountChanged = false;
			}
		},
		
		/**
		 * Returns number of columns per view
		 * 
		 * @private
		 */
		getColumnCount: function () {
			var width = this.get("contentBox").get("offsetWidth");
			return ~~(width / this.get("itemWidth"));
		},
		
		/**
		 * Returns list height
		 * 
		 * @private
		 */
		getListHeight: function () {
			return Math.ceil(this.data.length / this.get("columns")) * this.get("itemHeight");
		},
		
		/**
		 * Resizes list
		 * 
		 * @private
		 */
		resizeContainer: function () {
			var height = this.getListHeight();
			
			if (height != this.listHeight) {
				this.listHeight = height;
				this.list.transition({
					'height': height + 'px',
					'duration': 0.35
				});
			}
		},
		
		
		/**
		 * ---------------------------- ANIMATIONS -------------------------
		 */
		
		
		/**
		 * Fade in application
		 * 
		 * @private
		 */
		appFadeIn: function (app, container, position) {
			container.append(app);
			app.setStyles({
				"left": position[0] + "px",
				"top":  position[1] + "px",
				"transform": "scale(0.3)",
				"opacity": 0
			});
			
			app.transition({
				"opacity": 1,
				"transform": "scale(1)",
				"duration": 0.35
			});
		},
		
		/**
		 * Animate application from one position into another
		 * 
		 * @private
		 */
		appMove: function (app, position) {
			app.transition({
				"left": position[0] + "px",
				"top":  position[1] + "px",
				"duration": 0.35
			});
		},
		
		/**
		 * Place application in position without any animations
		 * 
		 * @private
		 */
		appPlace: function (app, container, position) {
			container.append(app);
			app.setStyles({
				"left": position[0] + "px",
				"top":  position[1] + "px"
			});
		},
		
 
 
		/**
		 * ---------------------------- API -------------------------
		 */
 
 
		/**
		 * Remove application form the list
		 * 
		 * @param {String} id Application ID
		 * @param {Boolean} silent Don't trigger event
		 */
		removeApplication: function (id, silent) {
			var applications = this.applications,
				application = null,
				info = this.applications_info,
				data = this.data,
				removed = false;
			
			for (var i=0, ii=applications.length; i<ii; i++) {
				if (applications[i].getAttribute("data-id") == id) {
					
					applications[i].remove();
					applications.splice(i, 1);
					
					info.splice(i, 1);
					
					removed = true;
					break;
				}
			}
			
			for (var i=0, ii=data.length; i<ii; i++) {
				if (data[i].id == id) {
					application = data[i];
					data.splice(i, 1); break;
				}
			}
			
			if (removed) {
				if (!data.length) {
					this.list.ancestor().addClass("app-list-empty");
				}
				
				this.moveApplications();
				this.draggable.syncTargets();
				
				if (silent !== true) {
					this.fire("appremove", {
						"application": application
					});
				}
			}
		},
		
		/**
		 * Add application
		 * 
		 * @param {Object} data Application data
		 * @param {Boolean} silent Don't trigger event
		 */
		addApplication: function (data, silent) {
			var find = Y.Array.find(this.data, function (item) {
				if (item.id === data.id) return true;
			});
			
			if (find) {
				//Item already in the list
				return;
			}
			
			var index = this.data.length,
				node = Y.Node.create(this.TEMPLATE_APPLICATION(data));
			
			node.setData("app", data);
			
			this.data.push(data);
			this.applications.push(node);
			this.applications_info.push({"ready": false, "index": index, "added": true});
			
			this.list.ancestor().removeClass("app-list-empty")
			
			this.moveApplications();
			this.draggable.syncTargets();
			
			if (silent !== true) {
				this.fire("appadd", {
					"application": data,
					"node": node
				});
			}
		},
 
 
		/**
		 * ---------------------------- ATTRIBUTES -------------------------
		 */
 
 
		/**
		 * Title attribute setter
		 * 
		 * @param {String} value New title
		 * @return New title
		 * @type {String}
		 * @private
		 */
		_setTitle: function (title) {
			var heading = this.get("contentBox").one("h2");
			if (heading) heading.set("text", title);
			
			return title;
		},
		
		/**
		 * Data attribute setter
		 * 
		 * @param {Array} value New dat
		 * @return New data
		 * @type {Array}
		 * @private
		 */
		_setData: function (data) {
			if (this.widgets && this.list && data.length) {
				this.fillApplicationList(data);
			}
			return data;
		}
	});
 
	Supra.AppFavourites = AppFavourites;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget", "transition", "dd"]});