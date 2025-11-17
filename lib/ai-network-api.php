<?php
if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly

/**
 * Class DT_AI_Network_API
 *
 * Network-level AI API that can be loaded independently of the DT theme.
 * This allows Network Admin functionality to work even when the main site
 * doesn't have the Disciple Tools theme active.
 */
class DT_AI_Network_API {

    public static function get_ai_connection_settings(){
        // Get local settings from single array option
        $settings = get_option( 'DT_AI_connection_settings', [
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

        // Empty local site options will default back to multisite network settings.
        if ( is_multisite() ) {
            // Get all network settings from single option
            $network_settings = get_site_option( 'DT_AI_connection_settings', [] );

            foreach ( $settings as $key => $value ) {
                if ( empty( $value ) && isset( $network_settings[$key] ) && !empty( $network_settings[$key] ) ) {
                    $settings[$key] = $network_settings[$key];
                }
            }
        }

        return [
            'enabled' => !empty( $settings['llm_endpoint'] ) && !empty( $settings['llm_api_key'] ) && !empty( $settings['llm_model'] ) && !empty( $settings['transcript_llm_endpoint'] ) && !empty( $settings['transcript_llm_api_key'] ) && !empty( $settings['transcript_llm_model'] ),
            'llm_provider' => $settings['llm_provider'],
            'llm_provider_chat_path' => $settings['llm_provider_chat_path'],
            'llm_endpoint' => $settings['llm_endpoint'],
            'llm_api_key' => $settings['llm_api_key'],
            'llm_model' => $settings['llm_model'],
            'transcript_llm_provider' => $settings['transcript_llm_provider'],
            'transcript_llm_provider_transcript_path' => $settings['transcript_llm_provider_transcript_path'],
            'transcript_llm_endpoint' => $settings['transcript_llm_endpoint'],
            'transcript_llm_api_key' => $settings['transcript_llm_api_key'],
            'transcript_llm_model' => $settings['transcript_llm_model'],
            'google_translate_api_key' => $settings['google_translate_api_key'] ?? ''
        ];
    }

    public static function list_modules( $defaults = [
        'dt_ai_list_filter' => [
            'id' => 'dt_ai_list_filter',
            'name' => 'List Search and Filter',
            'description' => 'Enable AI search and filter for lists.',
            'visible' => true,
            'enabled' => 1
        ],
        'dt_ai_ml_list_filter' => [
            'id' => 'dt_ai_ml_list_filter',
            'name' => 'List User App (Magic Link)',
            'description' => 'A new user app with AI search and filter integrated. ',
            'visible' => true,
            'enabled' => 1
        ],
        'dt_ai_metrics_dynamic_maps' => [
            'id' => 'dt_ai_metrics_dynamic_maps',
            'name' => 'Metrics Dynamic Maps',
            'description' => 'A new AI maps in the metrics section.',
            'visible' => true,
            'enabled' => 1
        ],
        'dt_ai_summarization' => [
            'id' => 'dt_ai_summarization',
            'name' => 'AI Summarization',
            'description' => 'Generate AI-powered summaries for contacts and records.',
            'visible' => true,
            'enabled' => 1
        ],
        'dt_ai_audio_comment_transcription' => [
            'id' => 'dt_ai_audio_comment_transcription',
            'name' => 'Audio Comment Transcription',
            'description' => 'Transcribe captured audio comments.',
            'visible' => true,
            'enabled' => 1
        ]
    ] ): array {
        $ai_modules = apply_filters( 'dt_ai_modules', $defaults );
        $module_enabled_states = get_option( 'dt_ai_modules', [] );

        // If multisite, check for network defaults for modules without site-specific settings
        if ( is_multisite() && empty( $module_enabled_states ) ) {
            $network_module_states = get_site_option( 'DT_AI_network_modules', [] );

            // For each module, use network default if site setting is not explicitly set
            foreach ( $ai_modules as $module_id => $module ) {
                if ( !isset( $module_enabled_states[$module_id] ) && isset( $network_module_states[$module_id] ) ) {
                    $module_enabled_states[$module_id] = $network_module_states[$module_id];
                }
            }
        }

        // Remove modules not present.
        foreach ( $module_enabled_states as $key => $enabled_state ) {
            if ( !isset( $ai_modules[$key] ) ) {
                unset( $module_enabled_states[$key] );
            }
        }

        // Merge enabled states with defaults.
        foreach ( $ai_modules as $module_id => $module ) {
            if ( isset( $module_enabled_states[$module_id] ) ) {
                $ai_modules[$module_id]['enabled'] = $module_enabled_states[$module_id];
            }
        }

        return $ai_modules;
    }
}
