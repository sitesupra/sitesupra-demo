/*
 * Layout
 * DEPRECATED!? In content-manager was replaced with PluginLayout 
 */
Supra(function () {
	
	var M = Supra.Manager;
	var layout = Supra.LayoutManager;
	
	var container = M.getAction('PageManager');
	var top = M.getAction('PageTop');
	var bottom = M.getAction('PageBottom');
	var content = M.getAction('PageContent');
	
	layout.addWidget(container, {
		'left': 20, 'top': 67, 'right': 20, 'bottom': 15
	});
	
	layout.addWidget(top, {
		'left': 20, 'top': 106, 'right': 20
	});
	
	layout.addWidget(bottom, {
		'left': 20, 'bottom': 15, 'right': 20
	});
	
	layout.addWidget(content, {
		'left': 20, 'right': 20,
		'offset': [
			{'widget': top, 'position': 'top', 'margin': 15},
			{'widget': bottom, 'position': 'bottom', 'margin': 15}
		]
	});
	
	layout.syncUI();
	
});
