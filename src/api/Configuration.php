<?php

namespace tuja;

use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\util\JwtUtils;
use tuja\util\Template;
use WP_REST_Request;
use WP_REST_Response;

class Configuration extends AbstractRestEndpoint {

	public static function get_configuration( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$competition_dao = new CompetitionDao();
		$competition     = $competition_dao->get( $group->competition_id );

		$info_page_content = @$competition->app_config['messages']['info_page_content'];
		if ( isset( $info_page_content ) ) {
			$competition->app_config['messages']['info_page_content'] = Template::string( $info_page_content, Template::TYPE_MARKDOWN )->render( array(), true );
		}

		$start_page_content = @$competition->app_config['messages']['start_page_content'];
		if ( isset( $start_page_content ) ) {
			$competition->app_config['messages']['start_page_content'] = Template::string( $start_page_content, Template::TYPE_MARKDOWN )->render( array(), true );
		}

		return array(
			'app' => $competition->app_config,
		);
	}
}
