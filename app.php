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
    <link rel="stylesheet" href="/style.css">
    <style>
        .app-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
            padding: 2rem;
        }
        .app-card-display {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2rem;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .app-image {
            width: 100%;
            max-width: 800px;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .app-prompt {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .app-sponsor {
            font-size: 1rem;
            opacity: 0.7;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, opacity 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #ff0055, #0055ff);
            color: white;
            border: none;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <header>
        <div class="logo">
            <a href="/index.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem;">
                <span class="vm-logo">VM</span> ViralMagical
            </a>
        </div>
        <nav>
            <a href="/index.php" class="nav-link">Create Your Own</a>
        </nav>
    </header>

    <main class="app-container">
        <div class="app-card-display">
            <img src="<?php echo htmlspecialchars($app['image_url']); ?>" alt="Generated App" class="app-image">
            
            <h1 class="app-prompt">"<?php echo htmlspecialchars($app['prompt']); ?>"</h1>
            
            <div class="app-sponsor">
                <span>✨ Sponsored by <?php echo htmlspecialchars($app['sponsor']); ?></span>
            </div>
            
            <div class="action-buttons">
                <a href="/create-app?remix=<?php echo urlencode($app['prompt']); ?>" class="btn btn-primary">
                    <span>🎨 Remix this Style</span>
                </a>
                <a href="/index.php" class="btn btn-secondary">
                    <span>🏠 Create New</span>
                </a>
                <button onclick="navigator.clipboard.writeText(window.location.href).then(() => alert('Link copied!'))" class="btn btn-secondary">
                    <span>🔗 Share</span>
                </button>
            </div>
        </div>
    </main>
</body>
</html>
