/**
 * Template selection input
 */
YUI.add("website.template-list", function (Y) {
	//Invoke strict mode
	"use strict";
	
	function TemplateList (config) {
		TemplateList.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._templates = null;
	}
	
	TemplateList.NAME = "template-list";
	TemplateList.CSS_PREFIX = 'su-' + TemplateList.NAME;
	
	TemplateList.ATTRS = {
		'requestUri': {
			value: null
		},
		'template': {
			value: null,
			setter: '_setTemplate'
		}
	};
	
	TemplateList.HTML_PARSER = {};
	
	/**
	 * Item template
	 * @type {Function}
	 */
	TemplateList.TEMPLATE_ITEM = Supra.Template.compile('\
		<li class="clearfix item {% if id == selected %}selected{% endif %}">\
			<div class="item-content">\
				<div class="item-content-inner">\
					<div class="img"><img src="{{ img }}" alt="" /></div>\
					<span></span><p><b>{{ title|e }}</b>{{ description|e }}</p>\
				</div>\
			</div>\
		</li>');
	
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
			var uri = this.get('requestUri');
			Supra.io(uri, this._loadTemplatesComplete, this);
		},
		
		/**
		 * Handle template loading completion
		 * 
		 * @param {Object} transaction
		 * @param {Object} data
		 * @private
		 */
		_loadTemplatesComplete: function (data) {
			this._templates = data;
			this.syncUI();
		},
		
		/**
		 * Set selected template
		 */
		_setTemplate: function (value) {
			this.get('contentBox').all('li').removeClass('selected').each(function () {
				if (this.getData('template_id') == value) {
					this.addClass('selected');
				}
			});
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
				item,
				selected = this.get('template');
			
			//Remove old items
			content.all('li').remove();
			
			//Create new items
			for(var i=0,ii=templates.length; i<ii; i++) {
				template = templates[i];
				
				item = TemplateList.TEMPLATE_ITEM(
					Supra.mix({
						'selected': selected,
						'description': 'Promo pages, part start pages'
					}, template)
				);
				
				item = Y.Node.create(item);
				item.setData('template_id', template.id);
				
				content.append(item);
			}
			
			content.all('li').on('click', function (evt) {
				var target = evt.target.closest('LI'),
					template_id = target.getData('template_id');
				
				for(var i=0,ii=templates.length; i<ii; i++) if (template_id == templates[i].id) {
					this.fire('change', {'template': templates[i]});
					return;
				}
			}, this);
			
			//Fire resize event
			var scrollable = content.closest('.su-scrollable');
			if (scrollable) {
				scrollable.fire('contentResize');
			}
		}
	});
	
	Supra.TemplateList = TemplateList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget", "supra.template"]});