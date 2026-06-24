<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Arena - Plataforma de Jogos</title>
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
            padding: 20px;
        }
        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.3rem;
            background: linear-gradient(90deg, #f7971e, #ffd200);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            color: #aaa;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        /* ---- Tela de Login ---- */
        #login-screen {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.08);
        }
        #login-screen input {
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0;
            border: 2px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        #login-screen input:focus { border-color: #f7971e; }
        #login-screen input::placeholder { color: #777; }
        #login-screen button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(90deg, #f7971e, #ffd200);
            color: #1a1a2e;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        #login-screen button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247,151,30,0.3);
        }
        #login-error {
            color: #ff6b6b;
            margin-top: 8px;
            font-size: 0.85rem;
            display: none;
        }

        /* ---- Tela de Jogo ---- */
        #game-screen { display: none; }
        #game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        #room-info strong { color: #ffd200; }
        #player-count { color: #aaa; }
        #status-indicator {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-aguardando { background: #f39c12; color: #fff; }
        .status-pergunta { background: #3498db; color: #fff; }
        .status-minigame { background: #2ecc71; color: #fff; }
        .status-fim { background: #e74c3c; color: #fff; }

        #game-container {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.08);
        }
        #game-container canvas { display: block; margin: 0 auto; }

        /* ---- Quiz Overlay ---- */
        #quiz-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
        }
        #quiz-overlay.active { display: flex; }
        #quiz-box {
            background: #1a1a2e;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            border: 2px solid rgba(255,255,255,0.1);
        }
        #quiz-question {
            font-size: 1.3rem;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .quiz-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .quiz-opt {
            padding: 14px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.04);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            color: #fff;
        }
        .quiz-opt:hover { border-color: #f7971e; background: rgba(247,151,30,0.1); }
        .quiz-opt.selected { border-color: #f7971e; background: rgba(247,151,30,0.2); }
        .quiz-opt.correct { border-color: #2ecc71; background: rgba(46,204,113,0.2); }
        .quiz-opt.wrong { border-color: #e74c3c; background: rgba(231,76,60,0.2); }
        .quiz-opt.disabled { opacity: 0.5; pointer-events: none; }
        .quiz-opt .letter {
            display: inline-block;
            width: 28px; height: 28px;
            line-height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            margin-right: 10px;
            font-weight: 700;
            text-align: center;
        }
        #quiz-result {
            display: none;
            text-align: center;
            margin-top: 15px;
            font-size: 1.1rem;
        }
        .score-popup {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: 900;
            pointer-events: none;
            z-index: 2000;
            animation: scoreFade 1.5s ease-out forwards;
        }
        @keyframes scoreFade {
            0% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -150%) scale(1.5); }
        }

        /* ---- Scoreboard ---- */
        #scoreboard {
            margin-top: 8px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            padding: 10px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .score-item {
            background: rgba(255,255,255,0.06);
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
        }
        .score-item .name { color: #ffd200; font-weight: 600; }
        .score-item .pts { color: #4ecdc4; }

        /* ---- Final Screen ---- */
        #final-screen {
            display: none;
            text-align: center;
            padding: 40px 20px;
        }
        #final-screen h2 { font-size: 2rem; margin-bottom: 10px; }
        #final-screen .final-score { font-size: 3rem; color: #ffd200; font-weight: 900; }
        #final-table { margin: 20px auto; max-width: 400px; width: 100%; }
        #final-table table { width: 100%; border-collapse: collapse; }
        #final-table th, #final-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        #final-table th { color: #aaa; font-weight: 400; font-size: 0.85rem; }
        .rank-1 { color: #ffd700; }
        .rank-2 { color: #c0c0c0; }
        .rank-3 { color: #cd7f32; }
        .highlight-row { background: rgba(247,151,30,0.1); }
    </style>
</head>
<body>
    <div id="app">
        <h1>🎮 Game Arena</h1>
        <p class="subtitle">Plataforma de Jogos Educacionais</p>

        <!-- TELA DE LOGIN -->
        <div id="login-screen">
            <h2 style="margin-bottom:0.8rem; font-size:1.2rem;">Entrar na Sala</h2>
            <input type="text" id="input-name" placeholder="Seu Nome" maxlength="20" autocomplete="off">
            <input type="text" id="input-room" placeholder="Código da Sala (ex: ABC123)" maxlength="10" autocomplete="off" style="text-transform:uppercase">
            <button id="btn-join">🎯 Entrar no Jogo</button>
            <div id="login-error"></div>
        </div>

        <!-- TELA DE JOGO -->
        <div id="game-screen">
            <div id="game-header">
                <div id="room-info">Sala: <strong id="room-code">---</strong>
                    <span id="status-indicator" class="status-aguardando">Aguardando</span>
                </div>
                <div id="player-count">👥 <span id="player-count-num">0</span></div>
            </div>
            <div id="game-container"></div>
            <div id="scoreboard"></div>
        </div>

        <!-- QUIZ OVERLAY -->
        <div id="quiz-overlay">
            <div id="quiz-box">
                <div id="quiz-question"></div>
                <div class="quiz-options" id="quiz-options"></div>
                <div id="quiz-result"></div>
            </div>
        </div>

        <!-- TELA FINAL -->
        <div id="final-screen">
            <h2>🏆 Jogo Encerrado!</h2>
            <div class="final-score" id="final-score">0</div>
            <p style="color:#aaa;margin-bottom:20px;">sua pontuação total</p>
            <div id="final-table"></div>
            <button onclick="location.reload()" style="margin-top:20px;padding:12px 30px;border:none;border-radius:10px;background:linear-gradient(90deg,#f7971e,#ffd200);color:#1a1a2e;font-weight:700;font-size:1rem;cursor:pointer;">
                🔄 Jogar Novamente
            </button>
        </div>
    </div>

    <!-- Phaser 3 via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>

    <script>
    // ============================================================
    // CLIENTE WEB - Plataforma de Jogos Multiplayer
    // Comunicação via HTTP Polling (sem WebSocket)
    // Mini-game: Space Shooter (navinha) single-player
    // ============================================================

    (function() {
        'use strict';

        // ---- Constantes ----
        const API_BASE = window.location.origin + '/plataforma_jogos';

        // ---- Estado Global ----
        let playerName   = '';
        let roomId       = '';
        let jogadorId    = '';
        let myScoreQuiz  = 0;
        let myScoreAcao  = 0;
        let game         = null;
        let pollInterval = null;
        let quizOverlay  = document.getElementById('quiz-overlay');
        let quizBox      = document.getElementById('quiz-box');
        let quizQuestion = document.getElementById('quiz-question');
        let quizOptions  = document.getElementById('quiz-options');
        let quizResult   = document.getElementById('quiz-result');

        // ---- Elementos DOM ----
        const loginScreen   = document.getElementById('login-screen');
        const gameScreen    = document.getElementById('game-screen');
        const finalScreen   = document.getElementById('final-screen');
        const inputName     = document.getElementById('input-name');
        const inputRoom     = document.getElementById('input-room');
        const btnJoin       = document.getElementById('btn-join');
        const loginError    = document.getElementById('login-error');
        const roomCodeEl    = document.getElementById('room-code');
        const playerCountEl = document.getElementById('player-count-num');
        const statusInd     = document.getElementById('status-indicator');
        const scoreboardEl  = document.getElementById('scoreboard');

        // ---- Utilitário: Fetch API ----
        async function apiGet(endpoint) {
            try {
                const res = await fetch(API_BASE + endpoint);
                return await res.json();
            } catch(e) {
                console.error('[API] GET error:', e);
                return null;
            }
        }

        async function apiPost(endpoint, data) {
            try {
                const res = await fetch(API_BASE + endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                return await res.json();
            } catch(e) {
                console.error('[API] POST error:', e);
                return null;
            }
        }

        // ---- Geração de ID único ----
        function gerarJogadorId() {
            return 'jogador_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 6);
        }

        // ============================================================
        // ENTRAR NA SALA
        // ============================================================

        btnJoin.addEventListener('click', async function() {
            const name = inputName.value.trim();
            const room = inputRoom.value.trim().toUpperCase();

            if (!name) { showError('Digite seu nome.'); return; }
            if (!room) { showError('Digite o código da sala.'); return; }
            if (name.length > 20) { showError('Nome muito longo (máx 20).'); return; }
            if (room.length > 10) { showError('Código muito longo.'); return; }

            hideError();

            playerName = name;
            roomId     = room;
            jogadorId  = gerarJogadorId();

            // Tenta criar/entrar na sala via POST em obter_status (simula entrada)
            // Na prática, o obter_status cria o jogador se não existir
            const status = await apiGet('/api/obter_status.php?room_id=' + encodeURIComponent(roomId)
                + '&jogador_id=' + encodeURIComponent(jogadorId));

            if (status && status.erro) {
                showError(status.erro);
                return;
            }

            // Muda para tela de jogo
            loginScreen.style.display = 'none';
            gameScreen.style.display  = 'block';
            roomCodeEl.textContent    = roomId;

            // Inicializa o Phaser
            initGame();

            // Inicia polling de status
            startPolling();

            // Foca no canvas
            document.querySelector('canvas')?.focus();
        });

        inputName.addEventListener('keydown', e => { if (e.key === 'Enter') btnJoin.click(); });
        inputRoom.addEventListener('keydown', e => { if (e.key === 'Enter') btnJoin.click(); });

        function showError(msg) {
            loginError.textContent = msg;
            loginError.style.display = 'block';
        }
        function hideError() {
            loginError.textContent = '';
            loginError.style.display = 'none';
        }

        // ============================================================
        // POLLING DE STATUS (a cada 1.5s)
        // ============================================================

        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(pollStatus, 1500);
            pollStatus(); // já chama na primeira vez
        }

        async function pollStatus() {
            if (!roomId || !jogadorId) return;

            const data = await apiGet('/api/obter_status.php?room_id=' + encodeURIComponent(roomId)
                + '&jogador_id=' + encodeURIComponent(jogadorId));

            if (!data) return;
            if (data.erro) {
                console.warn('[POLL]', data.erro);
                return;
            }

            // Atualiza status
            const status = data.status || 'aguardando';
            updateStatusUI(status);

            // Atualiza score do jogador
            if (data.jogador) {
                myScoreQuiz = data.jogador.score_quiz || 0;
                myScoreAcao = data.jogador.score_acao || 0;
            }

            // Atualiza contagem de jogadores
            if (data.ranking) {
                playerCountEl.textContent = data.ranking.length;
            }

            // Atualiza ranking
            updateScoreboard(data.ranking);

            // Se tem pergunta ativa e ainda não respondeu, exibe quiz
            if (data.pergunta && !data.ja_respondeu) {
                showQuiz(data.pergunta);
            } else if (data.pergunta && data.ja_respondeu) {
                // Já respondeu: mostra tela de aguardo
                hideQuiz();
            } else if (!data.pergunta && quizOverlay.classList.contains('active')) {
                hideQuiz();
            }

            // Se o status for "minigame" e não estamos no jogo ativo
            if (status === 'minigame' && game && game.scene.scenes[0]) {
                const scene = game.scene.scenes[0];
                if (scene.sceneKey !== 'SpaceShooter') {
                    scene.scene.start('SpaceShooter');
                }
                // Se o jogo já está rodando, não faz nada
            }

            // Se o status for "aguardando" ou "pergunta", pausa o mini-game
            if ((status.startsWith('pergunta') || status === 'aguardando' || status === 'fim') && game) {
                const scene = game.scene.scenes[0];
                if (scene && scene.sceneKey === 'SpaceShooter' && scene.scene.isActive()) {
                    scene.scene.pause('SpaceShooter');
                }
            }

            // Se acabou o jogo
            if (status === 'fim') {
                stopPolling();
                showFinalScreen(data.ranking);
            }
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        function updateStatusUI(status) {
            statusInd.textContent = status.replace(/_/g, ' ').toUpperCase();
            statusInd.className = '';

            if (status === 'aguardando') statusInd.classList.add('status-aguardando');
            else if (status.startsWith('pergunta')) statusInd.classList.add('status-pergunta');
            else if (status === 'minigame') statusInd.classList.add('status-minigame');
            else if (status === 'fim') statusInd.classList.add('status-fim');
        }

        function updateScoreboard(ranking) {
            if (!ranking || ranking.length === 0) {
                scoreboardEl.innerHTML = '<span style="color:#666;font-size:0.8rem;">Nenhum jogador ainda</span>';
                return;
            }
            scoreboardEl.innerHTML = ranking.map((p, i) => {
                return `<div class="score-item">
                    <span class="name">${i === 0 ? '🥇 ' : i === 1 ? '🥈 ' : i === 2 ? '🥉 ' : ''}${p.nome}</span>
                    <span class="pts">${p.total} pts</span>
                </div>`;
            }).join('');
        }

        // ============================================================
        // QUIZ OVERLAY
        // ============================================================

        function showQuiz(pergunta) {
            quizOverlay.classList.add('active');
            quizQuestion.textContent = pergunta.pergunta;

            const letras = ['a', 'b', 'c', 'd'];
            quizOptions.innerHTML = '';
            quizResult.style.display = 'none';
            quizResult.className = '';

            letras.forEach(letra => {
                const texto = pergunta[letra];
                if (!texto) return;
                const btn = document.createElement('button');
                btn.className = 'quiz-opt';
                btn.innerHTML = `<span class="letter">${letra.toUpperCase()}</span>${texto}`;
                btn.dataset.letra = letra.toUpperCase();
                btn.addEventListener('click', () => responderQuiz(pergunta.id, letra.toUpperCase(), btn));
                quizOptions.appendChild(btn);
            });
        }

        async function responderQuiz(perguntaId, resposta, btnEl) {
            // Desabilita todos os botões
            document.querySelectorAll('.quiz-opt').forEach(b => b.classList.add('disabled'));

            // Mostra selecionado
            btnEl.classList.add('selected');

            const result = await apiPost('/api/responder_pergunta.php', {
                room_id: roomId,
                jogador_id: jogadorId,
                pergunta_id: perguntaId,
                resposta: resposta
            });

            if (!result) {
                document.querySelectorAll('.quiz-opt').forEach(b => b.classList.remove('disabled'));
                return;
            }

            // Marca a resposta certa e errada
            document.querySelectorAll('.quiz-opt').forEach(b => {
                const letra = b.dataset.letra;
                if (letra === result.resposta_certa) b.classList.add('correct');
                if (letra === resposta && !result.acertou) b.classList.add('wrong');
            });

            // Mostra resultado
            quizResult.style.display = 'block';
            if (result.acertou) {
                quizResult.innerHTML = '✅ <strong>Correto!</strong> +' + result.pontos_ganhos + ' pts';
                quizResult.style.color = '#2ecc71';
                // Popup animado
                const popup = document.createElement('div');
                popup.className = 'score-popup';
                popup.textContent = '+' + result.pontos_ganhos;
                popup.style.color = '#2ecc71';
                document.body.appendChild(popup);
                setTimeout(() => popup.remove(), 1500);
            } else {
                quizResult.innerHTML = '❌ <strong>Errado!</strong> Resposta certa: <strong>' + result.resposta_certa + '</strong>';
                quizResult.style.color = '#e74c3c';
            }

            // Atualiza score
            myScoreQuiz = result.score_quiz || myScoreQuiz;
        }

        function hideQuiz() {
            quizOverlay.classList.remove('active');
            quizResult.style.display = 'none';
        }

        // ============================================================
        // TELA FINAL
        // ============================================================

        function showFinalScreen(ranking) {
            gameScreen.style.display   = 'none';
            quizOverlay.classList.remove('active');
            finalScreen.style.display  = 'block';

            document.getElementById('final-score').textContent = (myScoreQuiz + myScoreAcao);

            let html = '<table><tr><th>#</th><th>Jogador</th><th>Total</th></tr>';
            if (ranking) {
                ranking.forEach((p, i) => {
                    const rankClass = i < 3 ? 'rank-' + (i+1) : '';
                    const highlight = p.nome === playerName ? 'highlight-row' : '';
                    html += `<tr class="${highlight} ${rankClass}">
                        <td>${i + 1}</td>
                        <td>${i === 0 ? '🥇 ' : i === 1 ? '🥈 ' : i === 2 ? '🥉 ' : ''}${p.nome}</td>
                        <td>${p.total} pts</td>
                    </tr>`;
                });
            }
            html += '</table>';
            document.getElementById('final-table').innerHTML = html;
        }

        // ============================================================
        // PHASER 3 - MINI-GAME: SPACE SHOOTER (NAVINHA)
        // ============================================================

        function initGame() {
            if (game) {
                game.destroy(true);
                game = null;
            }

            const config = {
                type: Phaser.AUTO,
                width: 800,
                height: 600,
                parent: 'game-container',
                backgroundColor: '#0a0a1a',
                physics: {
                    default: 'arcade',
                    arcade: {
                        gravity: { y: 0 },
                        debug: false
                    }
                },
                scene: [SpaceShooterScene]
            };

            game = new Phaser.Game(config);
        }

        // ---- Cena do Space Shooter ----
        class SpaceShooterScene extends Phaser.Scene {
            constructor() {
                super({ key: 'SpaceShooter' });
                this.score       = 0;
                this.vida        = 3;
                this.ativo       = true;
                this.tiros       = null;
                this.inimigos    = null;
                this.estrelas    = null;
                this.nave        = null;
                this.scoreText   = null;
                this.vidaText    = null;
                this.tempoJogo   = 0;
                this.spawnTimer  = 0;
                this.fimEnviado  = false;
                this.fimCallback = null;
                this.paused      = false;
            }

            create() {
                const W = 800, H = 600;

                // Fundo estrelado (parallax simples)
                this.estrelas = [];
                for (let i = 0; i < 80; i++) {
                    const s = this.add.circle(
                        Phaser.Math.Between(0, W),
                        Phaser.Math.Between(0, H),
                        Phaser.Math.Between(1, 3),
                        0xffffff,
                        Phaser.Math.FloatBetween(0.3, 0.8)
                    );
                    s.velY = Phaser.Math.FloatBetween(0.5, 2);
                    this.estrelas.push(s);
                }

                // Nave do jogador (triângulo estilizado)
                this.nave = this.add.graphics();
                this.nave.fillStyle(0x4ecdc4, 1);
                this.nave.fillTriangle(0, -18, -14, 14, 14, 14);
                this.nave.fillStyle(0xffd200, 0.8);
                this.nave.fillRect(-4, 10, 8, 6);
                this.nave.x = W / 2;
                this.nave.y = H - 50;

                // Física da nave
                this.physics.add.existing(this.nave);
                this.nave.body.setSize(28, 28);
                this.nave.body.setCollideWorldBounds(true);

                // Grupo de tiros
                this.tiros = this.physics.add.group({
                    defaultKey: 'tiro',
                    maxSize: 30
                });

                // Grupo de inimigos
                this.inimigos = this.physics.add.group();

                // Textos HUD
                this.scoreText = this.add.text(16, 16, 'SCORE: 0', {
                    fontSize: '20px', color: '#ffd200', fontFamily: 'monospace', fontStyle: 'bold'
                });
                this.vidaText = this.add.text(16, 44, '❤️ ❤️ ❤️', {
                    fontSize: '20px', fontFamily: 'Arial'
                });

                // Texto de status
                this.statusText = this.add.text(W/2, 30, '', {
                    fontSize: '14px', color: '#888', fontFamily: 'Arial'
                }).setOrigin(0.5, 0);

                // Input: teclado
                this.cursors = this.input.keyboard.createCursorKeys();
                this.spaceKey = this.input.keyboard.addKey(Phaser.Input.Keyboard.KeyCodes.SPACE);

                // Colisão tiro-inimigo
                this.physics.add.overlap(this.tiros, this.inimigos, this.acertarInimigo, null, this);

                // Colisão nave-inimigo
                this.physics.add.overlap(this.nave, this.inimigos, this.naveColidiu, null, this);

                // Timer de spawn
                this.spawnTimer = 0;
                this.fimEnviado = false;
                this.score = 0;
                this.vida  = 3;
                this.ativo = true;
                this.tempoJogo = 0;

                // Se o status for minigame, começa ativo
                this.paused = false;
                this.statusText.setText('🎯 ATIRADOR ESPACIAL - Use SETAS + ESPAÇO');
            }

            update(time, delta) {
                if (this.paused) return;

                const W = 800, H = 600;
                this.tempoJogo += delta;

                // Movimento das estrelas (parallax)
                this.estrelas.forEach(s => {
                    s.y += s.velY;
                    if (s.y > H + 10) {
                        s.y = -10;
                        s.x = Phaser.Math.Between(0, W);
                    }
                });

                if (!this.ativo) return;

                // Movimento da nave
                const speed = 250;
                let vx = 0, vy = 0;
                if (this.cursors.left.isDown)  vx -= speed;
                if (this.cursors.right.isDown) vx += speed;
                if (this.cursors.up.isDown)    vy -= speed;
                if (this.cursors.down.isDown)  vy += speed;
                this.nave.body.setVelocity(vx, vy);

                // Atirar
                if (this.spaceKey.isDown) {
                    this.atirar();
                }

                // Spawn de inimigos (crescente)
                this.spawnTimer += delta;
                const intervalo = Math.max(400, 1500 - this.tempoJogo * 0.02);
                if (this.spawnTimer > intervalo) {
                    this.spawnTimer = 0;
                    this.criarInimigo();
                }

                // Se um inimigo passou da tela, remove
                this.inimigos.getChildren().forEach(inimigo => {
                    if (inimigo.y > H + 30) {
                        inimigo.destroy();
                    }
                });

                // Atualiza HUD
                this.scoreText.setText('SCORE: ' + this.score);
                this.vidaText.setText('❤️ '.repeat(Math.max(0, this.vida)) + '🖤 '.repeat(Math.max(0, 3 - this.vida)));

                // Se morreu, encerra
                if (this.vida <= 0 && !this.fimEnviado) {
                    this.ativo = false;
                    this.fimEnviado = true;
                    this.encerrarJogo();
                }
            }

            atirar() {
                if (!this.ativo) return;

                const tiro = this.tiros.get(this.nave.x, this.nave.y - 20);
                if (!tiro) return;

                // Como não temos sprite, criamos um graphic
                tiro.setActive(true).setVisible(true);
                // Usamos um círculo pequeno
                if (!tiro.graphic) {
                    tiro.graphic = this.add.circle(0, 0, 4, 0xffd200);
                }
                tiro.graphic.setPosition(tiro.x, tiro.y);

                this.physics.add.existing(tiro);
                tiro.body.setVelocity(0, -400);
                tiro.body.setSize(8, 8);

                // Remove após 2s
                this.time.delayedCall(2000, () => {
                    if (tiro.active) {
                        tiro.setActive(false).setVisible(false);
                        if (tiro.graphic) tiro.graphic.setVisible(false);
                        tiro.body.setVelocity(0, 0);
                    }
                });
            }

            criarInimigo() {
                const W = 800;
                const x = Phaser.Math.Between(30, W - 30);
                const inimigo = this.add.circle(x, -20, Phaser.Math.Between(10, 18), 0xe74c3c, 0.9);
                inimigo.setStrokeStyle(2, 0xff6b6b, 0.6);
                this.physics.add.existing(inimigo);
                inimigo.body.setVelocity(
                    Phaser.Math.Between(-60, 60),
                    Phaser.Math.Between(120, 220 + this.tempoJogo * 0.02)
                );
                inimigo.body.setSize(20, 20);
                this.inimigos.add(inimigo);
            }

            acertarInimigo(tiro, inimigo) {
                if (!tiro.active) return;

                // Desativa tiro
                tiro.setActive(false).setVisible(false);
                if (tiro.graphic) tiro.graphic.setVisible(false);
                tiro.body.setVelocity(0, 0);

                // Explosão simples
                const exp = this.add.circle(inimigo.x, inimigo.y, 8, 0xffd200, 1);
                this.tweens.add({
                    targets: exp,
                    scale: 3,
                    alpha: 0,
                    duration: 300,
                    onComplete: () => exp.destroy()
                });

                // Remove inimigo
                inimigo.destroy();

                // Pontua (mais pontos por inimigos mais rápidos = maiores)
                this.score += 10;
            }

            naveColidiu(nave, inimigo) {
                if (!this.ativo) return;
                this.vida--;

                // Flash na nave
                this.tweens.add({
                    targets: this.nave,
                    alpha: 0.3,
                    duration: 100,
                    yoyo: true,
                    repeat: 3,
                    onComplete: () => { if (this.nave) this.nave.alpha = 1; }
                });

                // Remove o inimigo que colidiu
                inimigo.destroy();
            }

            encerrarJogo() {
                // Mostra tela de game over
                const W = 800, H = 600;
                const overlay = this.add.rectangle(W/2, H/2, W, H, 0x000000, 0.7);

                const gameOverText = this.add.text(W/2, H/2 - 40, '💥 GAME OVER', {
                    fontSize: '40px', color: '#e74c3c', fontFamily: 'Arial', fontStyle: 'bold'
                }).setOrigin(0.5);

                const scoreFinalText = this.add.text(W/2, H/2 + 20, 'Pontuação: ' + this.score, {
                    fontSize: '28px', color: '#ffd200', fontFamily: 'Arial', fontStyle: 'bold'
                }).setOrigin(0.5);

                const salvandoText = this.add.text(W/2, H/2 + 60, 'Salvando pontuação...', {
                    fontSize: '16px', color: '#888', fontFamily: 'Arial'
                }).setOrigin(0.5);

                // Envia score para o servidor
                apiPost('/api/salvar_score_acao.php', {
                    room_id: roomId,
                    jogador_id: jogadorId,
                    score: this.score
                }).then(res => {
                    if (res && res.sucesso) {
                        myScoreAcao = res.score_acao;
                        salvandoText.setText('✅ Pontuação salva! Total: ' + res.total + ' pts');
                        salvandoText.setColor('#2ecc71');

                        // Mostra botão de continuar
                        const btn = this.add.text(W/2, H/2 + 100, '[ Clique para continuar ]', {
                            fontSize: '18px', color: '#4ecdc4', fontFamily: 'Arial'
                        }).setOrigin(0.5).setInteractive();

                        btn.on('pointerdown', () => {
                            // Volta ao estado de polling
                            this.scene.pause();
                        });
                    } else {
                        salvandoText.setText('⚠️ Erro ao salvar');
                        salvandoText.setColor('#e74c3c');
                    }
                }).catch(() => {
                    salvandoText.setText('⚠️ Erro de conexão');
                    salvandoText.setColor('#e74c3c');
                });
            }
        }

        // ---- Inicialização ----
        inputName.focus();

        console.log('[APP] Plataforma de Jogos (HTTP Polling) iniciada.');
        console.log('[APP] API Base:', API_BASE);

    })();
    </script>
</body>
</html>