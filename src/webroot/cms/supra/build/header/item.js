//Invoke strict mode
"use strict";

YUI.add('supra.header-item', function(Y) {
	
	function Item (config) {
		Item.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Item.NAME = 'item';
	
	Item.ATTRS = {
		'id': {
			value: null
		},
		'host': {
			value: null
		},
		'type': {
			value: 'label'
		},
		'title': {
			value: '',
			setter: '_setTitle'
		},
		'icon': {
			value: null
		}
	};
	
	Y.extend(Item, Y.Widget, {
		
		/**
		 * Destroy widget
		 * @private
		 */
		destructor: function () {
			var id = this.get('id');
			if (id) {
				this.get('boundingBox').previous().remove();
				this.set('id', null);
				this.get('host').removeItem(id);
			}
		},
		
		/**
		 * Bind UI
		 * @private
		 */
		bindUI: function () {
			Item.superclass.bindUI.apply(this, arguments);
			
			this.on('visibleChange', function (event) {
				var boundingBox = this.get('boundingBox');
				var arrow = boundingBox.previous();
				
				if (event.newVal) {
					boundingBox.removeClass('hidden');
					arrow.removeClass('hidden');
				} else {
					boundingBox.addClass('hidden');
					arrow.addClass('hidden');
				}
			}, this);
			
		},
		
		/**
		 * Render UI
		 * @private
		 */
		renderUI: function () {
			Item.superclass.renderUI.apply(this, arguments);
			
			var box = this.get('boundingBox');
			var content = this.get('contentBox');
			var type = Y.Lang.escapeHTML(this.get('type') || '');
			var icon = Y.Lang.escapeHTML(this.get('icon') || '');
			var title = Y.Lang.escapeHTML(this.get('title') || '');
			
			var arrow = Y.Node.create('<div class="item-arrow"></div>');
			
			var node = Y.Node.create('\
						<a href="javascript://" class="overlay"></a>\
						' + (icon ? '<img src="' + icon + '" alt="' + title + '" />' : '') + '\
						<h3>' + title + '</h3>\
				');
			
			box.addClass('yui3-item-' + type);
			box.insert(arrow, 'before');
			content.append(node);
		},
		
		/**
		 * Title attribute setter
		 * 
		 * @param {String} value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setTitle: function (value, raw) {
			var value = value || '';
			var output = raw ? value : Y.Lang.escapeHTML(value);
			
			var heading = this.get('contentBox').one('h3');
			if (heading) heading.set('innerHTML', output);
			
			return value;
		},
		
		/**
		 * Change item title
		 * 
		 * @param {String} value
		 */
		setTitle: function (value) {
			this.set('title', value);
			return this;
		}
	});
	
	Supra.HeaderItem = Item;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget']});