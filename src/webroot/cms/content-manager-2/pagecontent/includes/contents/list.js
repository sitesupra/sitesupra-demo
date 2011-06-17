YUI.add('supra.page-content-list', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function ContentList () {
		ContentList.superclass.constructor.apply(this, arguments);
	}
	
	ContentList.NAME = 'page-content-list';
	ContentList.CLASS_NAME = Y.ClassNameManager.getClassName(ContentList.NAME);
	ContentList.ATTRS = {
		'title': 'Block list'
	};
	
	Y.extend(ContentList, Action.Proto, {
		drop_target: null,
		
		bindUI: function () {
			ContentList.superclass.bindUI.apply(this, arguments);
			
			this.on('dragend:hit', function (e) {
				var randomId = +(new Date()) + '' + ~~(Math.random()*100000);
				
				var block = this.createBlock({
					'id': randomId,
					'type': e.block.id,
					'value': e.block.default_html
				});
				
				this.get('super').set('activeContent', block);
				
				return false;
			}, this);
		},
		
		renderOverlay: function () {
			ContentList.superclass.renderOverlay.apply(this, arguments);
		}
	});
	
	Action.List = ContentList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto']});