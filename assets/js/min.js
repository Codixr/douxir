jQuery(document).ready(function ($) {
    // Smooth scrolling for anchors
    $('a[name*="scroll_to_"]').on('click', function (e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: $(this.hash).offset().top - 120,
        }, 500);

        // Update active state in TOC
        $('#toc-box li').removeClass('active');
        $(this).parent('li').addClass('active');
    });

    // Add class to headings and dynamically generate TOC
    $(".doc-section .doc-content h2, .doc-section .doc-content h1").addClass("table_content");

    if ($('.doc-section .doc-content .table_content').length > 0) {
        let count = 1;
        $('.doc-section .doc-content .table_content').each(function () {
            const $item = $(this);
            $item.attr('id', 'scroll_to_' + count);

            // Create TOC entry. Use text() so heading characters are not treated as HTML.
            const li = $('<li/>');
            const headingText = $item.text();
            const $link = $('<a/>', {
                href: `#scroll_to_${count}`,
                name: `#scroll_to_${count}`,
                title: headingText,
            });
            if (headingText.trim().endsWith("pro")) {
                $link.text(headingText.replace(/pro$/, '').trim());
                $link.append(' ');
                $link.append($('<span/>', { 'class': 'pro-tag', text: 'pro' }));
            } else {
                $link.text(headingText);
            }
            $link.appendTo(li);

            $('#toc-box').append(li);
            count++;
        });
    }

    // Ensure correct dropdown state on page load
    function checkAndKeepOpen() {
        const currentSlug = window.location.pathname; // Get the current path
        $('.doc-child-title li a, .doc-title > li > a').each(function () {
            const $link = $(this);
            const linkSlug = new URL($link.attr('href'), window.location.origin).pathname;

            // Match current slug with href
            if (linkSlug === currentSlug) {
                const $parentLi = $link.closest('li');
                $parentLi.addClass('active');

                // Open nested lists
                const $childList = $link.closest('.doc-child-title');
                if ($childList.length) {
                    const totalHeight = $childList.prop('scrollHeight');
                    $childList.addClass('open').css({
                        height: totalHeight,
                        margin: '20px 0',
                    });
                    $childList.prev('i').addClass('open');
                }
            }
        });
    }

    // Add 'has-child' class to parent anchors with nested lists
    function addHasChildClass() {
        $('.doc-title > li').each(function () {
            const $parentLi = $(this);

            if ($parentLi.find('.doc-child-title').length > 0) {
                const $parentAnchor = $parentLi.children('a');
                $parentAnchor.addClass('has-child').attr('href', 'javascript:void(0)');
            }
        });
    }

    // Toggle dropdown for nested lists
    function handleDropdownToggle() {
        $('.doc-title li i, .doc-title > li > a.has-child').on('click', function (e) {
            e.preventDefault();

            const $parentLi = $(this).closest('li');
            const $childList = $parentLi.find('> .doc-child-title');

            if ($childList.hasClass('open')) {
                $childList.animate({ height: 0 }, 300, function () {
                    $childList.removeClass('open').css({ height: '', margin: '' });
                });
            } else {
                const totalHeight = $childList.prop('scrollHeight');
                $childList.addClass('open').css({ height: 0, margin: 0 });
                $childList.animate({ height: totalHeight, margin: '20px 0' }, 300);
            }

            // Toggle icon rotation
            $parentLi.find('> i').toggleClass('open');
        });
    }

    // Initialize functionality
    addHasChildClass();
    handleDropdownToggle();
    checkAndKeepOpen();

    // ---------------- New: Mobile Dropdown Menu ----------------
    function initMobileDropdown() {
        if (window.innerWidth <= 768) {

            const $dropdownContainer = $('.doc-mobile-dropdown');

            if (!$dropdownContainer.length) return;

            // Remove old dropdown
            $dropdownContainer.empty();

            // Wrapper
            const $wrapper = $('<div class="doc-title-mobile"></div>');

            // Select
            const $select = $('<select id="title-nav" class="doc-mobile-select"></select>');
            $select.append('<option value="">Select a Document...</option>');

            const currentSlug = window.location.pathname;

            // Loop through each category
            $('.doc-category-title').each(function () {

                const categoryName = $(this).text().trim();
                const $optgroup = $('<optgroup>', {
                    label: categoryName
                });

                // Get the UL immediately after this category heading
                $(this).next('.doc-title').find('a').each(function () {

                    const href = $(this).attr('href');
                    const title = $(this).text().trim();

                    if (href && href !== 'javascript:void(0)') {

                        const linkSlug = new URL(href, window.location.origin).pathname;

                        $('<option>', {
                            value: href,
                            text: title,
                            selected: (linkSlug === currentSlug)
                        }).appendTo($optgroup);
                    }
                });

                if ($optgroup.children().length) {
                    $select.append($optgroup);
                }
            });

            $wrapper.append($select);
            $dropdownContainer.html($wrapper);

            // Navigate on change
            $select.off('change').on('change', function () {
                const url = $(this).val();
                if (url) {
                    window.location.href = url;
                }
            });
        }
    }

