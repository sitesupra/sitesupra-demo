/**
 * Plugin to add folder drag and drop support to extended media list
 */
YUI().add("supra.medialibrary-list-folder-dd", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var TYPE_FOLDER = Supra.MediaLibraryList.TYPE_FOLDER;
	
	/**
	 * Add drag and drop support from media library to other actions
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = "medialist-folder-dd";
	Plugin.NS = "folder_dd";
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Drag and drop delegate
		 * @type {Object}
		 * @private
		 */
		delegate: null,
		
		/**
		 * Folder ID which is being dragged
		 * @type {Object}
		 * @private
		 */
		folderDragging: null,
		
		/**
		 * Folder drop targets
		 * @type {Object}
		 * @private
		 */
		targets: null,
	
		/**
		 * Add event listeners
		 */
		initializer: function () {
			
			this.targets = {};
			
			//div.su-multiview-slide-content, li.type-folder
			var delegate = this.delegate = new Y.DD.Delegate({
				"container": this.get("host").get("contentBox"),
				"nodes": "li.type-folder",
				"target": true,
				"invalid": "input, select, button, textarea",	//removed "A" from invalid list
				"dragConfig": {
					"haltDown": false,
					"invalid": "input, select, button, textarea",	//removed "A" from invalid list
				}
			});
			
			//For HTML5 drag and drop we have to use A tag
			delegate.dd.removeInvalid('a');
			
			delegate.dd.plug(Y.Plugin.DDProxy, {
				"moveOnEnd": false,
				"cloneNode": true
			});
			
			delegate.on('drag:start', this.onDragStart, this);
			delegate.on('drop:hit', this.onDrop, this);
			
			this.get("host").on("itemRender", this.handleItemRender, this);
			this.get("host").on("removeSlide", this.handleItemRemove, this);
			this.get("host").on("itemRender", this.handleChange, this);
			this.get("host").on("folderMoveComplete", this.handleChange, this);
		},
		
		/**
		 * On drag start style proxy node
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDragStart: function (e) {
			//Node
			var node = e.target.get("node"),
				id = node.getAttribute("data-id");
			
			if (id) {
				this.folderDragging = id;
			}
			
			//Add classname to proxy element
	        var proxy = e.target.get("dragNode");
			proxy.addClass("type-folder-proxy");
			
			proxy.closest(".su-slideshow-multiview-content").append(proxy);
		},
		
		/**
		 * On drop move folder
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onDrop: function (e) {
			var drag = e.drag.get("node"),
				drop = e.drop.get("node");
			
			if (drag && drag !== drop) {
				var id = drag.getAttribute("data-id"),
					parent = drop.getAttribute("data-id");
				
				if (parent === "") {
					drop = drop.one("ul.folder, div.empty");
					if (drag) {
						parent = drop.getAttribute("data-id");
					} else {
						return;
					}
				}
				
				if (id && id != parent) {
					if (parent === '0') {
						parent = 0;
					}
					Supra.immediate(this, function () {
						if (this.get('host').moveFolder(id, parent)) {
							var path = this.get('host').get('data').getPath(parent);
							path.push(parent);
							this.get('host').open(path);
						}
					});
				}
			}
		},
		
		/**
		 * After items changed update drop targets
		 * 
		 * @private
		 */
		handleChange: function () {
			this.delegate.syncTargets();
		},
		
		/**
		 * Handle item render
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleItemRender: function (e) {
			//Add drop target to folder or empty folder
			if (e.type == TYPE_FOLDER) {
				var node = e.node.closest('.su-multiview-slide-content, .su-slide-content'),
					drop = node.plug(Y.Plugin.Drop);
				
				drop.drop.on('drop:hit', this.onDrop, this);
				
				this.targets[e.id] = drop;
			}
		},
		
		/**
		 * Handle item remove
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleItemRemove: function (e) {
			//If folder or empty folder
			if (e.type == TYPE_FOLDER) {
				if (e.id in this.targets) {
					this.targets[e.id].destroy();
					delete(this.targets[e.id]);
				}
			}
		}
	
	});
	
	Supra.MediaLibraryList.FolderDD = Plugin;
	
	//Since this Widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["plugin", "dd", "supra.medialibrary-list"]});