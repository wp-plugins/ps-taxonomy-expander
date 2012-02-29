<?php
/*
Plugin Name: PS Taxonomy Expander
Plugin URI: http://www.warna.info/archives/451/
Description: PS Taxonomy Expander makes easy to use categories, tags and custom taxonomies on editing posts.
Author: Hitoshi Omagari
Version: 1.0.0

*/


class PS_Taxonomy_Expander {

function PS_Taxonomy_Expander() {
	$this->__construct();
}


function __construct() {
	load_plugin_textdomain( 'ps-taxonomy-expander', false, plugin_basename( dirname( __FILE__ ) ) . '/language' );
	if ( is_admin() ) {
		add_action( 'admin_print_styles-post-new.php'	, array( &$this, 'add_style_hide_add_category' ) );
		add_action( 'admin_print_styles-post.php'		, array( &$this, 'add_style_hide_add_category' ) );
		add_action( 'admin_footer-post.php'				, array( &$this, 'replace_check2radio_taxonomy_scripts' ) );
		add_action( 'admin_footer-post-new.php'			, array( &$this, 'replace_check2radio_taxonomy_scripts' ) );
		add_action( 'admin_footer-edit.php'				, array( &$this, 'quick_replace_check2radio_taxonomy_scripts' ) );
		add_action( 'admin_head-edit.php'				, array( &$this, 'remove_inline_edit_post_js' ) );
		add_action( 'load-edit.php'						, array( &$this, 'add_sc_inline_edit_js' ) );
		add_action( 'load-options-writing.php'			, array( &$this, 'add_default_term_setting_item' ) );
		add_filter( 'whitelist_options'					, array( &$this, 'allow_default_term_setting' ) );
		add_action( 'load-options.php'					, array( &$this, 'check_single_taxonomies_postdata' ) );
		add_action( 'admin_menu'						, array( &$this, 'add_media_taxonomy_menu' ) );
		add_filter( 'attachment_fields_to_edit'			, array( &$this, 'replace_attachement_taxonomy_input_to_check' ), 100, 2 );
		add_action( 'load-media.php'					, array( &$this, 'join_media_taxonomy_datas' ) );
		add_action( 'right_now_content_table_end'		, array( &$this, 'display_taxonomy_post_count' ) );
		add_action( 'personal_options'					, array( &$this, 'add_taxonomy_count_dashboard_right_now_field' ) );
		add_action( 'profile_update'					, array( &$this, 'update_taxonomy_count_dashboard_right_now' ), 10, 2 );
	}
	add_action( 'wp_insert_post'	, array( &$this, 'add_post_type_default_term' ), 10, 2 );
	add_action( 'add_attachment'	, array( &$this, 'add_post_type_default_term' ) );
	add_action( 'edit_attachment'	, array( &$this, 'add_post_type_default_term' ) );
}


function replace_check2radio_taxonomy_scripts() {
	global $post;
	$single_taxonomies = get_option( 'single_taxonomies' );
	$taxonomies = get_object_taxonomies( $post->post_type, 'object' );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $label => $obj ) {
			if ( $obj->show_ui && $obj->hierarchical && $obj->public && in_array( $obj->name, $single_taxonomies ) ) {
				echo <<< EOF
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready(function($){
		$("#{$obj->name}checklist input[type=checkbox]").each(function(){
			\$check = $(this);
			var checked = \$check.attr("checked") ? ' checked="checked"' : '';
			var item = '<input type="radio" id="' + \$check.attr("id") + '" name="' + \$check.attr("name") + '"' + checked + ' value="' + \$check.val() + '"/>';
			\$check.replaceWith( item );
		});
		$("#{$obj->name}checklist-pop input[type=checkbox]").each(function(){
			\$check = $(this);
			var checked = \$check.attr("checked") ? ' checked="checked"' : '';
			var item = '<input type="radio" id="' + \$check.attr("id") + '" name="' + \$check.attr("name") + '"' + checked + ' value="' + \$check.val() + '"/>';
			\$check.replaceWith( item );
		});
	});
	//]]>
</script>
EOF;
			}
		}
	}
}


