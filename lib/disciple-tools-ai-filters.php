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
