<?php

add_filter( 'dt_ai_has_module_value', 'dt_ai_has_module_value', 10, 4 );
function dt_ai_has_module_value( $has_module_value, $module_id, $module_property, $module_value ): bool {
    return Disciple_Tools_AI_API::has_module_value( $module_id, $module_property, $module_value );
}

add_filter( 'dt_ai_transcribe_audio', 'dt_ai_transcribe_audio', 10, 2 );
function dt_ai_transcribe_audio( $transcription, $audio_file ): array {
    $response = Disciple_Tools_AI_API::transcribe_audio_file( $audio_file );

    return ( isset( $response['status'], $response['transcription'] ) && $response['status'] == 'success' ) ? $response['transcription'] : $transcription;
}
