/**
 * Frontend script for My Plugin
 */
(function ($) {
	'use strict';

	$(function () {
		$('.my-plugin-hello').on('click', function () {
			var name = $(this).data('name');
			console.log('My Plugin: Hello, ' + name);
		});
	});
})(jQuery);
