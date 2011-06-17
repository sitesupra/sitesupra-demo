/**
 * Template selection input
 */
YUI.add("website.template-list", function (Y) {
	
	function TemplateList (config) {
		TemplateList.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._templates = null;
	}
	
	TemplateList.NAME = "template-list";
	TemplateList.CLASS_NAME = Y.ClassNameManager.getClassName(TemplateList.NAME);
	TemplateList.ATTRS = {
		'uri': null
	};
	
	TemplateList.HTML_PARSER = {};
	
	Y.extend(TemplateList, Y.Widget, {
		
		/**
		 * Template data
		 * @type {Object}
		 * @private
		 */
		_templates: null,
		
		/**
		 * Load templates
		 * 
		 * @private
		 */
		_loadTemplates: function () {
			var uri = this.get('uri');
			Supra.io(uri, this._loadTemplatesComplete, this);
		},
		
		/**
		 * Handle template loading completion
		 * 
		 * @param {Object} transaction
		 * @param {Object} data
		 * @private
		 */
		_loadTemplatesComplete: function (transaction, data) {
			this._templates = data;
			this.syncUI();
		},
		
		syncUI: function () {
			TemplateList.superclass.syncUI.apply(this, arguments);
			
			var templates = this._templates;
			if (!templates) {
				this._loadTemplates();
				return;
			}
			
			var content = this.get('contentBox'),
				template,
				item;
			
			//Remove old items
			content.all('li').remove();
			
			//Create new items
			for(var i=0,ii=templates.length; i<ii; i++) {
				template = templates[i];
				
				item = Y.Node.create('<li class="clearfix"><div><img src="' + template.img + '" alt="" /></div><p>' + Y.Lang.escapeHTML(template.title) + '</p></li>');
				item.setData('template_id', template.id);
				
				content.append(item);
			}
			
			content.all('li').on('click', function (evt) {
				var target = evt.target.test('LI') ? evt.target : evt.target.ancestor('LI'),
					template_id = target.getData('template_id');
				
				for(var i=0,ii=templates.length; i<ii; i++) if (template_id == templates[i].id) {
					this.fire('change', {'template': templates[i]});
					return;
				}
			}, this);
		}
	});
	
	Supra.TemplateList = TemplateList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget"]});