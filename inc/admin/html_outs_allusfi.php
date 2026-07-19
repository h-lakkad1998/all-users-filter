<?php

/**
 * Modal filter UI for All Users Filter
 *
 * Uses: $admin = new ALLUSFI_Admin(); $params = $admin->allusfi_get_query_params();
 *
 * - All request parsing/sanitization is done in the class method.
 * - The template only escapes output for safe rendering.
 *
 * Place this file where your admin class includes it.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wp_roles, $pagenow;

/* -------------------------
 * Instantiate admin helper
 * ------------------------- */
$params = array(
    'secure'          => false,
    'ordr_by'         => '',
    'usr_sort'        => '',
    'one_date'        => '',
    'cstm_dt'         => '',
    'relation'        => 'nd',
    'exclude_roles'   => array(),
    'excl_ids'        => array(),
    'multi_from_date' => array(),
    'multi_to_date'   => array(),
    'meta_keys'       => array(),
    'meta_vals'       => array(),
    'meta_ops'        => array(),
    'meta_tp'         => array(),
    // Relative-date meta filter rows.
    'rel_date_keys'   => array(),
    'rel_date_vals'   => array(),
    'rel_date_tp'     => array(),
    'wc_order_enabled' => false,
    'wc_order_count'   => 0,
    'wc_order_op'      => '>',
);

if (class_exists('ALLUSFI_Admin')) {
    $admin  = new ALLUSFI_Admin();
    $params = (array) $admin->allusfi_get_query_params();
}

/* Roles for checkboxes */
$roles = (isset($wp_roles) && is_object($wp_roles)) ? $wp_roles->get_names() : array();

/* Simple compatible compares list for select options */
$allusfi_html_compatible_compares = array('=', '!=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=', 'NOT EXISTS', 'NOT REGEXP');

?>
<div class="alignleft actions">
    <button id="allusfi_pop_up_btn" class="button allusfi_animated-btn"
        type="button"><?php esc_html_e("Filter Users", 'all-users-filter'); ?>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
        <span class="button-primary abc_snake"> </span>
    </button>
</div>
<!-- Trigger/Open The Modal -->

