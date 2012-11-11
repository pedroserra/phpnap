<?php

/**
 * class: PHPNap
 *
 * @author serra
 */
class PHPNap
{
    private $handlers = array();

    /**
     * Adds a URL listener
     *
     * @param string $className         Class that will handle the request
     * @param string|array $urlExpr     URL expression with parameters specified (or array with multiple URLs)
     */
    public function listenTo ($className, $urlExpr)
    {
        if ( is_array($urlExpr) )
            foreach ( $urlExpr as $url )
                $this->listenTo($className, $url);
        else
        {
            $this->handlers[$urlExpr] = $className;
        }
        $this->sortHandlers();
    }

    public function httpRequest ()
    {
        $ignore = substr_count(trim($_SERVER['SCRIPT_NAME'], '/'), '/');

        $url = $_SERVER['REQUEST_URI'];
        $url = explode('?', $url);
        $url = $url[0];
        $url = trim($url, '/');
        $url = '/'.implode('/', array_slice(explode('/', $url), $ignore));

        try
        {
            $output = $this->request($_SERVER['REQUEST_METHOD'], $url);
        }
        catch ( Exception $e )
        {
            $output = null;
            header("HTTP/1.1 {$e->getCode()} {$e->getMessage()}");
            error_log("HTTP/1.1 {$e->getCode()} {$e->getMessage()}");
        }
        return $output;
    }

    /**
     *
     * @param string $method        GET/POST/PUT/DELETE
     * @param string $resource      Resource path
     */
    public function request ($method, $resource)
    {
        $method = strtolower($method);

        error_log("Resource: $resource");
        foreach ( $this->handlers as $url => $handler )
        {
            $params = array();
            if ( preg_match(self::url2regex($url), $resource, $params) )
            {
                $handler = new $handler();
                if ( method_exists($handler, $method) )
                {
                    return call_user_func_array(array($handler, $method), array_slice($params,1));
                }
                else
                    throw new Exception('Method Not Allowed', 405);
            }
        }

        throw new Exception('Not Found', 404);
    }

    private function sortHandlers ()
    {
        uksort($this->handlers, function ($a, $b)
                 {
                     return substr_count($a, '/') < substr_count($b, '/');
                 });
    }

    public static function url2regex ($str)
    {
        // Parameters substitution
        $str = preg_replace('/(:.+)(\W|$)/U', '(\w+)$2', "$str");

        // Slashes
        $str = '/\A' . str_replace('/', '\/', $str) . '/';

        return $str;
    }

}

?>