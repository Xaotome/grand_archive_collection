<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

$user = new User();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $loginResult = $user->login($usernameOrEmail, $password);
        
        if ($loginResult) {
            $_SESSION['user_id'] = $loginResult['user_id'];
            $_SESSION['username'] = $loginResult['username'];
            $_SESSION['session_id'] = $loginResult['session_id'];
            
            setcookie('session_id', $loginResult['session_id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
            
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Grand Archive Collection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .links {
            text-align: center;
            margin-top: 1rem;
        }
        .links a {
            color: #007bff;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Connexion</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username_email">Nom d'utilisateur ou Email:</label>
                <input type="text" id="username_email" name="username_email" value="<?php echo htmlspecialchars($usernameOrEmail ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Se connecter</button>
        </form>
        
        <div class="links">
            <a href="register.php">Créer un compte</a> |
            <a href="../index.php">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>