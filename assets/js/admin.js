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
	$('body').on('click', '#allusfi_add_multi_date', function () {
		const DATE_COPY_CONTENT = $("#allusfi_dt_copy_content").html().trim();
		$("#dt_append_content").append(DATE_COPY_CONTENT);
	});
	$('body').on('click', '#allusfi_add_meta_query', function () {
		const META_COPY_CONTENT = $("#allusfi_meta_copy_content").html().trim();
		$("#advnce_append_content").append(META_COPY_CONTENT);
	});
	let csvRows = [];
    let totalUsers = 0;
    let processed = 0;

    $('body').on('click', '#allusfi_EXP-csv-BTN', function (e) {
        e.preventDefault();
        // Disable button
        $(this).prop('disabled', true).text(`${allusfi_obj.export_ongoing_txt}`);
        // Reset vars
        csvRows = [];
        processed = 0;
        totalUsers = 0;

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
			msg_ele.innerHTML = get_req_txt.post_req_txt;
			msg_ele.className = "show";
		} else {
			inpt_form.attr('method', 'get');
			var msg_ele = document.getElementById("pop-pop");
			msg_ele.innerHTML = allusfi_obj.get_req_txt;
			msg_ele.className = "show";
		}
		setTimeout(function () { msg_ele.className = msg_ele.className.replace("show", ""); }, 3000);
	});
	$('body').on("click", ".rst_single_dt", function () { $("input[name='one-dt']").val("") });
	// last tab should be opened. 
	$(`button[data-id='allusfi-${allusfi_crnt_tab}']`).click();
    /*common functions that is used by this js*/ 
    function allusfi_downloadCSV() {
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

        $('#allusfi_export_progress_text').html(`<span class="dashicons dashicons-yes-alt"></span> ${allusfi_obj.btn_export_finish_txt}`);
        $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
    }
    function allusfi_fetchBatch(page, queryVars) {
        let allusfi_searched = $('#user-search-input').val();
        $.ajax({
            type: "POST",
            url: allusfi_obj.ajax_url,
            data: queryVars += `&paged=${page}&s=${allusfi_searched}&action=allusfi_wp_usr_export_csv` ,
            success: function(res) {
                if (!res.success) {
                    console.log('Error: ' + (res.data ? res.data.msg : 'unknown'));
                    $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
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
                $('#allusfi_export_progress_text').text(`${allusfi_obj.export_process_txt} ${processed}/${totalUsers} (${pct}%)`);
                $('#allusfi_export_progress_bar').css('width', pct + '%');

                if (processed < totalUsers) {
                    allusfi_fetchBatch(page + 1, queryVars);
                } else {
                    allusfi_downloadCSV();
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                console.log("AJAX failed. See console.");
                $('#allusfi_EXP-csv-BTN').prop('disabled', false).html(`${allusfi_obj.btn_export_txt} <span class='dashicons dashicons-download'></span>`);
            }
        });
    }
});