(function ($) {
	"use strict";

})(jQuery);
jQuery(document).ready(function ($) {
	var lkd_crnt_tab = localStorage.getItem("lkd_usr_fltr_current_tab");
	lkd_crnt_tab = (lkd_crnt_tab === null) ? "general-settings" : lkd_crnt_tab;
	var lkd_usr_fltr_modal = $("#lkd_wp_usr_fltr_model_options");
	var btn = $(`#lkd_wp_usr_fltr_pop_up_btn`);
	var span = $(`.lkd_wp_usr_fltr_model_close`);
	// When the user clicks on the button, open the modal
	$('body').on('click', "#lkd_wp_usr_fltr_pop_up_btn", function () { lkd_usr_fltr_modal.attr("style", "display:flex;"); });
	$('body').on('click', ".lkd_wp_usr_fltr_model_close", function () { lkd_usr_fltr_modal.attr("style", "display:none;"); });
	// When the user clicks anywhere outside of the modal, close it
	$('body').on('click', function (e) {
		if (e.target.className == "lkd_wp_usr_fltr_modal")
			lkd_usr_fltr_modal.attr("style", "display:none;");
	});
	$('body').on("click", ".tablinks", function (e) {
		$(".tablinks").removeClass("set-active");
		$(this).addClass("set-active");
		$(".lkd_wp_usr_fltr-tabcontent").hide();
		$("#" + $(this).attr("data-id")).show();
		var crnt_tab_attr = $(this).attr("data-id");
		var splited_ary = crnt_tab_attr.split('_fltr-');
		localStorage.setItem("lkd_usr_fltr_current_tab", splited_ary[1]);
	});
	$('body').on('click', ".remov_date", function () { $(this).parents('tr').remove(); });
	$('body').on('click', ".remov_meta", function () { $(this).parents('tr').remove(); });
	$('body').on('click', '#lkd_wp_usr_fltr_add_multi_date', function () {
		const DATE_COPY_CONTENT = $("#lkd_wp_user_fltr_dt_copy_content").html().trim();
		$("#dt_append_content").append(DATE_COPY_CONTENT);
	});
	$('body').on('click', '#lkd_wp_usr_fltr_add_meta_query', function () {
		const META_COPY_CONTENT = $("#lkd_wp_user_fltr_meta_copy_content").html().trim();
		$("#advnce_append_content").append(META_COPY_CONTENT);
	});
	let csvRows = [];
    let totalUsers = 0;
    let processed = 0;

    $('body').on('click', '#lkd_EXP-csv-BTN', function (e) {
        e.preventDefault();

        // Disable button
        $(this).prop('disabled', true).text("Exporting...");

        // Reset vars
        csvRows = [];
        processed = 0;
        totalUsers = 0;

        // Collect form data (filters already used in your existing code)
        let queryVars = $('#lkd_wp_usr_fltr_model_options :input').serialize();

        // Reset progress UI
        $('#lkd_export_progress_text').text("Starting export...");
        $('#lkd_export_progress_bar').css('width', '0%');

        // Start batch
        kd_wp_usr_fltr_fetchBatch(1, queryVars);
    });
	$('body').on('dblclick', '#LETS-make-POST-Form', function (e) {
		var inpt_form = $(this).parents(`form[method]`);
		var frm_method = inpt_form.attr('method');
		if (frm_method === "get") {
			inpt_form.attr('method', 'post');
			var msg_ele = document.getElementById("pop-pop");
			msg_ele.innerHTML = 'POST REQUEST ENABLED!';
			msg_ele.className = "show";
		} else {
			inpt_form.attr('method', 'get');
			var msg_ele = document.getElementById("pop-pop");
			msg_ele.innerHTML = 'GET REQUEST ENABLED!';
			msg_ele.className = "show";
		}
		setTimeout(function () { msg_ele.className = msg_ele.className.replace("show", ""); }, 3000);
	});
	$('body').on("click", ".rst_single_dt", function () { $("input[name='one-dt']").val("") });
	// last tab should be opened. 
	$(`button[data-id='lkd_wp_usr_fltr-${lkd_crnt_tab}']`).click();
    /*common functions that is used by this js*/ 
    function lkd_wp_usr_fltr_downloadCSV() {
        let csvContent = csvRows.map(
            row => row.map(v => `"${String(v).replace(/"/g, '""')}"`).join(",")
        ).join("\n");

        let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        let url = URL.createObjectURL(blob);

        let a = document.createElement('a');
        a.href = url;
        a.download = "users-export.csv";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        $('#lkd_export_progress_text').html(`<span class="dashicons dashicons-yes-alt"></span> Export complete`);
        $('#lkd_EXP-csv-BTN').prop('disabled', false).html("CLICK HERE TO EXPORT CSV <span class='dashicons dashicons-download'></span>");
    }
    function kd_wp_usr_fltr_fetchBatch(page, queryVars) {
        let lkd_searched = $('#user-search-input').val();
        $.ajax({
            type: "POST",
            url: lkd_usr_fltr_obj.ajax_url,
            data: queryVars += `&paged=${page}&s=${lkd_searched}` ,
            success: function(res) {
                if (!res.success) {
                    console.log('Error: ' + (res.data ? res.data.msg : 'unknown'));
                    $('#lkd_EXP-csv-BTN').prop('disabled', false).html("CLICK HERE TO EXPORT CSV <span class='dashicons dashicons-download'></span>");
                    return;
                }

                let data = res.data;

                if (page === 1) {
                    totalUsers = data.total;
                    csvRows = []; // reset
                }

                csvRows = csvRows.concat(data.rows);

                processed += (data.rows.length - (page === 1 ? 1 : 0)); 
                let pct = (totalUsers > 0) ? Math.min(100, Math.round((processed / totalUsers) * 100)) : 0;

                // update progress bar
                $('#lkd_export_progress_text').text(`Exporting... ${processed}/${totalUsers} (${pct}%)`);
                $('#lkd_export_progress_bar').css('width', pct + '%');

                if (processed < totalUsers) {
                    kd_wp_usr_fltr_fetchBatch(page + 1, queryVars);
                } else {
                    lkd_wp_usr_fltr_downloadCSV();
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                console.log("AJAX failed. See console.");
                $('#lkd_EXP-csv-BTN').prop('disabled', false).html("CLICK HERE TO EXPORT CSV <span class='dashicons dashicons-download'></span>");
            }
        });
    }
});