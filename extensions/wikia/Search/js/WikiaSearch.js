(function($, window) {

var WikiaSearch = {
	init: function() {
		$('form#powersearch input[name=title]').val('Special:WikiaSearch');

		var hiddenInputs = $('input.default-tab-value');
		$('section.AdvancedSearch input[type="checkbox"]').change(function() {
			hiddenInputs.remove();
		});

		var advancedDiv = $('#AdvancedSearch'),
			advancedCheckboxes = advancedDiv.find('input[type="checkbox"]');

		var advancedOptions = false;
		if ( window.location.hash && window.location.hash == '#advanced' ) {
			advancedDiv.slideToggle('fast');
			advancedOptions = !advancedOptions;
		}

		$('#advanced-link').on('click', function(e) {
			e.preventDefault();
			advancedDiv.slideToggle('fast', function() {
				advancedOptions = !advancedOptions;
			});
		});

		$('#mw-search-select-all').click(function(){
			if ($(this).attr('checked')) {
				advancedCheckboxes.attr('checked', 'checked');
			} else {
				advancedCheckboxes.attr('checked', false);
			}
		});

		this.initVideoTabEvents();
		this.trackSearchResultsImpression();

		$('#search-v2-form').submit( function() {
			if ( advancedOptions && this.action.indexOf( '#advanced' ) < 0 ) {
				this.action += 'advanced';
			}
			if ( !advancedOptions ) {
				this.action = this.action.split('#')[0];
				this.action += '#';
			}
			console.log('SEARCH SUBMIT');
		});
	},
	initVideoTabEvents: function() {
		var videoFilterOptions = $('.search-filter-sort');

		if(!videoFilterOptions.length) {
			return;
		}

		videoFilterOptions.find('.search-filter-sort-overlay').remove();

		var searchForm = $('#search-v2-form'),
			videoRadio = $('#filter-is-video'),
			videoOptions = videoRadio.parent().next(),
			filterInputs = $('input[type="radio"][name="filters[]"]');

		// Show and hide video filter options when radio buttons change.
		filterInputs.on('change', function() {
			if(videoRadio.is(':checked')) {
				videoOptions
					.find('input') // only re-enable inputs, we'll handle the select input separately
					.attr('disabled', false);
			} else {
				videoOptions
					.find('input, select')
					.attr('disabled', true)
					.attr('checked', false);
			}
			// Refresh search results
			searchForm.submit();
		});

		// If the input isn't handled above, do a form submit
		videoFilterOptions.find('input, select').not(filterInputs).on('change', function() {
			// Refresh search results
			searchForm.submit();
		});

	},
	trackSearchResultsImpression() {
		var queryparams = new URL(window.location).searchParams;
		var query = queryparams.get('search') || queryparams.get('query');

		if (!query) {
			return;
		}

		var payload = {
			searchPhrase: query,
			filters: {},
			results: [], // TODO: ???
			page: parseInt(queryparams.get('page')) || 1,
			limit: 0, // TODO: count of result
			sortOrder: 'default',
			app: 'app',
			siteId: parseInt(window.wgCityId),
			searchId: 'aaa', // TODO: generate on submitting search form and pass as a query param; if query is present and searchId not then it needs to be generated
			pvUniqueId: window.pvUID || "dev", // on dev there is no pvUID available
		};
		// TODO: gdpr compliance
		console.log(payload);
	}
};


$(function() {
	WikiaSearch.init();
});

})(jQuery, this);
