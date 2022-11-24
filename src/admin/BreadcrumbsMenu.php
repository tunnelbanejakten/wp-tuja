<?php
namespace tuja\admin;


// Credits: https://codepen.io/kkhenriquez/pen/PQxvaa
class BreadcrumbsMenu {

	private $items = array();

	public function add( array $item, array ...$sub_items ) {
		$this->items[] = array(
			'item'      => $item,
			'sub_items' => $sub_items,
		);
		return $this;
	}

	public function render_leaves( bool $large = false ) {
		return $this->render_menu( $this->items[ count( $this->items ) - 1 ], $large ? 'leaves-menu-large' : 'root-menu');
	}

	public function render_root_menu() {
		return $this->render_menu( $this->items[0], 'main-menu root-menu' );
	}

	private function render_menu( $config, $container_class = 'root-menu' ) {
		$sub_items     = $config['sub_items'];
		$has_sub_items = ! empty( $sub_items );
		if ( ! $has_sub_items ) {
			return '';
		}

		$output      = array();
		$output[]    = sprintf( '<nav class="%s">', $container_class );
		$last_header = null;
		foreach ( $sub_items as $sub_item_config ) {
			list ($label, $link, $active, $header) = $sub_item_config;

			$current_header    = $header ?? '';
			$is_start_of_group = $current_header !== $last_header;
			if ( $is_start_of_group ) {
				$is_first_group = is_null( $last_header );
				if ( ! $is_first_group ) {
					$output[] = '</div></div>';
				}
				$output[] = sprintf( '<div><div class="root-menu-group-header">%s</div><div class="root-menu-group-items">', $current_header );
			}
			$output[] = sprintf( '<div class="root-menu-item"><a href="%s" class="%s">%s</a></div>', $link, $active ? 'active' : 'inactive', $label );

			$last_header = $current_header;
		}
		$output[] = '</div></div>';
		$output[] = '</nav>';

		return join( $output );

		return sprintf(
			'<nav class="root-menu">%s</nav>',
			join(
				' | ',
				array_map(
					function ( $sub_item_config ) {
					},
					$sub_items
				)
			)
		);
	}

	public function render() {
		return sprintf(
			'<nav class="breadcrumb"><ol>%s</ol></nav>',
			join(
				array_map(
					function ( array $config ) {
						list ($label, $link, $active) = $config['item'];
						$is_item_link_set             = ! empty( $link );
						$sub_items                    = $config['sub_items'];
						$has_sub_items                = ! empty( $sub_items );
						$class                        = $has_sub_items ? 'drop-container' : '';
						$sub_items_html               = $has_sub_items ? sprintf(
							'<div class="drop"><ul>%s</ul></li>',
							join(
								array_map(
									function ( $sub_item_config ) {
										list ($label, $link, $active) = $sub_item_config;
										return sprintf( '<li><a href="%s" class="%s">%s</a></li>', $link, $active ? 'active' : 'inactive', $label );
									},
									$sub_items
								)
							)
						) : '';
						$item_html                    = $is_item_link_set ? sprintf( '<a href="%s">%s</a>', $link, $label ) : $label;
						return sprintf( '<li class="%s"><span>%s</span>%s</li>', $class, $item_html, $sub_items_html );
					},
					array_slice( $this->items, 1 )
				)
			)
		);
	}

	public static function item( $label, $link = null, $active = false, $header = '' ) {
		return array( $label, $link, $active, $header );
	}

	public static function create() {
		return new BreadcrumbsMenu();
	}
}
