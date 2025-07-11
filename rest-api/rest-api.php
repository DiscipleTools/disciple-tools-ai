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
                    return $this->has_permission();
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
        $post_type = $request->get_param( 'post_type' ) ?? '';
        $post_id = $request->get_param( 'post_id' ) ?? '';

        if ( !DT_Posts::can_view( $post_type, $post_id ) ) {
            return new WP_Error( __METHOD__, __( 'Unauthorized: Unable to summarize!', 'disciple-tools-ai' ), [ 'status' => 401 ] );
        }

        // Get the prompt from the request and make a call to the OpenAI API to summarize and return the response
        $prompt = $request->get_param( 'prompt' );

        $llm_endpoint_root = get_option( 'DT_AI_llm_endpoint' );
        $llm_api_key = get_option( 'DT_AI_llm_api_key' );
        $llm_model = get_option( 'DT_AI_llm_model' );

        $llm_endpoint = $llm_endpoint_root . '/chat/completions';

        $response = wp_remote_post( $llm_endpoint, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $llm_api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( [
                'model' => $llm_model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_completion_tokens' => 1000,
                'temperature' => 1,
                'top_p' => 1,
            ] ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', 'Failed to connect to LLM API', [ 'status' => 500 ] );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $summary = $body['choices'][0]['message']['content'];

        $post_updated = false;
        if ( isset( $post_type, $post_id ) && in_array( $post_type, [ 'contacts', 'ai' ] ) ) {
            $updated = DT_Posts::update_post( $post_type, $post_id, [
                'ai_summary' => $summary
            ] );

            $post_updated = !is_wp_error( $updated );
        }

        return [
            'updated' => $post_updated,
            'summary' => $summary
        ];
    }

    public function create_filter( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['prompt'], $params['post_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }

        $prompt = $params['prompt'];
        $post_type = $params['post_type'];

        if ( isset( $params['selections'], $params['pii'], $params['filtered_fields'] ) ) {
            return $this->handle_create_filter_with_selections_request( $post_type, $prompt, $params['selections'], $params['pii'], $params['filtered_fields'] );
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

    private function handle_create_filter_with_selections_request( $post_type, $prompt, $selections, $pii, $filtered_fields ): array {

        $response = Disciple_Tools_AI_API::list_posts_with_selections( $post_type, $prompt, $selections, $pii, $filtered_fields );

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
