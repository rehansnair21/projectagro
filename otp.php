<?php
session_start(); 
$error_message = '';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function generateVerificationCode($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendVerificationEmail($recipientEmail, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'agrofresh52@gmail.com';
        $mail->Password   = 'uauq qfdl hxsq dqwb'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS
        $mail->Port       = 587;

        $mail->setFrom('agrofresh52@gmail.com', 'Agro Fresh');
        $mail->addAddress($recipientEmail);
        $mail->Subject = 'Your Verification Code';
        $mail->Body    = "Your verification code is: $verificationCode\n\nThis code will expire in 10 minutes.";

        // Disable SSL verification (for development purposes)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $verificationCode = generateVerificationCode();
        $_SESSION['verification_code'] = $verificationCode;
        $_SESSION['email'] = $email;
        sendVerificationEmail($email, $verificationCode);
    if (isset($_POST['verify'])) {
        $enteredOTP = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
        if ($enteredOTP == $_SESSION['verification_code']) {
            header('Location: reset_password.php');
            unset($_SESSION['verification_code']);  
        } else {
            $error_message = "Incorrect OTP.";
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?crop=entropy&cs=tinysrgb&fm=jpg&ixid=MnwzMjM4NDZ8MHwxfHJhbmRvbXx8fHx8fHx8fDE2MjY4NjQ0NTE&ixlib=rb-1.2.1&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.85);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .task, .mate { 
            background: linear-gradient(135deg, #43cea2, #185a9d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .otp-inputs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .otp-inputs input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .otp-inputs input:focus {
            outline: none;
            border-color: #43cea2;
            box-shadow: 0 0 0 4px rgba(67, 206, 162, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #185a9d, #43cea2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 206, 162, 0.3);
        }

        .resend-text {
            margin-top: 1.5rem;
            color: #64748b;
        }

        .resend-text a {
            color: #43cea2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .resend-text a:hover {
            color: #185a9d;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="task">𝑨𝑮𝑹𝑶</span><span class="mate">𝑭𝑹𝑬𝑺𝑯</span>
        </div>
        <h2>OTP Verification</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <p style="color: #64748b; margin-bottom: 1.5rem;">Enter the 6-digit code sent to your email.</p>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="otp-inputs">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp2')" id="otp1" name="otp1">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp3')" id="otp2" name="otp2">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp4')" id="otp3" name="otp3">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp5')" id="otp4" name="otp4">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp6')" id="otp5" name="otp5">
                <input type="text" maxlength="1" required id="otp6" name="otp6">
            </div>
            <button type="submit" class="login-btn" name="verify">Verify OTP</button>
            <p class="resend-text">Didn't receive the code? <a href="#">Resend OTP</a></p>
        </form>
    </div>

    <script>
        function moveToNext(current, nextFieldID) {
            if (current.value.length === 1) {
                document.getElementById(nextFieldID)?.focus();
            }
        }
    </script>
</body>
</html>
