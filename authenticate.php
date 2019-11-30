 <?php
require 'config.php';

if ( !isset($_POST['username'], $_POST['password']) ) {
	// Could not get the data that should have been sent.
	die ('Please fill both the username and password field!');
}

$user = Database::getObjectBySql(
	'SELECT id, rol, password FROM accounts WHERE username = ?',
	's',
	[ $_POST['username'] ]
);

if (!$user) {
	die ('El usuario no existe');
}

if (password_verify($_POST['password'], $user->password)) {
	// Verification success! User has loggedin!
	// Create sessions so we know the user is logged in, they basically act like cookies but remember the data on the server.
	session_regenerate_id();
	$_SESSION['loggedin'] = TRUE;
	$_SESSION['name'] = $_POST['username'];
	$_SESSION['id'] = $user->id;
	$_SESSION['rol'] = $user->rol;
	header('Location: home.php');
} else {
	echo 'Incorrect password!';
}
?>