<?php

declare(strict_types=1);

// =====================================================================
// Ponto de entrada do site. Toda a lógica no topo; o HTML depois.
// Execute na raiz do repositório:
//   php -S localhost:8000 -t public -d display_errors=1 -d output_buffering=0
// =====================================================================

// 1. Carregar as classes (uma por arquivo, na pasta src/)
// require_once __DIR__ . '/../src/MinhaClasse.php';

// 2. Criar os objetos (eles são criados de novo a cada requisição)

// 3. Tratar o formulário (POST), com try/catch

// 4. Rota JSON (?formato=json): header, json_encode com paraArray(), exit

// 5. Escolher a página (?pagina=...) e o status 404
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meu sistema</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <!-- 6. Cabeçalho e menu -->
  <!-- 7. Conteúdo da página escolhida -->
  <!-- 8. Rodapé -->
</body>
</html>
