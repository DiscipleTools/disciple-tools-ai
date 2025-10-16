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
                    'af',
                    'ak',
                    'am',
                    'ar',
                    'as',
                    'az',
                    'be',
                    'bg',
                    'bm',
                    'bn',
                    'bo',
                    'bs',
                    'ca',
                    'cs',
                    'cy',
                    'da',
                    'de',
                    'ee',
                    'el',
                    'en',
                    'eo',
                    'es',
                    'et',
                    'eu',
                    'fa',
                    'ff',
                    'fi',
                    'fo',
                    'fr',
                    'ga',
                    'gl',
                    'gu',
                    'gv',
                    'ha',
                    'he',
                    'hi',
                    'hr',
                    'hu',
                    'hy',
                    'id',
                    'ig',
                    'ii',
                    'is',
                    'it',
                    'ja',
                    'ka',
                    'ki',
                    'kk',
                    'kl',
                    'km',
                    'kn',
                    'ko',
                    'ku',
                    'kw',
                    'lg',
                    'lt',
                    'lv',
                    'mg',
                    'mk',
                    'ml',
                    'mr',
                    'ms',
                    'mt',
                    'my',
                    'nb',
                    'nd',
                    'ne',
                    'nl',
                    'nn',
                    'om',
                    'or',
                    'pa',
                    'pl',
                    'ps',
                    'pt',
                    'rm',
                    'ro',
                    'ru',
                    'rw',
                    'sg',
                    'si',
                    'sk',
                    'sl',
                    'so',
                    'sq',
                    'sr',
                    'sv',
                    'sw',
                    'ta',
                    'te',
                    'th',
                    'ti',
                    'tl',
                    'to',
                    'tr',
                    'uk',
                    'ur',
                    'uz',
                    'vi',
                    'wo',
                    'yo',
                    'zh',
                    'zu'
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

add_filter( 'dt_ai_providers', 'dt_ai_providers', 5, 1 );
function dt_ai_providers( $ai_providers ): array {
    if ( !is_array( $ai_providers ) ) {
        $ai_providers = [];
    }

    if ( !isset( $ai_providers['predictionguard'] ) ) {
        $ai_providers['predictionguard'] = [
            'label' => __( 'Prediction Guard', 'disciple-tools-ai' ),
            'endpoints' => [ // First endpoint in array, is classed as default.
                'chat' => [
                    'https://api.predictionguard.com'
                ],
                'transcript' => [
                    'https://api.predictionguard.com'
                ]
            ],
            'paths' => [ // New paths to be added, as more path-endpoints are supported.
                'chat' => [
                    'chat_complete' => '/chat/completions'
                ],
                'transcript' => [
                    'audio_transcript' => '/audio/transcriptions'
                ]
            ],
            'models' => [ // First model in array, is classed as default.
                'chat' => [
                    'Hermes-3-Llama-3.1-70B'
                ],
                'transcript' => [
                    'base'
                ]
            ]
        ];
    }

    if ( !isset( $ai_providers['openai'] ) ) {
        $ai_providers['openai'] = [
            'label' => __( 'OpenAI', 'disciple-tools-ai' ),
            'endpoints' => [
                'chat' => [
                    'https://api.openai.com'
                ],
                'transcript' => [
                    'https://api.openai.com'
                ]
            ],
            'paths' => [
                'chat' => [
                    'responses' => '/v1/responses'
                ],
                'transcript' => [
                    'audio_transcript' => '/v1/audio/transcriptions'
                ]
            ],
            'models' => [
                'chat' => [
                    'gpt-4.1'
                ],
                'transcript' => [
                    'gpt-4o-transcribe'
                ]
            ]
        ];
    }

    if ( !isset( $ai_providers['anthropic'] ) ) {
        $ai_providers['anthropic'] = [
            'label' => __( 'Anthropic', 'disciple-tools-ai' ),
            'endpoints' => [
                'chat' => [
                    'https://api.anthropic.com'
                ],
                'transcript' => []
            ],
            'paths' => [
                'chat' => [
                    'messages' => '/v1/messages'
                ],
                'transcript' => []
            ],
            'models' => [
                'chat' => [
                    'claude-sonnet-4-5-20250929'
                ],
                'transcript' => []
            ]
        ];
    }

    return $ai_providers;
}
