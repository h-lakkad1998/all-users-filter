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

if ( ! defined( 'ABSPATH' ) ) {
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
	'exclude_roles'    => array(),
	'excl_ids'        => array(),
	'multi_from_date' => array(),
	'multi_to_date'   => array(),
	'meta_keys'       => array(),
	'meta_vals'       => array(),
	'meta_ops'        => array(),
	'meta_tp'         => array(),
);

if ( class_exists( 'ALLUSFI_Admin' ) ) {
	$admin  = new ALLUSFI_Admin();
	$params = (array) $admin->allusfi_get_query_params();
}

/* Roles for checkboxes */
$roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->get_names() : array();

/* Simple compatible compares list for select options */
$allusfi_html_compatible_compares = array( '=', '!=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=', 'NOT EXISTS', 'NOT REGEXP' );

?>
<div class="alignleft actions">
    <button id="allusfi_pop_up_btn" class="button allusfi_animated-btn" type="button"><?php esc_html_e("Filter Users", 'all-users-filter'); ?>
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
                <button type="button" class="tablinks set-active" data-id="allusfi-general-settings"><?php esc_html_e("General", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="allusfi-date-filter-settings"><?php esc_html_e("Registered Date Filter", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="allusfi-advanced-settings"><?php esc_html_e("Advanced(Meta filter)", 'all-users-filter'); ?></button>
                <button type="button" class="tablinks" data-id="allusfi-export-settings"><?php esc_html_e("Export", 'all-users-filter'); ?></button>
                <a href="<?php echo esc_url($pagenow, 'all-users-filter'); ?>" class="button button-primary clear_filters"><?php esc_html_e("Clear Filters", 'all-users-filter'); ?></a>
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
                                <option value="" <?php selected( $params['usr_sort'], '' ); ?> ><?php esc_html_e("Select option...", 'all-users-filter'); ?></option>
                                <option value="f-nm" <?php selected( $params['usr_sort'], 'f-nm' ); ?>><?php esc_html_e("First name", 'all-users-filter'); ?></option>
                                <option value="l-nm" <?php selected( $params['usr_sort'], 'l-nm' ); ?>><?php esc_html_e("Last name", 'all-users-filter'); ?></option>
                                <option value="usr-id" <?php selected( $params['usr_sort'], 'usr-id' ); ?>><?php esc_html_e("User ID", 'all-users-filter'); ?></option>
                                <option value="usr-lgn" <?php selected( $params['usr_sort'], 'usr-lgn' ); ?>><?php esc_html_e("User Login", 'all-users-filter'); ?></option>
                                <option value="dis-nm" <?php selected( $params['usr_sort'], 'dis-nm' ); ?>><?php esc_html_e("Display Name", 'all-users-filter'); ?></option>
                                <option value="reg-dt" <?php selected( $params['usr_sort'], 'reg-dt' ); ?>><?php esc_html_e("Registered Date", 'all-users-filter'); ?></option>
                                <option value="pst-cnt" <?php selected( $params['usr_sort'], 'pst-cnt' ); ?>><?php esc_html_e("Post Count", 'all-users-filter'); ?></option>
                            </select>
                        </div>
                        <div class="form-field pad-top-40 ordr-by-fields">
                            <label><b><?php esc_html_e("Order By:", 'all-users-filter'); ?></b></label>
                            <div class="form-order">
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="1" <?php checked( $params['ordr_by'], '1' ); ?> ><?php esc_html_e("ASCENDING", 'all-users-filter'); ?>
                                    <span class="fancy-select button-primary"></span>
                                </label> <br> <br>
                                <label class="fancy-radio">
                                    <input type="radio" name="ordr-by" value="0" <?php checked( $params['ordr_by'], '0' ); ?>><?php esc_html_e("DESCENDING", 'all-users-filter'); ?>
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
                                <input value="<?php echo esc_attr( ( ! empty( $params['excl_ids'] ) && is_array( $params['excl_ids'] ) ) ? implode( '-', $params['excl_ids'] ) : '' ); ?>" type="text" pattern="^[0-9\-]+$" name="excl-ids" placeholder="<?php esc_attr_e('Only numbers and "-" are allowed.', 'all-users-filter') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="child-col between-two-dates">
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e("Filter users by date of registration : ", 'all-users-filter') ?></b></label>
                            <div class="pad-top-10">
                                <input type="date" name="one-dt" value="<?php echo esc_attr( $params['one_date'] ); ?>">
                                <button type="button" class="button rst_single_dt"> <?php esc_html_e("Reset", 'all-users-filter'); ?> </button>
                            </div>
                        </div>
                        <div class="form-field  pad-top-40">
                            <label><b><?php esc_html_e('Write something like  "12 Hours ago" : ', 'all-users-filter') ?></b></label>
                            <div class="tooltip"> ?
                                <span class="tooltiptext"><?php echo wp_kses_post("Find registered users with <br/>(E.g. 12 hours ago, <br/> 1 month ago): ", 'all-users-filter'); ?></span>
                            </div>
                            <div class="pad-top-10">
                                <input placeholder="<?php esc_attr_e('E.g. 12 Hours ago', 'all-users-filter') ?>" type="text" name="cstm-dt" value="<?php echo esc_attr( $params['cstm_dt'] ); ?>" >
                            </div>
                        </div>
                    </div>
                    <div class="child-col">
                        <div class="form-field pad-top-40">
                            <label><b><?php esc_html_e("Exclude roles:", 'all-users-filter') ?></b></label>
                            <div class="exclude-roles pad-top-10">
                                <?php
                                if (! empty($roles)):
                                    foreach ($roles as $role_slug => $role_name) : ?>
                                        <label class="fancy-check">
                                            <input 
                                                type="checkbox" 
                                                name="rl-excld[]" 
                                                value="<?php echo esc_attr($role_slug); ?>" 
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
                            <span class="tooltiptext"><?php esc_html_e("This filter will filter users based on registration date.", 'all-users-filter'); ?></span>
                        </div>
                        <button class="click_to_append button button-primary add_multi_date" type="button" id="allusfi_add_multi_date"><?php esc_html_e("Add date", 'all-users-filter'); ?></button>
                    </div>
                    <table class="meta_filter_table allusfi_meta_append_content">
                        <tbody id="dt_append_content">
                            <tr>
                                <th><?php esc_html_e('From Date', 'all-users-filter'); ?></th>
                                <th><?php esc_html_e('To Date', 'all-users-filter'); ?></th>
                            </tr>
                            <?php
							if ( ! empty( $params['multi_from_date'] ) && is_array( $params['multi_from_date'] ) ) :
								foreach ( $params['multi_from_date'] as $index => $from ) :
									$to = isset( $params['multi_to_date'][ $index ] ) ? $params['multi_to_date'][ $index ] : '';
									?>
									<tr>
										<td><input type="date" name="mlt-f-dt[]" value="<?php echo esc_attr( $from ); ?>"></td>
										<td>
											<input type="date" name="mlt-t-dt[]" value="<?php echo esc_attr( $to ); ?>">
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
                                <span class="tooltiptext"><?php esc_html_e("1) Add meta key.  2) Select Operator.  3) Enter value. ", 'all-users-filter'); ?></span>
                            </div>
                            <button class="click_to_append button button-primary add_multi_meta_query" type="button" id="allusfi_add_meta_query"><?php esc_html_e("ADD META FILTER", 'all-users-filter'); ?></button>
                            <label class="relation"> Relation: </label>
                            <select name="rltn">
                                <option value="nd" <?php selected( $params['relation'], 'nd' ); ?>><?php esc_html_e("AND", 'all-users-filter'); ?></option>
                                <option value="or" <?php selected( $params['relation'], 'or' ); ?>><?php esc_html_e("OR", 'all-users-filter'); ?></option>
                            </select>
                            <template id="allusfi_meta_copy_content">
                                <tr>
                                    <td>
                                        <input type="text" name="mta-ky[]" placeholder="<?php esc_attr_e('Add meta key like: monthly_salary', 'all-users-filter'); ?>">
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
                                            $meta_count = max( 0, max( count( (array) $params['meta_keys'] ), count( (array) $params['meta_ops'] ), count( (array) $params['meta_tp'] ), count( (array) $params['meta_vals'] ) ) );

                                            if( $meta_count >=  1 ):
                                                for ( $i = 0; $i < $meta_count; $i++ ) :
                                                    $key   = isset( $params['meta_keys'][ $i ] ) ? $params['meta_keys'][ $i ] : '';
                                                    $op    = isset( $params['meta_ops'][ $i ] ) ? $params['meta_ops'][ $i ] : '=';
                                                    $tp    = isset( $params['meta_tp'][ $i ] ) ? $params['meta_tp'][ $i ] : 'CHAR';
                                                    $value = isset( $params['meta_vals'][ $i ] ) ? $params['meta_vals'][ $i ] : '';
                                                    ?>
                                                    <tr>
                                                        <td><input type="text" name="mta-ky[]" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'Add meta key like: monthly_salary', 'all-users-filter' ); ?>"></td>

                                                        <td>
                                                            <select name="mta-op[]">
                                                                <?php foreach ( $allusfi_html_compatible_compares as $single_op ) : ?>
                                                                    <option value="<?php echo esc_attr( $single_op ); ?>" <?php selected( $op, $single_op ); ?>><?php echo esc_html( $single_op ); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <select name="mta-tp[]">
                                                                <?php
                                                                $compatible_type = array( 'CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME' );
                                                                foreach ( $compatible_type as $single_tp ) :
                                                                    ?>
                                                                    <option value="<?php echo esc_attr( $single_tp ); ?>" <?php selected( $tp, $single_tp ); ?>><?php echo esc_html( $single_tp ); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <input type="text" name="mta-vl[]" value="<?php echo esc_attr( $value ); ?>">
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
            <!-- tab content of export setting starts -->
            <div id="allusfi-export-settings" class="allusfi-tabcontent allusfi_us_export" style="display:none;">
                <div class="txt-center export-btn">
                    <p class="big_p_bold"><b><?php esc_html_e("The export file will include following things.", 'all-users-filter'); ?></b></p>
                    <p class="big_p">
                        <?php esc_html_e("User ID, User Login, User Email, User Nicename, Display Name, User Role.", 'all-users-filter'); ?> <br>
                        <mark><?php esc_html_e("Note: If filtered with meta key/s(advance filter), meta value/s will be included.",  'all-users-filter'); ?> </mark>
                    </p>
                    <br>
                    <button id="allusfi_EXP-csv-BTN" class="button glow-on-hover" type="button">
                        <?php esc_html_e("CLICK HERE TO EXPORT CSV", 'all-users-filter'); ?>
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <div id="allusfi_export_progress" style="margin-top:15px;">
                        <div id="allusfi_export_progress_text" style="margin-bottom:5px;font-weight:bold;"></div>
                        <div style="background:#eee;width:100%;height:18px;border-radius:4px;overflow:hidden;">
                            <div id="allusfi_export_progress_bar" style="background:#4caf50;width:0%;height:100%;transition:width 0.4s;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- tab content of export setting ends -->
            <div class="pop-up-footer">
                <div style="display: inline-block;">
                    <p> Made with <span class="heart"></span> By <a target="_blank" style="color: #5dacec;" href="https://www.instagram.com/hlakkad/">Hardik Patel(Lakkad)</a> </p>
                    <p> Need more plugins customiation? <a href="https://www.linkedin.com/in/hardik-patel-lakkad-097b12147/" target="_blank" style="color: #5dacec;">Contact me</a> &#128104;&#8205;&#128187;</p>
                </div>
                <div class="txt-right allusfi-sbmit-actions">
                    <?php wp_nonce_field('allusfi_secure', 'allusfi_secure') ?>
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
