SU(function (Y) {

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Does Root action has template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Set up header
		 * 
		 * @private
		 */
		setupHeader: function () {
			var sitemap = Manager.getAction('SiteMap');
			var pagemanager = Manager.getAction('Page');
			var header = Manager.getAction('Header');
			var pagemanagerfooter = Manager.getAction('PageBottomBar');
			
			function showSitemap() {
				sitemap.show();
				pagemanager.hide();
			}
			
			//When user clicks on header item "Pages" show initial view
			header.on('sitemapClick', showSitemap);
			
			pagemanagerfooter.on('cancel', showSitemap);
			pagemanagerfooter.on('save', showSitemap);
		},
		
		/**
		 * Bind Actions together
		 */
		initialize: function () {
			var sitemap = Manager.getAction('SiteMap');
			var pageinfo = Manager.getAction('PageInfo');
			var pagemanager = Manager.getAction('Page');
			
			//When page is selected, open page info block 
			sitemap.on('page-select', function (event) {
				//Get element on which user clicked
				var node = SU.Manager.SiteMap.getNodeById(event.data.id);
				//Get HTMLElement from Y.Node
				node = Y.Node.getDOMNode(node.one('div'));
				
				//Show page info block
				pageinfo.execute(event.data, node);
			});
			
			//When sitemap is hidden, hide also PageInfo
			sitemap.addChildAction('PageInfo');
			
			//When page info block is hidden, deselect page
			pageinfo.on('visibleChange', function (event) {
				if (!event.newVal) {
					sitemap.deselectPage();
				}
			});
			
			//When user clicks on one of the versions, show page action
			pageinfo.on('versionClick', function (event) {
				sitemap.hide();
				pagemanager.execute(event.data);
			});
			
			this.setupHeader();
		}
	});
	
});