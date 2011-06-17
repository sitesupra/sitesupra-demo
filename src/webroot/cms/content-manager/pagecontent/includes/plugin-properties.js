/*
 * NOT USED!!!
 */

YUI.add('supra.page-content-properties', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function Properties () {
		Properties.superclass.constructor.apply(this, arguments);
	}
	
	Properties.NAME = 'page-content-properties';
	Properties.NS = 'properties';
	Properties.ATTRS = {
		'node': {
			'value': null
		},
		'data': {
			'value': {}
		},
		'properties': {
			'value': {}
		},
		'form': {
			'value': null
		}
	};
	
	Y.extend(Properties, Y.Plugin.Base, {
		
		_node_content: null,
		
		destructor: function () {
			this.get('node').remove();
		},
		
		initializer: function (config) {
			var data = this.get('data');
			
			if (!data || !('type' in data)) return;
			
			var type = data.type;
			var block = SU.Manager.Blocks.getBlock(type);
			
			if (!block) return;
			this.set('properties', block.properties);
			
			//Bind to start editing and end editing events
			this.get('host').on('editing-start', function () {
				this.show();
			}, this);
			
			this.get('host').on('editing-end', function () {
				this.hide();
			}, this);
			
			//Properties form
			var form_config = {'fields': []};
			var properties = this.get('properties');
			
			for(var i=0, ii=properties.length; i<ii; i++) {
				form_config.fields.push(properties[i]);
			}
			
			var form = new Supra.Form(form_config);
				form.render(cont);
			
			this.set('form', form);
			
			//Delete button
			var btn = new Supra.Button({'label': 'Delete', 'style': 'mid-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-delete');
				btn.on('click', function () {
					this.fire('delete');
				}, this);
		}
	});
	
	Action.PluginProperties = Properties;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'plugin', 'supra.button', 'supra.form']});