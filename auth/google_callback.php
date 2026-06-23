<?php
// auth/google_callback.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/google.php';

if (isset($_GET['code'])) {
    // Exchange code for token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenInfo = json_decode($response, true);
    
    if (isset($tokenInfo['access_token'])) {
        // Get user info
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenInfo['access_token']]);
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);
        
        $userInfo = json_decode($userInfoResponse, true);
        
        if (isset($userInfo['email'])) {
            $email = $userInfo['email'];
            $google_id = $userInfo['id'];
            $full_name = $userInfo['name'];
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Update google_id if not set
                if (empty($user['google_id'])) {
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $updateStmt->execute([$google_id, $user['id']]);
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['success'] = "Logged in successfully!";
                
                redirect('/user/index.php');
            } else {
                // Register new user
                $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, google_id, phone_number, password_hash, role) VALUES (?, ?, ?, '', NULL, 'user')");
                if ($insertStmt->execute([$full_name, $email, $google_id])) {
                    $newUserId = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'user';
                    $_SESSION['success'] = "Account created via Google! Please update your phone number.";
                    
                    redirect('/user/profile.php');
                } else {
                    $_SESSION['error'] = "Failed to create account.";
                    redirect('/auth/login.php');
                }
            }
        }
    } else {
        $_SESSION['error'] = "Failed to authenticate with Google.";
        redirect('/auth/login.php');
    }
} else {
    $_SESSION['error'] = "Google authentication cancelled.";
    redirect('/auth/login.php');
}
?>