function quick_replace_check2radio_taxonomy_scripts() {
	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
	$single_taxonomies = get_option( 'single_taxonomies' );
	$taxonomies = get_object_taxonomies( $post_type, 'object' );
	if ( $taxonomies ) {
			foreach ( $taxonomies as $label => $obj ) {
			if ( $obj->show_ui && $obj->hierarchical && $obj->public && in_array( $obj->name, $single_taxonomies ) ) {
				echo <<< EOF
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready(function($){
		$(".{$obj->name}-checklist input[type=checkbox]").each(function(){
			\$check = $(this);
			var item = '<input type="radio" id="' + \$check.attr("id") + '" name="' + \$check.attr("name") + '" value="' + \$check.val() + '"/>';
			\$check.replaceWith( item );
		});
	});
	//]]>
</script>
EOF;
			}
		}
	}
}


function remove_inline_edit_post_js() {
	wp_deregister_script( 'inline-edit-post' );
}


function add_sc_inline_edit_js() {
	wp_enqueue_script( 'sc-inline-edit', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) .'/js/ps-inline-edit.js', array(), false, true );
}


function add_style_hide_add_category() {
	global $post;

	$single_taxonomies = get_option( 'single_taxonomies' );
	$taxonomies = get_object_taxonomies( $post->post_type, 'object' );
	if ( $taxonomies && is_array( $single_taxonomies ) ) {
?>
<style>
<!--
<?php
		foreach ( $taxonomies as $label => $obj ) {
			if ( $obj->show_ui && $obj->hierarchical && $obj->public && in_array( $obj->name, $single_taxonomies ) ) {
?>
#<?php echo esc_html( $label ); ?>-adder { display: none; }
<?php
			}
		}
?>
-->
</style>
<?php
	}
}


function add_default_term_setting_item() {
	$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), false );
	if ( $post_types ) {
		foreach ( $post_types as $post_type_slug => $post_type ) {
			$post_type_taxonomies = get_object_taxonomies( $post_type_slug, false );
			if ( $post_type_taxonomies ) {
				foreach ( $post_type_taxonomies as $tax_slug => $taxonomy ) {
					if ( ! ( $post_type_slug == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui ) {
						$post_type_label = $post_type->_builtin ? __( $post_type->labels->singular_name ) : $post_type->labels->singular_name;
						$taxonomy_label = $taxonomy->_builtin ? __( $taxonomy->labels->singular_name ) : $taxonomy->labels->singular_name;
						add_settings_field( $post_type_slug . '_default_' . $tax_slug, sprintf( __( 'Default %s %s', 'ps-taxonomy-expander' ), $post_type_label, $taxonomy_label ), array( &$this, 'default_term_setting_field' ), 'writing', 'default', array( 'post_type' => $post_type_slug, 'taxonomy' => $taxonomy ) );
					}
				}
			}
		}
	}
	$media_taxonomies = get_object_taxonomies( 'attachment', false );
	if ( count( $media_taxonomies ) ) {
		foreach ( $media_taxonomies as $tax_slug => $taxonomy ) {
			if ( $taxonomy->show_ui ) {
				add_settings_field( 'attachment_default_' . $tax_slug, sprintf( __( 'Default Media %s', 'ps-taxonomy-expander' ), $taxonomy->label ), array( &$this, 'default_term_setting_field' ), 'writing', 'default', array( 'post_type' => 'attachment', 'taxonomy' => $taxonomy ) );
			}
		}
	}
	add_settings_field( 'single_taxonomies', __( 'Option to register taxonomies', 'ps-taxonomy-expander' ), array( &$this, 'single_taxonomies_filed' ), 'writing', 'default' );
}


