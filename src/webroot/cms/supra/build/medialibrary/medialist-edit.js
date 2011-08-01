//Invoke strict mode
"use strict";

/**
 * Plugin to add editing functionality for MediaList
 */
YUI.add('supra.medialibrary-list-edit', function (Y) {
	
	//Shortcuts
	var Data = Supra.MediaLibraryData,
		MediaLibraryList = Supra.MediaLibraryList;
	
	/**
	 * File upload
	 * Handles standard file upload, HTML5 drag & drop, simple input fallback
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'medialist-edit';
	Plugin.NS = 'edit';
	
	Plugin.ATTRS = {
		/**
		 * Media library data object, Supra.MediaLibraryData instance
		 * @type {Object}
		 */
		'dataObject': {
			value: null
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			
		},
		
		/**
		 * Handle click on folder, show rename controls
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleRenameClick: function (event) {
			var target = event.target.closest('li.type-folder');
			
			if (!target || !target.hasClass('selected') || target.hasClass('renaming')) return;
			this.renameFolder(target);
			
			event.halt();
		},
		
		/**
		 * Rename folder
		 * 
		 * @param {Object} target Folder node
		 */
		renameFolder: function (target /* Folder node */) {
			var id = target.getData('itemId'),
				data = this.get('dataObject').getData(id) || {};
			
			//Create input
			var input = Y.Node.create('<input type="text" value="" />');
			input.setAttribute('value', data.title);
			
			target.one('span').insert(input, 'after');
			target.addClass('renaming');
			
			var string = new Supra.Input.String({
				'srcNode': input,
				'value': data.title
			});
			string.render();
			Y.Node.getDOMNode(input).focus();
			
			//On blur confirm changes
			input.on('blur', this.handleRenameComplete, this, {
				'data': data,
				'node': target,
				'object': string,
				'id': id
			});
		},
		
		/**
		 * Handle renaming confirm/cancel
		 * 
		 * @param {Object} event Event
		 * @param {Object} obj Item data
		 * @private
		 */
		handleRenameComplete: function (event /* Event */, obj /* Item data */) {
			var value = obj.object.get('value'),
				id = obj.id,
				post_data = null;
			
			if (obj.data.title != value && value) {
				obj.data.title = value;
				obj.node.one('span').set('innerHTML', Y.Lang.escapeHTML(value));
				
				post_data = {
					'title': value
				};
				
				if (obj.id == -1) {
					//For new item add also parent ID
					post_data.parent = obj.data.parent;
				}
				
				this.get('dataObject').saveData(obj.id, post_data, function (data, id) {
					if (id == -1) {
						if (data) {
							//Update node itemId
							obj.node.setData('itemId', data);
						} else {
							//On failure remove temporary folder
							this.get('dataObject').removeData(obj.id, true);
							obj.node.remove();
							
							//Redraw parent
							this.host.renderItem(obj.data.parent);
						}
					}
				}, this);
			} else if (id == -1) {
				this.get('dataObject').removeData(obj.id, true);
				obj.node.remove();
				
				//Redraw parent
				this.host.renderItem(obj.data.parent);
			}
			
			obj.node.removeClass('renaming');
			obj.object.destroy();
		},
		
		/**
		 * When image or file property is changed save them
		 * 
		 * @param {Object} event Event
		 * @param {Object} data Item data
		 * @private
		 */
		onItemPropertyChange: function (event /* Event */, data /* Item data*/) {
			var name = data.input.getAttribute('name'),
				value = data.input.get('value');
			
			if (value && value != data.data[name]) {
				var data_object = this.get('dataObject'),
					item_data = data_object.getData(data.data.id),
					props = {};
				
				props[name] = item_data[name] = value;
				
				data_object.saveData(data.data.id, props);
				
				//Update title in folder list
				if (name == 'title') {
					var host = this.get('host'),
						parent_id = host.getItemData(data.data.id).parent,
						li = host.slideshow.getSlide('slide_' + parent_id).one('li[data-id="' + data.data.id + '"]');
					
					li.one('span').set('innerHTML', Y.Lang.escapeHTML(value));
				}
			} else if (!value) {
				data.input.set('value', data.data[name]);
			}
		}
		
	});
	
	
	Supra.MediaLibraryList.Edit = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin']});