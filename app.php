<?php
require_once 'db_connect.php';

$short_code = $_GET['id'] ?? '';
$app = null;

if ($short_code) {
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE short_code = ?");
    $stmt->execute([$short_code]);
    $app = $stmt->fetch();
}

if (!$app) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical App - <?php echo htmlspecialchars($app['prompt']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .app-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
        }
        .app-image {
            max-width: 90%;
            max-height: 60vh;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            margin-bottom: 2rem;
        }
        .app-prompt {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            max-width: 800px;
        }
        .remix-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #ff0055, #0055ff);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <header>
        <div class="logo">
            <a href="index.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem;">
                <span class="vm-logo">VM</span> ViralMagical
            </a>
        </div>
    </header>

    <main class="app-container">
        <img src="<?php echo htmlspecialchars($app['image_url']); ?>" alt="Generated App" class="app-image">
        <h1 class="app-prompt">"<?php echo htmlspecialchars($app['prompt']); ?>"</h1>
        <p>Sponsored by <?php echo htmlspecialchars($app['sponsor']); ?></p>
        
        <a href="index.php?remix=<?php echo urlencode($app['prompt']); ?>" class="remix-btn">Remix this App</a>
    </main>
</body>
</html>
