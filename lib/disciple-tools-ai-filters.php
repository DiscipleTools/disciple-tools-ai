<?php

add_filter( 'dt_upload_audio_comment', 'dt_upload_audio_comment', 10, 2 );
function dt_upload_audio_comment( $transcription, $audio_file ): string {
    if ( Disciple_Tools_AI_API::has_module_value( Disciple_Tools_AI_API::$module_default_id_dt_ai_audio_comment_transcription, 'enabled', 1 ) ) {
        $response = Disciple_Tools_AI_API::transcribe_audio_file( $audio_file );

        if ( isset( $response['status'], $response['transcription'] ) && $response['status'] == 'success' ) {
            if ( !empty( $response['transcription']['html'] ) ) {
                $transcription = $response['transcription']['html'];

            } elseif ( !empty( $response['transcription']['text'] ) ) {
                $transcription = $response['transcription']['text'];
            }
        }
    }

    return $transcription;
}

add_action( 'dt_upload_audio_comment_languages', 'dt_upload_audio_comment_languages', 10, 0 );
function dt_upload_audio_comment_languages() {
    if ( Disciple_Tools_AI_API::has_module_value( Disciple_Tools_AI_API::$module_default_id_dt_ai_audio_comment_transcription, 'enabled', 1 ) ) {
        ?>
        <select name="locale">
            <?php
            $dt_user_locale = get_user_locale( get_current_user_id() );
            $languages = dt_get_global_languages_list();
            foreach ( $languages as $code => $language ) {
                if ( in_array( $code, [
                    'ar',
                    'nl',
                    'es',
                    'ko',
                    'it',
                    'de',
                    'th',
                    'ru',
                    'pt',
                    'pl',
                    'id',
                    'sv',
                    'cs',
                    'en',
                    'ja',
                    'fr',
                    'ro',
                    'tr',
                    'ca',
                    'hu',
                    'uk',
                    'el',
                    'bg',
                    'sr',
                    'mk',
                    'lv',
                    'sl',
                    'hi',
                    'gl',
                    'da',
                    'ur',
                    'sk',
                    'he',
                    'fi',
                    'az',
                    'lt',
                    'et',
                    'nn',
                    'cy',
                    'pa',
                    'fa',
                    'eu',
                    'vi',
                    'bn',
                    'ne',
                    'mr',
                    'be',
                    'kk',
                    'hy',
                    'sw',
                    'ta',
                    'sq'
                ] ) ) {
                    ?>
                    <option
                        value="<?php echo esc_html( $code ); ?>" <?php selected( substr( trim( $dt_user_locale ), 0, 2 ) === $code ) ?>>
                        <?php echo esc_html( ! empty( $language['flag'] ) ? $language['flag'] . ' ' : '' ); ?> <?php echo esc_html( $language['native_name'] ); ?>
                    </option>
                    <?php
                }
            }
            ?>
        </select>
        <br>
        <?php
    }
}
