/**
 * Comments block - In CMS on delete button click show confirmation window and delete comment
 */
(function ($) {
	"use strict";
	
	var _super = $.app.module.proto,
		win = window.top || window.self,
		
		// Only in CMS mode Supra object is available
		Supra = win.Supra;

	$.app.Comments = $.app.module($.app.AjaxContent, {
		
		/**
		 * Default method for 'trigger'
		 * @type {String}
		 */
		'default_method': 'confirmRemoveComment',
		
		/**
		 * Request method
		 * @type {String}
		 */
		'method': 'post',
		
		/**
		 * Action name
		 * @type {String}
		 */
		'action': false,
		
		/**
		 * Reload request response type
		 * @type {String}
		 */
		'reload_response_type': 'json',
		
		
		/**
		 * Show confirmation window before removing comment
		 */
		'confirmRemoveComment': function () {
			console.log('confirmRemoveComment');
			// Supra is not defined -> not CMS mode
			if (!Supra) return;
			
			// No "editing" class -> block is not beeing edited
			if (!this.element.closest('.editing').size()) return;
			
			Supra.Manager.executeAction('Confirmation', {
				'message': 'Are you sure you want to delete selected comment?',
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': Supra.Intl.get(['buttons', 'yes']), 'context': this, 'click': this.removeComment},
					{'id': 'no'}
				]
			});
			
		},
		
		/**
		 * Approve comment
		 */
		'approveComment': function () {
			if (!Supra) return;
			
			this.action = 'approve';
			this.url = Supra.Manager.getAction('Blog').getDataPath('../comments/approve');
			
			this.reload({
				'id': this.element.data('commentId'),
				'parent_id': this.element.data('parentId')
			});
		},
		
		/**
		 * Unapprove comment
		 */
		'unapproveComment': function () {
			if (!Supra) return;
			
			this.action = 'unapprove';
			this.url = Supra.Manager.getAction('Blog').getDataPath('../comments/unapprove');
			
			this.reload({
				'id': this.element.data('commentId'),
				'parent_id': this.element.data('parentId')
			});
		},
		
		/**
		 * Fade out comment before sending request
		 */
		'removeComment': function () {
			if (!Supra) return;
			
			this.action = 'remove';
			this.url = Supra.Manager.getAction('Blog').getDataPath('../comments/delete');
			
			this.element.css('opacity', 0.25);
			this.reload({
				'id': this.element.data('commentId'),
				'parent_id': this.element.data('parentId')
			});
		},
		
		/**
		 * On reload remove comment
		 * 
		 * @private
		 */
		'onReload': function (response) {
			// This is response from request to supra action
			if (response && response.status) {
				// Success
				if (this.action == 'remove') {
					this.element.remove();
				} else if (this.action == 'approve') {
					this.element.find('.button-approve').addClass('supra-hidden');
					this.element.find('.button-unapprove').removeClass('supra-hidden');
				} else if (this.action == 'unapprove') {
					this.element.find('.button-approve').removeClass('supra-hidden');
					this.element.find('.button-unapprove').addClass('supra-hidden');
				}
			} else {
				// Error
				if (this.action == 'remove') {
					this.element.css('opacity', 1);
				}
			}
		}
		
	});
	
})(jQuery);