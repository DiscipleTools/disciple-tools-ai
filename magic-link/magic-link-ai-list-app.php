<?php
if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly.

/**
 * Class Disciple_Tools_AI_Magic_List
 */
class Disciple_Tools_AI_Magic_List_App extends DT_Magic_Url_Base {

    public $page_title = 'AI Filtered List';
    public $page_description = 'Dynamically display AI generated filtered record lists.';
    public $root = 'ai'; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'list_app'; // @todo define the type
    public $post_type = 'user';
    private $meta_key = '';
    public $show_bulk_send = false;
    public $show_app_tile = false;

    private $default_post_type = 'contacts';
    private $default_fields = [
        'name',
        'age',
        'faith_status',
        'milestones',
        'ai_summary'
    ];

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.

    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - post_type     Magic link type post type.
         *      - contacts_only:    Boolean flag indicating how magic link type user assignments are to be handled within magic link plugin.
         *                          If True, lookup field to be provided within plugin for contacts only searching.
         *                          If false, Dropdown option to be provided for user, team or group selection.
         *      - fields:       List of fields to be displayed within magic link frontend form.
         *      - icon:         Custom font icon to be associated with magic link.
         *      - show_in_home_apps:    Boolean flag indicating if magic link should be automatically loaded and shown within Home Screen Plugin.
         */
        $this->meta = [
            'app_type'      => 'magic_link',
            'post_type'     => $this->post_type,
            'contacts_only' => false,
            'fields'        => [
                [
                    'id'    => 'name',
                    'label' => 'Name'
                ]
            ],
            'icon'           => 'mdi mdi-cog-outline',
            'show_in_home_apps' => true
        ];

        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * user_app and module section
         */
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match() ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function wp_enqueue_scripts() {
        $js_path = './assets/ai-list-app.js';
        $css_path = './assets/ai-list-app.css';

        wp_enqueue_style( 'ml-ai-list-app-css', plugin_dir_url( __FILE__ ) . $css_path, null, filemtime( plugin_dir_path( __FILE__ ) . $css_path ) );
        wp_enqueue_script( 'ml-ai-list-app-js', plugin_dir_url( __FILE__ ) . $js_path, null, filemtime( plugin_dir_path( __FILE__ ) . $js_path ) );

        $dtwc_version = '0.6.6';
        wp_enqueue_style( 'dt-web-components-css', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/src/styles/light.css", [], $dtwc_version ); // remove 'src' after v0.7
        wp_enqueue_script( 'dt-web-components-js', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/dist/index.js", $dtwc_version );
        add_filter( 'script_loader_tag', 'add_module_type_to_script', 10, 3 );
        function add_module_type_to_script( $tag, $handle, $src ) {
            if ( 'dt-web-components-js' === $handle ) {
                // @codingStandardsIgnoreStart
                $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
                // @codingStandardsIgnoreEnd
            }
            return $tag;
        }
        wp_enqueue_script( 'dt-web-components-services-js', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/dist/services.min.js", array( 'jquery' ), true ); // not needed after v0.7

        $mdi_version = '6.6.96';
        wp_enqueue_style( 'material-font-icons-css', "https://cdn.jsdelivr.net/npm/@mdi/font@$mdi_version/css/materialdesignicons.min.css", [], $mdi_version );

        if ( class_exists( 'Disciple_Tools_Bulk_Magic_Link_Sender_API' ) ) {
            Disciple_Tools_Bulk_Magic_Link_Sender_API::enqueue_magic_link_utilities_script();
        }

        dt_theme_enqueue_script( 'tribute-js', 'dt-core/dependencies/tributejs/dist/tribute.min.js', array(), true );
        dt_theme_enqueue_style( 'tribute-css', 'dt-core/dependencies/tributejs/dist/tribute.css', array() );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt-web-components-js';
        $allowed_js[] = 'dt-web-components-services-js';
        $allowed_js[] = 'ml-ai-list-app-js';
        $allowed_js[] = 'tribute-js';

        if ( class_exists( 'Disciple_Tools_Bulk_Magic_Link_Sender_API' ) ) {
            $allowed_js[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::get_magic_link_utilities_script_handle();
        }

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'material-font-icons-css';
        $allowed_css[] = 'dt-web-components-css';
        $allowed_css[] = 'ml-ai-list-app-css';
        $allowed_css[] = 'tribute-css';

        return $allowed_css;
    }

    /**
     * Builds magic link type settings payload:
     * - key:               Unique magic link type key; which is usually composed of root, type and _magic_key suffix.
     * - url_base:          URL path information to map with parent magic link type.
     * - label:             Magic link type name.
     * - description:       Magic link type description.
     * - settings_display:  Boolean flag which determines if magic link type is to be listed within frontend user profile settings.
     *
     * @param $apps_list
     *
     * @return mixed
     */
    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[ $this->meta_key ] = [
            'key'              => $this->meta_key,
            'url_base'         => $this->root . '/' . $this->type,
            'label'            => $this->page_title,
            'description'      => $this->page_description,
            'settings_display' => true
        ];

        return $apps_list;
    }

    /**
     * Writes custom styles to header
     *
     * @see DT_Magic_Url_Base()->header_style() for default state
     * @todo remove if not needed
     */
    public function header_style() {
        ?>
        <style>
            body {
                background-color: white;
                padding: 1em;
            }
        </style>
        <?php
    }

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     * @todo remove if not needed
     */
    public function header_javascript() {
        ?>
        <script>
            console.log('insert header_javascript')
        </script>
        <?php
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     * @todo remove if not needed
     */
    public function footer_javascript() {
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'default_post_type' => $this->default_post_type,
                'sys_type' => 'wp_user',
                'parts' => $this->parts,
                'translations' => [
                    'item_saved' => esc_attr__( 'Item Saved', 'disciple-tools-ai' )
                ]
            ] ) ?>][0];

            // Initialize tribute mentions search.
            init_mentions_search();

        </script>
        <?php
    }

    public function body() {
        ?>
        <main>
            <div id="list" class="is-expanded">
                <header>
                    <h1><?php echo esc_html( $this->page_title ); ?></h1>
                </header>

                <div id="search-filter">
                    <div id="search-bar">
                        <input type="text" id="search" placeholder="<?php esc_html_e( 'Describe the list to show...', 'disciple-tools-ai' ); ?>" onkeyup="show_filter_clear_option();" />
                        <button id="clear-button" style="display: none;" class="clear-button mdi mdi-close" onclick="clear_filter();"></button>
                        <button class="filter-button mdi mdi-star-four-points-outline" onclick="create_filter();"></button>
                    </div>
                </div>

                <ul id="list-items" class="items"></ul>
                <div id="spinner-div" style="justify-content: center; display: flex;">
                    <span id="temp-spinner" class="loading-spinner inactive" style="margin: 0; position: absolute; top: 50%; -ms-transform: translateY(-50%); transform: translateY(-50%); height: 100px; width: 100px; z-index: 100;"></span>
                </div>
                <template id="list-item-template">
                    <li>
                        <a href="javascript:load_post_details()">
                            <span class="post-id"></span>
                            <span class="post-title"></span>
                            <span class="post-updated-date"></span>
                        </a>
                    </li>
                </template>
            </div>
            <div id="detail" class="">

                <form>
                    <header>
                        <button type="button" class="details-toggle mdi mdi-arrow-right" onclick="toggle_panels()"></button>
                        <h2 id="detail-title"></h2>
                        <span id="detail-title-post-id"></span>
                    </header>

                    <div id="detail-content"></div>
                    <footer>
                        <dt-button onclick="save_item(event)" type="submit" context="primary"><?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?></dt-button>
                    </footer>
                </form>

                <template id="comment-header-template">
                    <div class="comment-header">
                        <span><strong id="comment-author"></strong></span>
                        <span class="comment-date" id="comment-date"></span>
                    </div>
                </template>
                <template id="comment-content-template">
                    <div class="activity-text">
                        <div dir="auto" class="" data-comment-id="" id="comment-id">
                            <div class="comment-text" title="" dir="auto" id="comment-content">
                            </div>
                        </div>
                    </div>
                </template>

                <template id="post-detail-template">
                    <input type="hidden" name="id" id="post-id" />
                    <input type="hidden" name="type" id="post-type" />

                    <dt-tile id="all-fields" open>
                        <?php
                        // ML Plugin required.
                        if ( class_exists( 'Disciple_Tools_Magic_Links_Helper' ) ) {
                            $post_field_settings = DT_Posts::get_post_field_settings( $this->default_post_type );
                            foreach ( $post_field_settings as $field_key => $field ) {
                                if ( in_array( $field_key, $this->default_fields ) ) {

                                    // display standard DT fields
                                    $post_field_settings[$field_key]['custom_display'] = false;
                                    $post_field_settings[$field_key]['readonly'] = false;

                                    Disciple_Tools_Magic_Links_Helper::render_field_for_display( $field_key, $post_field_settings, [] );
                                }
                            }
                        }
                        ?>
                    </dt-tile>

                    <dt-tile id="comments-tile" title="Comments">
                        <div>
                            <textarea id="comments-text-area"
                                      style="resize: none;"
                                      placeholder="<?php echo esc_html_x( 'Write your comment or note here', 'input field placeholder', 'disciple_tools' ) ?>"
                            ></textarea>
                        </div>
                        <div class="comment-button-container">
                            <button class="button loader" type="button" id="comment-button">
                                <?php esc_html_e( 'Submit comment', 'disciple_tools' ) ?>
                            </button>
                        </div>
                    </dt-tile>
                </template>
            </div>
            <div id="snackbar-area"></div>
            <template id="snackbar-item-template">
                <div class="snackbar-item"></div>
            </template>
        </main>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';

        register_rest_route(
            $namespace, '/' . $this->type . '/mentions_search', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'mentions_search' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/' . $this->type . '/create_filter', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'create_filter' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/' . $this->type . '/get_post', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'get_post' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/' . $this->type . '/comment', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'comment' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/'.$this->type . '/update', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'update_record' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function mentions_search( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['search'], $params['post_type'], $params['parts'], $params['action'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        $options = [];
        $search = $params['search'];
        $post_type = $params['post_type'];

        // Search users by name.
        $users = Disciple_Tools_Users::get_assignable_users_compact( $search, true, $post_type );
        foreach ( $users ?? [] as $user ) {
            if ( isset( $user['ID'], $user['name'] ) ) {
                $options[] = [
                    'id' => $user['ID'],
                    'name' => $user['name'],
                    'type' => $post_type,
                    'avatar' => $user['avatar'] ?? null
                ];
            }
        }

        // Search locations by name.
        $locations = Disciple_Tools_Mapping_Queries::search_location_grid_by_name( [
            'search_query' => $search,
            'filter' => 'all'
        ] );

        foreach ( $locations['location_grid'] ?? [] as $location ) {
            if ( isset( $location['grid_id'], $location['label'] ) ) {
                $options[] = [
                    'id' => $location['grid_id'],
                    'name' => $location['label'],
                    'type' => $post_type,
                    'avatar' => null
                ];
            }
        }

        // Return search results.
        return [
            'options' => $options
        ];
    }

    public function create_filter( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['filter'], $params['parts'], $params['action'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        $response = [];
        $prompt = $params['filter']['prompt'];
        $post_type = $params['filter']['post_type'];

        // Request inference from dt ai create filter endpoint.
        $filter = Disciple_Tools_AI_API::handle_create_filter_request( $prompt, $post_type );

        // Assuming we have a valid shape, generate required list.
        if ( !is_wp_error( $filter ) && isset( $filter['fields'] ) ) {
            $list = DT_Posts::list_posts( $post_type, [
                'fields' => $filter['fields']
            ]);

            $response = ( !is_wp_error( $list ) && isset( $list['posts'] ) ) ? $list : [];
        }

        return $response;
    }

    public function get_post( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['comment_count'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        // Fetch corresponding post object.
        $post = DT_Posts::get_post( $params['post_type'], $params['post_id'] );
        if ( empty( $post ) || is_wp_error( $post ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Assuming we have a valid hit, return along with specified comments.
        return [
            'success' => true,
            'post' => $post,
            'comments' => DT_Posts::get_post_comments( $params['post_type'], $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] )
        ];
    }

    public function comment( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['comment'], $params['comment_count'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        // Insert comment for specified post id.
        $comment_id = DT_Posts::add_post_comment( $params['post_type'], $params['post_id'], $params['comment'], 'comment', [], false );

        return [
            'success' => !is_wp_error( $comment_id ),
            'comments' => DT_Posts::get_post_comments( $params['post_type'], $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] )
        ];
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['fields'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }


        // Package field updates...
        $updates = [];
        foreach ( $params['fields']['dt'] ?? [] as $field ) {
            if ( $field['id'] === 'age' ) {
                $field['value'] = str_replace( '&lt;', '<', $field['value'] );
                $field['value'] = str_replace( '&gt;', '>', $field['value'] );
            }
            if ( isset( $field['value'] ) ) {
                switch ( $field['type'] ) {
                    case 'text':
                    case 'textarea':
                    case 'number':
                    case 'date':
                    case 'datetime':
                    case 'boolean':
                    case 'key_select':
                    case 'multi_select':
                        $updates[$field['id']] = $field['value'];
                        break;
                    case 'communication_channel':
                        $updates[$field['id']] = [
                            'values' => $field['value'],
                            'force_values' => true,
                        ];
                        break;
                    case 'location_meta':
                        $values = array_map(function ( $value ) {
                            // try to send without grid_id to get more specific location
                            if ( isset( $value['lat'], $value['lng'], $value['label'], $value['level'], $value['source'] ) ) {
                                return array_intersect_key($value, array_fill_keys([
                                    'lat',
                                    'lng',
                                    'label',
                                    'level',
                                    'source',
                                ], null));
                            }
                            return array_intersect_key($value, array_fill_keys([
                                'lat',
                                'lng',
                                'label',
                                'level',
                                'source',
                                'grid_id'
                            ], null));
                        }, $field['value'] );
                        $updates[$field['id']] = [
                            'values' => $values,
                            'force_values' => true,
                        ];
                        break;
                    default:
                        // unhandled field types
                        dt_write_log( 'Unsupported field type: ' . $field['value'] );
                        break;
                }
            }
        }

        // Execute final post field updates.
        $updated_post = DT_Posts::update_post( $params['post_type'], $params['post_id'], $updates, false, false );

        // Finally, return response accordingly by post state.
        $success = !( empty( $updated_post ) || is_wp_error( $updated_post ) );
        return [
            'success' => $success,
            'message' => '',
            'post' => $success ? $updated_post : null
        ];
    }

    public function update_user_logged_in_state( $sys_type, $user_id ) {
        switch ( strtolower( trim( $sys_type ) ) ) {
            case 'post':
                wp_set_current_user( 0 );
                $current_user = wp_get_current_user();
                $current_user->add_cap( 'magic_link' );
                $current_user->display_name = sprintf( __( '%s Submission', 'disciple_tools' ), apply_filters( 'dt_magic_link_global_name', __( 'Magic Link', 'disciple_tools' ) ) );
                break;
            default: // wp_user
                wp_set_current_user( $user_id );
                break;

        }
    }
}

Disciple_Tools_AI_Magic_List_App::instance();
