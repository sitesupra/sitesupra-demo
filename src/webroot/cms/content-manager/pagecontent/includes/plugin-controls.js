YUI.add('supra.page-content-controls', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function Controls () {
		Controls.superclass.constructor.apply(this, arguments);
	}
	
	Controls.NAME = 'page-content-controls';
	Controls.NS = 'properties';
	Controls.ATTRS = {
		'visible': {
			'value': false,
			'setter': '_setVisible'
		},
		'node': {
			'value': null
		}
	};
	
	Y.extend(Controls, Y.Plugin.Base, {
		
		_node_content: null,
		
		show: function () {
			this.set('visible', true);
		},
		
		hide: function () {
			this.set('visible', false);
		},
		
		destructor: function () {
			this.get('node').remove();
		},
		
		initializer: function (config) {
			//Bind to start editing and end editing events
			this.get('host').on('editing-start', function () {
				this.show();
			}, this);
			
			this.get('host').on('editing-end', function () {
				this.hide();
			}, this);
			
			//Create content
			if (!this.get('node')) {
				var node = Y.Node.create('<div class="yui3-properties yui3-hidden"><div class="yui3-properties-content"></div></div>');
				var cont = node.one('div.yui3-properties-content');
				this._node_content = cont;
					
				//Save button
				var btn = new Supra.Button({'label': 'Save', 'style': 'mid-blue'});
					btn.render(cont);
					
					btn.on('click', function () {
						//@TODO Save?
						this.get('host').get('super').set('activeContent', null);
					}, this);
				
				var lbl = Y.Node.create('<span>or</span>');
					cont.append(lbl);
					
				var link = Y.Node.create('<a href="javascript://">Cancel</a>');
					cont.append(link);
					link.on('click', function () {
						//@TODO Revert changes?
						this.get('host').get('super').set('activeContent', null);
					}, this);
				
				this.get('host').getNode().insert(node, 'after');
				this.set('node', node);
			}
		},
		
		_setVisible: function (value) {
			if (value) {
				this.get('node').removeClass('yui3-hidden');
			} else {
				this.get('node').addClass('yui3-hidden');
			}
			
			return !!value;
		}
	});
	
	Action.PluginControls = Controls;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'plugin', 'supra.button', 'supra.form']});