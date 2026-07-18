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
    let csvRows = [];
    let totalUsers = 0;
    let processed = 0;
    let csvSeparator = ',';

    $('body').on('click', '#allusfi_EXP-csv-BTN', function (e) {
        e.preventDefault();
        // Disable button
        $(this).prop('disabled', true).text(`${allusfi_obj.export_ongoing_txt}`);
        // Reset vars
        csvRows = [];
        processed = 0;
        totalUsers = 0;
        csvSeparator = ',';

        // Collect form data (filters already used in your existing code)
        let queryVars = $('#allusfi_model_options :input').serialize();

        // Reset progress UI
        $('#allusfi_export_progress_text').text(`${allusfi_obj.start_export_process_txt}`);
        $('#allusfi_export_progress_bar').css('width', '0%');

        // Start batch
        allusfi_fetchBatch(1, queryVars);
    });
    $('body').on('dblclick', '#LETS-make-POST-Form', function (e) {
        var inpt_form = $(this).parents(`form[method]`);
        var frm_method = inpt_form.attr('method');
        if (frm_method === "get") {
            inpt_form.attr('method', 'post');
            var msg_ele = document.getElementById("pop-pop");
            // phpcs:ignore WordPressVIPMinimum.JS.InnerHTML.Found
            msg_ele.innerHTML = allusfi_obj.post_req_txt;
            msg_ele.className = "show";
        } else {
            inpt_form.attr('method', 'get');
            var msg_ele = document.getElementById("pop-pop");
            // phpcs:ignore WordPressVIPMinimum.JS.InnerHTML.Found
            msg_ele.innerHTML = allusfi_obj.get_req_txt;
            msg_ele.className = "show";
        }
        setTimeout(function () { msg_ele.className = msg_ele.className.replace("show", ""); }, 3000);
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
    function allusfi_fetchBatch(page, queryVars) {
        let allusfi_searched = $('#user-search-input').val();
        $.ajax({
            type: "POST",
            url: allusfi_obj.ajax_url,
            data: queryVars += `&paged=${page}&s=${allusfi_searched}&action=allusfi_wp_usr_export_csv`,
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
                    allusfi_fetchBatch(page + 1, queryVars);
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

    // Params that must NOT be saved (session / page-specific)
    var ALLUSFI_SF_SKIP_PARAMS = [
        'allusfi_secure', 'paged',
        'action', 'action2', 'new_role', 'new_role2'
    ];

    /**
     * Strip unwanted params from a serialized query string.
     * Handles both plain keys and array-style keys (e.g. rl-excld%5B%5D).
     */
    function allusfi_sf_strip_params(serialized) {
        var pairs = serialized.split('&');
        var filtered = pairs.filter(function (pair) {
            var key = decodeURIComponent(pair.split('=')[0]);
            // Strip array brackets for comparison: "rl-excld[]" → "rl-excld"
            var baseKey = key.replace(/\[\d*\]$/, '');
            return ALLUSFI_SF_SKIP_PARAMS.indexOf(baseKey) === -1;
        });
        return filtered.join('&');
    }

    /**
     * Build a users.php URL from saved params + a fresh nonce.
     */
    function allusfi_sf_build_apply_url(savedParams) {
        var freshNonce = $('input[name="allusfi_secure"]').val() || '';
        return 'users.php?' + savedParams + '&allusfi_secure=' + encodeURIComponent(freshNonce) + '&fltr-sbmt=1';
    }

    /**
     * Render the saved filters list inside #allusfi_sf_list.
     */
    // phpcs:disable WordPressVIPMinimum.JS.HTMLExecutingFunctions.append -- All .append() calls in this function receive jQuery DOM objects built with .text()/.attr()/.addClass() only; no raw HTML strings are concatenated or passed.
    function allusfi_sf_render_list(filters) {
        var $list = $('#allusfi_sf_list');
        $list.empty();

        if (!filters || filters.length === 0) {
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
        $.each(filters, function (i, filter) {
            var applyUrl = allusfi_sf_build_apply_url(filter.params);
            var $row = $('<tr>').addClass('allusfi-sf-row');

            // Cell values set via .text() — safe against XSS.
            var $numTd  = $('<td>').addClass('allusfi-sf-num').text(i + 1);
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
            if (existing[i].name.toLowerCase() === filterName.toLowerCase()) {
                $msg.text(allusfi_obj.sf_duplicate_name_txt).removeClass('allusfi-sf-msg--ok').addClass('allusfi-sf-msg--error');
                return;
            }
        }

        // Collect + strip params from the current URL (the definitive source of truth).
        // The "Save" button is only visible when fltr-sbmt=1 is in the URL, so
        // window.location.search already contains the exact, fully-encoded filter params.
        // This avoids re-serialising the form, which can lose array indices, drop
        // checkboxes that weren't re-ticked, and mis-encode special values like "<".
        // phpcs:disable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location -- window.location.search is read-only here; its value is sent to the server via AJAX and sanitized server-side. It is never written to the DOM.
        var rawSearch = window.location.search.length > 1
            ? window.location.search.substring(1) // strip leading "?"
            : '';
        // phpcs:enable WordPressVIPMinimum.JS.Window.VarAssignment, WordPressVIPMinimum.JS.Window.location
        var cleanParams = allusfi_sf_strip_params(rawSearch);
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: allusfi_obj.ajax_url,
            data: {
                action: 'allusfi_save_filter',
                nonce: $('#allusfi_secure').val(),
                filter_name: filterName,
                filter_params: cleanParams
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
                nonce: $('#allusfi_secure').val(),
                filter_id: filterId
            },
            success: function (res) {
                if (!res.success) {
                    alert(res.data && res.data.msg ? res.data.msg : allusfi_obj.sf_delete_error_txt);
                    $btn.prop('disabled', false);
                    return;
                }
                allusfi_obj.saved_filters = res.data.saved_filters;
                allusfi_sf_render_list(allusfi_obj.saved_filters);
            },
            error: function () {
                alert(allusfi_obj.sf_delete_error_txt);
                $btn.prop('disabled', false);
            }
        });
    });
});