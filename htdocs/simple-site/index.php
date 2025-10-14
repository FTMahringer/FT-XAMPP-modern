<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP Website</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome to Simple PHP Website</h1>
            <p class="subtitle">A very simple PHP demonstration</p>
        </header>

        <main>
            <section class="info-box">
                <h2>Server Information</h2>
                <ul>
                    <li><strong>Current Date & Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></li>
                    <li><strong>Your IP Address:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></li>
                </ul>
            </section>

            <section class="info-box">
                <h2>Dynamic Content</h2>
                <p>This page was generated dynamically using PHP.</p>
                <?php
                    $colors = ['red', 'blue', 'green', 'orange', 'purple'];
                    $randomColor = $colors[array_rand($colors)];
                ?>
                <p>Random color of the day: <span style="color: <?php echo $randomColor; ?>; font-weight: bold;"><?php echo ucfirst($randomColor); ?></span></p>
            </section>

            <section class="info-box">
                <h2>About</h2>
                <p>This is a simple PHP website created as a demonstration for the FT-XAMPP modern development environment.</p>
                <p>Access this site at: <code>http://localhost/simple-site/</code></p>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> - Simple PHP Website | Powered by FT-XAMPP</p>
        </footer>
    </div>
</body>
</html>
