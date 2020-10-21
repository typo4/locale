<?php

defined('TYPO3_MODE') || die();

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper::class] = [
        'className' => \Typo4\Locale\ViewHelper\TranslateViewHelper::class,
    ];
})();
