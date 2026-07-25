(function ($) {
    "use strict";

})(jQuery);
jQuery(document).ready(function ($) {
    var allusfi_crnt_tab = localStorage.getItem("allusfi_current_tab");
    allusfi_crnt_tab = (allusfi_crnt_tab === null) ? "general-settings" : allusfi_crnt_tab;
    var allusfi_modal = $("#allusfi_model_options");
    // When the user clicks on the button, open the modal
    $('body').on('click', "#allusfi_pop_up_btn", function () { allusfi_modal.attr("style", "display:flex;"); });
    $('body').on('click', ".allusfi_model_close", function () { allusfi_modal.attr("style", "display:none;"); });
    // When the user clicks anywhere outside of the modal, close it
    $('body').on('click', function (e) {
        if (e.target.className == "allusfi_modal")
            allusfi_modal.attr("style", "display:none;");
    });
    $('body').on("click", ".tablinks", function (e) {
        $(".tablinks").removeClass("set-active");
        $(this).addClass("set-active");
        $(".allusfi-tabcontent").hide();
        $("#" + $(this).attr("data-id")).show();
        var crnt_tab_attr = $(this).attr("data-id");
        var splited_ary = crnt_tab_attr.split('usfi-');
        localStorage.setItem("allusfi_current_tab", splited_ary[1]);
    });
    $('body').on('click', ".remov_date", function () { $(this).parents('tr').remove(); });
    $('body').on('click', ".remov_meta", function () { $(this).parents('tr').remove(); });
    $('body').on('click', '.remov_rel_date', function () { $(this).parents('tr').remove(); });
    $('body').on('click', '#allusfi_add_rel_date', function () {
        const REL_DATE_COPY = $("#allusfi_rel_date_copy_content").html().trim();
        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
        $("#rel_date_append_content").append(REL_DATE_COPY);
    });
    $('body').on('click', '#allusfi_add_multi_date', function () {
        const DATE_COPY_CONTENT = $("#allusfi_dt_copy_content").html().trim();
        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
        $("#dt_append_content").append(DATE_COPY_CONTENT);
    });
    $('body').on('click', '#allusfi_add_meta_query', function () {
        const META_COPY_CONTENT = $("#allusfi_meta_copy_content").html().trim();
        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
        $("#advnce_append_content").append(META_COPY_CONTENT);
    });
    // Extra meta key repeater — add a new row.
    $('body').on('click', '#allusfi_add_extra_meta_btn', function () {
        const EXTRA_META_ROW = document.querySelector('#allusfi_extra_meta_row_tpl');
        if (!EXTRA_META_ROW) {
            return;
        }
        var clone = document.importNode(EXTRA_META_ROW.content, true);
        document.querySelector('#allusfi_extra_meta_rows').appendChild(clone);
    });
    // Extra meta key repeater — remove a row.
    $('body').on('click', '.allusfi_remov_extra_meta', function () {
        $(this).closest('tr').remove();
    });
    // WooCommerce toggle: enable/disable the order count fields.
    $('body').on('change', '#allusfi_wc_toggle', function () {
        var $fields = $('.allusfi-wc-fields');
        if ($(this).is(':checked')) {
            $fields.css({ 'opacity': '1', 'pointer-events': 'auto' });
        } else {
            $fields.css({ 'opacity': '0.5', 'pointer-events': 'none' });
        }
    });
    // Export: meta-include toggle — show/hide meta columns sub-section.
    $('body').on('change', '#allusfi_include_meta_toggle', function () {
        if ($(this).is(':checked')) {
            $('#allusfi_meta_export_wrap').slideDown(150);
        } else {
            $('#allusfi_meta_export_wrap').slideUp(150);
        }
    });

    /* ============================================================
       FILTER SUBMIT — shared logic used by both the "Filter Users"
       button click AND the Enter-key intercept inside the modal.

       1. POST the modal form to allusfi_save_filter_transient
          (stores everything in a per-user WP transient).
       2. Redirect to users.php?allu_filter_id=allusifi_current_state
          + nonce + optional &s= (WordPress search).

       The result is a short, clean URL regardless of how many filters
       are active.
       ============================================================ */

    /**
     * Trigger the transient-save AJAX call then redirect to the short URL.
     * Shared by button click and Enter-key intercept.
     *
     * @param {jQuery} $triggerBtn  The element to disable/re-enable on error.
     * @param {string} searchTerm   Optional WP search string to append.
     */
    function allusfi_save_and_redirect($triggerBtn, searchTerm) {
        if ($triggerBtn) {
            $triggerBtn.prop('disabled', true);
        }

        // Collect every input inside the modal (filter params + export tab).
        var formData = $('#allusfi_model_options :input').serialize();

        $.ajax({
            type: 'POST',
            url: allusfi_obj.ajax_url,
            data: formData + '&action=allusfi_save_filter_transient',
            success: function (res) {
                if (!res.success) {
                    if ($triggerBtn) { $triggerBtn.prop('disabled', false); }
                    // phpcs:ignore WordPressVIPMinimum.JS.AlertDebug.Found
                    alert(res.data && res.data.msg ? res.data.msg : 'Error saving filter state. Please try again.');
                    return;
                }
                // Build the short redirect URL.
                var nonce = allusfi_obj.nonce || $('#allusfi_secure').val() || '';
                var search = searchTerm !== undefined ? searchTerm : $.trim($('#user-search-input').val());
                // phpcs:disable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location
                var url = allusfi_obj.users_page_url +
                    '?allu_filter_id=allusifi_current_state' +
                    '&allusfi_secure=' + encodeURIComponent(nonce);
                if (search) {
                    url += '&s=' + encodeURIComponent(search);
                }
                window.location.href = url;
                // phpcs:enable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location
            },
            error: function () {
                if ($triggerBtn) { $triggerBtn.prop('disabled', false); }
                // phpcs:ignore WordPressVIPMinimum.JS.AlertDebug.Found
                alert('Error saving filter state. Please try again.');
            }
        });
    }

    /* ---- "Filter Users" button click ---- */
    $('body').on('click', 'button[name="fltr-sbmt"]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        allusfi_save_and_redirect($(this));
    });

    /* ---- Enter key inside the modal ---- */
    // Pressing Enter while focused on any modal input would normally bubble up
    // and submit the WP page form (sending all modal params to the URL).
    // We intercept it and run the same save-then-redirect flow instead.
    $('body').on('keydown', '#allusfi_model_options :input', function (e) {
        // Only intercept Enter (keyCode 13).
        if (e.which !== 13 && e.keyCode !== 13) {
            return;
        }
        // Allow Enter inside the filter-name text box (handled separately below).
        if ($(this).attr('id') === 'allusfi_sf_name_input') {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        allusfi_save_and_redirect($('button[name="fltr-sbmt"]'));
    });

    /* ---- Enter in the saved-filter name input ---- */
    // Trigger "Confirm Save" so the user doesn't have to click the button.
    $('body').on('keydown', '#allusfi_sf_name_input', function (e) {
        if (e.which === 13 || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            $('#allusfi_sf_confirm_save').trigger('click');
        }
    });

    /* ---- WordPress Search form submit intercept ---- */
    // The WP users page search form wraps everything — including our modal.
    // When the user submits a search, the form's GET submission would include
    // all modal :input fields in the URL.
    //
    // The form has NO id on the users.php page (it's just <form method="get">).
    // We target it via the search input's closest parent form to be reliable.
    //
    // We intercept the submit and build a clean URL:
    //   a) If there is already an active filter (allu_filter_id in JS context),
    //      just navigate to users.php?allu_filter_id=<id>&allusfi_secure=<nonce>&s=<term>.
    //      (No new AJAX needed — the transient is already set.)
    //   b) If NO filter is active, let the normal WP search proceed but we disable all
    //      modal inputs first so they never appear in the URL.
    $('#user-search-input').closest('form').on('submit', function (e) {
        var searchTerm = $.trim($('#user-search-input').val());
        var nonce = allusfi_obj.nonce || $('#allusfi_secure').val() || '';
        var filterId = allusfi_obj.allu_filter_id || '';

        if (filterId) {
            // Active filter exists — navigate to the short URL + new search term.
            e.preventDefault();
            e.stopPropagation();
            // phpcs:disable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location
            var url = allusfi_obj.users_page_url +
                '?allu_filter_id=' + encodeURIComponent(filterId) +
                '&allusfi_secure=' + encodeURIComponent(nonce);
            if (searchTerm) {
                url += '&s=' + encodeURIComponent(searchTerm);
            }
            window.location.href = url;
            // phpcs:enable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location
        } else {
            // No active filter — let WP search proceed normally BUT disable all
            // modal inputs first so they are excluded from the submitted URL.
            $('#allusfi_model_options :input').prop('disabled', true);
            // (The browser submits the form; the page reload re-enables everything.)
        }
    });



    let csvRows = [];
    let totalUsers = 0;
    let processed = 0;
    let csvSeparator = ',';

    /* ============================================================
       EXPORT — send only the export-tab settings + allu_filter_id.
       Filter params are fetched server-side from the transient.
       ============================================================ */
    $('body').on('click', '#allusfi_EXP-csv-BTN', function (e) {
        e.preventDefault();
        // Disable button
        $(this).prop('disabled', true).text(`${allusfi_obj.export_ongoing_txt}`);
        // Reset vars
        csvRows = [];
        processed = 0;
        totalUsers = 0;
        csvSeparator = ',';

        // Collect only the Export tab inputs (not the whole modal).
        var exportVars = $('#allusfi-export-settings :input').serialize();

        // Reset progress UI
        $('#allusfi_export_progress_text').text(`${allusfi_obj.start_export_process_txt}`);
        $('#allusfi_export_progress_bar').css('width', '0%');

        // Start batch
        allusfi_fetchBatch(1, exportVars);
    });

    $('body').on("click", ".rst_single_dt", function () { $("input[name='one-dt']").val("") });
    // last tab should be opened.
    $(`button[data-id='allusfi-${allusfi_crnt_tab}']`).click();
    /*common functions that is used by this js*/
    function allusfi_downloadCSV(separator) {
        separator = separator || ',';
        let csvContent = csvRows.map(
            row => row.map(v => `"${String(v).replace(/"/g, '""')}"`).join(separator)
        ).join("\n");

        let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        let url = URL.createObjectURL(blob);

        let a = document.createElement('a');
        a.href = url;
        a.download = "users-export.csv";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
        $('#allusfi_export_progress_text').html(`<span class="dashicons dashicons-yes-alt"></span> ${allusfi_obj.btn_export_finish_txt}`);
        // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
        $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
    }

    /**
     * Fetch one export batch via AJAX.
     *
     * Filter params come from the server-side transient (identified by allu_filter_id).
     * Only export-tab settings and pagination are sent in the POST body.
     *
     * @param {number} page         1-based page number.
     * @param {string} exportVarsStr  Serialised export-tab inputs.
     */
    function allusfi_fetchBatch(page, exportVarsStr) {
        var allusfi_searched = $('#user-search-input').val();
        var nonce = allusfi_obj.nonce || $('#allusfi_secure').val() || '';
        var allu_filter_id = allusfi_obj.allu_filter_id || '';

        $.ajax({
            type: "POST",
            url: allusfi_obj.ajax_url,
            data: exportVarsStr +
                '&paged=' + encodeURIComponent(page) +
                '&s=' + encodeURIComponent(allusfi_searched) +
                '&allu_filter_id=' + encodeURIComponent(allu_filter_id) +
                '&allusfi_secure=' + encodeURIComponent(nonce) +
                '&action=allusfi_wp_usr_export_csv',
            success: function (res) {
                if (!res.success) {
                    console.log('Error: ' + (res.data ? res.data.msg : 'unknown'));
                    // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
                    $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
                    return;
                }

                let data = res.data;

                if (page === 1) {
                    totalUsers = data.total;
                    csvRows = []; // reset
                    // Capture separator from first batch response.
                    csvSeparator = (data.separator && data.separator.length) ? data.separator : ',';
                }

                csvRows = csvRows.concat(data.rows);

                processed += (data.rows.length - (page === 1 ? 1 : 0));
                let pct = (totalUsers > 0) ? Math.min(100, Math.round((processed / totalUsers) * 100)) : 0;

                // update progress bar
                $('#allusfi_export_progress_text').text(`${allusfi_obj.export_process_txt} ${processed}/${totalUsers} (${pct}%)`);
                $('#allusfi_export_progress_bar').css('width', pct + '%');

                if (processed < totalUsers) {
                    allusfi_fetchBatch(page + 1, exportVarsStr);
                } else {
                    allusfi_downloadCSV(csvSeparator);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                console.log("AJAX failed. See console.");
                // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
                $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
            }
        });
    }

    /* ============================================================
       SAVED FILTERS
       ============================================================ */

    /**
     * Collect the current export-tab settings as a plain object.
     * Used when saving a filter so the export preferences are persisted too.
     */
    function allusfi_collect_export_settings() {
        var cols = [];
        $('input[name="allusfi_export_cols[]"]:checked').each(function () {
            cols.push($(this).val());
        });
        var metaKeys = [];
        $('input[name="allusfi_export_meta_keys[]"]:checked').each(function () {
            metaKeys.push($(this).val());
        });
        var extraMeta = [];
        $('input[name="allusfi_export_extra_meta[]"]').each(function () {
            var v = $.trim($(this).val());
            if (v) { extraMeta.push(v); }
        });
        return {
            cols: cols,
            include_meta: $('#allusfi_include_meta_toggle').is(':checked'),
            meta_keys: metaKeys,
            extra_meta: extraMeta,
            separator: $('#allusfi_csv_sep').val() || ','
        };
    }

    /**
     * Build the users.php URL for applying a saved filter.
     * The URL is short: only allu_filter_id + nonce — no filter params in the URL.
     *
     * @param {Object} filter  Saved filter object (must have an 'id' property).
     * @return {string}
     */
    function allusfi_sf_build_apply_url(filter) {
        var nonce = allusfi_obj.nonce || '';
        var url = allusfi_obj.users_page_url +
            '?allu_filter_id=saved_filter_' + encodeURIComponent(filter.id) +
            '&allusfi_secure=' + encodeURIComponent(nonce);
        // Re-apply the search term that was active when the filter was saved.
        if (filter.search_term) {
            url += '&s=' + encodeURIComponent(filter.search_term);
        }
        return url;
    }

    /**
     * Render the saved filters list inside #allusfi_sf_list.
     * Entries without an 'id' property are old-format (URL-string) records
     * and are silently skipped.
     */
    // phpcs:disable WordPressVIPMinimum.JS.HTMLExecutingFunctions.append -- All .append() calls in this function receive jQuery DOM objects built with .text()/.attr()/.addClass() only; no raw HTML strings are concatenated or passed.
    function allusfi_sf_render_list(filters) {
        var $list = $('#allusfi_sf_list');
        $list.empty();

        // Filter to new-format entries only (must have 'id' and 'filter_array').
        var validFilters = [];
        if (filters && filters.length) {
            for (var fi = 0; fi < filters.length; fi++) {
                if (filters[fi].id) {
                    validFilters.push(filters[fi]);
                }
            }
        }

        if (validFilters.length === 0) {
            var $emptyMsg = $('<p>').addClass('allusfi-sf-empty').text(allusfi_obj.sf_no_filters_txt);
            $list.empty().append($emptyMsg);
            return;
        }

        var $table = $('<table>').addClass('allusfi-sf-table widefat striped');

        // Build thead using DOM nodes — no HTML string concatenation.
        var $headRow = $('<tr>');
        $headRow.append($('<th>').text('#'))
            .append($('<th>').text(allusfi_obj.sf_col_name_txt))
            .append($('<th>').text(allusfi_obj.sf_col_actions_txt));
        $table.append($('<thead>').append($headRow));

        var $tbody = $('<tbody>');
        $.each(validFilters, function (i, filter) {
            var applyUrl = allusfi_sf_build_apply_url(filter);
            var $row = $('<tr>').addClass('allusfi-sf-row');

            // Cell values set via .text() — safe against XSS.
            var $numTd = $('<td>').addClass('allusfi-sf-num').text(i + 1);
            var $nameTd = $('<td>').addClass('allusfi-sf-name').text(filter.name);

            var $applyBtn = $('<a>').addClass('button button-primary allusfi-sf-apply-btn')
                .attr({ href: applyUrl, target: '_self' })
                .text(allusfi_obj.sf_apply_txt);
            var $delBtn = $('<button>').attr({ type: 'button', 'data-id': i })
                .addClass('button allusfi-sf-delete-btn')
                .text(allusfi_obj.sf_delete_txt);

            var $actionsTd = $('<td>').addClass('allusfi-sf-actions');
            $actionsTd.append($applyBtn).append(document.createTextNode(' ')).append($delBtn);

            $row.append($numTd).append($nameTd).append($actionsTd);
            $tbody.append($row);
        });

        $table.append($tbody);
        $list.append($table);
    }
    // phpcs:enable WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

    // Initial render on page load using localized data
    allusfi_sf_render_list(allusfi_obj.saved_filters);

    /* ---- Show / hide the save-name form ---- */
    $('body').on('click', '#allusfi_sf_show_save_form', function () {
        $('#allusfi_sf_name_wrap').slideDown(150);
        $('#allusfi_sf_name_input').focus();
        $(this).hide();

    });

    $('body').on('click', '#allusfi_sf_cancel_save', function () {
        $('#allusfi_sf_name_wrap').slideUp(150);
        $('#allusfi_sf_name_input').val('');
        $('#allusfi_sf_save_msg').text('').removeClass('allusfi-sf-msg--error allusfi-sf-msg--ok');
        $('#allusfi_sf_show_save_form').show();
    });

    /* ---- Confirm Save ---- */
    $('body').on('click', '#allusfi_sf_confirm_save', function () {
        var filterName = $.trim($('#allusfi_sf_name_input').val());
        var $msg = $('#allusfi_sf_save_msg');

        if (!filterName) {
            $msg.text(allusfi_obj.sf_enter_name_txt).removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
            return;
        }

        // Check duplicate names client-side first (fast feedback)
        var existing = allusfi_obj.saved_filters || [];
        for (var i = 0; i < existing.length; i++) {
            if (existing[i].name && existing[i].name.toLowerCase() === filterName.toLowerCase()) {
                $msg.text(allusfi_obj.sf_duplicate_name_txt).removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
                return;
            }
        }

        // The filter state is already stored in the transient from the last submit.
        // We pass allu_filter_id so the server can locate it; also send current
        // export-tab settings so they are persisted with the saved filter.
        var allu_filter_id = allusfi_obj.allu_filter_id || '';

        if (!allu_filter_id) {
            $msg.text(allusfi_obj.sf_no_active_state_txt).removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
            return;
        }

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: allusfi_obj.ajax_url,
            data: {
                action: 'allusfi_save_filter',
                nonce: allusfi_obj.nonce || $('#allusfi_secure').val() || '',
                filter_name: filterName,
                allu_filter_id: allu_filter_id,
                // Persist the active WP search term so applying the saved filter
                // re-appends ?s= to the URL automatically.
                search_term: $.trim($('#user-search-input').val()),
                export_settings: JSON.stringify(allusfi_collect_export_settings())
            },
            success: function (res) {
                $btn.prop('disabled', false);
                if (!res.success) {
                    $msg.text(res.data && res.data.msg ? res.data.msg : allusfi_obj.sf_save_error_txt)
                        .removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
                    return;
                }
                // Update local cache
                allusfi_obj.saved_filters = res.data.saved_filters;
                allusfi_sf_render_list(allusfi_obj.saved_filters);

                // Reset the form
                $('#allusfi_sf_name_input').val('');
                $('#allusfi_sf_name_wrap').slideUp(150);
                $('#allusfi_sf_show_save_form').show();
                $msg.text('').removeClass('allusfi-sf-msg--error allusfi-sf-msg--ok');
            },
            error: function () {
                $btn.prop('disabled', false);
                $msg.text(allusfi_obj.sf_save_error_txt).removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
            }
        });
    });

    /* ---- Delete ---- */
    $('body').on('click', '.allusfi-sf-delete-btn', function () {
        if (!window.confirm(allusfi_obj.sf_delete_confirm_txt)) {
            return;
        }
        var filterId = $(this).attr('data-id');
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: allusfi_obj.ajax_url,
            data: {
                action: 'allusfi_delete_filter',
                nonce: allusfi_obj.nonce || $('#allusfi_secure').val() || '',
                filter_id: filterId
            },
            success: function (res) {
                if (!res.success) {
                    // phpcs:ignore WordPressVIPMinimum.JS.AlertDebug.Found
                    alert(res.data && res.data.msg ? res.data.msg : allusfi_obj.sf_delete_error_txt);
                    $btn.prop('disabled', false);
                    return;
                }
                allusfi_obj.saved_filters = res.data.saved_filters;
                allusfi_sf_render_list(allusfi_obj.saved_filters);
            },
            error: function () {
                // phpcs:ignore WordPressVIPMinimum.JS.AlertDebug.Found
                alert(allusfi_obj.sf_delete_error_txt);
                $btn.prop('disabled', false);
            }
        });
    });
});