YUI.add('supra.page-content-proto', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function ContentProto () {
		this.children = {};
		this.node = null;
		this.overlay = null;
		
		ContentProto.superclass.constructor.apply(this, arguments);
	}
	
	ContentProto.NAME = 'page-content-proto';
	ContentProto.CLASS_NAME = Y.ClassNameManager.getClassName(ContentProto.NAME);
	ContentProto.ATTRS = {
		'data': {
			value: null
		},
		'win': {
			value: null
		},
		'doc': {
			value: null
		},
		'body': {
			value: null
		},
		'editing': {
			value: false,
			setter: '_setEditing'
		},
		'parent': {
			value: null
		},
		'super': {
			value: null
		},
		'editable': {
			value: false,
			readOnly: true
		},
		'highlight': {
			value: false,
			setter: '_setHighlight'
		},
		'highlightOverlay': {
			value: false,
			setter: '_setHighlightOverlay'
		},
		'title': ''
	};
	
	Y.extend(ContentProto, Y.Base, {
		children: {},
		node: null,
		overlay: null,
		
		destructor: function () {
			if (this.get('editing')) {
				this.get('super').set('activeContent', null);
			}
			
			if (this.overlay) {
				this.overlay.remove();
			}
			
			var node = this.getNode();
			if (node) {
				node.remove();
			}
		},
		
		getType: function () {
			var data = this.get('data');
			return (data ? data.type : null);
		},
		
		getId: function () {
			var data = this.get('data');
			return (data ? data.id : null);
		},
		
		getNodeId: function () {
			var id = 'content_' + this.getType() + '_' + this.getId();
			return id;
		},
		
		getTitle: function () {
			return this.get('title');
		},
		
		getNode: function () {
			if (!this.node) {
				this.node = this.get('body').one('#' + this.getNodeId());
			}
			return this.node;
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
			
			//Use timeout to make sure everything is styled before doing sync
			setTimeout(Y.bind(this.syncUI, this), 1);
		},
		
		bindUI: function () {
			if (this.get('editable') && this.overlay) {
				this.overlay.on('click', function() {
				
					this.get('super').set('activeContent', this);
					
				}, this);
				
				//Handle block save / cancel
				this.on('block:save', function () {
					// Unset active content
					this.get('super').set('activeContent', null);
				});
				this.on('block:cancel', function () {
					// Unset active content
					this.get('super').set('activeContent', null);
				});
			}
		},
		
		syncUI: function (traverse) {
			if (this.overlay) {
				var node = this.getNode();
				var w = node.get('offsetWidth'), h = node.get('offsetHeight');
			
				this.overlay.setStyles({
					width: w + 'px',
					height: h + 'px'
				});
			}
			
			if (traverse !== false) {
				for (var i in this.children) {
					this.children[i].syncUI();
				}
			}
		},
		
		renderOverlay: function () {
			var div = new Y.Node(this.get('doc').createElement('DIV'));
			this.overlay = div;
			this.overlay.set('innerHTML', '<span>Click to edit!</span>');
			this.overlay.addClass(Y.ClassNameManager.getClassName(ContentProto.NAME, 'overlay'));
			
			this.getNode().insert(div, 'before');
		},
		
		createBlock: function (data) {
			var win = this.get('win');
			var doc = this.get('doc');
			var body = this.get('body');
			
			var type = data.type;
			var classname = type[0].toUpperCase() + type.substr(1);
			
			if (classname in Action) {
				var block = this.children[data.id] = new Action[classname]({
					'doc': doc,
					'win': win,
					'body': body,
					'data': data,
					'parent': this,
					'super': this.get('super')
				});
				block.render();
			}
			
			return block;
		},
		
		renderUI: function () {
			var data = this.get('data');
			
			if ('contents' in data) {
				for(var i=0,ii=data.contents.length; i<ii; i++) {
					this.createBlock(data.contents[i]);
				}
			}
			
			if (!this.getNode()) {
				var type = data.type;
				var id = data.id;
				var node = Y.Node.create('<div id="content_' + type + '_' + id + '" class="yui3-page-content yui3-page-content-' + type + ' yui3-page-content-' + type + '-' + id + '">' + data.value || '' + '</div>');
				
				this.node = node;
				this.get('parent').getNode().append(node);
			}
		},
		
		isChildTypeAllowed: function (type) {
			var data = this.get('data');
			if ('allow' in data) {
				for(var i=0, ii=data.allow.length; i<ii; i++) {
					if (data.allow[i] == type) {
						return true;
					}
				}
			}
			return false;
		},
		
		removeChild: function (child) {
			for(var i in this.children) {
				if (this.children[i] === child) {
					delete(this.children[i]);
					child.destroy();
				}
			}
		},
		
		_setEditing: function (value) {
			if (value == this.get('editing')) return !!value;
			
			var classname_overlay = Y.ClassNameManager.getClassName(ContentProto.NAME, 'editing');
			var classname_node = Y.ClassNameManager.getClassName(this.constructor.NAME, 'editing');
			
			if (value) {
				if (this.overlay) {
					this.overlay.addClass(classname_overlay);
				}
				this.getNode().addClass(classname_node);
				this.getNode().addClass('yui3-page-content-editing');
				
				this.fire('editing-start');
				this.get('super').fire('editing-start', this.get('data'));
			} else {
				if (this.overlay) this.overlay.removeClass(classname_overlay);
				this.getNode().removeClass(classname_node);
				this.getNode().removeClass('yui3-page-content-editing');
				
				this.fire('editing-end');
				this.get('super').fire('editing-end', this.get('data'));
				this.syncUI();
			}
			
			return !!value;
		},
		
		_setHighlightOverlay: function (value) {
			if (!this.overlay) return false;
			if (value == this.get('highlightOverlay')) return !!value;
			
			var classname_overlay = Y.ClassNameManager.getClassName(ContentProto.NAME, 'overlay', 'hover');
			
			if (value) {
				this.overlay.addClass(classname_overlay);
			} else {
				this.overlay.removeClass(classname_overlay);
			}
			
			return !!value;
		},
		
		_setHighlight: function (value) {
			if (value) {
				if (this.overlay) {
					this.overlay.addClass(classname_overlay);
				}
				
				this.getNode().addClass('yui3-highlight-' + this.get('data').type);
				if (this.get('editing')) {
					this.set('editing', false);
				}
				if (this.get('highlightOverlay')) {
					this.set('highlightOverlay', false);
				}
			} else {
				this.getNode().removeClass('yui3-highlight-' + this.get('data').type);
			}
			
			return !!value;
		}
		
	});
	
	Action.Proto = ContentProto;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});