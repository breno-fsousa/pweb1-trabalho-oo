# Testes

## Roteiro de testes no terminal

Comando: `php teste.php`

```text
(cole aqui a saída do terminal)
```

**Explicação de cada linha:**

_(Para cada linha: qual regra foi testada e o que a saída comprova.)_

## Camada web

**Uma página que existe:** `curl.exe -i "http://localhost:8000/?pagina=..."` (cole só até o início do HTML)

```text
(cole aqui a saída do terminal)
```

**Uma página que não existe:** `curl.exe -i "http://localhost:8000/?pagina=inexistente"`

```text
(cole aqui a saída do terminal)
```

**A rota JSON:** `curl.exe -i "http://localhost:8000/?formato=json"`

```text
(cole aqui a saída do terminal)
```

**O formulário com um dado inválido:** print da página com a mensagem de recusa e o log do servidor.

```text
(cole aqui o log do servidor)
```