function default_term_setting_field( $args ) {
	$option_name = $args['post_type'] . '_default_' . $args['taxonomy']->name;
	$default_term = get_option( $option_name );
	$terms = get_terms( $args['taxonomy']->name, 'hide_empty=0' );
	if ( $terms ) :
?>
	<select name="<?php echo $option_name; ?>">
		<option value="0"><?php _e( 'unset', 'ps-taxonomy-expander' ); ?></option>
<?php foreach ( $terms as $term ) : ?>
		<option value="<?php echo esc_attr( $term->term_id ); ?>"<?php echo $term->term_id == $default_term ? ' selected="selected"' : ''; ?>><?php echo esc_html( $term->name ); ?></option>
<?php endforeach; ?>
	</select>
<?php
	else:
?>
	<p><?php printf( __( '%s is not registerd.', 'ps-taxonomy-expander' ), esc_html( $args['taxonomy']->labels->singular_name ) ,esc_html( $args['taxonomy']->labels->name ) ); ?></p>
<?php
	endif;
}


function single_taxonomies_filed() {
	$single_taxonomies = get_option( 'single_taxonomies' );
	if ( $single_taxonomies === false ) {
		$single_taxonomies = array();
	}
	$taxonomies = get_taxonomies( array( 'hierarchical' => true, 'public' => true, 'show_ui' => true ), false );
?>

		<p><?php _e( 'check the taxonomy box to turn into single selection.', 'ps-taxonomy-expander' ) ?></p>
		<ul>
<?php foreach ( $taxonomies as $key => $obj ) :
$label = $obj->_builtin ? __( $obj->label ) : $obj->label;
$checked = $single_taxonomies && in_array( $obj->name, $single_taxonomies ) ? ' checked="checked"' : '';
?>
			<li><input type="checkbox" name="single_taxonomies[]" id="single_taxonomies_<?php echo esc_attr( $obj->name ); ?>" value="<?php echo esc_html( $obj->name );?>"<?php echo $checked; ?> /> <label for="single_taxonomies_<?php echo esc_attr( $obj->name ); ?>"><?php echo esc_html( $label ); ?></label></li>
<?php endforeach; ?>
		</ul>
<?php
}


function check_single_taxonomies_postdata() {
	$taxonomies = get_taxonomies( array( 'hierarchical' => true, 'public' => true, 'show_ui' => true ) );
	if ( isset( $POST['single_taxonomies'] ) ) {
		if ( is_array( $POST['single_taxonomies'] ) ) {
			foreach ( $POST['single_taxonomies'] as $key => $val ) {
				$val = maybe_unserialize( $val );
				if ( ! in_array( $val, array_keys( $taxonomies ) ) ) {
					unset( $POST['single_taxonomies'][$key] );
				}
			}
		} else {
			unset( $POST['single_taxonomies'] );
		}
	}
}


function allow_default_term_setting( $whitelist_options ) {
	$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), false );
	if ( $post_types ) {
		foreach ( $post_types as $post_type_slug => $post_type ) {
			$post_type_taxonomies = get_object_taxonomies( $post_type_slug, false );
			if ( $post_type_taxonomies ) {
				foreach ( $post_type_taxonomies as $tax_slug => $taxonomy ) {
					if ( ! ( $post_type_slug == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui ) {
						$whitelist_options['writing'][] = $post_type_slug . '_default_' . $tax_slug;
					}
				}
			}
		}
	}
	$media_taxonomies = get_object_taxonomies( 'attachment', false );
	if ( count( $media_taxonomies ) ) {
		foreach ( $media_taxonomies as $tax_slug => $taxonomy ) {
			if ( $taxonomy->show_ui ) {
				$whitelist_options['writing'][] = 'attachment_default_' . $tax_slug;
			}
		}
	}
	$whitelist_options['writing'][] = 'single_taxonomies';
	return $whitelist_options;
}


