<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_AI_Menu
 */
class Disciple_Tools_AI_Menu {

    public $token = 'disciple_tools_ai';
    public $page_title = 'Disciple Tools AI';

    private static $_instance = null;

    /**
     * Disciple_Tools_AI_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_AI_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_AI_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        $this->page_title = __( 'Disciple Tools AI', 'disciple-tools-ai' );
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $this->page_title = __( 'Disciple Tools AI', 'disciple-tools-ai' );

        add_submenu_page( 'dt_extensions', $this->page_title, $this->page_title, 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET['tab'] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->page_title ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">AI Settings</a>
            </h2>

            <?php
            switch ( $tab ) {
                case 'general':
                    $object = new Disciple_Tools_AI_Tab_General();
                    $object->content();
                    break;
                case 'second':
                    $object = new Disciple_Tools_AI_Tab_Second();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}
Disciple_Tools_AI_Menu::instance();

/**
 * Class Disciple_Tools_AI_Tab_General
 */
class Disciple_Tools_AI_Tab_General {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->
                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $token = Disciple_Tools_AI_Menu::instance()->token;
        $this->process_form_fields( $token );

        $connection_settings = Disciple_Tools_AI_API::get_ai_connection_settings();

        $ai_providers = DT_AI_Network_API::get_ai_providers();

        $selected_ai_provider = $connection_settings['llm_provider'] ?? 'predictionguard';
        $selected_ai_provider_chat_path = $connection_settings['llm_provider_chat_path'] ?? 'chat_complete';

        $selected_ai_transcript_provider = $connection_settings['transcript_llm_provider'] ?? 'predictionguard';
        $selected_ai_transcript_provider_chat_path = $connection_settings['transcript_llm_provider_transcript_path'] ?? 'audio_transcript';

        // Check if network defaults are available
        $has_network_defaults = false;
        $network_chat_provider = '';
        $network_chat_path = '';
        $network_transcript_provider = '';
        $network_transcript_path = '';

        if ( is_multisite() ) {
            $network_settings = get_site_option( 'DT_AI_connection_settings', [] );
            $has_network_defaults = !empty( $network_settings );
            $network_chat_provider = $network_settings['llm_provider'] ?? '';
            $network_chat_path = $network_settings['llm_provider_chat_path'] ?? '';
            $network_transcript_provider = $network_settings['transcript_llm_provider'] ?? '';
            $network_transcript_path = $network_settings['transcript_llm_provider_transcript_path'] ?? '';
        }

        // Fetch default and 3rd-Party AI modules.
        $modules = Disciple_Tools_AI_API::list_modules();
        ?>
        <p>
            Turn AI on for superpowers. For this, you will need to get an API key from your favorite AI provider(s).
            <br>
            Please use an AI model that you trust. Contact data and personal information may be shared with these models.
            <br>
            Consider contacting <a href="https://predictionguard.com" target="_blank">predictionguard.com</a> to get a model that is safe to use.
        </p>

