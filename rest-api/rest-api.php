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

        $system_prompt = 'You are an assistant helping summarize a contact\'s current state based on CRM field updates and user comments.
Given a list of recent field changes and comments from various users, write a clear and concise summary or bio of the contact.
✅ Focus on the current state of the contact — who they are, where they\'re at in their journey, any needs or interests, and relevant background.
❌ Do not list every field change or activity in chronological order.
✅ You may infer reasonable context from patterns or repeated updates.
✅ Use a warm and neutral tone, like something a teammate would write in a handoff note.

Input format:
- Contact Fields (Current Values): {list of current fields and values}
- Field Update History: {optional list of past field changes}
- User Comments: {collection of internal user notes, messages, or logs}

Output format:
- A paragraph summary (2–5 sentences)
- Clear and readable, no jargon, and understandable to someone new to the team
- No prelude like "Here is the summary: ';

        $contact_fields = DT_Posts::get_post( $post_type, $post_id );
        $comments = DT_Posts::get_post_comments( $post_type, $post_id );
        $field_updates = DT_Posts::get_post_activity( $post_type, $post_id );

        // Get field settings to filter by type
        $field_settings = DT_Posts::get_post_field_settings( $post_type );

        // Define allowed field types
        $allowed_field_types = [
            'key_select',
            'multi_select',
            'number',
            'text',
            'location',
            'location_meta',
            'date'
        ];

        $prompt = "Contact Fields (Current Values):\n";
        foreach ( $contact_fields as $field => $value ) {
            // Only include fields with the specified types
            if ( !empty( $value ) && isset( $field_settings[$field]['type'] ) && in_array( $field_settings[$field]['type'], $allowed_field_types ) ) {
                $formatted_value = $this->format_field_value_to_text( $field_settings[$field]['type'], $value, $field_settings[$field] );
                $prompt .= "- {$field}: {$formatted_value}\n";
            }
        }
        $prompt .= "\nField Update History:\n";
        foreach ( $field_updates['activity'] as $update ) {
            // Only include updates for fields with the specified types
            $meta_key = $update['meta_key'] ?? '';
            if ( !empty( $meta_key ) && isset( $field_settings[$meta_key]['type'] ) && in_array( $field_settings[$meta_key]['type'], $allowed_field_types ) ) {
                $formatted_date = !empty( $update['hist_time'] ) ? gmdate( 'Y-m-d H:i:s', $update['hist_time'] ) : 'Unknown date';
                $user_name = $update['name'] ?? 'Unknown user';
                $activity_note = $update['object_note'] ?? 'No details';
                $prompt .= "- {$meta_key}: {$activity_note} by {$user_name} ({$formatted_date})\n";
            }
        }
        $prompt .= "\nUser Comments:\n";
        foreach ( $comments['comments'] as $comment ) {
            $prompt .= "- {$comment['comment_author']}: {$comment['comment_content']} ({$comment['comment_date']})\n";
        }

        $connection_settings = Disciple_Tools_AI_API::get_ai_connection_settings();
        $llm_endpoint_root =$connection_settings['llm_endpoint'];
        $llm_api_key = $connection_settings['llm_api_key'];
        $llm_model = $connection_settings['llm_model'];

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
                        'role' => 'system',
                        'content' => $system_prompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_completion_tokens' => 1000,
                'temperature' => 0.3,
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

        if ( isset( $params['selections'], $params['pii'], $params['inferred'] ) ) {
            return $this->handle_create_filter_with_selections_request( $post_type, $prompt, $params['selections'], $params['pii'], $params['inferred'] );
        } else {
            return $this->handle_create_filter_request( $post_type, $prompt );
        }
    }

    /**
     * Format field value based on field type for AI prompt
     */
    private function format_field_value_to_text( $field_type, $value, $field_settings ) {
        switch ( $field_type ) {
            case 'key_select':
                // Returns array with 'key' and 'label'
                return is_array( $value ) && isset( $value['label'] ) ? $value['label'] : (string) $value;

            case 'multi_select':
                //get label from field settings
                if ( is_array( $value ) && isset( $field_settings['default'] ) ) {
                    $labels = [];
                    foreach ( $value as $key ) {
                        if ( isset( $field_settings['default'][$key] ) ) {
                            $labels[] = $field_settings['default'][$key]['label'] ?? $key;
                        }
                    }
                    return implode( ', ', $labels );
                }
                // Returns array of keys
                return is_array( $value ) ? implode( ', ', $value ) : (string) $value;

            case 'date':
                // Returns array with 'timestamp' and 'formatted'
                return is_array( $value ) && isset( $value['formatted'] ) ? $value['formatted'] : (string) $value;

            case 'location':
                // Returns array of location objects with 'label'
                if ( is_array( $value ) ) {
                    $locations = [];
                    foreach ( $value as $location ) {
                        if ( is_array( $location ) && isset( $location['label'] ) ) {
                            $locations[] = $location['label'];
                        }
                    }
                    return implode( ', ', $locations );
                }
                return (string) $value;

            case 'location_meta':
                // Returns array of location meta objects
                if ( is_array( $value ) ) {
                    $meta_info = [];
                    foreach ( $value as $meta ) {
                        if ( is_array( $meta ) ) {
                            $parts = [];
                            if ( isset( $meta['lat'], $meta['lng'] ) ) {
                                $parts[] = "Coordinates: {$meta['lat']}, {$meta['lng']}";
                            }
                            if ( isset( $meta['level'] ) ) {
                                $parts[] = "Level: {$meta['level']}";
                            }
                            if ( !empty( $parts ) ) {
                                $meta_info[] = implode( ' | ', $parts );
                            }
                        }
                    }
                    return implode( '; ', $meta_info );
                }
                return (string) $value;

            case 'number':
                // Returns numeric value
                return (string) $value;

            case 'text':
                // Returns string value
                return (string) $value;
            default:
                break;
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
