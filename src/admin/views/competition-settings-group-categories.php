<?php
namespace tuja\admin;

use tuja\data\model\GroupCategory;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\PassthroughRuleSet;

$this->print_root_menu();
$this->print_leaves_menu();
?>

<form method="post" class="tuja">
		<p>
			Grupptyper gör det möjligt att hantera flera tävlingsklasser och att skilja på tävlande och funktionärer.
			Grypptyper ska inte förväxlas med grupper. En tävling kan ha flera grupper och varje person är med i en
			grupp. Grupptyper är ett sätt att klassificera grupperna utifrån deras roll i tävlingen.
		</p>

	<table>
	<tbody>
		<tr>
		<th></th>
		<?php
			print join(
				array_map(
					function ( GroupCategory $category ) use ( $competition ) {
						return sprintf(
							'<td>
                                <input type="text" class="text tuja-map-name-field" value="%s" name="%s" id="%s"><br>
                                <button type="submit" class="button" name="tuja_action" onclick="return confirm(\'Är du säker?\');" value="%s" id="%s">Ta bort</button>
                            </td>',
							$category->name,
							$this->list_item_field_name( 'groupcategory', $category->id, 'name' ),
							$this->list_item_field_name( 'groupcategory', $category->id, 'name' ),
							'tuja_groupcategory_delete__' . $category->id,
							'tuja_groupcategory_delete__' . $category->id
						);
					},
					$category_dao->get_all_in_competition( $competition->id )
				)
			);
			?>
		<td rowspan="2" style="vertical-align: top;">
			<input type="text" name="tuja_groupcategory_name" id="tuja_groupcategory_name" placeholder="Kategorinamn"/><br>
			<div>
				Förvalda regler:
				<?php
				$selected_ruleset = isset( $_POST['tuja_groupcategory_ruleset'] ) ? stripslashes( $_POST['tuja_groupcategory_ruleset'] ) : PassthroughRuleSet::class;
				echo join(
					array_map(
						function ( $class_name, $label ) use ( $selected_ruleset ) {
							return sprintf(
								'<br><input type="radio" name="tuja_groupcategory_ruleset" value="%s" %s id="%s"><label for="%s">%s</label>',
								$class_name,
								$selected_ruleset === $class_name ? 'checked="checked"' : '',
								'tuja_groupcategory_ruleset__' . crc32( $class_name ),
								'tuja_groupcategory_ruleset__' . crc32( $class_name ),
								$label
							);
						},
						array_keys( self::RULE_SETS ),
						array_values( self::RULE_SETS )
					)
				)
				?>
			</div>
			
			<div class="tuja-buttons">
				<button type="submit" class="button" name="tuja_action" value="tuja_groupcategory_create" id="tuja_groupcategory_create_button">
					Lägg till
				</button>
			</div>

		</td>
		</tr>
			<tr>
				<?php
				printf(
					'<td><div class="tuja-ruleset-column">%s</div></td>',
					join(
						array_map(
							function ( array $props ) {
								list ($label, $description) = $props;
								return sprintf(
									'<div class="row">%s%s</div>',
									$label,
									! empty( $description ) ? AdminUtils::tooltip( $description ) : ''
								);
							},
							( new GroupCategoryRules( array() ) )->get_props_labels()
						)
					)
				);

				print join(
					array_map(
						function ( GroupCategory $category ) use ( $competition ) {
							return $this->print_group_category_form( $category, $competition );
						},
						$category_dao->get_all_in_competition( $competition->id )
					)
				);
				?>
			</tr>
		</tbody>
	</table>

	<button class="button button-primary"
			type="submit"
			name="tuja_action"
			id="tuja_save_competition_settings_button"
			value="tuja_groupcategory_save">
		Spara
	</button>
</form>
