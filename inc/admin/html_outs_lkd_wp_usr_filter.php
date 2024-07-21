<?php
/* 
this files is used for data sanitization and GET variables from url query parameters for filtration
*/

// Exit if accessed directly
if ( !defined('ABSPATH') ) exit;

include LKD_WP_USR_FLTR_DIR . '/inc/admin/query_get_paras.' . LKD_WP_USR_FLTR_PREFIX . '.php';
global $pagenow, $wp_roles;
$roles = $wp_roles->get_names();
?>
<div class="alignleft actions">
    <button id="lkd_wp_usr_fltr_pop_up_btn" class="button lkd_usr_fltr_animated-btn" type="button"><?php esc_html_e("Filter Users", 'all-users-filter'); ?>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
    </button>
</div>
<!-- Trigger/Open The Modal -->

<!-- The Modal wrapper starts -->
<div id="lkd_wp_usr_fltr_model_options" class="lkd_wp_usr_fltr_modal">
    <!-- Modal content start -->
    <div class="lkd_wp_usr_fltr_modal-content">
        <div class="close-popup-btn">
            <span class="lkd_wp_usr_fltr_model_close">&times;</span>
        </div>
        <div>
            <div class="lkd_wp_usr_fltr-tabs">
                <button type="button" class="tablinks set-active" data-id="lkd_wp_usr_fltr-general-settings"><?php esc_html_e("General", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="lkd_wp_usr_fltr-date-filter-settings"><?php esc_html_e("Registered Date Filter", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="lkd_wp_usr_fltr-advanced-settings"><?php esc_html_e("Advanced(Meta filter)", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="lkd_wp_usr_fltr-export-settings"><?php esc_html_e("Export", 'all-users-filter'); ?></button>
                <a href="<?php echo esc_url($pagenow, 'all-users-filter'); ?>" class="button button-primary clear_filters"><?php esc_html_e("Clear Filters", 'all-users-filter'); ?></a>
            </div>
            <!-- tab content of genral setting -->
            <div id="lkd_wp_usr_fltr-general-settings" class="lkd_wp_usr_fltr-tabcontent lkd_us_general" style="display:block;">
                <div class="stng-title">
                    <h2><?php esc_html_e("General Filter", 'all-users-filter'); ?></h2>
                </div>
                <div class="parent-col">
                    <div class="child-col">
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Sort By :", 'all-users-filter') ?></b></label>
                            <select name="usr_srt">
                                <option value="" <?php echo ($usr_sort === "") ? " selected" : ""; ?>><?php esc_html_e("Select option...", 'all-users-filter'); ?></option>
                                <option value="f-nm" <?php echo ($usr_sort === "f-nm") ? " selected" : ""; ?>><?php esc_html_e("Firstname", 'all-users-filter'); ?></option>
                                <option value="l-nm" <?php echo ($usr_sort === "l-nm") ? " selected" : ""; ?>><?php esc_html_e("Lastname", 'all-users-filter'); ?></option>
                                <option value="usr-id" <?php echo ($usr_sort === "usr-id") ? " selected" : ""; ?>><?php esc_html_e("User ID", 'all-users-filter'); ?></option>
                                <option value="usr-lgn" <?php echo ($usr_sort === "usr-lgn") ? " selected" : ""; ?>><?php esc_html_e("User Login", 'all-users-filter'); ?></option>
                                <option value="dis-nm" <?php echo ($usr_sort === "dis-nm") ? " selected" : ""; ?>><?php esc_html_e("Display Name", 'all-users-filter'); ?></option>
                                <option value="reg-dt" <?php echo ($usr_sort === "reg-dt") ? " selected" : ""; ?>><?php esc_html_e("Registered Date", 'all-users-filter'); ?></option>
                                <option value="pst-cnt" <?php echo ($usr_sort === "pst-cnt") ? " selected" : ""; ?>><?php esc_html_e("Post Count", 'all-users-filter'); ?></option>
                            </select>
                        </div>
                        <div class="form-field pad-top-40 ordr-by-fields">
                            <label><b><?php esc_html_e("Order By:", 'all-users-filter'); ?></b></label>
                            <div class="form-order">
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="1" <?php echo ($ordr_by ===  "1" || $ordr_by == "") ? " checked" : ""; ?>><?php esc_html_e("ASCENDING", 'all-users-filter'); ?>
                                    <span class="fancy-select button-primary"></span>
                                </label> <br> <br>
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="0" <?php echo ($ordr_by === "0") ? " checked" : ""; ?>><?php esc_html_e("DESCENDING", 'all-users-filter'); ?>
                                    <span class="fancy-select button-primary"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Exclude Users Id/s:", 'all-users-filter'); ?></b></label>
                            <div class="tooltip"> ?
                                <span class="tooltiptext"><?php esc_html_e('Use "-" between numbers to exclude multiple ids.', 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <input value="<?php echo ($excl_ids && is_array($excl_ids)) ? esc_attr(implode('-', $excl_ids)) : ''; ?>" type="text" pattern="^[0-9\-]+$" name="excl-ids" placeholder='Only "numbers" and "-" are allowed.'>
                            </div>
                        </div>
                    </div>
                    <div class="child-col between-two-dates">
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e("Filter users by date of registration : ", 'all-users-filter') ?></b></label>
                            <div class="pad-top-10">
                                <input type="date" name="one-dt" <?php echo ($one_date) ? " value='" . esc_attr($one_date) . "'" : ""; ?>>
                                <button type="button" class="button rst_single_dt"> <?php esc_html_e("Reset", 'all-users-filter'); ?> </button>
                            </div>
                        </div>
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e('Write something like  "12 Hours ago" : ', 'all-users-filter') ?></b></label>
                            <div class="tooltip"> ?
                                <span class="tooltiptext"><?php echo wp_kses_post("Find registered users with <br/>(E.g. 12 hours ago, <br/> 1 month ago): ", 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <input placeholder="E.g. 12 Hours ago" type="text" name="cstm-dt" <?php echo ($cstm_dt) ? " value='" . esc_attr($cstm_dt) . "'" : ""; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="child-col">
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Exclude roles:", 'all-users-filter') ?></b></label>
                            <div class="exclude-roles pad-top-10">
                                <?php
                                    if( ! empty( $roles ) ):
                                        foreach ($roles as $role_slug => $role_name) : ?>
                                    <label class="fancy-check">
                                        <input type="checkbox" name="rl-excld[]" value="<?php echo esc_attr($role_slug); ?>" <?php echo (in_array($role_slug, $exlude_roles)) ? " checked" : ""; ?>> <?php echo esc_html($role_name); ?>
                                        <span class="fancy-checkmark button"></span>
                                    </label>
                                <?php endforeach;
                                    endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- tab content of genral setting ends -->
            <!-- tab content of date setting starts -->
            <div id="lkd_wp_usr_fltr-date-filter-settings" class="lkd_wp_usr_fltr-tabcontent lkd_us_dates" style="display:none;">
                <div class="txt-center">
                    <h3><?php esc_html_e("Multiple Date Filter", 'all-users-filter'); ?></h3>
                </div>
                <div class="form-field pad-top-40">
                    <div>
                        <label><b><?php esc_html_e("Filter users between two dates:", 'all-users-filter') ?></b></label>
                        <div class="tooltip"> ?
                            <span class="tooltiptext"><?php esc_html_e("This filter will filter users based on registration date.", 'all-users-filter'); ?></span>
                        </div>
                        <button class="click_to_append button button-primary add_multi_date" type="button" id="lkd_wp_usr_fltr_add_multi_date"><?php esc_html_e("Add date", 'all-users-filter'); ?></button>
                    </div>
                    <table class="meta_filter_table lkd_wp_user_fltr_meta_append_content">
                        <tbody id="dt_append_content">
                            <tr>
                                <th><?php esc_html_e('From Date', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('To Date', 'all-users-filter'); ?></th>
                            </tr>
                            <?php if (!empty($multi_from_date) && !empty($multi_to_date)) {
                                foreach ($multi_from_date as $index => $single_val) {
                            ?>
                                    <tr>
                                        <td>
                                            <input type="date" name="mlt-f-dt[]" <?php echo ($multi_from_date[$index]) ? " value='" . esc_attr($multi_from_date[$index]) . "'" : ''; ?>>
                                        </td>
                                        <td>
                                            <input type="date" name="mlt-t-dt[]" <?php echo ($multi_to_date[$index]) ? " value='" . esc_attr($multi_to_date[$index]) . "'" : ''; ?>>
                                            <button type="button" class="button remov_date"> X </button>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } ?>
                        </tbody>
                    </table>
                    <template id="lkd_wp_user_fltr_dt_copy_content">
                        <tr>
                            <td>
                                <input type="date" name="mlt-f-dt[]">
                            </td>
                            <td>
                                <input type="date" name="mlt-t-dt[]">
                                <button type="button" class="button remov_date"> X </button>
                            </td>
                        </tr>
                    </template>
                </div>
            </div>
            <!-- tab content of date setting ends -->
            <!-- tab content of advanced setting starts -->
            <div id="lkd_wp_usr_fltr-advanced-settings" class="lkd_wp_usr_fltr-tabcontent lkd_us_advance" style="display:none;">
                <div id="LETS-make-POST-Form" class="stng-title">
                    <h2><?php esc_html_e("Advanced Filters", 'all-users-filter') ?></h2>
                </div>
                <div>
                    <div>
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Filter users using meta key/value:", 'all-users-filter') ?></b></label>
                            <div class="tooltip"> ?
                                <span class="tooltiptext"><?php esc_html_e("1) Add meta key.  2) Select Operator.  3) Enter value. ", 'all-users-filter'); ?></span>
                            </div>
                            <button class="click_to_append button button-primary add_multi_meta_query" type="button" id="lkd_wp_usr_fltr_add_meta_query"><?php esc_html_e("ADD META FILTER", 'all-users-filter'); ?></button>
                            <label class="relation"> Relation: </label>
                            <select name="rltn">
                                <option value="nd" <?php echo ($relation == 'nd') ?  " selected" : ""; ?>><?php esc_html_e("AND", 'all-users-filter'); ?></option>
                                <option value="or" <?php echo ($relation == 'or') ?  " selected" : ""; ?>><?php esc_html_e("OR", 'all-users-filter'); ?></option>
                            </select>
                            <template id="lkd_wp_user_fltr_meta_copy_content">
                                <tr>
                                    <td>
                                        <input type="text" name="mta-ky[]" placeholder="Add meta key like: monthly_salary">
                                    </td>
                                    <td>
                                        <select name="mta-op[]">
                                            <?php
                                            if (!empty($compatible_compares)) {
                                                foreach ($compatible_compares as $single_op) {
                                                    echo "<option value='" . esc_attr($single_op) . "' >" . esc_html($single_op) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="mta-tp[]">
                                            <?php
                                            $compatible_type = array("CHAR", "NUMERIC", "BINARY", "DATE", "DATETIME", "DECIMAL", "SIGNED", "UNSIGNED", "TIME");
                                            if (!empty($compatible_type)) {
                                                foreach ($compatible_type as $single_tp) {
                                                    echo "<option value='" . esc_attr($single_tp) . "' >" . esc_html($single_tp) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="mta-vl[]">
                                        <button type="button" class="button remov_meta"> X </button>
                                    </td>
                                </tr>
                            </template>
                            <div class="pad-top-10 ">
                                <table class="lkd_table_append meta_filter_table lkd_wp_user_fltr_meta_append_content">
                                    <tbody id="advnce_append_content">
                                        <tr>
                                            <th><?php esc_html_e("Meta key", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Operator", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Type", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Value", 'all-users-filter'); ?></th>
                                        </tr>
                                        <?php if ($meta_keys) {
                                            foreach ($meta_keys as $index => $single_val) {
                                                $compatible_compares = array('=', "!=", 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=', 'NOT EXISTS', 'NOT REGEXP');  ?>
                                                <tr>
                                                    <td>
                                                        <input type="text" name="mta-ky[]" value="<?php echo esc_attr($meta_keys[$index]); ?>" placeholder="Add meta key like: monthly_salary">
                                                    </td>
                                                    <td>
                                                        <select name="mta-op[]">
                                                            <?php
                                                            foreach ($compatible_compares as $single_op)
                                                                echo "<option value='" . esc_attr($single_op) . "'" . ($single_op === $meta_ops[$index]  ?  " selected " : "")  . " >" . esc_html($single_op) . '</option>';
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="mta-tp[]">
                                                            <?php
                                                            $compatible_type = array("CHAR", "NUMERIC", "BINARY", "DATE", "DATETIME", "DECIMAL", "SIGNED", "UNSIGNED", "TIME");
                                                            foreach ($compatible_type as $single_tp)
                                                                echo "<option value='" . esc_attr($single_tp) . "'" . ($single_tp ==  $meta_tp[$index]  ?  " selected " : "")  . " >" .  esc_html($single_tp) . '</option>';
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="mta-vl[]" value="<?php echo  empty(trim($meta_vals[$index])) ? "" : esc_attr($meta_vals[$index]); ?>">
                                                        <button type="button" class="button remov_meta"> X </button>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- tab content of advanced setting ends-->
            <!-- tab content of export setting starts -->
            <div id="lkd_wp_usr_fltr-export-settings" class="lkd_wp_usr_fltr-tabcontent lkd_us_export" style="display:none;">
                <div class="txt-center export-btn">
                    <p class="big_p_bold"><b><?php esc_html_e("The export file will include following things.", 'all-users-filter'); ?></b></p>
                    <p class="big_p">
                        <?php esc_html_e("User ID, User Login, User Email, User Nicename, Display Name, User Role.", 'all-users-filter'); ?> <br>
                        <mark><?php esc_html_e("Note: If filtered with meta key/s(advance filter), meta value/s will be included.",  'all-users-filter'); ?> </mark>
                    </p>
                    <br>
                    <button id="lkd_EXP-csv-BTN" class="button glow-on-hover" name="exp-csv" type="button"><?php esc_html_e("CLICK HERE TO EXPORT CSV &#8681;", 'all-users-filter'); ?></button>
                </div>
            </div>
            <!-- tab content of export setting ends -->
            <div class="pop-up-footer">
                <div style="display: inline-block;">
                    <p> Made with <span class="heart"></span> By <a target="_blank" style="color: #5dacec;" href="https://www.instagram.com/hlakkad/">Hardik Patel/Hardik Lakkad</a> </p>
                    <p> Need more plugins customiation? <a href="https://in.linkedin.com/in/hardik-lakkad-097b12147" target="_blank" style="color: #5dacec;">Contact me</a> &#128104;&#8205;&#128187;</p>
                </div>
                <div class="txt-right lkd-sbmit-actions">
                    <?php wp_nonce_field('lkd_usr_filter_secure','lkd_usr_filter_secure') ?>
                    <button class="button button-primary" type="submit" name="fltr-sbmt" value="1"><?php esc_html_e("Filter Users", 'all-users-filter'); ?></button>
                </div>
                <div id="pop-pop"></div>
            </div>
            <!-- tab content of advanced setting ends -->
        </div>
    </div>
    <!-- Modal content ends -->
</div>
<!-- The Modal wrapper ends -->
<?php
