/**
 * Environmental Advanced Search - Frontend JavaScript
 * Phase 53 - Advanced Search & Filtering
 */

(function($) {
    'use strict';

    /**
     * Main Search Application Class
     */
    class EnvironmentalAdvancedSearch {
        constructor() {
            this.searchForm = null;
            this.searchInput = null;
            this.resultsContainer = null;
            this.filtersContainer = null;
            this.suggestionsContainer = null;
            this.currentXhr = null;
            this.suggestionTimeout = null;
            this.currentPage = 1;
            this.totalPages = 1;
            this.isLoading = false;
            this.activeFilters = {};
            this.searchHistory = [];
            
            this.init();
        }

        /**
         * Initialize the search application
         */
        init() {
            this.bindElements();
            this.bindEvents();
            this.initializeFilters();
            this.loadSearchHistory();
            
            // Initialize from URL parameters
            this.initializeFromURL();
        }

        /**
         * Bind DOM elements
         */
        bindElements() {
            this.searchForm = $('.eas-search-form');
            this.searchInput = $('.eas-search-input');
            this.resultsContainer = $('.eas-results-list');
            this.filtersContainer = $('.eas-filters');
            this.suggestionsContainer = $('.eas-suggestions');
            this.loadMoreBtn = $('.eas-load-more');
            this.clearFiltersBtn = $('.eas-clear-filters');
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Search form submission
            this.searchForm.on('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });

            // Search input events
            this.searchInput.on('input', (e) => {
                this.handleSearchInput(e);
            });

            this.searchInput.on('keydown', (e) => {
                this.handleKeyNavigation(e);
            });

            this.searchInput.on('focus', () => {
                this.showSuggestions();
            });

            $(document).on('click', (e) => {
                if (!$(e.target).closest('.eas-search-input-wrapper').length) {
                    this.hideSuggestions();
                }
            });

            // Filter events
            this.filtersContainer.on('change', 'input, select', (e) => {
                this.handleFilterChange(e);
            });

            // Suggestion clicks
            $(document).on('click', '.eas-suggestion-item', (e) => {
                this.selectSuggestion($(e.currentTarget));
            });

            // Load more results
            this.loadMoreBtn.on('click', () => {
                this.loadMoreResults();
            });

            // Clear filters
            this.clearFiltersBtn.on('click', () => {
                this.clearAllFilters();
            });

            // Active filter tag removal
            $(document).on('click', '.eas-filter-tag-remove', (e) => {
                this.removeActiveFilter($(e.currentTarget));
            });

            // Sort change
            $('.eas-sort-select').on('change', (e) => {
                this.handleSortChange(e);
            });

            // Range slider updates
            $('.eas-radius-slider').on('input', (e) => {
                this.updateRadiusDisplay(e);
            });

            // Location input geocoding
            $('.eas-location-input').on('blur', (e) => {
                this.geocodeLocation($(e.target));
            });
        }

        /**
         * Handle search input with debouncing
         */
        handleSearchInput(e) {
            const query = $(e.target).val();
            
            // Clear existing timeout
            if (this.suggestionTimeout) {
                clearTimeout(this.suggestionTimeout);
            }
            
            // Set new timeout for suggestions
            this.suggestionTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    this.loadSuggestions(query);
                } else {
                    this.hideSuggestions();
                }
            }, 300);
        }

        /**
         * Handle keyboard navigation in suggestions
         */
        handleKeyNavigation(e) {
            const suggestions = $('.eas-suggestion-item');
            const highlighted = $('.eas-suggestion-item.highlighted');
            
            switch(e.keyCode) {
                case 40: // Arrow Down
                    e.preventDefault();
                    if (highlighted.length === 0) {
                        suggestions.first().addClass('highlighted');
                    } else {
                        const next = highlighted.removeClass('highlighted').next();
                        if (next.length) {
                            next.addClass('highlighted');
                        } else {
                            suggestions.first().addClass('highlighted');
                        }
                    }
                    break;
                    
                case 38: // Arrow Up
                    e.preventDefault();
                    if (highlighted.length === 0) {
                        suggestions.last().addClass('highlighted');
                    } else {
                        const prev = highlighted.removeClass('highlighted').prev();
                        if (prev.length) {
                            prev.addClass('highlighted');
                        } else {
                            suggestions.last().addClass('highlighted');
                        }
                    }
                    break;
                    
                case 13: // Enter
                    if (highlighted.length) {
                        e.preventDefault();
                        this.selectSuggestion(highlighted);
                    }
                    break;
                    
                case 27: // Escape
                    this.hideSuggestions();
                    break;
            }
        }

        /**
         * Load search suggestions
         */
        loadSuggestions(query) {
            const data = {
                action: 'eas_get_suggestions',
                nonce: eas_ajax.nonce,
                query: query,
                filters: this.activeFilters
            };

            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.suggestions) {
                        this.displaySuggestions(response.data.suggestions);
                    }
                },
                error: () => {
                    console.error('Failed to load suggestions');
                }
            });
        }

        /**
         * Display search suggestions
         */
        displaySuggestions(suggestions) {
            if (!suggestions.length) {
                this.hideSuggestions();
                return;
            }

            let html = '';
            suggestions.forEach(suggestion => {
                html += `
                    <div class="eas-suggestion-item" data-value="${suggestion.value}" data-type="${suggestion.type}">
                        <span class="eas-suggestion-text">${suggestion.text}</span>
                        <span class="eas-suggestion-type">${suggestion.type}</span>
                    </div>
                `;
            });

            this.suggestionsContainer.html(html).show();
        }

        /**
         * Hide suggestions
         */
        hideSuggestions() {
            this.suggestionsContainer.hide();
            $('.eas-suggestion-item').removeClass('highlighted');
        }

        /**
         * Show suggestions
         */
        showSuggestions() {
            if (this.suggestionsContainer.children().length > 0) {
                this.suggestionsContainer.show();
            }
        }

        /**
         * Select a suggestion
         */
        selectSuggestion($suggestion) {
            const value = $suggestion.data('value');
            const type = $suggestion.data('type');
            
            this.searchInput.val(value);
            this.hideSuggestions();
            
            // Track suggestion click
            this.trackSuggestionClick(value, type);
            
            // Perform search
            this.performSearch();
        }

        /**
         * Perform search with current parameters
         */
        performSearch(page = 1) {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.currentPage = page;
            
            // Show loading state
            if (page === 1) {
                this.resultsContainer.addClass('eas-loading');
            } else {
                this.loadMoreBtn.prop('disabled', true).append('<span class="eas-spinner"></span>');
            }

            // Cancel previous request
            if (this.currentXhr) {
                this.currentXhr.abort();
            }

            const searchData = this.gatherSearchData();
            searchData.page = page;

            this.currentXhr = $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: searchData,
                success: (response) => {
                    this.handleSearchResponse(response, page);
                },
                error: (xhr) => {
                    if (xhr.statusText !== 'abort') {
                        this.handleSearchError();
                    }
                },
                complete: () => {
                    this.isLoading = false;
                    this.resultsContainer.removeClass('eas-loading');
                    this.loadMoreBtn.prop('disabled', false).find('.eas-spinner').remove();
                }
            });
        }

        /**
         * Gather search data from form and filters
         */
        gatherSearchData() {
            return {
                action: 'eas_perform_search',
                nonce: eas_ajax.nonce,
                query: this.searchInput.val(),
                filters: this.activeFilters,
                sort: $('.eas-sort-select').val() || 'relevance',
                per_page: parseInt($('.eas-results-container').data('per-page')) || 10
            };
        }

        /**
         * Handle search response
         */
        handleSearchResponse(response, page) {
            if (!response.success) {
                this.handleSearchError(response.data);
                return;
            }

            const data = response.data;
            
            if (page === 1) {
                // Replace results for new search
                this.displayResults(data.results);
                this.updateResultsInfo(data.total, data.query_time);
                this.updateFacets(data.facets);
                this.updateActiveFilters();
            } else {
                // Append results for load more
                this.appendResults(data.results);
            }

            // Update pagination
            this.totalPages = data.total_pages;
            this.updateLoadMoreButton();

            // Track search
            this.trackSearch(data.query, data.total);
            
            // Update URL
            this.updateURL();
        }

        /**
         * Display search results
         */
        displayResults(results) {
            if (!results.length) {
                this.displayNoResults();
                return;
            }

            let html = '';
            results.forEach(result => {
                html += this.buildResultHTML(result);
            });

            this.resultsContainer.html(html);
        }

        /**
         * Append more results
         */
        appendResults(results) {
            let html = '';
            results.forEach(result => {
                html += this.buildResultHTML(result);
            });

            this.resultsContainer.append(html);
        }

        /**
         * Build HTML for a single result
         */
        buildResultHTML(result) {
            let metaHTML = '';
            if (result.meta && result.meta.length) {
                metaHTML = result.meta.map(meta => 
                    `<div class="eas-result-meta-item">
                        <i class="${meta.icon || 'fas fa-info-circle'}"></i>
                        <span>${meta.label}: ${meta.value}</span>
                    </div>`
                ).join('');
            }

            let tagsHTML = '';
            if (result.tags && result.tags.length) {
                tagsHTML = result.tags.map(tag => 
                    `<a href="${tag.url}" class="eas-result-tag">${tag.name}</a>`
                ).join('');
            }

            let distanceHTML = '';
            if (result.distance) {
                distanceHTML = `<span class="eas-distance">${result.distance}</span>`;
            }

            return `
                <li class="eas-result-item" data-id="${result.id}" data-type="${result.type}">
                    <h3 class="eas-result-title">
                        <a href="${result.url}" data-result-id="${result.id}">${result.title}</a>
                        ${distanceHTML}
                    </h3>
                    <div class="eas-result-excerpt">${result.excerpt}</div>
                    ${metaHTML ? `<div class="eas-result-meta">${metaHTML}</div>` : ''}
                    ${tagsHTML ? `<div class="eas-result-tags">${tagsHTML}</div>` : ''}
                </li>
            `;
        }

        /**
         * Display no results message
         */
        displayNoResults() {
            const html = `
                <div class="eas-no-results">
                    <div class="eas-no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="eas-no-results-title">No results found</h3>
                    <p class="eas-no-results-message">
                        Try adjusting your search terms or filters to find what you're looking for.
                    </p>
                </div>
            `;
            this.resultsContainer.html(html);
        }

        /**
         * Handle search error
         */
        handleSearchError(error) {
            console.error('Search error:', error);
            const html = `
                <div class="eas-no-results">
                    <div class="eas-no-results-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="eas-no-results-title">Search Error</h3>
                    <p class="eas-no-results-message">
                        An error occurred while searching. Please try again.
                    </p>
                </div>
            `;
            this.resultsContainer.html(html);
        }

        /**
         * Initialize filters
         */
        initializeFilters() {
            // Initialize range sliders
            $('.eas-radius-slider').each((index, element) => {
                this.updateRadiusDisplay({ target: element });
            });

            // Load initial facet counts
            this.loadFacetCounts();
        }

        /**
         * Handle filter changes
         */
        handleFilterChange(e) {
            const $input = $(e.target);
            const filterType = $input.data('filter-type');
            const filterKey = $input.data('filter-key');
            const filterValue = $input.val();

            if (!this.activeFilters[filterType]) {
                this.activeFilters[filterType] = {};
            }

            if ($input.is(':checkbox')) {
                if (!this.activeFilters[filterType][filterKey]) {
                    this.activeFilters[filterType][filterKey] = [];
                }
                
                if ($input.is(':checked')) {
                    if (this.activeFilters[filterType][filterKey].indexOf(filterValue) === -1) {
                        this.activeFilters[filterType][filterKey].push(filterValue);
                    }
                } else {
                    const index = this.activeFilters[filterType][filterKey].indexOf(filterValue);
                    if (index > -1) {
                        this.activeFilters[filterType][filterKey].splice(index, 1);
                    }
                }
            } else if ($input.is(':radio')) {
                this.activeFilters[filterType][filterKey] = filterValue;
            } else if ($input.hasClass('eas-range-input')) {
                const rangeType = $input.data('range-type');
                if (!this.activeFilters[filterType][filterKey]) {
                    this.activeFilters[filterType][filterKey] = {};
                }
                this.activeFilters[filterType][filterKey][rangeType] = filterValue;
            } else {
                this.activeFilters[filterType][filterKey] = filterValue;
            }

            // Clean empty filters
            this.cleanEmptyFilters();

            // Perform new search
            this.performSearch(1);
        }

        /**
         * Clean empty filter values
         */
        cleanEmptyFilters() {
            Object.keys(this.activeFilters).forEach(filterType => {
                Object.keys(this.activeFilters[filterType]).forEach(filterKey => {
                    const value = this.activeFilters[filterType][filterKey];
                    if (Array.isArray(value) && value.length === 0) {
                        delete this.activeFilters[filterType][filterKey];
                    } else if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) {
                        delete this.activeFilters[filterType][filterKey];
                    }
                });
                
                if (Object.keys(this.activeFilters[filterType]).length === 0) {
                    delete this.activeFilters[filterType];
                }
            });
        }

        /**
         * Update facet counts
         */
        updateFacets(facets) {
            if (!facets) return;

            Object.keys(facets).forEach(facetKey => {
                const facetData = facets[facetKey];
                const $facetGroup = $(`.eas-filter-group[data-facet="${facetKey}"]`);
                
                if ($facetGroup.length) {
                    Object.keys(facetData).forEach(valueKey => {
                        const count = facetData[valueKey];
                        const $countElement = $facetGroup.find(`[data-value="${valueKey}"] .eas-filter-count`);
                        if ($countElement.length) {
                            $countElement.text(count);
                        }
                    });
                }
            });
        }

        /**
         * Load facet counts
         */
        loadFacetCounts() {
            const data = {
                action: 'eas_get_facet_counts',
                nonce: eas_ajax.nonce,
                filters: this.activeFilters
            };

            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.facets) {
                        this.updateFacets(response.data.facets);
                    }
                }
            });
        }

        /**
         * Update active filters display
         */
        updateActiveFilters() {
            const $container = $('.eas-active-filter-tags');
            $container.empty();

            let hasFilters = false;

            Object.keys(this.activeFilters).forEach(filterType => {
                Object.keys(this.activeFilters[filterType]).forEach(filterKey => {
                    const filterValue = this.activeFilters[filterType][filterKey];
                    
                    if (Array.isArray(filterValue)) {
                        filterValue.forEach(value => {
                            if (value) {
                                this.addFilterTag($container, filterType, filterKey, value);
                                hasFilters = true;
                            }
                        });
                    } else if (typeof filterValue === 'object') {
                        const parts = [];
                        if (filterValue.min) parts.push(`Min: ${filterValue.min}`);
                        if (filterValue.max) parts.push(`Max: ${filterValue.max}`);
                        if (parts.length) {
                            this.addFilterTag($container, filterType, filterKey, parts.join(', '));
                            hasFilters = true;
                        }
                    } else if (filterValue) {
                        this.addFilterTag($container, filterType, filterKey, filterValue);
                        hasFilters = true;
                    }
                });
            });

            $('.eas-active-filters').toggle(hasFilters);
        }

        /**
         * Add filter tag to active filters
         */
        addFilterTag($container, filterType, filterKey, value) {
            const label = this.getFilterLabel(filterType, filterKey, value);
            const $tag = $(`
                <span class="eas-filter-tag" data-filter-type="${filterType}" data-filter-key="${filterKey}" data-filter-value="${value}">
                    ${label}
                    <button type="button" class="eas-filter-tag-remove" aria-label="Remove filter">×</button>
                </span>
            `);
            $container.append($tag);
        }

        /**
         * Get human-readable filter label
         */
        getFilterLabel(filterType, filterKey, value) {
            // Try to get label from filter group
            const $filterGroup = $(`.eas-filter-group[data-facet="${filterKey}"]`);
            if ($filterGroup.length) {
                const $option = $filterGroup.find(`[data-value="${value}"]`);
                if ($option.length) {
                    return $option.text().replace(/\(\d+\)$/, '').trim();
                }
            }

            // Fallback to value
            return `${filterKey}: ${value}`;
        }

        /**
         * Remove active filter
         */
        removeActiveFilter($button) {
            const $tag = $button.closest('.eas-filter-tag');
            const filterType = $tag.data('filter-type');
            const filterKey = $tag.data('filter-key');
            const filterValue = $tag.data('filter-value');

            // Update activeFilters
            if (this.activeFilters[filterType] && this.activeFilters[filterType][filterKey]) {
                const currentValue = this.activeFilters[filterType][filterKey];
                
                if (Array.isArray(currentValue)) {
                    const index = currentValue.indexOf(filterValue);
                    if (index > -1) {
                        currentValue.splice(index, 1);
                    }
                } else {
                    delete this.activeFilters[filterType][filterKey];
                }
            }

            // Update form controls
            this.updateFormControls();

            // Clean and search
            this.cleanEmptyFilters();
            this.performSearch(1);
        }

        /**
         * Clear all filters
         */
        clearAllFilters() {
            this.activeFilters = {};
            this.updateFormControls();
            this.performSearch(1);
        }

        /**
         * Update form controls to match active filters
         */
        updateFormControls() {
            // Reset all inputs
            $('.eas-filters input[type="checkbox"]').prop('checked', false);
            $('.eas-filters input[type="radio"]').prop('checked', false);
            $('.eas-filters select').val('');
            $('.eas-filters input[type="text"], .eas-filters input[type="number"]').val('');

            // Set active values
            Object.keys(this.activeFilters).forEach(filterType => {
                Object.keys(this.activeFilters[filterType]).forEach(filterKey => {
                    const filterValue = this.activeFilters[filterType][filterKey];
                    const $inputs = $(`.eas-filters [data-filter-key="${filterKey}"]`);

                    if (Array.isArray(filterValue)) {
                        filterValue.forEach(value => {
                            $inputs.filter(`[value="${value}"]`).prop('checked', true);
                        });
                    } else if (typeof filterValue === 'object') {
                        if (filterValue.min) {
                            $inputs.filter('[data-range-type="min"]').val(filterValue.min);
                        }
                        if (filterValue.max) {
                            $inputs.filter('[data-range-type="max"]').val(filterValue.max);
                        }
                    } else {
                        $inputs.val(filterValue);
                        $inputs.filter(`[value="${filterValue}"]`).prop('checked', true);
                    }
                });
            });
        }

        /**
         * Update results info
         */
        updateResultsInfo(total, queryTime) {
            $('.eas-results-count').text(total.toLocaleString());
            if (queryTime) {
                $('.eas-query-time').text(queryTime);
            }
        }

        /**
         * Update load more button
         */
        updateLoadMoreButton() {
            if (this.currentPage >= this.totalPages) {
                this.loadMoreBtn.hide();
            } else {
                this.loadMoreBtn.show();
            }
        }

        /**
         * Load more results
         */
        loadMoreResults() {
            if (this.currentPage < this.totalPages && !this.isLoading) {
                this.performSearch(this.currentPage + 1);
            }
        }

        /**
         * Handle sort change
         */
        handleSortChange(e) {
            this.performSearch(1);
        }

        /**
         * Update radius display
         */
        updateRadiusDisplay(e) {
            const $slider = $(e.target);
            const value = $slider.val();
            const $display = $slider.siblings('.eas-radius-display');
            $display.text(`${value} km`);
        }

        /**
         * Geocode location input
         */
        geocodeLocation($input) {
            const address = $input.val().trim();
            if (!address) return;

            // Simple geocoding (in real implementation, use proper geocoding service)
            const data = {
                action: 'eas_geocode_address',
                nonce: eas_ajax.nonce,
                address: address
            };

            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data) {
                        $input.data('lat', response.data.lat);
                        $input.data('lng', response.data.lng);
                    }
                }
            });
        }

        /**
         * Track search
         */
        trackSearch(query, results) {
            const data = {
                action: 'eas_track_search',
                nonce: eas_ajax.nonce,
                query: query,
                results: results,
                filters: this.activeFilters
            };

            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: data
            });

            // Add to local history
            this.addToSearchHistory(query);
        }

        /**
         * Track suggestion click
         */
        trackSuggestionClick(suggestion, type) {
            const data = {
                action: 'eas_track_suggestion_click',
                nonce: eas_ajax.nonce,
                suggestion: suggestion,
                type: type
            };

            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: data
            });
        }

        /**
         * Add to search history
         */
        addToSearchHistory(query) {
            if (!query || this.searchHistory.includes(query)) return;
            
            this.searchHistory.unshift(query);
            if (this.searchHistory.length > 10) {
                this.searchHistory = this.searchHistory.slice(0, 10);
            }
            
            localStorage.setItem('eas_search_history', JSON.stringify(this.searchHistory));
        }

        /**
         * Load search history
         */
        loadSearchHistory() {
            try {
                const stored = localStorage.getItem('eas_search_history');
                if (stored) {
                    this.searchHistory = JSON.parse(stored);
                }
            } catch (e) {
                this.searchHistory = [];
            }
        }

        /**
         * Initialize from URL parameters
         */
        initializeFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const query = urlParams.get('s');
            
            if (query) {
                this.searchInput.val(query);
                
                // Parse filters from URL
                urlParams.forEach((value, key) => {
                    if (key.startsWith('filter_')) {
                        const filterKey = key.replace('filter_', '');
                        // Add to active filters
                        // Implementation depends on filter structure
                    }
                });
                
                this.performSearch(1);
            }
        }

        /**
         * Update URL with current search state
         */
        updateURL() {
            const url = new URL(window.location);
            const query = this.searchInput.val();
            
            if (query) {
                url.searchParams.set('s', query);
            } else {
                url.searchParams.delete('s');
            }
            
            // Add filters to URL
            Object.keys(this.activeFilters).forEach(filterType => {
                Object.keys(this.activeFilters[filterType]).forEach(filterKey => {
                    const value = this.activeFilters[filterType][filterKey];
                    const paramName = `filter_${filterKey}`;
                    
                    if (Array.isArray(value)) {
                        url.searchParams.set(paramName, value.join(','));
                    } else if (typeof value === 'object') {
                        url.searchParams.set(paramName, JSON.stringify(value));
                    } else {
                        url.searchParams.set(paramName, value);
                    }
                });
            });
            
            // Update URL without page reload
            window.history.replaceState({}, '', url);
        }
    }

    /**
     * Result click tracking
     */
    $(document).on('click', '.eas-result-title a', function(e) {
        const resultId = $(this).data('result-id');
        const query = $('.eas-search-input').val();
        
        if (resultId && query) {
            $.ajax({
                url: eas_ajax.url,
                type: 'POST',
                data: {
                    action: 'eas_track_click',
                    nonce: eas_ajax.nonce,
                    result_id: resultId,
                    query: query
                }
            });
        }
    });

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize if search elements exist
        if ($('.eas-search-form').length) {
            window.easSearch = new EnvironmentalAdvancedSearch();
        }
    });

})(jQuery);
