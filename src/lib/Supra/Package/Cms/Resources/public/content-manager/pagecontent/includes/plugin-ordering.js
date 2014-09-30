YUI.add("supra.page-content-ordering", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		PageContent = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_REORDER = Y.ClassNameManager.getClassName("content", "reorder"),		//yui3-content-reorder
		CLASSNAME_DRAGING = Y.ClassNameManager.getClassName("content", "draging"),		//yui3-content-draging
		CLASSNAME_PROXY = Y.ClassNameManager.getClassName("content", "proxy"),			//yui3-content-proxy
		CLASSNAME_DRAGGABLE = 'su-overlay-draggable',
		CLASSNAME_OVERLAY = 'su-overlay';
	
	/**
	 * This is plugin for Supra.Manager.PageContent.IframeContents to enable block ordering
	 * and changing block container
	 */
	
	function PluginOrdering (config) {
		PluginOrdering.superclass.constructor.apply(this, arguments);
	}
	
	PluginOrdering.NAME = "page-content-ordering";
	PluginOrdering.NS = "order";
	
	PluginOrdering.ATTRS = {
		
	};
	
	Y.extend(PluginOrdering, Y.Plugin.Base, {
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.bindDragAndDrop();
		},
		
		/**
		 * Automatically called by Base, during destruction
		 */
		destructor: function () {
			if (this.dragDelegate) {
				this.dragDelegate.destroy();
				this.dragDelegate = null;
			}
		},
		
		
		/* ---------------------------- DRAG AND DROP --------------------------- */
		
		
		/**
		 * CSS selector for draggable nodes
		 * @type {String}
		 * @private
		 */
		dragSelector: "",
		
		/**
		 * Drag object, Y.DD.Delegate instance
		 * @type {Object}
		 * @private
		 */
		dragDelegate: null,
		
		/**
		 * List block ID to which dragged block belongs to
		 * @type {String}
		 * @private
		 */
		dragOriginalList: null,
		
		/**
		 * List block ID into which block is dragged into
		 * Matches dragOriginalList if only reordering
		 * @type {String}
		 * @private
		 */
		dragTargetList: null,
		
		/**
		 * Order has changed
		 * @type {Boolean}
		 * @private
		 */
		dragOrderChanged: false,
		
		/**
		 * Block was dragged to another list
		 * @type {Boolean}
		 * @private
		 */
		dragListChanged: false,
		
		/**
		 * Block ID which is dragged
		 * @type {String}
		 * @private
		 */
		dragBlockId: null,
		
		/**
		 * Block index which is dragged
		 * @type {Number}
		 * @private
		 */
		dragBlockIndex: null,
		
		/**
		 * Block index before dragging
		 * @type {Number}
		 * @private
		 */
		dragOriginalBlockIndex: null,
		
		/**
		 * Block region which is dragged
		 * @type {Object}
		 * @private
		 */
		dragBlockRegion: null,
		
		/**
		 * Dragging is active
		 * @type {Boolean}
		 * @private
		 */
		dragBlockActive: false,
		
		/**
		 * Highlight mode before user started dragging
		 * @type {String}
		 * @private
		 */
		highlightModeBeforeDrag: null,
		
		/**
		 * Scroll offset created by highlight mode change
		 * @type {Number}
		 * @private
		 */
		highlightScrollOffset: 0,
		
		
		/**
		 * Bind drag and drop
		 * 
		 * @private
		 */
		bindDragAndDrop: function () {
			var container = this.get("host").get("body"),
				selector = "div." + CLASSNAME_DRAGGABLE;
			
			this.dragSelector = selector;
			this.dragBlockActive = false;
			
			var del = this.dragDelegate = new Y.DD.Delegate({
				"container": container,
				"nodes": selector,
				"target": true,
				"dragConfig": {
					"useShim": false, // shim + iframe scroll creates an offset 
					"haltDown": false,
					"clickTimeThresh": 1000
				}
			});
			
			del.dd.plug(Y.Plugin.DDProxy, {
				"moveOnEnd": false,
				"cloneNode": true,
				"resizeFrame": false
			});
			
			del.on("drag:start", this.onDragStart, this);
			del.on("drag:end", this.onDragEnd, this);
			
			//Use throttle, because drag event executes very often and affects performance
			del.on("drag:drag", Y.throttle(Y.bind(this.onDragDrag, this), 50));
			
			//When blocks are added or removed sync drag and drop
			//this.afterHostMethod("createChildren", this.dragDelegate.syncTargets, this.dragDelegate);
			this.afterHostMethod("createChildren", this.dragDelegate.syncTargets, this.dragDelegate);
		},
		
		/**
		 * Handle drag start event
		 * 
		 * @param {Object} e Drag:start event facade object
		 * @private
		 */
		onDragStart: function (e) {
			var listRegions = this.getListRegions(),
				i = 0,
				ii = listRegions.length,
				block,
				
				overlay = e.target.get("node"),
				node = e.target.get("node").next(),
				xy = node.getXY(),
				
				iframe   = this.get('host').get('iframe'),
				scroll   = iframe.getScroll(),
				position = [0, 0];
			
			//Save element position relative to screen position
			position[0] = xy[0] - scroll[0];
			position[1] = xy[1] - scroll[1];
			
			//Add classname to lists
			for (; i<ii; i++) {
				block = this.get("host").getChildById(listRegions[i].id);
				block.getNode().addClass(CLASSNAME_REORDER);
			}
			
			this.highlightModeBeforeDrag = this.get('host').get('highlightMode');
			this.get('host').set('highlightMode', 'order');
			
			//Add classname to proxy element
	        var proxy = e.target.get("dragNode");
			proxy.addClass(CLASSNAME_PROXY);
			
			//Move to the body to prevent overflow: hidden from hiding it
			this.get("host").get("body").append(proxy);
			
			//Add classname to item which is dragged (not proxy)
			//to mark item which is dragged
			node.addClass(CLASSNAME_DRAGING);
			
			this.resetRegionsCache();
			
			//Find block ID, index and list
			var blockRegionsOrder = this.getBlockRegionsOrder(),
				blockId = overlay.getDOMNode()._contentBlockId,
				blockIndex = -1,
				blockRegion = null,
				
				listId = "",
				blocks = [],
				i = 0,
				ii = 0;
			
			if (!blockId) {
				Y.DD.DDM.activeDrag.stopDrag();
				e.halt();
				return;
			}
			
			for (listId in blockRegionsOrder) {
				blocks = blockRegionsOrder[listId];
				i = 0;
				ii = blocks.length;
				
				for (; i<ii; i++) {
					if (blocks[i].id == blockId) {
						blockIndex = i;
						blockRegion = blocks[i].region;
						break;
					}
				}
				
				if (blockIndex != -1) break;
			}
			
			this.dragBlockId = blockId;
			this.dragBlockRegion = blockRegion;
			this.dragBlockIndex = this.dragOriginalBlockIndex = blockIndex;
			this.dragOriginalList = this.dragTargetList = listId;
			
			// After small delay enable dragging, because we don't want swap to happen
			// immediatelly on drag start
			Y.later(250, this, function () {
				this.dragBlockActive = true;
			});
			
			// Change scroll position to make sure element is relatively to screen at
			// the same position as it was before
			xy = node.getXY();
			position[0] = xy[0] - position[0];
			position[1] = xy[1] - position[1];
			
			this.highlightScrollOffset = [
				position[0] - scroll[0],
				position[1] - scroll[1]
			];
			
			iframe.setScroll(position);
		},
		
		/**
		 * Handle drag end event
		 * 
		 * @param {Object} e Drag:end event facade object
		 * @private
		 */
		onDragEnd: function (e) {
			var listRegions = this.getListRegions(),
				i = 0,
				ii = listRegions.length,
				block = null,
				
				iframe   = this.get('host').get('iframe'),
				scroll = null;
			
			//Remove classname from lists
			for (; i<ii; i++) {
				block = this.get("host").getChildById(listRegions[i].id);
				block.getNode().removeClass(CLASSNAME_REORDER);
			}
			
			this.get('host').set('highlightMode', this.highlightModeBeforeDrag);
			
			//Remove classname from node which was dragged (not proxy node)
			var node = e.target.get("node").next();
			if (node) node.removeClass(CLASSNAME_DRAGING);
			
			var dragListChanged = this.dragListChanged && this.dragOriginalList != this.dragTargetList,
				dragOrderChanged = this.dragOrderChanged && this.dragOriginalBlockIndex != this.dragBlockIndex;
			
			//Save new order
			if (dragListChanged) {
				var order = this.blockRegionsOrder[this.dragTargetList],
					block = this.get("host").getChildById(this.dragBlockId);
				
				order = Y.Array.map(order, function (item) {
					return item.id;
				});
				
				this.fire("listChange", {
					"order": order,
					"block": block
				});
				
			} else if (dragOrderChanged) {
				var order = this.blockRegionsOrder[this.dragTargetList],
					block = this.get("host").getChildById(this.dragTargetList);
				
				order = Y.Array.map(order, function (item) {
					return item.id;
				});
				
				this.fire("orderChange", {
					"order": order,
					"block": block
				});
			}
			
			this.dragBlockActive = false;
			
			//Update scroll position to compensate for placeholder size change due to
			//highlight mode change
			scroll = iframe.getScroll();
			scroll[0] -= this.highlightScrollOffset[0];
			scroll[1] -= this.highlightScrollOffset[1];
			iframe.setScroll(scroll);
			
			//Clean up
			this.resetRegionsCache();
		},
		
		/**
		 * Handle drag event
		 * 
		 * @param {Object} e Drag:drag event facade object
		 * @private
		 */
		onDragDrag: function (e) {
			if (!this.dragBlockActive) return;
			
			var dragOverlay = e.target.get("node"),
				dragContent = dragOverlay.next(),
				
				dropOverlay = null,
				dropContent = null,
				
				proxyNode   = e.target.get("dragNode"),
				
				blockId     = this.dragBlockId,
				blockRegion = this.dragBlockRegion,
				blockIndex  = this.dragBlockIndex,
				
				regions = this.getBlockRegions(),
				
				listRegions = this.getListRegions(),
				listRegion  = null,
				i = 0,
				ii = listRegions.length,
				
				targetList  = this.dragTargetList,
				currentList = null,
				
				newBlockIndex = null,
				swapBlockIndex = null, // index of the block before/after which to insert
				swapBlockId = null,  // ID of the block before/after which to insert
				swapBlock = null,  // block before/after which to insert
				
				direction = 0,
				x = e.target.mouseXY[0],
				y = e.target.mouseXY[1];
			
			//Find list in which to drop by measuring in which list item center is inside
			for (; i<ii; i++) {
				listRegion = listRegions[i].region;
				if (x >= listRegion.left && x < listRegion.right && y >= listRegion.top && y < listRegion.bottom) {
					currentList = listRegions[i].id;
					break;
				}
			}
			
			if (currentList === null) {
				//Not in any list, ignore
				return e.halt();
			}
			
			//Find block
			var allBlockRegions = this.getBlockRegionsOrder()[currentList],
				allBlockRegion = null,
				i = 0,
				ii = allBlockRegions.length,
				direction = 0;
			
			for (; i<ii; i++) {
				if (targetList == currentList && blockIndex == i) {
					//Don't check against itself
					continue;
				}
				
				allBlockRegion = allBlockRegions[i].region;
				
				//Check if regions intersect
				if (y >= allBlockRegion.top && y < allBlockRegion.bottom) {
					
					if (targetList != currentList) {
						//List changed
						
						direction = y < (allBlockRegion.top + allBlockRegion.height / 2) ? -1 : 1;
						swapBlockIndex = i;
						
						if (direction == 1) {
							//After
							newBlockIndex = i + 1;
							break;
						} else {
							//Before
							newBlockIndex = i;
							break;
						}
					} else {
						//List didn't changed
						
						direction = y < (allBlockRegion.top + allBlockRegion.height / 2) ? -1 : 1;
						swapBlockIndex = i;
						
						if (direction == 1) {
							newBlockIndex = blockIndex < i ? i : i + 1;
							break;
						} else if (direction == -1) {
							newBlockIndex = blockIndex < i ? i - 1 : i;
							break;
						}
					}
				
				}
			}
			
			if (!ii) {
				//If currently there are no block in the list then put dragged block inside
				newBlockIndex = 0;
				swapBlockIndex = null;
			}
			
			if (newBlockIndex !== null && (newBlockIndex != blockIndex || targetList != currentList)) {
				//List or index changed
				
				if (swapBlockIndex !== null) {
					//Insert before / after another block
					
					swapBlockId = allBlockRegions[swapBlockIndex].id;
					swapBlock = this.get("host").getChildById(swapBlockId);
					
					dropOverlay = swapBlock.overlay;
					dropContent = swapBlock.getNode();
					
					if (direction == -1) {
						dropOverlay.insert(dragOverlay, "before");
						dragOverlay.insert(dragContent, "after");
					} else {
						dropContent.insert(dragOverlay, "after");
						dragOverlay.insert(dragContent, "after");
					}
				} else {
					//Append to the list
					
					var dropBlock = this.get("host").getChildById(currentList);
					dropContent = dropBlock.getNode();
					
					dropContent.append(dragOverlay);
					dropContent.append(dragContent);
				}
				
				this.moveBlock(currentList, blockId, newBlockIndex);
				
				//Reset regions, will force to recalculate
				this.listRegions = null;
				this.blockRegions = null;
				this.blockRegionsOrder = null;
				
				this.dragBlockIndex = newBlockIndex;
				this.dragTargetList = currentList;
				
				this.dragOrderChanged = true;
				this.dragListChanged = this.dragListChanged || targetList != currentList;
			}
		},
		
		
		/* ---------------------------- CHILDREN DATA MANIPULATION --------------------------- */
		
		
		/**
		 * Remove block from list block
		 * 
		 * @param {String} newListId New list ID
		 * @param {String} blockId Block ID
		 * @param {Number} index New block index
		 */
		moveBlock: function (newListId, blockId, index) {
			var host = this.get("host"),
				newList = host.getChildById(newListId),
				block = host.getChildById(blockId),
				oldList = block.get("parent");
			
			if (oldList && oldList !== newList) {
				oldList.removeChildFromList(block);
			}
			
			newList.addChildToList(block, index);
			
			host.resizeOverlays();
		},
		
		
		/* ---------------------------- REGION HELPER FUNCTIONS --------------------------- */
		
		
		/**
		 * List block region cache
		 * @type {Array}
		 * @private
		 */
		listRegions: null,
		
		/**
		 * Content block region cache
		 * @type {Array}
		 * @private
		 */
		blockRegions: null,
		
		/**
		 * Block region order by list block ID
		 * @type {Object}
		 * @private
		 */
		blockRegionsOrder: null,
		
		/**
		 * Returns all content block regions
		 * 
		 * @return Array with content block ids and regions
		 * @type {Array}
		 * @private
		 */
		getBlockRegions: function () {
			if (this.blockRegions) return this.blockRegions;
			
			var listRegions = [],
				blockRegions = [],
				blockRegionsOrder = {},
				
				listBlocks = this.get("host").getAllChildren(),
				listBlock = null,
				listBlockData = null,
				listId = null,
				
				blocks = null,
				region = null,
				id = null,
				
				dragBlockId = this.dragBlockId;
			
			for (listId in listBlocks) {
				listBlock = listBlocks[listId];
				
				// If block is closed, then children can't be ordered or dragged
				if (listBlock.isList() && !listBlock.isClosed()) {
					
					// Check if anything can be dropped inside
					listBlockData = listBlock.get('data');
					if ('allow' in listBlockData && !listBlockData.allow.length) {
						// This list can't have any children, skipping
						continue;
					}
					
					
					blocks = listBlock.getChildren();
					blockRegionsOrder[listId] = [];
					
					for (id in blocks) {
						//If block is closed then it can't be dragged and can't be dropped on
						if (!blocks[id].isClosed() && blocks[id].get('draggable')) {
							region = listBlock.getChildRegion(id);
							
							if (region) {
								blockRegions.push(region);
								blockRegionsOrder[listId].push(region);
								
								//Set drag block region
								if (dragBlockId == id) {
									this.dragBlockRegion = region.region;
								}
								
								//setData and getData doesn't work for some reason
								//couldn't pinpoint where it breaks, YUI bug?
								blocks[id].overlay.getDOMNode()._contentBlockId = id;
							}
						}
					}
					
					// Order by top/left position
					blockRegionsOrder[listId].sort(function (a, b) {
						if (a.region.top == b.region.top) {
							if (a.region.left == b.region.left) {
								return 0;
							}
							return a.region.left > b.region.left ? 1 : -1;
						}
						return a.region.top > b.region.top ? 1 : -1;
					});
					
					// Same structure as blockRegions
					listRegions.push({
						"id": listId,
						"region": listBlock.getNode().get("region")
					});
				}
			}
			
			this.blockRegionsOrder = blockRegionsOrder;
			this.blockRegions = blockRegions;
			this.listRegions = listRegions;
			return blockRegions;
		},
		
		/**
		 * Returns all list block regions ordered and grouped by list block ID
		 * 
		 * @return Object with block regions grouped by list block ID
		 * @type {Object}
		 * @private
		 */
		getBlockRegionsOrder: function () {
			if (this.blockRegionsOrder) return this.blockRegionsOrder;
			this.getBlockRegions();
			return this.blockRegionsOrder;
		},
		
		/**
		 * Returns all list block regions
		 * 
		 * @return Array with list block ids and regions
		 * @type {Array}
		 * @private
		 */
		getListRegions: function () {
			if (this.listRegions) return this.listRegions;
			this.getBlockRegions();
			return this.listRegions;
		},
		
		/**
		 * Remove regions cache
		 */
		resetRegionsCache: function () {
			this.listRegions = null;
			this.blockRegions = null;
			this.blockRegionsOrder = null;
			
			this.dragOrderChanged = false;
			this.dragListChanged = false;
			this.dragBlockId = null;
			this.dragBlockIndex = null;
			this.dragBlockRegion = null;
			
			var children = this.get("host").children,
				id = null;
			
			for (id in children) {
				children[id].resetBlockPositionCache();
			}
			
			this.get('host').resizeOverlays();
		},
		
		/**
		 * Test if drag is in position to be swapped with drop
		 * 
		 * @param {Number} top Top position of drag
		 * @param {Number} drag Drag index
		 * @param {Number} drop Drop index
		 * @return True if drag and drop elements should be swapped, otherwise false
		 * @type {Boolean}
		 */
		testSwap: function (top, drag, drop, drag_region, drop_region) {
			var target_y = drop_region.top + drop_region.height / 2;
			
			if (drag === -1) {
				//Item was not in this list previously
				
			} else {
				if (drag < drop) {
					if (top + drag_region.height >= target_y) {
						return true;
					}
				} else {
					if (top <= target_y) {
						return true;
					}
				}
			}
			
			return false
		}
		
	});
	
	Manager.PageContent.PluginOrdering = PluginOrdering;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["plugin"]});