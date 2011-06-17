SU('anim', 'supra.button', 'supra.template', 'dd-drag', function (Y) {

	var TYPE_FOLDER = 1,
		TYPE_IMAGE = 2;

	/**
	 * Media bar data tree class
	 */
	function DataTree () {
		this._data = {0:{'children': {}}};
	}
	DataTree.prototype = {
		_data: null,
		
		add: function (data, folder) {
			if (folder && !(folder in this._data)) return;
			
			if (Y.Lang.isArray(data)) {
				for(var i=0,ii=data.length; i<ii; i++) {
					this.add(data[i], folder);
				}
			} else {
				data.children = {};
				data.folder = folder;
				
				this._data[folder].children[data.id] = data;
				this._data[data.id] = data;
			}
		},
		
		get: function (id, all) {
			if (id in this._data) {
				var data = this._data[id];
				if (all) return data;
				
				//Clone data object and remove 'children'
				data = Supra.mix({}, data);
				delete(data.children);
				return data;
				
			} else {
				return null;
			}
		},
		
		has: function (id) {
			return (id in this._data);
		},
		
		getSize: function (id, sizeid) {
			var data = this.get(id, true);
			if (data && data.sizes) {
				for(var i=0,ii=data.sizes.length; i<ii; i++) {
					if (data.sizes[i].id == sizeid) return data.sizes[i];
				}
			}
			return null;
		},
		
		destroy: function () {
			//Destroy data
			for(var i in this._data) {
				for(var k in this._data[i].children) {
					delete(this._data[i].children[k]);
				}
				delete(this._data[i].children);
				delete(this._data[i]);
			}
		}
	};
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Add as left bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('MediaLibrary');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'MediaLibrary',
		
		/**
		 * Include stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Folder template string
		 * @type {String}
		 */
		TEMPLATE_FOLDER: '<li draggable="true" class="folder">' +
								'<img src="{%=thumbnail%}" alt="" />' +
								'<p>{%=title%}</p>' +
						  '</li>',
		
		/**
		 * Image template string
		 * @type {String}
		 */
		TEMPLATE_IMAGE: '<li draggable="true" class="image" title="Click to insert">' +
								'<img src="{%=thumbnail%}" alt="" />' +
								'<p>' + 
									'{%=title%}' +
									'<span>{%=filename%}</span>' +
									'<span>{%=description%}</span>' +
								'</p>' +
						  '</li>',
		
		/**
		 * "No data" template string
		 * @type {String}
		 */
		TEMPLATE_EMPTY: '<li class="empty">No files in this folder</li>',
		
		/**
		 * Folder type constant
		 * @type {Number}
		 */
		TYPE_FOLDER: TYPE_FOLDER,
		
		/**
		 * Image type constant
		 * @type {Number}
		 */
		TYPE_IMAGE: TYPE_IMAGE,
		
		/**
		 * Folder template function
		 * @type {Function}
		 */
		template_folder: null,
		
		/**
		 * Image template function
		 * @type {Function}
		 */
		template_image: null,
		
		/**
		 * "No data" template function
		 * @type {Function}
		 */
		template_empty: null,
		
		/**
		 * File/folder data
		 * @type {Object}
		 */
		data: null,
		
		/**
		 * Path to current folder
		 */
		current_folder_stack: [],
		
		/**
		 * List nodes
		 * @type {Object}
		 */
		list_nodes: {},
		
		/**
		 * Back button
		 * @type {Object}
		 */
		button_back: null,
		
		/**
		 * Heading node
		 * @type {Object}
		 */
		nodeHeading: null,
		
		/**
		 * Heading node
		 * @type {Object}
		 */
		nodeFooter: null,
		
		/**
		 * Heading node
		 * @type {Object}
		 */
		nodeLists: null,
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			var container = this.getContainer();
			
			this.nodeHeading = container.one('.yui3-medialibrary-heading');
			this.nodeFooter = container.one('.yui3-medialibrary-footer');
			this.nodeLists = container.one('.yui3-medialibrary-lists');
			
			this.data = new DataTree();
			
			//"Back" button
			var btn = new Supra.Button({"label": "Back", "style": "small", "icon": "/cms/supra/img/media/icon-back.png"});
				btn.render(this.nodeHeading);
				btn.hide();
				btn.on('click', function () {
					var folder = this.data.get(this.getCurrentFolder(), true).folder;
					this.openFolder(folder);
				}, this);
				
				this.button_back = btn;
				
			//"Done" button
			var btn = new Supra.Button({"label": "Done", "style": "small"});
				btn.render(this.nodeHeading);
				btn.addClass('yui3-medialibrary-button-close');
				btn.on('click', function () {
					this.hide();
				}, this);
				
			//"Open in new window" button
			var btn = new Supra.Button({"label": "Open in new window", "style": "small", "icon": "/cms/supra/img/media/icon-window.png"});
				btn.render(this.nodeFooter);
				btn.on('click', function () {
					//@TODO
				}, this);
			
			
			//Load folders
			if (this.get('visible')) {
				this.openFolder(0);
			} else {
				var vc = this.on('visibleChange', function (evt) {
					if (evt.newVal) {
						this.openFolder(0);
						vc.detach();
					}
				}, this);
			}
			
		},
		
		/**
		 * Handle loaded data
		 * 
		 * @param {Number} transaction
		 * @param {Object} data
		 */
		loadComplete: function (transaction, data) {
			var folder_id = this.getCurrentFolder();
			var node = this.list_nodes[folder_id];
			
			//Add to data list
			this.data.add(data, folder_id);
			
			for(var i=0,ii=data.length; i<ii; i++) {
				var tpl = null;
				var tpl_data = data[i];
				
				switch(data[i].type) {
					case TYPE_FOLDER:
						//Folder
						tpl = this.template_folder ? this.template_folder : (this.template_folder = new Supra.Template(this.TEMPLATE_FOLDER));
						break;
					case TYPE_IMAGE:
						//Image
						tpl = this.template_image ? this.template_image : (this.template_image = new Supra.Template(this.TEMPLATE_IMAGE));
						tpl_data = Supra.mix({}, tpl_data, {'thumbnail': this.data.getSize(data[i].id, '60x60').external_path});
						
						break;
				}
				
				if (tpl) {
					var item = Y.Node.create(tpl.render(tpl_data));
						item.setData('id', data[i].id);
					
					node.append(item);
					item.setData('data', data[i]);
					
					//Add HTML5 drag & drop support
					node.on('dragstart', function (e) {
						var target = e.target.test('LI') ? e.target : e.target.ancestor('LI');
						if (!target) return;
						var is_image = target.test('.image'),
							is_folder = target.test('.folder');
						
						if (is_image || is_folder) {
							var data = target.getData('data');
							
							e._event.dataTransfer.effectAllowed = 'copy';
							e._event.dataTransfer.setData('text', String(data.id));	// Use text to tranfer image ID
							
							if (is_folder && !(data.id in this.list_nodes)) {
								//Load images
								this.openFolder(data.id, true);
							}
						}
					}, this);
				}
			}
			
			if (data.length == 0) {
				tpl = this.template_empty ? this.template_empty : (this.template_empty = new Supra.Template(this.TEMPLATE_EMPTY));
				var item = Y.Node.create(tpl.render({}));
				node.append(item);
			}
			
			this.set('loading', false);
		},
		
		/**
		 * Open folder
		 * If silent attribute is set, then folder will be created in DOM, but not opened
		 * 
		 * @param {Number} folder_id Folder ID
		 */
		openFolder: function (folder_id, silent) {
			if (!this.data.has(folder_id)) return false;
			
			if (folder_id in this.list_nodes) {
				if (silent) return;
				
				var stack = this.current_folder_stack;
				var in_stack = false;
				var current = this.getCurrentFolder();
				
				if (this.data.get(folder_id, true).folder == current) {
					//Open already loaded child
					
					this.current_folder_stack.push(folder_id);
					this.list_nodes[folder_id].removeClass('hidden');
					
					//Animate folder movement
					var anim_pos = - (this.current_folder_stack.length - 1) * 316;
					if (anim_pos) {
						var anim = new Y.Anim({
							node: this.list_nodes[0],
							duration: 0.5,
							easing: Y.Easing.easeOut,
							to: {
								marginLeft: anim_pos + 'px'
							}
						});
						anim.run();
					}
					
				} else {
					//If folder is in currently opened folder list
					//then show it
					for (var i = 0, ii = stack.length; i < ii; i++) {
						if (stack[i] == folder_id) {
							in_stack = i;
							break;
						}
					}
					
					if (in_stack !== false) {
						var anim_pos = -in_stack * 316;
						var anim = new Y.Anim({
							node: this.list_nodes[0],
							duration: 0.5,
							easing: Y.Easing.easeOut,
							to: {
								marginLeft: anim_pos + 'px'
							}
						});
						
						anim.on('end', function(){
						
							//Hide unneeded folders
							for(var i=in_stack+1, ii=stack.length; i<ii; i++) {
								this.list_nodes[stack[i]].addClass('hidden');
							}
						
						}, this);
						
						anim.run();
						
						//Remove unneeded folders from stack
						this.current_folder_stack = this.current_folder_stack.slice(0, in_stack + 1);
					} else {
						//Not possible?
					}
				}
				
			} else {
				this.current_folder_stack.push(folder_id);
				
				var node = Y.Node.create('<ul></ul>');
				
				if (silent) {
					node.addClass('hidden');
				}
				
				this.nodeLists.append(node);
				this.list_nodes[folder_id] = node;
				
				if (!silent) {
					//Animate folder movement
					var anim_pos = - (this.current_folder_stack.length - 1) * 316;
					if (anim_pos) {
						var anim = new Y.Anim({
							node: this.list_nodes[0],
							duration: 0.5,
							easing: Y.Easing.easeOut,
							to: {
								marginLeft: anim_pos + 'px'
							}
						});
						anim.run();
					}
				}
				
				this.loadData(folder_id);
			}
			
			if (!silent) {
				if (this.current_folder_stack.length > 1) {
					this.button_back.show();
				} else {
					this.button_back.hide();
				}
			}
		},
		
		/**
		 * Load folder data
		 * 
		 * @param {Number} folder_id Folder ID
		 * @private
		 */
		loadData: function (folder_id) {
			this.set('loading', true);
			
			//Supra.io(this.getDataPath(), {
			Supra.io(this.getPath() + 'medialibrary.json.php', {
				data: {'folder': folder_id || 0},
				on: {
					success: Y.bind(this.loadComplete, this)
				}
			});
		},
		
		/**
		 * Returns currently opened folder ID
		 * 
		 * @return Folder ID
		 * @type {Number}
		 */
		getCurrentFolder: function () {
			var stack = this.current_folder_stack;
			return (stack.length ? stack[stack.length-1] : 0);
		},
		
		/**
		 * Handle folder/image click
		 * 
		 * @param {Event} e Event
		 */
		_handleClick: function (e) {
			var node = e.currentTarget;
			if (node) {
				var id = node.getData('id');
				if (id) {
					var data = this.data.get(id, true);
					
					if (data.type == TYPE_FOLDER) { //Folder
						this.openFolder(id);
					} else if (data.type == TYPE_IMAGE) { //Image
						//Fire 'insert' event with all image data
						data = SU.mix({}, data);
						delete(data.children);
						this.fire('insert', {'image': data});
					}
				}
			}
		},
		
		/**
		 * Set loading state
		 * 
		 * @param {Boolean} value State
		 */
		_setLoading: function (value) {
			var classname = Y.ClassNameManager.getClassName('medialibrary', 'loading');
			
			var folder_id = this.getCurrentFolder();
			var node = this.list_nodes[folder_id];
			
			if (value) {
				node.addClass(classname);
			} else {
				node.removeClass(classname);
			}
			
			return !!value;
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			Y.delegate('click', this._handleClick, Y.Node.getDOMNode(this.getContainer()), 'li', this);
			
			//On visibility change show/hide tabs
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.getContainer().removeClass('hidden');
					this.fire('show');
				} else {
					this.getContainer().addClass('hidden');
					this.fire('hide');
				}
			});
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (callback) {
			this.callback = callback;
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
		}
	});
	
});