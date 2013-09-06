<?php

class ValidatorTest extends PHPUnit_Framework_TestCase
{
  private $_validator;

  private $_post = [
    'username'       => 'jonnothebonno',
    'password'       => 'password',
    'password_again' => 'password',
    'email'          => 'hello@phpcodemonkey.com'
  ];

  private $_rules = [
    'username'       => ['required', 'min:3', 'max:52'],
    'password'       => ['required', 'min:6'],
    'password_again' => ['required', 'match:password'],
    'email'          => ['required', 'valid_email']
  ];

  public function setUp()
  {
    $this->_validator = new Validator;
  }

  public function fakeEmailsDataProvider()
  {
    return [
      ['john.doe@example'],
      ['@example.com'],
      ['john'],
      ['hello@fake@mail.com']
    ];
  }

  public function testValidatorUsesCustomMessageForEmail()
  {
    $validator = $this->_validator;

    $this->_post['email'] = 'fake@mail';

    $validator->make($this->_post, $this->_rules, array(
      'email.valid_email' => 'This is my custom message for the email.'
    ));

    $this->assertEquals($validator->hasMessage('email'), 'This is my custom message for the email.');
  }

  public function testValidatorUsesCustomMessages()
  {
    // Get the validator
    $validator = $this->_validator;

    // Set some custom messages
    // 'username'       => ['required', 'min:3', 'max:52'],
    // 'password'       => ['required', 'min:6'],
    // 'password_again' => ['required', 'match:password'],
    // 'email'          => ['required', 'valid_email']
    $messages = array(
      'username.required' => 'The username is required.',
      // 'username.min' => 'The username is too short.',
      // 'username.max' => 'The username is too big.',

      'password.required' => 'The password is required.',
      // 'password.min' => 'The password is too short.',

      'password_again.required' => 'The password again is required.',
      // 'password_again.match' => 'The password again does not match password.',

      'email.required' => 'The email is required.',
      // 'email.valid_email' => 'The email address is invalid.'
    );

    $this->_post['username']       = '';
    $this->_post['password']       = '';
    $this->_post['password_again'] = '';
    $this->_post['email']          = '';

    $validator->make($this->_post, $this->_rules, $messages);

    $usernameErrors = $validator->getAttributeErrorMessages('username');
    $this->assertEquals('The username is required.', $usernameErrors['required']);

    $passwordErrors = $validator->getAttributeErrorMessages('password');
    $this->assertEquals('The password is required.', $passwordErrors['required']);

    $passwordAgainErrors = $validator->getAttributeErrorMessages('password_again');
    $this->assertEquals('The password again is required.', $passwordAgainErrors['required']);

    $emailErrors = $validator->getAttributeErrorMessages('email');
    $this->assertEquals('The email is required.', $emailErrors['required']);

  }

  // public function testValidatorFailsWhenMatchRuleIsApplied()
  // {
  //   $this->markTestIncomplete('This test has not been implemented yet.');
  // }

  /**
   * @dataProvider fakeEmailsDataProvider
   */
  public function testValidatorValidatesAgainstFakeEmails($email)
  {
    $this->_post['email'] = $email;
    $this->_validator->make($this->_post, $this->_rules);
    $this->assertTrue($this->_validator->hasError('email'));
    $this->assertTrue($this->_validator->fails());
  }

  public function testValidatorDoesNotContainAnyErrorsWithValidData()
  {
    $this->_validator->make($this->_post, $this->_rules);
    $this->assertTrue($this->_validator->passes());
  }

  public function testPasswordsAreRemoved()
  {
    // Simulated $_POST variable
    $this->_validator->make($this->_post, $this->_rules);

    $this->assertFalse($this->_validator->hasValue('password'));
    $this->assertFalse($this->_validator->hasValue('password_again'));
  }



}
