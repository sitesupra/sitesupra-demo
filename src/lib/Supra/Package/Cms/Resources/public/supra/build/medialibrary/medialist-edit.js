/**
 * Plugin to add editing functionality for MediaList
 */
YUI.add('supra.medialibrary-list-edit', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var MediaLibraryList = Supra.MediaLibraryList;
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'medialist-edit';
	Plugin.NS = 'edit';
	
	Plugin.ATTRS = {
		/**
		 * Media library data object, Supra.DataObject.Data instance
		 * @type {Object}
		 */
		'data': {
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
				data = this.get('data').cache.one(id) || {};
			
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
				original_title,
				deferred = null;
			
			if (obj.data.filename != value && value) {
				original_title = obj.data.filename;
				obj.node.one('span').set('innerHTML', Y.Escape.html(value));
				
				if (id == -1) {
					//For new item add also parent ID and private status
					this.get('data').add({
						'filename': value,
						'type': 1,
						'parent': obj.data.parent,
						'private': obj.data.private
					})
						.always(function () {
							this.get('data').cache.remove(-1);
						}, this)
						.done(function (data) {
							//Redraw parent
							this.get('host').renderItem(obj.data.parent);
						}, this)
						.fail(function () {
							//Remove item
							obj.node.remove();
						}, this);
					
				} else {
					this.get('data').save({
						'id': obj.id,
						'filename': value
					})
						.done(function (data) {
							//Redraw parent
							this.get('host').reloadFolder(obj.id);
						}, this)
						.fail(function () {
							//Revert title changes
							obj.node.one('span').set('innerHTML', Y.Escape.html(original_title));
						}, this);
					
				}
				
			} else if (id == -1) {
				this.get('data').cache.remove(obj.id);
				obj.node.remove();
				
				//Redraw parent
				this.get('host').renderItem(obj.data.parent);
			}
			
			if (obj.node) {
				obj.node.removeClass('renaming');
			}
			
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
			var name = data.input.get('name') || data.input.getAttribute('name'),
				value = data.input.get('value');
			
			if (value && value != data.data[name]) {
				var data_object = this.get('data'),
					id = data.data.id,
					item_data = data_object.cache.one(id),
					original_value = data.data[name],
					props = {'id': id},
					locale = null;
				
				props[name] = value;
				
				data_object.save(props)
					.fail(function (changes) {
						//Revert input changes
						data.input.set('value', changes[name]);
					}, this);
				
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