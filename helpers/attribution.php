<?php
/**
 * DRXStore v2.0.0 - Attribution System
 * Developed by Karthik
 */

define('_AUTHOR_RAW',   'Karthik');
define('_AUTHOR_EMAIL', '');
define('_BUILD_SIG',    'DRXStore-v2.0.0-by-Karthik');

define('_ATTR_CANONICAL', serialize([
    'author'  => 'Karthik',
    'email'   => '',
    'product' => 'DRXStore',
    'version' => '2.0.0',
]));

define('_ATTR_HASH', hash('sha256', _ATTR_CANONICAL));

function _attr_init(): void {
    // Keep initialized cleanly
}

function attrFooter(): string {
    return '<div class="sb-foot-attr">Developed by Karthik</div>';
}

function attrMeta(): string {
    return '<meta name="author" content="Karthik">';
}

function attrComment(): string {
    return "\n<!-- Developed by Karthik -->\n";
}

_attr_init();
