<?php namespace locker;

class Request {

  // Canonicalization of common params to improve maintenance and reduce bugs.
  const authParam = 'Authorization';
  const authUser = 'user';
  const authPass = 'password';

  //the current request
  protected $request;
  //the payload content
  protected $payload;
  // Stores/caches params from the payload.
  protected $params;

  /**
   * Constructs a new Locker Request.
   */
  public function __construct() {
    $this->request = \Request::instance();
    $this->payload = $this->request->getContent(); 

    $this->request_params = \Request::all();
    $this->payload_params = $this->getPayloadParams();

    $this->params = array_merge($this->payload_params, $this->request_params);

  }

  /**
   * Gets the data stored in the request payload.
   * @return AssocArray params from the payload.
   */
  public function getPayloadParams() {
    $payloadParams = [];
    parse_str($this->payload, $payloadParams); // Parse the payload into an AssocArray.
    $payloadParams = json_decode(json_encode($payloadParams), true);
    return $payloadParams;
  }

  /**
   * Gets the user from the basic auth.
   * @return String user in the basic auth.
   */
  public function getUser() {
    $user = \Request::getUser();

    // If the password is set in the headers then return it.
    if ($user) {
      return $user;
    }

    // Else return it from the payload.
    else {
      return $this->getAuth()[self::authUser];
    }
  }

  /**
   * Gets the password from the basic auth.
   * @return String password in the basic auth.
   */
  public function getPassword() {
    $pass = \Request::getPassword();

    // If the password is set in the headers then return it.
    if ($pass) {
      return $pass;
    }

    // Else return it from the payload.
    else {
      return $this->getAuth()[self::authPass];
    }
  }

  /**
   * Gets a header from the request headers.
   * @param  $key Header to be returned.
   * @param  $default Value to be returned if the header is not set.
   * @return mixed Value of the header.
   */
  public function header($key, $default=null) {
    $value = \Request::header($key);

    // If the key is set in the headers then return it.
    if ($value) {
      return $value;
    }

    // Else return it from the payload.
    else {
      return $this->getParam($key, $default);
    }
  }

  /**
   * Gets the stored/cached params.
   * @return AssocArray Stored/cached params.
   */
  public function all() {
    return $this->params;
  }

  /**
   * Gets the stored/cached params.
   * @return AssocArray Stored/cached params.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Gets a param from the stored/cached params.
   * @param  String $key Param to be retrieved.
   * @param  mixed $default Value to be returned if the param is not set.
   * @return mixed Value of the param.
   */
  public function getParam($key, $default = null) {
    // If the key has been set then return its value.
    if ($this->hasParam($key)) {
      return $this->params[$key];
    }

    // If the key has not been set then return the default value.
    else {
      return $default;
    }
  }

  public function getContent() {
    return $this->getParam('content', $this->payload );
  }

  /**
   * Determines if the param is set.
   * @param  String  $key Param to be checked.
   * @return boolean True if the param exists, false if it doesn't.
   */
  public function hasParam($key) {
    return isset($this->params[$key]);
  }

  /**
   * Gets the authentication details from the stored/cached params.
   * @return AssocArray Basic auth details.
   */
  private function getAuth() {
    $result = [];

    // If the basic auth details are set, decode and return them.
    if ($this->hasParam(self::authParam)) {
      $auth = explode(' ', $this->getParam(self::authParam));
      $decoded = base64_decode($auth[1]);
      $auth_parts = explode(':', $decoded);
      
      $result[self::authUser] = $auth_parts[0];
      $result[self::authPass] = $auth_parts[1];
    }

    // If the basic auth details are not set return a null user and password.
    else {
      $result[self::authUser] = null;
      $result[self::authPass] = null;
    }

    return $result;
  }
}