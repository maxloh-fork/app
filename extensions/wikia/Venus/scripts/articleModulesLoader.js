/*global require*/
require([
	'jquery', 'venus.lightboxLoader', 'scrollableTables', 'wikia.window', 'wikia.document'
], function ($, lightboxLoader, scrollableTables, win, doc) {
	'use strict';
	var $win = $(win);

		/** Look for all tables on article and add or remove scrollbar if needed */
		function scanTables() {
			var innerArticle = doc.getElementById('mw-content-text'),
				tables = innerArticle.getElementsByClassName('article-table');

			[].forEach.call(tables, function (table) {
				scrollableTables.adjustScroll(table, innerArticle.offsetWidth);
			});
		}

		//scan for tables in article and if table is too wide add scrollbar
		scanTables();

		$(function () {
			//Lightbox initialization needs to be done after DOMReady
			//in order to be sure that Bucky is in place
			lightboxLoader.init();
		});

			$win
				.on('resize', $.throttle(100, scanTables))
				// wikiaTabClicked event is triggered when user switches between different tabs in article
				.on('wikiaTabClicked', scanTables);

		// #############################################################
		// Temporary left nav init
	}
);
