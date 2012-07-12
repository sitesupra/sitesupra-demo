//Invoke strict mode
"use strict";
 
YUI.add("dashboard.app-list", function (Y) {
 	
 	
 	var TEMPLATE_APPLICATION = Supra.Template.compile('\
			<li data-id="{{ id|escape }}">\
				<span><img src="{{ icon|escape }}" alt="" /></span>\
				<label>{{ title|escape }}</label>\
			</li>');
 	
	
	/**
	 * Application list
	 */
	function AppList (config) {
		AppList.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	AppList.NAME = "app-list";
	AppList.CSS_PREFIX = "su-" + AppList.NAME;
	AppList.CLASS_NAME = Y.ClassNameManager.getClassName(AppList.NAME);
 
	AppList.ATTRS = {
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
		
		//Number of rows
		"rows": {
			"value": 3
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
			"value": 125
		}
	};
	
	AppList.HTML_PARSER = {
		"title": function (srcNode) {
			var attr = srcNode.getAttribute("suTitle");
			if (attr) return attr;
		}
	};
 
	Y.extend(AppList, Y.Widget, {
		
		/**
		 * Application template function
		 * @type {Function}
		 * @private
		 */
		"TEMPLATE_APPLICATION": TEMPLATE_APPLICATION,
		
		
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
		 * Slide count
		 * @type {Number}
		 * @private
		 */
		"slideCount": 0,
		
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
		 * Column count changed due to resize
		 */
		"columnCountChanged": false,
		
 
		/**
		 * Create/add nodes, render widgets
		 *
		 * @private
		 */
		renderUI: function () {
			AppList.superclass.renderUI.apply(this, arguments);
			
			var container = this.get("contentBox");
			
			//Heading
			var h2 = Y.Node.create("<h2></h2>").set("text", this.get("title"));
			container.append(h2);
			
			//Slideshow
			var slideshow = new Supra.Slideshow({
				"scrollable": false
			});
			
			slideshow.render(container);
			slideshow.get("boundingBox").setStyle("height", this.getSlideHeight());
			
			//Pagination
			var pagination = new Supra.Pagination();
			
			pagination.render(container);
			
			//Set initial column count
			this.set("columns", this.getColumnCount());
			
			//Finalize
			this.widgets = {
				"slideshow": slideshow,
				"pagination": pagination
			};
			
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
			AppList.superclass.bindUI.apply(this, arguments);
			
			//On resize update column count
			Y.on("resize", Y.throttle(Y.bind(this.checkColumnsCount, this), 50), window);
			Y.on("resize", Y.bind(this.widgets.slideshow.syncUI, this.widgets.slideshow), window);
			
			//On column count change move items
			this.after("columnsChange", this.updateColumnsCount, this);
			
			//Change slide on navigation active index change
			this.widgets.pagination.on("indexChange", this.handleNavigationIndexChange, this);
			
			//Click
			this.widgets.slideshow.get("contentBox").delegate("click", this.handleAppClick, "li", this);
			
			//Drag and drop
			var draggable = this.draggable = new Y.DD.Delegate({
				"container": this.widgets.slideshow.get("contentBox"),
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
				"node": this.widgets.slideshow.get("contentBox")
			});
			
			draggable.on("drag:start", this.onDragStart, this);
			target.on("drop:hit", this.onDrop, this);
		},
 
		/**
		 * Clean up
		 *
		 * @private
		 */
		destructor: function () {
			var widgets = this.widgets,
				apps = this.applications;
			
			for (var i in widgets) {
				if (widgets[i]) widgets[i].destroy();
			}
			
			for (var i=0, ii=apps.length; i<ii; i++) {
				apps[i].remove();
			}
			
			this.applications = null;
			this.applications_info = null;
			this.data = null;
			this.draggable.destroy();
			this.widgets = null;
		},
		
		
		/**
		 * ---------------------------- DRAG AND DROP -------------------------
		 */
		
		
		onDragStart: function (e) {
			//Add classname to proxy element
	        var proxy = e.target.get("dragNode");
			proxy.addClass("app-list-proxy");
			
			Y.one("body").append(proxy);
		},
		
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
				rows = this.get("rows"),
				info = [];
			
			applications = Y.Array.map(data, function (app, index) {
				info[index] = {"ready": false, "slide": -1, "index": index};
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
			this.resizeSlideshow();
			if (!this.data.length) return;
			
			var applications = this.applications,
				info = this.applications_info,
				perView = this.getItemCountPerSlide(),
				slideshow = this.widgets.slideshow,
				slides = [],
				slideIndex = 0,
				itemIndex = 0,
				currentSlide = parseInt(slideshow.get("slide").replace("slide_", ""), 10),
				inVisibleSlide = false,
				
				position = null,
				
				columnCountChanged = this.columnCountChanged;
			
			for(var i in slideshow.slides) slides[parseInt(i.replace("slide_", ""), 10)] = slideshow.slides[i].one("ul");
			
			for (var i=0, ii=applications.length; i<ii; i++) {
				slideIndex = ~~(i / perView);
				//itemIndex = i % ((slideIndex + 1) * perView);
				itemIndex = i % perView;
				
				position = this.getItemPosition(itemIndex);
				
				if (info[i].ready) {
					//If in DOM
					
					//Is this item in visible slide or will it be?
					inVisibleSlide = (info[i].slide == currentSlide || slideIndex == currentSlide);
					
					if (info[i].slide != slideIndex) {
						if (inVisibleSlide) {
							//If item changed to or from visible slide, then fade in or out
							
							if (slideIndex == currentSlide) {
								//Fade in
								this.appFadeIn(applications[i], slides[slideIndex], position);
							} else {
								//Fade out
								this.appFadeOut(applications[i], slides[slideIndex], position);
							}
						} else {
							//Item changed from and to invisible slide
							this.appPlace(applications[i], slides[slideIndex], position);
						}
					} else {
						//Only if slide or index changed
						if (columnCountChanged || info[i].slide != slideIndex || info[i].index != itemIndex) {
							//Animate if in visible slide
							this.appMove(applications[i], position, inVisibleSlide);
						}
					}
				} else {
					//If not already in DOM
					
					
					//If not already in DOM
					if (info[i].added && inVisibleSlide) {
						this.appFadeIn(applications[i], slides[slideIndex], position, true);
					} else {
						this.appPlace(applications[i], slides[slideIndex], position);
					}
				}
				
				//Save info
				info[i] = {"ready": true, "slide": slideIndex, "index": itemIndex};
			}
		},
		
		/**
		 * Returns item position in slide
		 * 
		 * @param {Number} index Index in current slide
		 * @private
		 */
		getItemPosition: function (index) {
			var width = this.get("itemWidth"),
				height = this.get("itemHeight"),
				columns = this.get("columns"),
				rows = this.get("rows");
			
			/*
			return [
				~~(index / rows) * width,	// x
				index % rows * height		// y
			];
			*/
			
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
		 * Returns slide count
		 * 
		 * @private
		 */
		getSlideCount: function () {
			if (!this.data) return 1;
			
			var count = this.data.length,
				perSlide = this.getItemCountPerSlide() || 1; // 1 to prevent division by zero
			
			return Math.max(1, Math.ceil(count / perSlide));
		},
		
		/**
		 * Returns item count per view
		 * 
		 * @private
		 */
		getItemCountPerSlide: function () {
			return this.get("columns") * this.get("rows");
		},
		
		/**
		 * Returns slide height
		 * 
		 * @private
		 */
		getSlideHeight: function () {
			return this.get("rows") * this.get("itemHeight");
		},
		
		/**
		 * Add or remove slides
		 * 
		 * @private
		 */
		resizeSlideshow: function () {
			var prevSlideCount = this.slideCount,
				newSlideCount = this.getSlideCount(),
				slideshow = this.widgets.slideshow,
				slide = null;
			
			if (prevSlideCount != newSlideCount) {
				if (prevSlideCount < newSlideCount) {
					//Add slides
					for(var i=prevSlideCount; i<newSlideCount; i++) {
						slide = slideshow.getSlide("slide_" + i);
						if (!slide) {
							slide = slideshow.addSlide({
								"id": "slide_" + i,
								"scrollable": false
							});
							
							slide.one(".su-slide-content").set("innerHTML", "<ul class=\"app-list\"></ul>");
						}
					}
				}
				
				this.widgets.pagination.set("total", newSlideCount);
			}
			
			this.slideCount = newSlideCount;
		},
		
		/**
		 * On navigation index change update slideshow
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleNavigationIndexChange: function (e) {
			if (e.newVal != e.prevVal) {
				this.widgets.slideshow.set("slide", "slide_" + e.newVal);
			}
		},
		
		/**
		 * Handle click on application
		 */
		handleAppClick: function (e) {
			var node	= e.target.closest("li"),
				id		= node.getAttribute("data-id"),
				apps	= this.data,
				i		= 0,
				ii		= apps.length;
			
			for (; i<ii; i++) {
				if (apps[i].id == id) {
					if (e.button == 2) {
						//Middle mouse button, open in new tab
						window.open(apps[i].path);
					} else {
						document.location = apps[i].path;
					}
				}
			}
		},
		
		
		/**
		 * ---------------------------- ANIMATIONS -------------------------
		 */
		
		
		/**
		 * Fade out application
		 * 
		 * @private
		 */
		appFadeOut: function (app, container, position) {
			app.transition({
				"opacity": 0,
				"duration": 0.35
			}, function () {
				if (container) {
					//Move into correct position
					container.append(app);
					app.setStyles({
						"left": position[0] + "px",
						"top":  position[1] + "px",
						"opacity": 1
					});
				} else {
					//Remove
					app.remove();
				}
			});
		},
		
		/**
		 * Fade in application
		 * 
		 * @private
		 */
		appFadeIn: function (app, container, position, added) {
			var styles = {
					"left": position[0] + "px",
					"top":  position[1] + "px",
					"opacity": 0
				},
				anim = {
					"opacity": 1,
					"duration": 0.35
				};
			
			if (added) {
				styles.transform = "scale(0.3)";
				anim.transform = "scale(1)";
			}
			
			container.append(app);
			app.setStyles(styles);
			app.transition(anim);
		},
		
		/**
		 * Animate application from one position into another
		 * 
		 * @private
		 */
		appMove: function (app, position, animate) {
			if (animate) {
				app.transition({
					"left": position[0] + "px",
					"top":  position[1] + "px",
					"duration": 0.35
				});
			} else {
				app.setStyles({
					"left": position[0] + "px",
					"top":  position[1] + "px"
				});
			}
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
				//draggables = this.draggables,
				data = this.data,
				removed = false;
			
			for (var i=0, ii=applications.length; i<ii; i++) {
				if (applications[i].getAttribute("data-id") == id) {
					this.appFadeOut(applications[i]);
					
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
			this.applications_info.push({"ready": false, "slide": -1, "index": index, "added": true});
			
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
			if (this.widgets && this.widgets.slideshow && data) {
				this.fillApplicationList(data);
			}
			return data;
		}
	});
 
	Supra.AppList = AppList;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget", "supra.slideshow", "dashboard.pagination", "transition", "dd"]});