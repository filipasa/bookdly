<?php

namespace BookneticAddon\Customerpanel;


use BookneticApp\Providers\Helpers\Helper;

class Listener
{

    public static function replaceShortCode( $text, $data )
    {
        return str_replace('{customer_panel_url}', CustomerPanelHelper::customerPanelURL(), $text );
    }

    public static function saasSharePageFooter()
    {
        // Removed as it is not needed in Share Page modal
    }

    public static function initGutenbergBlocks()
    {
        if( !function_exists('register_block_type') )
            return;

        wp_register_script(
            'booknetic-cp-blocks',
            plugins_url( 'assets/backend/js/gutenberg-block.js', dirname(__DIR__) . '/init.php' ),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type( 'booknetic/customerpanel' , ['editor_script' => 'booknetic-cp-blocks'] );
    }

}