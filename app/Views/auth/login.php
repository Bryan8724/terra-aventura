<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion – Terra Aventura</title>
    <!-- Anti-flash : lire le thème AVANT le rendu -->
        <script>
    (function(){
        // Pages auth : toujours en thème CLAIR (indépendant du choix utilisateur)
        document.documentElement.setAttribute('data-theme', 'light');
    })();
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* Variables light */
        :root, [data-theme="light"] {
            --bg:         linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f0ff 100%);
            --card-bg:    #ffffff;
            --card-bd:    rgba(79,70,229,.12);
            --card-sh:    0 20px 60px rgba(79,70,229,.1), 0 4px 16px rgba(0,0,0,.06);
            --input-bg:   #f8fafc;
            --input-bd:   #e2e8f0;
            --input-foc:  #4f46e5;
            --input-ring: rgba(79,70,229,.15);
            --text:       #1e293b;
            --text-sub:   #64748b;
            --text-lbl:   #475569;
            --ph:         #94a3b8;
            --link:       #4f46e5;
            --link-h:     #3730a3;
            --btn:        linear-gradient(135deg,#4f46e5,#7c3aed);
            --btn-sh:     rgba(79,70,229,.35);
            --sep:        #e2e8f0;
            --orb-op:     .35;
        }
        /* Variables dark */
        [data-theme="dark"] {
            --bg:         linear-gradient(135deg,#0f172a 0%,#1a1f3a 50%,#0f1a2e 100%);
            --card-bg:    rgba(30,41,59,.96);
            --card-bd:    rgba(255,255,255,.07);
            --card-sh:    0 25px 60px rgba(0,0,0,.55);
            --input-bg:   rgba(255,255,255,.06);
            --input-bd:   rgba(255,255,255,.1);
            --input-foc:  #6366f1;
            --input-ring: rgba(99,102,241,.2);
            --text:       #f1f5f9;
            --text-sub:   #94a3b8;
            --text-lbl:   #94a3b8;
            --ph:         #475569;
            --link:       #818cf8;
            --link-h:     #a5b4fc;
            --btn:        linear-gradient(135deg,#4f46e5,#6d28d9);
            --btn-sh:     rgba(79,70,229,.4);
            --sep:        rgba(255,255,255,.08);
            --orb-op:     .1;
        }
        /* System dark */
        @media (prefers-color-scheme: dark) {
            [data-theme="system"] {
                --bg:         linear-gradient(135deg,#0f172a 0%,#1a1f3a 50%,#0f1a2e 100%);
                --card-bg:    rgba(30,41,59,.96);
                --card-bd:    rgba(255,255,255,.07);
                --card-sh:    0 25px 60px rgba(0,0,0,.55);
                --input-bg:   rgba(255,255,255,.06);
                --input-bd:   rgba(255,255,255,.1);
                --input-foc:  #6366f1;
                --input-ring: rgba(99,102,241,.2);
                --text:       #f1f5f9; --text-sub: #94a3b8; --text-lbl: #94a3b8;
                --ph:         #475569; --link: #818cf8; --link-h: #a5b4fc;
                --btn:        linear-gradient(135deg,#4f46e5,#6d28d9);
                --btn-sh:     rgba(79,70,229,.4);
                --sep:        rgba(255,255,255,.08); --orb-op: .1;
            }
        }

        html,body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }
        body { background:var(--bg); display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1.25rem; }
        .orb  { position:fixed; border-radius:50%; filter:blur(80px); opacity:var(--orb-op); pointer-events:none; z-index:0; }
        .o1   { width:320px; height:320px; background:#818cf8; top:-80px; right:-80px; }
        .o2   { width:260px; height:260px; background:#c084fc; bottom:-60px; left:-60px; }
        .card { position:relative; z-index:1; width:100%; max-width:400px; background:var(--card-bg);
                border:1px solid var(--card-bd); border-radius:1.75rem; padding:2.25rem 2rem;
                box-shadow:var(--card-sh); backdrop-filter:blur(16px); }
        .logo { width:4.5rem; height:4.5rem; background:linear-gradient(135deg,#4f46e5,#7c3aed);
                border-radius:1.375rem; display:flex; align-items:center; justify-content:center;
                font-size:2.1rem; margin:0 auto 1.375rem; box-shadow:0 8px 24px var(--btn-sh); }
        .ttl  { text-align:center; margin-bottom:1.875rem; }
        .ttl h1 { font-size:1.5rem; font-weight:800; color:var(--text); letter-spacing:-.02em; margin-bottom:.3rem; }
        .ttl p  { font-size:.875rem; color:var(--text-sub); }
        .form { display:flex; flex-direction:column; gap:1.1rem; }
        .field{ display:flex; flex-direction:column; gap:.375rem; }
        .lbl  { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-lbl); }
        .inp  { width:100%; padding:.75rem 1rem; background:var(--input-bg); border:1.5px solid var(--input-bd);
                border-radius:.875rem; color:var(--text); font-size:1rem; outline:none;
                transition:border-color .15s,box-shadow .15s; -webkit-appearance:none; }
        .inp::placeholder { color:var(--ph); }
        .inp:focus { border-color:var(--input-foc); box-shadow:0 0 0 3px var(--input-ring); background:var(--card-bg); }
        .btn  { width:100%; padding:.875rem; background:var(--btn); color:#fff; font-size:1rem; font-weight:700;
                border:none; border-radius:.875rem; cursor:pointer; box-shadow:0 4px 16px var(--btn-sh);
                transition:all .15s; margin-top:.25rem; }
        .btn:hover { filter:brightness(1.08); transform:translateY(-1px); box-shadow:0 6px 24px var(--btn-sh); }
        .btn:active { transform:translateY(0); }
        .err  { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); border-radius:.75rem;
                padding:.75rem 1rem; font-size:.875rem; text-align:center; margin-bottom:1rem; color:#dc2626; }
        [data-theme="dark"] .err { color:#fca5a5; }
        @media (prefers-color-scheme:dark) { [data-theme="system"] .err { color:#fca5a5; } }
        .footer { text-align:center; margin-top:1.375rem; padding-top:1.375rem; border-top:1px solid var(--sep); }
        .footer a { font-size:.875rem; color:var(--link); text-decoration:none; font-weight:500; transition:color .15s; }
        .footer a:hover { color:var(--link-h); text-decoration:underline; }
    </style>
</head>
<body>
<div class="orb o1"></div>
<div class="orb o2"></div>
<div class="card">
    <div class="logo">🧭</div>
    <div class="ttl">
        <h1>Terra Aventura</h1>
        <p>Connectez-vous à votre espace</p>
    </div>
    <?php if (!empty($error)): ?>
        <div class="err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/login" class="form">
        <div class="field">
            <label class="lbl" for="login">Utilisateur ou email</label>
            <input type="text" id="login" name="login" required autocomplete="username"
                   class="inp" placeholder="nom d'utilisateur ou email">
        </div>
        <div class="field">
            <label class="lbl" for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="inp" placeholder="••••••••">
        </div>
        <button type="submit" class="btn">Se connecter →</button>
    </form>
    <div class="footer">
        <a href="/forgot-password">🔑 Mot de passe oublié ?</a>
    </div>
</div>
</body>
</html>
