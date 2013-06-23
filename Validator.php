<?php
/**
 * Validator Class
 *
 * Validator is a quick and simple mockup of a nice validation
 * system built for Advanced User Manager
 *
 * @author  John Crossley <hello@phpcodemonkey.com>
 * @package advanced-user-manager
 * @version 1.0
 *
 *
 *                              ,|
 *                            //|                              ,|
 *                          //,/                             -~ |
 *                        // / |                         _-~   /  ,
 *                      /'/ / /                       _-~   _/_-~ |
 *                     ( ( / /'                   _ -~     _-~ ,/'
 *                      \~\/'/|             __--~~__--\ _-~  _/,
 *              ,,)))))));, \/~-_     __--~~  --~~  __/~  _-~ /
 *           __))))))))))))));,>/\   /        __--~~  \-~~ _-~
 *          -\(((((''''(((((((( >~\/     --~~   __--~' _-~ ~|
 * --==//////((''  .     `)))))), /     ___---~~  ~~\~~__--~
 *         ))| @    ;-.     (((((/           __--~~~'~~/
 *         ( `|    /  )      )))/      ~~~~~__\__---~~__--~~--_
 *            |   |   |       (/      ---~~~/__-----~~  ,;::'  \         ,
 *            o_);   ;        /      ----~~/           \,-~~~\  |       /|
 *                  ;        (      ---~~/         `:::|      |;|      < >
 *                 |   _      `----~~~~'      /      `:|       \;\_____//
 *           ______/\/~    |                 /        /         ~------~
 *         /~;;.____/;;'  /          ___----(   `;;;/
 *        / //  _;______;'------~~~~~    |;;/\    /
 *       //  | |                        /  |  \;;,\
 *      (<_  | ;                      /',/-----'  _>
 *       \_| ||_                     //~;~~~~~~~~~
 *           `\_|                   (,~~
 *                                   \~\
 *                                    ~~
 *
 */

class Validator
{

  protected $values = array();

  protected $messages = array(
    'required'    => 'The :attribute field is required',
    'min'         => 'The :attribute should be a minimum of :min characters',
    'max'         => 'The :attribute should be a maximum of :max characters',
    'match'       => 'The :attribute fields do not match',
    'unique'      => 'The :attribute has already been taken',
    'valid_email' => ':email doesn\'t seem to be a valid'
  );

  protected $customAttributeMessages = array();

  protected $errorMessages = array();

  protected $errors = array();


  public function __construct() {}

  public function make($data, $rules = array(), $messages = array())
  {

    if (!empty($messages)) {

      // Explode
      foreach ($messages as $key => $value) {

        $key = explode('.', $key);

        if (count($key) >= 2) {
          // Whats the attribute?
          $attribute = $key[0];
          // Whats the rule this message should be applied to?
          $rule = $key[1];
          // Store
          $this->customAttributeMessages[$attribute] = array(
            $rule => $value
          );
        }

      }

      $this->messages = array_replace($this->messages, $messages);
    }


    // Loop through and validate
    foreach ($data as $key => $value) {

      // Store the value
      $this->values[$key] = $value;

      // Set the value here because the value
      // may not be backed up during validation.
      $_SESSION['FORM_ERRORS'][$key]['value'] = $value;

      if (array_key_exists($key, $rules)) {

        $validate_against = explode('|', $rules[$key]);

        foreach ($validate_against as $rule) {

          // Perform the validation
          $this->validate($value, $rule, $key);

        }
      }
    }
    // Cleanup
    $this->removeAnyPasswords();
  }

  protected function removeAnyPasswords()
  {
    // Cheeky way for now
    foreach ($this->values as $k => $v) {
      if ($k == 'password' || $k == 'pass' || $k == 'password_again') {
        unset($this->values[$k]);
        unset($_SESSION['FORM_ERRORS'][$k]['value']);
      }
    }
  }

  protected function createMessage($message, $attributes, $rule, $attr)
  {


    $tmp = $message;
    foreach ($attributes as $attribute => $value) {

      // // Does this attribute have a custom value?
      if (isset($this->customAttributeMessages[$attr][$rule])) {
        // Right so we have a custom error message
        $tmp = $this->customAttributeMessages[$attr][$rule];
      }

      $tmp = preg_replace("/$attribute/", $value, $tmp);
    }
    return $tmp;
  }

