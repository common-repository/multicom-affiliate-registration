<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM_Admin_API
{

	/**
	 * Constructor function
	 */
	public function __construct()
	{
		add_action('save_post', array($this, 'save_meta_boxes'), 10, 1);
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param array $data
	 * @param object $post
	 * @param boolean $echo
	 * @return void
	 */
	public function display_field($data = array(), $post = false, $echo = true)
	{
		// Get field info
		if (isset($data['field'])) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		$html = '';
		$have_error = false;

		if (isset($field['have_error'])) {
			$have_error = true;
			$html .= '<span class="update-nag notice notice-error">' . $field['have_error'] . '</span>';
		}

		// Get field show media
		if (isset($data['media_buttons'])) {
			$show_media = $data['media_buttons'];
		} else {
			$show_media = true;
		}

		// Check for prefix on option name
		$option_name = '';
		if (isset($data['prefix'])) {
			$option_name = $data['prefix'];
		}

		// Get saved data
		$data = '';
		if ($post) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta($post->ID, $field['id'], true);

			// Get data to display in field
			if (isset($option)) {
				$data = $option;
			}
		} else {

			// Get saved option
			$option_name .= $field['id'];
			$option = get_option($option_name);

			// Get data to display in field
			if (isset($option)) {
				$data = $option;
			}
		}

		// Show default data if no option saved and default is supplied
		if ($data === false && isset($field['default'])) {
			$data = $field['default'];
		} elseif ($data === false) {
			$data = '';
		}

		if (!$have_error) {
			switch ($field['type']) {

				case 'text':
				case 'url':
				case 'email':
					$html .= '<input id="' . esc_attr($field['id']) . '" type="text" name="' . esc_attr($option_name) . '" placeholder="' . (isset($field['placeholder']) && !empty($field['placeholder']) ? esc_attr($field['placeholder']) : '') . '"' . (isset($field['size']) ? (' size="' . $field['size'] . '"') : '') . ' value="' . esc_attr($data) . '" />';
					break;

				case 'password':
				case 'number':
				case 'hidden':
					$min = '';
					if (isset($field['min'])) {
						$min = ' min="' . esc_attr($field['min']) . '"';
					}

					$max = '';
					if (isset($field['max'])) {
						$max = ' max="' . esc_attr($field['max']) . '"';
					}
					$html .= '<input id="' . esc_attr($field['id']) . '" type="' . esc_attr($field['type']) . '" name="' . esc_attr($option_name) . '" placeholder="' . (isset($field['placeholder']) && !empty($field['placeholder']) ? esc_attr($field['placeholder']) : '') . '" value="' . esc_attr($data) . '"' . $min . '' . $max . '/>';
					break;

				case 'text_secret':
					$html .= '<input id="' . esc_attr($field['id']) . '" type="text" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($field['placeholder']) . '" value="" />';
					break;

				case 'textarea':
					$html .= '<textarea id="' . esc_attr($field['id']) . '" rows="5" cols="50" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($field['placeholder']) . '">' . $data . '</textarea>';
					break;

				case 'checkbox':
					$checked = '';
					if ($data && 'on' == $data) {
						$checked = 'checked="checked"';
					}
					$html .= '<input id="' . esc_attr($field['id']) . '" type="' . esc_attr($field['type']) . '" name="' . esc_attr($option_name) . '" ' . $checked . '/>';
					break;

				case 'checkbox_multi':
					foreach ($field['options'] as $k => $v) {
						$checked = false;
						if (in_array($k, (array) $data)) {
							$checked = true;
						}
						$html .= '<p><label for="' . esc_attr($field['id'] . '_' . $k) . '" class="checkbox_multi"><input type="checkbox" ' . checked($checked, true, false) . ' name="' . esc_attr($option_name) . '[]" value="' . esc_attr($k) . '" id="' . esc_attr($field['id'] . '_' . $k) . '" /> ' . $v . '</label></p> ';
					}
					break;

				case 'radio':
					foreach ($field['options'] as $k => $v) {
						$checked = false;
						if ($k == $data) {
							$checked = true;
						}
						$html .= '<label for="' . esc_attr($field['id'] . '_' . $k) . '"><input type="radio" ' . checked($checked, true, false) . ' name="' . esc_attr($option_name) . '" value="' . esc_attr($k) . '" id="' . esc_attr($field['id'] . '_' . $k) . '" /> ' . $v . '</label> ';
					}
					break;

				case 'select':
					$html .= '<select name="' . esc_attr($option_name) . '" id="' . esc_attr($field['id']) . '">';
					foreach ($field['options'] as $k => $v) {
						$selected = false;
						if ($k == $data) {
							$selected = true;
						}
						$html .= '<option ' . selected($selected, true, false) . ' value="' . esc_attr($k) . '">' . $v . '</option>';
					}
					$html .= '</select> ';
					break;

				case 'select_multi':
					$html .= '<select name="' . esc_attr($option_name) . '[]" id="' . esc_attr($field['id']) . '" multiple="multiple">';
					foreach ($field['options'] as $k => $v) {
						$selected = false;
						if (in_array($k, (array) $data)) {
							$selected = true;
						}
						$html .= '<option ' . selected($selected, true, false) . ' value="' . esc_attr($k) . '">' . $v . '</option>';
					}
					$html .= '</select> ';
					break;

				case 'image':
					$image_thumb = '';
					if ($data) {
						$image_thumb = wp_get_attachment_thumb_url($data);
					}
					$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>';
					$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __('Upload an image', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '" data-uploader_button_text="' . __('Use image', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '" class="image_upload_button button" value="' . __('Upload new image', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '" />';
					$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __('Remove image', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '" />';
					$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>';
					break;

				case 'color':
?><div class="color-picker" style="position:relative;">
						<input type="text" name="<?php esc_attr_e($option_name); ?>" class="color" value="<?php esc_attr_e($data); ?>" />
						<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>
					</div>
<?php
					break;

				case 'editor':
					wp_editor($data, $option_name, array(
						'textarea_name' => $option_name,
						'media_buttons' => $show_media,
					));
					break;

				case 'link':
					$html .= '<a id="' . esc_attr($field['id']) . '"  href="' . esc_attr($field['href']) . '" class="button-primary" name="' . esc_attr($option_name) . '" value="' . esc_attr($data) . '"  onclick="' . esc_attr($field['onclick']) . '" >' . $field['text'] . '</a>';
					break;

				case 'button':
					$html .= '<button id="' . esc_attr($field['id']) . '"  type="button" class="button-primary" name="' . esc_attr($option_name) . '" ' . (isset($field['onclick']) ? (' onclick="' . esc_attr($field['onclick']) . '"') : '') . '>' . $field['text'] . '</button>';
					break;
			}

			switch ($field['type']) {

				case 'checkbox_multi':
				case 'radio':
				case 'select_multi':
					$html .= '<br/><span class="description">' . $field['description'] . '</span>';
					break;

				default:
					if (!empty($field['description'])) {
						$html .= '<br/>';
						if (!$post) {
							$html .= '<label for="' . esc_attr($field['id']) . '">';
						}

						$html .= '<span class="description">' . $field['description'] . '</span>';

						if (!$post) {
							$html .= '</label>';
						}
					}
					break;
			}
		}

		if (!$echo) {
			return $html;
		}

		echo $html;
	}

	/**
	 * Validate form field
	 * @param  string $data Submitted value
	 * @param  string $type Type of field to validate
	 * @return string       Validated value
	 */
	public function validate_field($data = '', $type = 'text')
	{

		switch ($type) {
			case 'url':
				$data = esc_url_raw($data);
				break;
			case 'email':
				$data = sanitize_email($data);
				break;
			default:
				$data = sanitize_text_field($data);
				break;
		}

		return $data;
	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box($id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null)
	{

		// Get post type(s)
		if (!is_array($post_types)) {
			$post_types = array($post_types);
		}

		// Generate each metabox
		foreach ($post_types as $post_type) {
			add_meta_box($id, $title, array($this, 'meta_box_content'), $post_type, $context, $priority, $callback_args);
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content($post, $args)
	{

		$fields = apply_filters($post->post_type . '_custom_fields', array(), $post->post_type);

		if (!is_array($fields) || 0 == count($fields)) return;

		echo '<div class="custom-field-panel">';

		foreach ($fields as $field) {

			if (!isset($field['metabox'])) continue;

			if (!is_array($field['metabox'])) {
				$field['metabox'] = array($field['metabox']);
			}

			if (in_array($args['id'], $field['metabox'])) {
				$this->display_meta_box_field($field, $post);
			}
		}

		echo '</div>';
	}

	/**
	 * Dispay field in metabox
	 * @param  array  $field Field data
	 * @param  object $post  Post object
	 * @return void
	 */
	public function display_meta_box_field($field = array(), $post)
	{

		if (!is_array($field) || 0 == count($field)) return;

		$field = '<p class="form-field"><label for="' . $field['id'] . '">' . $field['label'] . '</label>'
			. $this->display_field($field, $post, false) . '</p>';

		echo $field;
	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes($post_id = 0)
	{

		if (!$post_id) return;

		$post_type = get_post_type($post_id);

		$fields = apply_filters($post_type . '_custom_fields', array(), $post_type);

		if (!is_array($fields) || 0 == count($fields)) return;

		foreach ($fields as $field) {
			if (isset($_REQUEST[$field['id']])) {
				update_post_meta($post_id, $field['id'], $this->validate_field($_REQUEST[$field['id']], $field['type']));
			} else {
				update_post_meta($post_id, $field['id'], '');
			}
		}
	}
}
