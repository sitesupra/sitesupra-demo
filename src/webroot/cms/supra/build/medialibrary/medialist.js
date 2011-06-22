YUI.add('supra.medialibrary-list', function (Y) {
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function List (config) {
		List.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	List.NAME = 'medialist';
	List.CLASS_NAME = Y.ClassNameManager.getClassName(List.NAME);
	
	
	/**
	 * Constant, display all data
	 * @type {Number}
	 */
	List.DISPLAY_ALL = 0;
	
	/**
	 * Constant, display only images
	 * @type {Number}
	 */
	List.DISPLAY_IMAGES = 1;
	
	/**
	 * Constant, display only folders
	 * @type {Number}
	 */
	List.DISPLAY_FOLDERS = 2;
	
	
	List.ATTRS = {
		/**
		 * Request URI
		 * @type {String}
		 */
		'requestURI': null,
		
		/**
		 * Root folder ID
		 * @type {Number}
		 */
		'rootFolderId': 0,
		
		/**
		 * Folders, files and image scan be selected
		 * @type {Boolean}
		 */
		'foldersSelectable': false,
		
		/**
		 * Display type: all, images or files
		 * @type {Number}
		 */
		'displayType': List.DISPLAY_ALL
	};
	
	List.HTML_PARSER = {
	};
	
	Y.extend(List, Y.Widget, {
		
		/**
		 * Supra.Slideshow instance
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			//Create slideshow
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			
		}
	});
	
	Supra.MediaLibraryList = List;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'supra.slideshow', 'supra.medialibrary-data']});