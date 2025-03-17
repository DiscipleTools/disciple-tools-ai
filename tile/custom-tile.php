<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_AI_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields' ], 1, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_add_section' ], 30, 2 );
        add_action( 'dt_post_list_filters_sidebar', [ $this, 'list_filter_box' ], 10, 1 );
    }

    public function dt_site_scripts(): void {
        dt_theme_enqueue_script( 'jquery-mentions', 'dt-core/dependencies/jquery-mentions-input/jquery.mentionsInput.min.js', array( 'jquery' ), true );
        dt_theme_enqueue_script( 'jquery-mentions-elastic', 'dt-core/dependencies/jquery-mentions-input/lib/jquery.elastic.min.js', array( 'jquery' ), true );
        dt_theme_enqueue_style( 'jquery-mentions-css', 'dt-core/dependencies/jquery-mentions-input/jquery.mentionsInput.css', array() );
    }

    /**
     * This function registers a new tile to a specific post type
     *
     * @todo Set the post-type to the target post-type (i.e. contacts, groups, trainings, etc.)
     * @todo Change the tile key and tile label
     *
     * @param $tiles
     * @param string $post_type
     * @return mixed
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( in_array( $post_type, [ 'contacts', 'ai' ] ) ){
            $tiles['disciple_tools_ai'] = [ 'label' => __( 'Disciple Tools AI', 'disciple-tools-ai' ) ];
        }
        return $tiles;
    }

    /**
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = '' ) {
        return $fields;
    }

    public function dt_add_section( $section, $post_type ) {
        /**
         * @todo set the post type and the section key that you created in the dt_details_additional_tiles() function
         */
        if ( in_array( $post_type, [ 'contacts', 'ai' ] ) && $section === 'disciple_tools_ai' ){
            /**
             * These are two sets of key data:
             * $this_post is the details for this specific post
             * $post_type_fields is the list of the default fields for the post type
             *
             * You can pull any query data into this section and display it.
             */
            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            $nonce = wp_create_nonce( 'wp_rest' ); // Generate the nonce

            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    document.getElementById('dt-ai-summary-button').addEventListener('click', function(){
                        var endpoint = '<?php echo esc_url( get_option( 'disciple_tools_ai_llm_endpoint' ) ); ?>';
                        var api_key = '<?php echo esc_js( get_option( 'disciple_tools_ai_llm_api_key' ) ); ?>';
                        var nonce = '<?php echo esc_js( $nonce ); ?>'; // Pass the nonce to JavaScript

                        this.classList.add('loading');

                        const post_type = window.commentsSettings?.post?.post_type;
                        const post_id = window.commentsSettings?.post?.ID;

                        prepareDataForLLM( post_type, post_id, window.commentsSettings.comments.comments, window.commentsSettings.activity.activity, nonce );

                    });
                });

                function prepareDataForLLM(post_type, post_id, commentData, activityData, nonce) {

                    var combinedData = [];

                    commentData.forEach(function(comment){
                        combinedData.push({
                            date: comment.comment_date,
                            content: comment.comment_content,
                            type: 'comment'
                        });
                    });

                    activityData.forEach(function(activity){
                        combinedData.push({
                            date: window.moment.unix(activity.hist_time),
                            content: activity.object_note,
                            type: 'activity'
                        });
                    });

                    combinedData.sort(function(a, b){
                        return new Date(a.date) - new Date(b.date);
                    });

                    let prompt = "If comments count is less than 5, then summarize in only 20 words; otherwise, summarize in only 100 words; the following activities and comments. Prioritize comments over activities:\n\n";
                    combinedData.forEach(function(item){
                        prompt += item.date + " - " + item.type + ": " + item.content + "\n";
                    });

                    fetch(`${wpApiShare.root}disciple-tools-ai/v1/dt-ai-summarize`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce // Include the nonce in the headers
                        },
                        body: JSON.stringify({
                            prompt: prompt,
                            post_type,
                            post_id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {

                        document.querySelector('#dt-ai-summary-button').classList.remove('loading');

                        // Determine action to take based on endpoint response.
                        if ( data?.updated ) {
                            window.location.reload();

                        } else {
                            document.querySelector('#dt-ai-summary').innerText = data?.summary;
                            $('.grid').masonry('layout');
                        }

                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            </script>
            <!--
            @todo you can add HTML content to this section.
            -->
            <div class="cell small-12 medium-4">
                <!-- @todo remove this notes section-->
                <div class="dt-tile">
                    <div class="dt-tile-content">
                        <button id="dt-ai-summary-button" class="button loader" style="min-width: 100%;"><?php esc_html_e( 'Summarize This Contact', 'disciple-tools-ai' ) ?></button>
                        <p id="dt-ai-summary"></p>
                    </div>
            </div>

        <?php }
    }

    public function list_filter_box( $post_type ) {
        $this->dt_site_scripts();
        ?>
        <br>
        <div class="bordered-box">
            <div class="section-header">
                <h4><?php esc_html_e( 'Natural Language Filtering', 'disciple-tools-ai' ); ?></h4>
            </div>
            <div class="section-body">
                <label for="dt_ai_filter_prompt"><?php esc_html_e( 'Describe the list you want to see:', 'disciple-tools-ai' ); ?></label>
                <textarea id="dt_ai_filter_prompt" name="dt_ai_filter_prompt" class="mention" rows="5" style="width: 100%;"></textarea>
                <span id="dt_ai_filter_prompt_spinner" class="loading-spinner active" style="display: none;"></span>
                <button id="dt_ai_filter_prompt_button" class="button" style="min-width: 100%;"><?php esc_html_e( 'Create Filter', 'disciple-tools-ai' ); ?></button>
            </div>
        </div>
        <script>
            jQuery(document).ready(function ($) {

                let settings = [<?php echo json_encode([
                    'post_type' => $post_type,
                    'root' => esc_url_raw( rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'translations' => [
                        'custom_filter' => __('Custom AI Filter', 'disciple-tools-ai')
                    ]
                ]) ?>][0]

                let searchUsersPromise = null;
                $('#dt_ai_filter_prompt').mentionsInput({
                    onDataRequest: (mode, query, callback) => {
                        if (searchUsersPromise && window.lodash.get(searchUsersPromise, 'readyState') !== 4) {
                            searchUsersPromise.abort('abortPromise');
                        }

                        searchUsersPromise = window.API.search_users(query);

                        searchUsersPromise
                            .then((responseData) => {

                                let data = [];
                                responseData.forEach((user) => {
                                    data.push({
                                        id: user.ID,
                                        name: user.name,
                                        type: settings.post_type,
                                        avatar: user.avatar,
                                    });
                                    callback.call(this, data);
                                });
                            })
                            .catch((err) => {
                                console.error(err);
                            });
                    },
                    templates: {
                        mentionItemSyntax: function (data) {
                            return `[${data.value}](${data.id})`;
                        }
                    },
                    showAvatars: true,
                    minChars: 0
                });

                const getMentionedUsers = (callback) => {
                    $('#dt_ai_filter_prompt').mentionsInput('getMentions', function (data) {
                        callback(data);
                    });
                };

                const getCommentWithMentions = (callback) => {
                    $('#dt_ai_filter_prompt').mentionsInput('val', function (text) {
                        callback(text);
                    });
                };

                const create_filter_spinner = $('#dt_ai_filter_prompt_spinner');
                document.querySelector('#dt_ai_filter_prompt_button').addEventListener('click', (e) => {
                    e.preventDefault();

                    getCommentWithMentions((data) => {
                        if (data) {
                            console.log(data);

                            create_filter_spinner.fadeIn('slow');

                            fetch(`${wpApiShare.root}disciple-tools-ai/v1/dt-ai-create-filter`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': settings.nonce // Include the nonce in the headers
                                },
                                body: JSON.stringify({
                                    prompt: data,
                                    post_type: settings.post_type
                                })
                            })
                            .then(response => response.json())
                            .then(response => {
                                response = JSON.parse(response);

                                console.log(response);

                                create_filter_spinner.fadeOut('fast');

                                /**
                                 * Assuming valid fields have been generated and required shared
                                 * functions are present, proceed with custom filter creation and
                                 * list refresh.
                                 */

                                if (response?.fields && window.SHAREDFUNCTIONS?.add_custom_filter && window.SHAREDFUNCTIONS?.reset_split_by_filters) {

                                    /**
                                     * First, attempt to identify labels to be used based on returned
                                     * fields shape; otherwise, labels shall remain blank.
                                     */

                                    let labels = [];
                                    if (Array.isArray(response.fields) && window.SHAREDFUNCTIONS?.create_name_value_label) {
                                        response.fields.forEach((field) => {
                                            for (const [key, filters] of Object.entries(field)) {

                                                if (key && Array.isArray(filters)) {
                                                    filters.forEach((filter) => {

                                                        const {newLabel} = window.SHAREDFUNCTIONS?.create_name_value_label(key, filter, isNaN(filter) ? filter : '', window?.list_settings);
                                                        if (newLabel) {
                                                            labels.push(newLabel);
                                                        }

                                                    });
                                                }
                                            }
                                        });
                                    }

                                    console.log(labels);

                                    /**
                                     * Proceed with Custom AI Filter creation and list refresh.
                                     */

                                    window.SHAREDFUNCTIONS.reset_split_by_filters();
                                    window.SHAREDFUNCTIONS.add_custom_filter(
                                        settings.translations['custom_filter'],
                                        'custom-filter',
                                        {
                                            fields: response.fields
                                        },
                                        labels
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
Disciple_Tools_AI_Tile::instance();
