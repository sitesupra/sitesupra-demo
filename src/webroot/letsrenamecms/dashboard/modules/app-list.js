YUI.add("dashboard.app-list", function (Y) {
 	//Invoke strict mode
	"use strict";
	
 	
 	var TEMPLATE_APPLICATION = Supra.Template.compile('\
			<li data-id="{{ id|escape }}" {% if active %}class="active"{% endif %}>\
				<a href="{{ path|escape }}" />\
					<span><img src="{{ icon|escape }}" alt="" /></span>\
					<label>{{ title|escape }}</label>\
				</a>\
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
			"value": 2
		},
		
		//Number of columns
		"columns": {
			"value": 5
		},
		
		//Single item width
		"itemWidth": {
			"value": 136
		},
		
		//Single item width
		"itemHeight": {
			"value": 136
		},
		
		// Current application id
		"value": {
			"value": null
		}
	};
	
	AppList.HTML_PARSER = {
		"title": function (srcNode) {
			var attr = srcNode.getAttribute("data-title");
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
		 * Application ID which is beeing dragged
		 * @type {String}
		 * @private
		 */
		"appDragging": null,
		
		/**
		 * Application ID before which dragged item is inserted
		 * @type {String}
		 * @private
		 */
		"appTarget": null,
		
 
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
			
			//Pagination
			var pagination = new Supra.Pagination();
			pagination.render(container);
			
			//Slideshow
			var slideshow = new Supra.Slideshow({
				"scrollable": false
			});
			
			slideshow.render(container);
			slideshow.get("boundingBox").setStyle("height", this.getSlideHeight());
			
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
			
			//Drag and drop
			var draggable = this.draggable = new Y.DD.Delegate({
				"container": this.widgets.slideshow.get("contentBox"),
				"nodes": "li",
				"target": true,
				"invalid": "input, select, button, textarea",
				"dragConfig": {
					"haltDown": false,
					"invalid": "input, select, button, textarea"
				}
			});
			
			draggable.dd.removeInvalid('a');
			
			draggable.dd.plug(Y.Plugin.DDProxy, {
				"moveOnEnd": false,
				"cloneNode": true
			});
			
			var target = this.target = new Y.DD.Drop({
				"node": this.widgets.slideshow.get("contentBox")
			});
			
			draggable.on("drag:start", this.onDragStart, this);
			draggable.on("drag:over", Supra.throttle(this.onDragOver, 16, this));
			draggable.on("drag:end", this.onDragEnd, this);
			draggable.on("drop:hit", this.onDrop, this);
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
			//Node
			var node = e.target.get("node"),
				data = node.getData("app");
			
			if (data) {
				this.appDragging = data;
				this.appTarget = null;
			}
			
			//Add classname to proxy element
	        var proxy = e.target.get("dragNode");
			proxy.addClass("app-list-proxy");
			
			Y.one('body').append(proxy);
		},
		
		/**
		 * Fires when draggable item is over another item
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDragOver: function (e) {
			var drag = e.drag.get("node"),
				drop = e.drop.get("node");
			
			if (drag && drop) {
				drag = drag.getData("app");
				drop = drop.getData("app");
				
				if (drag && drop && drag.id !== drop.id) {
					var drag_index = this.getApplicationIndex(drag.id),
						drop_index = this.getApplicationIndex(drop.id);
					
					if (drag_index != drop_index) {
						if (this.changeApplicationIndex(drag_index, drop_index)) {
							//Save drop ID
							if (drop_index + 1 < this.data.length) {
								this.appTarget = this.data[drop_index + 1];
							} else {
								this.appTarget = "";
							}
							
							//Animate items
							this.moveApplications();
						}
					}
				}
			}
		},
		
		/**
		 * Fires when dragable item from this list is droped
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDragEnd: function (e) {
			if (this.appTarget !== null) {
				this.fire("appmove", {
					"application": this.appDragging,
					"reference": this.appTarget
				});
				
				this.appDragging = null;
				this.appTarget = null;
			}
		},
		
		/**
		 * Fires when dragable item is droped on this list
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDrop: function (e) {
			this.moveApplications();
		},
		
		/**
		 * Reset drag and drop cache
		 * 
		 * @param {Boolean} clean Clean all cache
		 * @private
		 */
		resetDropCache: function (clean) {
			if (Y.DD.DDM.activeDrag) {
				if (clean === true) {
					Y.DD.DDM._activateTargets();
				} else {
					//Shim
		            Y.each(Y.DD.DDM.targets, function(v, k) {
		                v.sizeShim();
		            }, Y.DD.DDM);
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
				info = [],
				current = this.get("value"),
				content = null;
			
			applications = Y.Array.map(data, function (app, index) {
				info[index] = {"ready": false, "slide": -1, "index": index, "animating": false};
				
				var data = Supra.mix({
					"active": app.id === current
				}, app);
				
				var node = Y.Node.create(this.TEMPLATE_APPLICATION(data));
				
				node.setData("app", app);
				return node;
			}, this);
			
			this.data = data;
			this.applications = applications;
			this.applications_info = info;
			
			this.resizeSlideshow();
			
			content = this.widgets.slideshow.getSlide('slide_0')
			content.setStyle("opacity", 0);
			
			this.moveApplications();
			
			// Animate in all icons
			content
				.transition({
					"opacity": 1,
					"duration": 0.35
				});
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
								this.appFadeIn(applications[i], slides[slideIndex], position, false, info[i]);
							} else {
								//Fade out
								this.appFadeOut(applications[i], slides[slideIndex], position, info[i]);
							}
						} else {
							//Item changed from and to invisible slide
							this.appPlace(applications[i], slides[slideIndex], position, info[i]);
						}
					} else {
						//Only if slide or index changed
						if (columnCountChanged || info[i].slide != slideIndex || info[i].index != itemIndex) {
							//Animate if in visible slide
							this.appMove(applications[i], position, inVisibleSlide, info[i]);
						}
					}
				} else {
					//If not already in DOM
					if (info[i].added && inVisibleSlide) {
						this.appFadeIn(applications[i], slides[slideIndex], position, true, info[i]);
					} else {
						this.appPlace(applications[i], slides[slideIndex], position, info[i]);
					}
				}
				
				//Save info
				info[i].ready = true;
				info[i].slide = slideIndex;
				info[i].index = itemIndex;
			}
			
			//Update DND
			this.draggable.syncTargets();
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
		 * ---------------------------- SORTING -------------------------
		 */
		
		
		/**
		 * Get application index
		 * 
		 * @param {String} id Application id
		 * @private
		 */
		getApplicationIndex: function (id) {
			var data = this.data,
				ii = data.length,
				i = 0;
			
			for (; i<ii; i++) {
				if (data[i].id === id) return i;
			}
			
			return -1;
		},
		
		/**
		 * Move application from one place to another
		 * 
		 * @param {Number} from Index of item which will be moed
		 * @param {Number} to Index to move to
		 * @private
		 */
		changeApplicationIndex: function (from ,to) {
			var data = this.data,
				applications = this.applications,
				applications_info = this.applications_info,
				item = null;
			
			if (!applications_info[to].animating) {
				item = data[from];
				data.splice(from, 1);
				data.splice(to, 0, item);
				
				item = applications[from];
				applications.splice(from, 1);
				applications.splice(to, 0, item);
				
				item = applications_info[from];
				applications_info.splice(from, 1);
				applications_info.splice(to, 0, item);
				
				return true;
			}
			
			return false;
		},
		
		
		/**
		 * ---------------------------- ANIMATIONS -------------------------
		 */
		
		
		/**
		 * Fade out application
		 * 
		 * @private
		 */
		appFadeOut: function (app, container, position, info) {
			if (info) {
				info.animating = true;
			}
			
			app.transition({
				"opacity": 0,
				"duration": 0.35
			}, Y.bind(function () {
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
				
				if (info) {
					this.resetDropCache(true);
					info.animating = false;
				}
			}, this));
		},
		
		/**
		 * Fade in application
		 * 
		 * @private
		 */
		appFadeIn: function (app, container, position, added, info) {
			if (info) {
				info.animating = true;
			}
			
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
			app.transition(anim, Y.bind(function () {
				if (info) {
					this.resetDropCache(true);
					info.animating = false;
				}
			}, this));
		},
		
		/**
		 * Animate application from one position into another
		 * 
		 * @private
		 */
		appMove: function (app, position, animate, info) {
			if (animate) {
				if (info) {
					info.animating = true;
				}
				
				app.transition({
					"left": position[0] + "px",
					"top":  position[1] + "px",
					"duration": 0.35
				}, Y.bind(function () {
					if (info) {
						this.resetDropCache(true);
						info.animating = false;
					}
				}, this));
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