  protected function storeErrorInformation($attribute, $rule, $data = array())
  {
    // Set an error for the attribute
    // Eg: $this->errors['username'] = true
    $this->errors[$attribute] = true;

    // Store the error message
    $this->errorMessages[$attribute][] = $this->createMessage($this->messages[$rule], $data, $rule, $attribute);

    // Store in a session
    $_SESSION['FORM_ERRORS'][$attribute]['error'] = true;
    $_SESSION['FORM_ERRORS'][$attribute]['message'] = $this->errorMessages[$attribute];
  }

  protected function validate($value, $rule, $attribute)
  {
    $rule = explode(':', $rule);

    switch (strtolower($rule[0])) {
      case 'required':
        if (empty($value)) {
          $this->storeErrorInformation($attribute, 'required', array(
            ':attribute' => $attribute
          ));
        }
        break;
      case 'min':
        if (strlen($value) <= $rule[1]) {
          $this->storeErrorInformation($attribute, 'min', array(
            ':attribute' => $attribute,
            ':min'       => $rule[1]
          ));
        }
        break;
      case 'max':
        if (strlen($value) > $rule[1]) {
          $this->storeErrorInformation($attribute, 'max', array(
            ':attribute' => $attribute,
            ':max'       => $rule[1]
          ));
        }
        break;
      case 'valid_email':
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $this->storeErrorInformation($attribute, 'valid_email', array(
            ':email' => $value
          ));
        }
        break;
      case 'unique':
        if (DB::table($rule[1])->where($attribute, '=', $value)->count()->count > 0 ) {
          $this->storeErrorInformation($attribute, 'unique', array(
            ':attribute' => $attribute
          ));
        }
        break;
      case 'match':

        if (!isset($rule[1]))
          break; // I'm out mate

        // Store the first field.
        $firstField = $attribute;
        $secondField = $rule[1];

        // Store the values
        $firstFieldValue = $this->values[$firstField];
        $secondFieldValue = $this->values[$secondField];

        if ($firstFieldValue !== $secondFieldValue) {
          $this->storeErrorInformation($attribute, 'match', array(
            // Second value
            ':attribute' => $secondField
          ));
        }
        break;

      default:
        break;
    }
  }

  /**
   * Static functions
   */
  public static function has_error($attribute)
  {
    if (isset($_SESSION['FORM_ERRORS'][$attribute])) {
      $data = $_SESSION['FORM_ERRORS']; // tmp
      if ($data[$attribute]['error']) {
        $_SESSION['FORM_ERRORS'][$attribute]['error'] = null; // Kill it
        return 'error';
      }
    }
    return false;
  }

  public static function has_value($attribute)
  {
    if (isset($_SESSION['FORM_ERRORS'][$attribute])) {
      $data = $_SESSION['FORM_ERRORS'];
      if (isset($data[$attribute]['value'])) {
        $_SESSION['FORM_ERRORS'][$attribute]['value'] = null;
        return $data[$attribute]['value'];
      }
    }
  }

  public static function has_message($attribute)
  {
    if (isset($_SESSION['FORM_ERRORS'][$attribute])) {
      $data = $_SESSION['FORM_ERRORS'];
      if (isset($data[$attribute]['message'])) {
        $_SESSION['FORM_ERRORS'][$attribute]['message'] = null;
        // Return the first error message
        return $data[$attribute]['message'][0];
      }
    }
  }

  public function hasValue($attribute)
  {
    if (isset($this->values[$attribute])) {
      return $this->values[$attribute];
    }
  }

  public function has($key)
  {
    if (isset($this->errors[$key]))
      return true;

    return false;
  }

  public function all()
  {
    return $this->errorMessages;
  }

  public function first($key)
  {
    if (isset($this->errorMessages[$key])) {
      echo $this->errorMessages[$key][0];
    }
    return false;
  }

  public function get($key)
  {
    if (isset($this->messages[$key])) {
      return $this->messages[$key];
    }
    return false;
  }

  public function success()
  {
    return (empty($this->errors)) ? true : false;
  }

  public function fails()
  {
    return (!empty($this->errors)) ? true : false;
  }

}