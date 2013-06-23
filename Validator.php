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
  /**
   * Stores the values of the form being validated.
   * @var array
   */
  protected $values = array();

  /**
   * Default messages for the current validation methods.
   * @var array
   */
  protected $messages = array(
    'required'    => 'The :attribute field is required',
    'min'         => 'The :attribute should be a minimum of :min characters',
    'max'         => 'The :attribute should be a maximum of :max characters',
    'match'       => 'The :attribute fields do not match',
    'unique'      => 'The :attribute has already been taken',
    'valid_email' => ':email doesn\'t seem to be a valid'
  );

  /**
   * Stores any custom attribute messages that may be supplied.
   * @var array
   */
  protected $customAttributeMessages = array();

  /**
   * Stores any error messages that the object has found.
   * @var array
   */
  protected $errorMessages = array();

  /**
   * Stores either a true or false value for a form attribute. If
   * an error has been found then the attribute will yield true
   * Eg: array('username' => true, 'password' => false)
   * @var array
   */
  protected $errors = array();


  /**
   * Make - Carry's out the validation process on the
   * data passed to the method.
   * @param  array  $data     The data array to be validated, for example $_POST may
   * be passed as a parameter.
   * @param  array  $rules    The rules array, what rules should be carried
   * out on the data array
   * @param  array  $messages Any custom messages to be used instead of the default
   * messages supplied by the class.
   * @return NULL
   */
  public function make(array $data, array $rules = array(), array $messages = array())
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

  /**
   * Cheeky little method to remove any attributes that look
   * like passwords. This is to stop the Validator saving password
   * data and entering it back into the form.
   */
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

  /**
   * Creates a message to be displayed on the form. This takes a generic
   * message Eg: 'The :attribute should be a minimum of :min characters.' and
   * replaced the placeholders.
   * @param  string $message    The message to be shown to the user.
   * @param  array $attributes The attributs to be replaced in the message.
   * Eg: $attributes = [':attribute' => 'email', ':min' => '3']
   * @param  string $rule       The name of the rule. Eg: required
   * @param  string $attr       The name of the attribute
   * @return string             The prepared message.
   */
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

  /**
   * Stores the error information to be accessed from outside of the
   * Validator object. It also creates sessions should the Validator be
   * used across multiple pages. (If that makes sense)
   * @param  string $attribute The name of the current attribute
   * @param  string $rule      The name of the current rule.
   * @param  array  $data      The data array for the custom error message.
   * The data from this array is used to replace the string placeholders to
   * make the message more dynamic. Like so: :attribute => 'email'
   * @return NULL
   */
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

  /**
   * Performs the validation on an attribute. When an attribute is linked
   * to a rule, it will eventually trickle down to this method.
   * @param  string $value     The value in question
   * @param  string $rule      The rule the value must adhere to.
   * @param  string $attribute The name of the attribute.
   * @return NULL
   */
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
   * Checks to see if a form attribute has an error associated with it
   * with the use of PHP SESSIONS. This is useful for cross page form requests.
   * Once this method has been used it will destory the session associated with
   * the attribute. If your not after the use of sessions then use the non-static
   * version of this method.
   *
   * @param  string  $attribute The name of the attribute
   * @return string            If an error is found then 'error'
   * will be returned.
   */
  public static function hasError($attribute)
  {
    if (isset($_SESSION['FORM_ERRORS'][$attribute])) {
      $data = $_SESSION['FORM_ERRORS']; // tmp
      if ($data[$attribute]['error']) {
        $_SESSION['FORM_ERRORS'][$attribute]['error'] = null; // Kill it
        return 'error';
      }
    }
  }

  /**
   * Checks to see if the specified form attribute has an error relating
   * to it. If so then true is returned or false - If none.
   *
   * @param  string  $attribute The name of the attribute.
   * @return boolean            True if error relating is found, false if not.
   */
  public function hasError($attribute)
  {
    if (isset($this->errors[$attribute]))
      return true;
    return false;
  }

  /**
   * Checks to see if the form attribute has a value associated with it
   * again with the use of PHP SESSIONS. This is useful for cross page form
   * requests. If you don't require the use of sessions then use the non-static
   * version of this method.
   *
   * @param  string  $attribute The name of the form attribute
   * @return string            If a value is found then it will be returned.
   */
  public static function hasValue($attribute)
  {
    if (isset($_SESSION['FORM_ERRORS'][$attribute])) {
      $data = $_SESSION['FORM_ERRORS'];
      if (isset($data[$attribute]['value'])) {
        $_SESSION['FORM_ERRORS'][$attribute]['value'] = null;
        return $data[$attribute]['value'];
      }
    }
  }

  /**
   * Checks to see if the specified form attribute has a value associated
   * with it. If so then it is returned.
   *
   * @param  string  $attribute The name of the form attribute.
   * @return string            The value if one is found.
   */
  public function hasValue($attribute)
  {
    if (isset($this->values[$attribute])) {
      return $this->values[$attribute];
    }
  }

  /**
   * Chcks to see if the form attribute has an error message associated with it
   * again with the use of PHP SESSIONS. If the use of sessions is not needed
   * then use the non-static version of this method.
   *
   * @param  string  $attribute The name of the form attribute
   * @return string            If a message is found then it is returned.
   */
  public static function hasMessage($attribute)
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

  /**
   * Checks to see if the specified form attribute has an error message
   * relating to it. If so then that error message is returned.
   *
   * @param  string  $attribute
   * @return string      The error message if one is found.
   */
  public function hasMessage($attribute)
  {
    if (isset($this->errorMessages[$attribute])) {
      return $this->errorMessages[$attribute][0];
    }
  }

  /**
   * Returns all of the error messages
   *
   * @return array All of the error messages relating to the form.
   */
  public function all()
  {
    return $this->errorMessages;
  }

  /**
   * Simply returns true upon success.
   * @return bool Returns trus if no errors are found.
   */
  public function success()
  {
    return (empty($this->errors)) ? true : false;
  }

  /**
   * Simply returns true if any errors relating to the form are found.
   * @return bool Returns true if errors are found.
   */
  public function fails()
  {
    return (!empty($this->errors)) ? true : false;
  }

}