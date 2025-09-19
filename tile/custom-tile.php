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
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields' ], 100, 2 );
        add_action( 'dt_record_top_above_details', [ $this, 'dt_record_top_above_details' ], 10, 2 );
        add_action( 'archive_template_action_bar_buttons', [ $this, 'archive_template_action_bar_buttons' ], 5, 1 );
        add_action( 'archive_template_mobile_action_bar_buttons', [ $this, 'archive_template_mobile_action_bar_buttons' ], 5, 1 );
    }

    public function dt_site_scripts(): void {
    }

    /**
     * Register the ai_summary custom field
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = '' ) {
        $fields['ai_summary_array'] = [
            'name' => __( 'AI Summary', 'disciple-tools-ai' ),
            'type' => 'array',
            //'hidden' => true, // Hide from normal field display since we show it in custom section
        ];
        return $fields;
    }

    /**
     * Render AI summary section at the top of contact pages
     * @param string $post_type
     * @param array $dt_post
     */
    public function dt_record_top_above_details( $post_type, $dt_post ) {

        // Check if AI summarization module is enabled
        if ( Disciple_Tools_AI_API::has_module_value( Disciple_Tools_AI_API::$module_default_id_dt_ai_summarization, 'enabled', 0 ) ) {
            return;
        }

        $ai_summary_raw = $dt_post['ai_summary_array'] ?? [];
        if ( !empty( $ai_summary_raw ) && is_string( $ai_summary_raw ) ) {
            $ai_summary_raw = [ 'en_US' => $ai_summary_raw ];
        }

        $language_keys = array_keys( $ai_summary_raw ?? [] );
        $available_languages = dt_get_available_languages( true, false, $language_keys );
        $user_locale = get_locale();

       
        $ai_summary_entries = [];

        if ( is_array( $ai_summary_raw ) ) {
            foreach ( $ai_summary_raw as $language_code => $summary_text ) {
                if ( !is_string( $summary_text ) || '' === trim( $summary_text ) || empty( $language_code ) ) {
                    continue;
                }
                $ai_summary_entries[] = [
                    'code'  => $language_code,
                    'label' => $available_languages[$language_code]['flag'] . ' ' . ( $available_languages[$language_code]['root_name'] ?? $available_languages[$language_code]['native_name'] ),
                    'text'  => $summary_text,
                    'dir' => $available_languages[$language_code]['rtl'] ? 'rtl' : 'ltr',
                ];
            }
        }

        $has_ai_summary = !empty( $ai_summary_entries );
        $active_locale = $user_locale;
        if ( $has_ai_summary ) {
            $normalized_user_local = explode( '_', $user_locale )[0] ?? $user_locale;
            $entry_codes = array_column( $ai_summary_entries, 'code' );
            if ( in_array( $normalized_user_local, $entry_codes, true ) ) {
                $active_locale = $normalized_user_local;
            } else {
                if ( !in_array( $active_locale, $entry_codes, true )  ) {
                    $active_locale = $entry_codes[0] ?? $active_locale;
                }
            }
        }
        $generate_label = __( 'Generate', 'disciple-tools-ai' );
        $regenerate_label = __( 'Re-generate', 'disciple-tools-ai' );
        $button_label = $has_ai_summary ? $regenerate_label : $generate_label;
        ?>
        <style>
            .ai-summary-inline {
                border-left: 3px solid #007cba;
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                padding: 12px 16px;
                margin-bottom: 12px;
                position: relative;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .25);
                border-radius: 5px;
            }
            .ai-summary-inline::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 40px;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(0, 124, 186, 0.05));
                pointer-events: none;
            }
            .ai-summary-row {
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            .ai-summary-icon {
                flex-shrink: 0;
                width: 20px;
                height: 20px;
                background: #007cba;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 2px;
            }
            .ai-summary-icon::after {
                content: '✨';
                font-size: 10px;
                color: white;
            }
            .ai-summary-main {
                flex-grow: 1;
                min-width: 0;
            }
            .ai-summary-header-inline {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 6px;
            }
            .ai-summary-header-main {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: 1 1 auto;
                flex-wrap: wrap;
                min-width: 0;
            }
            .ai-summary-label {
                font-size: 12px;
                font-weight: 600;
                color: #007cba;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin: 0;
            }
            .ai-summary-button-inline {
                font-size: 11px;
                padding: 3px 8px;
                min-height: auto;
                line-height: 1.2;
                border-radius: 12px;
                background: #007cba;
                color: white;
                border: none;
                transition: all 0.2s ease;
            }
            .ai-summary-button-inline:hover {
                background: #005a87;
                transform: translateY(-1px);
            }
            button.loader:not(.disabled).loading {
                padding-right: 22px !important;
            }
            .ai-summary-button-inline.loading::before {
                width: 12px !important;
                height: 12px !important;
                margin: -6px !important;
            }
            .ai-summary-text {
                color: #495057;
                line-height: 1.5;
                font-size: 14px;
                margin: 0;
                font-style: italic;
            }
            .ai-summary-text:empty::before {
                content: attr(data-placeholder);
                color: #9ca3af;
                font-style: italic;
            }
            .ai-summary-text:not(:empty) {
                font-style: normal;
            }
            .ai-summary-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 0;
            }
            .ai-summary-tab-button {
                font-size: 12px;
                padding: 4px 10px;
                border-radius: 12px;
                border: 1px solid #007cba;
                background: #ffffff;
                color: #007cba;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .ai-summary-tab-button:hover {
                background: rgba(0, 124, 186, 0.08);
            }
            .ai-summary-tab-button.is-active {
                background: #007cba;
                color: white;
            }
            .ai-summary-panel {
                display: none;
            }
            .ai-summary-panel.is-active {
                display: block;
            }
            .is-hidden {
                display: none !important;
            }
        </style>

        <section class="cell small-12 ai-summary-inline">
            <div class="ai-summary-row">
                <div class="ai-summary-icon"></div>
                <div class="ai-summary-main" id="dt-ai-summary-root">
                    <div class="ai-summary-header-inline">
                        <div class="ai-summary-header-main">
                            <span class="ai-summary-label"><?php esc_html_e( 'AI Insights', 'disciple-tools-ai' ); ?></span>
                            <div id="dt-ai-summary-tabs" class="ai-summary-tabs<?php echo $has_ai_summary ? '' : ' is-hidden'; ?>" role="tablist" aria-label="<?php esc_attr_e( 'AI summary languages', 'disciple-tools-ai' ); ?>">
                                <?php foreach ( $ai_summary_entries as $index => $entry ) :
                                    $tab_slug = sanitize_html_class( strtolower( str_replace( [ ' ', ':' ], '-', $entry['code'] ) ) );
                                    if ( '' === $tab_slug ) {
                                        $tab_slug = 'lang-' . substr( md5( $entry['code'] ), 0, 6 );
                                    }
                                    $tab_id = 'ai-summary-tab-' . $tab_slug;
                                    $panel_id = $tab_id . '-panel';
                                    $is_active = $entry['code'] === $active_locale;
                                    ?>
                                    <button
                                        type="button"
                                        id="<?php echo esc_attr( $tab_id ); ?>"
                                        class="ai-summary-tab-button<?php echo $is_active ? ' is-active' : ''; ?>"
                                        data-lang="<?php echo esc_attr( $entry['code'] ); ?>"
                                        role="tab"
                                        aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                        tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                    >
                                        <?php echo esc_html( $entry['label'] ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button id="dt-ai-summary-button" class="ai-summary-button-inline loader" data-default-label="<?php echo esc_attr( $button_label ); ?>">
                            <?php echo esc_html( $button_label ); ?>
                        </button>
                    </div>
                    <div id="dt-ai-summary-panels" class="ai-summary-panels<?php echo $has_ai_summary ? '' : ' is-hidden'; ?>">
                        <?php foreach ( $ai_summary_entries as $index => $entry ) :
                            $tab_slug = sanitize_html_class( strtolower( str_replace( [ ' ', ':' ], '-', $entry['code'] ) ) );
                            if ( '' === $tab_slug ) {
                                $tab_slug = 'lang-' . substr( md5( $entry['code'] ), 0, 6 );
                            }
                            $tab_id = 'ai-summary-tab-' . $tab_slug;
                            $panel_id = $tab_id . '-panel';
                            $is_active = $entry['code'] === $active_locale;
                            ?>
                            <div
                                id="<?php echo esc_attr( $panel_id ); ?>"
                                class="ai-summary-text ai-summary-panel<?php echo $is_active ? ' is-active' : ''; ?>"
                                data-lang="<?php echo esc_attr( $entry['code'] ); ?>"
                                role="tabpanel"
                                aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
                                aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
                                dir="<?php echo esc_attr( $entry['dir'] ); ?>"
                            >
                                <?php echo esc_html( $entry['text'] ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p id="dt-ai-summary-placeholder" class="ai-summary-text<?php echo $has_ai_summary ? ' is-hidden' : ''; ?>" data-placeholder="<?php esc_attr_e( 'Generate an AI summary to see key insights about this contact...', 'disciple-tools-ai' ); ?>" dir="auto"></p>
                </div>
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function(){
                const aiSummaryButton = document.getElementById('dt-ai-summary-button');
                if (aiSummaryButton) {
                    aiSummaryButton.addEventListener('click', function(){
                        this.classList.add('loading');
                        this.textContent = '<?php esc_html_e( 'Working...', 'disciple-tools-ai' ); ?>';
                        const post_type = window.commentsSettings?.post?.post_type;
                        const post_id = window.commentsSettings?.post?.ID;
                        prepareDataForLLM( post_type, post_id );
                    });
                }

                const tabsContainer = document.getElementById('dt-ai-summary-tabs');
                const panelsContainer = document.getElementById('dt-ai-summary-panels');
                const setActiveTab = (languageCode) => {
                    if (!tabsContainer || !panelsContainer) {
                        return;
                    }

                    tabsContainer.querySelectorAll('[role="tab"]').forEach((tab) => {
                        const isActive = tab.dataset.lang === languageCode;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        tab.setAttribute('tabindex', isActive ? '0' : '-1');
                    });

                    panelsContainer.querySelectorAll('[role="tabpanel"]').forEach((panel) => {
                        const isActive = panel.dataset.lang === languageCode;
                        panel.classList.toggle('is-active', isActive);
                        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    });
                };

                const defaultLocale = '<?php echo esc_js( $active_locale ); ?>';

                if (tabsContainer) {
                    if (defaultLocale) {
                        setActiveTab(defaultLocale);
                    }
                    tabsContainer.addEventListener('click', (event) => {
                        const tab = event.target.closest('[role="tab"]');
                        if (!tab || !tabsContainer.contains(tab)) {
                            return;
                        }
                        setActiveTab(tab.dataset.lang);
                    });
                }
            });

            function showAiSummaryMessage(message) {
                const root = document.getElementById('dt-ai-summary-root');
                if (!root) {
                    return;
                }

                const tabsContainer = root.querySelector('#dt-ai-summary-tabs');
                const panelsContainer = root.querySelector('#dt-ai-summary-panels');
                const placeholder = root.querySelector('#dt-ai-summary-placeholder');

                if (tabsContainer) {
                    tabsContainer.classList.add('is-hidden');
                }
                if (panelsContainer) {
                    panelsContainer.classList.add('is-hidden');
                }
                if (placeholder) {
                    placeholder.textContent = message;
                    placeholder.classList.remove('is-hidden');
                }
            }

            function prepareDataForLLM(post_type, post_id) {
                fetch(`${wpApiShare.root}disciple-tools-ai/v1/dt-ai-summarize`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.wpApiShare.nonce // Include the nonce in the headers
                    },
                    body: JSON.stringify({
                        post_type,
                        post_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const button = document.getElementById('dt-ai-summary-button');
                    button.classList.remove('loading');
                    button.dataset.defaultLabel = '<?php echo esc_js( $regenerate_label ); ?>';
                    button.textContent = button.dataset.defaultLabel;

                    if ( data?.data?.status === 401 && data?.message ) {
                        showAiSummaryMessage( data.message );
                        return;
                    }

                    window.location.reload();

                })
                .catch(error => {
                    console.error('Error:', error);
                    const button = document.getElementById('dt-ai-summary-button');
                    button.classList.remove('loading');
                    button.textContent = button.dataset.defaultLabel || '<?php echo esc_js( $generate_label ); ?>';
                    showAiSummaryMessage( 'Error generating summary. Please try again.' );
                });
            }
        </script>
        <?php
    }

    public function archive_template_action_bar_buttons( $post_type ): void {
        $this->display_archive_template_action_bar_buttons( $post_type );
    }

    public function archive_template_mobile_action_bar_buttons( $post_type ): void {
        $this->display_archive_template_action_bar_buttons( $post_type, true );
    }

    public function display_archive_template_action_bar_buttons( $post_type, $is_mobile = false ): void {
        if ( Disciple_Tools_AI_API::has_module_value( Disciple_Tools_AI_API::$module_default_id_dt_ai_list_filter, 'enabled', 0 ) ) {
            return;
        }
        $ai_prompt_suffix = $is_mobile ? 'mobile' : 'desktop';
        ?>
        <style>
            /* ===== Search & Filter ===== */
            #ai-search-filter {
            }

            #ai-search-filter:not(:has(.filters)) {
            }

            #ai-search-filter:has(.filters.hidden) {
            }

            #ai-search-bar {
                width: 100%;
            }

            #ai-search-filter,
            #ai-search-bar {
                background: #fff;
            }

            #ai-search-bar {
                position: relative;
                display: flex;
                flex-wrap: wrap;
                z-index: 1;
            }

            #ai-search {
                margin: var(--search-margin-block);
                flex-grow: 1;
                min-width: 1rem;
                font-size: 1.25rem;
                padding-left: 10px;
                padding-right: 10px;
                height: var(--search-input-height);
            }

            #ai-search-bar button.ai-clear-button {
                position: absolute;
                inset-inline-end: 0.5rem;
                top: 0.99rem;
                border: 0;
                background-color: #FFFFFF;
                padding: 2px;
                height: var(--search-input-height);
            }

            /* ===== Search & Filter ===== */
        </style>

        <div id="ai-search-filter">
            <button id="ai_prompt_button_<?php echo esc_attr( $ai_prompt_suffix ) ?>" class="button no-margin icon-button" style="padding: 0.1rem 0.75rem;min-height: 100%;" onclick="show_ai_prompt_modal('<?php echo esc_attr( $ai_prompt_suffix ) ?>');">
                <i id="ai_prompt_icon_<?php echo esc_attr( $ai_prompt_suffix ) ?>" class="mdi mdi-large mdi-star-four-points-outline" style="font-size: large;"></i>
                <span style="<?php echo ( !$is_mobile ? esc_attr( 'margin-left: 0.5rem;' ) : '' ) ?>">
                    <?php
                    if ( !$is_mobile ) {
                        esc_html_e( 'Search or Filter', 'disciple-tools-ai' );
                    }
                    ?>
                </span>
                <span id="ai_prompt_spinner_<?php echo esc_attr( $ai_prompt_suffix ) ?>" style="display: none; height: 16px; width: 16px; <?php echo ( !$is_mobile ? esc_attr( 'margin-left: 0.5rem;' ) : '' ) ?>" class="loading-spinner active"></span>
            </button>
        </div>

        <script>
            jQuery(document).ready(function ($) {

                let settings = [<?php echo json_encode([
                    'post_type' => $post_type,
                    'settings' => DT_Posts::get_post_settings( $post_type, false ),
                    'root' => esc_url_raw( rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'ai_prompt_suffix' => esc_attr( $ai_prompt_suffix ),
                    'translations' => [
                        'custom_filter' => __( 'Custom AI Filter', 'disciple-tools-ai' ),
                        'text_search_prefix' => __( 'Search', 'disciple-tools-ai' ),
                        'ai_prompt' => [
                            'title' => __( 'AI Search', 'disciple-tools-ai' ),
                            'prompt_placeholder' => __( 'Describe list to show...', 'disciple-tools-ai' ),
                            'search' => __( 'Search', 'disciple-tools-ai' ),
                            'close_but' => __( 'Close', 'disciple-tools-ai' )
                        ],
                        'multiple_options' => [
                            'title' => __( 'Multiple Options Detected', 'disciple-tools-ai' ),
                            'locations' => __( 'Locations', 'disciple-tools-ai' ),
                            'users' => __( 'Users', 'disciple-tools-ai' ),
                            'posts' => __( 'Posts', 'disciple-tools-ai' ),
                            'ignore_option' => __( '-- Ignore --', 'disciple-tools-ai' ),
                            'submit_but' => __( 'Submit', 'disciple-tools-ai' ),
                            'close_but' => __( 'Close', 'disciple-tools-ai' )
                        ]
                    ]
                ]) ?>][0]

                /**
                * Hide default advanced search wrapper options.
                */

                $('#search').hide();
                $('.search-wrapper').hide();
                $('#open-search').hide();

                /**
                 * Proceed with AI filter prompt setup.
                 */

                window.show_ai_filter_clear_option = () => {
                    const text = document.getElementById('ai-search').value;
                    const clear_button = document.getElementById('ai-clear-button');

                    if (!text && clear_button.style.display === 'block') {
                        clear_button.setAttribute('style', 'display: none;');

                    } else if (text && clear_button.style.display === 'none') {
                        clear_button.setAttribute('style', 'display: block;');
                    }
                }

                window.clear_ai_filter = () => {
                    document.getElementById('ai-search').value = '';
                }

                window.create_ai_filter = (text, id_suffix) => {
                    if (!text) {
                        return;
                    }

                    settings['ai_prompt_suffix'] = id_suffix;
                    const dt_ai_filter_prompt_spinner = $(`#ai_prompt_spinner_${settings.ai_prompt_suffix}`);
                    const dt_ai_filter_prompt_button = $(`#ai_prompt_icon_${settings.ai_prompt_suffix}`);

                    dt_ai_filter_prompt_button.fadeOut('fast', () => {
                        dt_ai_filter_prompt_spinner.fadeIn('slow', () => {

                            fetch(`${wpApiShare.root}disciple-tools-ai/v1/dt-ai-create-filter`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': settings.nonce // Include the nonce in the headers
                                },
                                body: JSON.stringify({
                                    prompt: text,
                                    post_type: settings.post_type
                                })
                            })
                            .then(response => response.json())
                            .then(response => {
                                console.log(response);

                                /**
                                 * Pause the flow accordingly, if multiple connection options are available.
                                 * If so, then display modal with connection options.
                                 */

                                if (response?.status === 'error') {
                                    alert( response?.message );

                                    document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'none';
                                    document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'inline-block';

                                } else if ((response?.status === 'multiple_options_detected') && (response?.multiple_options)) {
                                    window.show_multiple_options_modal(response.multiple_options, response?.pii, response?.inferred);

                                } else if ((response?.status === 'success') && (response?.filter)) {

                                    create_custom_filter(response.filter, response?.inferred, response?.text_search);

                                    // Stop spinning....
                                    document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'none';
                                    document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'inline-block';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);

                                dt_ai_filter_prompt_spinner.fadeOut('fast', () => {
                                    dt_ai_filter_prompt_button.fadeIn('slow');
                                    if ( window.SHAREDFUNCTIONS?.empty_list ) {
                                        window.SHAREDFUNCTIONS.empty_list();
                                    }
                                });
                            });

                        });
                    });
                }

                window.show_ai_prompt_modal = (id_suffix) => {
                    const modal = $('#modal-large');
                    if (modal) {
                        $(modal).find('#modal-large-title').html(`${window.lodash.escape(settings.translations.ai_prompt.title)}`);

                        // Add HTML to modal.
                        const html = `
                            <div id="ai-search-bar">
                                <input type="text" id="ai-search" placeholder="${window.lodash.escape(settings.translations.ai_prompt.prompt_placeholder)}" />
                                <button id="ai-clear-button" style="display: none;" class="ai-clear-button mdi mdi-close" onclick="clear_ai_filter();"></button>
                            </div>
                            <br>
                            <p>
                               Examples:
                               <ul>
                                <li>Contacts in Spain</li>
                                <li>Active contacts</li>
                                <li>Assigned to John</li>
                                <li>Groups created this year</li>
                               </ul>
                            </p>
                            <div style="display: flex; justify-content: space-between;">
                                <button class="button" data-close aria-label="submit" type="button" style="background-color: #f2f2f2; color: #000;">
                                    <span aria-hidden="true">${window.lodash.escape(settings.translations.ai_prompt.close_but)}</span>
                                </button>
                                <button class="button" aria-label="submit" type="button" id="ai_prompt_submit">
                                    <span aria-hidden="true">${window.lodash.escape(settings.translations.ai_prompt.search)}</span>
                                </button>
                            </div>
                        `;
                        $(modal).find('#modal-large-content').html(html);


                        // Add event listeners to modal.
                        $(document).off('open.zf.reveal', '[data-reveal]'); // Remove existing modal open listeners
                        //auto focus on the search field
                        $(document).on('open.zf.reveal', '[data-reveal]', function (evt) {
                            document.getElementById('ai-search').focus();
                        });

                        $(document).on('open.zf.reveal', '[data-reveal]', function (evt) {
                            document.getElementById('ai-search').addEventListener('keyup', function(e) {
                                e.preventDefault();

                                if (e.key === 'Enter') { // Enter key pressed.
                                    window.handle_ai_prompt_submit(modal, id_suffix);

                                } else { // Manage field clearing option.
                                    window.show_ai_filter_clear_option();
                                }
                            });
                        });

                        // Remove existing modal close listeners
                        $(document).off('closed.zf.reveal', '[data-reveal]');
                        $(document).on('closed.zf.reveal', '[data-reveal]', function (evt) {
                            console.log('closed', evt);
                            // Remove click event listener, to avoid a build-up of event listeners.
                            $(document).off('click', '#ai_prompt_submit');
                            $('#ai_prompt_submit').off('click');
                        });

                        // Remove any existing event listeners first to prevent duplicates
                        $(document).off('click', '#ai_prompt_submit');
                        $('#ai_prompt_submit').off('click');
                        $(document).on('click', '#ai_prompt_submit', function (evt) {
                            window.handle_ai_prompt_submit(modal, id_suffix);
                        });

                        // Open modal.
                        $(modal).foundation('open');
                        $(modal).css('top', '150px');
                    }
                }

                window.handle_ai_prompt_submit = (modal, id_suffix) => {
                    window.create_ai_filter( document.getElementById('ai-search').value, id_suffix );

                    // Close modal.
                    $(modal).foundation('close');
                }

                window.show_multiple_options_modal = (multiple_options, pii, inferred) => {
                    const modal = $('#modal-small');
                    if (modal) {

                        $(modal).find('#modal-small-title').html(`${window.lodash.escape(settings.translations.multiple_options.title)}`);

                        /**
                         * Location Options.
                         */

                        let locations_html = '';
                        if (multiple_options?.locations && multiple_options.locations.length > 0) {

                            locations_html += `
                                <h4>${window.lodash.escape(settings.translations.multiple_options.locations)}</h4>
                                <table class="widefat striped">
                                    <tbody class="ai-locations">
                              `;

                            multiple_options.locations.forEach((location) => {
                                if (location?.prompt && location?.options) {
                                    locations_html += `
                                        <tr>
                                          <td style="vertical-align: top;">
                                            ${window.lodash.escape(location.prompt)}
                                            <input class="prompt" type="hidden" value="${location.prompt}" />
                                          </td>
                                          <td>
                                            <select class="options">`;

                                    locations_html += `<option value="ignore">${window.lodash.escape(settings.translations.multiple_options.ignore_option)}</option>`;

                                    location.options.forEach((option) => {
                                        if (option?.id && option?.label) {
                                            locations_html += `<option value="${window.lodash.escape(option.id)}">${window.lodash.escape(option.label)}</option>`;
                                        }
                                    });

                                    locations_html += `</select>
                                      </td>
                                    </tr>
                                    `;
                                }
                            });

                            locations_html += `
                                </tbody>
                            </table>
                            `;
                        }

                        /**
                         * User Options.
                         */

                        let users_html = '';
                        if (multiple_options?.users && multiple_options.users.length > 0) {

                            users_html += `
                                <h4>${window.lodash.escape(settings.translations.multiple_options.users)}</h4>
                                <table class="widefat striped">
                                    <tbody class="ai-users">
                            `;

                            multiple_options.users.forEach((user) => {
                                if (user?.prompt && user?.options) {
                                    users_html += `
                                        <tr>
                                          <td style="vertical-align: top;">
                                            ${window.lodash.escape(user.prompt)}
                                            <input class="prompt" type="hidden" value="${user.prompt}" />
                                          </td>
                                          <td>
                                            <select class="options">`;

                                    users_html += `<option value="ignore">${window.lodash.escape(settings.translations.multiple_options.ignore_option)}</option>`;

                                    user.options.forEach((option) => {
                                        if (option?.id && option?.label) {
                                            users_html += `<option value="${window.lodash.escape(option.id)}">${window.lodash.escape(option.label)}</option>`;
                                        }
                                    });

                                    users_html += `</select>
                                        </td>
                                    </tr>
                                    `;
                                }
                            });

                            users_html += `
                                </tbody>
                            </table>
                            `;
                        }

                        /**
                         * Post Options.
                         */

                        let posts_html = '';
                        if (multiple_options?.posts && multiple_options.posts.length > 0) {

                            posts_html += `
                                <h4>${window.lodash.escape(settings.translations.multiple_options.posts)}</h4>
                                <table class="widefat striped">
                                    <tbody class="ai-posts">
                            `;

                            multiple_options.posts.forEach((post) => {
                                if (post?.prompt && post?.options) {
                                    posts_html += `
                                        <tr>
                                          <td style="vertical-align: top;">
                                            ${window.lodash.escape(post.prompt)}
                                            <input class="prompt" type="hidden" value="${post.prompt}" />
                                          </td>
                                          <td>
                                            <select class="options">`;

                                    posts_html += `<option value="ignore">${window.lodash.escape(settings.translations.multiple_options.ignore_option)}</option>`;

                                    post.options.forEach((option) => {
                                        if (option?.id && option?.label) {
                                            posts_html += `<option value="${window.lodash.escape(option.id)}">${window.lodash.escape(option.label)}</option>`;
                                        }
                                    });

                                    posts_html += `</select>
                                        </td>
                                    </tr>
                                    `;
                                }
                            });

                            posts_html += `
                                    </tbody>
                                </table>
                            `;
                        }

                        let html = `
                            <br>
                            ${locations_html}
                            <br>
                            ${users_html}
                            <br>
                            ${posts_html}
                            <br>

                            <div style="display: flex; justify-content: space-between;">
                                <button class="button" data-close aria-label="submit" type="button" style="background-color: #f2f2f2; color: #000;">
                                    <span aria-hidden="true">${window.lodash.escape(settings.translations.multiple_options.close_but)}</span>
                                </button>
                                <button class="button" aria-label="submit" type="button" id="multiple_options_submit">
                                    <span aria-hidden="true">${window.lodash.escape(settings.translations.multiple_options.submit_but)}</span>
                                </button>
                            </div>
                            <input id="multiple_options_inferred" type="hidden" value="${encodeURIComponent( JSON.stringify(inferred) )}" />
                            <input id="multiple_options_pii" type="hidden" value="${encodeURIComponent( JSON.stringify(pii) )}" />
                        `;

                        $(modal).find('#modal-small-content').html(html);

                        $(modal).foundation('open');
                        $(modal).css('top', '150px');

                        $(document).on('closed.zf.reveal', '[data-reveal]', function (evt) {
                            document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'none';
                            document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'inline-block';

                            // Remove click event listener, to avoid a build-up and duplication of modal selection submissions.
                            $(document).off('click', '#multiple_options_submit');
                        });

                        $(document).on('click', '#multiple_options_submit', function (evt) {
                            window.handle_multiple_options_submit(modal);
                        });
                    }
                }

                window.handle_multiple_options_submit = (modal) => {

                    // Re-submit query, with specified selections.
                    const payload = {
                        "prompt": document.getElementById('ai-search').value,
                        "post_type": settings.post_type,
                        "selections": window.package_multiple_options_selections(),
                        "inferred": JSON.parse( decodeURIComponent( document.getElementById('multiple_options_inferred').value ) ),
                        "pii": JSON.parse( decodeURIComponent( document.getElementById('multiple_options_pii').value ) )
                    };

                    // Close modal and proceed with re-submission.
                    $(modal).foundation('close');

                    // Ensure spinner is still spinning.
                    document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'inline-block';
                    document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'none';

                    // Submit selections.
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(payload),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: `${wpApiShare.root}disciple-tools-ai/v1/dt-ai-create-filter`,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', settings.nonce);
                        }
                    })
                    .done(function (data) {
                        console.log(data);

                        // If successful, load points.
                        if ((data?.status === 'success') && (data?.filter)) {
                            create_custom_filter(data.filter, data?.inferred, data?.text_search);

                        } else if (data?.status === 'error') {
                            alert( data?.message );

                        }

                        // Stop spinning....
                        document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'none';
                        document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'inline-block';

                    })
                    .fail(function (err) {
                        console.log('error')
                        console.log(err)

                        document.getElementById(`ai_prompt_spinner_${settings.ai_prompt_suffix}`).style.display = 'none';
                        document.getElementById(`ai_prompt_icon_${settings.ai_prompt_suffix}`).style.display = 'inline-block';
                    });
                }

                window.package_multiple_options_selections = () => {
                    let selections = {};

                    /**
                     * Locations.
                     */

                    const locations = $('tbody.ai-locations');
                    if (locations) {
                        selections['locations'] = [];
                        $(locations).find('tr').each((idx, tr) => {
                            const prompt = $(tr).find('input.prompt').val();
                            const selected_opt_id = $(tr).find('select.options option:selected').val();
                            const selected_opt_label = $(tr).find('select.options option:selected').text();

                            selections['locations'].push({
                                'prompt': prompt,
                                'id': selected_opt_id,
                                'label': selected_opt_label
                            });
                        });
                    }

                    /**
                     * Users.
                     */

                    const users = $('tbody.ai-users');
                    if (users) {
                        selections['users'] = [];
                        $(users).find('tr').each((idx, tr) => {
                            const prompt = $(tr).find('input.prompt').val();
                            const selected_opt_id = $(tr).find('select.options option:selected').val();
                            const selected_opt_label = $(tr).find('select.options option:selected').text();

                            selections['users'].push({
                                'prompt': prompt,
                                'id': selected_opt_id,
                                'label': selected_opt_label
                            });
                         });
                    }

                    /**
                     * Posts.
                     */

                    const posts = $('tbody.ai-posts');
                    if (posts) {
                        selections['posts'] = [];
                        $(posts).find('tr').each((idx, tr) => {
                            const prompt = $(tr).find('input.prompt').val();
                            const selected_opt_id = $(tr).find('select.options option:selected').val();
                            const selected_opt_label = $(tr).find('select.options option:selected').text();

                            selections['posts'].push({
                                'prompt': prompt,
                                'id': selected_opt_id,
                                'label': selected_opt_label
                            });
                        });
                    }

                    return selections;
                }

                window.create_custom_filter = (filter, inferred_fields, text_search = null) => {

                    /**
                     * Assuming valid fields have been generated and required shared
                     * functions are present, proceed with custom filter creation and
                     * list refresh.
                     */

                    if (inferred_fields && window.SHAREDFUNCTIONS?.add_custom_filter && window.SHAREDFUNCTIONS?.reset_split_by_filters) {

                        /**
                         * First, attempt to identify labels to be used based on returned
                         * fields shape; otherwise, labels shall remain blank.
                         */

                        let labels = [];
                        if (text_search) {
                            labels.push({ id: 'text_search', name: `${settings.translations.text_search_prefix}: ${text_search}`, field: 'name' });
                        } else if (Array.isArray(inferred_fields) && window.SHAREDFUNCTIONS?.create_name_value_label) {
                            inferred_fields.forEach((field) => {
                                if (field?.field_key && field?.field_value) {
                                    const {field_key, field_value} = field;
                                    let array_field_values = !Array.isArray(field_value) ? [field_value] : field_value;

                                    array_field_values.forEach((value) => {
                                        const {newLabel} = window.SHAREDFUNCTIONS?.create_name_value_label(field_key, value, isNaN(value) ? value : '', window?.list_settings);
                                        if (newLabel) {
                                            labels.push(newLabel);
                                        }
                                    });
                                }
                            });
                        }

                        /**
                         * Determine status field to be appended to filter fields.
                         */

                        const status_detected = Array.isArray(filter) && filter.filter((field) => !!field[settings.settings.status_field.status_key]).length > 0;
                        if ( !status_detected && window.SHAREDFUNCTIONS.get_json_from_local_storage && settings.settings?.status_field?.status_key && settings.settings?.status_field?.archived_key ) {

                            // Determine if archived records are to be shown.
                            const show_archived_records = window.SHAREDFUNCTIONS.get_json_from_local_storage(
                                'list_archived_switch_status',
                                false,
                                settings.post_type
                            );

                            // Package archived records status flag.
                            let status = {};
                            status[settings.settings.status_field.status_key] = [ `${show_archived_records ? '' : '-'}${settings.settings.status_field.archived_key}` ];

                            // Finally append to filter fields, only if it's in a false state.
                            if ( !show_archived_records && Array.isArray( filter ) ) {
                                filter.push( status );
                            }
                        }

                        /**
                         * Proceed with Custom AI Filter creation and list refresh. Ensure text searches
                         * overwrite any other filter fields.
                         */

                        let query = text_search ? { text: text_search } : { fields: filter };
                        window.SHAREDFUNCTIONS.reset_split_by_filters();
                        window.SHAREDFUNCTIONS.add_custom_filter(
                            settings.translations['custom_filter'],
                            'custom-filter',
                            query,
                            labels
                        );
                    }
                }
            });
        </script>
        <?php
    }
}
Disciple_Tools_AI_Tile::instance();
