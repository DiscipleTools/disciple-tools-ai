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

        // Fetch default and 3rd-Party AI modules.
        $modules = Disciple_Tools_AI_API::list_modules();
        ?>
        <form method="post">
            <?php wp_nonce_field( 'dt_admin_form', 'dt_admin_form_nonce' ) ?>

            <p>
                Turn AI on for superpowers. For this, you will need to get an API key from your favorite AI provider(s).
                <br>
                Please use an AI model that you trust. Contact data and personal information may be shared with these models.
                <br>
                Consider contacting <a href="https://predictionguard.com" target="_blank">predictionguard.com</a> to get a model that is safe to use.
            </p>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th colspan="2"><span style="font-weight: bold;">Chat Model</span></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Endpoint
                        </td>
                        <td>
                            <input type="text" name="llm-endpoint" placeholder="" value="<?php echo esc_attr( $connection_settings['llm_endpoint'] ) ?>" style="width: 100%">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            API Key
                        </td>
                        <td>
                            <input type="text" name="llm-api-key" placeholder=""
                                   value="<?php echo esc_attr( $connection_settings['llm_api_key'] ? '•••••••' : '' ) ?>" style="width: 100%">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Model
                        </td>
                        <td>
                            <input type="text" name="llm-model" placeholder="" value="<?php echo esc_attr( $connection_settings['llm_model'] ) ?>" style="width: 100%">
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
                    <th colspan="2"><span style="font-weight: bold;">Transcription Model</span></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        Endpoint
                    </td>
                    <td>
                        <input type="text" name="transcript-llm-endpoint" placeholder="" value="<?php echo esc_attr( $connection_settings['transcript_llm_endpoint'] ) ?>" style="width: 100%">
                    </td>
                </tr>
                <tr>
                    <td>
                        API Key
                    </td>
                    <td>
                        <input type="text" name="transcript-llm-api-key" placeholder=""
                               value="<?php echo esc_attr( $connection_settings['transcript_llm_api_key'] ? '•••••••' : '' ) ?>" style="width: 100%">
                    </td>
                </tr>
                <tr>
                    <td>
                        Model
                    </td>
                    <td>
                        <input type="text" name="transcript-llm-model" placeholder="" value="<?php echo esc_attr( $connection_settings['transcript_llm_model'] ) ?>" style="width: 100%">
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
                            <button class="button" type="submit">Save</button>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function process_form_fields( $token ){
        if ( isset( $_POST['dt_admin_form_nonce'] ) &&
            wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_admin_form_nonce'] ) ), 'dt_admin_form' ) ) {

            $post_vars = dt_recursive_sanitize_array( $_POST );

            // Get current settings to preserve existing values
            $current_settings = get_option( 'DT_AI_connection_settings', [
                'llm_endpoint' => '',
                'llm_api_key' => '',
                'llm_model' => '',
                'transcript_llm_endpoint' => '',
                'transcript_llm_api_key' => '',
                'transcript_llm_model' => '',
                'google_translate_api_key' => ''
            ] );

            $updated_settings = $current_settings;

            if ( isset( $post_vars['llm-endpoint'] ) ) {
                $updated_settings['llm_endpoint'] = $post_vars['llm-endpoint'];
            }

            if ( isset( $post_vars['llm-api-key'] ) && $post_vars['llm-api-key'] !== '•••••••' ) {
                $updated_settings['llm_api_key'] = $post_vars['llm-api-key'];
            }

            if ( isset( $post_vars['llm-model'] ) ) {
                $updated_settings['llm_model'] = $post_vars['llm-model'];
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

            if ( isset( $post_vars['google-translate-api-key'] ) && $post_vars['google-translate-api-key'] !== '•••••••' ) {
                $updated_settings['google_translate_api_key'] = $post_vars['google-translate-api-key'];
            }

            // Save all settings as a single array
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
