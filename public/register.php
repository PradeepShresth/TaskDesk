<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

start_app_session();

// Already signed in? Skip the form.
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean_input($_POST['name'] ?? '');
    $email    = clean_input($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be 100 characters or fewer.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 150) {
        $errors[] = 'Email must be 150 characters or fewer.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Uniqueness check only if everything else passed.
    if (empty($errors)) {
        $stmt = $conn->prepare(
            'SELECT user_id FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'developer';

        $stmt = $conn->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        $stmt->execute();
        $stmt->close();

        flash_set('success', 'Account created. Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register &mdash; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
</head>
<body>
    <main class="auth-page">
        <h1>Create a <?= e(APP_NAME) ?> account</h1>

        <?php if (!empty($errors)): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(url('register.php')) ?>" class="auth-form" novalidate>
            <p>
                <label for="name">Full name</label>
                <input type="text" id="name" name="name"
                       value="<?= e($name) ?>" required maxlength="100">
            </p>
            <p>
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= e($email) ?>" required maxlength="150">
            </p>
            <p>
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       required minlength="6">
            </p>
            <p>
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       required minlength="6">
            </p>
            <p>
                <button type="submit">Create account</button>
            </p>
        </form>

        <p>Already have an account?
            <a href="<?= e(url('login.php')) ?>">Log in here</a>.
        </p>
    </main>
</body>
</html>
