YUI.add('supra.page-content-facebook', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager,
	PageContent = Manager.PageContent;
	
	
	/**
	 * Content block which has editable properties
	 */
	function FacebookBlock () {
		FacebookBlock.superclass.constructor.apply(this, arguments);
	}
	
	FacebookBlock.NAME = 'page-content-facebook';
	FacebookBlock.CLASS_NAME = Y.ClassNameManager.getClassName(FacebookBlock.NAME);
	
	Y.extend(FacebookBlock, PageContent.Editable, {
		
		html: '<div class="custom-html">\
					<a class="fb-account-link">Link your facebook account</a>\
					<h2>Fetched pages:</h2>\
					<ul class="fetched-pages"></ul>\
					<div id="fb-root"></div>\
	 			</div>',
		
		fetchedTemplate: Supra.Template.compile('\
				{% for page in pages %}\
					<li data-id="{{ page.id|escape }}">{{ page.title|escape }} <a>X</a></li>\
				{% endfor %}\
			'),
		
		pages: {
			social: '/social',
			facebook: '/social/facebook',
			addPage: '/social/add-page'
		},
		
		defaultSelect: { 
			'id': '',
			'title': 'Select a page'
		},
		
		/**
		 * When form is rendered add gallery button
		 * @private
		 */
		renderUISettings: function () {
			FacebookBlock.superclass.renderUISettings.apply(this, arguments);
			
			//Find container node
			var slideshow = this.properties.get('slideshow'),
			slide_content = slideshow.getSlide('propertySlideMain').one('.su-slide-content'),
			container = Y.Node.create(this.html),
			existingPages = null;
			
			this.container = container;
			
			//Insert HTML
			slide_content.prepend(container);
			
			
			// Hide unneeded stuff
			var form = this.form = this.properties.get('form');
			form.getInput('tab_name').addClass('hidden');
			
			existingPages = this.existingPages = form.getInput('available_pages');
			//			if(existingPages.get('values').length > 0) {
			//				this.fillDropdown(existingPages.get('values'));
			//			}
			
			existingPages.hide();
			
			/*
			 * @TODO
			 * container is div.custom-html
			 * .one(CSS_SELECTOR)
			 */
			container.one('a').on('click', this.handleLinkClick, this);
			container.one('a').addClass('hidden');
			container.one('h2').addClass('hidden');
			
			container.one('ul.fetched-pages').delegate('click', this.addFBPage, 'a', this);
			
			this.getApplicationData();
		},
		
		showMessage: function (message) {
			Supra.Manager.executeAction('Confirmation', {
				'message': message,
				'buttons': [{
					'id': 'ok', 
					'label': 'OK'
				}]
			});
		},
		
		getApplicationData: function () {
			Supra.io(this.pages.social, {
				'method': 'GET', //or POST
				'suppress_errors': true,
				'on': {
					'success': this.onApplicationData,
					'failure': function (data, status) {
						this.showMessage('Failed to fetch application data.\
											Might facebook account is not linked with sitesupra');
						this.container.one('a').removeClass('hidden');
					}
				}
			}, this);
		},
		
		onApplicationData: function (data, status) {
			if (data !== false) {	
				this.initFB(data.application_id);
				
				if (data.facebook_data != true) {
					this.container.one('a').removeClass('hidden');
				}
				
				if (data.facebook_data == true && data.fetched_pages && data.fetched_pages.length > 0) {	
					this.container.one('h2').removeClass('hidden');
					
					var html = this.fetchedTemplate({
						'pages': data.fetched_pages
					});
					this.container.one('ul.fetched-pages').set('innerHTML', html);
				}
				
				if (data.available_pages && data.available_pages.length > 0) {	
					
					this.fillDropdown(data.available_pages);
					
					if(this.existingPages.get('value') != '') {
						this.form.getInput('tab_name').removeClass('hidden');
					}
					
					this.existingPages.show();
				}
				
			}
		},
		
		addFBPage: function (e) {
			var target = e.target.closest('li'),
			id = target.getAttribute('data-id');
			
			Supra.io(this.pages.addPage, {
				'method': 'POST', //or POST
				'data': {
					'page_id': id
				},
				'on': {
					'success': function (data, status) {
						var values = this.existingPages.get('values');
						
						if( ! data.page) {
							return;
						}
						
						values = values.concat([{
							'id': id, 
							'title': data.page.name
						}]);
						
						this.fillDropdown(values);
						
						this.container.one('h2').removeClass('hidden');
						
						if(data.fetched_pages_count == 0) {
							this.container.one('h2').addClass('hidden');
						}
						
						this.container.one('ul.fetched-pages').one('li[data-id="' + id + '"]').addClass('hidden');
						
						this.existingPages.show();
					}
				}
			}, this);			
		},
		
		initFB: function (applicationId) {
			
			window.fbAsyncInit = function () {
				FB.init({
					appId: applicationId, // App ID
					status: true, // check login status
					cookie: true, // enable cookies to allow the server to access the session
					oauth: true, // enable OAuth 2.0
					xfbml: true  // parse XFBML
				});
			};
			
			var d = document,
			js, id = 'facebook-jssdk';
			
			if (d.getElementById(id)) {
				return;
			}
			
			js = d.createElement('script');
			js.id = id;
			js.async = true;
			js.src = "//connect.facebook.net/en_US/all.js";
			d.getElementsByTagName('head')[0].appendChild(js);
		},
		
		handleLinkClick: function () {
			FB.login(
				Y.bind(function (response) {
					if(response.authResponse) {
						Supra.io(this.pages.facebook, {
							'method': 'POST',		//or POST
							'data': response,
							'on': {
								'success': function (data, status) {
									this.container.one('a').addClass('hidden');
									
									var html = this.fetchedTemplate({
										'pages': data.fetched_pages
									});
									
									if(data.available_pages) {
										this.fillDropdown(data.available_pages);
					
										if(this.existingPages.get('value') != ''){
											this.form.getInput('tab_name').removeClass('hidden');
										}

										this.existingPages.show();
									}
									
									this.container.one('ul.fetched-pages').set('innerHTML', html);
								}
							}
						}, this);
					} else {
						this.showMessage('Failed to authenticate facebook account');
					}
				}, this),
				{
					scope: 'manage_pages, publish_stream , offline_access, email, user_about_me'
				});
		},
		
		fillDropdown: function (values) {
			
			if( ! values.length || values[0].id !== '') {
				values = [this.defaultSelect].concat(values);
			}
			
			this.existingPages.set('values', [].concat(values));
			this.existingPages.show();
			
			this.form.getInput('available_pages').set('value', this.get('data').properties.available_pages);
			
			this.existingPages.on('valueChange', function (evt) {
				if(evt.newVal == '') {
					this.form.getInput('tab_name').addClass('hidden');
				} else {
					this.form.getInput('tab_name').removeClass('hidden');
				}
			}, this);
			
			//Make sure scrollbars are updated
			this.properties.get('slideshow').syncUI();
		}
		
	//		openTabSettings: function () {
	//			var slideshow = this.properties.get('slideshow');
	//			slideshow.set('slide', 'tabSettings');
	//		}
		
	});
	
	PageContent.Facebook = FacebookBlock;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);
	this.fn = function () {};
	
}, YUI.version, {
	requires:['supra.page-content-editable', 'supra.template']
});