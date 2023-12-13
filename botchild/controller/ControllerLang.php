<?php


class ControllerLang
{
    private static $translate;
    public static $lang;
    public static $bot;

    public static function trans(string $key, array $params = []): string
    {

        if(empty(self::$translate)) {
            self::getTranslate();
        }
        $translates = self::$translate;
        
        if(!isset($translates[self::$lang]) || !isset($translates[self::$lang][$key]) ) {
            return self::setParams($key, $params);
        }

        return self::setParams($translates[self::$lang][$key], $params);

    }

    private static function getTranslate(): array
    {
        $translate = include( $_SERVER['DOCUMENT_ROOT'] . '/core/langs.php');
        return self::$translate = $translate[self::$bot];
    }

    private static function setParams(string $string, array $params): string
    {
        foreach ($params as $key => $value) {
            if(strpos($string, "%" . $key . "%") !== false) {
                $string = str_replace("%" . $key . "%", $value, $string);
            }
        }
        return $string;
    }
}
