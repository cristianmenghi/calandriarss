<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login ~ Calandria RSS</title>
    <link rel="stylesheet" href="/assets/css/admin-terminal.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="terminal-container">
        <div class="terminal-header">
            <span class="terminal-title">calandria@rss:~$</span>
            <span class="terminal-controls">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </span>
        </div>
        
        <div class="terminal-body">
            <pre class="ascii-art">
   ___      _                _      _       
  / __\__ _| | __ _ _ __   __| |_ __(_) __ _ 
 / /  / _` | |/ _` | '_ \ / _` | '__| |/ _` |
/ /__| (_| | | (_| | | | | (_| | |  | | (_| |
\____/\__,_|_|\__,_|_| |_|\__,_|_|  |_|\__,_|
                                              
RSS Aggregator System v2.0
            </pre>

            <?php if (isset($error)): ?>
            <div class="terminal-error">
                <span class="prompt">ERROR:</span> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="login-form">
                <form method="POST" action="/login">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    
                    <div class="form-group">
                        <label class="prompt">
                            <span class="prompt-symbol">></span> username:
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            class="terminal-input" 
                            required 
                            autofocus
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label class="prompt">
                            <span class="prompt-symbol">></span> password:
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            class="terminal-input" 
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="terminal-button">
                            <span class="prompt-symbol">></span> login
                        </button>
                    </div>
                </form>
            </div>

            <div class="terminal-footer">
                <p class="hint">Default credentials: admin / admin123</p>
                <p class="hint">Change password after first login</p>
            </div>
        </div>
    </div>

    <script>
        // Terminal typing effect for inputs
        document.querySelectorAll('.terminal-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('active');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('active');
            });
        });

        // Add cursor blink effect
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.terminal-input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.setAttribute('data-value', this.value);
                });
            });
        });
    </script>
</body>
</html>
