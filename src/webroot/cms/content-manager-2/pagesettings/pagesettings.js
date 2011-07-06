//Invoke strict mode
"use strict";

//Add module definition
SU.addModule('website.template-list', {
	path: 'pagesettings/modules/template-list.js',
	requires: ['widget', 'website.template-list-css']
});
SU.addModule('website.template-list-css', {
	path: 'pagesettings/modules/template-list.css',
	type: 'css'
});

SU.addModule('website.version-list', {
	path: 'pagesettings/modules/version-list.js',
	requires: ['widget', 'website.version-list-css']
});
SU.addModule('website.version-list-css', {
	path: 'pagesettings/modules/version-list.css',
	type: 'css'
});


SU('website.template-list', 'website.version-list', 'supra.form', 'supra.calendar', 'anim', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Calendar dates
	var DEFAULT_DATES = [
		{'date': '2011-06-16', 'title': 'Select today'},
		{'date': '2011-06-17', 'title': 'Select tomorrow'}
	];		
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('PageSettings');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageSettings',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Form instance
		 * @type {Object}
		 */
		form: null,
		
		/**
		 * Current slide ID
		 * @type {String}
		 */
		slide_id: null,
		
		/**
		 * Slide animation
		 * @type {Object}
		 */
		slide_anim: null,
		
		/**
		 * Buttons
		 * @type {Object}
		 */
		button_cancel: null,
		button_back: null,
		
		/**
		 * Template list object
		 * @type {Object}
		 */
		template_list: null,
		
		/**
		 * Version list object
		 * @type {Object}
		 */
		version_list: null,
		
		/**
		 * Page data
		 * @type {Object}
		 */
		page_data: {},
		
		/**
		 * Show slide
		 */
		showSlide: function (slide_id, quick) {
			if (slide_id == this.slide_id) return;
			
			var old_slide_id = this.slide_id,
				//Nodes
				root_item = Y.one('#slideMain'),
				new_item = (slide_id ? Y.one('#' + slide_id) : null),
				old_item = (this.slide_id ? Y.one('#' + this.slide_id) : root_item),
				//Animation position
				to = 0,
				from = old_item.get('offsetWidth'),
				scroll_top = root_item.ancestor().get('scrollTop'),
				//Callback
				fn = slide_id ? 'on' + (slide_id.substr(0,1).toUpperCase() + slide_id.substr(1)) : null;
			
			if (new_item) {
				to = root_item.get('offsetWidth');
				from = 0;
				new_item.removeClass('hidden');
				
				this.button_cancel.hide();
				this.button_back.show();
			} else {
				root_item.removeClass('hidden');
				this.button_cancel.show();
				this.button_back.hide();
			}
			
			//Create animation
			if (!this.slide_anim) {
				this.slide_anim = new Y.Anim({
				    node: root_item.ancestor(),
				    duration: 0.5,
				    easing: Y.Easing.easeOutStrong
				});
			}
			
			//If there is old item, then after animation hide it
			if (old_item) {
				if (quick) {
					old_item.addClass('hidden');
				} else {
					this.slide_anim.once('end', function () {
						old_item.addClass('hidden');
						root_item.ancestor().set('scrollLeft', to);
					});
				}
			}
			
			this.slide_id = slide_id;
			
			//Animate
			if (quick) {
				root_item.ancestor().set('scrollLeft', to);
			} else {
				this.slide_anim.set('to', {scroll: [to, 0]});
				this.slide_anim.set('from', {scroll: [from, scroll_top]});
				this.slide_anim.run();
			}
			
			//Call "onSlide..." callback function
			if (fn && fn in this) {
				this[fn](new_item);
			}
		},
		
		/**
		 * When schedule slide is shown create widget, bind listeners
		 */
		onSlideSchedule: function (node) {
			var date = this.page_data.scheduled_date;
			
			//Create calendar if it doesn't exist
			if (!this.calendar) {
				//Create calendar
				var calendar = this.calendar = new Supra.Calendar({
					'srcNode': node.one('.calendar'),
					'date': date,
					'minDate': new Date(),
					'dates': DEFAULT_DATES
				});
				calendar.render();
				
				//Create apply button
				var btn = new Supra.Button({srcNode: node.one('button')});
				btn.render();
				btn.on('click', this.onSlideScheduleApply, this);
			} else {
				//Set date
				this.calendar.set('date', date);
				this.calendar.set('displayDate', date);
			}
			
			//Set time
			var time = Y.DataType.Date.parse(this.page_data.scheduled_time, {format: '%H:%M'}),
				hours = (time ? time.getHours() : 0),
				minutes = (time ? time.getMinutes() : 0);
			
			this.form.getInput('schedule_hours').set('value', hours < 10 ? '0' + hours : hours);
			this.form.getInput('schedule_minutes').set('value', minutes < 10 ? '0' + minutes : minutes);
		},
		/**
		 * On schedule apply button click save values
		 */
		onSlideScheduleApply: function () {
			//Save date
			this.page_data.scheduled_date = Y.DataType.Date.format(this.calendar.get('date'));
			
			//Save time
			var inp_h = this.form.getInput('schedule_hours'),
				inp_m = this.form.getInput('schedule_minutes'),
				date = new Date();
			
			date.setHours(parseInt(inp_h.getValue(), 10) || 0);
			date.setMinutes(parseInt(inp_m.getValue(), 10) || 0);
			
			this.page_data.scheduled_time = Y.DataType.Date.format(date, {format: '%H:%M'});
			this.showSlide(null);
		},
		
		
		/**
		 * When template slide is shown create widget, bind listeners
		 */
		onSlideTemplate: function (node) {
			if (!this.template_list) {
				this.template_list = new Supra.TemplateList({
					'srcNode': node.one('ul.template-list'),
					'uri': this.getPath() + 'templates' + Loader.EXTENSION_DATA
				});
				
				this.template_list.render();
				
				this.template_list.on('change', function (e) {
					this.page_data.template = e.template;
					
					this.setFormValue('template', this.page_data);
					this.showSlide(null);
				}, this);
			}
		},
		
		/**
		 * When version slide is shown create widget, bind listeners
		 */
		onSlideVersion: function (node) {
			if (!this.version_list) {
				this.version_list = new Supra.VersionList({
					'srcNode': node.one('div.version-list'),
					'uri': this.getPath() + 'versions' + Loader.EXTENSION_DATA
				});
				
				this.version_list.render();
				
				this.version_list.on('change', function (e) {
					this.page_data.version = e.version;
					
					this.setFormValue('version', this.page_data);
					this.showSlide(null);
				}, this);
			}
		},
		
		
		
		/**
		 * Update scroll position
		 */
		syncSlidePos: function () {
			if (this.get('visible') && this.slide_id) {
				var root_item = Y.one('#slideMain'),
					node = root_item.ancestor(),
					width = root_item.get('offsetWidth');
				
				node.set('scrollLeft', width);
			}
		},
		
		/**
		 * Create form
		 */
		createForm: function () {
			
			var buttons = this.getContainer().all('button');
			
			//Apply button
			(new Supra.Button({'srcNode': buttons.filter('.button-save').item(0), 'style': 'mid-blue'}))
				.render().on('click', this.saveSettingsChanges, this);
				
			//Close button
			this.button_cancel = new Supra.Button({'srcNode': buttons.filter('.button-cancel').item(0)});
			this.button_cancel.render().on('click', this.cancelSettingsChanges, this);
			
			//Back button
			this.button_back = new Supra.Button({'srcNode': buttons.filter('.button-back').item(0)});
			this.button_back.render().hide().on('click', function () { this.showSlide(null); }, this);
			
			//Delete button
			(new Supra.Button({'srcNode': buttons.filter('.button-delete').item(0), 'style': 'mid-red'}))
				.render().on('click', this.deletePage, this);
			
			//Meta button
			(new Supra.Button({'srcNode': buttons.filter('.button-meta').item(0)}))
				.render().on('click', function () { this.showSlide('slideMeta'); }, this);
			
			//Version button
			(new Supra.Button({'srcNode': buttons.filter('.button-version').item(0), 'style': 'large'}))
				.render().on('click', function () { this.showSlide('slideVersion'); }, this);
			
			//Template button
			(new Supra.Button({'srcNode': buttons.filter('.button-template').item(0), 'style': 'template'}))
				.render().on('click', function () { this.showSlide('slideTemplate'); }, this);
			
			//Schedule button
			(new Supra.Button({'srcNode': buttons.filter('.button-schedule').item(0)}))
				.render().on('click', function () { this.showSlide('slideSchedule'); }, this);
			
			//Blocks button
			(new Supra.Button({'srcNode': buttons.filter('.button-blocks').item(0)}))
				.render().on('click', function () {
					this.renderBlocks();
					this.showSlide('slideBlocks');
				}, this);
			
			//Form
			var form = this.form = new Supra.Form({
				'srcNode': this.getContainer('form')
			});
			form.render();
			
			//When layout position/size changes update slide
			Manager.LayoutRightContainer.layout.on('sync', this.syncSlidePos, this);
		},
		
		/**
		 * Delete page
		 */
		deletePage: function () {
			// @TODO
			this.hide();
		},
		
		/**
		 * Save changes
		 */
		saveSettingsChanges: function () {
			var page_data = this.page_data;
			Supra.mix(page_data, this.form.getValuesObject());
			
			//Remove unneded data for save request
			var post_data = Supra.mix({}, page_data);
			post_data.version = post_data.version.id;
			post_data.template = post_data.template.id;
			
			delete(post_data.version_id);
			delete(post_data.schedule_hours);
			delete(post_data.schedule_minutes);
			delete(post_data.path_prefix);
			delete(post_data.internal_html);
			delete(post_data.contents);
			
			post_data.context = Supra.data.get('context');
			post_data.language = Supra.data.get('language');
			
			//Save data
			var url = this.getDataPath('save');
			Supra.io(url, {
				'data': post_data,
				'method': 'POST',
				'on': {
					'success': function (transaction, version) {
						page_data.version = version;
						Manager.Page.setPageData(page_data);
					}
				}
			}, this);
			
			this.hide();
		},
		
		/**
		 * CancelSave changes
		 */
		cancelSettingsChanges: function () {
			this.hide();
		},
		
		/**
		 * Set form values
		 */
		setFormValues: function () {
			var page_data = this.page_data;
			this.form.setValues(page_data, 'id');
			
			//Set version info
			this.setFormValue('version', page_data);
			
			//Set template info
			this.setFormValue('template', page_data);
		},
		
		/**
		 * Set form value
		 * 
		 * @param {Object} key
		 * @param {Object} value
		 */
		setFormValue: function (key, page_data) {
			switch(key) {
				case 'template':
					//Set version info
					var node = this.getContainer('.button-template small');
					node.one('span').set('text', page_data.template.title);
					node.one('img').set('src', page_data.template.img);
					break;
				case 'version':
					var node = this.getContainer('.button-version small');
					node.one('b').set('text', page_data.version.title);
					node.one('span').set('text', page_data.version.author + ', ' + page_data.version.date);
					break;
				default:
					var obj = {};
					obj[key] = page_data[key];
					this.form.setValues(obj, 'id');
					break;
			}
		},
		
		/**
		 * Render all block list
		 */
		renderBlocks: function () {
			var blocks = Manager.PageContent.getContentBlocks(),
				block = null,
				block_type = null,
				block_definition = null,
				container = this.getContainer('ul.block-list'),
				item = null;
			
			container.all('li').remove();
			
			for(var id in blocks) {
				if (!blocks[id].isLocked()) {
					block = blocks[id];
					block_type = block.getType();
					block_definition = Manager.Blocks.getBlock(block_type);
					
					item = Y.Node.create('<li class="clearfix"><div><img src="' + block_definition.icon + '" alt="" /></div><p>' + Y.Lang.escapeHTML(block_definition.title) + '</p></li>');
					item.setData('content_id', id);
					
					container.append(item);
				}
			}
			
			var li = container.all('li');
			li.on('mouseenter', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id');
					
				blocks[content_id].set('highlightOverlay', true);
			});
			li.on('mouseleave', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id');
				
				blocks[content_id].set('highlightOverlay', false);
			});
			li.on('click', function (evt) {
				this.hide();
				
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id');
				
				//Start editing content
				contents = SU.Manager.PageContent.getContentContainer();
				contents.set('activeContent', blocks[content_id]);
				
				//Show properties form
				if (blocks[content_id].properties) {
					blocks[content_id].properties.showPropertiesForm();
				}
			}, this);
		},
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.getContainer().removeClass('hidden');
					} else {
						this.showSlide(null, true);
						this.getContainer().addClass('hidden');
					}
				}
			}, this);
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			if (!this.form) this.createForm();
			this.page_data = Supra.mix({}, Manager.Page.getPageData());
			this.setFormValues();
			
			this.showSlide(null, true);
		}
	});
	
});