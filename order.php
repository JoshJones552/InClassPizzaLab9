<?php
function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: indexPizzalab.html');
    exit;
}

$customer = $_POST['customer'] ?? '';
if (!is_string($customer) || trim($customer) === ''
    || strlen(trim($customer)) > 40) {
    $errors[] = 'Enter a chef name of 1–40 bytes.';
} else {
    $customer = trim($customer);
}

$size = $_POST['size'] ?? '';
if (!is_string($size)
    || !in_array($size, ['small', 'medium', 'large'], true)) {
    $errors[] = 'Choose a valid pizza size.';
}

// B: Check quantity.
$quantity = $_POST['quantity'] ?? '';
if (!is_string($quantity)) {
    $quantity = false;
} else {
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
}
if ($quantity === false || $quantity < 1 || $quantity > 10) {
    $errors[] = 'Quantity must be a whole number from 1 to 10.';
}

// C: Check the optional toppings list.
$toppings = $_POST['toppings'] ?? [];
$allowed = ['pepperoni', 'mushrooms', 'peppers'];

if (!is_array($toppings)) {
    $errors[] = 'Invalid toppings.';
} else {
    foreach ($toppings as $topping) {
        if (!is_string($topping)
            || !in_array($topping, $allowed, true)) {
            $errors[] = 'Choose toppings from the form.';
            break;
        }
    }
}

// D: Check optional notes.
$notes = $_POST['notes'] ?? '';
if (!is_string($notes) || strlen($notes) > 500) {
    $errors[] = 'Notes must be no more than 500 bytes.';
} else {
    $notes = trim($notes);
}

// E: All processing above is complete.
if ($errors !== []) {
    http_response_code(422);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pizza ticket</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php if ($errors !== []): ?>
  <h1>Check your order</h1>
  <ul>
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <h1>Your pizza ticket</h1>
  <p>Chef: <?= e($customer) ?></p>
  <p>Size: <?= e($size) ?></p>
  <p>Quantity: <?= e($quantity) ?></p>
  <p>Toppings: <?= e($toppings === [] ? 'Plain cheese' : implode(', ', $toppings)) ?></p>
  <p>Notes: <?= e($notes === '' ? 'None' : $notes) ?></p>
  <p>Practice only. No real order was placed.</p>
<?php endif; ?>

<a href="indexPizzalab.html">Back to the pizza form</a>
</body>
</html>