<?php

if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_AI_Data
 *
 * @since  1.11.0
 */
class Disciple_Tools_AI_Data {

    /**
     * The single instance of Disciple_Tools_AI_Data.
     *
     * @var    object
     * @access private
     * @since  1.11.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_AI_Data Instance
     *
     * Ensures only one instance of Disciple_Tools_AI_Data is loaded or can be loaded.
     *
     * @return Disciple_Tools_AI_Data instance
     * @since  1.11.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Disciple_Tools_AI_Data constructor.
     */
    public function __construct() {
        add_filter( 'dt_ai_field_specs', [ $this, 'dt_ai_field_specs' ], 10, 2 );
    }

    private function get_data( $path, $escape = true ): array {
        $contents = [];

        $handle = fopen( $path, 'r' );
        if ( $handle !== false ) {
            while ( !feof( $handle ) ) {
                $line = fgets( $handle );
                if ( $line && !empty( trim( $line ) ) ) {
                    $contents[] = ( $escape ) ? addslashes( trim( $line ) ) : $line;
                }
            }

            fclose( $handle );
        }

        return $contents;
    }

    private function reshape_examples( $examples, $add_header = true, $delimiter = '==//==' ): array {
        $reshaped = [];

        if ( $add_header ) {
            $reshaped[] = 'Examples';
        }

        foreach ( $examples as $example ) {
            $exploded_example = explode( $delimiter, $example );

            $query = trim( $exploded_example[0] );
            $output = trim( $exploded_example[1] );

            $reshaped[] = 'User Query:\n'. $query .'\nOutput:\n'. $output;
        }

        return $reshaped;
    }

    private function generate_record_post_type_specs( $post_type ): array {
        $post_type_settings = DT_Posts::get_post_settings( $post_type, false );

        if ( is_wp_error( $post_type_settings ) ) {
            return [];
        }

        $specs = [
            'post-type-key' => $post_type,
            'post-type-singular-label' => $post_type_settings['label_singular'],
            'post-type-plural-label' => $post_type_settings['label_plural'],
            'fields' => []
        ];
        foreach ( $post_type_settings['fields'] ?? [] as $field_key => $field ){
            $spec = [
                'field-key' => $field_key,
                'label' => $field['name'],
                'type' => $field['type']
            ];

            // If available, also capture options....
            if ( isset( $field['default'] ) && is_array( $field['default'] ) && count( $field['default'] ) > 0 ) {
                $spec['options'] = [];
                foreach ( $field['default'] as $option => $defaults ) {
                    $spec['options'][] = [
                        'option-key' => $option,
                        'label' => $defaults['label'] ?? ''
                    ];
                }
            }

            $specs['fields'][] = $spec;
        }

        return $specs;
    }

    public function dt_ai_field_specs( $field_specs, $post_type ): array {

        /**
         * Fetch list of fields associated with given post type and list
         * keys.
         */

        $brief = [ 'The following field_keys are allowed:' ];
        foreach ( DT_Posts::get_post_settings( $post_type )['fields'] ?? [] as $key => $field ) {
            if ( in_array( $field['type'], [ 'boolean', 'communication_channel', 'connection', 'date', 'key_select', 'location', 'location_meta', 'multi_select', 'tags', 'text', 'user_select' ] ) ) {
                if ( !isset( $field['private'] ) || !$field['private'] ) {
                    $brief[] = $key;
                }
            }
        }

        /**
         * Load the various parts which will eventually be used
         * to construct the field generation model specification.
         */

        $fields_dir = __DIR__ . '/fields/';

        $instructions = $this->get_data( $fields_dir . '1-instructions/instructions.txt' );

        /**
         * The extraction of examples will require additional logic, to work into the desired shape.
         */

        $examples = [];
        $examples[] = 'Examples';
        foreach ( DT_Posts::get_field_types() as $field_type_key => $field_type ) {
            $path = $fields_dir . '3-examples/'. $field_type_key .'/examples.txt';
            if ( file_exists( $path ) ) {
                $examples = array_merge( $examples, $this->reshape_examples( $this->get_data( $path ), false ) );
            }
        }

        /**
         * Finally, build and return the required specification shape.
         */

        $field_specs['fields'] = [
            'brief' => $brief,
            'instructions' => $instructions,
            'examples' => $examples
        ];

        return $field_specs;
    }
}

Disciple_Tools_AI_Data::instance();
