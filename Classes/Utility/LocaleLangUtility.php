<?php

namespace Typo4\Locale\Utility;

use DOMDocument;
use Exception;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocaleLangUtility {
    public static function update(string $id, string $extensionKey): void {
        $pathToLanguageFolder = Environment::getPublicPath() . '/typo3conf/ext/' . $extensionKey . '/Resources/Private/Language/';

        // get translation array and locales
        try {
            [$translations, $locales] = self::loadTranslationDataFromAllXLF($extensionKey);
        } catch (Exception $e) {
            return;
        }

        // add new translation to translation array
        if (empty($translations)) {
            $translations = [];
        }

        $changed = false;

        if (!isset($translations[$id]['default'])) {
            $translations[$id]['default'] = $id;
            $changed = true;
        }

        foreach ($locales as $locale) {
            if (!isset($translations[$id][$locale])) {
                $translations[$id][$locale] = $translations[$id]['default'];
                $changed = true;
            }
        }

        if(!$changed) {
            return;
        }

        ksort($translations);

        // change hierarchy array to locale
        $translationSortedByLocale = [];

        foreach ($translations as $translationId => $translationEntry) {
            foreach ($translations[$translationId] as $locale => $value) {
                $translationSortedByLocale[$locale][$translationId] = $value;
            }
        }

        if($translations)

        foreach ($translationSortedByLocale as $locale => $translationSortedByLocaleEntry) {
            if ('default' === $locale) {
                $pathToTranslationFile = $pathToLanguageFolder . 'locallang.xlf';
            } else {
                $pathToTranslationFile = $pathToLanguageFolder . $locale . '.locallang.xlf';
            }

            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->preserveWhiteSpace = false;
            $xml->formatOutput = true;
            $xml->load($pathToLanguageFolder . 'locallang.xlf');

            $body = $xml->getElementsByTagName('body')->item(0);

            while ($body->childNodes->length) {
                $body->removeChild($body->firstChild);
            }

            foreach ($translationSortedByLocaleEntry as $translationId => $value) {
                $transUnit = $xml->createElement('trans-unit');
                $transUnitAttributeId = $xml->createAttribute('id');
                $transUnitAttributeId->value = $translationId;
                $transUnit->appendChild($transUnitAttributeId);
                $source = $xml->createElement(('default' === $locale) ? 'source' : 'target');
                $source->appendChild($xml->createCDATASection($value));
                $transUnit->appendChild($source);

                $body->appendChild($transUnit);
            }

            file_put_contents($pathToTranslationFile, $xml->saveXML());
        }
    }

    protected static function loadTranslationDataFromAllXLF(string $extensionKey): ?array {
        /** @var LogManager $logManager */
        $logManager = GeneralUtility::makeInstance(LogManager::class);

        $logger = $logManager->getLogger(__CLASS__);

        $pathToExtension = Environment::getPublicPath() . '/typo3conf/ext/' . $extensionKey;

        if (!is_dir($pathToExtension)) {
            throw new RuntimeException('Extension with key ' . $extensionKey . ' does not exists.');
        }

        $pathToLanguageFolder = $pathToExtension . '/Resources/Private/Language/';

        if (!is_dir($pathToLanguageFolder)) {
            $message = $pathToLanguageFolder . ' does not exist. Please create that folder.';
            $logger->error($message);
            throw new RuntimeException($message);
        }

        $pathToDefaultLanguageFile = $pathToLanguageFolder . 'locallang.xlf';

        if (!is_file($pathToDefaultLanguageFile)) {
            $message = $pathToDefaultLanguageFile . ' does not exist. Please create that file.';
            $logger->error($message);
            throw new RuntimeException($message);
        }

        $xml = simplexml_load_string(file_get_contents($pathToDefaultLanguageFile));

        $translations = [];

        foreach ($xml->file->body->children() as $transUnit) {
            $id = (string) $transUnit->attributes()->id;

            if (empty($id)) {
                $message = 'error in translation file: ' . $pathToDefaultLanguageFile;
                $logger->error($message);
                throw new RuntimeException($message);
            }

            $value = (string) $transUnit->source;

            if (empty($value)) {
                $message = 'error in translation file: ' . $pathToDefaultLanguageFile;
                $logger->error($message);
                throw new RuntimeException($message);
            }

            $translations[$id] = [];
            $translations[$id]['default'] = $value;
        }

        $files = GeneralUtility::getFilesInDir($pathToLanguageFolder);

        $locales = [];

        foreach ($files as $file) {
            if (false !== strpos($file, '.locallang.xlf')) {
                $pathToTranslationFile = $pathToLanguageFolder . $file;
                $xml = simplexml_load_string(file_get_contents($pathToTranslationFile));

                $locale = explode('.', $file)[0];

                foreach ($xml->file->body->children() as $transUnit) {
                    $id = (string) $transUnit->attributes()->id;
                    if (empty($id)) {
                        $message = 'error in translation file: ' . $pathToTranslationFile;
                        $logger->error($message);
                        throw new RuntimeException($message);
                    }
                    $value = (string) $transUnit->target;
                    if (empty($value)) {
                        $message = 'error in translation file: ' . $pathToTranslationFile;
                        $logger->error($message);
                        throw new RuntimeException($message);
                    }
                    if (isset($translations[$id]['default'])) {
                        $translations[$id][$locale] = $value;
                    }
                }

                if (!array_key_exists($locale, $locales)) {
                    $locales[] = $locale;
                }
            }
        }

        foreach ($translations as $id => $translation) {
            foreach ($locales as $locale) {
                if (!array_key_exists($locale, $translations[$id])) {
                    $translations[$id][$locale] = $translations[$id]['default'];
                }
            }
        }

        return [$translations, $locales];
    }
}
