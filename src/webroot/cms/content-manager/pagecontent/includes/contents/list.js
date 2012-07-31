//Invoke strict mode
"use strict";

YUI.add('supra.page-content-list', function (Y) {
	
	//Shortcut
	var Manager = Supra.Manager,
		PageContent = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_REORDER = Y.ClassNameManager.getClassName('content', 'reorder'),		//yui3-content-reorder
		CLASSNAME_DRAGING = Y.ClassNameManager.getClassName('content', 'draging'),		//yui3-content-draging
		CLASSNAME_PROXY = Y.ClassNameManager.getClassName('content', 'proxy'),			//yui3-content-proxy
		CLASSNAME_DRAGGABLE = Y.ClassNameManager.getClassName('content', 'draggable'),	//yui3-content-draggable
		CLASSNAME_OVERLAY = Y.ClassNameManager.getClassName('content', 'overlay');		//yui3-content-overlay
	
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
					index = null;
				
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
				
				this.get('super').getBlockInsertData({
					'type': e.block.id,
					'placeholder_id': this.getId(),
					'reference_id': reference
				}, function (data) {
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
		 * Create block from data
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
				'editable': true
			}, false, index);
			
			//Disable highlight, we will be editing this block
			this.get('super').set('highlight', false);
			
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
		
		/**
		 * Shouldn't have any overlay, since user can't click or drag place holders
		 */
		renderOverlay: function () {
		},
		
		/**
		 * There is no need to reload content, because list doesn't have any
		 * properties which could change content
		 */
		reloadContentHTML: function () {
		},
		
		/**
		 * Since there are no properties which could change content we don't have
		 * to do anything
		 * @private
		 */
		_reloadContentSetHTML: function () {
		}
	});
	
	PageContent.List = ContentList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'dd-delegate', 'dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', 'dd-scroll']});