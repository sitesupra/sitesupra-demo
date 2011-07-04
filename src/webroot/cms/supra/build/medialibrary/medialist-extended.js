//Invoke strict mode
"use strict";

YUI.add('supra.medialibrary-list-extended', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Data = Supra.MediaLibraryData,
		List = Supra.MediaLibraryList;
	
	/**
	 * Extended media list
	 * Handles data loading, scrolling, selection
	 */
	function Extended (config) {
		Extended.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Extended.NAME = 'medialist';
	Extended.CLASS_NAME = Y.ClassNameManager.getClassName(Extended.NAME);
	
	
	Extended.ATTRS = {
		/**
		 * Slideshow class
		 * @type {Function}
		 */
		'slideshowClass': {
			'value': Supra.MediaLibrarySlideshow
		}
	};
	
	
	Y.extend(Extended, List, {
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			Extended.superclass.renderUI.apply(this, arguments);
		},
		
		/**
		 * Add folder to the parent
		 * 
		 * @param {Number} parent Parent ID
		 */
		addFolder: function (parent, label) {
			var parent_id = null,
				parent_data = null,
				data_object = this.get('dataObject');
			
			if (parent) {
				parent_id = parent;
				parent_data = data_object.getData(parent_id);
				
				if (!parent_data || parent_data.type != Data.TYPE_FOLDER) {
					return false;
				}
			} else {
				parent_data = this.getSelectedFolder();
				if (parent_data) {
					parent_id = parent_data.id;
				} else {
					parent_id = this.get('rootFolderId');
				}
			}
			
			if (parent_data) {
				
			}
		},
		
		/**
		 * Returns currently selected folder
		 * 
		 * @return Selected folder data
		 * @type {Object}
		 */
		getSelectedFolder: function () {
			var history = this.slideshow.getHistory(),
				data_object = this.get('dataObject'),
				item_id = String(history[history.length - 1]).replace('slide_', ''),
				folder_data = data_object.getData(item_id);
			
			while(folder_data) {
				if (folder_data.type == Data.TYPE_FOLDER) return folder_data;
				folder_data = data_object.getData(folder_data.parent);
			}
			
			return null;
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Extended.superclass.bindUI.apply(this, arguments);
			
			var slide_selector = '.yui3-ml-slideshow-slide',
					content = this.get('contentBox');
			
			//On list click close folder
			content.delegate('click', function (event) {
				var target = event.target;
				
				// Click on folder item is already handled 
				if (target.test('ul.folder') || target.ancestor('ul.folder')) return;
				
				// Get slide
				target = target.test(slide_selector) ? target : target.ancestor(slide_selector);
				if (!target) return;
				
				var id = target.getData('itemId');
				if (!id) return;
				
				//Style element
				target.all('li').removeClass('selected');
				
				//Scroll to slide
				this.open(id);
				
			}, slide_selector, this);
			
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			Extended.superclass.syncUI.apply(this, arguments);
		}
	}, {});
	
	
	Supra.MediaLibraryExtendedList = Extended;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.medialibrary-list', 'supra.medialibrary-slideshow']});