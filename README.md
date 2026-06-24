# 🎮 Plataforma de Jogos Multiplayer (MVP)

Plataforma estilo Kahoot com mini-games multiplayer de ação em tempo real.  
**Stack:** PHP (Ratchet/ReactPHP WebSocket) + Phaser 3 (Canvas) + MySQL

---

## 📁 Estrutura do Projeto

```
plataforma_jogos/
├── config/
│   └── database.php          # Conexão PDO com MySQL (lê do .env)
├── database/
│   └── schema.sql            # Script SQL para criar o banco e tabelas
├── vendor/                   # Dependências do Composer (gerado)
├── .env                      # Configurações sensíveis (NÃO comitar)
├── .gitignore
├── composer.json
├── composer.lock
├── index.php                 # Frontend: Login + Phaser 3 + WebSocket Client
├── server.php                # Servidor WebSocket (Ratchet/ReactPHP)
└── README.md
```

---

## 🚀 Passo a Passo para Rodar o Projeto

### 1. Pré-requisitos

- **PHP 8.0+** (com extensões: `pdo_mysql`, `mbstring`, `openssl`)
- **Composer** (gerenciador de dependências PHP)
- **MySQL** (recomendado: XAMPP - já incluso)
- **Apache** (para servir o `index.php`)

### 2. Configurar o Banco de Dados

**Opção A - Via linha de comando:**
```bash
mysql -u root < database/schema.sql
```

**Opção B - Via phpMyAdmin:**
1. Abra http://localhost/phpmyadmin
2. Vá na aba "SQL"
3. Copie e cole o conteúdo do arquivo `database/schema.sql`
4. Clique em "Executar"

Isso criará:
- Banco `plataforma_jogos`
- Tabelas: `salas`, `jogadores`, `perguntas`, `respostas`
- 8 perguntas de exemplo para o quiz

### 3. Configurar o .env

Edite o arquivo `.env` na raiz do projeto e ajuste conforme necessário:

```env
# --- Banco de Dados MySQL ---
DB_HOST=localhost
DB_PORT=3306
DB_NAME=plataforma_jogos
DB_USER=root
DB_PASS=

# --- Servidor WebSocket ---
WS_HOST=0.0.0.0
WS_PORT=8080
```

Para XAMPP (usuário `root`, senha vazia), não precisa alterar nada.

### 4. Instalar Dependências

```bash
cd e:\Projetos\plataforma_jogos
composer install
```

### 5. Iniciar o Servidor WebSocket

Abra um **terminal separado** e execute:

```bash
cd e:\Projetos\plataforma_jogos
php server.php
```

Você verá:
```
================================================
  Plataforma de Jogos - WebSocket Server
  Endereço: 0.0.0.0:8080
  MySQL:    localhost:3306/plataforma_jogos
  Pressione Ctrl+C para parar
================================================
[INICIO] Servidor WebSocket de Jogos Multiplayer iniciado.
```

**Deixe este terminal aberto** — o servidor precisa ficar rodando em segundo plano.

### 6. Servir o Frontend via Apache

Coloque a pasta `plataforma_jogos` dentro do `htdocs` do XAMPP (ou aponte o Apache para ela):

- Copie (ou mova) a pasta para: `E:\xampp\htdocs\plataforma_jogos`
- Acesse no navegador: **[http://localhost/plataforma_jogos/index.php](http://localhost/plataforma_jogos/index.php)**

### 7. Testar Multiplayer

1. Abra **duas abas/guia** do navegador em `http://localhost/plataforma_jogos/index.php`
2. Em cada aba:
   - Digite um nome diferente (ex: "Jogador 1" e "Jogador 2")
   - Digite o **mesmo código de sala** (ex: `DEMO`)
   - Clique em "Entrar no Jogo"
3. Use as **setas do teclado** para mover o círculo
4. Ambos se verão se movendo em tempo real ✅

---

## 🎯 Eventos WebSocket (Protocolo)

### Cliente → Servidor

| Evento | Descrição | Payload |
|--------|-----------|---------|
| `join_room` | Entrar/criar sala | `{ "type": "join_room", "roomId": "ABC", "name": "Jogador" }` |
| `send_movement` | Enviar movimento | `{ "type": "send_movement", "x": 400, "y": 300 }` |
| `submit_answer` | Responder quiz | `{ "type": "submit_answer", "answer": "B", "questionId": 1 }` |
| `leave_room` | Sair da sala | `{ "type": "leave_room" }` |

### Servidor → Cliente

| Evento | Descrição |
|--------|-----------|
| `joined_room` | Confirma entrada (já vem lista de jogadores) |
| `player_joined` | Novo jogador entrou na sala |
| `player_moved` | Jogador se moveu (atualiza posição) |
| `player_left` | Jogador saiu da sala |
| `scoreboard_update` | Placar atualizado |
| `answer_result` | Resultado da resposta do quiz |
| `error` | Erro |

---

## 🧠 Arquitetura

```
┌─────────────────────────────────────────────────┐
│                  Navegador (Chrome)              │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐   │
│  │  Phaser 3 │    │ WebSocket│    │   UI     │   │
│  │ (Canvas)  │◄──►│ (Client) │    │ (Login/  │   │
│  │           │    │          │    │  Placar) │   │
│  └──────────┘    └────┬─────┘    └──────────┘   │
└───────────────────────┼─────────────────────────┘
                        │ WebSocket (porta 8080)
                        ▼
┌─────────────────────────────────────────────────┐
│           Servidor WebSocket (PHP)               │
│  ┌─────────────────────────────────────────────┐ │
│  │  Ratchet (ReactPHP) - GameServer            │ │
│  │  • Gerencia salas em memória                │ │
│  │  • Broadcast de movimentos em tempo real    │ │
│  │  • Valida respostas do quiz                 │ │
│  └──────────────────┬──────────────────────────┘ │
└─────────────────────┼───────────────────────────┘
                      │ PDO
                      ▼
┌─────────────────────────────────────────────────┐
│              MySQL (XAMPP)                       │
│  • salas     - registro de salas ativas          │
│  • jogadores - jogadores e pontuações            │
│  • perguntas - banco de perguntas do quiz        │
│  • respostas - histórico de respostas            │
└─────────────────────────────────────────────────┘
```

---

## 🔮 Próximos Passos (Evolução do MVP)

- [ ] **Sistema de quiz completo** com temporizador e rodadas
- [ ] **Jogo Snake multiplayer** dentro da mesma sala
- [ ] **Jogo Nave (space shooter)** cooperativo
- [ ] **Autenticação** via banco de dados
- [ ] **Painel Admin** para criar perguntas
- [ ] **Ranking global** entre salas
- [ ] **Salas privadas** com senha
- [ ] **Suporte a Redis** para escalar horizontalmente

---

## 🔧 Solução de Problemas

**"Erro de conexão WebSocket" no navegador**
- Verifique se o servidor PHP está rodando: `php server.php`
- Verifique se a porta 8080 não está bloqueada por firewall

**"Could not connect to MySQL"**
- Confirme que o MySQL do XAMPP está rodando (Painel XAMPP → Start MySQL)
- Verifique as credenciais no `.env`

**Phaser não carrega**
- Verifique a conexão com a internet (CDN do Phaser)
- Ou baixe o Phaser localmente: `https://phaser.io/download`