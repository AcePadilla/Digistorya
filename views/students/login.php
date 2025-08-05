<?php
session_start(); 
require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$message = "";
$showOtpModal = false;
$cooldown = 180;

if (!isset($_SESSION['otp_sent_time'])) {
    $_SESSION['otp_sent_time'] = 0;
}

$now = time();
$time_left = ($_SESSION['otp_sent_time'] + $cooldown) - $now;
if ($time_left < 0) $time_left = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {

    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $secret_key = '6LdlpTwrAAAAAM7vVRgLBF6x1P9PccwTwCO27G0Z';

    if (empty($recaptcha_response)) {
        $message = "Please complete the reCAPTCHA.";
    } else {
        $recaptcha_verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha_response");
        $recaptcha_result = json_decode($recaptcha_verify, true);

        if (!$recaptcha_result['success']) {
            $message = "reCAPTCHA failed. Please try again.";
        } else {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $result = $conn->query("SELECT * FROM users WHERE email='$email'");
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_verified']) {
                    $otp = rand(100000, 999999);
                    $_SESSION['email'] = $email;
                    $_SESSION['otp'] = $otp;

                    $stmt = $conn->prepare("UPDATE users SET otp=? WHERE email=?");
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
                        $mail->addAddress($email, $user['firstname'] . ' ' . $user['lastname']);

                        $mail->Subject = 'Your Login OTP Code';
                        $mail->Body = "Hello " . $user['firstname'] . ", your login OTP is: $otp";

                        $mail->send();
                        $showOtpModal = true;
                    } catch (Exception $e) {
                        $message = "OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $message = "Please verify your email first.";
                }
            } else {
                $message = "Invalid login credentials.";
            }
        }
    }
}

$otpVerified = false;
$otpFailed = false;

if (isset($_POST['verify_otp'])) {
    $enteredOtp = $_POST['otp'];
    $email = $_SESSION['email'] ?? '';

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    $user = $result->fetch_assoc();

    if ($user && $user['otp'] == $enteredOtp) {
        $_SESSION['loggedin'] = true;
        unset($_SESSION['otp']);

        // Optional: store user info in session
        $_SESSION['user_id'] = $user['id']; // or any other info you need later

        // Check kung nakapag-take na siya ngayong buwan
        $userId = $_SESSION['user_id'];
        $currentYearMonth = date('Y-m');

        $sql = "SELECT COUNT(*) as count FROM quiz_results WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $currentYearMonth);
        $stmt->execute();
        $resultCheck = $stmt->get_result();
        $row = $resultCheck->fetch_assoc();
        $stmt->close();

        if ($row['count'] > 0) {
            // Nakapag-take na ngayong buwan, redirect sa home.php
            header("Location: home.php");
            exit;
        } else {
            // Wala pang quiz record ngayong buwan, redirect sa survey.php
            header("Location: survey.php");
            exit;
        }
    } else {
        $message = "Incorrect OTP.";
        $showOtpModal = true;
        $otpFailed = true;
    }
}

if (isset($_POST['resend_otp'])) {
    if ($time_left === 0) {
        $email = $_SESSION['email'] ?? '';
        $otp = rand(100000, 999999);

        $result = $conn->query("SELECT * FROM users WHERE email='$email'");
        $user = $result->fetch_assoc();

        if ($user) {
            $_SESSION['otp'] = $otp;

            $stmt = $conn->prepare("UPDATE users SET otp=? WHERE email=?");
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
                $mail->addAddress($email, $user['firstname'] . ' ' . $user['lastname']);

                $mail->Subject = 'Your Login OTP Code - Resend';
                $mail->Body = "Hello " . $user['firstname'] . ", your new login OTP is: $otp";

                $mail->send();
                $message = "OTP resent successfully!";
                $showOtpModal = true;

                $_SESSION['otp_sent_time'] = time();
                $time_left = $cooldown;
            } catch (Exception $e) {
                $message = "OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $showOtpModal = true;
            }
        } else {
            $message = "User session expired, please login again.";
            $showOtpModal = false;
        }
    } else {
        $message = "Please wait before resending OTP.";
        $showOtpModal = true;
    }
}

