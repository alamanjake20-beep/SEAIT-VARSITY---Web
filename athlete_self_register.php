<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Check if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!empty($_SESSION['athlete_id'])) {
    header('Location: athlete_dashboard.php');
    exit;
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $sport = trim($_POST['sport'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? null;
    $contact_number = trim($_POST['contact_number'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if ($first_name === '') $errors[] = 'First name is required.';
    if ($last_name === '') $errors[] = 'Last name is required.';
    if ($middle_name === '') $errors[] = 'Middle name is required.';
    if ($student_id === '') $errors[] = 'Student ID is required.';
    if ($sport === '') $errors[] = 'Sport/varsity team is required.';
    if ($course === '') $errors[] = 'Course is required.';
    if ($year_level === '') $errors[] = 'Year level is required.';
    if (!in_array($gender, ['Male', 'Female'], true)) $errors[] = 'Please select a valid gender.';
    if ($birthdate === '') $errors[] = 'Birthdate is required.';
    if ($contact_number === '') $errors[] = 'Contact number is required.';
    if ($guardian_name === '') $errors[] = 'Guardian name is required.';
    if ($guardian_contact === '') $errors[] = 'Guardian contact is required.';
    if ($address === '') $errors[] = 'Address is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if (strlen($username) < 4) $errors[] = 'Username must be at least 4 characters.';
    if ($password === '') $errors[] = 'Password is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    // Check for duplicate username
    if (!$errors) {
        $checkStmt = $pdo->prepare("SELECT id FROM athletes WHERE username = :username");
        $checkStmt->execute(['username' => $username]);
        if ($checkStmt->fetch()) {
            $errors[] = 'This username is already taken. Please choose another.';
        }
    }

    // Handle photo upload
    $photoPath = null;
    if (!$errors) {
        try {
            $photoPath = handle_photo_upload($_FILES['photo'] ?? []);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Generate card_id
            $cardId = generate_card_id($pdo);

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO athletes
                (card_id, username, password, first_name, middle_name, last_name, student_id, sport, course, year_level,
                 gender, birthdate, contact_number, guardian_name, guardian_contact, address, photo, status)
                VALUES
                (:card_id, :username, :password, :first_name, :middle_name, :last_name, :student_id, :sport, :course, :year_level,
                 :gender, :birthdate, :contact_number, :guardian_name, :guardian_contact, :address, :photo, 'Active')
            ");
            $stmt->execute([
                'card_id' => $cardId,
                'username' => $username,
                'password' => $hashedPassword,
                'first_name' => $first_name,
                'middle_name' => $middle_name ?: null,
                'last_name' => $last_name,
                'student_id' => $student_id ?: null,
                'sport' => $sport,
                'course' => $course ?: null,
                'year_level' => $year_level ?: null,
                'gender' => $gender,
                'birthdate' => $birthdate ?: null,
                'contact_number' => $contact_number ?: null,
                'guardian_name' => $guardian_name ?: null,
                'guardian_contact' => $guardian_contact ?: null,
                'address' => $address ?: null,
                'photo' => $photoPath,
            ]);
            $newId = $pdo->lastInsertId();
            $pdo->commit();

            $_SESSION['flash_success'] = "Registration successful! Your Card ID is: {$cardId}. You can now login with your credentials.";
            header('Location: athlete_login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Athlete Registration | SEAIT VARSITY DORM VISITOR MONITORING SYSTEM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/auth-ui.css">
</head>
<body class="auth-page">
<main class="auth-layout">
  <section class="auth-hero" aria-label="SEAIT Varsity Dorm">
    <div class="auth-hero-content">
      <div class="auth-hero-badge"><span></span> SEAIT VARSITY DORMITORY</div>
      <h1>SEAIT Varsity<br>Dorm <span>Visitor</span><br><span>Monitoring System</span></h1>
      <p>A safer, more organized way to welcome and keep track of visitors at the SEAIT Varsity Dorm.</p>
    </div>
  </section>

  <section class="auth-side auth-side-register">
    <div class="auth-content auth-content-register">
      <div class="auth-brand">
        <div class="auth-brand-icon"><i class="bi bi-house-door"></i></div>
        <div>
          <strong>SEAIT Varsity Dorm</strong>
          <span>Visitor Monitoring System</span>
        </div>
      </div>

      <div class="auth-card auth-card-register">
        <header class="auth-card-heading">
          <h2>Create your account</h2>
          <p>Register as a varsity athlete to access the portal.</p>
        </header>

        <?php if ($errors): ?>
          <div class="alert alert-danger py-2">
            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="auth-form auth-register-form">
          <div class="alert alert-info py-2">
            <i class="bi bi-info-circle"></i> Complete this form to register as an athlete. A unique QR code will be generated automatically.
          </div>

          <h6 class="auth-section-heading">Personal Information</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">First Name *</label>
              <input type="text" name="first_name" class="form-control" value="<?= h($old['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name *</label>
              <input type="text" name="middle_name" class="form-control" value="<?= h($old['middle_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name *</label>
              <input type="text" name="last_name" class="form-control" value="<?= h($old['last_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Student ID *</label>
              <input type="text" name="student_id" class="form-control" value="<?= h($old['student_id'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Photo (ID Picture)</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Sport / Varsity Team *</label>
              <select name="sport" class="form-select" required>
                <option value="">-- Select Sport --</option>
                <option value="Basketball" <?= (($old['sport'] ?? '')==='Basketball')?'selected':'' ?>>Basketball</option>
                <option value="Volleyball" <?= (($old['sport'] ?? '')==='Volleyball')?'selected':'' ?>>Volleyball</option>
                <option value="Football" <?= (($old['sport'] ?? '')==='Football')?'selected':'' ?>>Football</option>
                <option value="Softball" <?= (($old['sport'] ?? '')==='Softball')?'selected':'' ?>>Softball</option>
                <option value="Swimming" <?= (($old['sport'] ?? '')==='Swimming')?'selected':'' ?>>Swimming</option>
                <option value="Track and Field" <?= (($old['sport'] ?? '')==='Track and Field')?'selected':'' ?>>Track and Field</option>
                <option value="Badminton" <?= (($old['sport'] ?? '')==='Badminton')?'selected':'' ?>>Badminton</option>
                <option value="Tennis" <?= (($old['sport'] ?? '')==='Tennis')?'selected':'' ?>>Tennis</option>
                <option value="Chess" <?= (($old['sport'] ?? '')==='Chess')?'selected':'' ?>>Chess</option>
                <option value="Table Tennis" <?= (($old['sport'] ?? '')==='Table Tennis')?'selected':'' ?>>Table Tennis</option>
                <option value="Taekwondo" <?= (($old['sport'] ?? '')==='Taekwondo')?'selected':'' ?>>Taekwondo</option>
                <option value="Karate" <?= (($old['sport'] ?? '')==='Karate')?'selected':'' ?>>Karate</option>
                <option value="Judo" <?= (($old['sport'] ?? '')==='Judo')?'selected':'' ?>>Judo</option>
                <option value="Wushu" <?= (($old['sport'] ?? '')==='Wushu')?'selected':'' ?>>Wushu</option>
                <option value="Arnis" <?= (($old['sport'] ?? '')==='Arnis')?'selected':'' ?>>Arnis</option>
                <option value="Sepak Takraw" <?= (($old['sport'] ?? '')==='Sepak Takraw')?'selected':'' ?>>Sepak Takraw</option>
                <option value="Futsal" <?= (($old['sport'] ?? '')==='Futsal')?'selected':'' ?>>Futsal</option>
                <option value="Baseball" <?= (($old['sport'] ?? '')==='Baseball')?'selected':'' ?>>Baseball</option>
                <option value="Gymnastics" <?= (($old['sport'] ?? '')==='Gymnastics')?'selected':'' ?>>Gymnastics</option>
                <option value="Dance Sport" <?= (($old['sport'] ?? '')==='Dance Sport')?'selected':'' ?>>Dance Sport</option>
                <option value="Athletics" <?= (($old['sport'] ?? '')==='Athletics')?'selected':'' ?>>Athletics</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Course *</label>
              <select name="course" class="form-select" required>
                <option value="">-- Select Course --</option>
                <option value="BSIT" <?= (($old['course'] ?? '')==='BSIT')?'selected':'' ?>>BSIT</option>
                <option value="BSCS" <?= (($old['course'] ?? '')==='BSCS')?'selected':'' ?>>BSCS</option>
                <option value="BSBA" <?= (($old['course'] ?? '')==='BSBA')?'selected':'' ?>>BSBA</option>
                <option value="BSTM" <?= (($old['course'] ?? '')==='BSTM')?'selected':'' ?>>BSTM</option>
                <option value="BSHRM" <?= (($old['course'] ?? '')==='BSHRM')?'selected':'' ?>>BSHRM</option>
                <option value="BSE" <?= (($old['course'] ?? '')==='BSE')?'selected':'' ?>>BSE</option>
                <option value="BSA" <?= (($old['course'] ?? '')==='BSA')?'selected':'' ?>>BSA</option>
                <option value="AB English" <?= (($old['course'] ?? '')==='AB English')?'selected':'' ?>>AB English</option>
                <option value="AB PolSci" <?= (($old['course'] ?? '')==='AB PolSci')?'selected':'' ?>>AB PolSci</option>
                <option value="BS Nursing" <?= (($old['course'] ?? '')==='BS Nursing')?'selected':'' ?>>BS Nursing</option>
                <option value="BS Pharmacy" <?= (($old['course'] ?? '')==='BS Pharmacy')?'selected':'' ?>>BS Pharmacy</option>
                <option value="BS Medical Technology" <?= (($old['course'] ?? '')==='BS Medical Technology')?'selected':'' ?>>BS Medical Technology</option>
                <option value="BS Biology" <?= (($old['course'] ?? '')==='BS Biology')?'selected':'' ?>>BS Biology</option>
                <option value="BS Chemistry" <?= (($old['course'] ?? '')==='BS Chemistry')?'selected':'' ?>>BS Chemistry</option>
                <option value="BS Physics" <?= (($old['course'] ?? '')==='BS Physics')?'selected':'' ?>>BS Physics</option>
                <option value="BS Mathematics" <?= (($old['course'] ?? '')==='BS Mathematics')?'selected':'' ?>>BS Mathematics</option>
                <option value="BS Psychology" <?= (($old['course'] ?? '')==='BS Psychology')?'selected':'' ?>>BS Psychology</option>
                <option value="BS Architecture" <?= (($old['course'] ?? '')==='BS Architecture')?'selected':'' ?>>BS Architecture</option>
                <option value="BS Civil Engineering" <?= (($old['course'] ?? '')==='BS Civil Engineering')?'selected':'' ?>>BS Civil Engineering</option>
                <option value="BS Electrical Engineering" <?= (($old['course'] ?? '')==='BS Electrical Engineering')?'selected':'' ?>>BS Electrical Engineering</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Year Level *</label>
              <select name="year_level" class="form-select" required>
                <option value="">-- Select Year Level --</option>
                <option value="1st Year" <?= (($old['year_level'] ?? '')==='1st Year')?'selected':'' ?>>1st Year</option>
                <option value="2nd Year" <?= (($old['year_level'] ?? '')==='2nd Year')?'selected':'' ?>>2nd Year</option>
                <option value="3rd Year" <?= (($old['year_level'] ?? '')==='3rd Year')?'selected':'' ?>>3rd Year</option>
                <option value="4th Year" <?= (($old['year_level'] ?? '')==='4th Year')?'selected':'' ?>>4th Year</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Gender *</label>
              <select name="gender" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="Male" <?= (($old['gender'] ?? '')==='Male')?'selected':'' ?>>Male</option>
                <option value="Female" <?= (($old['gender'] ?? '')==='Female')?'selected':'' ?>>Female</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Birthdate *</label>
              <input type="date" name="birthdate" class="form-control" value="<?= h($old['birthdate'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Contact Number *</label>
              <input type="text" name="contact_number" class="form-control" value="<?= h($old['contact_number'] ?? '') ?>" required>
            </div>
          </div>

          <h6 class="auth-section-heading">Guardian Information</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Guardian Name *</label>
              <input type="text" name="guardian_name" class="form-control" value="<?= h($old['guardian_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Guardian Contact *</label>
              <input type="text" name="guardian_contact" class="form-control" value="<?= h($old['guardian_contact'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Address *</label>
              <textarea name="address" class="form-control" rows="2" required><?= h($old['address'] ?? '') ?></textarea>
            </div>
          </div>

          <h6 class="auth-section-heading">Account Information</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Username *</label>
              <input type="text" name="username" class="form-control" value="<?= h($old['username'] ?? '') ?>" required minlength="4">
              <small class="text-muted">At least 4 characters</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password *</label>
              <input type="password" name="password" class="form-control" required minlength="6">
              <small class="text-muted">At least 6 characters</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password *</label>
              <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn auth-submit w-100"><i class="bi bi-person-plus"></i> Complete Registration</button>
            <a href="login.php" class="btn auth-create-account w-100 mt-2">Back to Login</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
</body>
</html>