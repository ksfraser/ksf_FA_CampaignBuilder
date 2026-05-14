<?php
/**
 * PHPUnit Test Bootstrap
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('TB_PREF')) {
    define('TB_PREF', 'fa_');
}
if (!defined('INPUT_COOKIE')) {
    define('INPUT_COOKIE', '');
}