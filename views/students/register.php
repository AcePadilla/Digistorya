<?php 
session_start();
include 'db_connection.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$message = "";
$showOtpModal = false;
$otpVerified = false;
$otpFailed = false;

if (isset($_POST['register'])) {
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $otp = rand(100000, 999999);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "Email is already registered.";
    } else {
        $gender = $_POST['gender'];
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, gender, email, password, otp, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssss", $fname, $lname, $gender, $email, $password, $otp);

        if ($stmt->execute()) {
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
                $mail->addAddress($email, "$fname $lname");
                $mail->Subject = 'Your OTP Code';
                $mail->Body = "Hello $fname,\n\nYour OTP is: $otp\n\nPlease use this to verify your account.";

                $mail->send();

                $_SESSION['email'] = $email;
                $showOtpModal = true;
            } catch (Exception $e) {
                $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $message = "Registration failed: " . $conn->error;
        }
    }
}

if (isset($_POST['verify'])) {
    if (!isset($_SESSION['email'])) {
        $message = "Session expired, please register again.";
    } else {
        $enteredOtp = $_POST['otp'];
        $email = $_SESSION['email'];

        $result = $conn->query("SELECT * FROM users WHERE email='$email'");
        $user = $result->fetch_assoc();

        if ($user && $user['otp'] == $enteredOtp) {
            $conn->query("UPDATE users SET is_verified=1, otp=NULL WHERE email='$email'");
            unset($_SESSION['email']);
            $otpVerified = true;
            $showOtpModal = false;
        } else {
            $otpFailed = true;
            $showOtpModal = true;
        }
    }
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
 <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
    .strength-meter {
      height: 5px;
      border-radius: 5px;
      margin-top: 5px;
      transition: width 0.3s ease;
    }
    .strength-weak { background-color: #e74c3c; }
    .strength-medium { background-color: #f39c12; }
    .strength-strong { background-color: #2ecc71; }
  </style>
</head>
<body class="bg-[#f0e8e1] min-h-screen flex items-center justify-center px-4 py-8">
  <div class="bg-white rounded-3xl shadow-2xl flex flex-col md:flex-row max-w-4xl w-full overflow-hidden">

    <div class="md:w-1/2 bg-[#551a25] text-white flex flex-col items-center justify-center p-10">
      <img src="../../images/logo.png" alt="Logo" class="w-80 h-80 mb-4">

      <p class="text-center text-base opacity-90">Register to assess your learning style.</p>
    </div>

    <div class="md:w-1/2 p-8">
      <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Create an Account</h2>
     <form action="register.php" method="POST" id="registration-form" class="space-y-4">

  <?php if (!empty($message) && !$otpVerified && !$otpFailed): ?>
    <div class="mb-4 p-3 text-sm rounded bg-red-100 text-red-700">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <div class="flex flex-col md:flex-row gap-4">
    <div class="w-full">
      <label for="firstname" class="block text-base text-gray-700 mb-1">First Name</label>
      <input type="text" id="firstname" name="firstname" required
        class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
    </div>
    <div class="w-full">
      <label for="lastname" class="block text-base text-gray-700 mb-1">Last Name</label>
      <input type="text" id="lastname" name="lastname" required
        class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
    </div>
  </div>
<div class="w-full">
  <label class="block text-sm text-gray-700 mb-1">Gender</label>
  <div class="flex gap-2">
    <!-- Male Option -->
    <label class="cursor-pointer flex-1">
      <input type="radio" name="gender" value="Male" class="hidden peer" />
      <div class="py-2 px-1 rounded border border-gray-300 peer-checked:border-blue-500 peer-checked:shadow text-center transition-all">
        <i class="fa-solid fa-mars text-lg text-blue-600 mb-0.5"></i>
        <div class="text-xs text-blue-600 font-medium">Male</div>
      </div>
    </label>
    <!-- Female Option -->
    <label class="cursor-pointer flex-1">
      <input type="radio" name="gender" value="Female" class="hidden peer" required />
      <div class="py-2 px-1 rounded border border-gray-300 peer-checked:border-pink-500 peer-checked:shadow text-center transition-all">
        <i class="fa-solid fa-venus text-lg text-pink-600 mb-0.5"></i>
        <div class="text-xs text-pink-600 font-medium">Female</div>
      </div>
    </label>
  </div>
</div>

  <div>
    <label for="email" class="block text-base text-gray-700 mb-1">Email</label>
    <input type="email" id="email" name="email" required
      class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
  </div>

 <div>
  <label for="password" class="block text-base text-gray-700 mb-1">Password</label>
  <div class="relative">
    <input
      type="password"
      id="password"
      name="password"
      required
      class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 pr-10 rounded-lg bg-white text-gray-700 text-base"
    />
    <button
      type="button"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 p-1"
      onclick="togglePasswordVisibility('password', this)"
      tabindex="-1"
      aria-label="Toggle password visibility"
    ><i class="fas fa-eye"></i>
    </button>
  </div>
<div id="strengthBar" class="strength-meter mt-1 w-full bg-gray-200 hidden">
  <div id="strengthIndicator" class="h-1 rounded transition-all duration-300"></div>
</div>

 <div id="passwordHelp" class="text-sm text-gray-600 mt-2 hidden">
  <p id="uppercase" class="mb-1 flex items-center gap-1 text-red-600 font-semibold">
    At least 1 uppercase letter
  </p>
  <p id="lowercase" class="mb-1 flex items-center gap-1 text-red-600 font-semibold">
    At least 1 lowercase letter
  </p>
  <p id="number" class="mb-1 flex items-center gap-1 text-red-600 font-semibold">
    At least 1 number
  </p>
  <p id="special" class="mb-1 flex items-center gap-1 text-red-600 font-semibold">
    At least 1 special character
  </p>
  <p id="length" class="mb-1 flex items-center gap-1 text-red-600 font-semibold">
    8 to 16 characters long
  </p>
</div>
</div>


  <div>
    <label for="confirm_password" class="block text-base text-gray-700 mb-1">Confirm Password</label>
    <div class="relative">
      <input type="password" id="confirm_password" name="confirm_password" required
        oninput="checkPasswordMatch()"
        class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 pr-10 rounded-lg bg-white text-gray-700 text-base" />
      <button type="button"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
        onclick="togglePasswordVisibility('confirm_password', this)" tabindex="-1"><i class="fas fa-eye"></i>
      </button>
    </div>
    <p id="passwordMatchMessage" class="mt-1 text-sm"></p>
  </div>

  <button type="submit" name="register"
    class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-bold py-3 px-4 rounded-full text-base transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
    Register
  </button>

  <p class="mt-4 text-base text-center text-gray-600">
    Already have an account?
    <a href="login.php" class="text-[#551a25] font-medium hover:underline">Login</a>
  </p>
</form>

    </div>
  </div>
<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 <?= $showOtpModal ? 'block' : 'hidden' ?>">
  <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full relative border border-gray-100 ">
    
    <!-- Close Button -->
    <button id="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-[#551a25] text-xl transition duration-200">
      <i class="fas fa-times"></i>
    </button>

    <!-- Title -->
    <h3 class="text-2xl font-semibold mb-2 text-[#551a25] flex items-center gap-2">
      <i class="fas fa-shield-alt text-[#facc15] animate-pulse"></i> Verify OTP
    </h3>

    <!-- Description -->
    <p class="mb-6 text-gray-500 text-sm leading-relaxed">
      We've sent a 6-digit code to your email. Please enter it below to verify your account.
    </p>

    <!-- Form -->
    <form method="POST" action="" id="otpForm" class="space-y-5">
      
      <!-- OTP Input -->
      <input type="text" name="otp" maxlength="6" pattern="\d{6}" required
             class="w-full text-center text-xl tracking-widest px-5 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#551a25] bg-gray-50 text-gray-700 placeholder:text-gray-400"
             placeholder="" />

      <!-- Verify Button -->
      <button type="submit" name="verify"
              class="w-full bg-[#551a25] hover:bg-[#6f1d28] text-white font-semibold py-3 rounded-xl text-base transition duration-300 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
        <i class="fas fa-check text-white"></i> Verify OTP
      </button>
    </form>
  </div>
</div>


<?php if ($showOtpModal): ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("otpModal").classList.remove("hidden");
    document.getElementById("otpModal").classList.add("flex");
  });
</script>
<?php endif; ?>

</body>
<script>
const passwordInput = document.getElementById("password");
const strengthBar = document.getElementById("strengthBar");
const strengthIndicator = document.getElementById("strengthIndicator");
const passwordHelp = document.getElementById("passwordHelp");

const uppercaseEl = document.getElementById("uppercase");
const lowercaseEl = document.getElementById("lowercase");
const numberEl = document.getElementById("number");
const specialEl = document.getElementById("special");
const lengthEl = document.getElementById("length");

passwordInput.addEventListener("input", () => {
  const value = passwordInput.value;
  strengthBar.classList.remove("hidden");
  passwordHelp.classList.remove("hidden");

  // Validation flags
  const hasUppercase = /[A-Z]/.test(value);
  const hasLowercase = /[a-z]/.test(value);
  const hasNumber = /[0-9]/.test(value);
  const hasSpecial = /[^A-Za-z0-9]/.test(value);
  const isProperLength = value.length >= 8 && value.length <= 16;

  // Update indicators
  updateIndicator(uppercaseEl, hasUppercase);
  updateIndicator(lowercaseEl, hasLowercase);
  updateIndicator(numberEl, hasNumber);
  updateIndicator(specialEl, hasSpecial);
  updateIndicator(lengthEl, isProperLength);

  // Strength calculation
  const strength = [hasUppercase, hasLowercase, hasNumber, hasSpecial, isProperLength].filter(Boolean).length;
  updateStrengthBar(strength);
});

function updateIndicator(element, isValid) {
  if (isValid) {
    element.innerHTML = '<span class="text-green-600 font-bold">&#10004;</span> ' + element.textContent.slice(2);
    element.classList.remove("text-red-600");
    element.classList.add("text-green-600");
  } else {
    element.innerHTML = '<span class="text-red-600 font-bold">&#10006;</span>  ' + element.textContent.slice(2);
    element.classList.remove("text-green-600");
    element.classList.add("text-red-600");
  }
}

function updateStrengthBar(strength) {
  const percent = (strength / 5) * 100;
  strengthIndicator.style.width = percent + "%";

  strengthIndicator.className = "h-1 rounded transition-all duration-300"; // reset

  if (strength <= 2) {
    strengthIndicator.classList.add("strength-weak");
  } else if (strength === 3 || strength === 4) {
    strengthIndicator.classList.add("strength-medium");
  } else {
    strengthIndicator.classList.add("strength-strong");
  }
}

function togglePasswordVisibility(fieldId, button) {
  const field = document.getElementById(fieldId);
  const icon = button.querySelector("i");
  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    field.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

function checkPasswordMatch() {
  const password = document.getElementById('password').value;
  const confirm = document.getElementById('confirm_password').value;
  const messageEl = document.getElementById('passwordMatchMessage');

  if (confirm.length === 0) {
    messageEl.textContent = '';
    messageEl.className = 'mt-1 text-sm';
    return;
  }

  if (password === confirm) {
    messageEl.textContent = 'Password match';
    messageEl.className = 'mt-1 text-sm text-green-600';
  } else {
    messageEl.textContent = 'Password does not match';
    messageEl.className = 'mt-1 text-sm text-red-600';
  }
}

  // Modal close button
  document.getElementById('closeModal').addEventListener('click', function() {
    document.getElementById('otpModal').style.display = 'none';
  });

  // SweetAlert for OTP verification result
  <?php if ($otpVerified): ?>
    Swal.fire({
      icon: 'success',
      title: 'OTP Verified!',
      text: 'You can now log in.',
      confirmButtonColor: '#16a34a'
    }).then(() => {
      window.location.href = 'login.php';
    });
  <?php elseif ($otpFailed): ?>
    Swal.fire({
      icon: 'error',
      title: 'Incorrect OTP',
      text: 'The OTP you entered is incorrect. Please try again.',
      confirmButtonColor: '#dc2626'
    });
  <?php endif; ?>//try try try try //try try try try //try try try try //try try try try //try try try try //try try try try //try try try try //try try try try 
</script>
</html>

