<?php
/**
 * Google Translate Provider
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Google_Translate extends EMS_Translation_Provider {

    private $api_endpoint = 'https://translation.googleapis.com/language/translate/v2';

    protected function get_api_key() {
        return isset($this->options['google_translate_api_key']) ? $this->options['google_translate_api_key'] : '';
    }

    public function translate($text, $from_language, $to_language, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Google Translate API key is not configured.', 'environmental-multilang-support'));
        }

        $url = $this->api_endpoint . '?key=' . $this->api_key;
        
        $data = array(
            'q' => $text,
            'source' => $this->convert_language_code($from_language),
            'target' => $this->convert_language_code($to_language),
            'format' => 'text',
        );

        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return new WP_Error('translation_error', $result['error']['message']);
        }

        if (isset($result['data']['translations'][0]['translatedText'])) {
            return $result['data']['translations'][0]['translatedText'];
        }

        return new WP_Error('no_translation', __('No translation returned from Google Translate.', 'environmental-multilang-support'));
    }

    public function get_supported_languages() {
        return array(
            'vi', 'en', 'zh', 'zh-cn', 'zh-tw', 'ja', 'ko', 'th', 'ar', 'he', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'hi', 'bn',
            'nl', 'pl', 'tr', 'sv', 'da', 'no', 'fi', 'cs', 'sk', 'hu', 'ro', 'bg', 'hr', 'sl', 'et', 'lv', 'lt', 'mt',
            'el', 'ca', 'eu', 'gl', 'cy', 'ga', 'is', 'mk', 'al', 'az', 'be', 'ka', 'hy', 'kk', 'ky', 'lv', 'lt', 'mk',
            'mo', 'mn', 'ne', 'ps', 'fa', 'pa', 'si', 'so', 'sw', 'tg', 'ta', 'te', 'th', 'tk', 'tr', 'uk', 'ur', 'uz',
            'vi', 'cy', 'xh', 'yi', 'yo', 'zu'
        );
    }

    private function convert_language_code($code) {
        $mapping = array(
            'vi' => 'vi',
            'en' => 'en',
            'zh' => 'zh-cn',
            'ja' => 'ja',
            'ko' => 'ko',
            'th' => 'th',
            'ar' => 'ar',
            'he' => 'he',
            'fr' => 'fr',
            'es' => 'es',
        );

        return isset($mapping[$code]) ? $mapping[$code] : $code;
    }
}
