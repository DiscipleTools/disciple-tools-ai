<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_AI_Endpoints
{
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/theme-core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


    /**
     * @todo define the name of the $namespace
     * @todo define the name of the rest route
     * @todo defne method (CREATABLE, READABLE)
     * @todo apply permission strategy. '__return_true' essentially skips the permission check.
     */
    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'disciple-tools-ai/v1';

        register_rest_route(
            $namespace, '/dt-ai-summarize', [
                'methods'  => 'POST',
                'callback' => [ $this, 'summarize_endpoint' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );

        register_rest_route(
            $namespace, '/dt-ai-create-filter', [
                'methods'  => 'POST',
                'callback' => [ $this, 'create_filter_endpoint' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );
    }


    public function summarize_endpoint( WP_REST_Request $request ) {
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

        return $body['choices'][0]['message']['content'];

        return rest_ensure_response( $data );
    }

    public function create_filter_endpoint( WP_REST_Request $request ) {
        // Get the prompt from the request and make a call to the OpenAI API to summarize and return the response
        $prompt = $request->get_param( 'prompt' );
        dt_write_log($prompt);
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
                        'role' => 'system',
                        'content' => 'You are an AI designed to convert natural language search queries into structured JSON filter objects compatible with the Disciple.Tools List Query API.

API Filter Structure:

The filter object should be a JSON structure with the following possible fields:
	•	sort (string): Specifies the sorting order of the results.
	•	Options:
	•	"name": Sort by the name or title of the record.
	•	"post_date": Sort by the creation date of the record.
	•	Any field_key: Sort by a specific field.
	•	Prefix with "-" for descending order.
	•	Example: "sort": "-post_date" (sorts by creation date from newest to oldest).
	•	assigned_to (array): Filters records based on assignment.
	•	Options:
	•	"me": Records assigned to the user making the query.
	•	User ID (e.g., 83): Records assigned to a specific user.
	•	Exclusion (e.g., "-84"): Exclude records assigned to a specific user.
	•	Example: "assigned_to": ["me"] (records assigned to the current user).
	•	key_select, multi_select, tags (array): Filters based on specific keys or tags.
	•	Example: "milestones": ["milestone_has_bible", "milestone_reading_bible"] (records with specific milestones).
	•	connection (array): Filters based on connections.
	•	Example: "subassigned": [93, -23] (records subassigned to contact 93 and not subassigned to contact 23).
	•	location (array): Filters based on location grid IDs.
	•	Example: "location": [12345] (records associated with a specific location).
	•	date (object): Filters based on date fields.
	•	Structure:
	•	"field": The date field to filter on.
	•	"operator": Comparison operator (">", "<", ">=", "<=", "=", "!=").
	•	"value": The date value in YYYY-MM-DD format.
	•	Example: "date": {"field": "created_at", "operator": ">", "value": "2024-01-01"} (records created after January 1, 2024).
	•	boolean (object): Filters based on boolean fields.
	•	Structure:
	•	"field": The boolean field to filter on.
	•	"value": Boolean value (true or false).
	•	Example: "boolean": {"field": "is_active", "value": true} (records where is_active is true).
	•	number (object): Filters based on numerical fields.
	•	Structure:
	•	"field": The numerical field to filter on.
	•	"operator": Comparison operator (">", "<", ">=", "<=", "=", "!=").
	•	"value": The numerical value to compare.
	•	Example: "number": {"field": "age", "operator": ">=", "value": 18} (records where age is 18 or older).
	•	text (object): Filters based on text fields.
	•	Structure:
	•	"field": The text field to filter on.
	•	"value": The text value to match.
	•	Example: "text": {"field": "status", "value": "Active"} (records with status “Active”).

Instructions:
	1.	Analyze the user’s natural language query to determine the filtering criteria.
	2.	Construct a JSON object following the structure outlined above, ensuring it accurately represents the user’s intent.
	3.	Ensure the JSON includes only the necessary fields and values specified by the user’s request.
	4.	If the user’s intent is ambiguous, make reasonable assumptions while maintaining flexibility for further refinement.

Examples:

User Query:
“Show me all contacts assigned to John Doe with a status of Active.”

Output:
`{fields: [{"assigned_to": ["9"]},{"overall_status": ["active"]}]}`

User Query:
“Find groups created after January 1, 2024, that are tagged as ‘New Believers’.”

Output:
`{fields: [{"post_date": {"start": "2024-01-01"}},{"tags": ["New Believers"]}]}`

User Query:
“Get all contacts from Northern Cyprus who have been baptized”

Output:
`{fields: [{"location_grid": ["100380091"]},{"baptized": ["*"]}]}`

Additional Considerations:
	•	Date Formatting: Ensure dates are in YYYY-MM-DD format.
	•	Operators: Use appropriate comparison operators for date and number filters.
	•	Combining Criteria: When multiple criteria are specified, include all relevant filters in the JSON object.
	•	API Structure Compliance: Ensure the output matches the expected API structure exactly.
    •	Only return the filter object no other text.
    •	You should return an object with the key `fields` and the value as the filter object.',
                    ],
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

        return $body['choices'][0]['message']['content'];

        return rest_ensure_response( $data );
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
}
Disciple_Tools_AI_Endpoints::instance();