<!-- The Modal wrapper starts -->
<div id="allusfi_model_options" class="allusfi_modal" style="display: none;">
    <!-- Modal content start -->
    <div class="allusfi_modal-content">
        <div class="close-popup-btn">
            <span class="allusfi_model_close">&times;</span>
        </div>
        <div>
            <div class="allusfi-tabs">
                <button type="button" class="tablinks set-active"
                    data-id="allusfi-general-settings"><?php esc_html_e("General", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks"
                    data-id="allusfi-date-filter-settings"><?php esc_html_e("Registered Date Filter", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks"
                    data-id="allusfi-advanced-settings"><?php esc_html_e("Advanced(Meta filter)", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks"
                    data-id="allusfi-meta-date-filter"><?php esc_html_e("Relative/Meta Date Filter", 'all-users-filter'); ?></button>
                <?php if (class_exists('WooCommerce')): ?>
                    <button type="button" class="tablinks"
                        data-id="allusfi-woocommerce-settings"><?php esc_html_e("WooCommerce", 'all-users-filter'); ?></button>
                <?php endif; ?>
                <button type="button" class="tablinks"
                    data-id="allusfi-export-settings"><?php esc_html_e("Export", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks"
                    data-id="allusfi-saved-filters"><?php esc_html_e("Saved Filters", 'all-users-filter'); ?></button>
                <a href="<?php echo esc_url($pagenow, 'all-users-filter'); ?>"
                    class="button button-primary clear_filters"><?php esc_html_e("Clear Filters", 'all-users-filter'); ?></a>
            </div>
            <!-- tab content of genral setting -->
            <div id="allusfi-general-settings" class="allusfi-tabcontent allusfi_us_general" style="display:block;">
                <div class="stng-title">
                    <h2><?php esc_html_e("General Filter", 'all-users-filter'); ?></h2>
                </div>
                <div class="parent-col">
                    <div class="child-col">
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Sort By :", 'all-users-filter') ?></b></label>
                            <select name="usr_srt">
                                <option value="" <?php selected($params['usr_sort'], ''); ?>>
                                    <?php esc_html_e("Select option...", 'all-users-filter'); ?></option>
                                <option value="f-nm" <?php selected($params['usr_sort'], 'f-nm'); ?>>
                                    <?php esc_html_e("First name", 'all-users-filter'); ?></option>
                                <option value="l-nm" <?php selected($params['usr_sort'], 'l-nm'); ?>>
                                    <?php esc_html_e("Last name", 'all-users-filter'); ?></option>
                                <option value="usr-id" <?php selected($params['usr_sort'], 'usr-id'); ?>>
                                    <?php esc_html_e("User ID", 'all-users-filter'); ?></option>
                                <option value="usr-lgn" <?php selected($params['usr_sort'], 'usr-lgn'); ?>>
                                    <?php esc_html_e("User Login", 'all-users-filter'); ?></option>
                                <option value="dis-nm" <?php selected($params['usr_sort'], 'dis-nm'); ?>>
                                    <?php esc_html_e("Display Name", 'all-users-filter'); ?></option>
                                <option value="reg-dt" <?php selected($params['usr_sort'], 'reg-dt'); ?>>
                                    <?php esc_html_e("Registered Date", 'all-users-filter'); ?></option>
                                <option value="pst-cnt" <?php selected($params['usr_sort'], 'pst-cnt'); ?>>
                                    <?php esc_html_e("Post Count", 'all-users-filter'); ?></option>
                            </select>
                        </div>
                        <div class="form-field pad-top-40 ordr-by-fields">
                            <label><b><?php esc_html_e("Order By:", 'all-users-filter'); ?></b></label>
                            <div class="form-order">
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="1" <?php checked($params['ordr_by'], '1'); ?>><?php esc_html_e("ASCENDING", 'all-users-filter'); ?>
                                    <span class="fancy-select button-primary"></span>
                                </label> <br> <br>
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="0" <?php checked($params['ordr_by'], '0'); ?>><?php esc_html_e("DESCENDING", 'all-users-filter'); ?>
                                    <span class="fancy-select button-primary"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Exclude Users Id/s:", 'all-users-filter'); ?></b></label>
                            <div class="tooltip"> ?
                                <span
                                    class="tooltiptext"><?php esc_html_e('Use "-" between numbers to exclude multiple ids.', 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <input
                                    value="<?php echo esc_attr((!empty($params['excl_ids']) && is_array($params['excl_ids'])) ? implode('-', $params['excl_ids']) : ''); ?>"
                                    type="text" pattern="^[0-9\-]+$" name="excl-ids"
                                    placeholder="<?php esc_attr_e('Only numbers and "-" are allowed.', 'all-users-filter') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="child-col between-two-dates">
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e("Filter users by date of registration : ", 'all-users-filter') ?></b></label>
                            <div class="pad-top-10">
                                <input type="date" name="one-dt" value="<?php echo esc_attr($params['one_date']); ?>">
                                <button type="button" class="button rst_single_dt">
                                    <?php esc_html_e("Reset", 'all-users-filter'); ?> </button>
                            </div>
                        </div>
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e('Write something like  "12 Hours ago" : ', 'all-users-filter') ?></b></label>
                            <div class="tooltip"> ?
                                <span
                                    class="tooltiptext"><?php echo wp_kses_post("Find registered users with <br/>(E.g. 12 hours ago, <br/> 1 month ago): ", 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <input placeholder="<?php esc_attr_e('E.g. 12 Hours ago', 'all-users-filter') ?>"
                                    type="text" name="cstm-dt" value="<?php echo esc_attr($params['cstm_dt']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="child-col">
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Exclude roles:", 'all-users-filter') ?></b></label>
                            <div class="exclude-roles pad-top-10">
                                <?php
                                if (!empty($roles)):
                                    foreach ($roles as $role_slug => $role_name): ?>
                                        <label class="fancy-check">
                                            <input type="checkbox" name="rl-excld[]" value="<?php echo esc_attr($role_slug); ?>"
                                                <?php echo (in_array($role_slug, (array) $params['exclude_roles'], true)) ? ' checked' : ''; ?>> <?php echo esc_html($role_name); ?>
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
            <div id="allusfi-date-filter-settings" class="allusfi-tabcontent allusfi_us_dates" style="display:none;">
                <div class="txt-center">
                    <h3><?php esc_html_e("Multiple Date Filter", 'all-users-filter'); ?></h3>
                </div>
                <div class="form-field pad-top-40">
                    <div>
                        <label><b><?php esc_html_e("Filter users between two dates:", 'all-users-filter') ?></b></label>
                        <div class="tooltip"> ?
                            <span
                                class="tooltiptext"><?php esc_html_e("This filter will filter users based on registration date.", 'all-users-filter'); ?></span>
                        </div>
                        <button class="click_to_append button button-primary add_multi_date" type="button"
                            id="allusfi_add_multi_date"><?php esc_html_e("Add date", 'all-users-filter'); ?></button>
                    </div>
                    <table class="meta_filter_table allusfi_meta_append_content">
                        <tbody id="dt_append_content">
                            <tr>
                                <th><?php esc_html_e('From Date', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('To Date', 'all-users-filter'); ?></th>
                            </tr>
                            <?php
                            if (!empty($params['multi_from_date']) && is_array($params['multi_from_date'])):
                                foreach ($params['multi_from_date'] as $index => $from):
                                    $to = isset($params['multi_to_date'][$index]) ? $params['multi_to_date'][$index] : '';
                            ?>
                                    <tr>
                                        <td><input type="date" name="mlt-f-dt[]" value="<?php echo esc_attr($from); ?>"></td>
                                        <td>
                                            <input type="date" name="mlt-t-dt[]" value="<?php echo esc_attr($to); ?>">
                                            <button type="button" class="button remov_date">X</button>
                                        </td>
                                    </tr>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                    <template id="allusfi_dt_copy_content">
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
            <div id="allusfi-advanced-settings" class="allusfi-tabcontent allusfi_us_advance" style="display:none;">
                <div id="LETS-make-POST-Form" class="stng-title">
                    <h2><?php esc_html_e("Advanced Filters", 'all-users-filter') ?></h2>
                </div>
                <div>
                    <div>
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Filter users using meta key/value:", 'all-users-filter') ?></b></label>
                            <div class="tooltip"> ?
                                <span
                                    class="tooltiptext"><?php esc_html_e("1) Add meta key.  2) Select Operator.  3) Enter value. ", 'all-users-filter'); ?></span>
                            </div>
                            <button class="click_to_append button button-primary add_multi_meta_query" type="button"
                                id="allusfi_add_meta_query"><?php esc_html_e("ADD META FILTER", 'all-users-filter'); ?></button>
                            <label class="relation"> Relation: </label>
                            <select name="rltn">
                                <option value="nd" <?php selected($params['relation'], 'nd'); ?>>
                                    <?php esc_html_e("AND", 'all-users-filter'); ?></option>
                                <option value="or" <?php selected($params['relation'], 'or'); ?>>
                                    <?php esc_html_e("OR", 'all-users-filter'); ?></option>
                            </select>
                            <template id="allusfi_meta_copy_content">
                                <tr>
                                    <td>
                                        <input type="text" name="mta-ky[]"
                                            placeholder="<?php esc_attr_e('Add meta key like: monthly_salary', 'all-users-filter'); ?>">
                                    </td>
                                    <td>
                                        <select name="mta-op[]">
                                            <?php
                                            if (!empty($allusfi_html_compatible_compares)) {
                                                foreach ($allusfi_html_compatible_compares as $single_op) {
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
                                <table class="allusfi_table_append meta_filter_table allusfi_meta_append_content">
                                    <tbody id="advnce_append_content">
                                        <tr>
                                            <th><?php esc_html_e("Meta key", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Operator", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Type", 'all-users-filter'); ?></th>
                                            <th><?php esc_html_e("Value", 'all-users-filter'); ?></th>
                                        </tr>
                                        <?php
                                        $meta_count = max(0, max(count((array) $params['meta_keys']), count((array) $params['meta_ops']), count((array) $params['meta_tp']), count((array) $params['meta_vals'])));

                                        if ($meta_count >= 1):
                                            for ($i = 0; $i < $meta_count; $i++):
                                                $key = isset($params['meta_keys'][$i]) ? $params['meta_keys'][$i] : '';
                                                $op = isset($params['meta_ops'][$i]) ? $params['meta_ops'][$i] : '=';
                                                $tp = isset($params['meta_tp'][$i]) ? $params['meta_tp'][$i] : 'CHAR';
                                                $value = isset($params['meta_vals'][$i]) ? $params['meta_vals'][$i] : '';
                                        ?>
                                                <tr>
                                                    <td><input type="text" name="mta-ky[]" value="<?php echo esc_attr($key); ?>"
                                                            placeholder="<?php esc_attr_e('Add meta key like: monthly_salary', 'all-users-filter'); ?>">
                                                    </td>

                                                    <td>
                                                        <select name="mta-op[]">
                                                            <?php foreach ($allusfi_html_compatible_compares as $single_op): ?>
                                                                <option value="<?php echo esc_attr($single_op); ?>" <?php selected($op, $single_op); ?>>
                                                                    <?php echo esc_html($single_op); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <select name="mta-tp[]">
                                                            <?php
                                                            $compatible_type = array('CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME');
                                                            foreach ($compatible_type as $single_tp):
                                                            ?>
                                                                <option value="<?php echo esc_attr($single_tp); ?>" <?php selected($tp, $single_tp); ?>>
                                                                    <?php echo esc_html($single_tp); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="mta-vl[]"
                                                            value="<?php echo esc_attr($value); ?>">
                                                        <button type="button" class="button remov_meta">X</button>
                                                    </td>
                                                </tr>
                                        <?php endfor;
                                        endif;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- tab content of advanced setting ends-->

            <!-- tab content of meta date filter starts -->
            <div id="allusfi-meta-date-filter" class="allusfi-tabcontent allusfi_us_meta_date" style="display:none;">
                <div class="stng-title">
                    <h2><?php esc_html_e('Relative/Meta Date Filter', 'all-users-filter'); ?></h2>
                </div>
                <div class="form-field pad-top-20">
                    <div>
                        <p class="description">
                            <?php
                            echo wp_kses(
                                __('Filter users by a meta field containing a date using relative or absolute expressions.<br><strong>Examples:</strong> <code>today</code>, <code>yesterday</code>, <code>last week</code>, <code>this month</code>, <code>last month</code>, <code>last 30 days</code>, <code>30 mins ago</code>, <code>2 months ago</code>, <code>2026-05-01,2026-05-31</code>, <code>2026-07-18</code>', 'all-users-filter'),
                                array('br' => array(), 'strong' => array(), 'code' => array())
                            );
                            ?>
                        </p>
                        <button class="button button-primary" type="button" id="allusfi_add_rel_date" style="margin-top:10px;">
                            <?php esc_html_e('Add Meta Date Filter Row', 'all-users-filter'); ?>
                        </button>
                    </div>

                    <?php
                    // Re-use the $rd_allowed_types_ui variable (may already be set above, redeclare safely).
                    $rd_allowed_types_ui = array('DATE', 'DATETIME', 'TIME', 'NUMERIC');
                    $rd_count = max(
                        count((array) $params['rel_date_keys']),
                        count((array) $params['rel_date_vals']),
                        count((array) $params['rel_date_tp'])
                    );
                    ?>

                    <table class="meta_filter_table allusfi_meta_append_content allusfi-rel-date-table" style="margin-top:14px;">
                        <tbody id="rel_date_append_content">
                            <tr>
                                <th><?php esc_html_e('Meta Key', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('Operator', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('Type', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('Value (semantic or YYYY-MM-DD)', 'all-users-filter'); ?></th>
                            </tr>
                            <?php
                            if ($rd_count >= 1):
                                for ($i = 0; $i < $rd_count; $i++):
                                    $rd_key = isset($params['rel_date_keys'][$i]) ? $params['rel_date_keys'][$i] : '';
                                    $rd_val = isset($params['rel_date_vals'][$i]) ? $params['rel_date_vals'][$i] : '';
                                    $rd_tp  = isset($params['rel_date_tp'][$i])   ? $params['rel_date_tp'][$i]   : 'DATE';
                            ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="rdmq-ky[]" value="<?php echo esc_attr($rd_key); ?>"
                                                placeholder="<?php esc_attr_e('e.g. last_updated', 'all-users-filter'); ?>">
                                        </td>
                                        <td>
                                            <span class="allusfi-op-label">BETWEEN</span>
                                            <input type="hidden" name="rdmq-op[]" value="BETWEEN">
                                        </td>
                                        <td>
                                            <select name="rdmq-tp[]">
                                                <?php foreach ($rd_allowed_types_ui as $single_rdtp): ?>
                                                    <option value="<?php echo esc_attr($single_rdtp); ?>" <?php selected($rd_tp, $single_rdtp); ?>>
                                                        <?php echo esc_html($single_rdtp); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="rdmq-vl[]" value="<?php echo esc_attr($rd_val); ?>"
                                                placeholder="<?php esc_attr_e('e.g. last week, today, 30 mins ago', 'all-users-filter'); ?>">
                                            <button type="button" class="button remov_rel_date">X</button>
                                        </td>
                                    </tr>
                            <?php
                                endfor;
                            endif;
                            ?>
                        </tbody>
                    </table>

                    <template id="allusfi_rel_date_copy_content">
                        <tr>
                            <td>
                                <input type="text" name="rdmq-ky[]"
                                    placeholder="<?php esc_attr_e('e.g. last_updated', 'all-users-filter'); ?>">
                            </td>
                            <td>
                                <span class="allusfi-op-label">BETWEEN</span>
                                <input type="hidden" name="rdmq-op[]" value="BETWEEN">
                            </td>
                            <td>
                                <select name="rdmq-tp[]">
                                    <?php foreach ($rd_allowed_types_ui as $single_rdtp): ?>
                                        <option value="<?php echo esc_attr($single_rdtp); ?>">
                                            <?php echo esc_html($single_rdtp); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="rdmq-vl[]"
                                    placeholder="<?php esc_attr_e('e.g. last week, today, 30 mins ago', 'all-users-filter'); ?>">
                                <button type="button" class="button remov_rel_date">X</button>
                            </td>
                        </tr>
                    </template>
                </div>
            </div>
            <!-- tab content of meta date filter ends -->

            <?php if (class_exists('WooCommerce')): ?>
                <!-- tab content of woocommerce setting starts -->
                <div id="allusfi-woocommerce-settings" class="allusfi-tabcontent allusfi_us_woocommerce"
                    style="display:none;">
                    <div class="stng-title">
                        <h2><?php esc_html_e("WooCommerce Order Filter", 'all-users-filter'); ?></h2>
                    </div>
                    <div class="parent-col">
                        <div class="child-col" style="width:100%;">
                            <div class="form-field pad-top-40">
                                <label><b><?php esc_html_e("Enable Order Count Filter:", 'all-users-filter') ?></b></label>
                                <div class="pad-top-10">
                                    <label class="allusfi-wc-toggle">
                                        <input type="checkbox" name="wc-ordr-enabled" value="1" id="allusfi_wc_toggle" <?php checked($params['wc_order_enabled'], true); ?>>
                                        <span class="allusfi-wc-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-field pad-top-40 allusfi-wc-fields" <?php echo !$params['wc_order_enabled'] ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
                                <label><b><?php esc_html_e("Comparison Operator:", 'all-users-filter') ?></b></label>
                                <div class="pad-top-10">
                                    <select name="wc-ordr-op">
                                        <?php
                                        $wc_ops = array('>' => 'Greater than (>)', '<' => 'Less than (<)', '=' => 'Equal to (=)', '!=' => 'Not equal to (!=)');
                                        foreach ($wc_ops as $op_val => $op_label): ?>
                                            <option value="<?php echo esc_attr($op_val); ?>" <?php selected($params['wc_order_op'], $op_val); ?>><?php echo esc_html($op_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-field pad-top-40 allusfi-wc-fields" <?php echo !$params['wc_order_enabled'] ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
                                <label><b><?php esc_html_e("Order Count:", 'all-users-filter') ?></b></label>
                                <div class="tooltip"> ?
                                    <span
                                        class="tooltiptext"><?php esc_html_e('Enter the number of orders to compare against. Only completed and processing orders are counted.', 'all-users-filter'); ?></span>
                                </div>
                                <div class="pad-top-10">
                                    <input type="number" name="wc-ordr-cnt" min="0" step="1"
                                        value="<?php echo esc_attr($params['wc_order_count']); ?>"
                                        placeholder="<?php esc_attr_e('e.g. 5', 'all-users-filter'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- tab content of woocommerce setting ends -->
            <?php endif; ?>
            <!-- tab content of export setting starts -->
            <div id="allusfi-export-settings" class="allusfi-tabcontent allusfi_us_export" style="display:none;">

                <!-- ── 1. CSV Delimiter ── -->
                <div class="allusfi-export-section">
                    <h3 class="allusfi-export-section-title"><?php esc_html_e('CSV Separator / Delimiter', 'all-users-filter'); ?></h3>
                    <div class="form-field">
                        <label for="allusfi_csv_sep"><b><?php esc_html_e('Delimiter character:', 'all-users-filter'); ?></b></label>
                        <div class="tooltip"> ?
                            <span class="tooltiptext"><?php esc_html_e('Character used to separate columns in the exported CSV file. Default is a comma (,). Common alternatives: semicolon (;) or tab (\t).', 'all-users-filter'); ?></span>
                        </div>
                        <div class="pad-top-10">
                            <input type="text"
                                id="allusfi_csv_sep"
                                name="allusfi_csv_sep"
                                value=","
                                maxlength="3"
                                style="width:60px;text-align:center;font-size:1.1em;"
                                placeholder=",">
                        </div>
                    </div>
                </div>

                <!-- ── 2. Column Selection ── -->
                <div class="allusfi-export-section">
                    <h3 class="allusfi-export-section-title"><?php esc_html_e('Select Columns to Export', 'all-users-filter'); ?></h3>
                    <p class="description"><?php esc_html_e('Choose which columns to include in the CSV. All are selected by default.', 'all-users-filter'); ?></p>
                    <?php
                    $allusfi_std_cols = array(
                        'user_id'           => __('User ID', 'all-users-filter'),
                        'user_login'        => __('User Login', 'all-users-filter'),
                        'user_email'        => __('User Email', 'all-users-filter'),
                        'user_nicename'     => __('User Nicename', 'all-users-filter'),
                        'display_name'      => __('Display Name', 'all-users-filter'),
                        'user_role'         => __('User Role', 'all-users-filter'),
                        'user_registered'   => __('Registration Date', 'all-users-filter'),
                        'first_name'        => __('First Name', 'all-users-filter'),
                        'last_name'         => __('Last Name', 'all-users-filter'),
                    );
                    ?>
                    <div class="allusfi-col-checkboxes pad-top-10">
                        <?php foreach ($allusfi_std_cols as $col_slug => $col_label): ?>
                            <label class="fancy-check allusfi-export-col-label">
                                <input type="checkbox"
                                    name="allusfi_export_cols[]"
                                    value="<?php echo esc_attr($col_slug); ?>"
                                    checked="checked">
                                <?php echo esc_html($col_label); ?>
                                <span class="fancy-checkmark button"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── 3. Meta Field Columns ── -->
                <div class="allusfi-export-section">
                    <h3 class="allusfi-export-section-title"><?php esc_html_e('Include User Meta Fields', 'all-users-filter'); ?></h3>
                    <label class="fancy-check">
                        <input type="checkbox" id="allusfi_include_meta_toggle" name="allusfi_include_meta" value="1">
                        <?php esc_html_e('Include meta field columns', 'all-users-filter'); ?>
                        <span class="fancy-checkmark button"></span>
                    </label>

                    <div id="allusfi_meta_export_wrap" style="display:none;margin-top:12px;">
                        <?php
                        // Advanced-tab meta keys.
                        $active_meta_keys = !empty($params['meta_keys']) && is_array($params['meta_keys'])
                            ? array_values(array_filter(array_unique($params['meta_keys'])))
                            : array();

                        // Rel-date tab meta keys — surface them here too.
                        $active_rd_keys = !empty($params['rel_date_keys']) && is_array($params['rel_date_keys'])
                            ? array_values(array_filter(array_unique($params['rel_date_keys'])))
                            : array();

                        // Merge both sources, de-duplicate.
                        $all_active_meta_keys = array_values(array_unique(array_merge($active_meta_keys, $active_rd_keys)));
                        ?>

                        <?php if (!empty($all_active_meta_keys)): ?>
                            <p class="description"><b><?php esc_html_e('Active meta filter keys (Advanced & Meta Date Filter tabs):', 'all-users-filter'); ?></b></p>
                            <div class="allusfi-col-checkboxes pad-top-10">
                                <?php foreach ($all_active_meta_keys as $amk): ?>
                                    <label class="fancy-check allusfi-export-col-label">
                                        <input type="checkbox"
                                            name="allusfi_export_meta_keys[]"
                                            value="<?php echo esc_attr($amk); ?>"
                                            checked="checked">
                                        <?php echo esc_html($amk); ?>
                                        <span class="fancy-checkmark button"></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="description allusfi-no-meta-note">
                                <?php esc_html_e('No active meta filters. Use the Advanced or Meta Date Filter tab to add filters whose keys will appear here as selectable columns.', 'all-users-filter'); ?>
                            </p>
                        <?php endif; ?>

                        <div class="pad-top-10">
                            <label><b><?php esc_html_e('Additional Meta Key Columns:', 'all-users-filter'); ?></b></label>
                            <div class="tooltip"> ?
                                <span class="tooltiptext"><?php esc_html_e('Add one or more extra user meta keys to include as additional columns in the exported CSV. Each key becomes its own column.', 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <button type="button" class="button button-primary" id="allusfi_add_extra_meta_btn">
                                    <?php esc_html_e('Add Meta Key', 'all-users-filter'); ?>
                                </button>
                            </div>
                            <table class="meta_filter_table allusfi_meta_append_content" style="margin-top:10px;">
                                <tbody id="allusfi_extra_meta_rows">
                                    <tr>
                                        <th><?php esc_html_e('Meta Key', 'all-users-filter'); ?></th>
                                        <th><?php esc_html_e('Action', 'all-users-filter'); ?></th>
                                    </tr>
                                </tbody>
                            </table>
                            <template id="allusfi_extra_meta_row_tpl">
                                <tr>
                                    <td>
                                        <input type="text"
                                            name="allusfi_export_extra_meta[]"
                                            placeholder="<?php esc_attr_e('e.g. billing_phone', 'all-users-filter'); ?>"
                                            style="width:220px;">
                                    </td>
                                    <td>
                                        <button type="button" class="button allusfi_remov_extra_meta">X</button>
                                    </td>
                                </tr>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ── 4. Export Button & Progress ── -->
                <div class="allusfi-export-section txt-center export-btn">
                    <p class="big_p_bold">
                        <b><?php esc_html_e('The export file will include the columns selected above.', 'all-users-filter'); ?></b>
                    </p>
                    <p class="big_p">
                        <?php esc_html_e('Tip: switch to the Advanced tab to add meta filters whose values will appear as selectable meta columns here.', 'all-users-filter'); ?>
                        <?php if (class_exists('WooCommerce')): ?>
                            <br><mark><?php esc_html_e("WooCommerce: If order count filter is enabled, a 'Total Orders' column will be appended.", 'all-users-filter'); ?></mark>
                        <?php endif; ?>
                    </p>
                    <br>
                    <button id="allusfi_EXP-csv-BTN" class="button glow-on-hover" type="button">
                        <?php esc_html_e("CLICK HERE TO EXPORT CSV", 'all-users-filter'); ?>
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <div id="allusfi_export_progress" style="margin-top:15px;">
                        <div id="allusfi_export_progress_text" style="margin-bottom:5px;font-weight:bold;"></div>
                        <div style="background:#eee;width:100%;height:18px;border-radius:4px;overflow:hidden;">
                            <div id="allusfi_export_progress_bar"
                                style="background:#4caf50;width:0%;height:100%;transition:width 0.4s;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- tab content of export setting ends -->

            <!-- tab content of saved filters starts -->
            <div id="allusfi-saved-filters" class="allusfi-tabcontent allusfi_us_saved_filters" style="display:none;">
                <div class="stng-title">
                    <h2><?php esc_html_e("Saved Filters", 'all-users-filter'); ?></h2>
                </div>

                <!-- Save current filter form (only shown when allusfi_secure is set) -->
                <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only controls visibility of the Save-filter button; the nonce value itself is verified in the AJAX handler when the form is submitted. ?>
                <?php if (isset($_GET['allusfi_secure'])): ?>
                    <div class="allusfi-sf-save-wrap">
                        <button type="button" id="allusfi_sf_show_save_form" class="button button-primary">
                            <span class="dashicons dashicons-cloud-upload"></span>
                            <?php esc_html_e("Save Current Filter", 'all-users-filter'); ?>
                        </button>
                        <div id="allusfi_sf_name_wrap" class="allusfi-sf-name-wrap" style="display:none;">
                            <input type="text" id="allusfi_sf_name_input"
                                placeholder="<?php esc_attr_e('Enter filter name...', 'all-users-filter'); ?>"
                                maxlength="100">
                            <button type="button" id="allusfi_sf_confirm_save" class="button button-primary">
                                <?php esc_html_e("Confirm Save", 'all-users-filter'); ?>
                            </button>
                            <button type="button" id="allusfi_sf_cancel_save" class="button">
                                <?php esc_html_e("Cancel", 'all-users-filter'); ?>
                            </button>
                            <span id="allusfi_sf_save_msg" class="allusfi-sf-msg"></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Saved filters list (rendered by JS) -->
                <div id="allusfi_sf_list_wrap" class="allusfi-sf-list-wrap">
                    <div id="allusfi_sf_list"></div>
                </div>
            </div>
            <!-- tab content of saved filters ends -->

            <div class="pop-up-footer">
                <div style="display: inline-block;">
                    <p> Made with <span class="dashicons dashicons-heart"></span> By <a target="_blank" style="color: #5dacec;"
                            href="https://www.instagram.com/hlakkad/">Hardik Patel(Lakkad)</a> </p>
                    <p> Need more plugins customiation? <a
                            href="https://www.linkedin.com/in/hardik-patel-lakkad-097b12147/" target="_blank"
                            style="color: #5dacec;">Contact me</a> &#128104;&#8205;&#128187;</p>
                </div>
                <div class="txt-right allusfi-sbmit-actions">
                    <?php wp_nonce_field('allusfi_secure', 'allusfi_secure') ?>
                    <button class="button button-primary" type="submit" name="fltr-sbmt"
                        value="1"><?php esc_html_e("Filter Users", 'all-users-filter'); ?></button>
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
