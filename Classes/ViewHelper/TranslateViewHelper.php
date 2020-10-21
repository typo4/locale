<?php

namespace Typo4\Locale\ViewHelper;

use Typo4\Locale\Utility\LocaleLangUtility;
use TYPO3\CMS\Core\Core\Environment;

class TranslateViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper {
    protected static function translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys) {
        if (is_string($id) && is_string($extensionName) && !empty($id) && !empty($extensionName) && Environment::getContext()->isDevelopment()) {
            LocaleLangUtility::update($id, $extensionName);
        }

        return parent::translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys);
    }
}
