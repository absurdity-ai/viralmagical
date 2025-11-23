<?php
require_once 'db_connect.php';
require_once 'config.php';
require_once 'sponsor_config.php';

// Basic Auth (Optional but recommended for a dashboard)
// For now, we'll keep it open or rely on folder protection if configured.
// Ideally, add a simple password check here.

$stmt = $pdo->query("SELECT * FROM apps ORDER BY created_at DESC LIMIT 50");
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ViralMagical Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f0f13;
            --text-color: #ffffff;
            --accent-color: #7000ff;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 2rem;
        }
        h1 { margin-bottom: 2rem; }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        .card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .card img {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
        }
        .card-content {
            padding: 1.5rem;
        }
        .meta {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
        }
        .prompt-section {
            margin-top: 1rem;
            background: rgba(0,0,0,0.3);
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.8rem;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .label {
            font-weight: bold;
            color: var(--accent-color);
            display: block;
            margin-bottom: 0.2rem;
        }
    </style>
</head>
<body>
    <h1>ViralMagical Debug Dashboard</h1>
    
    <div class="dashboard-grid">
        <?php foreach ($apps as $app): ?>
            <div class="card">
                <a href="<?php echo htmlspecialchars($app['image_url']); ?>" target="_blank">
                    <img src="<?php echo htmlspecialchars($app['image_url']); ?>" loading="lazy">
                </a>
                <div class="card-content">
                    <div class="meta">
                        <span><?php echo htmlspecialchars($app['created_at']); ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding: 0.5rem; background: rgba(255,255,255,0.05); border-radius: 8px;">
                        <?php 
                            $sponsorKey = $app['sponsor'];
                            $sponsorIcon = $sponsor_prompts[$sponsorKey]['image_icon'] ?? '';
                        ?>
                        <?php if ($sponsorIcon): ?>
                            <img src="<?php echo htmlspecialchars($sponsorIcon); ?>" style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px;">
                        <?php endif; ?>
                        <span style="font-weight: 600; color: #ddd;">
                            <?php echo htmlspecialchars($sponsor_prompts[$sponsorKey]['name'] ?? $app['sponsor']); ?>
                        </span>
                    </div>
                    
                    <div>
                        <span class="label">User Prompt:</span>
                        <?php echo htmlspecialchars($app['prompt']); ?>
                    </div>

                    <div class="prompt-section">
                        <span class="label">Full JSON Prompt:</span>
                        <?php 
                            if (!empty($app['full_prompt'])) {
                                // Pretty print JSON
                                $json = json_decode($app['full_prompt']);
                                if ($json) {
                                    echo htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                } else {
                                    echo htmlspecialchars($app['full_prompt']);
                                }
                            } else {
                                echo '<span style="opacity: 0.5; font-style: italic;">Not available (Old App)</span>';
                            }
                        ?>
                    </div>
                    
                    <div style="margin-top: 1rem; font-size: 0.8rem;">
                        <a href="app/<?php echo $app['short_code']; ?>" target="_blank" style="color: white;">View App Page</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
