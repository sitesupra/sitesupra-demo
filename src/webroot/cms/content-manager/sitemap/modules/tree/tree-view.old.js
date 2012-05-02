//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-view', function (Y) {
	
	//Constants
	var SPACING = {
		'left': 200,
		'right': 0,
		'bottom': 60
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
		 * Drag and drop information
		 * @type {Object}
		 * @private
		 */
		'dnd': {
			'dragging': false,
			'mouseStartX': 0,
			'mouseStartY': 0,
			'viewStartX': 0,
			'viewStartY': 0,
			'constrains': null
		},
		
		/**
		 * View animation object
		 * @type {Object}
		 * @private
		 */
		'viewAnim': null,
		
		
		
		/**
		 * Add needed elements, etc.
		 * 
		 * @private
		 */
		'renderUI': function () {
			var srcNode = this.get('srcNode'),
				contentBox = srcNode.ancestor();
			
			this.set('contentBox', contentBox);
		},
		
		/**
		 * Add event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			var contentBox = this.get('contentBox');
			
			contentBox.on('mousedown', this.dragStart, this);
			
			this.get('tree').after('loadingChange', function (e) {
				if (e.newVal) this.resetCenter();
			}, this);
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * On drag start save current view and mouse positions
		 * 
		 * @private 
		 */
		'dragStart': function (e) {
			//Disabled, can't move
			if (this.get('disabled')) return;
			
			//Left or middle mouse button only
			if (e.button != 1 && e.button != 2) return;
			
			var target = e.target;
			
			//Stop previous drag
			if (this.dnd.dragging) {
				this.dragEnd();
			}
			
			//Only if event didn't originated inside tree
			if (target.closest('.su-tree-node')) return;
			
			var dnd 		= this.dnd,
				contentBox 	= this.get('contentBox');
			
			dnd.constrains = this.getConstrains();
			
			if (dnd.constrains[0][0] == dnd.constrains[0][1] && dnd.constrains[1][0] == dnd.constrains[1][1]) {
				//Can't move
				e.halt();
				return;
			}
			
			dnd.dragging = true;
			dnd.mouseStartX = e.clientX;
			dnd.mouseStartY = e.clientY;
			dnd.viewStartX = parseInt(contentBox.getStyle('margin-left') || 0, 10);
			dnd.viewStartY = parseInt(contentBox.getStyle('margin-top') || 0, 10);
			dnd.mouseEventMove = contentBox.on('mousemove', Y.throttle(Y.bind(this.dragMove, this), 16));
			dnd.mouseEventUp = Y.one(document).on('mouseup', this.dragEnd, this);
			
			contentBox.addClass('su-sitemap-dragging');
			
			//Hide popups
			this.get('tree').page_edit.hide();
			
			e.halt();
		},
		
		'dragMove': function (e) {
			var dnd = this.dnd,
				contentBox = this.get('contentBox'),
				diffX = e.clientX - dnd.mouseStartX,
				diffY = e.clientY - dnd.mouseStartY,
				
				posX = dnd.viewStartX + diffX,
				posY = dnd.viewStartY + diffY;
			
			posX = Math.min(Math.max(posX, dnd.constrains[0][0]), dnd.constrains[0][1]);
			posY = Math.min(Math.max(posY, dnd.constrains[1][0]), dnd.constrains[1][1]);
			
			contentBox.setStyles({
				'margin-left': posX,
				'margin-top': posY
			});
		},
		
		'dragEnd': function () {
			var dnd = this.dnd;
			
			if (dnd.dragging) {
				dnd.mouseEventMove.detach();
				dnd.mouseEventMove = null;
				dnd.mouseEventUp.detach();
				dnd.mouseEventUp = null;
				dnd.dragging = false;
				
				var contentBox = this.get('contentBox');
				contentBox.removeClass('su-sitemap-dragging');
			}
		},
		
		/**
		 * Returns constrains
		 * 
		 * @return Constrains object
		 * @type {Object}
		 * @private
		 */
		'getConstrains': function () {
			var contentBox 	= this.get('contentBox'),
				boundingBox = contentBox.ancestor(),
				
				areaWidth	= contentBox.get('offsetWidth'),
				viewWidth 	= this.viewWidth = boundingBox.get('offsetWidth'),
				viewHeight 	= boundingBox.get('offsetHeight'),
				centerX 	= areaWidth / 2,
				
				treeRegion	= this.getTreeRegion(),
				treeLeft	= centerX - treeRegion.left,
				treeRight	= treeRegion.left + treeRegion.width - centerX,
				treeWidth 	= treeRegion.width,
				treeHeight 	= treeRegion.height;
			
			return [
				[//X: min, max
					Math.min(-viewWidth / 2, treeRegion.centerX - (treeRegion.left + treeRegion.width)) + viewWidth / 2 - centerX,
					Math.max(viewWidth / 2, treeRegion.centerX - treeRegion.left) - viewWidth / 2 - centerX
				],
				[//Y: min, max
					- Math.max(viewHeight, treeHeight) + viewHeight,
					0
				]
			];
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
				i = 0,
				children	= tree.size(),
				region		= tree.get('boundingBox').get('region'),
				left		= region.left,
				top			= region.top,
				right		= region.left + region.width,
				bottom		= region.top + region.height,
				centerX		= region.left + region.width / 2;
			
			for(; i<children; i++) {
				region = this.getChildRegion(tree.item(i));
				if (region) {
					left   = Math.min(left, region.left);
					top    = Math.min(top, region.top);
					right  = Math.max(right, region.right);
					bottom = Math.max(bottom, region.bottom);
				}
			}
			
			return {
				'left': left - SPACING.left - this.get('spacingLeft'),
				'top': top,
				'width': right - left + SPACING.left + SPACING.right + this.get('spacingRight'),
				'height': bottom - top + SPACING.bottom + this.get('spacingBottom'),
				'centerX': centerX
			};
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
				region = null,
				pos = null,
				height = null,
				width = null,
				i = 0,
				children = node.size(),
				children_region = null;
			
			if (node.get('expanded')) {
				pos = node.get('childrenBox').getXY();
				width = node.get('childrenBox').get('offsetWidth');
				height = node.get('childrenBox').get('offsetHeight');
				
				region = {
					'left': Math.min(pos[0], pos[0] + width / 2 - this.viewWidth / 2),
					'top': pos[1],
					'right': Math.max(pos[0] + width, pos[0] + width / 2 + this.viewWidth / 2),
					'bottom': pos[1] + height
				};
				
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
			}
			
			return region;
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Returns true if item is fully visible, otherwise false
		 * 
		 * @param {String} id Tree node or tree node ID
		 * @return True if node is fully visible
		 * @type {Boolean}
		 */
		'isVisible': function (id) {
			//var node = this.get('tree').item(id);
		},
		
		/**
		 * Reset view position
		 */
		'resetCenter': function () {
			this.get('contentBox').setStyles({
				'margin-left': -2500,
				'margin-top': 0
			});
		},
		
		/**
		 * Center item
		 * 
		 * @param {Object} id TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function execution context, optional
		 */
		'center': function (id, callback, context) {
			var constrains = this.getConstrains(),
				contentBox = this.get('contentBox'),
				
				tree = this.get('tree'),
				treeBox = tree.get('boundingBox'),
				
				posX = constrains[0][0],
				posY = constrains[1][0],
				
				item = tree.item(id),
				node = null;
			
			if (item.get('expanded')) {
				node = item.get('childrenBox');
			} else {
				node = item.get('boundingBox');
			}
			
			posX = ~~(node.getXY()[0] - treeBox.getXY()[0] + node.get('offsetWidth') / 2 - treeBox.get('offsetWidth') / 2);
			posX = -2500 - posX;
			
			if (parseInt(contentBox.getStyle('margin-left'), 10) == posX && parseInt(contentBox.getStyle('margin-top'), 10) == posY) {
				//Already centered
				if (Y.Lang.isFunction(callback)) {
					callback.call(context || this);
				}
				return;
			}
			
			if (!this.viewAnim) {
				this.viewAnim = new Y.Anim({
					'node': contentBox,
					'easing': Y.Easing.easeOutStrong,
					'duration': 0.35,
					'to': {
						'margin-left': posX,
						'margin-top': posY
					}
				});
			} else {
				this.viewAnim.stop();
				this.viewAnim.set('to', {
					'margin-left': posX,
					'margin-top': posY
				});
			}
			
			if (callback) {
				this.viewAnim.once('end', callback, context || this);
			}
			
			this.viewAnim.run();
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
		}
	};
	
	Y.augment(TreeView, Y.Attribute);
	
	Action.TreeView = TreeView;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree']});