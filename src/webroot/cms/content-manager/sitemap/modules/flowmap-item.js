//Invoke strict mode
"use strict";

YUI.add('website.sitemap-flowmap-item', function (Y) {
	
	var ITEM_WIDTH = 146;

	function FlowMapItem (config) {
		var config = Y.mix(config || {}, {
			'label': '',
			'icon': '',
			'data': null,
			'selectable': true
		});
		
		FlowMapItem.superclass.constructor.apply(this, [config]);
	};
	
	FlowMapItem.NAME = 'flowmap-item';
	FlowMapItem.CLASS_NAME = Y.ClassNameManager.getClassName('flowmap-item');
	
	FlowMapItem.ATTRS = {
		'defaultChildType': {
			value: FlowMapItem,
		},
		'preview': {
			value: '',
			setter: 'setPreview'
		},
		'dragableSelector': {
			value: 'div.flowmap-node'
		}
	};
	
	Y.extend(FlowMapItem, Supra.FlowMapItemNormal, {
		/**
		 * Root element type
		 * @type {Object}
		 * @private
		 */
		ROOT_TYPE: FlowMapItem,
		
		BOUNDING_TEMPLATE: '<li></li>',
		CONTENT_TEMPLATE: '<div class="flowmap-node">\
								<div class="flowmap-node-inner">\
									<span class="img"><img src="/cms/lib/supra/img/tree/none.png" /></span>\
									<label></label>\
									<span class="edit hidden"></span>\
								</div>\
								<span class="toggle hidden"></span>\
			  				</div>\
			  				<ul class="flowmap-children"></ul>\
			  				<a class="show-all hidden">' + SU.Intl.get(['sitemap', 'show_all_pages']) + '</a>',
		
		/**
		 * Create and add the nodes which the widget needs
		 * 
		 * @private
		 */
		renderUI: function () {
			var data = this.get('data'),
				level = 0;
			
			if (this.isRoot()) {
				level = 1;
				
				//Root level pages and templates can't be dragable
				this.set('isDragable', false);
				
				//Root level page can't be deleted or duplicated
				if (SU.Manager.SiteMap.getType() != 'templates') {
					this.get('boundingBox').one('.edit').addClass('edit-hidden');
				}
			} else if (this.get('parent').isRoot()) {
				level = 2;
				this.set('defaultChildType', Supra.FlowMapItemNormal);
				this.get('boundingBox').addClass('yui3-tree-node-collapsed');
				this.get('boundingBox').one('.flowmap-children').addClass('tree-children');
			}
			
			Supra.FlowMapItem.superclass.renderUI.apply(this, arguments);
			
			//Add type class
			if (data.type) {
				this.get('boundingBox').one('.flowmap-node-inner').addClass('type-' + data.type);
			}
			
			//Update style
			this.onChildChange();
				
			//Preview
			this.setPreview(this.get('preview') || data.preview || '');
		},
		
		/**
		 * Attach event listeners
		 */
		bindUI: function () {
			//Make sure user clicked inside flowmap node, not on bounding box
			this.get('boundingBox').one('div').on('click', this.onNodeClick, this);
			
			FlowMapItem.superclass.bindUI.apply(this, arguments);
			
			//On children add remove update style
			this.after('addChild', this.onChildChange, this);
			this.after('removeChild', this.onChildChange, this);
		},
		
		/**
		 * Handle node click
		 * 
		 * @private
		 */
		onNodeClick: function (evt) {
			if (evt.target.closest('.flowmap-node-inner')) {
				var data = this.get('data'),
					clickable = data.type != 'group';
				
				if (clickable && this.getTree().fire('node-click', {'node': this, 'data': data})) {
					//If event wasn't stopped then set this node as selected
					this.set('isSelected', true);
				}
			}
			evt.halt();
		},
		
		onChildChange: function () {
			if (this.size()) {
				this.get('boundingBox').addClass('has-children');
			} else {
				this.get('boundingBox').removeClass('has-children');
			}
		},
		
		/**
		 * Expand item; animate width
		 */
		expand: function (silent) {
			var box = this.get('boundingBox'),
				node = box.one('ul'),
				proxy = node.cloneNode(true),
				width = null;
			
			proxy.addClass('offscreen');
			proxy.setStyle('display', 'block');
			
			node.insert(proxy, 'before');
			width = proxy.get('offsetWidth');
			proxy.remove();
			
			if (width) {
				box.setStyle('width', Math.max(ITEM_WIDTH, width) + 'px');
			}
			
			Supra.FlowMapItem.superclass.expand.apply(this, arguments);
		},
		
		/**
		 * Collapse item; animate width
		 */
		collapse: function () {
			var box = this.get('boundingBox');
			box.setStyle('width', ITEM_WIDTH + 'px');
			
			Supra.FlowMapItem.superclass.collapse.apply(this, arguments);
		},
		
		/**
		 * Set preview
		 * @param {Object} preview
		 */
		setPreview: function (preview) {
			this.get('boundingBox').one('img').set('src', preview);
			return preview;
		}
	});
	
	Supra.FlowMapItem = FlowMapItem;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['website.sitemap-flowmap-item-normal']});