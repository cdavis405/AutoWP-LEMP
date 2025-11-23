/**
 * AutoWP Pinned Navigation Admin JavaScript
 *
 * @package AutoWP
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initSortable();
        initSearch();
        initSave();
        initRemove();
    });

    /**
     * Initialize sortable list
     */
    function initSortable() {
        $('#pinned-items-sortable').sortable({
            handle: '.handle',
            placeholder: 'ui-sortable-placeholder',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
            }
        });
    }

    /**
     * Initialize search functionality
     */
    function initSearch() {
        var searchInput = $('#pinned-nav-search-input');
        var searchBtn = $('#pinned-nav-search-btn');
        var resultsContainer = $('#pinned-nav-search-results');
        var searchTimeout;

        // Search on button click
        searchBtn.on('click', function() {
            performSearch();
        });

        // Search on enter key
        searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                performSearch();
            }
        });

        // Search as you type (debounced)
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });

        function performSearch() {
            var query = searchInput.val().trim();
            
            if (query.length < 2) {
                resultsContainer.removeClass('has-results').empty();
                return;
            }

            resultsContainer.html('<div style="padding: 15px; text-align: center;">Searching...</div>');
            resultsContainer.addClass('has-results');

            $.ajax({
                url: autowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'autowp_search_posts',
                    search: query,
                    nonce: autowpAdmin.searchNonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        renderSearchResults(response.data);
                    } else {
                        resultsContainer.html('<div style="padding: 15px; text-align: center; color: #666;">No results found.</div>');
                    }
                },
                error: function() {
                    resultsContainer.html('<div style="padding: 15px; text-align: center; color: #b32d2e;">Error performing search.</div>');
                }
            });
        }

        function renderSearchResults(results) {
            var html = '';
            
            results.forEach(function(item) {
                var typeLabel = item.type === 'guide' ? 'Guide' : 
                               item.type === 'page' ? 'Page' : 'Post';
                
                html += '<div class="search-result-item" data-id="' + item.id + '">' +
                    '<div class="search-result-info">' +
                        '<div class="search-result-title">' + item.title + '</div>' +
                        '<div class="search-result-meta">' +
                            '<span class="search-result-type">' + typeLabel + '</span>' +
                            '<a href="' + item.url + '" target="_blank">View</a>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="button add-pinned-item">Add to Pinned</button>' +
                '</div>';
            });
            
            resultsContainer.html(html);
            
            // Add click handler for add buttons
            resultsContainer.find('.add-pinned-item').on('click', function() {
                var item = $(this).closest('.search-result-item');
                var id = item.data('id');
                var title = item.find('.search-result-title').text();
                
                addPinnedItem(id, title);
                $(this).prop('disabled', true).text('Added');
            });
        }
    }

    /**
     * Add item to pinned list
     */
    function addPinnedItem(id, title) {
        var list = $('#pinned-items-sortable');
        var noItems = list.find('.no-items');
        
        // Remove "no items" message if present
        if (noItems.length) {
            noItems.remove();
        }
        
        // Check if item already exists
        if (list.find('li[data-id="' + id + '"]').length > 0) {
            alert('This item is already in the pinned list.');
            return;
        }
        
        var html = '<li data-id="' + id + '">' +
            '<span class="dashicons dashicons-menu handle"></span>' +
            '<span class="item-title">' + title + '</span>' +
            '<input type="text" class="custom-title" placeholder="Custom title (optional)" value="" />' +
            '<button type="button" class="button remove-item">Remove</button>' +
        '</li>';
        
        list.append(html);
        
        // Refresh sortable
        list.sortable('refresh');
    }

    /**
     * Initialize remove functionality
     */
    function initRemove() {
        $(document).on('click', '.remove-item', function() {
            var item = $(this).closest('li');
            var list = $('#pinned-items-sortable');
            
            if (confirm('Remove this item from pinned navigation?')) {
                item.remove();
                
                // Add "no items" message if list is empty
                if (list.find('li').length === 0) {
                    list.html('<li class="no-items">No pinned items yet. Search and add items above.</li>');
                }
            }
        });
    }

    /**
     * Initialize save functionality
     */
    function initSave() {
        $('#save-pinned-items').on('click', function() {
            var button = $(this);
            var spinner = button.next('.spinner');
            var message = button.siblings('.save-message');
            var list = $('#pinned-items-sortable');
            
            // Collect items
            var items = [];
            list.find('li').not('.no-items').each(function() {
                var li = $(this);
                items.push({
                    id: li.data('id'),
                    custom_title: li.find('.custom-title').val()
                });
            });
            
            // Show loading
            button.prop('disabled', true);
            spinner.addClass('is-active');
            message.text('').removeClass('error');
            
            // Save via AJAX
            $.ajax({
                url: autowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'autowp_save_pinned_items',
                    items: items,
                    nonce: autowpAdmin.saveNonce
                },
                success: function(response) {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                    
                    if (response.success) {
                        message.text('Saved successfully!');
                        setTimeout(function() {
                            message.text('');
                        }, 3000);
                    } else {
                        message.addClass('error').text('Error: ' + response.data);
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                    message.addClass('error').text('Error saving changes.');
                }
            });
        });
    }

})(jQuery);
