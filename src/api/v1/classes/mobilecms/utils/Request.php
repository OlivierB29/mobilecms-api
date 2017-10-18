<?php namespace mobilecms\utils;

class Request
{


    public $uri = '';
  /**
   * Headers array.
   */
    public $headers = null;

      /**
       * Property: method
       * The HTTP method this request was made in, either GET, POST, PUT or DELETE.
       */
    public $method = '';
      /**
       * Property: endpoint
       * The Model requested in the URI.
       * eg: /files.
       */
    public $endpoint = '';
      /**
       * Property: verb
       * An optional additional descriptor about the endpoint, used for things that can
       * not be handled by the basic methods.
       * eg: /files/process.
       */
    public $verb = '';

      /**
       * Property: apiversion
       * eg : v1.
       */
    public $apiversion = '';
      /**
       * Property: args
       * Any additional URI components after the endpoint and verb have been removed, in our
       * case, an integer ID for the resource.
       * eg: /<endpoint>/<verb>/<arg0>/<arg1>
       * or /<endpoint>/<arg0>.
       */
    public $args = [];


      /**
       * Request content from post data or JSON body.
       */
    public $request = null;
}
