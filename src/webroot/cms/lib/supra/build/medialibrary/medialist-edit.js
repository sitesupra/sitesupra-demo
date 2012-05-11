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
			input.setAttribute('value', data.filename);
			
			target.one('span').insert(input, 'after');
			target.addClass('renaming');
			
			var string = new Supra.Input.String({
				'srcNode': input,
				'value': data.filename,
				'blurOnReturn': true
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
				post_data = null,
				original_title;
			
			if (obj.data.filename != value && value) {
				original_title = obj.data.filename;
				obj.data.filename = value;
				obj.node.one('span').set('innerHTML', Y.Escape.html(value));
				
				post_data = {
					'title': value
				};
				
				if (obj.id == -1) {
					//For new item add also parent ID and private status
					post_data.parent = obj.data.parent;
					post_data['private'] = obj.data['private'];
				}
				
				this.get('dataObject').saveData(obj.id, post_data, function (status, data, id) {
					if (id == -1) {
						if (status && data) {
							//Update node itemId
							obj.node.setData('itemId', data);
						} else {
							//Remove data
							this.get('dataObject').removeData(obj.id, true);
							obj.node.remove();
						}
						
						//Redraw parent
						this.get('host').renderItem(obj.data.parent);
					} else {
						if (!status) {
							//Revert title changes
							obj.data.filename = original_title;
							obj.node.one('span').set('innerHTML', Y.Escape.html(original_title));
						} else {
							this.get('host').reloadFolder(obj.id);
						}
					}
				}, this);
			} else if (id == -1) {
				this.get('dataObject').removeData(obj.id, true);
				obj.node.remove();
				
				//Redraw parent
				this.get('host').renderItem(obj.data.parent);
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
					id = data.data.id,
					item_data = data_object.getData(id),
					original_value = data.data[name],
					props = {},
					locale = null;
					
				props[name] = item_data[name] = data.data[name] = value;
				
				data_object.saveData(id, props, Y.bind(function (status, responseData) {
					if (!status) {
						//Revert changes
						this.revertItemPropertyChange(id, name, original_value);
						
						if (Y.Lang.isObject(data.data[name])) {
							data.data[name][locale] = original_value;
						} else {
							data.data[name] = original_value;
						}
					} else {
						if (responseData && responseData.id && responseData.id == id) {
							Supra.mix(item_data, responseData);
						}
					}
				}, this));
				
				//Update filename in folder list
				if (name == 'filename') {
					var host = this.get('host'),
						parent_id = host.getItemData(id).parent,
						li = host.slideshow.getSlide('slide_' + parent_id).one('li[data-id="' + id + '"]');
					
					li.one('span').set('text', value);
				}
			} else if (!value) {
				data.input.set('value', data.data[name]);
			}
		},
		
		/**
		 * If save request fails revert changes
		 * 
		 * @param {String} id File or folder ID
		 * @param {String} name Property name
		 * @param {String} value Original property value
		 * @private
		 */
		revertItemPropertyChange: function (id, name, value) {
			var host = this.get('host'),
				data_object = this.get('dataObject'),
				item_data = data_object.getData(id),
				props = {};
			
			//Revert data
			item_data[name] = value;
			
			//Revert 'title' which is shown in item list
			if (name == 'filename') {
				var host = this.get('host'),
					parent_id = host.getItemData(id).parent,
					li = host.slideshow.getSlide('slide_' + parent_id).one('li[data-id="' + id + '"]');
				
				li.one('span').set('text', value);
			}
			
			//Revert input value
			var widgets = host.getPropertyWidgets();
			if (name in widgets) {
				widgets[name].set('value', value);
			}
		}
		
	});
	
	
	Supra.MediaLibraryList.Edit = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin']});