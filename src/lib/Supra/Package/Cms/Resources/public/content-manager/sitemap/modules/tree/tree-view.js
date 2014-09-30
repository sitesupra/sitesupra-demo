//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-view', function (Y) {
	
	//Constants
	var SPACING = {
		'left': 60,
		'right': 60,
		'bottom': 0
	};
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	
	function TreeView(config) {
		var attrs = {
			//Tree 
			'tree': {'value': null},
			
			//Source node (Tree)
			'srcNode': {'value': null},
			
			//Content box
			'contentBox': {'value': null},
			
			//Center node
			'centerNode': {
				'value': Y.Node.create('<div class="su-treeview-center"></div>')
			},
			
			//Disabled
			'disabled': {'value': false},
			
			//Additional tree spacing
			'spacingLeft': {
				'value': 0,
				'setter': '_setSpacing'
			},
			'spacingRight': {
				'value': 0,
				'setter': '_setSpacing'
			},
			'spacingBottom': {
				'value': 0,
				'setter': '_setSpacing'
			},
			
			//Arrows
			'arrowUpNode': {
				'value': Y.Node.create('<a class="su-treeview-up hidden"></a>')
			},
			'arrowLeftNode': {
				'value': Y.Node.create('<a class="su-treeview-left hidden"></a>')
			},
			'arrowRightNode': {
				'value': Y.Node.create('<a class="su-treeview-right hidden"></a>')
			},
			'shadowLeftNode': {
				'value': Y.Node.create('<div class="su-treeview-fade-left hidden"></div>')
			},
			'shadowRightNode': {
				'value': Y.Node.create('<div class="su-treeview-fade-right hidden"></div>')
			},
			
			//Animation is in progress
			'animating': {
				'value': false
			}
		};
		
		this.addAttrs(attrs, config || {});
		
		//Clone object to break reference
		this.dnd = Supra.mix({}, this.dnd, true);
		
		this.renderUI();
		this.bindUI();
	}
	
	TreeView.NAME = 'TreeView';
	
	TreeView.prototype = {
		
		/**
		 * View animation objects
		 * @type {Object}
		 * @private
		 */
		'viewAnimX': null,
		'viewAnimY': null,
		
		/**
		 * Scroll position
		 * @type {Number}
		 * @private
		 */
		'viewScrollLeft': -2500,
		'viewScrollTop': 0,
		
		/**
		 * Arrow up drop target
		 * @type {Object}
		 * @private
		 */
		'dropArrowUp': null,
		
		/**
		 * Arrow left drop target
		 * @type {Object}
		 * @private
		 */
		'dropArrowLeft': null,
		
		/**
		 * Arrow right drop target
		 * @type {Object}
		 * @private
		 */
		'dropArrowRight': null,

		/**
		 * Timer for sitemap level up transition
		 * @type {Object}
		 * @private
		 */
		'_timer': null,


		/**
		 * Add needed elements, etc.
		 * 
		 * @private
		 */
		'renderUI': function () {
			var srcNode = this.get('srcNode'),
				contentBox = srcNode.ancestor(),
				boundingBox = contentBox.get('parentNode');
			
			contentBox.append(this.get('centerNode'));
			boundingBox.append(this.get('arrowUpNode'));
			boundingBox.append(this.get('arrowLeftNode'));
			boundingBox.append(this.get('arrowRightNode'));
			boundingBox.closest('.su-sitemap').append(this.get('shadowLeftNode'));
			boundingBox.closest('.su-sitemap').append(this.get('shadowRightNode'));
			
			this.set('contentBox', contentBox);
		},
		
		/**
		 * Add event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			this.get('tree').after('load', function () {
				this.set('disabled', false);
				this.resetCenter();
			}, this);
			
			this.get('tree').after('visibilityRootNodeChange', function (e) {
				if (e.prevVal !== e.newVal) {
					if (e.newVal) {
						var region = this.getNodeRegion(e.newVal.get('boundingBox')),
							offset = e.newVal.get('root') ? 80 : 110;	//root node is smaller
						
						this.scrollY(-region.top - offset);
						this.get('arrowUpNode').removeClass('hidden');
					} else {
						this.scrollY(0);
						this.get('arrowUpNode').addClass('hidden').removeClass('yui3-dd-drop-over');
					}
				}
			}, this);
			
			this.get('arrowLeftNode').on('mousedown', function (e) {
				if (e.button == 1 && !this.get('disabled')) this.scrollLeft();
			}, this);
			
			this.get('arrowRightNode').on('mousedown', function (e) {
				if (e.button == 1 && !this.get('disabled')) this.scrollRight();
			}, this);
			this.get('arrowUpNode').on('mousedown', function (e) {
				if (!this.get('disabled')) this.get('tree').visibilityRootNodeUp(e);
			}, this);
			
			//On resize show/hide arrows
			Y.on('resize', Y.throttle(Y.bind(this.checkOverflow, this), 50), window);
			
			this.bindDnD();
		},
		
		/**
		 * Bind drag over arrows
		 * 
		 * @private
		 */
		'bindDnD': function () {
			var groups = ['default', 'new-page', 'restore-page', 'new-template', 'restore-template', 'new-group', 'new-application'];
			
			//Up
			this.dropArrowUp = new Y.DD.Drop({
				'node': this.get('arrowUpNode'),
				'groups': groups
			});

			this.dropArrowUp.on('drop:hit', function (e) { this._cancelTimer(); e.halt(); }, this);
			this.dropArrowUp.on('drop:exit', this._cancelTimer, this);
			this.dropArrowUp.on('drop:enter', function (e) { 
				this._cancelTimer();

				if (this.get('disabled')) {
					e.halt();
					return;
				}
				
				var tree = this.get('tree'),
					root = tree.get('visibilityRootNode');
				
				if (root) {
					this._timer = Y.later(600, this, function(e, tree) {
						tree.visibilityRootNodeUp(e);
						this.onArrowScroll(e);
					}, [e, tree], true);
				}
			}, this);
			
			//Left
			this.dropArrowLeft = new Y.DD.Drop({
				'node': this.get('arrowLeftNode'),
				'groups': groups
			});
			this.dropArrowLeft.on('drop:hit', function (e) { this._cancelTimer(); e.halt(); }, this);
			this.dropArrowLeft.on('drop:exit', this._cancelTimer, this);
			this.dropArrowLeft.on('drop:enter', function (e) {
				this._cancelTimer();

				if (this.get('disabled')) {
					e.halt();
					return;
				}
				
				this._timer = Y.later(300, this, function(e) {
					this.scrollLeft();
					this.onArrowScroll(e);
				}, [e], true);
			}, this);
			
			//Right
			this.dropArrowRight = new Y.DD.Drop({
				'node': this.get('arrowRightNode'),
				'groups': groups
			});
			this.dropArrowRight.on('drop:hit', function (e) { this._cancelTimer(); e.halt(); }, this);
			this.dropArrowRight.on('drop:exit', this._cancelTimer, this);
			this.dropArrowRight.on('drop:enter', function (e) {
				this._cancelTimer();

				if (this.get('disabled')) {
					e.halt();
					return;
				}
				
				this._timer = Y.later(300, this, function(e) {
					this.scrollRight();
					this.onArrowScroll(e);
				}, [e], true);
			}, this);
		},
		
		/**
		 * On arrow scroll reset marker
		 */
		'onArrowScroll': function (e) {
			var node = e.drag.get('treeNode');
			if (node) {
				node.hideDropMarker();
			}
				
			e.halt();
			e.stopImmediatePropagation();
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Returns center position relative to the screen
		 * This is used to calculate getNodeRegion
		 * 
		 * @return Array where first item is left position and second is top position relative to the screen
		 * @type {Array}
		 * @private
		 */
		'getCenterPosition': function () {
			var node = this.get('centerNode');
			return node.getXY();
		},
		
		/**
		 * Returns node region where left and top are relative to the center of the view
		 * 
		 * @return Region object 
		 * @type {Object}
		 * @private
		 */
		'getNodeRegion': function (node) {
			var region = node.get('region'),
				center = this.getCenterPosition();
			
			region.left -= center[0];
			region.top -= center[1];
			region.right -= center[0];
			region.bottom -= center[1];
			
			return region;
		},
		
		/**
		 * Returns visible region
		 * 
		 * @return Visible region
		 * @type {Object}
		 * @private
		 */
		'getVisibleRegion': function () {
			var tree = this.get('tree'),
				root = tree.get('visibilityRootNode'),
				region = null;
			
			if (root) {
				// node may not be in dom yet, may happen during rendering
				// and region can't be retrieved in that case
				region = this.getChildRegion(root);
			}
			if (!region) {
				region = this.getTreeRegion();
			}
			
			return region;
		},
		
		/**
		 * Returns tree region
		 * 
		 * @return Object with keys 'left', 'top', 'width', 'height'
		 * @type {Object}
		 * @private
		 */
		'getTreeRegion': function () {
			var tree = this.get('tree'),
				
				region = this.getNodeRegion(tree.get('boundingBox')),
				
				i = 0,
				children	= tree.size(),
				children_region = null;
			
			for(; i<children; i++) {
				if (tree.item(i).get('expanded')) {
					children_region = this.getChildRegion(tree.item(i));
					
					if (children_region) {
						region.left   = Math.min(region.left, children_region.left);
						region.top    = Math.min(region.top, children_region.top);
						region.right  = Math.max(region.right, children_region.right);
						region.bottom = Math.max(region.bottom, children_region.bottom);
					}
				}
			}
			
			region.width = region.right - region.left;
			region.height = region.bottom - region.top;
			
			return region;
		},
		
		/**
		 * Returns tree node children region
		 * 
		 * @param {String} id Tree node or tree node ID
		 * @return Object with keys 'left', 'top', 'width', 'height'
		 * @type {Object}
		 * @private
		 */
		'getChildRegion': function (id) {
			var tree = this.get('tree'),
				node = tree.item(id),
				
				nodeBox = node.get('childrenBox'),
				region = null,
				
				i = 0,
				children = node.size(),
				children_region = null;
			
			if (node.get('expanded')) {
				region = this.getNodeRegion(nodeBox);
				
				for(; i<children; i++) {
					if (node.item(i).get('expanded')) {
						children_region = this.getChildRegion(node.item(i));
						
						if (children_region) {
							region.left   = Math.min(region.left, children_region.left);
							region.top    = Math.min(region.top, children_region.top);
							region.right  = Math.max(region.right, children_region.right);
							region.bottom = Math.max(region.bottom, children_region.bottom);
						}
					}
				}
				
				region.width  = region.right - region.left;
				region.height = region.bottom - region.top;
			}
			
			return region;
		},
		
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Check content overflow
		 */
		'checkOverflow': function () {
			if (this.get('disabled')) return;
			
			var scrollLeft = this.viewScrollLeft + 2500,
				region = this.getVisibleRegion(),
				
				boundingBox = this.get('contentBox').ancestor(),
				viewWidth  = boundingBox.get('offsetWidth'),
				viewHeight = boundingBox.get('offsetHeight'),
				
				arrowLeft  = this.get('arrowLeftNode'),
				arrowRight = this.get('arrowRightNode'),
				
				shadowLeft = this.get('shadowLeftNode'),
				shadowRight = this.get('shadowRightNode');
			
			if (region.left + scrollLeft < - viewWidth / 2 + SPACING.left) {
				arrowLeft.removeClass('hidden');
				shadowLeft.removeClass('hidden');
			} else {
				arrowLeft.addClass('hidden').removeClass('yui3-dd-drop-over');
				shadowLeft.addClass('hidden');
			}
			
			if (region.right + scrollLeft > viewWidth / 2 - SPACING.right) {
				arrowRight.removeClass('hidden');
				shadowRight.removeClass('hidden');
			} else {
				arrowRight.addClass('hidden').removeClass('yui3-dd-drop-over');
				shadowRight.addClass('hidden');
			}
		},
		
		/**
		 * Scroll view left
		 */
		'scrollLeft': function () {
			var scrollLeft = this.viewScrollLeft + 2500,
				region = this.getVisibleRegion(),
				
				boundingBox = this.get('contentBox').ancestor(),
				viewWidth  = boundingBox.get('offsetWidth'),
				viewHeight = boundingBox.get('offsetHeight');
			
			scrollLeft = Math.min(scrollLeft + 240, - viewWidth / 2 - region.left + SPACING.left) - 2500;
			
			this.scrollX(scrollLeft);
		},
		
		/**
		 * Scroll view right
		 */
		'scrollRight': function () {
			var scrollLeft = this.viewScrollLeft + 2500,
				region = this.getVisibleRegion(),
				
				boundingBox = this.get('contentBox').ancestor(),
				viewWidth  = boundingBox.get('offsetWidth'),
				viewHeight = boundingBox.get('offsetHeight');
			
			scrollLeft = Math.max(scrollLeft - 240, viewWidth / 2 - region.left - region.width - SPACING.right) - 2500;
			
			this.scrollX(scrollLeft);
		},
		
		/**
		 * Scroll to X position
		 */
		'scrollX': function (posX, callback, context) {
			if (!this.viewAnimX) {
				this.viewAnimX = new Y.Anim({
					'node': this.get('contentBox'),
					'easing': Y.Easing.easeOutStrong,
					'duration': 0.35,
					'to': {
						'marginLeft': posX
					}
				});
				
				this.viewAnimX.on('end', function () {
					this.set('animating', false);
				}, this);
			} else {
				this.viewAnimX.stop();
				this.viewAnimX.set('to', {'marginLeft': posX});
			}
			
			this.viewScrollLeft = posX;
			
			//Attribute
			this.set('animating', true);
			
			//Update arrows
			this.viewAnimX.once('end', this.checkOverflow, this);
			
			//Update drop target regions
			this.viewAnimX.once('end', this.resetDropCache, this);
			
			if (typeof callback === 'function') {
				this.viewAnimX.once('end', callback, context || this);
			}
			
			this.viewAnimX.run();
		},
		
		/**
		 * Scroll to Y position
		 */
		'scrollY': function (posY, callback, context) {
			if (!this.viewAnimY) {
				this.viewAnimY = new Y.Anim({
					'node': this.get('contentBox'),
					'easing': Y.Easing.easeOutStrong,
					'duration': 0.35,
					'to': {
						'marginTop': posY
					}
				});
				
				this.viewAnimY.on('end', function () {
					this.set('animating', false);
				}, this);
			} else {
				this.viewAnimY.stop();
				this.viewAnimY.set('to', {'marginTop': posY});
			}
			
			this.viewScrollTop = posY;
			
			if (typeof callback === 'function') {
				this.viewAnimY.once('end', callback, context || this);
			}
			
			//Attribute
			this.set('animating', true);
			
			//Update drop target regions
			this.viewAnimY.once('end', this.resetDropCache, this);
			
			this.viewAnimY.run();
		},
		
		/**
		 * Reset view position
		 */
		'resetCenter': function () {
			this.get('contentBox').setStyles({
				'marginLeft': -2500,
				'marginTop': 0
			});
			
			this.viewScrollLeft = -2500;
			this.viewScrollTop = 0;
			
			this.get('arrowUpNode').addClass('hidden');
			this.get('arrowLeftNode').addClass('hidden');
			this.get('arrowRightNode').addClass('hidden');
		},
		
		/**
		 * Center item
		 * 
		 * @param {Object} id TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function execution context, optional
		 */
		'center': function (id, callback, context) {
			if (this.get('disabled')) return;
			
			var contentBox = this.get('contentBox'),
				
				tree = this.get('tree'),
				treeBox = tree.get('boundingBox'),
				
				posX = 0,
				
				item = tree.item(id),
				node = null;
			
			if (item.get('expanded')) {
				node = item.get('childrenBox');
			} else {
				node = item.get('boundingBox');
			}
			
			posX = ~~(node.getXY()[0] - treeBox.getXY()[0] + node.get('offsetWidth') / 2 - treeBox.get('offsetWidth') / 2);
			posX = -2500 - posX;
			
			this.scrollX(posX, callback, context);
		},
		
		/**
		 * Reset drag and drop cache
		 * 
		 * @param {Boolean} clean Clean all cache
		 */
		'resetDropCache': function (clean) {
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
		 * ------------------------------ ATTRIBUTES ------------------------------
		 */
		
		/**
		 * spacingLeft, spacingRight and spacingBottom attribute setter
		 * 
		 * @param {Number} value Number of pixels
		 * @return Number of pixels
		 * @type {Number}
		 * @private
		 */
		'_setSpacing': function (px) {
			return parseInt(px, 10) || 0;
		},

		/**
		 * Removes tree arrow scroll execution timer
		 *
		 * @private
		 */
		'_cancelTimer': function () {
			if (this._timer) {
				this._timer.cancel();
				this._timer = null;
			}
		}
	};
	
	Y.augment(TreeView, Y.Attribute);
	
	Action.TreeView = TreeView;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree', 'anim']});