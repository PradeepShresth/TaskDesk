<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

start_app_session();

// Already signed in? Send to dashboard.
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors  = [];
$email   = '';
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean_input($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'SELECT user_id, name, email, password, role
               FROM users
              WHERE email = ?
              LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation by issuing a fresh session ID.
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int)$user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            redirect('dashboard.php');
        }

        $errors[] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in &mdash; <?= e(APP_NAME) ?></title>
</head>
<body>
    <main class="auth-page">
        <h1>Log in to <?= e(APP_NAME) ?></h1>

        <?php if ($success): ?>
            <p class="form-success"><?= e($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login.php')) ?>" class="auth-form" novalidate>
            <p>
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= e($email) ?>" required maxlength="150" autofocus>
            </p>
            <p>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </p>
            <p>
                <button type="submit">Log in</button>
            </p>
        </form>

        <p>Don&rsquo;t have an account?
            <a href="<?= e(url('register.php')) ?>">Create one</a>.
        </p>
    </main>
</body>
</html>
