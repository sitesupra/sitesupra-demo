YUI.add('supra.manager-action-plugin-footer', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginFooter () {
		PluginFooter.superclass.constructor.apply(this, arguments);
	};
	
	PluginFooter.NAME = 'PluginFooter';
	
	Y.extend(PluginFooter, Action.PluginBase, {
		
		initialize: function () {
			
			if (!this.placeholders) {
				Y.log('Can\'t find container to create Form for Action ' + this.host.NAME + '. Please make sure there is a template', 'error');
				return;
			}
			
			//Find container
			var node = this.host.one('div.footer');
			
			//Add widget
			if (node) {
				var config = {
					'srcNode': node
				};
				this.addWidget(new Supra.Footer(config));
			}
		},
		
		render: function () {
			PluginFooter.superclass.render.apply(this, arguments);
			
			//Find panel
			var panel = this.host.getPluginWidgets('PluginPanel', true);
			panel = panel.length ? panel[0] : null;

			//Find form
			var form = this.host.getPluginWidgets('PluginForm', true);
			form = form.length ? form[0] : null;

			//Close button should close form
			var cancel = this.instances.footer.getButton('cancel');
			if (cancel && panel) {
				cancel.on('click', panel.hide, panel);
			}
		},
		
		execute: function () {
			PluginFooter.superclass.execute.apply(this, arguments);
			
			// @TODO reset form values
		}
		
	});
	
	Action.PluginFooter = PluginFooter;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.footer']});