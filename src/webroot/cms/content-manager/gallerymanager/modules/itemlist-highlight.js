YUI.add('gallerymanager.itemlist-highlight', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//Template
	var TEMPLATE_HIGHLIGHT_T = '<div class="yui3-inline-reset yui3-box-reset supra-gallerymanager-highlight-top"></div>',
		TEMPLATE_HIGHLIGHT_R = '<div class="yui3-inline-reset yui3-box-reset supra-gallerymanager-highlight-right"></div>',
		TEMPLATE_HIGHLIGHT_B = '<div class="yui3-inline-reset yui3-box-reset supra-gallerymanager-highlight-bottom"></div>',
		TEMPLATE_HIGHLIGHT_L = '<div class="yui3-inline-reset yui3-box-reset supra-gallerymanager-highlight-left"></div>';
	
	/*
	 * Editable content
	 */
	function ItemListHighlight (config) {
		ItemListHighlight.superclass.constructor.apply(this, arguments);
	}
	
	ItemListHighlight.NAME = 'gallerymanager-itemlist-highlight';
	ItemListHighlight.NS = 'highlight';
	
	ItemListHighlight.ATTRS = {
		'highlightNodes': {
			'value': null
		},
		'childSelector': {
			'value': null
		},
		
		'borderLeft': {
			'value': 2
		},
		'borderRight': {
			'value': 2
		},
		'borderTop': {
			'value': 2
		},
		'borderBottom': {
			'value': 2
		},
		
		'heightTop': {
			'value': 6
		},
		'heightBottom': {
			'value': 6
		},
		
		/**
		 * Message block height
		 */
		'messageHeight': {
			'value': 30
		},
		
		'offset': {
			'value': 7
		}
	};
	
	Y.extend(ItemListHighlight, Y.Plugin.Base, {
		
		/**
		 * Event listeners
		 * @type {Array}
		 * @private
		 */
		listeners: [],
		
		/**
		 * Target node
		 * @type {Object}
		 * @private
		 */
		target: null,
		
		/**
		 * Attach listeners, etc.
		 */
		initializer: function(config) {
			var itemlist = this.get('host'),
				container = itemlist.get('listNode');
			
			this.listeners = [];
			this.listeners.push(itemlist.after('listNodeChange', this.reattachListeners, this));
			
			// Initialize?
			if (container) {
				this.reattachListeners();
			}
		},
		
		destructor: function () {
			this.resetAll();
			
			// Listeners
			var listeners = this.listeners,
				i = 0,
				ii = listeners.length;
			
			for (; i<ii; i++) listeners[i].detach();
			this.listeners = [];
			
			// Nodes
			var nodes = this.get('highlightNodes');
			if (nodes) nodes.remove(true);
		},
		
		
		/* ------------ EVENT HANDLERS ------------ */
		
		
		reattachListeners: function () {
			var itemlist = this.get('host'),
				container = itemlist.get('listNode'),
				childSelector = null,
				nodes = null;
			
			if (!container) {
				// Nothing to attach listeneres to
				return;
			}
			
			childSelector = itemlist.getChildSelector()
			
			if (itemlist.order) {
				itemlist.order.on('dragStart', this.hideHighlight, this);
			}
			
			itemlist.on('focusItem', this.hideHighlight, this);
			itemlist.on('addItem', this.showHighlight, this); // updates position
			
			container.delegate('mouseenter', this.mouseEnter, childSelector, this);
			container.delegate('mouseleave', this.hideHighlight, childSelector, this);
			
			// Create node
			nodes = new Y.NodeList([
				Y.Node.create(TEMPLATE_HIGHLIGHT_T),
				Y.Node.create(TEMPLATE_HIGHLIGHT_R),
				Y.Node.create(TEMPLATE_HIGHLIGHT_B),
				Y.Node.create(TEMPLATE_HIGHLIGHT_L)
			]);
			this.set('highlightNodes', nodes);
			
			Y.Node(itemlist.getDocument().body).append(nodes);
			this.set('childSelector', childSelector);
		},
		
		mouseEnter: function (evt) {
			var target = evt.target.closest(this.get('childSelector'));
			
			if (this.get('host').editingId) {
				// Don't show highlight while editing
				return;
			}
			if (!target || target.hasClass('supra-gallerymanager-new')) {
				// New item shouldn't be highlighted
				return;
			}
			
			this.target = target;
			this.showHighlight(null, true);
		},
		
		/**
		 * Show node highlight
		 */
		showHighlight: function (target, showMessage) {
			if (target && target.isInstanceOf && target.isInstanceOf('Node')) {
				this.target = target;
			}
			if (!this.target) return;
			
			var target = this.target,
				region = target.get('region'),
				nodes = this.get('highlightNodes'),
				
				paddingTop    = parseInt(target.getStyle('paddingTop'), 10) || 0,
				paddingBottom = parseInt(target.getStyle('paddingBottom'), 10) || 0,
				paddingLeft   = parseInt(target.getStyle('paddingLeft'), 10) || 0,
				paddingRight  = parseInt(target.getStyle('paddingRight'), 10) || 0,
				
				borderLeft = this.get('borderLeft'),
				borderRight = this.get('borderRight'),
				borderTop = this.get('borderTop'),
				borderBottom = this.get('borderBottom'),
				
				heightTop = this.get('heightTop'),
				heightBottom = this.get('heightBottom'),
				
				messageHeight = this.get('messageHeight'),
				
				offset = this.get('offset');
			
			region.top -= paddingTop + borderTop + offset;
			region.left -= paddingLeft + borderLeft + offset;
			region.height -= paddingTop + paddingBottom - borderTop - offset * 2;
			region.width  -= paddingLeft + paddingRight - borderLeft - offset * 2;
			
			if (showMessage) {
				// Message
				heightBottom += messageHeight;
				region.height += messageHeight;
				nodes.item(2).set('innerHTML', '<span class="yui3-inline-reset yui3-box-reset">' + Supra.Intl.get(['gallerymanager', 'edit_move']) + '<span class="yui3-inline-reset yui3-box-reset"></span></span>');
				nodes.item(2).addClass('message');
			} else {
				// No message
				nodes.item(2).set('innerHTML', '');
				nodes.item(2).removeClass('message');
			}
			
			nodes.item(0).getDOMNode().style.cssText = 'display: block !important; left:' + region.left + 'px !important; top:' + region.top + 'px !important; width:' + (region.width - borderLeft) + 'px !important;';
			nodes.item(1).getDOMNode().style.cssText = 'display: block !important; left:' + (region.left + region.width) + 'px !important; top:' + (region.top + heightTop) + 'px !important; height:' + (region.height - heightTop - heightBottom) + 'px !important;';
			nodes.item(2).getDOMNode().style.cssText = 'display: block !important; left:' + region.left + 'px !important; top:' + (region.top + region.height - heightBottom) + 'px !important; width:' + (region.width - borderLeft) + 'px !important;';
			nodes.item(3).getDOMNode().style.cssText = 'display: block !important; left:' + region.left + 'px !important; top:' + (region.top + heightTop) + 'px !important; height:' + (region.height - heightTop - heightBottom) + 'px !important;';
		},
		
		/**
		 * Hide highlight
		 */
		hideHighlight: function (evt) {
			if (this.target) {
				var nodes = this.get('highlightNodes');
				nodes.item(0).getDOMNode().style.cssText = 'display: none;';
				nodes.item(1).getDOMNode().style.cssText = 'display: none;';
				nodes.item(2).getDOMNode().style.cssText = 'display: none;';
				nodes.item(3).getDOMNode().style.cssText = 'display: none;';
				
				this.target = null;
			}
		},
		
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
			var nodes = this.get('highlightNodes');
			if (nodes) {
				nodes.remove();
				this.set('highlightNodes', false);
			}
		}
		
	});
	
	Supra.GalleryManagerItemListHighlight = ItemListHighlight;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin']});