<?php
session_start();
require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$error = null;
$showOtpModal = false;

function sendOTPEmail($toEmail, $otpCode) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digistorya@gmail.com';
        $mail->Password = 'lvyu ooni neiw gxem';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('digistorya@gmail.com', 'DIGIstorya');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code';
        $mail->Body = "<h3>Your OTP Code is: <b>$otpCode</b></h3><p>Please use this code to verify your login.</p>";
        $mail->AltBody = "Your OTP Code is: $otpCode. Please use this code to verify your login.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if (isset($_POST['verify_otp'])) {
    $userOtp = $_POST['otp'] ?? '';
    $savedOtp = $_SESSION['otp_code'] ?? '';
    $savedEmail = $_SESSION['otp_email'] ?? '';

    if ($userOtp === $savedOtp && !empty($savedEmail)) {
        $_SESSION["teacher_email"] = $savedEmail;
        unset($_SESSION['otp_code'], $_SESSION['otp_email']);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid OTP code. Please try again.";
        $showOtpModal = true;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['verify_otp'])) {
    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    $secret_key = '6LdlpTwrAAAAAM7vVRgLBF6x1P9PccwTwCO27G0Z';

    $verify_response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha_response");
    $response_data = json_decode($verify_response);

    if (!$response_data->success) {
        $error = "reCAPTCHA verification failed. Please try again.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($teacher = $result->fetch_assoc()) {
            if (password_verify($password, $teacher['password']) || $password === $teacher['password']) {
                $otp = rand(100000, 999999);
                if (sendOTPEmail($email, $otp)) {
                    $_SESSION['otp_code'] = strval($otp);
                    $_SESSION['otp_email'] = $email;
                    $showOtpModal = true;
                } else {
                    $error = "Failed to send OTP email. Please try again later.";
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Teacher user not found.";
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
            <p class="text-center text-base opacity-90">Welcome back Teacher!</p>
        </div>

        <!-- Right Form Section -->
        <div class="md:w-1/2 p-8">
            <?php if (!$showOtpModal): ?>
            <!-- Login Form -->
            <h2 class="text-2xl font-bold mt-8 mb-6 text-gray-800 text-center">Login to Your Account</h2>
            <form id="login-form" method="POST" class="space-y-4">
                <div>
                    <label for="email" class="block text-base text-gray-700 mb-1">Email</label>
                    <input type="text" id="email" name="email" required placeholder="Enter your email"
                        class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
                </div>

                <div>
                    <label for="password" class="block text-base text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required
                            class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg bg-white text-gray-700 text-base" />
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer" onclick="togglePassword()">
                            <i id="eyeIcon" class="fas fa-eye text-gray-500"></i>
                        </span>
                    </div>
                </div>
                
                    <div class="mb-3 text-right">
                    <a href="forgot_password.php" class="text-[#551a25] font-medium hover:underline flex items-center justify-end gap-1 text-sm">
                        <i class="fas fa-unlock-alt"></i> Forgot Password?
                    </a>
                    </div>
                <button type="submit" id="submitBtn"
                    class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-bold py-2 px-4 rounded-full text-base transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    Login
                </button>
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="6LdlpTwrAAAAAOtupAPS2BOHr_hoq-tcpDFLuPoU"></div>
                </div>
            </form>

            <?php else: ?>
            <!-- OTP Modal Form -->
            <h2 class="text-2xl font-bold mt-8 mb-6 text-[#551a25] text-center">Enter OTP Code</h2>
            <p class="mb-4 text-gray-700">An OTP code was sent to your email. Please enter it below to continue.</p>
            <form id="otp-form" method="POST" class="space-y-4">
                <input type="text" name="otp" maxlength="6" required placeholder="Enter OTP"
                    class="w-full border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25] outline-none py-2 px-3 rounded-lg text-gray-700 text-center text-xl tracking-widest" />
                <button type="submit" name="verify_otp"
                    class="w-full bg-[#551a25] hover:bg-[#7d2636] text-white font-bold py-2 px-4 rounded-full text-base transition duration-300">
                    Verify OTP
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

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

<?php if ($error): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= htmlspecialchars($error) ?>',
    });
</script>
<?php endif; ?>

</body>
</html>