        <!-- Chat Model Form -->
        <form method="post" id="chat-model-form">
            <?php wp_nonce_field( 'dt_admin_form_chat', 'dt_admin_form_chat_nonce' ) ?>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Chat Model</span></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Provider
                        </td>
                        <td>
                            <select id="llm-providers" name="llm-providers" style="width:50%; vertical-align: top;" <?php echo ( !$has_network_defaults || empty( $network_chat_provider ) ) ? 'required' : ''; ?>>
                                <?php if ( $has_network_defaults && !empty( $network_chat_provider ) ) : ?>
                                    <option value="">--- Network Default ---</option>
                                <?php else : ?>
                                    <option value="" disabled selected>--- Please Select Option ---</option>
                                <?php endif; ?>
                                <?php
                                foreach ( $ai_providers as $provider_key => $provider ) {

                                    // Exclude providers with no valid paths.
                                    if ( !empty( $provider['paths']['chat'] ) ) {
                                        $selected = ( !empty( $selected_ai_provider ) && $selected_ai_provider == $provider_key ) ? 'selected="selected"' : '';
                                        ?>
                                        <option value="<?php echo esc_attr( $provider_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $provider['label'] ) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Endpoint
                        </td>
                        <td>
                            <input type="text" id="llm-endpoint" name="llm-endpoint" placeholder="" value="<?php echo esc_attr( $connection_settings['llm_endpoint'] ) ?>" style="width: 50%; vertical-align: top;">

                            <select id="llm-provider-chat-paths" name="llm-provider-chat-paths" style="width:48%; vertical-align: top;" <?php echo ( !$has_network_defaults || empty( $network_chat_path ) ) ? 'required' : ''; ?>>
                                <?php if ( $has_network_defaults && !empty( $network_chat_path ) ) : ?>
                                    <option value="">--- Network Default ---</option>
                                <?php else : ?>
                                    <option value="" disabled selected>--- Please Select Option ---</option>
                                <?php endif; ?>
                                <?php
                                if ( !empty( $selected_ai_provider ) && isset( $ai_providers[ $selected_ai_provider ]['paths']['chat'] ) ) {
                                    foreach ( $ai_providers[ $selected_ai_provider ]['paths']['chat'] as $path_key => $path ) {
                                        $selected = ( !empty( $selected_ai_provider_chat_path ) && $selected_ai_provider_chat_path == $path_key ) ? 'selected="selected"' : '';
                                        ?>
                                        <option value="<?php echo esc_attr( $path_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $path ) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            API Key
                        </td>
                        <td>
                            <input type="text" id="llm-api-key" name="llm-api-key" placeholder=""
                                   value="<?php echo esc_attr( $connection_settings['llm_api_key'] ? '•••••••' : '' ) ?>" style="width: 100%">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Model
                        </td>
                        <td>
                            <input type="text" id="llm-model" name="llm-model" placeholder="" value="<?php echo esc_attr( $connection_settings['llm_model'] ) ?>" style="width: 100%">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span style="float:right;">
                                <button class="button" type="submit">Save Chat Model</button>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>

        <!-- Transcription Model Form -->
        <form method="post" id="transcript-model-form">
            <?php wp_nonce_field( 'dt_admin_form_transcript', 'dt_admin_form_transcript_nonce' ) ?>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Transcription Model</span></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="width:30%;">
                        Provider
                    </td>
                    <td>
                        <select id="transcript-llm-providers" name="transcript-llm-providers" style="width:50%; vertical-align: top;" <?php echo ( !$has_network_defaults || empty( $network_transcript_provider ) ) ? 'required' : ''; ?>>
                            <?php if ( $has_network_defaults && !empty( $network_transcript_provider ) ) : ?>
                                <option value="">--- Network Default ---</option>
                            <?php else : ?>
                                <option value="" disabled selected>--- Please Select Option ---</option>
                            <?php endif; ?>
                            <?php
                            foreach ( $ai_providers as $provider_key => $provider ) {

                                // Exclude providers with no valid paths.
                                if ( !empty( $provider['paths']['transcript'] ) ) {
                                    $selected = ( !empty( $selected_ai_transcript_provider ) && $selected_ai_transcript_provider == $provider_key ) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo esc_attr( $provider_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $provider['label'] ) ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        Endpoint
                    </td>
                    <td>
                        <input type="text" id="transcript-llm-endpoint" name="transcript-llm-endpoint" placeholder="" value="<?php echo esc_attr( $connection_settings['transcript_llm_endpoint'] ) ?>" style="width: 50%; vertical-align: top;">

                        <select id="transcript-llm-provider-transcript-paths" name="transcript-llm-provider-transcript-paths" style="width:48%; vertical-align: top;" <?php echo ( !$has_network_defaults || empty( $network_transcript_path ) ) ? 'required' : ''; ?>>
                            <?php if ( $has_network_defaults && !empty( $network_transcript_path ) ) : ?>
                                <option value="">--- Network Default ---</option>
                            <?php else : ?>
                                <option value="" disabled selected>--- Please Select Option ---</option>
                            <?php endif; ?>
                            <?php
                            if ( !empty( $selected_ai_transcript_provider ) && isset( $ai_providers[ $selected_ai_transcript_provider ]['paths']['transcript'] ) ) {
                                foreach ( $ai_providers[ $selected_ai_transcript_provider ]['paths']['transcript'] as $path_key => $path ) {
                                    $selected = ( !empty( $selected_ai_transcript_provider_chat_path ) && $selected_ai_transcript_provider_chat_path == $path_key ) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo esc_attr( $path_key ) ?>" <?php echo esc_attr( $selected ) ?>><?php echo esc_attr( $path ) ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        API Key
                    </td>
                    <td>
                        <input type="text" id="transcript-llm-api-key" name="transcript-llm-api-key" placeholder=""
                               value="<?php echo esc_attr( $connection_settings['transcript_llm_api_key'] ? '•••••••' : '' ) ?>" style="width: 100%">
                    </td>
                </tr>
                <tr>
                    <td>
                        Model
                    </td>
                    <td>
                        <input type="text" id="transcript-llm-model" name="transcript-llm-model" placeholder="" value="<?php echo esc_attr( $connection_settings['transcript_llm_model'] ) ?>" style="width: 100%">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                            <span style="float:right;">
                                <button class="button" type="submit">Save Transcription Model</button>
                            </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>

        <!-- Translation Settings and Features Form -->
        <form method="post" id="settings-features-form">
            <?php wp_nonce_field( 'dt_admin_form_settings', 'dt_admin_form_settings_nonce' ) ?>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Translation Settings</span></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        Google Translate API Key
                    </td>
                    <td>
                        <input type="text" name="google-translate-api-key" placeholder=""
                               value="<?php echo esc_attr( $connection_settings['google_translate_api_key'] ? '•••••••' : '' ) ?>" style="width: 100%">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                            <span style="float:right;">
                                <button class="button" type="submit">Save</button>
                            </span>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Enabled Features</span></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $modules as $module ) {
                    if ( isset( $module['visible'] ) && $module['visible'] ) {
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_attr( $module['name'] ) ?>
                                <br>
                                <small><?php echo esc_attr( $module['description'] ) ?></small>
                            </td>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr( $module['id'] ) ?>" <?php echo ( ( isset( $module['enabled'] ) && $module['enabled'] ) ? 'checked' : '' ) ?>>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                <tr>
                    <td colspan="2">
                        <span style="float:right;">
                            <button class="button" type="submit">Save Settings</button>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <script>
            jQuery(document).ready(function() {
                const aiProviders = [<?php echo json_encode( $ai_providers ) ?>][0];

                /**
                 * Chat Model
                 */

                // Function to update the chat paths dropdown based on selected provider
                function updateChatElements(selectedProvider) {
                    const chatEndpoint = jQuery('#llm-endpoint');
                    const chatPathsSelect = jQuery('#llm-provider-chat-paths');
                    const chatApiKey = jQuery('#llm-api-key');
                    const chatModel = jQuery('#llm-model');
                    const aiProvider = aiProviders[selectedProvider];

                    // Ensure we have a valid ai provider
                    if ( aiProvider ) {

                        // Get the paths for the selected provider
                        if ( aiProvider?.paths?.chat ) {
                            const chatPaths = aiProvider.paths.chat;

                            // Clear existing options
                            chatPathsSelect.empty();

                            // Add options for each path
                            jQuery.each(chatPaths, function(pathKey, pathValue) {
                                chatPathsSelect.append(
                                    jQuery('<option></option>')
                                    .attr('value', pathKey)
                                    .text(pathValue)
                                );
                            });
                        }

                        // Set default endpoint for the selected provider
                        if ( aiProvider?.endpoints?.chat && aiProvider.endpoints.chat.length > 0 ) {
                            chatEndpoint.val( aiProvider.endpoints.chat[0] );
                        }

                        // Set default model for the selected provider
                        if ( aiProvider?.models?.chat && aiProvider.models.chat.length > 0 ) {
                            chatModel.val( aiProvider.models.chat[0] );
                        }

                    } else if ( !selectedProvider ) {

                        // Clear existing options
                        chatPathsSelect.empty();

                        // Add options for each path
                        if ( hasNetworkDefaults && networkChatPath ) {
                            chatPathsSelect.append(
                                jQuery('<option></option>')
                                .attr('value', '')
                                .text('--- Network Default ---')
                            );
                        } else {
                            chatPathsSelect.append(
                                jQuery('<option></option>')
                                .attr('value', '')
                                .attr('disabled', 'disabled')
                                .attr('selected', 'selected')
                                .text('--- Please Select Option ---')
                            );
                        }

                        // Reset remaining fields, to force the pull down of default network settings.
                        chatEndpoint.val('');
                        chatApiKey.val('');
                        chatModel.val('');
                    }
                }

                // Add change event listener to provider select
                jQuery('#llm-providers').on('change', function() {
                    updateChatElements( jQuery(this).val() );
                });

                /**
                 * Transcription Model
                 */

                // Function to update the transcription paths dropdown based on selected provider
                function updateTranscriptElements(selectedProvider) {
                    const transcriptEndpoint = jQuery('#transcript-llm-endpoint');
                    const transcriptPathsSelect = jQuery('#transcript-llm-provider-transcript-paths');
                    const transcriptApiKey = jQuery('#transcript-llm-api-key');
                    const transcriptModel = jQuery('#transcript-llm-model');
                    const aiProvider = aiProviders[selectedProvider];

                    // Ensure we have a valid ai provider
                    if ( aiProvider ) {

                        // Get the paths for the selected provider
                        if ( aiProvider?.paths?.transcript ) {
                            const transcriptPaths = aiProvider.paths.transcript;

                            // Clear existing options
                            transcriptPathsSelect.empty();

                            // Add options for each path
                            jQuery.each(transcriptPaths, function(pathKey, pathValue) {
                                transcriptPathsSelect.append(
                                    jQuery('<option></option>')
                                    .attr('value', pathKey)
                                    .text(pathValue)
                                );
                            });
                        }

                        // Set default endpoint for the selected provider
                        if ( aiProvider?.endpoints?.transcript && aiProvider.endpoints.transcript.length > 0 ) {
                            transcriptEndpoint.val( aiProvider.endpoints.transcript[0] );
                        }

                        // Set default model for the selected provider
                        if ( aiProvider?.models?.transcript && aiProvider.models.transcript.length > 0 ) {
                            transcriptModel.val( aiProvider.models.transcript[0] );
                        }

                    } else if ( !selectedProvider ) {

                        // Clear existing options
                        transcriptPathsSelect.empty();

                        // Add options for each path
                        if ( hasNetworkDefaults && networkTranscriptPath ) {
                            transcriptPathsSelect.append(
                                jQuery('<option></option>')
                                .attr('value', '')
                                .text('--- Network Default ---')
                            );
                        } else {
                            transcriptPathsSelect.append(
                                jQuery('<option></option>')
                                .attr('value', '')
                                .attr('disabled', 'disabled')
                                .attr('selected', 'selected')
                                .text('--- Please Select Option ---')
                            );
                        }

                        // Reset remaining fields, to force the pull down of default network settings.
                        transcriptEndpoint.val('');
                        transcriptApiKey.val('');
                        transcriptModel.val('');
                    }
                }

                // Add change event listener to provider select
                jQuery('#transcript-llm-providers').on('change', function() {
                    updateTranscriptElements( jQuery(this).val() );
                });
            });
        </script>
        <?php
    }

    public function process_form_fields( $token ){
        // Verify nonces
        $chat_nonce_verified = isset( $_POST['dt_admin_form_chat_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_admin_form_chat_nonce'] ) ), 'dt_admin_form_chat' );

        $transcript_nonce_verified = isset( $_POST['dt_admin_form_transcript_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_admin_form_transcript_nonce'] ) ), 'dt_admin_form_transcript' );

        $settings_nonce_verified = isset( $_POST['dt_admin_form_settings_nonce'] )
            && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_admin_form_settings_nonce'] ) ), 'dt_admin_form_settings' );

        // Early return if no valid nonce
        if ( !$chat_nonce_verified && !$transcript_nonce_verified && !$settings_nonce_verified ) {
            return;
        }

        // Get current settings from database once
        $current_settings = get_option( 'DT_AI_connection_settings', [
            'llm_provider' => '',
            'llm_provider_chat_path' => '',
            'llm_endpoint' => '',
            'llm_api_key' => '',
            'llm_model' => '',
            'transcript_llm_provider' => '',
            'transcript_llm_provider_transcript_path' => '',
            'transcript_llm_endpoint' => '',
            'transcript_llm_api_key' => '',
            'transcript_llm_model' => '',
            'google_translate_api_key' => ''
        ] );

        // Process Chat Model Form
        if ( $chat_nonce_verified ) {
            $post_vars = dt_recursive_sanitize_array( $_POST );
            $updated_settings = $current_settings;

            if ( isset( $post_vars['llm-providers'] ) ) {
                $updated_settings['llm_provider'] = $post_vars['llm-providers'];
            }

            if ( isset( $post_vars['llm-provider-chat-paths'] ) ) {
                $updated_settings['llm_provider_chat_path'] = $post_vars['llm-provider-chat-paths'];
            }

            if ( isset( $post_vars['llm-endpoint'] ) ) {
                $updated_settings['llm_endpoint'] = $post_vars['llm-endpoint'];
            }

            if ( isset( $post_vars['llm-api-key'] ) && $post_vars['llm-api-key'] !== '•••••••' ) {
                $updated_settings['llm_api_key'] = $post_vars['llm-api-key'];
            }

            if ( isset( $post_vars['llm-model'] ) ) {
                $updated_settings['llm_model'] = $post_vars['llm-model'];
            }

            update_option( 'DT_AI_connection_settings', $updated_settings );
            return;
        }

        // Process Transcription Model Form
        if ( $transcript_nonce_verified ) {
            $post_vars = dt_recursive_sanitize_array( $_POST );
            $updated_settings = $current_settings;

            if ( isset( $post_vars['transcript-llm-providers'] ) ) {
                $updated_settings['transcript_llm_provider'] = $post_vars['transcript-llm-providers'];
            }

            if ( isset( $post_vars['transcript-llm-provider-transcript-paths'] ) ) {
                $updated_settings['transcript_llm_provider_transcript_path'] = $post_vars['transcript-llm-provider-transcript-paths'];
            }

            if ( isset( $post_vars['transcript-llm-endpoint'] ) ) {
                $updated_settings['transcript_llm_endpoint'] = $post_vars['transcript-llm-endpoint'];
            }

            if ( isset( $post_vars['transcript-llm-api-key'] ) && $post_vars['transcript-llm-api-key'] !== '•••••••' ) {
                $updated_settings['transcript_llm_api_key'] = $post_vars['transcript-llm-api-key'];
            }

            if ( isset( $post_vars['transcript-llm-model'] ) ) {
                $updated_settings['transcript_llm_model'] = $post_vars['transcript-llm-model'];
            }

            update_option( 'DT_AI_connection_settings', $updated_settings );
            return;
        }

        // Process Translation Settings and Features Form
        if ( $settings_nonce_verified ) {
            $post_vars = dt_recursive_sanitize_array( $_POST );
            $updated_settings = $current_settings;

            if ( isset( $post_vars['google-translate-api-key'] ) && $post_vars['google-translate-api-key'] !== '•••••••' ) {
                $updated_settings['google_translate_api_key'] = $post_vars['google-translate-api-key'];
            }

            update_option( 'DT_AI_connection_settings', $updated_settings );

            /**
             * Process incoming module state changes.
             */
            $updated_modules = [];
            foreach ( Disciple_Tools_AI_API::list_modules() as $module ) {
                $module['enabled'] = isset( $post_vars[ $module['id'] ] ) ? 1 : 0;
                $updated_modules[ $module['id'] ] = $module;
            }

            Disciple_Tools_AI_API::update_modules( $updated_modules );

            do_action( 'dt_ai_modules_updated', $updated_modules );
        }
    }
}


/**
 * Class Disciple_Tools_AI_Tab_Second
 */
class Disciple_Tools_AI_Tab_Second {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Header</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}
