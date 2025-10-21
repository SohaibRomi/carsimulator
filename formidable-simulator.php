
<?php
/*
Plugin Name: Formidable Simulator Add-on
Description: Adds Canvas Background and Simulator Layer fields to Formidable Forms for creating interactive image simulators.
Version: 1.6
Author: xAI
Plugin URI: https://example.com
Text Domain: frm-sim
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('FRM_SIM_PATH', plugin_dir_path(__FILE__));
define('FRM_SIM_URL', plugin_dir_url(__FILE__));

// Register field types
add_filter('frm_pro_available_fields', 'frm_sim_add_fields');
function frm_sim_add_fields($fields) {
    $fields['canvas_background'] = array(
        'name' => 'Canvas Background',
        'icon' => 'frm_icon_font frm_image_options_icon',
    );
    $fields['simulator_layer'] = array(
        'name' => 'Simulator Layer',
        'icon' => 'frm_icon_font frm_layers_icon',
    );
    return $fields;
}

// Treat canvas as divider for nesting
add_filter('frm_is_field_divider', 'frm_sim_is_divider', 10, 2);
function frm_sim_is_divider($is_divider, $field) {
    if ($field['type'] == 'canvas_background') {
        return true;
    }
    return $is_divider;
}

// Add section class to canvas container
add_filter('frm_field_div_classes', 'frm_sim_field_div_classes', 10, 2);
function frm_sim_field_div_classes($classes, $field) {
    if ($field['type'] == 'canvas_background') {
        // Treat as a section and add a unique marker class used by our JS
        $classes .= ' frm_section_heading simulator-canvas-field';
    } elseif ($field['type'] == 'simulator_layer') {
        // Hide label container spacing and add a unique marker class
        $classes .= ' frm_none_container simulator-layer-field';
    }
    return $classes;
}

// Hide labels for layers
add_filter('frm_get_label_position', 'frm_sim_label_position', 10, 3);
function frm_sim_label_position($position, $field, $form) {
    if ($field['type'] == 'simulator_layer') {
        return 'none';
    }
    return $position;
}

// Set default field options
add_filter('frm_before_field_created', 'frm_sim_set_defaults');
function frm_sim_set_defaults($field_data) {
    if ($field_data['type'] == 'canvas_background') {
        $field_data['name'] = __('Canvas Background', 'frm-sim');
        $defaults = array(
            'background_image' => '',
            'width' => '600',
            'height' => '400',
        );
        $field_data['field_options'] = array_merge($field_data['field_options'] ?? [], $defaults);
    } elseif ($field_data['type'] == 'simulator_layer') {
        $field_data['name'] = __('Simulator Layer', 'frm-sim');
        $defaults = array(
            'layer_image' => '',
        );
        $field_data['field_options'] = array_merge($field_data['field_options'] ?? [], $defaults);
    }
    return $field_data;
}

// Add custom options to field settings in admin
add_action('frm_field_options_form', 'frm_sim_add_options_ui', 10, 3);
function frm_sim_add_options_ui($field, $display, $values) {
    $field_options = $field['field_options'] ?? [];
    if ($field['type'] == 'canvas_background') {
        $bg_id = isset($field_options['background_image']) ? $field_options['background_image'] : '';
        $bg_url = $bg_id ? wp_get_attachment_url($bg_id) : '';
        ?>
        <tr>
            <td><label><?php _e('Background Image', 'frm-sim'); ?></label></td>
            <td>
                <input type="hidden" name="field_options[background_image_<?php echo esc_attr($field['id']); ?>]" id="background_image_<?php echo esc_attr($field['id']); ?>" value="<?php echo esc_attr($bg_id); ?>">
                <?php if ($bg_url): ?>
                    <img id="bg-preview-<?php echo esc_attr($field['id']); ?>" src="<?php echo esc_attr($bg_url); ?>" style="max-width:200px; display:block; margin-bottom:10px;">
                <?php endif; ?>
                <button type="button" class="button frm_sim_upload_button" data-field-id="<?php echo esc_attr($field['id']); ?>" data-upload-type="background"><?php _e('Upload Image', 'frm-sim'); ?></button>
            </td>
        </tr>
        <tr>
            <td><label><?php _e('Width (px)', 'frm-sim'); ?></label></td>
            <td><input type="text" name="field_options[width_<?php echo esc_attr($field['id']); ?>]" value="<?php echo esc_attr($field_options['width'] ?? '600'); ?>"></td>
        </tr>
        <tr>
            <td><label><?php _e('Height (px)', 'frm-sim'); ?></label></td>
            <td><input type="text" name="field_options[height_<?php echo esc_attr($field['id']); ?>]" value="<?php echo esc_attr($field_options['height'] ?? '400'); ?>"></td>
        </tr>
        <?php
    } elseif ($field['type'] == 'simulator_layer') {
        $layer_id = isset($field_options['layer_image']) ? $field_options['layer_image'] : '';
        $layer_url = $layer_id ? wp_get_attachment_url($layer_id) : '';
        ?>
        <tr>
            <td><label><?php _e('Layer Image', 'frm-sim'); ?></label></td>
            <td>
                <input type="hidden" name="field_options[layer_image_<?php echo esc_attr($field['id']); ?>]" id="layer_image_<?php echo esc_attr($field['id']); ?>" value="<?php echo esc_attr($layer_id); ?>">
                <?php if ($layer_url): ?>
                    <img id="layer-preview-<?php echo esc_attr($field['id']); ?>" src="<?php echo esc_attr($layer_url); ?>" style="max-width:200px; display:block; margin-bottom:10px;">
                <?php endif; ?>
                <button type="button" class="button frm_sim_upload_button" data-field-id="<?php echo esc_attr($field['id']); ?>" data-upload-type="layer"><?php _e('Upload Transparent Image', 'frm-sim'); ?></button>
            </td>
        </tr>
        <?php
    }
}

// Update field options on save
add_filter('frm_update_field_options', 'frm_sim_update_options', 10, 3);
function frm_sim_update_options($field_options, $field, $values) {
    if ($field->type == 'canvas_background') {
        $field_options['background_image'] = isset($values['field_options']['background_image_' . $field->id]) ? intval($values['field_options']['background_image_' . $field->id]) : '';
        $field_options['width'] = isset($values['field_options']['width_' . $field->id]) ? sanitize_text_field($values['field_options']['width_' . $field->id]) : '600';
        $field_options['height'] = isset($values['field_options']['height_' . $field->id]) ? sanitize_text_field($values['field_options']['height_' . $field->id]) : '400';
    } elseif ($field->type == 'simulator_layer') {
        $field_options['layer_image'] = isset($values['field_options']['layer_image_' . $field->id]) ? intval($values['field_options']['layer_image_' . $field->id]) : '';
    }
    return $field_options;
}

// Show placeholder in form builder
add_action('frm_display_added_fields', 'frm_sim_show_admin_field');
function frm_sim_show_admin_field($field) {
    if ($field['type'] == 'canvas_background') {
        ?>
        <h3 class="frm_pos_top frm_section_spacing"><?php echo esc_html($field['name']); ?></h3>
        <ul class="frm_sortable_field_list frm_clearfix" id="frm_field_list_<?php echo esc_attr($field['id']); ?>" style="margin: 10px 0; padding: 10px; border: 1px dashed #ccc; min-height: 50px;">
            <div class="howto button-secondary frm_html_field" style="background: #f0f0f0; padding: 10px; border: 1px solid #ddd; text-align: center;">
                <?php _e('Drag Simulator Layers or HTML fields here.', 'frm-sim'); ?>
            </div>
        </ul>
        <?php
    } elseif ($field['type'] == 'simulator_layer') {
        ?>
        <div class="frm_html_field_placeholder">
            <div class="howto button-secondary frm_html_field"><?php _e('Simulator Layer', 'frm-sim'); ?></div>
        </div>
        <?php
    }
}

// Render canvas on front-end
add_action('frm_form_fields', 'frm_sim_render_canvas', 10, 3);
function frm_sim_render_canvas($field, $field_name, $atts) {
    if ($field['type'] != 'canvas_background') {
        return;
    }
    // Fetch full field object
    $field_obj = FrmField::getOne($field['id']);
    if (!$field_obj || empty($field_obj->field_options)) {
        error_log('Formidable Simulator: Failed to load field options for Canvas Background field ID ' . $field['id']);
        echo '<div>' . esc_html__('Error: Canvas Background field options not found.', 'frm-sim') . '</div>';
        return;
    }
    $field_options = $field_obj->field_options;
    $html_id = $atts['html_id'];
    $bg_id = isset($field_options['background_image']) ? intval($field_options['background_image']) : 0;
    $bg_url = $bg_id ? wp_get_attachment_image_src($bg_id, 'full')[0] : '';
    $width = isset($field_options['width']) ? absint($field_options['width']) : 600;
    $height = isset($field_options['height']) ? absint($field_options['height']) : 400;
    if (!$bg_url) {
        error_log('Formidable Simulator: No background image set for field ID ' . $field['id']);
        echo '<div>' . esc_html__('No background image set.', 'frm-sim') . '</div>';
        return;
    }
    $aspect = ($height / $width) * 100;
    ?>
    <div class="simulator-canvas-wrapper" style="max-width: <?php echo esc_attr($width); ?>px; width: 100%;">
        <div id="<?php echo esc_attr($html_id); ?>" class="simulator-canvas" style="position: relative; padding-bottom: <?php echo esc_attr($aspect); ?>%;">
            <img src="<?php echo esc_attr($bg_url); ?>" alt="Background" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
        </div>
    </div>
    <input type="hidden" name="<?php echo esc_attr($field_name); ?>" id="merged_<?php echo esc_attr($html_id); ?>" value="">
    <?php
}

// Render layer on front-end
add_action('frm_form_fields', 'frm_sim_render_layer', 10, 3);
function frm_sim_render_layer($field, $field_name, $atts) {
    if ($field['type'] != 'simulator_layer') {
        return;
    }
    // Fetch full field object
    $field_obj = FrmField::getOne($field['id']);
    if (!$field_obj || empty($field_obj->field_options)) {
        error_log('Formidable Simulator: Failed to load field options for Simulator Layer field ID ' . $field['id']);
        return;
    }
    $field_options = $field_obj->field_options;
    $html_id = $atts['html_id'];
    $layer_id = isset($field_options['layer_image']) ? intval($field_options['layer_image']) : 0;
    $layer_url = $layer_id ? wp_get_attachment_image_src($layer_id, 'full')[0] : '';
    if (!$layer_url) {
        error_log('Formidable Simulator: No layer image set for field ID ' . $field['id']);
        return;
    }
    ?>
    <img class="simulator-layer-img" src="<?php echo esc_attr($layer_url); ?>" alt="Layer" data-layer-id="<?php echo esc_attr($html_id); ?>">
    <?php
}

// Process merged image on submission
add_filter('frm_pre_create_entry', 'frm_sim_process_merged');
function frm_sim_process_merged($values) {
    foreach ($values['item_meta'] as $field_id => &$val) {
        $field = FrmField::getOne($field_id);
        if (!$field || $field->type != 'canvas_background' || strpos($val, 'data:image') !== 0) {
            continue;
        }
        $base64 = str_replace('data:image/png;base64,', '', $val);
        $data = base64_decode($base64);
        if (!$data) {
            error_log('Formidable Simulator: Failed to decode base64 for field ID ' . $field_id);
            continue;
        }
        $upload_dir = wp_upload_dir();
        $filename = 'merged_' . sanitize_title($field->name) . '_' . time() . '.png';
        $file_path = $upload_dir['path'] . '/' . $filename;
        if (!file_put_contents($file_path, $data)) {
            error_log('Formidable Simulator: Failed to save merged image file for field ID ' . $field_id);
            continue;
        }
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title' => $filename,
            'post_content' => '',
            'post_status' => 'inherit',
        );
        $attach_id = wp_insert_attachment($attachment, $file_path);
        if (is_wp_error($attach_id)) {
            error_log('Formidable Simulator: Failed to insert attachment for field ID ' . $field_id . ': ' . $attach_id->get_error_message());
            continue;
        }
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        $val = $attach_id;
    }
    return $values;
}

// Display merged image in entries
add_filter('frm_display_value', 'frm_sim_display_merged', 10, 3);
function frm_sim_display_merged($value, $field, $atts) {
    if ($field->type == 'canvas_background' && is_numeric($value)) {
        $value = wp_get_attachment_image($value, 'full');
    }
    return $value;
}

// Enqueue assets
add_action('wp_enqueue_scripts', 'frm_sim_enqueue_frontend');
function frm_sim_enqueue_frontend() {
    if (!class_exists('FrmAppHelper')) {
        return;
    }
    wp_enqueue_script('frm-sim-js', FRM_SIM_URL . 'js/simulator.js', array('jquery'), '1.6', true);
    wp_enqueue_style('frm-sim-css', FRM_SIM_URL . 'css/simulator.css', array(), '1.6');
}

add_action('admin_enqueue_scripts', 'frm_sim_enqueue_admin');
function frm_sim_enqueue_admin($hook) {
    if ($hook !== 'toplevel_page_formidable' && (!isset($_GET['frm_action']) || $_GET['frm_action'] !== 'edit')) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('frm-sim-builder-js', FRM_SIM_URL . 'js/builder.js', array('jquery', 'jquery-ui-sortable'), '1.6', true);
    wp_enqueue_script('frm-sim-admin-js', FRM_SIM_URL . 'js/admin.js', array('jquery'), '1.6', true);
}
