<?php
session_start();
require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$error = null;
$showOtpModal = false;

function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digistorya@gmail.com'; // REPLACE
        $mail->Password = 'lvyu ooni neiw gxem';  // REPLACE (App Password)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'DIGIstorya');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "<p>Your OTP code is <strong>$otp</strong>. Enter it to complete login.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$_SESSION['last_otp_time'] = time();

if (isset($_POST['resend_otp']) && isset($_SESSION['admin_email'])) {
    $lastSent = $_SESSION['otp_sent_time'] ?? 0;
    $now = time();

    if ($now - $lastSent >= 180) {
        $otp = rand(100000, 999999);
        $_SESSION["otp"] = $otp;
        $_SESSION['otp_sent_time'] = $now;

        if (!sendOtpEmail($_SESSION['admin_email'], $otp)) {
            $error = "Failed to resend OTP. Try again.";
        } else {
            $showOtpModal = true;
            $resendSuccess = true;
        }
    } else {
        // Pag cooldown active, but gusto mo pa rin ipakita modal at cooldown countdown:
        $showOtpModal = true;
        $error = "Please wait before resending OTP.";
    }
}

    // Handle login
    elseif (isset($_POST['email'], $_POST['password'])) {
        $email = $_POST["email"];
        $password = $_POST["password"];
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

        $recaptchaSecret = '6LdlpTwrAAAAAM7vVRgLBF6x1P9PccwTwCO27G0Z';
        $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
        $responseData = json_decode($verifyResponse);

        if (!$responseData->success) {
            $error = "Please complete the reCAPTCHA.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($admin = $result->fetch_assoc()) {
                if (password_verify($password, $admin['password']) || $password === $admin['password']) {
                    $otp = rand(100000, 999999);
                    $_SESSION["otp"] = $otp;
                    $_SESSION["admin_email"] = $admin["email"];
                    $_SESSION["admin_id_temp"] = $admin["id"];
                    $_SESSION['otp_sent_time'] = time();

                    if (!sendOtpEmail($email, $otp)) {
                        $error = "Failed to send OTP. Try again.";
                    } else {
                        $showOtpModal = true;
                    }
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Admin user not found.";
            }
        }
    }

    // Handle OTP verification
    elseif (isset($_POST['otp'])) {
        if ($_POST['otp'] == $_SESSION['otp']) {
            $_SESSION["admin_id"] = $_SESSION["admin_id_temp"];
            unset($_SESSION["otp"], $_SESSION["admin_id_temp"], $_SESSION["otp_sent_time"]);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect OTP.";
            $showOtpModal = true;
        }
    }
?>
<?php if (!empty($resendSuccess)) : ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'OTP Resent',
    text: 'A new OTP was sent to your email.',
  });
</script>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGIstorya Admin</title>
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

       
    </style>
</head>
<body class="bg-[#f0e8e1] min-h-screen flex items-center justify-center px-4 py-8">
  <div class="bg-white rounded-3xl shadow-2xl flex flex-col md:flex-row max-w-4xl w-full overflow-hidden">
    <!-- Left Logo / Branding -->
    <div class="md:w-1/2 bg-[#551a25] text-white flex flex-col items-center justify-center p-10">
      <img src="../../images/logo.png" alt="Logo" class="w-80 h-80 mb-4">
      <p class="text-center text-base opacity-90">Welcome back Admin!</p>
    </div>

    <!-- Right Form Section -->
    <div class="md:w-1/2 p-8">
      <h2 class="text-2xl font-bold mt-8 mb-6 text-gray-800 text-center">Login to Your Account</h2>
      <form id="login-form" method="POST" class="space-y-4">
     
        <div>
          <label for="email" class="block text-base text-gray-700 mb-1">Email</label>
          <input type="text" id="email" name="email" required
            class="w-full border border-gray-300 focus:border-[#551a25]  focus:ring-2 focus:ring-[#551a25]  outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
        </div>

        <!-- Password with eye -->
        <div>
          <label for="password" class="block text-base text-gray-700 mb-1">Password</label>
          <div class="relative">
            <input type="password" name="password" id="password" placeholder="Enter your password" required
              class="w-full border border-gray-300 focus:border-[#551a25]  focus:ring-2 focus:ring-[#551a25]  outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer" onclick="togglePassword()">
              <i id="eyeIcon" class="fas fa-eye text-gray-500"></i>
            </span>
          </div>
        </div>
   

        <!-- Submit Button -->
        <button type="submit" id="submitBtn"
          class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-bold py-2 px-4 rounded-full text-base transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
          Login
        </button>
     <div class="flex justify-center">
  <div class="g-recaptcha" data-sitekey="6LdlpTwrAAAAAOtupAPS2BOHr_hoq-tcpDFLuPoU"></div>
