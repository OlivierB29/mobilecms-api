<?php namespace mobilecms\utils;

class UrlUtils
{
    /**
    * URI to array
    * @param string $uri request uri
    *
    * @return array uri parts
    */
    public function toArray(string $uri)
    {
        return explode('/', rtrim(ltrim($uri, '/'), '/'));
    }

    /**
    * test if an URI matches a pattern
    *
    * @param string $pattern /store/order/{orderId}
    * @param string $uri uri

    * @return array found parameters
    */
    public function match($pattern, $uri, &$matches = null) :bool
    {
        $diffFound = false;
        if (empty($pattern)) {
            // @codeCoverageIgnoreStart
            throw new \Exception('empty pattern');
            // @codeCoverageIgnoreEnd
        }


        $patternArray = $this->toArray($pattern);
        $uriArray = $this->toArray($uri);
        // basic test
        if (count($patternArray) === count($uriArray)) {
            //
            // pattern /foo/{bar}
            // uri /foo/123

            foreach ($patternArray as $key => $value) {
                if ($this->isPathParameter($value)) {
                    // sample result [ 'bar' => '123']
                    if (isset($matches)) {
                        $matches[$this->getPathParameterName($value)] = $uriArray[$key];
                    }
                } else {
                    //  /foo/{bar} VS /foo/123

                    // /foo/{bar} VS /aaa/123
                    if ($value !== $uriArray[$key]) {
                        $diffFound = true;
                    }
                }
            }
        } else {
            $diffFound = true;
        }



        if ($diffFound) {
            $matches = [];
        }

        return !$diffFound;
    }



    /**
    * Test if path element is a parameter.
    * {foo} => true
    * bar => false
    *
    * @param string $path element
    *
    * @return bool true if path element is a parameter
    */
    public function isPathParameter($value): bool
    {
        // return 1 === preg_match('(\{[-a-zA-Z0-9_]*\})', '{paramvalue}');

        return StringUtils::startsWith($value, '{') && StringUtils::endsWith($value, '}');
    }

    /**
    * Get a path parameter, taken from an URI.
    * @param string $value element : {bar}
    * @return string bar
    */
    public function getPathParameterName($value): string
    {
        $result = null;

        if (StringUtils::startsWith($value, '{') && StringUtils::endsWith($value, '}')) {
            $result = \substr($value, 1, strlen($value) - 2);
        }

        return $result;
    }
}
