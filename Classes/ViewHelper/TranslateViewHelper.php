<?php

namespace Bundl\Locale\ViewHelper;

use Bundl\Locale\Utility\LocaleLangUtility;
use TYPO3\CMS\Core\Core\Environment;

class TranslateViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper {
    protected static function translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys) {
        if (Environment::getContext()->isDevelopment()) {
            LocaleLangUtility::update($id, $extensionName);
        }

        return parent::translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys);
    }
}
