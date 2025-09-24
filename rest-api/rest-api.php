<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_AI_Endpoints
{
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'disciple-tools-ai/v1';

        register_rest_route(
            $namespace, '/dt-ai-summarize', [
                'methods'  => 'POST',
                'callback' => [ $this, 'summarize' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    $post_type = $request->get_param( 'post_type' ) ?? '';
                    $post_id = $request->get_param( 'post_id' ) ?? '';
                    return DT_Posts::can_view( $post_type, $post_id );
                },
            ]
        );

        register_rest_route(
            $namespace, '/dt-ai-create-filter', [
                'methods'  => 'POST',
                'callback' => [ $this, 'create_filter' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );
    }

    public function summarize( WP_REST_Request $request ) {
        // Check if AI summarization module is enabled
        if ( Disciple_Tools_AI_API::has_module_value( Disciple_Tools_AI_API::$module_default_id_dt_ai_summarization, 'enabled', 0 ) ) {
            return new WP_Error( 'module_disabled', 'AI Summarization module is not enabled', [ 'status' => 403 ] );
        }

        $post_type = $request->get_param( 'post_type' ) ?? '';
        $post_id = $request->get_param( 'post_id' ) ?? '';
        if ( empty( $post_type ) || empty( $post_id ) ) {
            return new WP_Error( 'missing_parameters', 'post_type and post_id are required', [ 'status' => 400 ] );
        }

        return Disciple_Tools_AI_API::summarize( $post_type, $post_id );
    }

    public function create_filter( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['prompt'], $params['post_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }

        $prompt = $params['prompt'];
        $post_type = $params['post_type'];

        if ( isset( $params['selections'], $params['pii'], $params['inferred'] ) ) {
            return $this->handle_create_filter_with_selections_request( $post_type, $prompt, $params['selections'], $params['pii'], $params['inferred'] );
        } else {
            return $this->handle_create_filter_request( $post_type, $prompt );
        }
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    private function handle_create_filter_request( $post_type, $prompt ): array {

        /**
         * If the initial response is multiple_options_detected, then return; otherwise,
         * filter locations and then return.
         */

        $response = Disciple_Tools_AI_API::list_posts( $post_type, $prompt );
        if ( isset( $response['status'] ) && in_array( $response['status'], [ 'error', 'multiple_options_detected' ] ) ) {
            return $response;
        }

        /**
         * Finally, the finish line - return the response.
         */

        return [
            'status' => 'success',
            'prompt' => $response['prompt'] ?? [],
            'pii' => $response['pii'] ?? [],
            'connections' => $response['connections'] ?? [],
            'filter' => $response['filter'] ?? [],
            'text_search' => $response['text_search'] ?? null,
            'posts' => $response['posts'] ?? [],
            'inferred' => $response['inferred'] ?? []
        ];
    }

    private function handle_create_filter_with_selections_request( $post_type, $prompt, $selections, $pii, $inferred ): array {

        $response = Disciple_Tools_AI_API::list_posts_with_selections( $post_type, $prompt, $selections, $pii, $inferred );

        /**
         * Ensure any encountered errors are echoed directly back to calling client.
         */

        if ( isset( $response['status'] ) && $response['status'] == 'error' ) {
            return $response;
        }

        /**
         * Finally, the finish line - return the response.
         */

        return [
            'status' => 'success',
            'prompt' => $response['prompt'] ?? [],
            'filter' => $response['filter'] ?? [],
            'text_search' => $response['text_search'] ?? null,
            'posts' => $response['posts'] ?? [],
            'inferred' => $response['inferred'] ?? []
        ];
    }
}
Disciple_Tools_AI_Endpoints::instance();