if (isset($_POST['close_otp_modal'])) {
    $showOtpModal = false;
    unset($_SESSION['email']);
    unset($_SESSION['otp']);
    $_SESSION['otp_sent_time'] = 0;
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGIstorya</title>
    <link rel="icon" href="../../images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Island+Moments&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
        .input-group:focus-within label {
        transform: translateY(-1.5rem);
        font-size: 0.75rem;
        color: #2563eb;
        }
       
    </style>
</head>
<body class="bg-[#f0e8e1] min-h-screen flex items-center justify-center px-4 py-8">
  <div class="bg-white rounded-3xl shadow-2xl flex flex-col md:flex-row max-w-4xl w-full overflow-hidden">
    <div class="md:w-1/2 bg-[#551a25] text-white flex flex-col items-center justify-center p-10">
      <img src="../../images/logo.png" alt="Logo" class="w-80 h-80 mb-4">
      <p class="text-center text-base opacity-90">Login to assess your learning style.</p>
    </div>

    <div class="md:w-1/2 p-8 mx-auto">
  <h2 class="text-2xl font-bold mt-8 mb-6 text-gray-800 text-center">Login to Your Account</h2>

  <?php if ($message && !$showOtpModal): ?>
    <div class="mb-6 p-4 text-red-700 bg-red-100 rounded-md border border-red-300 text-center font-medium">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Login Form -->
  <form method="POST" action="" id="loginForm" class="<?= $showOtpModal ? 'opacity-40 pointer-events-none' : '' ?> ">
    <div class="mb-5">
      <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
      <input type="email" name="email" id="email" required autofocus
             class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#551a25] focus:border-[#551a25]"
             placeholder="Enter your email" />
    </div>

 <div class="mb-2">
  <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
  <div class="relative flex items-center">
    <input type="password" name="password" id="password" required
           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#551a25] focus:border-[#551a25] pr-12"
           placeholder="Enter your password" />
    <button type="button" id="togglePassword"
            class="absolute right-4 text-gray-500 hover:text-[#551a25] focus:outline-none">
      <i class="far fa-eye" id="togglePasswordIcon"></i>
    </button>
  </div>
</div>


    <div class="mb-3 text-right">
      <a href="forgot_password.php" class="text-[#551a25] font-medium hover:underline flex items-center justify-end gap-1 text-sm">
        <i class="fas fa-unlock-alt"></i> Forgot Password?
      </a>
    </div>
    

    <button type="submit" name="login"
            class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-semibold py-3 rounded-full transition duration-300 focus:outline-none focus:ring-4 focus:ring-[#7d2636]">
      Login
    </button>
    <div class="flex justify-center">
          <div class="g-recaptcha mt-2" data-sitekey="6LdlpTwrAAAAAOtupAPS2BOHr_hoq-tcpDFLuPoU"></div>
        </div>
    <p class="mt-6 text-center text-gray-600 text-base">
      Don't have an account? 
      <a href="register.php" class="text-[#551a25] font-semibold hover:underline ml-1">Sign up</a>
    </p>
  </form>


<!-- OTP Modal -->
<?php if ($showOtpModal): ?>
<div id="otpModal" tabindex="-1" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-8 relative">
        <button
            onclick="document.getElementById('closeOtpForm').submit()"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 focus:outline-none"
            title="Close OTP Modal"
            aria-label="Close OTP Modal"
        >
            <i class="fas fa-times text-2xl"></i>
        </button>

        <h3 class="text-2xl font-semibold mb-6 text-center text-[#551a25]">Enter OTP sent to your email</h3>

        <form method="POST" action="" id="otpForm" class="mb-6">
            <input type="text" name="otp" maxlength="6" pattern="\d{6}" required
                   class="w-full mb-5 px-5 py-3 border border-gray-300 rounded-lg text-center text-xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-[#551a25] focus:border-[#551a25]"
                   placeholder="" inputmode="numeric" />
            <button type="submit" name="verify_otp"
                    class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-semibold py-3 rounded-full transition duration-300 focus:outline-none focus:ring-4 focus:ring-[#7d2636]">
                Verify OTP
            </button>
        </form>

        <form method="POST" action="" id="resendForm">
            <button type="submit" name="resend_otp" id="resendBtn"
                    class="w-full bg-gray-300 text-gray-700 font-semibold py-3 rounded-full transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    <?= $time_left > 0 ? 'disabled' : '' ?> >
                Resend OTP
            </button>
        </form>

        <p id="timerText" class="text-center text-sm text-gray-600 mt-4 select-none">
            <?= $time_left > 0 ? "You can resend OTP in $time_left second" . ($time_left > 1 ? 's' : '') . "." : "You can resend OTP now." ?>
        </p>
    </div>
</div>

<form method="POST" action="" id="closeOtpForm" style="display:none;">
    <input type="hidden" name="close_otp_modal" value="1" />
</form>

<script>
  let timeLeft = <?= $time_left ?>;
const resendBtn = document.getElementById('resendBtn');
const timerText = document.getElementById('timerText');

if (timeLeft > 0) {
    resendBtn.disabled = true;

    let timer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(timer);
            resendBtn.disabled = false;
            timerText.textContent = 'You can resend OTP now.';
        } else {
            timerText.textContent = `You can resend OTP in ${timeLeft} second${timeLeft > 1 ? 's' : ''}.`;
        }
    }, 1000);
}
</script>
<?php endif; ?>

<script>
// Show SweetAlert for OTP verification results
<?php if ($otpVerified): ?>
Swal.fire({
    icon: 'success',
    title: 'OTP Verified!',
    text: 'You are now logged in.',
    confirmButtonColor: '#7c3aed'
}).then(() => {
    window.location.href = 'survey.php'; 
});
<?php elseif ($otpFailed): ?>
Swal.fire({
    icon: 'error',
    title: 'Verification Failed',
    text: 'Incorrect OTP. Please try again.',
    confirmButtonColor: '#7c3aed'
});
<?php endif; ?>

// Optional: prevent form submit if OTP is not 6 digits
document.getElementById('otpForm')?.addEventListener('submit', function(e) {
    const otpInput = this.otp.value.trim();
    if (!/^\d{6}$/.test(otpInput)) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Invalid OTP',
            text: 'Please enter a 6-digit OTP.',
            confirmButtonColor: '#7c3aed'
        });
    }
});

 const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const icon = document.getElementById('togglePasswordIcon');

  togglePassword.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
  });

</script>
</body>
</html>