function add_post_type_default_term( $post_id, $post = null ) {
	if ( is_null( $post ) ) {
		$post_id = (int)$post_id;
		$post = get_post( $post_id );

	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $post->post_status == 'auto-draft' || $post->post_type == 'revision' ) { return; }
	$taxonomies = get_object_taxonomies( $post->post_type, false );
	if ( $taxonomies ) {
		foreach ( $taxonomies as $tax_slug => $taxonomy ) {
			$default = get_option( $post->post_type . '_default_' . $tax_slug );
			if ( ! ( $post->post_type == 'post' && $tax_slug == 'category' ) && $taxonomy->show_ui && $default && ! ( $terms = get_the_terms( $post_id, $tax_slug ) ) ) {
				if ( $taxonomy->hierarchical ) {
					$term = get_term( $default, $tax_slug );
					if ( $term ) {
						wp_set_post_terms( $post_id, array_filter( array( $default ) ), $tax_slug );
					}
				} else {
					$term = get_term( $default, $tax_slug );
					if ( $term ) {
						wp_set_post_terms( $post_id, $term->name, $tax_slug );
					}
				}
			}
		}
	}
}


function add_media_taxonomy_menu() {
	global $wp_taxonomies, $submenu;

	$media_taxonomies = array();
	if ( $wp_taxonomies ) {
		foreach ( $wp_taxonomies as $key => $obj ) {
			if ( count( $obj->object_type ) == 1 && $obj->object_type[0] == 'attachment' && $obj->show_ui ) {
				$media_taxonomies[$key] = $obj;
			}
		}
	}

	if ( $media_taxonomies ) {
		$priority = 50;
		foreach ( $media_taxonomies as $key => $media_taxonomy ) {
			if ( current_user_can( $media_taxonomy->cap->manage_terms ) ) {
				$submenu['upload.php'][$priority] = array( $media_taxonomy->labels->menu_name, 'upload_files', 'edit-tags.php?taxonomy=' . $key );
				$priority += 5;
			}
		}
	}
}


function replace_attachement_taxonomy_input_to_check( $form_fields, $post ) {
	if ( $form_fields ) {
		foreach ( $form_fields as $taxonomy => $obj ) {
			if ( isset( $obj['hierarchical'] ) && $obj['hierarchical'] ) {
				$terms = get_terms( $taxonomy, array( 'get' => 'all' ) );
				$taxonomy_tree = array();
				$branches = array();
				$term_id_arr = array();

				foreach( $terms as $term ) {
					$term_id_arr[$term->term_id] = $term;
					if ( $term->parent == 0 ) {
						$taxonomy_tree[$term->term_id] = array();
					} else {
						$branches[$term->parent][$term->term_id] = array();
					}
				}

				if ( count( $branches ) ) {
					foreach( $branches as $foundation => $branch ) {
						foreach( $branches as $branche_key => $val ) {
							if ( array_key_exists( $foundation, $val ) ) {
								$branches[$branche_key][$foundation] = &$branches[$foundation];
								break 1;
							}
						}
					}

					foreach ( $branches as $foundation => $branch ) {
						if ( isset( $taxonomy_tree[$foundation] ) ) {
							$taxonomy_tree[$foundation] = $branch;
						}
					}
				}

				$html = $this->walker_media_taxonomy_html( $post->ID, $taxonomy, $term_id_arr, $taxonomy_tree );
				if ( $terms ) {
					$form_fields[$taxonomy]['input'] = 'checkbox';
					$form_fields[$taxonomy]['checkbox'] = $html;
				} else {
					$form_fields[$taxonomy]['input'] = 'html';
					$form_fields[$taxonomy]['html'] = sprintf( __( '%s is not registerd.', 'ps-taxonomy-expander' ), esc_html( $obj['labels']->singular_name ), esc_html( $obj['labels']->name ) );
				}
			}
		}
	}
	return $form_fields;
}


function walker_media_taxonomy_html( $post_id, $taxonomy,  $term_id_arr, $taxonomy_tree, $html = '', $cnt = 0 ) {
	$single_taxonomies = get_option( 'single_taxonomies' );
	foreach ( $taxonomy_tree as $term_id => $arr ) {
		$checked = is_object_in_term( $post_id, $taxonomy, $term_id ) ? ' checked="checked"' : '';
		$type = in_array( $taxonomy, $single_taxonomies ) ? 'radio' : 'checkbox';
		$html .= str_repeat( '—', count( get_ancestors( $term_id, $taxonomy ) ) );
		$html .= ' <input type="' . $type . '" id="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '" name="attachments[' . $post_id . '][' . $taxonomy . '][]" value="' . esc_attr( $term_id_arr[$term_id]->name ) . '"' . $checked . ' /><label for="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '">' . esc_html( $term_id_arr[$term_id]->name ) . "</label><br />\n";
		$cnt++;
		if ( count( $arr ) ) {
			$html = $this->walker_media_taxonomy_html( $post_id, $taxonomy, $term_id_arr, $arr, $html, &$cnt );
		}
	}
	return $html;
}


