<?php

class templates{
    static function HtmlByTemplate($template, $params, $switches = null) {
        $html = file_get_contents("./templates/$template.html") or die("Template '$template' not found");
        if (isset($params)) {
            if (!is_array($params)) die('Params not array');
            foreach ($params as $key => $value) {
                $html = str_replace("{% $key %}", $value, $html);
            }
        }
        if(isset($switches)) {
            if (!is_array($params)) die('Switches not array');
            foreach ($switches as $key => $value) {
                if (is_bool($value)) {
                    if($value) {
                        $html = preg_replace("/{%-$key (.*) %}/Us", "$1", $html);
                    } else {
                        $html = preg_replace("/{%-$key (.*) %}/Us", "", $html);
                    }
                }
            }
        }

        $html = preg_replace('/{%(.*)%}/is', "", $html);
        $html = preg_replace('/^[ \t]*[\r\n]+/m', "", $html); // Удаляем пустые строки
        return $html;
    }

}

