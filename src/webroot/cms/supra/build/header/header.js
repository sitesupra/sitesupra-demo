//Invoke strict mode
"use strict";

YUI.add('supra.header', function(Y) {
	
	function Header (config) {
		Header.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		this.items = {};
	}
	
	Header.NAME = 'header';
	
	Y.extend(Header, Y.Widget, {
		
		/**
		 * Item list
		 * @type {Object}
		 * @private
		 */
		items: {},
		
		/**
		 * Render UI
		 * @private
		 */
		renderUI: function() {
			var items = this.items,
				content = this.get('contentBox');
			
			for(var id in items) {
				items[id].render(content);
			}
			
			//Set user profile
			var user = Supra.data.get('user', null),
				node = content.one('.user');
			
			if (user) {
				var username = user.username;
				node.one('.username').set('innerHTML', Y.Lang.escapeHTML(username));
				
				var avatar = user.avatar || '/cms/supra/img/header-user-default-32x32.png';
				node.one('.avatar').setAttribute('src', avatar);
				
				node.removeClass('hidden');
			}
		},
		
		/**
		 * Returns item instance Supra.HeaderItem or null if not found
		 * 
		 * @param {String} item_id Item ID
		 * @return Item instance
		 * @type {Object}
		 * @see Supra.HeaderItem
		 */
		getItem: function (item_id) {
			var items = this.items;
			if (item_id in items) {
				return items[item_id];
			} else {
				return null;
			}
		},
		
		/**
		 * Add item to the header
		 * 
		 * @param {String} item_id Item ID
		 * @param {Object} item_config Item configuration:
		 *     type - "label" or "link"
		 *     icon - "path_to_icon", optional
		 *     title - app title
		 * @return Created item instance
		 * @type {Object}
		 * @see Supra.HeaderItem
		 */
		addItem: function (item_id, item_config) {
			if (!item_id || !item_config) return null;
			
			var items = this.items;
			var item = item_config;
			
			if (!(item_id in items)) {
				if (!(item instanceof Supra.HeaderItem)) {
					item_config.id = item_id;
					item_config.host = this;
					item = new Supra.HeaderItem(item_config);
				} else {
					item_id = item.get('id');
				}
				
				items[item_id] = item;
				
				//If header is rendered, then item can be rendered too
				if (this.get('rendered')) {
					item.render(this.get('contentBox'));
				}
				
				//Bind events
				item.on('click', function (event) {
					this.fire(item_id.toLowerCase() + 'Click', {'target': item});
					this.fire('itemClick', {'id': item_id, 'target': item});
					return false;
				}, this);
			}
			
			return item;
		},
		
		/**
		 * Remove item from header
		 * 
		 * @param {String} item_id
		 */
		removeItem: function (item_id) {
			var items = this.items;
			if (item_id in items) {
				items[item_id].destroy();
				delete(items[item_id]);
			}
			return this;
		}
	});
	
	Supra.Header = Header;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'supra.header-item']});