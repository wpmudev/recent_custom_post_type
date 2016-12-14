<?php
/*
Plugin Name: Recent Custom Posts Type Widget
Plugin URI: http://premium.wpmudev.org/project/recent-custom-posts-type-widget
Description: Allows you to display recent custom post types.
Version: 2.1.3
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
WDP ID: 226

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( ! function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );
	function wdp_un_check() {
		if ( ! class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) ) {
			echo '<div class="error fade"><p>' . __( 'Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev' ) . '</a></p></div>';
		}
	}
}

/* --------------------------------------------------------------------- */


class RcptWidget extends WP_Widget {

	var $_order_options = array();
	var $_order_directions = array();

	function RcptWidget() {
		$this->_order_options    = array(
			'none'     => __( 'No order', 'rcpt' ),
			'rand'     => __( 'Random order', 'rcpt' ),
			'id'       => __( 'Order by ID', 'rcpt' ),
			'author'   => __( 'Order by author', 'rcpt' ),
			'title'    => __( 'Order by title', 'rcpt' ),
			'date'     => __( 'Order by creation date', 'rcpt' ),
			'modified' => __( 'Order by last modified date', 'rcpt' ),
		);
		$this->_order_directions = array(
			'ASC'  => __( 'Ascending', 'rcpt' ),
			'DESC' => __( 'Descending', 'rcpt' ),
		);
		$widget_ops = array(
			'classname'   => 'widget_rcpt',
			'description' => __( 'Shows the most recent posts from a selected custom post type', 'rcpt' )
		);
		$this->__construct( 'rcpt', 'Recent Custom Posts Type Widget', $widget_ops );
	}

