YUI.add('supra.page-content-list', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function Content () {
		Content.superclass.constructor.apply(this, arguments);
	}
	
	Content.NAME = 'page-content-list';
	Content.CLASS_NAME = Y.ClassNameManager.getClassName(Content.NAME);
	Content.ATTRS = {
		'title': {
			value: 'Block list'
		}
	};
	
	Y.extend(Content, Action.Proto, {
		drop_target: null,
		
		bindUI: function () {
			Content.superclass.bindUI.apply(this, arguments);
			
			this.on('dragend:hit', function (e) {
				var randomId = +(new Date()) + '' + ~~(Math.random()*100000);
				
				var block = this.createBlock({
					'id': randomId,
					'type': e.block.id,
					'value': e.block.default_value
				});
				
				this.get('super').set('activeContent', block);
				
				return false;
			}, this);
		},
		
		renderOverlay: function () {
			Content.superclass.renderOverlay.apply(this, arguments);
		}
	});
	
	Action.List = Content;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto']});