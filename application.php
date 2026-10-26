<?php
declare(strict_types=1);
session_start();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$fields = [
    'position' => ['label' => 'Position Applied For', 'type' => 'text', 'required' => true],
    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
    'gender' => ['label' => 'Gender (optional)', 'type' => 'text', 'required' => false],
    'nationality' => ['label' => 'Nationality (optional)', 'type' => 'text', 'required' => false],
    'dob' => ['label' => 'Date of Birth (optional)', 'type' => 'date', 'required' => false],
    'address' => ['label' => 'Address', 'type' => 'textarea', 'required' => false],
    'phone' => ['label' => 'Telephone Number', 'type' => 'tel', 'required' => false],
    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
    'education' => ['label' => 'Educational History and Qualifications', 'type' => 'textarea', 'required' => false],
    'yearsExperience' => ['label' => 'Years of Experience', 'type' => 'number', 'required' => true],
    'personalStatement' => ['label' => 'Personal Statement', 'type' => 'textarea', 'required' => false],
    'referee2Name' => ['label' => 'Referee 2 Name', 'type' => 'text', 'required' => false],
    'referee2Occupation' => ['label' => 'Referee 2 Occupation', 'type' => 'text', 'required' => false],
    'referee1Relationship' => ['label' => 'Referee 1 Relationship', 'type' => 'text', 'required' => false],
    'referee2Address' => ['label' => 'Referee 2 Address', 'type' => 'textarea', 'required' => false],
    'referee2Phone' => ['label' => 'Referee 2 Phone', 'type' => 'tel', 'required' => false],
];
$allowedFruits = ['apple', 'banana', 'orange'];
$errors = [];
$clean = [];
$values = array_fill_keys(array_keys($fields), '');
$fruits = [];
$vegetarian = false;
$receipt = null;

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['receipt'])) {
    $receipt = $_SESSION['receipt'];
    unset($_SESSION['receipt']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    foreach ($fields as $key => $field) {
        $raw = $_POST[$key] ?? '';
        if (!is_string($raw)) {
            $errors[] = $field['label'] . ': expected one text value.';
            continue;
        }
        $value = trim($raw);
        $values[$key] = $value;
        if ($field['required'] && $value === '') {
            $errors[] = $field['label'] . ' is required.';
            continue;
        }
        if (strlen($value) > 2000) {
            $errors[] = $field['label'] . ': maximum 2000 bytes.';
            continue;
        }
        if ($value !== '' && $field['type'] === 'email'
            && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
            continue;
        }
        if ($key === 'yearsExperience') {
            $years = filter_var($value, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 60]]);
            if ($years === false) {
                $errors[] = 'Experience must be a whole number from 0 to 60.';
                continue;
            }
            $value = (string) $years;
        }
        if ($key === 'dob' && $value !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($date === false || $date->format('Y-m-d') !== $value
                || $date > new DateTimeImmutable('today')) {
                $errors[] = 'Date of birth must be a valid date, not in the future.';
                continue;
            }
        }
        $clean[$key] = $value;
    }
    $rawVegetarian = $_POST['vegetarian'] ?? null;
    if ($rawVegetarian !== null && $rawVegetarian !== 'yes') {
        $errors[] = 'Vegetarian: invalid selection.';
    } else {
        $vegetarian = $rawVegetarian === 'yes';
        $clean['vegetarian'] = $vegetarian ? 'Yes' : 'No';
    }
    $rawFruits = $_POST['fruits'] ?? [];
    if (!is_array($rawFruits) || count($rawFruits) > count($allowedFruits)) {
        $errors[] = 'Fruits: expected a short list of selections.';
    } else {
        foreach ($rawFruits as $fruit) {
            if (!is_string($fruit) || !in_array($fruit, $allowedFruits, true)) {
                $errors[] = 'Fruits: invalid selection.';
            } else {
                $fruits[] = $fruit;
            }
        }
    }
    $fruits = array_values(array_unique($fruits));
    $clean['fruits'] = $fruits;
    if ($errors === []) {
        // A one-time teaching receipt, not a database or an application submission.
        $_SESSION['receipt'] = $clean;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: application.php', true, 303);
        exit;
    }
    http_response_code(422);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Job application practice</title>
  <style>
    * { box-sizing: border-box; }
    body { font: 16px/1.6 system-ui, sans-serif; max-width: 820px; margin: 30px auto; padding: 20px; color: #18352d; background: #f7f9f5; }
    form, section { background: white; padding: 24px; border: 1px solid #c9d8ca; border-radius: 10px; }
    label { display: block; margin: 14px 0; }
    input:not([type=checkbox]), textarea { width: 100%; padding: 9px; border: 1px solid #9cb3a4; border-radius: 5px; font: inherit; }
    button { background: #116b58; color: white; padding: 12px 20px; border: 0; border-radius: 5px; font: inherit; }
    fieldset { margin: 18px 0; border: 1px solid #c9d8ca; }
    .errors { color: #922c2c; }
  </style>
</head>
<body>
<h1>Job Application Form</h1>
<p>Local practice only. Use fictional data. No application is saved to a database or sent to an employer. The validated result is kept in your server session until the next page load.</p>
<?php if ($receipt !== null): ?>
<section aria-labelledby="receipt-title">
  <h2 id="receipt-title">Form Data</h2>
  <ul>
  <?php foreach ($fields as $key => $field): ?>
    <li><strong><?= e($field['label']) ?>:</strong>
      <?= e($receipt[$key] === '' ? 'Not provided' : $receipt[$key]) ?></li>
  <?php endforeach; ?>
    <li><strong>Vegetarian:</strong> <?= e($receipt['vegetarian']) ?></li>
    <li><strong>Selected Fruits:</strong>
      <?= e($receipt['fruits'] === [] ? 'None' : implode(', ', $receipt['fruits'])) ?></li>
  </ul>
</section>
<?php endif; ?>
<?php if ($errors !== []): ?>
<section class="errors" role="alert">
  <h2>Please correct these fields</h2>
  <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>
<form action="application.php" method="post">
  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
  <p>Required fields are labeled. The browser helps; PHP checks again.</p>
  <?php foreach ($fields as $key => $field): ?>
    <label for="<?= e($key) ?>"><?= e($field['label']) ?><?= $field['required'] ? ' (required)' : '' ?></label>
    <?php if ($field['type'] === 'textarea'): ?>
      <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3" maxlength="2000"><?= e($values[$key]) ?></textarea>
    <?php else: ?>
      <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($field['type']) ?>"
        value="<?= e($values[$key]) ?>" <?= $field['required'] ? 'required' : '' ?>
        <?= $key === 'yearsExperience' ? 'min="0" max="60" step="1"' : '' ?>
        <?= $key === 'dob' ? 'max="' . e(date('Y-m-d')) . '"' : '' ?>>
    <?php endif; ?>
  <?php endforeach; ?>
  <fieldset>
    <legend>Food preferences (optional)</legend>
    <label><input type="checkbox" name="vegetarian" value="yes" <?= $vegetarian ? 'checked' : '' ?>> Vegetarian</label>
    <?php foreach ($allowedFruits as $fruit): ?>
      <label><input type="checkbox" name="fruits[]" value="<?= e($fruit) ?>"
        <?= in_array($fruit, $fruits, true) ? 'checked' : '' ?>> <?= e(ucfirst($fruit)) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <button type="submit">Validate application</button>
</form>
</body>
</html>