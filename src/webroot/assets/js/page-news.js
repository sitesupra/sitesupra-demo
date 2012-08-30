$(function () {
	
	var news_list = $('#news-list');
	
	news_list.find('h3 a').each( function (index, node) {
		$(node).click(
			function(e) {
				e.preventDefault();
				
				// collapse all articles
				news_list.find('div.news-content').addClass('hidden');
				
				// find related to link div with news content
				// add/remove hidden class
				var paragraph = $(e.target).parent().next('div');
				if (paragraph.hasClass('hidden')) {
					paragraph.removeClass('hidden');
				} else {
					paragraph.addClass('hidden');
				}
				
				// re-sync columns height
				$.columns.add($('[data-column-id]')).sync();
				
				// change hash
				window.location.hash = '#' + paragraph.attr('id');
				
			});
	}) ;
	
	if (window.location.hash) {
		// if hash is set, try to find related article
		var article = $('#' + window.location.hash.substring(1));
		if (article.length) {
			// expand it
			article.removeClass('hidden');
			
			// scroll page to article content div
			$('html,body').animate({
				scrollTop: '+=' + article.offset().top + 'px'
			}, 'fast');
			
			// re-sync columns height
			$.columns.add($('[data-column-id]')).sync();
		}
	}
})	