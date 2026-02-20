<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GROW-CRM</title>
    <style>
        /* --- VARIÁVEIS GLOBAIS (SUA IDENTIDADE VISUAL) --- */
        :root {
            --roxo-grow: #50006C;
            --verde-grow: #4CAF50;
            --cinza-dark: #1a1d21;
            --cinza-medium: #262a30;
            --cinza-border: #464646;
            --texto-branco: #ffffff;
            --texto-muted: #9ca3af;
            --bg-body: #0f1113;
        }

        /* Reset Básico */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--texto-branco); font-size: 16px; }

        /* Estilos de Alerta (Flash Messages) */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(76, 175, 80, 0.1); border: 1px solid var(--verde-grow); color: var(--verde-grow); }
        .alert-error { background: rgba(255, 68, 68, 0.1); border: 1px solid #ff4444; color: #ff4444; }

        /* Botões Padrão */
        .btn { padding: 10px 20px; border-radius: 5px; cursor: pointer; border: none; font-weight: bold; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-primary { background: var(--roxo-grow); color: white; }
        .btn-primary:hover { background: #6a008f; }
        
        /* Inputs Padrão */
        .input-padrao { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--cinza-border); background: var(--cinza-medium); color: white; outline: none; margin-bottom: 15px; }
        .input-padrao:focus { border-color: var(--verde-grow); }
    </style>
</head>
<body>