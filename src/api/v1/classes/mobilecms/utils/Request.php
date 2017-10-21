<?php namespace mobilecms\utils;

class Request extends GenericRequest
{


  /**
  * test if an URI matches a pattern
  * @param string $pattern /store/order/{orderId}
  * @param string $uri uri

  * @return array found parameters
  */
    public function match(string $pattern) : bool
    {
        $this->params = [];
        $diffFound = false;
        if (empty($pattern)) {
            throw new \Exception('empty pattern');
        }


        $patternArray = $this->toArray($pattern);
        $uriArray = $this->toArray($this->uri);
      // basic test
        if (count($patternArray) === count($uriArray)) {
            //
            // pattern /foo/{bar}
            // uri /foo/123

            foreach ($patternArray as $key => $value) {
                if ($this->isPathParameter($value)) {
                    // sample result [ 'bar' => '123']
                      $this->params[$this->getPathParameterName($value)] = $uriArray[$key];
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
            $this->params = [];
        }

        return !$diffFound;
    }

    public function matchRequest(string $method, string $pattern) : bool
    {
        $result = false;
        if (!empty($method) && $method == $this->method) {
            $result = $this->match($pattern);
        }
        return $result;
    }

    public function getParam(string $key)
    {
        return $this->params[$key];
    }

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
