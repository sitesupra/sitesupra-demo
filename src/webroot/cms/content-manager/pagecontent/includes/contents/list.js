YUI.add('supra.page-content-list', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		PageContent = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_OVERLAY = Y.ClassNameManager.getClassName('content', 'overlay');		//yui3-content-overlay
	
	/**
	 * Content block which is a container for other blocks
	 */
	function ContentList () {
		ContentList.superclass.constructor.apply(this, arguments);
	}
	
	ContentList.NAME = 'page-content-list';
	ContentList.CLASS_NAME = Y.ClassNameManager.getClassName(ContentList.NAME);
	ContentList.ATTRS = {
		/**
		 * Placeholders are not draggable
		 */
		'draggable': {
			'value': false
		}
	};
	
	Y.extend(ContentList, PageContent.Editable, {
		
		/**
		 * Block is list
		 * @type {Boolean}
		 */
		is_list: true,
		
		
		/* --------------------------------- LIFE CYCLE ------------------------------------ */
		
		
		bindUI: function () {
			ContentList.superclass.bindUI.apply(this, arguments);
			
			//Drag & drop breaks click event
			//Capture 
			var overlay_selector = '.' + CLASSNAME_OVERLAY;
			
			this.getNode().on('click', function (e) {
				var target = e.target.closest(overlay_selector),
					overlay = null,
					block = null;
				
				if (!target) return;
				
				for(var id in this.children) {
					block = this.children[id];
					//If children is editable and is not part of drag & drop
					if (block.get('editable') && block.get('draggable') && !block.get('loading')) {
						overlay = this.children[id].overlay;
						if (overlay.compareTo(target)) {
							this.get('super').set('activeChild', this.children[id]);
							break;
						}
					}
				}
			}, this);
			
			//On new block drop create it and start editing
			this.on('dragend:hit', function (e) {
				var reference = e.insertReference,
					before = e.insertBefore,
					index = null,
					properties = null,
					defaults = null;
				
				if (!before && reference) {
					//Find next block
					index = Y.Array.indexOf(this.children_order, reference);
					if (index != -1) {
						//Before next block
						index += 1;
						reference = this.children_order[index] || null;
						before = true;
					} else {
						//At the end
						index = null;
						reference = null;
					}
				} else if (before && reference) {
					index = Y.Array.indexOf(this.children_order, reference);
				}
				
				// Generate lipsum data
				defaults = Manager.Blocks.getBlockDefaultData(e.block.id);
				properties = Manager.Blocks.getBlockLipsumData(e.block.id);
				
				// Insert block
				this.get('super').getBlockInsertData({
					'type': e.block.id,
					'placeholder_id': this.getId(),
					'reference_id': reference,
					'properties': properties
				}, function (data) {
					// Create block in UI
					data = Supra.mix({'properties': defaults}, data, true);
					
					for (var id in data.properties) {
						data.properties[id] = {
							'__shared__': false,
							'language': null,
							'value': data.properties[id]
						};
					}
					
					this.createChildFromData(data, index);
				}, this);
				
				return false;
			}, this);
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		beforeDestroy: function () {
			ContentList.superclass.beforeDestroy.apply(this, arguments);
			delete(this.children_order);
		},
		
		
		/* --------------------------------- CHILDREN BLOCKS ------------------------------------ */
		
		
		/**
		 * Before we change HTML destroy all children
		 * 
		 * @private
		 */
		beforeSetHTMLHost: function () {
			ContentList.superclass.beforeSetHTMLHost.apply(this, arguments);
			
			var children = this.children,
				id = null;
				
			for (id in children) {
				children[id].destroy();
			}
			
			this.children_order = [];
			this.children = {};
		},
		
		/**
		 * After HTML change recreate children
		 * Children-children will be automatically created
		 * 
		 * @private
		 */
		afterSetHTMLHost: function () {
			var data = this.get('data'),
				permission_order = true,
				permission_edit = true;
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createChild(data.contents[i], {
						'draggable': !data.contents[i].closed && !this.isClosed() && permission_order,
						'editable': !data.contents[i].closed && permission_edit && data.contents[i].editable !== false
					}, true);
				}
			}
			
			this.setHighlightMode();
			ContentList.superclass.afterSetHTMLHost.apply(this, arguments);
		},
		
		/**
		 * Create block from data
		 * Populates all properties with lorem ipsum
		 * 
		 * @param {Object} data Block data
		 * @param {Number} index Index where block should be inserted, default at the end
		 */
		createChildFromData: function (data, index) {
			var block = this.createChild({
				'id': data.id,
				'closed': false,
				'locked': false,
				'type': data.type,
				'properties': data.properties,
				'value': data.html
			}, {
				'draggable': !this.isClosed(),
				'editable': data.editable !== false
			}, false, index);
			
			//Disable highlight, we will be editing this block
			this.get('super').set('highlightMode', 'edit');
			
			//When new item is created focus on it
			this.get('super').set('activeChild', block);
		},
		
		/**
		 * Returns child block region (left, top, width, height) by ID
		 * 
		 * @param {String} id Block ID
		 * @return Object with 'region' and 'id'
		 * @type {Object}
		 */
		getChildRegion: function (id) {
			if (this.children[id].get('draggable')) {
				var node = this.children[id].getNode(),
					overlay = this.children[id].overlay;
				
				//setData and getData doesn't work for some reason
				//couldn't pinpoint where it breaks, bug?
				overlay.getDOMNode()._contentId = id;
				
				return {
					'id': id,
					'region': node.get('region')
				};
			} else {
				return null;
			}
		},
		
		
		/* --------------------------------- ATTRIBUTES ------------------------------------ */
		
		
		/**
		 * draggable attribute setter
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setDraggable: function (value) {
			return false;
		}
	});
	
	PageContent.List = ContentList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'dd-delegate', 'dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', 'dd-scroll']});