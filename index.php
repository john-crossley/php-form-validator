<?php
require_once 'Validator.php';
$validator = new Validator();
?>

<DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
      font-weight: 300;
      color: #2c3e50;
    }
    form {
      border: 1 solid #bdc3c7;
      padding: 10px;
      width: 500px;
      background: #ecf0f1;
    }
    input {
      border: 1px solid #bdc3c7;
      color: #34495e;
      padding: 8px 5px;
      height: 30px;
      border-radius: 3px;
      font-size: 14px;
      width: 250px;
      outline: none;
    }
    small {
      display: block;
      margin-top: 4px;
    }
    .error {color: #c0392b;}
    .success { color: #2ecc71;}
    label {
      display: block;
      margin-bottom: 4px;
    }
    form div {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

  <div class="container">

    <form action="index.php" method="POST">
      <div>
        <label for="username">Username:</label>
        <input type="text" name="username" id="username">
      </div>
      <div>
        <label for="fullname">Full name:</label>
        <input type="text" name="fullname" id="fullname">
      </div>
      <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email">
      </div>
      <div>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password">
      </div>
      <div>
        <label for="password_again">Password again:</label>
        <input type="password" name="password_again" id="password_again">
      </div>
      <div>
        <button type="submit">Create account</button>
      </div>
    </form>

  </div><!--//container-->

</body>
</html>