	function form( $instance ) {
		$title          = esc_attr( $instance['title'] );
		$post_type      = esc_attr( $instance['post_type'] );
		$featured_image = esc_attr( $instance['featured_image'] );
		$post_author    = esc_attr( $instance['post_author'] );
		$limit          = esc_attr( $instance['limit'] );
		$class          = esc_attr( $instance['class'] );
		$order_by       = esc_attr( $instance['order_by'] );
		$order_dir      = esc_attr( $instance['order_dir'] );

		// Fields
		$show_title      = isset( $instance['show_title'] ) ? (int) $instance['show_title'] : true; // Show by default
		$titles_as_links = isset( $instance['titles_as_links'] ) ? (int) $instance['titles_as_links'] : true; // True by default
		$show_body       = (int) $instance['show_body'];
		$show_thumbs     = esc_attr( $instance['show_thumbs'] );
		$show_dates      = esc_attr( $instance['show_dates'] );

		$fields = $instance['fields'];
		$fields = $fields ? $fields : array();

		// Set defaults
		// ...

		// Get post types
		$post_types   = get_post_types( array( 'public' => true ), 'objects' );
		$post_authors = $this->_get_post_authors();

		$html = '<p>';
		$html .= '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'rcpt' ) . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name( 'title' ) . '" id="' . $this->get_field_id( 'title' ) . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'post_type' ) . '">' . __( 'Post type:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'post_type' ) . '" id="' . $this->get_field_id( 'post_type' ) . '">';
		foreach ( $post_types as $pt ) {
			$html .= '<option value="' . $pt->name . '" ' . ( ( $pt->name == $post_type ) ? 'selected="selected"' : '' ) . '>' . $pt->label . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'post_author' ) . '">' . __( 'Authored by:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'post_author' ) . '" id="' . $this->get_field_id( 'post_author' ) . '">';
		foreach ( $post_authors as $pa_id => $pa_name ) {
			$html .= '<option value="' . $pa_id . '" ' . ( ( $pa_id == $post_author ) ? 'selected="selected"' : '' ) . '>' . $pa_name . '&nbsp;</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'limit' ) . '">' . __( 'Limit:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'limit' ) . '" id="' . $this->get_field_id( 'limit' ) . '">';
		for ( $i = 1; $i < 21; $i ++ ) {
			$html .= '<option value="' . $i . '" ' . ( ( $i == $limit ) ? 'selected="selected"' : '' ) . '>' . $i . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'featured_image' ) . '" id="' . $this->get_field_id( 'featured_image' ) . '" value="1" ' . ( $featured_image ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'featured_image' ) . '">' . __( 'Restrict to posts with featured image', 'rcpt' ) . '</label> ' .
		         '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'class' ) . '">' . __( 'Additional CSS class(es) <small>(optional)</small>:', 'rcpt' ) . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name( 'class' ) . '" id="' . $this->get_field_id( 'class' ) . '" class="widefat" value="' . $class . '"/>';
		$html .= '<div><small>' . __( 'One or more space separated valid CSS class names that will be applied to the generated list', 'rcpt' ) . '</small></div>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'order_by' ) . '">' . __( 'Order by:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'order_by' ) . '" id="' . $this->get_field_id( 'order_by' ) . '">';
		foreach ( $this->_order_options as $key => $label ) {
			$html .= '<option value="' . $key . '" ' . ( ( $key == $order_by ) ? 'selected="selected"' : '' ) . '>' . __( $label, 'rcpt' ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'order_dir' ) . '">' . __( 'Order direction:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'order_dir' ) . '" id="' . $this->get_field_id( 'order_dir' ) . '">';
		foreach ( $this->_order_directions as $key => $label ) {
			$html .= '<option value="' . $key . '" ' . ( ( $key == $order_dir ) ? 'selected="selected"' : '' ) . '>' . __( $label, 'rcpt' ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'show_title' ) . '" id="' . $this->get_field_id( 'show_title' ) . '" value="1" ' . ( $show_title ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'show_title' ) . '">' . __( 'Show titles', 'rcpt' ) . '</label> ' .
		         '</p>';

		$html .= '<p>&nbsp;&nbsp;&nbsp;&nbsp;<small>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'titles_as_links' ) . '" id="' . $this->get_field_id( 'titles_as_links' ) . '" value="1" ' . ( $titles_as_links ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'titles_as_links' ) . '">' . __( 'Titles as links to posts', 'rcpt' ) . '</label> ' .
		         '</small></p>';

		$html .= '<p>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'show_body' ) . '" id="' . $this->get_field_id( 'show_body' ) . '" value="1" ' . ( $show_body ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'show_body' ) . '">' . __( 'Show body excerpt', 'rcpt' ) . '</label> ' .
		         '</p>';

		$html .= '<p>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'show_dates' ) . '" id="' . $this->get_field_id( 'show_dates' ) . '" value="1" ' . ( $show_dates ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'show_dates' ) . '">' . __( 'Show post dates', 'rcpt' ) . '</label> ' .
		         '</p>';

		$html .= '<p>' .
		         '<input type="checkbox" name="' . $this->get_field_name( 'show_thumbs' ) . '" id="' . $this->get_field_id( 'show_thumbs' ) . '" value="1" ' . ( $show_thumbs ? 'checked="checked"' : '' ) . '/>' .
		         ' <label for="' . $this->get_field_id( 'show_thumbs' ) . '">' . __( 'Show featured thumbnails <small>(if available)</small>', 'rcpt' ) . '</label> ' .
		         '</p>';

		// Custom fields
		$id = sprintf( 'rcpt-custom_fields-%04d-%04d-%04d-%04d', rand(), rand(), rand(), rand() );
		$html .= '<p><a href="#toggle" class="rcpt-toggle_custom_fields" id="' . $id . '-handler">' . __( 'Show/hide custom fields', 'rcpt' ) . '</a></p>';
		$html .= '<div class="rcpt-show_custom_fields" id="' . $id . '" style="display:none">';
		$html .= '<h5>' . __( 'Custom fields', 'rcpt' ) . '</h5>';
		$html .= '<p>';
		$_fields      = $this->_get_post_fields( $post_type );
		$shown_fields = array();
		$skips        = array(
			'/^_edit_.*/',
			'/^_thumbnail_.*/',
			'/^_wp_.*/',
		);
		if ( $_fields ) {
			foreach ( $_fields as $field ) {
				if ( preg_filter( $skips, ':skip:', $field ) ) {
					continue;
				}
				$value          = in_array( $field, array_keys( $fields ) ) ? esc_attr( $fields[ $field ] ) : '';
				$shown_fields[] = '<label for="' . $this->get_field_id( 'fields' ) . '-' . $field . '">' . sprintf( __( 'Label for &quot;%s&quot;', 'rcpt' ), $field ) . '</label>' .
				                  '<input type="text" class="widefat" name="' . $this->get_field_name( 'fields' ) . '[' . $field . ']" id="' . $this->get_field_id( 'fields' ) . '-' . $field . '" value="' . $value . '" />' .
				                  '';
			}
		}
		if ( $shown_fields ) {
			$html .= '<small><em>' . __( "Fields with no associated label won't be shown in the widget output", 'rcpt' ) . '</em></small><br />';
			$html .= join( '<br />', $shown_fields );
		}
		$html .= '<small><em>' . __( 'Select your post type above and save settings to refresh', 'rcpt' ) . '</em></small>';
		$html .= '</p>';
		$html .= '</div>';
		$html .= <<<EORcptJs
<script type="text/javascript">
(function ($) {
$("#{$id}-handler").live("click", function () {
	var el = $("#{$id}");
	if (!el.length) return false;
	if (el.is(":visible")) el.hide();
	else el.show();
	return false;
});
})(jQuery);
</script>
EORcptJs;

		echo $html;
	}

	private function _get_post_fields( $type ) {
		if ( ! $type ) {
			return false;
		}
		global $wpdb;
		$fields = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE post_id=ID and post_type='%s'", $type )
		);

		return $fields;
	}

	private function _get_post_authors() {
		global $wpdb;
		$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts}" );
		$info    = array( '' => __( 'Anyone', 'rcpt' ) );
		foreach ( $authors as $author ) {
			$user            = new WP_User( $author );
			$info[ $author ] = $user->display_name;
		}

		return $info;
	}

	function update( $new_instance, $old_instance ) {
		$instance                   = $old_instance;
		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['post_type']      = strip_tags( $new_instance['post_type'] );
		$instance['featured_image'] = strip_tags( $new_instance['featured_image'] );
		$instance['post_author']    = strip_tags( $new_instance['post_author'] );
		$instance['limit']          = strip_tags( $new_instance['limit'] );
		$instance['class']          = strip_tags( $new_instance['class'] );
		$instance['order_by']       = strip_tags( $new_instance['order_by'] );
		$instance['order_dir']      = strip_tags( $new_instance['order_dir'] );

		$instance['show_title']      = strip_tags( $new_instance['show_title'] );
		$instance['titles_as_links'] = strip_tags( $new_instance['titles_as_links'] );
		$instance['show_body']       = strip_tags( $new_instance['show_body'] );
		$instance['show_thumbs']     = strip_tags( $new_instance['show_thumbs'] );
		$instance['show_dates']      = strip_tags( $new_instance['show_dates'] );

		$instance['fields'] = array();
		$fields             = $new_instance['fields'];
		$fields             = $fields ? $fields : array();
		foreach ( $fields as $key => $value ) {
			$key                        = wp_strip_all_tags( $key );
			$instance['fields'][ $key ] = wp_strip_all_tags( $value );
		}

		return $instance;
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title          = apply_filters( 'widget_title', $instance['title'] );
		$post_type      = $instance['post_type'];
		$post_author    = (int) $instance['post_author'];
		$featured_image = (int) $instance['featured_image'];
		$limit          = (int) $instance['limit'];
		$class          = $instance['class'];
		$class          = $class ? " {$class}" : '';

		$order_by  = $instance['order_by'];
		$order_by  = in_array( $order_by, array_keys( $this->_order_options ) ) ? $order_by : 'none';
		$order_dir = $instance['order_dir'];
		$order_dir = in_array( $order_dir, array_keys( $this->_order_directions ) ) ? $order_dir : 'ASC';

		// Fields
		$show_title      = isset( $instance['show_title'] ) ? (int) $instance['show_title'] : true; // Show by default
		$titles_as_links = isset( $instance['titles_as_links'] ) ? (int) $instance['titles_as_links'] : true; // True by default
		$show_body       = (int) $instance['show_body'];
		$show_thumbs     = (int) $instance['show_thumbs'];
		$show_dates      = (int) $instance['show_dates'];

		$fields = $instance['fields'];
		$fields = $fields ? $fields : array();

		$query_args = array(
			'showposts'        => $limit,
			'nopaging'         => 0,
			'post_status'      => 'publish',
			'post_type'        => $post_type,
			'orderby'          => $order_by,
			'order'            => $order_dir,
			'caller_get_posts' => 1
		);
		if ( $post_author ) {
			$query_args['author'] = $post_author;
		}
		if ( $featured_image ) {
			$query_args['meta_key'] = '_thumbnail_id';
		}
		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			while ( $query->have_posts() ) {
				$query->the_post();

				$item_title = get_the_title() ? get_the_title() : get_the_ID();
				$image      = $src = $width = $height = false;
				if ( $show_thumbs ) {
					$thumb_id = get_post_thumbnail_id( get_the_ID() );
					if ( $thumb_id ) {
						$image = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
						if ( $image ) {
							$src    = $image[0];
							$width  = $image[1];
							$height = $image[2];
						}
					}
				}

				$image_format = $image
					? '<span class="rcpt_item_image"><img src="%s" height="%d" width="%d" alt="%s" border="0" /></span>'
					: '';
				$image_str    = sprintf( $image_format, $src, $height, $width, esc_attr( $item_title ) );

				$item_title_str       = $show_title
					? sprintf( '<span class="rcpt_item_title">%s %s</span>', $image_str, $item_title )
					: sprintf( '<span class="rcpt_item_title">%s</span>', $image_str );
				$final_item_title_str = $titles_as_links
					? sprintf( '<a href="%s" title="%s">%s</a>', get_permalink(), esc_attr( $item_title ), $item_title_str )
					: $item_title_str;

				$post_fields  = get_post_custom( get_the_ID() );
				$shown_fields = array();
				foreach ( $post_fields as $field => $value ) {
					if ( ! in_array( $field, array_keys( $fields ) ) ) {
						continue;
					} // Not here
					if ( ! $fields[ $field ] ) {
						continue;
					} // No label
					$value          = is_array( $value ) ? join( ', ', $value ) : $value;
					$shown_fields[] = array(
						'label' => wp_strip_all_tags( $fields[ $field ] ),
						'value' => wp_strip_all_tags( $value ),
					);
				}


				echo '<div class="rcpt_items"><ul class="rcpt_items_list' . $class . '">';

				echo '<li>';
				echo $final_item_title_str;
				if ( $show_body ) {
					echo '<div class="rcpt_item_excerpt">' . get_the_excerpt() . '</div>';
				}
				if ( $show_dates ) {
					echo '<span class="rcpt_item_date"><span class="rcpt_item_posted">' . __( 'Posted on', 'rcpt' ) . ' </span>' . get_the_date() . '</span>';
				}
				if ( $shown_fields ) {
					echo '<dl class="rcpt_item_custom_fields">';
					foreach ( $shown_fields as $custom ) {
						echo '<dt>' . $custom['label'] . '</dt>';
						echo '<dd>' . $custom['value'] . '</dd>';
					}
					echo '</dl>';
				}
				echo '</li>';

				echo '</ul></div>';
			}

			echo $after_widget;
		}
		wp_reset_postdata();
		wp_reset_query();
	}
}

// PHP<5.3 compatibility layer.
if ( ! function_exists( 'preg_filter' ) ) {
	function preg_filter( $pattern, $replace, $subject, $limit = - 1, $count = null ) {
		if ( ! is_array( $subject ) ) {
			$noArray = 1;
			$subject = array( $subject );
		}

		$preg = preg_replace( $pattern, $replace, $subject, $limit, $count );

		$diff = array_diff( $preg, $subject );

		if ( $noArray == 1 ) {
			$diff = implode( $diff );
		}

		return $diff;
	}
}


load_plugin_textdomain( 'rcpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

// Init widget
add_action( 'widgets_init', create_function( '', "register_widget('RcptWidget');" ) );

// Queue in the stylesheet
if ( ! is_admin() ) {
	add_action( 'init', create_function( '', 'wp_enqueue_style("rcpt_style", plugins_url("media/style.css", __FILE__));' ) );
}