// Init on load
    initMobileDropdown();

// Re-init on resize
    $(window).on('resize', function () {
        if (window.innerWidth <= 1024 && !$('.doc-mobile-select').length) {
            initMobileDropdown();
        } else if (window.innerWidth > 1024) {
            $('.doc-title').show();
            $('.doc-mobile-dropdown').empty();
        }
    });
    // ---------------- Live Search ----------------
    let searchTimeout;
    $(document).on('input', '#cd-doc-search, .douxir-search-input', function() {
        const query = $(this).val();
        const $resultsContainer = $(this).closest('.doc-search-wrapper').find('.cd-search-results');

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $resultsContainer.empty().hide();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: douxir_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'douxir_search_docs',
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        const $list = $('<ul></ul>');
                        response.data.forEach(function(item) {
                            $list.append(
                                $('<li></li>').append(
                                    $('<a></a>').attr('href', item.link).text(item.title)
                                )
                            );
                        });
                        $resultsContainer.empty().append($list).show();
                    } else {
                        $resultsContainer.empty().append($('<p class="no-results"></p>').text(douxir_data.no_results)).show();
                    }
                }
            });
        }, 300);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.doc-search-wrapper').length) {
            $('.cd-search-results').hide();
        }
    });

    $(document).on('click', '[data-douxir-print]', function() {
        window.print();
    });

    $(document).on('click', '.douxir-feedback-btn', function() {
        const $btn = $(this);
        const $box = $btn.closest('[data-douxir-feedback]');
        const postId = $box.data('douxir-feedback');
        const vote = $btn.data('vote');
        if (!postId || $box.data('voted')) {
            return;
        }
        $.ajax({
            url: douxir_data.ajax_url,
            type: 'POST',
            data: {
                action: 'douxir_feedback',
                nonce: douxir_data.feedback_nonce,
                post_id: postId,
                vote: vote
            },
            success: function(response) {
                $box.data('voted', true);
                $box.find('.douxir-feedback-btn').prop('disabled', true);
                $btn.addClass('is-active');
                if (response.success && response.data && response.data.label) {
                    $box.find('.douxir-feedback-counts').text(response.data.label);
                }
                $box.find('.douxir-feedback-thanks').removeAttr('hidden');
            },
            error: function(xhr) {
                const message = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message)
                    ? xhr.responseJSON.data.message
                    : (douxir_data.already_voted || '');
                if (message) {
                    $box.find('.douxir-feedback-counts').text(message);
                }
                $box.data('voted', true);
                $box.find('.douxir-feedback-btn').prop('disabled', true);
            }
        });
    });

    // Mouse wheel smooth scroll for .doc-title-list
    $(document).on('wheel', '.doc-title-list', function(e) {
        var el = this;
        var scrollTop = el.scrollTop;
        var scrollHeight = el.scrollHeight;
        var height = el.offsetHeight;
        var delta = e.originalEvent.deltaY;

        var atTop    = (delta < 0 && scrollTop === 0);
        var atBottom = (delta > 0 && scrollTop + height >= scrollHeight);

        if (!atTop && !atBottom) {
            e.preventDefault();
            e.stopPropagation();
            el.scrollBy({ top: delta, behavior: 'smooth' });
        }
    });

});