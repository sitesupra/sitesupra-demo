YUI.add("dashboard.pagination", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Pagination navigation
	 */
	function Pagination (config) {
		Pagination.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Pagination.NAME = "pagination";
	Pagination.CSS_PREFIX = 'su-' + Pagination.NAME;
	Pagination.CLASS_NAME = Y.ClassNameManager.getClassName(Pagination.NAME);
 
	Pagination.ATTRS = {
		//Total number of pages
		"total": {
			"value": 1,
			"setter": "_setTotal"
		},
		//Selected page index
		"index": {
			"value": 0,
			"setter": "_setIndex"
		},
		//Style
		"style": {
			"value": "",
			"setter": "_setStyle"
		}
	};
 
	Y.extend(Pagination, Y.Widget, {
 
		/**
		 * Create/add nodes, render widgets
		 *
		 * @private
		 */
		renderUI: function () {
			Pagination.superclass.renderUI.apply(this, arguments);
			//...
		},
 
		/**
		 * Attach event listeners
		 *
		 * @private
		 */
		bindUI: function () {
			Pagination.superclass.bindUI.apply(this, arguments);
			
			this.get("contentBox").delegate("click", Y.bind(this.itemClick, this), "a");
		},
		
		
		/**
		 * ---------------------------- ITEMS -------------------------
		 */
		
		/**
		 * Render items
		 */
		renderItems: function (count) {
			var container = this.get("contentBox"),
				nodes = container.all("a"),
				prev  = nodes.size();
			
			if (prev < count) {
				for (var i=prev; i<count; i++) {
					container.append(Y.Node.create("<a data-id='" + i + "'></a>"));
				}
			} else if (prev > count) {
				for (var i=prev - 1; i >= count; i--) {
					nodes.item(i).remove();
				}
			}
		},
		
		/**
		 * On item click update index
		 */
		itemClick: function (e) {
			var index = e.target.closest("a").getAttribute("data-id");
			index = parseInt(index, 10);
			
			this.set("index", index);
			
			e.halt();
		},
		
 
 
		/**
		 * ---------------------------- API -------------------------
		 */
 
 
		//API functions
 
 
		/**
		 * ---------------------------- ATTRIBUTES -------------------------
		 */
 
 
		/**
		 * Total number of pages attribute setter
		 * 
		 * @param {Number} value New total number of pages
		 * @return New total number of pages
		 * @type {Number}
		 * @private
		 */
		_setTotal: function (total) {
			total = Math.max(1, total);
			
			if (total != this.get("total")) {
				this.renderItems(total);
				
				var index = this.get("index");
				if (index >= total) {
					this.set("index", total - 1);
				}
				
				if (total > 1) {
					this.set("visible", true);
				} else {
					this.set("visible", false);
				}
			}
			
			return total;
		},
 
		/**
		 * Selected page index attribute setter
		 * 
		 * @param {Number} value New selected page index
		 * @return New selected page index
		 * @type {Number}
		 * @private
		 */
		_setIndex: function (index) {
			
			var nodes = this.get("contentBox").all("a"),
				prev = this.get("index"),
				node = nodes.item(prev);
			
			if (node) node.removeClass("active");
			node = nodes.item(index);
			if (node) node.addClass("active");
			
			return index;
		},
 
		/**
		 * Style attribute setter
		 * 
		 * @param {String} value New style
		 * @return New style
		 * @type {String}
		 * @private
		 */
		_setStyle: function (style) {
			return style;
		}
	});
 
	Supra.Pagination = Pagination;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget"]});