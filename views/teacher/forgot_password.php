<?php
session_start(); 
require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$message = "";
$step = 1;

if (isset($_POST['send_otp'])) {
    $email = $_POST['email'];
    $result = $conn->query("SELECT * FROM teachers WHERE email='$email'");
    $teacher = $result->fetch_assoc();

    if ($teacher) {
        $_SESSION['reset_email'] = $email;
        $otp = rand(100000, 999999);

        $stmt = $conn->prepare("UPDATE teachers SET otp=? WHERE email=?");
        $stmt->bind_param("ss", $otp, $email);
        $stmt->execute();
        $stmt->close();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'digistorya@gmail.com';
            $mail->Password = 'lvyu ooni neiw gxem';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('digistorya@gmail.com', 'Digistorya');
            $mail->addAddress($email, $teacher['firstname'].' '.$teacher['lastname']);
            $mail->Subject = 'Your Password Reset OTP Code';
            $mail->Body = "Hello ".$teacher['firstname'].", your password reset OTP is: $otp";

            $mail->send();
            $message = "OTP sent to your email.";
            $step = 2;
        } catch (Exception $e) {
            $message = "OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Email not found.";
    }
}

if (isset($_POST['verify_otp'])) {
    $enteredOtp = $_POST['otp'];
    $email = $_SESSION['reset_email'] ?? '';
    $result = $conn->query("SELECT * FROM teachers WHERE email='$email'");
    $teacher = $result->fetch_assoc();

    if ($teacher && $teacher['otp'] == $enteredOtp) {
        $message = "OTP verified. Please reset your password.";
        $step = 3;
    } else {
        $message = "Incorrect OTP.";
        $step = 2;
    }
}

if (isset($_POST['reset_password'])) {
    $email = $_SESSION['reset_email'] ?? '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $step = 3;
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $step = 3;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE teachers SET password=?, otp=NULL WHERE email=?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['reset_email']);
        $message = "Password reset successful. You can now login.";
        $step = 1;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Digistorya</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Poppins', sans-serif; }
    .input-wrapper { position: relative; }
    .toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #555;
    }
  </style>
</head>
<body class="bg-[#f0e8e1] min-h-screen flex items-center justify-center px-4 py-8">
  <div class="bg-white rounded-3xl shadow-2xl flex flex-col md:flex-row max-w-4xl w-full overflow-hidden">
    <div class="md:w-1/2 bg-[#551a25] text-white flex flex-col items-center justify-center p-10">
      <img src="../../images/logo.png" alt="Logo" class="w-52 h-52 mb-4">

    </div>

    <div class="md:w-1/2 p-8">
      <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Reset Your Password</h2>

      <?php if ($message): ?>
      <div class="mb-4 p-3 text-sm rounded <?= $step === 1 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
      <form method="POST">
        <label class="block text-gray-700 font-semibold mb-2">Email Address</label>
        <input type="email" name="email" required
               class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#551a25] mb-4"
               placeholder="Enter your registered email">
        <button name="send_otp" type="submit"
                class="w-full bg-[#551a25] hover:bg-[#45121c] text-white font-semibold py-2 rounded transition duration-300">
          Send OTP
        </button>
      </form>

      <?php elseif ($step === 2): ?>
      <form method="POST">
        <label class="block text-gray-700 font-semibold mb-2">Enter OTP</label>
        <input type="text" name="otp" maxlength="6" required pattern="\d{6}"
               class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#551a25] mb-4"
               placeholder="6-digit OTP">
        <button name="verify_otp" type="submit"
                class="w-full bg-[#551a25] hover:bg-[#45121c] text-white font-semibold py-2 rounded transition duration-300">
          Verify OTP
        </button>
      </form>

      <?php elseif ($step === 3): ?>
      <form method="POST">
        <label class="block text-gray-700 font-semibold mb-2">New Password</label>
        <div class="input-wrapper mb-4">
          <input type="password" name="password" id="password" required
                 class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#551a25]"
                 placeholder="Enter new password">
          <span class="toggle" onclick="togglePassword('password')">👁</span>
        </div>

        <label class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
        <div class="input-wrapper mb-4">
          <input type="password" name="confirm_password" id="confirm_password" required
                 class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#551a25]"
                 placeholder="Confirm new password">
          <span class="toggle" onclick="togglePassword('confirm_password')">👁</span>
        </div>
<div id="match-indicator" class="text-sm font-semibold mb-4"></div>

        <button name="reset_password" type="submit"
                class="w-full bg-[#551a25] hover:bg-[#45121c] text-white font-semibold py-2 rounded transition duration-300">
          Reset Password
        </button>
      </form>
      <?php endif; ?>

      <div class="mt-6 text-center text-sm text-gray-600">
        <a href="login.php" class="text-[#551a25] hover:underline">Back to Login</a>
      </div>
    </div>
  </div>

  <script>
     function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
  }

  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const indicator = document.getElementById('match-indicator');

  if (password && confirmPassword && indicator) {
    function checkMatch() {
      if (confirmPassword.value === "") {
        indicator.textContent = "";
        indicator.className = "text-sm font-semibold mb-4";
      } else if (password.value === confirmPassword.value) {
        indicator.textContent = "✅ Passwords match";
        indicator.className = "text-sm font-semibold mb-4 text-green-600";
      } else {
        indicator.textContent = "❌ Passwords do not match";
        indicator.className = "text-sm font-semibold mb-4 text-red-600";
      }
    }

    password.addEventListener('input', checkMatch);
    confirmPassword.addEventListener('input', checkMatch);
  }
  </script>
</body>
</html>