</div>
      
      </form>
    </div>
  </div>
<div id="otpModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4 hidden">
  <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-8 relative">
    <button
      onclick="document.getElementById('closeOtpForm').submit()"
      class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 focus:outline-none"
      title="Close OTP Modal"
      aria-label="Close OTP Modal"
    >
      <i class="fas fa-times text-2xl"></i>
    </button>

    <h2 class="text-2xl font-semibold mb-6 text-center text-[#551a25]">Enter OTP sent to your email</h2>

    <form method="POST" class="space-y-5">
      <input type="text" name="otp" required maxlength="6" pattern="\d{6}"
        class="w-full px-5 py-3 border border-gray-300 rounded-lg text-center text-xl font-mono tracking-widest bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#551a25] focus:border-[#551a25]"
        placeholder="" inputmode="numeric" />

      <button type="submit"
        class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-semibold py-3 rounded-full transition duration-300 focus:outline-none focus:ring-4 focus:ring-[#7d2636]">
        Verify OTP
      </button>
    <div class="text-center mt-4">
  <button id="resendBtn" class="text-[#551a25] font-semibold">
    Resend OTP
  </button>
  <div id="cooldownTimer" class="mt-2 text-gray-600" style="display:none;">
    Please wait <span id="cooldown">180</span>s before resending.
  </div>
</div>

</div>

    </form>
  </div>
</div>

<form method="POST" action="" id="closeOtpForm" style="display:none;">
  <input type="hidden" name="close_otp_modal" value="1" />
</form>


  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      const eyeIcon = document.getElementById("eyeIcon");
      if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");
      } else {
        passwordField.type = "password";
        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
      }
    }
  </script>

<?php if (isset($error)) : ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: '<?= $error ?>',
    });

    
</script>
<?php endif; ?>
<script>
  <?php if ($showOtpModal): ?>
    document.getElementById('otpModal').classList.remove('hidden');
  <?php endif; ?>

  <?php if ($error): ?>
    Swal.fire({
      icon: 'error',
      title: 'Login Failed',
      text: '<?= $error ?>',
    });
  <?php endif; ?>
</script>
<script>
const resendBtn = document.getElementById("resendBtn");
const cooldownSpan = document.getElementById("cooldown");

// Kunin ang natitirang cooldown seconds mula server-side PHP (para consistent)
let cooldown = <?php
    if (isset($_SESSION['otp_sent_time'])) {
        $left = 180 - (time() - $_SESSION['otp_sent_time']);
        echo ($left > 0) ? $left : 0;
    } else {
        echo 0;
    }
?>;

function startCooldown() {
    if (cooldown > 0) {
        resendBtn.disabled = true;
        cooldownSpan.textContent = cooldown;
        resendBtn.textContent = `Resend OTP in ${cooldown}s`;

        const interval = setInterval(() => {
            cooldown--;
            cooldownSpan.textContent = cooldown;
            resendBtn.textContent = `Resend OTP in ${cooldown}s`;

            if (cooldown <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtn.textContent = "Resend OTP";
                cooldownSpan.textContent = "";
            }
        }, 1000);
    } else {
        resendBtn.disabled = false;
        cooldownSpan.textContent = "";
        resendBtn.textContent = "Resend OTP";
    }
}

if (<?php echo $showOtpModal ? 'true' : 'false'; ?>) {
    document.getElementById('otpModal').classList.remove('hidden');
    startCooldown();
}

</script>

</body>
</html>