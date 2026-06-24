<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Jogos Multiplayer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        #app {
            text-align: center;
            width: 100%;
            max-width: 900px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #f7971e, #ffd200);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: none;
        }
        .subtitle {
            color: #aaa;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* Tela de Login */
        #login-screen {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 420px;
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.1);
        }
        #login-screen input {
            width: 100%;
            padding: 14px 18px;
            margin: 10px 0;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        #login-screen input:focus {
            border-color: #f7971e;
        }
        #login-screen input::placeholder {
            color: #888;
        }
        #login-screen button {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(90deg, #f7971e, #ffd200);
            color: #1a1a2e;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        #login-screen button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(247,151,30,0.4);
        }
        #login-error {
            color: #ff6b6b;
            margin-top: 10px;
            font-size: 0.9rem;
            display: none;
        }

        /* Tela de Jogo */
        #game-screen {
            display: none;
        }
        #game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: rgba(255,255,255,0.06);
            border-radius: 12px;
            margin-bottom: 10px;
        }
        #room-info {
            font-size: 0.9rem;
            color: #ccc;
        }
        #room-info strong {
            color: #ffd200;
        }
        #player-count {
            font-size: 0.9rem;
            color: #aaa;
        }
        #game-container {
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.1);
        }
        #game-container canvas {
            display: block;
            margin: 0 auto;
        }
        #scoreboard {
            margin-top: 10px;
            background: rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .score-item {
            background: rgba(255,255,255,0.08);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .score-item .name { color: #ffd200; font-weight: 600; }
        .score-item .pts { color: #4ecdc4; }

        #connection-status {
            position: fixed;
            top: 15px;
            right: 15px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-connected { background: #2ecc71; color: #fff; }
        .status-disconnected { background: #e74c3c; color: #fff; }
        .status-connecting { background: #f39c12; color: #fff; }
    </style>
</head>
<body>
    <div id="app">
        <h1>🎮 Game Arena</h1>
        <p class="subtitle">Plataforma de Jogos Multiplayer em Tempo Real</p>

        <!-- Status da Conexão -->
        <div id="connection-status" class="status-connecting">Conectando...</div>

        <!-- Tela de Login -->
        <div id="login-screen">
            <h2 style="margin-bottom: 1rem; font-size: 1.4rem;">Entrar na Sala</h2>
            <input type="text" id="input-name" placeholder="Seu Nome" maxlength="20" autocomplete="off">
            <input type="text" id="input-room" placeholder="Código da Sala (ex: ABC123)" maxlength="10" autocomplete="off" style="text-transform: uppercase;">
            <button id="btn-join">🎯 Entrar no Jogo</button>
            <div id="login-error"></div>
        </div>

        <!-- Tela de Jogo -->
        <div id="game-screen">
            <div id="game-header">
                <div id="room-info">Sala: <strong id="room-code">---</strong></div>
                <div id="player-count">👥 <span id="player-count-num">0</span> jogadores</div>
            </div>
            <div id="game-container"></div>
            <div id="scoreboard"></div>
        </div>
    </div>

    <!-- Phaser 3 via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>

    <script>
    // ============================================================
    // CLIENTE WEB - Plataforma de Jogos Multiplayer
    // ============================================================

    (function() {
        'use strict';

        // ---- Elementos DOM ----
        const loginScreen    = document.getElementById('login-screen');
        const gameScreen     = document.getElementById('game-screen');
        const inputName      = document.getElementById('input-name');
        const inputRoom      = document.getElementById('input-room');
        const btnJoin        = document.getElementById('btn-join');
        const loginError     = document.getElementById('login-error');
        const roomCodeEl     = document.getElementById('room-code');
        const playerCountEl  = document.getElementById('player-count-num');
        const scoreboardEl   = document.getElementById('scoreboard');
        const connStatusEl   = document.getElementById('connection-status');

        // ---- Estado do Jogador ----
        let playerName = '';
        let roomId     = '';
        let myId       = null;
        let players    = {};   // { id: { id, name, x, y, score } }
        let ws         = null;
        let game       = null;
        let playerSprites = {}; // id -> Phaser.GameObjects.Arc
        let nameTexts     = {}; // id -> Phaser.GameObjects.Text
        let localCursor   = { x: 400, y: 300 };

        // ---- Config WebSocket ----
        const WS_URL = 'ws://localhost:8080';

        // ============================================================
        // WEBSOCKET
        // ============================================================

        function connectWebSocket() {
            setConnStatus('connecting', 'Conectando...');

            ws = new WebSocket(WS_URL);

            ws.onopen = function() {
                console.log('[WS] Conectado ao servidor!');
                setConnStatus('connected', '🟢 Conectado');
                // Se já tinha dados de login, tenta reconectar
                if (playerName && roomId) {
                    sendJoinRoom();
                }
            };

            ws.onclose = function() {
                console.log('[WS] Desconectado.');
                setConnStatus('disconnected', '🔴 Desconectado');
                // Tenta reconectar após 3s
                setTimeout(connectWebSocket, 3000);
            };

            ws.onerror = function(err) {
                console.error('[WS] Erro:', err);
                setConnStatus('disconnected', '🔴 Erro de conexão');
            };

            ws.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleServerMessage(data);
                } catch (e) {
                    console.error('[WS] Erro ao parsear mensagem:', e);
                }
            };
        }

        function sendToServer(data) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify(data));
            }
        }

        function sendJoinRoom() {
            sendToServer({
                type: 'join_room',
                roomId: roomId,
                name: playerName
            });
        }

        function sendMovement(x, y) {
            sendToServer({
                type: 'send_movement',
                x: Math.round(x),
                y: Math.round(y)
            });
        }

        function setConnStatus(status, text) {
            connStatusEl.textContent = text;
            connStatusEl.className = 'status-' + status;
        }

        // ============================================================
        // HANDLER DAS MENSAGENS DO SERVIDOR
        // ============================================================

        function handleServerMessage(data) {
            switch (data.type) {
                case 'joined_room':
                    myId = data.yourId;
                    roomCodeEl.textContent = data.roomId;
                    players = {};
                    (data.players || []).forEach(p => {
                        players[p.id] = p;
                    });
                    updatePlayerCount();
                    updateScoreboard();
                    // Inicializa sprites no Phaser
                    if (game) {
                        syncAllSprites();
                    }
                    break;

                case 'player_joined':
                    if (data.player) {
                        players[data.player.id] = data.player;
                        updatePlayerCount();
                        updateScoreboard();
                        if (game) {
                            addPlayerSprite(data.player);
                        }
                    }
                    break;

                case 'player_moved':
                    if (data.player && players[data.player.id]) {
                        players[data.player.id].x = data.player.x;
                        players[data.player.id].y = data.player.y;
                        if (game) {
                            updatePlayerSprite(data.player.id);
                        }
                    }
                    break;

                case 'player_left':
                    if (data.playerId && players[data.playerId]) {
                        delete players[data.playerId];
                        updatePlayerCount();
                        updateScoreboard();
                        if (game) {
                            removePlayerSprite(data.playerId);
                        }
                    }
                    break;

                case 'scoreboard_update':
                    if (data.players) {
                        data.players.forEach(p => {
                            if (players[p.id]) {
                                players[p.id].score = p.score;
                            }
                        });
                        updateScoreboard();
                    }
                    break;

                case 'answer_result':
                    // Feedback visual da resposta (futuro)
                    console.log('[QUIZ] Resposta:', data);
                    break;

                case 'error':
                    console.error('[SERVER]', data.message);
                    alert('Erro: ' + data.message);
                    break;
            }
        }

        // ============================================================
        // PHASER 3 - JOGO
        // ============================================================

        function initGame() {
            if (game) {
                game.destroy(true);
                game = null;
                playerSprites = {};
                nameTexts = {};
            }

            const config = {
                type: Phaser.AUTO,
                width: 800,
                height: 600,
                parent: 'game-container',
                backgroundColor: '#1a1a2e',
                physics: {
                    default: 'arcade',
                    arcade: {
                        gravity: { y: 0 },
                        debug: false
                    }
                },
                scene: {
                    create: createScene,
                    update: updateScene
                }
            };

            game = new Phaser.Game(config);
        }

        function createScene() {
            const self = this;

            // Grid decorativo
            const graphics = this.add.graphics();
            graphics.lineStyle(1, 0x2a2a4e, 0.3);
            for (let x = 0; x <= 800; x += 50) {
                graphics.moveTo(x, 0);
                graphics.lineTo(x, 600);
            }
            for (let y = 0; y <= 600; y += 50) {
                graphics.moveTo(0, y);
                graphics.lineTo(800, y);
            }
            graphics.strokePath();

            // Borda da arena
            const border = this.add.graphics();
            border.lineStyle(3, 0x4ecdc4, 0.6);
            border.strokeRect(2, 2, 796, 596);

            // Texto de instrução
            this.add.text(400, 20, 'Use as SETAS do teclado para se mover', {
                fontSize: '14px',
                color: '#888',
                fontFamily: 'Arial'
            }).setOrigin(0.5, 0);

            // Captura teclado
            this.cursors = this.input.keyboard.createCursorKeys();

            // Sincroniza sprites existentes
            syncAllSprites();
        }

        function updateScene() {
            if (!myId) return;

            const cursors = this.cursors;
            let dx = 0, dy = 0;
            const speed = 5;

            if (cursors.left.isDown)  dx -= speed;
            if (cursors.right.isDown) dx += speed;
            if (cursors.up.isDown)    dy -= speed;
            if (cursors.down.isDown)  dy += speed;

            if (dx !== 0 || dy !== 0) {
                // Atualiza posição local
                localCursor.x = Phaser.Math.Clamp(localCursor.x + dx, 10, 790);
                localCursor.y = Phaser.Math.Clamp(localCursor.y + dy, 10, 590);

                // Atualiza sprite local
                if (playerSprites[myId]) {
                    playerSprites[myId].setPosition(localCursor.x, localCursor.y);
                    if (nameTexts[myId]) {
                        nameTexts[myId].setPosition(localCursor.x, localCursor.y - 25);
                    }
                }

                // Envia movimento para o servidor
                sendMovement(localCursor.x, localCursor.y);
            }
        }

        // ============================================================
        // GERENCIAMENTO DE SPRITES
        // ============================================================

        function syncAllSprites() {
            if (!game || !game.scene.scenes[0]) return;
            const scene = game.scene.scenes[0];

            // Remove sprites antigos
            Object.keys(playerSprites).forEach(id => removePlayerSprite(id));

            // Recria para todos os jogadores
            Object.values(players).forEach(p => addPlayerSprite(p));
        }

        function addPlayerSprite(player) {
            if (!game || !game.scene.scenes[0]) return;
            const scene = game.scene.scenes[0];

            if (playerSprites[player.id]) return;

            const isMe = player.id === myId;
            const color = isMe ? 0xffd200 : 0x4ecdc4;
            const size = isMe ? 16 : 14;

            // Círculo do jogador
            const circle = scene.add.circle(player.x, player.y, size, color, 0.9);
            circle.setStrokeStyle(2, isMe ? 0xff6b6b : 0xffffff, 0.5);
            playerSprites[player.id] = circle;

            // Nome do jogador
            const nameText = scene.add.text(player.x, player.y - 25, player.name, {
                fontSize: '12px',
                color: isMe ? '#ffd200' : '#ffffff',
                fontFamily: 'Arial',
                fontStyle: 'bold'
            }).setOrigin(0.5, 0.5);
            nameTexts[player.id] = nameText;
        }

        function updatePlayerSprite(id) {
            const player = players[id];
            if (!player) return;

            if (playerSprites[id]) {
                playerSprites[id].setPosition(player.x, player.y);
            }
            if (nameTexts[id]) {
                nameTexts[id].setPosition(player.x, player.y - 25);
            }
        }

        function removePlayerSprite(id) {
            if (playerSprites[id]) {
                playerSprites[id].destroy();
                delete playerSprites[id];
            }
            if (nameTexts[id]) {
                nameTexts[id].destroy();
                delete nameTexts[id];
            }
        }

        // ============================================================
        // UI - PLACAR E CONTAGEM
        // ============================================================

        function updatePlayerCount() {
            const count = Object.keys(players).length;
            playerCountEl.textContent = count;
        }

        function updateScoreboard() {
            const sorted = Object.values(players).sort((a, b) => b.score - a.score);
            scoreboardEl.innerHTML = sorted.map(p => {
                const isMe = p.id === myId;
                return `<div class="score-item">
                    <span class="name">${isMe ? '⭐ ' : ''}${p.name}</span>
                    <span class="pts">${p.score} pts</span>
                </div>`;
            }).join('');
        }

        // ============================================================
        // LOGIN / ENTRAR NA SALA
        // ============================================================

        btnJoin.addEventListener('click', function() {
            const name = inputName.value.trim();
            const room = inputRoom.value.trim().toUpperCase();

            if (!name) {
                showLoginError('Digite seu nome.');
                return;
            }
            if (!room) {
                showLoginError('Digite o código da sala.');
                return;
            }
            if (name.length > 20) {
                showLoginError('Nome muito longo (máx 20 caracteres).');
                return;
            }
            if (room.length > 10) {
                showLoginError('Código da sala muito longo.');
                return;
            }

            hideLoginError();

            playerName = name;
            roomId = room;

            // Muda para tela de jogo
            loginScreen.style.display = 'none';
            gameScreen.style.display = 'block';

            // Inicializa Phaser
            initGame();

            // Envia join_room
            if (ws && ws.readyState === WebSocket.OPEN) {
                sendJoinRoom();
            } else {
                // Se não estiver conectado, aguarda reconexão
                setConnStatus('connecting', 'Aguardando conexão...');
            }
        });

        // Enter nos inputs
        inputName.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') btnJoin.click();
        });
        inputRoom.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') btnJoin.click();
        });

        function showLoginError(msg) {
            loginError.textContent = msg;
            loginError.style.display = 'block';
        }

        function hideLoginError() {
            loginError.textContent = '';
            loginError.style.display = 'none';
        }

        // ============================================================
        // INICIALIZAÇÃO
        // ============================================================

        // Conecta WebSocket ao carregar
        connectWebSocket();

        // Foco no input de nome
        inputName.focus();

        console.log('[APP] Plataforma de Jogos Multiplayer iniciada.');
        console.log('[APP] Conectando em:', WS_URL);

    })();
    </script>
</body>
</html>