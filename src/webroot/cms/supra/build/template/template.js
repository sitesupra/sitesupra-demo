/**
 * ==== DEPRECATED ====
 * 
 * Replacement:
 * 		handlebars.js ?
 * 		data-... attributes ?
 */



/**
 * Based on John Resig micro templating
 * http://ejohn.org/blog/javascript-micro-templating/
 * 
 * Example:
 * 		<div class="{%=classname%}">{%=title%}</div>
 * 		{% if (description) { %} <p>{%=description%}</p> {% } %}
 */
YUI.add('supra.template', function(Y) {
	
	function Template (config) {
		if (Y.Lang.isString(config)) {
			config = {
				template: config
			};
		}
		
		this.config = config;
		this.parseTemplate();
	};
	
	Template.NAME = 'template';
	Template.ATTRS = {};
	Template.HTML_PARSER = {};
	
	Y.extend(Template, Y.Base, {
		config: null,
		
		render: function (data) {
			return this.config.fn(data);
		},
		
		parseTemplate: function () {
			var tpl = ('template' in this.config ? this.config.template : null);
			var fn = null;
			
			if (tpl) {
				
				tpl = tpl.replace(/\'/g, '\\\'').replace(/\n/g, '\\n').replace(/\r/g, '\\r');

				fn = new  Function("obj",
					"var p=[],print=function(){p.push.apply(p,arguments);};\
					 with(obj){p.push('" +
						tpl
						  .split("{%").join("\t")
						  .replace(/((^|%})[^\t]*)'/g, "$1\r")
						  .replace(/\t=(.*?)%}/g, "',$1,'")
						  .split("\t").join("');")
						  .split("%}").join("p.push('")
						  .split("\r").join("\\'") +
					"');}return p.join('');"
				);
	  		
			} else {
				fn = function (data) { return ''; };
			}
			
			this.config.fn = fn;
		}
	});
	
	Supra.Template = Template;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});