//Invoke strict mode
"use strict";

YUI.add('supra.page-content-gallery', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Content block which has editable properties
	 */
	function ContentGallery () {
		ContentGallery.superclass.constructor.apply(this, arguments);
	}
	
	ContentGallery.NAME = 'page-content-gallery';
	ContentGallery.CLASS_NAME = Y.ClassNameManager.getClassName(ContentGallery.NAME);
	
	Y.extend(ContentGallery, Action.Editable, {
		
		renderUISettings: function () {
			ContentGallery.superclass.renderUISettings.apply(this, arguments);
			
			var container = this.properties.get('form').get('contentBox');
			var button_gallery = new Supra.Button({
				'label': 'Manage images'
			});
			
			button_gallery.render(container);
			button_gallery.on('click', this.openGalleryManager, this);
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 */
		openGalleryManager: function () {
			
			//Data
			var gallery_data = this.get('data').properties;
			
			//Show gallery
			SU.Manager.executeAction('GalleryManager', gallery_data, Y.bind(function (gallery_data, changed) {
				if (changed) {
					//@TODO
				}
			}, this));
		},
		
	});
	
	Action.Gallery = ContentGallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable']});