## PHP Validator


### Introduction
This is a simple data validator originally developed for an application I am developing. Seeing as though I found this super helpful I decided to make it available for you guys.


### How to use
Before I show you how to use this you need to use the [john-crossley/db-class](https://github.com/john-crossley/db-class) I built. This is so you can ensure data is unique.

Let's move on…

    <?php
    // Require the validator
    require 'DB.php';
    require 'Validator.php';

    // Create a new instance of the validator
    $v = new Validator();
    ?>

Right so what do we need to validate?

    <form action="test.php" method="POST">

      <p>
        <label for="username">Username: </label>
        <input type="text" name="username" id="username">
      </p>

      …

Tell the validator we would like to validate the `username` field like so:

    …
    $v = new Validator();
    $bannedEmailExtensions = '@example.com @domain-name.com @fake.co.uk';
    $rules = [
        'username'       => ['required', 'min:3', 'max:62', 'unique:user'],
        'password'       => ['required', 'min:8'],
        'password_again' => ['required', 'match:password'],
        'email'          => ['required', 'valid_email', 'unique:email', 'banned:'.$bannedExtensions]
    ];

As you can see in the `$rules` array we specify several rules for the username input field those are separated by the `|` we have `require, min, max and unique` as mentioned before you may only use the `unique` if you have required the DB.php file.

Next we need to tell the Validator about these rules. We do this like so

    $v->make($_POST, $rules);

Note how I'm passing in the POST array? Any data that needs validating should be formatted as such; `array('username' => 'admin', 'password' => 'myPass123' … `

## README not complete