function join_media_taxonomy_datas() {
	global $wp_taxonomies;
 
	if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'editattachment' ) { return; }
	$attachment_id = (int)$_POST['attachment_id'];
	$media_taxonomies = array();
	if ( $wp_taxonomies ) {
		foreach ( $wp_taxonomies as $key => $obj ) {
			if ( count( $obj->object_type ) == 1 && $obj->object_type[0] == 'attachment' ) {
				$media_taxonomies[$key] = $obj;
			}
		}
	}

	if ( $media_taxonomies ) {
		foreach ( $media_taxonomies as $key => $media_taxonomy ) {
			if ( isset( $_POST['attachments'][$attachment_id][$key] ) ) {
				if ( is_array( $_POST['attachments'][$attachment_id][$key] ) ) {
					$_POST['attachments'][$attachment_id][$key] = implode( ', ', $_POST['attachments'][$attachment_id][$key] );
				}
			} else {
					$_POST['attachments'][$attachment_id][$key] = '';
			}
		}
	}
}


function display_taxonomy_post_count() {
	$user = wp_get_current_user();
	if ( isset( $user->disp_tax_right_now ) && $user->disp_tax_right_now ) {
		$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), false );
		if ( count( $taxonomies ) ) {
			foreach ( $taxonomies as $tax_slug => $taxonomy ) {
				$num = wp_count_terms( $tax_slug );
				// Ummm....
				$text = $num == 1 ? $taxonomy->labels->singular_name : $taxonomy->labels->name;
				$num = number_format_i18n( $num );
				$text = esc_html( $text );
				if ( current_user_can( $taxonomy->cap->manage_terms ) ) {
					$num = '<a href="edit-tags.php?taxonomy=' . $tax_slug . '">' . $num . '</a>';
					$text = '<a href="edit-tags.php?taxonomy=' . $tax_slug . '">' . $text . '</a>';
				}
?>
<tr>
	<td class="b b-<?php echo esc_attr( $tax_slug ); ?>"><a><?php echo $num; ?></a></td>
	<td class="last t"><a><?php echo $text; ?></a></td>
</tr>
<?php
			}
		}
	}
}


function add_taxonomy_count_dashboard_right_now_field() {
	global $profileuser;
	$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ) );
	if ( count( $taxonomies ) ) {
?>
	<tr>
		<th scope="row"><?php _e( 'Add taxonomies on Right Now', 'ps-taxonomy-expander' ) ?></th>
			<td>
				<label for="disp_tax_right_now">
					<input type="checkbox" name="disp_tax_right_now" id="disp_tax_right_now" value="1"<?php if ( $profileuser->disp_tax_right_now ) : ?> checked="checked"<?php endif; ?> />
					<?php _e( 'Display taxonomies on Right Now in the Dashboard.', 'ps-taxonomy-expander' ) ?>
				</label>
			</td>
		</tr>
<?php
	}
}


function update_taxonomy_count_dashboard_right_now( $user_id, $old_user_data ) {
	$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ) );
	if ( count( $taxonomies ) ) {
		if ( isset( $_POST['disp_tax_right_now'] ) && ( ! isset( $old_user_data->disp_tax_right_now ) || ! $old_user_data->disp_tax_right_now ) ) {
			update_user_meta( $user_id, 'disp_tax_right_now', 1 );
		} else {
			update_user_meta( $user_id, 'disp_tax_right_now', 0 );
		}
	}
}


} // class end
$ps_taxonomy_expander = new PS_Taxonomy_Expander();