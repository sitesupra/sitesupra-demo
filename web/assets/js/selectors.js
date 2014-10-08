!function($) {

	var toggle = '[data-toggle="selectors"]',
		Selectors = function ($this, options) {
			var self = this;
			
			this.toggle = $this;
			this.input = $('input', $this);
			this.trigger = $('> a', $this);
			this.dropdown = $('> div', $this);
			this.options = $('a', this.dropdown);
			
			this.trigger.on('click.Selectors', $.proxy(this.onTrigger, this));
			this.options.on('click.Selectors', $.proxy(this.onOptions, this));
			
			this.input.focus(function () {
				self.trigger.trigger('click');
			});
		};
	
	Selectors.prototype = {
		
		constructor: Selectors,
				
		onTrigger: function () {
			
			this.toggle.removeClass('errored');
			
			if (this.toggle.hasClass('opened')) {
				this.hide();
			} else {
				this.show();
			}
		},
		
		show: function () {
			this.toggle.addClass('opened');
			
			var self = this;
			
			this.id = Math.floor(Math.random()*1001);
			this.toggle.attr('data-id', this.id);
			
			this.options.parent().removeClass('active');
			this.options.filter('[href="' + this.input.val() + '"]').parent().addClass('active');
			
			$(document).on('click.fieldSelect' + this.id, function (e) {
				if (!$(e.target).parents('[data-id="' + self.id + '"]').size()) self.hide();
			});
		},
		
		hide: function () {
			this.toggle.removeClass('opened').attr('data-id', false);
			$(document).unbind('click.fieldSelect' + this.id);
			this.id = 0;
		},
		
		onOptions: function (e) {
			if (this.toggle.attr('data-default-behaviour') != "true") {
				e.preventDefault();
			}
			
			var $this = $(e.currentTarget);
			
			this.hide();
			
			this.input.val($this.attr('href'));
			$('span', this.trigger).text($this.text());
			
			this.options.parent().removeClass('active');
			$this.parent().addClass('active');
		}
	
	};
	
	$.fn.selectors = function (option) {
		return this.each(function () {
			var $this = $(this),
				data = $this.data('selectors'),
				options = typeof option == 'object' && option;
			if (!data) $this.data('selectors', (data = new Selectors($(this), options)));
			if (typeof option == 'string') data[option].call($this);
		});
	};

	$.fn.selectors.defaults = {
	};

	$.fn.selectors.Constructor = Selectors;
	
	$(toggle).selectors();
	
}(window.jQuery);