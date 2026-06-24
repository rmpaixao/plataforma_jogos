# 🎮 Game Arena - Plataforma de Jogos Educacionais

Plataforma estilo Kahoot com mini-game de ação (Space Shooter) em tempo real.  
**Stack:** PHP + MySQL + Apache + Phaser 3 (Canvas)  
**Comunicação:** HTTP Polling (sem WebSocket, sem processos background)

✅ **Pronto para hospedagem compartilhada (Hostinger, etc.)**  
✅ **Apenas subir via FTP - sem Composer, sem dependências, sem terminal**

---

## 📁 Estrutura do Projeto

```
plataforma_jogos/
├── api/
│   ├── obter_status.php       # GET - Status da sala, pergunta atual, ranking
│   ├── responder_pergunta.php # POST - Enviar resposta do quiz
│   └── salvar_score_acao.php  # POST - Salvar pontuação do mini-game
├── config/
│   └── database.php           # Conexão PDO com MySQL (edite aqui!)
├── database/
│   └── schema.sql             # Script SQL para criar o banco e tabelas
├── .env                       # Credenciais do banco (opcional, use database.php)
├── .gitignore
├── index.php                  # Frontend: Login + Phaser 3 + HTTP Polling
└── README.md
```

---

## 🚀 Como Subir para a Hostinger (ou qualquer hospedagem)

### 1. Criar o Banco de Dados

1. Acesse o **phpMyAdmin** da sua hospedagem (Hostinger: hPanel → Databases → phpMyAdmin)
2. Clique na aba **SQL**
3. Copie e cole **todo o conteúdo** do arquivo `database/schema.sql`
4. Clique em **Executar**

Isso criará:
- Banco `plataforma_jogos`
- Tabelas: `salas`, `jogadores`, `perguntas`, `respostas`
- 8 perguntas de exemplo para o quiz

### 2. Configurar a Conexão com o Banco

Edite o arquivo **`config/database.php`** com os dados do seu banco:

```php
define('DB_HOST', 'localhost');           // Hostinger: mysql.hostinger.com
define('DB_PORT', '3306');
define('DB_NAME', 'plataforma_jogos');    // Hostinger: u123456789_plataforma
define('DB_USER', 'root');                // Hostinger: u123456789_root
define('DB_PASS', '');                    // Sua senha do MySQL
```

> **Hostinger:** As credenciais estão em hPanel → Databases → MySQL Databases

### 3. Fazer Upload dos Arquivos

**Via FTP (FileZilla, etc.):**
- Conecte ao FTP da sua hospedagem
- Envie a pasta `plataforma_jogos` inteira para o diretório `public_html/`

**Via Gerenciador de Arquivos (hPanel):**
- Acesse hPanel → Files → File Manager
- Faça upload da pasta `plataforma_jogos` para `public_html/`

### 4. Acessar o Jogo

Abra no navegador:
```
https://seudominio.com/plataforma_jogos/index.php
```

---

## 🎯 Como Usar (Aluno)

1. **Digite seu Nome** (ex: "João")
2. **Digite o Código da Sala** (ex: "ABC123" - o professor informa)
3. Clique em **"Entrar no Jogo"**
4. O jogo sincroniza automaticamente com o professor via polling a cada 1.5s

### O que acontece em cada status:

| Status da Sala | O que o aluno vê |
|----------------|------------------|
| `aguardando` | Tela de espera com ranking |
| `pergunta_1` | Quiz overlay com 4 alternativas |
| `pergunta_2` | Próxima pergunta |
| `minigame` | **Space Shooter** - atirar nas naves inimigas! |
| `fim` | Tela final com ranking e pontuação total |

---

## 🎮 Mini-Game: Space Shooter (Navinha)

- **Setas do teclado:** Movimentar a nave
- **Espaço:** Atirar
- **Objetivo:** Destruir o máximo de inimigos antes de perder 3 vidas
- Ao final, a pontuação é **automaticamente enviada** para o servidor via `api/salvar_score_acao.php`

---

## 🧠 Para o Professor (Controle da Sala)

O professor controla a sala alterando a coluna `status` na tabela `salas`:

```sql
-- Criar sala
INSERT INTO salas (room_id, host_name, status) VALUES ('ABC123', 'Professor', 'aguardando');

-- Avançar para pergunta 1
UPDATE salas SET status = 'pergunta_1' WHERE room_id = 'ABC123';

-- Avançar para pergunta 2
UPDATE salas SET status = 'pergunta_2' WHERE room_id = 'ABC123';

-- Iniciar mini-game
UPDATE salas SET status = 'minigame' WHERE room_id = 'ABC123';

-- Encerrar
UPDATE salas SET status = 'fim' WHERE room_id = 'ABC123';
```

> 💡 **Dica:** Crie uma interface admin simples com botões que executam esses SQLs.

---

## 📡 API - Documentação

### `GET /api/obter_status.php`

**Parâmetros:** `room_id`, `jogador_id`

**Retorno:**
```json
{
  "status": "pergunta_1",
  "room_id": "ABC123",
  "jogador": { "jogador_id": "jogador_42", "nome": "João", "score_quiz": 0, "score_acao": 0 },
  "pergunta": {
    "id": 1,
    "pergunta": "Qual é a capital do Brasil?",
    "a": "Rio de Janeiro", "b": "Brasília", "c": "São Paulo", "d": "Salvador"
  },
  "ranking": [ { "nome": "João", "total": 0 } ],
  "ja_respondeu": false
}
```

### `POST /api/responder_pergunta.php`

**Body:**
```json
{ "room_id": "ABC123", "jogador_id": "jogador_42", "pergunta_id": 1, "resposta": "B" }
```

**Retorno:**
```json
{ "acertou": true, "resposta_certa": "B", "pontos_ganhos": 100, "score_quiz": 100 }
```

### `POST /api/salvar_score_acao.php`

**Body:**
```json
{ "room_id": "ABC123", "jogador_id": "jogador_42", "score": 1500 }
```

**Retorno:**
```json
{ "sucesso": true, "score_acao": 1500, "score_quiz": 0, "total": 1500 }
```

---

## 🔧 Solução de Problemas

**"Erro de conexão com o banco de dados"**
- Verifique as credenciais em `config/database.php`
- No Hostinger, o host geralmente é `mysql.hostinger.com` (não `localhost`)

**Tela branca ao acessar**
- Verifique se o PHP está habilitado na hospedagem
- Confira se o caminho da URL está correto

**Phaser não carrega**
- Verifique a conexão com a internet (CDN)
- Ou baixe o Phaser em `https://phaser.io/download` e coloque localmente

**Polling não funciona**
- O navegador precisa estar com JavaScript habilitado
- Verifique o console do navegador (F12) para erros

---

## 🔮 Próximos Passos

- [ ] **Painel Admin** para professor controlar a sala via interface web
- [ ] **Jogo Snake** como segundo mini-game de ação
- [ ] **Temporizador** nas perguntas do quiz
- [ ] **Exportar relatório** de respostas em CSV
- [ ] **Modo dupla** (cooperativo) no Space Shooter