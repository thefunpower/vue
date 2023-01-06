<?php

/**
 * Encodes a PHP variable into javascript representation.
 *
 * Example:
 * <pre>
 * $options=array('key1'=>true,'key2'=>123,'key3'=>'value');
 * echo php_to_js($options);
 * // The following javascript code would be generated:
 * // {'key1':true,'key2':123,'key3':'value'}
 * </pre>
 *
 * For highly complex data structures use {@link jsonEncode} and {@link jsonDecode}
 * to serialize and unserialize.
 *
 * If you are encoding user input, make sure $safe is set to true.
 *
 * @param mixed $value PHP variable to be encoded
 * @param boolean $safe If true, 'js:' will not be allowed. In case of
 * wrapping code with {@link CJavaScriptExpression} JavaScript expression
 * will stay as is no matter what value this parameter is set to.
 * Default is false. This parameter is available since 1.1.11.
 * @return string the encoded string
 */
function php_to_js($value, $safe = false)
{
    if (is_string($value)) {
        if (strpos($value, 'js:') === 0 && $safe === false)
            return substr($value, 3);
        else
            return "'" . yii_quote($value) . "'";
    } elseif ($value === null)
        return 'null';
    elseif (is_bool($value))
        return $value ? 'true' : 'false';
    elseif (is_integer($value))
        return "$value";
    elseif (is_float($value)) {
        if ($value === -INF)
            return 'Number.NEGATIVE_INFINITY';
        elseif ($value === INF)
            return 'Number.POSITIVE_INFINITY';
        else
            return str_replace(',', '.', (float)$value);  // locale-independent representation
    } elseif ($value instanceof Yii_CJavaScriptExpression)
        return $value->__toString();
    elseif (is_object($value))
        return php_to_js(get_object_vars($value), $safe);
    elseif (is_array($value)) {
        $es = array();
        if (($n = count($value)) > 0 && array_keys($value) !== range(0, $n - 1)) {
            foreach ($value as $k => $v)
                $es[] = "'" . yii_quote($k) . "':" . php_to_js($v, $safe);
            return '{' . implode(',', $es) . '}';
        } else {
            foreach ($value as $v)
                $es[] = php_to_js($v, $safe);
            return '[' . implode(',', $es) . ']';
        }
    } else
        return '';
}

function yii_quote($js, $forUrl = false)
{
    if ($forUrl)
        return strtr($js, array('%' => '%25', "\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/'));
    else
        return strtr($js, array("\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/'));
}

/**
 * CJavaScriptExpression class file.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CJavaScriptExpression represents a JavaScript expression that does not need escaping.
 * It can be passed to {@link CJavaScript::encode()} and the code will stay as is.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @package system.web.helpers
 * @since 1.1.11
 */
class Yii_CJavaScriptExpression
{
    /**
     * @var string the javascript expression wrapped by this object
     */
    public $code;

    /**
     * @param string $code a javascript expression that is to be wrapped by this object
     * @throws CException if argument is not a string
     */
    public function __construct($code)
    {
        if (!is_string($code))
            throw new Exception('Value passed to CJavaScriptExpression should be a string.');
        if (strpos($code, 'js:') === 0)
            $code = substr($code, 3);
        $this->code = $code;
    }

    /**
     * String magic method
     * @return string the javascript expression wrapped by this object
     */
    public function __toString()
    {
        return $this->code;
    }
}
