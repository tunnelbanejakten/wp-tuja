<?php
namespace tuja\admin;


class BreadcrumbsMenu {

	private $items = array();

	public function add( array $item, array ...$sub_items ) {
		$this->items[] = array(
			'item'      => $item,
			'sub_items' => $sub_items,
		);
		return $this;
	}

	public function render() {
		return sprintf(
			'<nav class="breadcrumb"><ol>%s</ol></nav>',
			join(
				array_map(
					function ( array $config ) {
						list ($label, $link) = $config['item'];
						$is_item_link_set    = ! empty( $link );
						$sub_items           = $config['sub_items'];
						$has_sub_items       = ! empty( $sub_items );
						$class               = $has_sub_items ? 'drop-container' : '';
						$sub_items_html      = $has_sub_items ? sprintf(
							'<div class="drop"><ul>%s</ul></li>',
							join(
								array_map(
									function ( $sub_item_config ) {
										list ($label, $link) = $sub_item_config;
										return sprintf( '<li><a href="%s">%s</a></li>', $link, $label );
									},
									$sub_items
								)
							)
						) : '';
						$item_html           = $is_item_link_set ? sprintf( '<a href="%s">%s</a>', $link, $label ) : sprintf( '<span>%s</span>', $label );
						return sprintf( '<li class="%s">%s%s</li>', $class, $item_html, $sub_items_html );
					},
					$this->items
				)
			)
		);
	}

	public static function item( $label, $link = null ) {
		return array( $label, $link );
	}

	public static function create() {
		return new BreadcrumbsMenu();
	}
}
