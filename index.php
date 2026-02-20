<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/header.php'; // Já carrega todo o CSS e cores
?>

<style>
    /* CSS Específico só desta página */
    .login-wrapper { display: flex; justify-content: center; align-items: center; height: 100vh; }
    .login-box { background: var(--cinza-dark); padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; border: 1px solid var(--cinza-border); box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .logo-area { text-align: center; margin-bottom: 30px; }
    .logo-area h1 { color: var(--roxo-grow); font-size: 32px; letter-spacing: 2px; text-transform: uppercase; }
    .logo-area p { color: var(--texto-muted); font-size: 14px; margin-top: 5px; }
</style>

<div class="login-wrapper">
    <div class="login-box">
        <div class="logo-area">
            <h1>GROW-CRM</h1> <p>Faça login para gerenciar seus processos</p>
        </div>

        <?php echo getFlash('login'); ?>

        <form action="auth.php" method="POST">
            <label style="color: #d1d5db; font-size: 14px; margin-bottom: 5px; display:block;">E-mail</label>
            <input type="email" name="email" class="input-padrao" placeholder="seu@email.com" required>

            <label style="color: #d1d5db; font-size: 14px; margin-bottom: 5px; display:block;">Senha</label>
            <input type="password" name="password" class="input-padrao" placeholder="••••••••" required>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">ENTRAR</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="#" style="color: var(--texto-muted); font-size: 13px; text-decoration: none;">Esqueceu a senha?</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>