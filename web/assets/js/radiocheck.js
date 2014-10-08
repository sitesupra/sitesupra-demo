!function($) {
	
	var toggle = '[data-toggle="radiocheck"]',
		Radiocheck = function (element, options) {
			this.handle = $(element);
			this.type = this.handle.attr('data-type');
			this.labels = $('label', this.handle);
			this.labels.click($.proxy(this.onClick, this));
			
			this.labels.each(function () {
				var $this = $(this),
					input = $('input', $this);
				
				if (input.attr('checked')) {
					$this.addClass('checked');
				}
			});
		};
	
	Radiocheck.prototype = {
		
		constructor: Radiocheck,
		
		onClick: function (e) {
			var $this = $(e.currentTarget),
				input = $('input', $this);
			
			if (this.type == 'radio') {
				this.labels.filter('.checked').removeClass('checked').find('input').attr('checked', false);
				$this.addClass('checked');
				input.attr('checked', true);
			} else {
				if ($this.toggleClass('checked').hasClass('checked')) {
					input.attr('checked', true);
				} else {
					input.attr('checked', false);
				}
			}
		}
	
	};
	
	$.fn.radiocheck = function (option) {
		return this.each(function () {
			var $this = $(this),
				data = $this.data('radiocheck'),
				options = typeof option == 'object' && option;
			if (!data) $this.data('radiocheck', (data = new Radiocheck(this, options)));
			if (typeof option == 'string') data[option].call($this);
		});
	};

	$.fn.radiocheck.defaults = {
	};

	$.fn.radiocheck.Constructor = Radiocheck;
	
	$(toggle).radiocheck();
	
}(window.jQuery);