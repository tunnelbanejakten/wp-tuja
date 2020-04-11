<?php


namespace tuja\util;


class TemplateEditor {
	public static function render( string $field_name, string $body, array $sample_parameters ): string {
		$target = uniqid();

		return sprintf(
			'
	            <div class="tuja-templateeditor" data-target="%s" data-preview-endpoint="%s" %s>
	                <div class="input">
	                    <textarea name="%s" rows="10" id="%s">%s</textarea>
	                </div>
	                <div class="preview">
	                    <iframe name="%s" srcdoc="%s"></iframe>
	                </div>
	                <div class="controls">
	                	<div class="tooltip">
                            <a class="thickbox" title="Så här skriver du" href="#TB_inline?height=600&inlineId=tuja-tooltip-content-%s"><span class="dashicons dashicons-editor-help"></span></a>
	                		
	                		<div class="tooltip-content" id="tuja-tooltip-content-%s"><div>
	                			Du kan använda dessa variabler: <br>
	                			%s
	                			
                                <br>
                                Utöver variabler kan du även använda <a href="https://daringfireball.net/projects/markdown/basics">Markdown</a> för att göra fet text, lägga in länkar mm:
                                <br><br>
                                <div class="placeholder">**Fet**</div>
								<div class="placeholder">*Kursiv*</div>
								<div class="placeholder">&lt;%s&gt;</div>
								<div class="placeholder">~~Genomstruken~~</div>
								<div class="placeholder">En lista:</div>
								<div class="placeholder">* Mobiltelefon</div>
								<div class="placeholder">* Glatt humör</div>
							</div></div>
                        </div>
	                </div>
	            </div>',
			$target,
			add_query_arg( array(
				'action' => 'tuja_markdown'
			), admin_url( 'admin.php' ) ),
			join( ' ', array_map( function ( $key, $value ) {
				return sprintf( 'data-param-%s="%s:%s"', uniqid(), $key, htmlentities( $value ) );
			}, array_keys( $sample_parameters ), array_values( $sample_parameters ) ) ),
			$field_name,
			$field_name,
			$body,
			$target,
			htmlentities( self::render_preview( $body, $sample_parameters ) ),
			$target,
			$target,
			join( array_map( function ( $var ) {
				return sprintf( '<div class="placeholder">{{%s}}</div>', $var );
			}, array_keys( $sample_parameters ) ) ),
			$target,
			get_site_url() );
	}

	public static function render_preview( string $template, array $parameters ): string {
		return sprintf( '
			<html>
				<head>
					<style>
						body {font-family: sans-serif; font-size: 90%%}
					</style>
				</head>
				<body>
					%s
				</body>
			</html>',
			Template::string( $template )->render( $parameters, true ) );
	}
}