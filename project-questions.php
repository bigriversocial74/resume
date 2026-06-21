<?php
require_once __DIR__ . '/app/bootstrap.php';
$submitted = false;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        create_project_request($_POST);
        $submitted = true;
    } catch (Throwable $e) {
        $error = 'Please check the form and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Questions Agent | David Evans</title>
</head>
<body>
<h1>Project Questions Agent</h1>
<?php if ($submitted): ?><p>Your project questions were submitted.</p><?php endif; ?>
<?php if ($error): ?><p><?= e($error) ?></p><?php endif; ?>
<form method="post">
<?= csrf_field() ?>
<label>Name <input name="full_name" required></label>
<label>Email <input name="email" type="email" required></label>
<label>Project notes <textarea name="notes"></textarea></label>
<button type="submit">Submit Project Questions</button>
</form>
</body>
</html>
