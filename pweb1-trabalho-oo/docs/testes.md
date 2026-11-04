# Testes

## Roteiro de testes no terminal

Comando: `php teste.php`

```text
PS C:\Users\breno\Documents\pweb1-trabalho-oo\pweb1-trabalho-oo> php -S localhost:8000 -t public
[Tue Nov  3 21:34:14 2026] PHP 8.4.25 Development Server (http://localhost:8000) started
[Tue Nov  3 21:34:43 2026] [::1]:50418 Accepted
[Tue Nov  3 21:34:45 2026] [::1]:50418 [200]: GET /
[Tue Nov  3 21:34:45 2026] [::1]:50418 Closing
[Tue Nov  3 21:34:45 2026] [::1]:50060 Accepted
[Tue Nov  3 21:34:45 2026] [::1]:50060 [200]: GET /estilo.css
[Tue Nov  3 21:34:45 2026] [::1]:50060 Closing 
```

**Explicação de cada linha:**

PS C:\Users\breno\Documents\pweb1-trabalho-oo\pweb1-trabalho-oo> php -S localhost:8000 -t public
(Comando utilizado para iniciar o servidor PHP local na porta 8000, definindo a pasta public como diretório raiz do projeto.)

[Tue Nov  3 21:34:14 2026] PHP 8.4.25 Development Server (http://localhost:8000) started
(Servidor de desenvolvimento PHP versão 8.4.25 iniciado com sucesso no endereço http://localhost:8000.)

[Tue Nov  3 21:34:43 2026] [::1]:50418 Accepted
(O servidor recebeu e aceitou uma conexão do navegador através do endereço local IPv6 ::1, utilizando a porta temporária 50418.)

[Tue Nov  3 21:34:45 2026] [::1]:50418 [200]: GET /
(O navegador solicitou a página inicial do sistema utilizando o método GET. O código HTTP 200 indica que a solicitação foi atendida com sucesso.)

[Tue Nov  3 21:34:45 2026] [::1]:50418 Closing
(O servidor finalizou o atendimento da primeira solicitação e encerrou essa conexão.)

[Tue Nov  3 21:34:45 2026] [::1]:50060 Accepted
(O servidor recebeu e aceitou uma segunda conexão do navegador, agora utilizando a porta temporária 50060.)

[Tue Nov  3 21:34:45 2026] [::1]:50060 [200]: GET /estilo.css
(O navegador solicitou o arquivo estilo.css, responsável pela formatação visual da página. O código HTTP 200 confirma que o arquivo foi encontrado e carregado com sucesso.)

[Tue Nov  3 21:34:45 2026] [::1]:50060 Closing
(O servidor finalizou o envio do arquivo CSS e encerrou a segunda conexão.)

## Camada web

**Uma página que existe:** `curl.exe -i "http://localhost:8000/?pagina=..."` (cole só até o início do HTML)

```text
[Tue Nov  3 21:46:37 2026] [::1]:61185 Accepted
[Tue Nov  3 21:46:37 2026] [::1]:61185 [200]: GET /index.php?pagina=ordens
[Tue Nov  3 21:46:37 2026] [::1]:61185 Closing
[Tue Nov  3 21:46:37 2026] [::1]:65028 Accepted
[Tue Nov  3 21:46:37 2026] [::1]:65028 [200]: GET /estilo.css
[Tue Nov  3 21:46:37 2026] [::1]:65028 Closing
```

**Uma página que não existe:** `curl.exe -i "http://localhost:8000/?pagina=inexistente"`

```text
[Tue Nov  3 21:47:28 2026] [::1]:60481 Accepted
[Tue Nov  3 21:47:28 2026] [::1]:60481 [404]: GET /index.php?pagina=inexistente
[Tue Nov  3 21:47:28 2026] [::1]:60481 Closing
[Tue Nov  3 21:47:28 2026] [::1]:52353 Accepted
[Tue Nov  3 21:47:28 2026] [::1]:52353 [200]: GET /estilo.css
[Tue Nov  3 21:47:28 2026] [::1]:52353 Closing
```

**A rota JSON:** `curl.exe -i "http://localhost:8000/?formato=json"`

```text
[Tue Nov  3 21:48:09 2026] [::1]:55113 Accepted
[Tue Nov  3 21:48:09 2026] [::1]:55113 [200]: GET /index.php?formato=json
[Tue Nov  3 21:48:09 2026] [::1]:55113 Closing
[Tue Nov  3 21:48:09 2026] [::1]:63228 Accepted
```

**O formulário com um dado inválido:** print da página com a mensagem de recusa e o log do servidor.

```text
[Tue Nov  3 21:50:05 2026] [::1]:65158 Accepted
[Tue Nov  3 21:50:05 2026] [::1]:65158 [200]: POST /index.php?pagina=nova
[Tue Nov  3 21:50:05 2026] [::1]:65158 Closing
[Tue Nov  3 21:50:05 2026] [::1]:63076 Accepted
[Tue Nov  3 21:50:05 2026] [::1]:63076 [200]: GET /estilo.css
[Tue Nov  3 21:50:05 2026] [::1]:63076 Closing
```
