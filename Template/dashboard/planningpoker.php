<body>
    <div id="app" v-cloak>
        <div class="form-group" v-if="isLeaderRole || isAnalystRole">
            <label for="ticketId">Nº Ticket</label>
            <input id="ticketId" v-model="ticketId" @blur="shareTicketId" placeholder="Digite o numero do ticket" max="99999" min="1000" type="number"/>
        </div>
        <div class="form-group" v-else-if="isDeveloperRole">
            <label for="sharedTicketId">Nº Ticket (informado pelo Analista)</label>
            <input id="sharedTicketId" title="Clique aqui para ver o ticket" :value="sharedTicketId" readonly type="number" class="readonly-ticket" @click="viewTicket"/>
        </div>
        <div class="card-container" v-show="isConnected" v-cloak>
            <div v-if="toast.show" class="toast" v-cloak>
                {{ toast.text }}
                <a v-if="toast.link" :href="toast.link" target="_blank" class="toast-link">Abrir Ticket</a>
            </div>
            <div class="card-container-flex">
                <div v-show="isDeveloperRole">
                    <label class="card-container-title">Escolha uma carta</label>
                    <div class="cards-grid">
                        <div v-for="card in cards" :key="card.value" @click="selectCard(card.value)" :title="card.value" :class="[{ disabled: !canVote }, 'card-item']">
                            <div class="inner" :style="{ background: card.color, border: selectedCard === card.value ? '4px solid rgba(255,255,255,0.9)' : '4px solid transparent' }">
                                <div class="face front" :style="{ background: card.color }">{{ card.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-container-info">
                <div class="card-container-info-users-title">Pessoas conectadas</div>
                <div class="card-container-info-flex">
                    <div>
                        <div v-for="user in users" :key="user.id" class="card-container-info-users">
                            <div>
                                <div class="" v-if="(isLeaderRole || isAnalystRole) && user.id !== youId">
                                    <button class="btn-call-attention" @click="sendAttention(user.id)">Chamar Atenção</button>
                                </div>
                                <div class="card-container-info-users-name">{{ user.name }}</div>
                                <div class="card-container-info-users-role">{{ user.role }}</div>
                            </div>
                            <div class="card-container-info-users-selection">
                                <div v-if="user.selection !== null" :class="['small-card', {flipped: (revealed || (user.id === youId && isDeveloperRole))}]">
                                    <div class="inner">
                                        <div class="face back">?</div>
                                        <div class="face front" :style="smallCardStyle(user.selection)">{{ user.selection }}</div>
                                    </div>
                                </div>
                                <div v-else class="no-card-selection">—</div>
                            </div>
                        </div>
                    </div>
    
                    <div class="card-container-info-score">
                        <div class="card-container-info-score-title">Estatísticas</div>
                        <div class="card-container-info-score-avg" v-if="revealed">
                            <div>Média:</div>
                            <div class="card-container-info-score-value">{{ average.toFixed(1) }}</div>
                        </div>
                        <div class="card-container-info-score-suggestion" v-if="revealed">
                            <div>Sugestão:</div>
                            <div class="card-container-info-score-value">{{ suggestion }}</div>
                        </div>
                        <div class="card-container-info-score-suggestion" v-if="isLeaderRole || isAnalystRole">
                            <button class="btn-send" @click="defineScore(true)" :disabled="!revealed || isLoadingScore || !ticketId">
                                <span v-if="!isLoadingScore">Adicionar a proxima sprint</span>
                                <span v-else class="loading-spinner">⏳ Salvando...</span>
                            </button>
                        </div>
                        <div class="card-container-info-score-suggestion" v-if="isLeaderRole || isAnalystRole">
                            <button class="btn-send" @click="defineScore(false)" :disabled="!revealed || isLoadingScore || !ticketId">
                                <span v-if="!isLoadingScore">Adicionar a sprint atual</span>
                                <span v-else class="loading-spinner">⏳ Salvando...</span>
                            </button>
                        </div>
                        <div class="card-container-info-score-suggestion" v-if="showButtonsRevealAndReset">
                            <button class="btn-send" @click="reveal" :disabled="!isConnected">Virar Cartas</button>
                        </div>
                        <div class="card-container-info-score-suggestion" v-if="showButtonsRevealAndReset">
                            <button class="btn-send" @click="reset" :disabled="!isConnected">Nova partida</button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="sharedTicketId && sharedTicketId > 0" class="ticket-iframe-container">
                <div class="ticket-iframe-header">
                    <span class="ticket-iframe-title">Visualização do Ticket #{{ sharedTicketId }}</span>
                    <button class="btn-close-iframe" @click="closeTicketIframe">✕</button>
                </div>
                <div class="ticket-content-wrapper">
                    <div id="ticketContentArea" class="ticket-content-area">
                        <div class="loading-ticket">Carregando ticket...</div>
                    </div>
                    <div class="ticket-overlay"></div>
                </div>
            </div>
        </div>
    </div>
</body>
<script type="module">
    class WebSocketManager {
        constructor(callbacks) {
            this.ws = null
            this.callbacks = callbacks
        }

        async connect(supabaseUrl, supabaseKey, roomId = 'planning-poker') {
            if (this.ws) {
                throw new Error('Already connected')
            }

            // Usa o adapter em vez do WebSocket nativo
            const adapter = new PlanningPokerAdapter()
            
            adapter.onopen = () => {
                this.callbacks.onOpen()
            }

            adapter.onmessage = event => {
                this.callbacks.onMessage(event.data)
            }

            adapter.onclose = () => {
                this.ws = null
                this.callbacks.onClose()
            }

            adapter.onerror = error => {
                this.callbacks.onError(error)
            }

            // Conecta ao Supabase
            await adapter.connect(supabaseUrl, supabaseKey, roomId)
            
            this.ws = adapter
        }

        disconnect() {
            if (this.ws) {
                this.ws.close()
            }
        }

        send(data) {
            if (!this.isConnected()) {
                throw new Error('Not connected')
            }
            this.ws.send(data)
        }

        isConnected() {
            return this.ws && this.ws.readyState === 1 
        }
    }

    class MessageFactory {
        static createChatMessage(from, message, time) {
            return {
                type: 'chat',
                from,
                message,
                time
            }
        }

        static createSystemMessage(text) {
            return {
                type: 'system',
                text
            }
        }

        static createPayload(username, message) {
            return JSON.stringify({
                username,
                message
            })
        }
    }

    class MessageParser {
        static parse(data) {
            try {
                return JSON.parse(data)
            } catch (e) {
                return null
            }
        }

        static isValidChatMessage(data) {
            return data && data.from && data.message && data.time
        }
    }
    new Vue({
        el: "#app",
        data() {
            return {
                ticketId: null,
                sharedTicketId: null, // Ticket compartilhado pelo analista/líder
                wsManager: null,
                username: '<?php echo $user['username']; ?>',
                isConnected: false,
                role: '<?php echo $role; ?>',
                youId: null,
                users: [],
                selectedCard: null,
                revealed: false,
                isLoadingScore: false,
                toast: {
                    show: false,
                    text: '',
                    link: null
                },
                wsAddress: '<?php echo $planningPokerServerUrl; ?>',
                supabaseUrl: 'https://qyxdzqizoyhughuwwisv.supabase.co',
                supabaseKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF5eGR6cWl6b3lodWdodXd3aXN2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Njc2MzIyMDAsImV4cCI6MjA4MzIwODIwMH0.DxL2pedwkVp0-p-7s-WSs5yosySZCxCs1Wq2bAiTD-U',
                roomId: 'planning-poker',
                cards: [
                    {value: 0,color: `#888B8D`},
                    {value: 1,color: `#00B5E2`},
                    {value: 2,color: `#00AF66`},
                    {value: 3,color: `#FFD700`},
                    {value: 5,color: `#FF6900`},
                    {value: 8,color: `#7C3F98`},
                    {value: 13,color: `#C8102E`}
                ]
            }
        },
        computed: {
            showButtonsRevealAndReset() {
                return (
                    this.isConnected &&
                    (this.isLeaderRole || (this.isAnalystRole && !this.hasLeader))
                )
            },
            isLeaderRole() {
                return this.role === 'LEADER'
            },
            isAnalystRole() {
                return this.role === 'ANALYST'
            },
            isDeveloperRole() {
                return this.role === 'DEVELOPER'
            },
            effectiveUsername() {
                return this.username.trim() || 'Anônimo'
            },
            average() {
                const vals = this.users
                    .filter(u => u.selection !== null)
                    .map(u => Number(u.selection))
                if (!vals.length) return 0
                const sum = vals.reduce((a, b) => a + b, 0)
                return Math.round((sum / vals.length) * 10) / 10
            },
            suggestion() {
                if (!this.cards.length) return null
                const avg = this.average
                let best = this.cards[0].value
                let bestDiff = Math.abs(best - avg)
                for (const card of this.cards) {
                    const diff = Math.abs(card.value - avg)
                    if (diff < bestDiff) {
                        bestDiff = diff
                        best = card.value
                    }
                }
                return best
            },
            hasLeader() {
                return this.users.some(u => (u.role || '') === 'LEADER')
            },
            canVote() {
                return this.isDeveloperRole && !this.revealed
            }
        },

        watch: {
            sharedTicketId(newVal) {
                if (newVal && newVal > 0) {
                    this.$nextTick(() => {
                        this.loadTicketContent(newVal)
                    })
                }
            }
        },

        async mounted() {
            this.initializeWebSocketManager();
            await this.connect();

            try {
                let planningPokerCards = '<?php echo $planningPokerCards; ?>';
                let scoreColors = planningPokerCards.split('|');
                let cards = [];
                for(let scoreColor of scoreColors){
                    let [value, color] = scoreColor.split('=>');
                    cards.push({value: parseInt(value), color: color});
                }
                if(cards.length > 0){
                    this.cards = cards;
                }
            } catch (e) {
                console.log(e)
            }
        },

        methods: {
            initializeWebSocketManager() {
                this.wsManager = new WebSocketManager({
                    onOpen: () => this.handleConnectionOpen(),
                    onMessage: data => this.handleMessage(data),
                    onClose: () => this.handleConnectionClose(),
                    onError: error => this.handleError(error)
                })
            },

            async connect() {
                if (!this.username.trim()) {
                    this.showToast('⚠️ Por favor, digite um username')
                    return
                }

                try {
                    // Agora usa Supabase em vez de WebSocket PHP
                    await this.wsManager.connect(
                        this.supabaseUrl,
                        this.supabaseKey,
                        this.roomId
                    )
                } catch (e) {
                    this.showToast('❌ Erro ao conectar: ' + e.message)
                }
            },

            disconnect() {
                if (this.wsManager) this.wsManager.disconnect()
            },

            handleConnectionOpen() {
                this.isConnected = true
                this.showToast('Conectado ao servidor')
                try {
                    const payload = JSON.stringify({
                        type: 'join',
                        username: this.effectiveUsername,
                        role: this.role
                    })
                    this.wsManager.send(payload)
                } catch (e) {
                    this.showToast('Erro ao enviar join: ' + e.message)
                }
            },

            handleConnectionClose() {
                this.isConnected = false
                this.showToast('Desconectado do servidor')
            },

            handleError(error) {
                this.showToast('Erro na conexão WebSocket')
            },

            handleMessage(data) {
                const parsed = MessageParser.parse(data)

                if (!parsed) {
                    this.showToast('Mensagem desconhecida do servidor')
                    return
                }

                if (parsed.type === 'users' && Array.isArray(parsed.users)) {
                    this.revealed = !!parsed.revealed
                    this.users = parsed.users.map(u => ({
                        id: u.id,
                        name: u.name,
                        selection: u.selection ?? null,
                        role: u.role ?? 'DEVELOPER'
                    }))
                    this.youId = parsed.youId ?? this.youId
                    const me = this.users.find(u => u.id === this.youId)
                    if (me) this.selectedCard = me.selection
                    return
                }

                if (parsed.type === 'ticket-shared') {
                    // Recebe o ticket compartilhado pelo analista/líder
                    this.sharedTicketId = parsed.ticketId
                    
                    // Só mostra toast se não for do sync (usuário recém-conectado)
                    if (this.isDeveloperRole && !parsed.fromSync) {
                        this.showToast(`📋 Ticket #${parsed.ticketId} compartilhado por ${parsed.sharedBy}`)
                    }
                    return
                }

                if (parsed.type === 'ticket-cleared') {
                    // Limpa o ticket quando reset é enviado
                    this.sharedTicketId = null
                    return
                }

                if (parsed.type === 'attention') {
                    // If the server targets this client or broadcasts, trigger effects
                    const target = parsed.target ?? parsed.to ?? null
                    if (!target || target === this.youId) {
                        this.triggerAttentionEffects()
                    }
                    return
                }
            },

            selectCard(value) {
                if (!this.wsManager || !this.wsManager.isConnected()) {
                    this.showToast('⚠️ Não conectado ao servidor')
                    return
                }

                if (!this.canVote) {
                    this.showToast('Você não pode votar nesta rodada')
                    return
                }

                try {
                    const payload = JSON.stringify({
                        type: 'select',
                        value
                    })
                    this.wsManager.send(payload)
                    this.selectedCard = value
                } catch (e) {
                    this.showToast('❌ Erro ao enviar seleção: ' + e.message)
                }
            },

            defineScore(nextSprint = false) {
                if (!this.ticketId) {
                    this.showToast('Por favor, insira o número do ticket.')
                    return
                }
                
                this.isLoadingScore = true
                
                $.ajax({
                    url: 'https://sistemas.canoas.rs.gov.br/kanboard/?controller=PlanningPokerController&action=updateScore&plugin=Ctec',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        task_id: this.ticketId,
                        score: this.suggestion,
                        next_sprint: nextSprint ? 1 : 0
                    }),
                    success: (result) => {
                        this.isLoadingScore = false
                        if (result.success) {
                            const ticketLink = `https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=${this.ticketId}`
                            this.showToastWithLink(`✅ Complexidade ${this.suggestion} definida!`, ticketLink)
                            this.ticketId = null;
                            this.reset()
                        } else {
                            this.showToast(`Erro: ${result.message || 'Não foi possível atualizar o ticket'}`)
                        }
                    },
                    error: (xhr, status, error) => {
                        this.isLoadingScore = false
                        this.showToast(`Erro ao definir complexidade: ${error}`)
                    }
                })
            },

            viewTicket() {
                if (!this.sharedTicketId) return null
                let url = `https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=${this.sharedTicketId}`
                window.open(url, '_blank');
           },

            smallCardStyle(value) {
                const card = this.cards.find(c => c.value === value) || {
                    color: '#999'
                }
                return {
                    background: card.color
                }
            },

            showToast(text) {
                this.toast.text = text
                this.toast.show = true
                setTimeout(() => {
                    this.toast.show = false
                }, 3000)
            },

            showToastWithLink(message, link) {
                this.toast.text = message
                this.toast.link = link
                this.toast.show = true
                setTimeout(() => {
                    this.toast.show = false
                    this.toast.link = null
                }, 5000)
            },

            reveal() {
                if (!this.wsManager || !this.wsManager.isConnected()) {
                    this.showToast('Não conectado')
                    return
                }
                try {
                    this.wsManager.send(JSON.stringify({
                        type: 'reveal'
                    }))
                } catch (e) {
                    this.showToast('Erro ao enviar reveal')
                }
            },

            reset() {
                if (!this.wsManager || !this.wsManager.isConnected()) {
                    this.showToast('Não conectado')
                    return
                }
                try {
                    this.wsManager.send(JSON.stringify({
                        type: 'reset'
                    }))
                    this.selectedCard = null
                    this.ticketId = null
                    this.sharedTicketId = null
                } catch (e) {
                    this.showToast('Erro ao enviar reset')
                }
            },
            shareTicketId() {
                if (!this.wsManager || !this.wsManager.isConnected()) {
                    return
                }
                
                if (!this.ticketId) {
                    return
                }
                
                try {
                    const payload = JSON.stringify({
                        type: 'share-ticket',
                        ticketId: this.ticketId,
                        sharedBy: this.effectiveUsername
                    })
                    this.wsManager.send(payload)
                } catch (e) {
                    console.error('Erro ao compartilhar ticket:', e)
                }
            },

            sendAttention(targetId) {
                if (!this.wsManager || !this.wsManager.isConnected()) {
                    this.showToast('⚠️ Não conectado ao servidor')
                    return
                }
                try {
                    const payload = JSON.stringify({
                        type: 'attention',
                        target: targetId
                    })
                    this.wsManager.send(payload)
                    this.showToast('Atenção enviada')
                } catch (e) {
                    this.showToast('❌ Erro ao enviar atenção: ' + e.message)
                }
            },

            triggerAttentionEffects() {
                // Shake
                document.body.classList.add('attention-shake')
                // Blink background
                document.body.classList.add('attention-blink')
                // Play beep
                this.playBeep()

                // Remove classes after duration
                setTimeout(() => {
                    document.body.classList.remove('attention-shake')
                }, 1500)
                setTimeout(() => {
                    document.body.classList.remove('attention-blink')
                }, 3000)
            },

            playBeep() {
                try {
                    if (!this._audioCtx) {
                        this._audioCtx = new (window.AudioContext || window.webkitAudioContext)()
                    }
                    const ctx = this._audioCtx
                    const now = ctx.currentTime;

                    const gain = ctx.createGain();
                    gain.connect(ctx.destination);
                    gain.gain.setValueAtTime(0.0001, now);

                    const compressor = ctx.createDynamicsCompressor();
                    compressor.connect(gain);

                    const master = compressor;

                    const sirenLFO = ctx.createOscillator();
                    const sirenDepth = ctx.createGain();

                    sirenLFO.type = "triangle";
                    sirenLFO.frequency.value = 2;
                    sirenDepth.gain.value = 200;

                    sirenLFO.connect(sirenDepth);

                    const baseFreqs = [900, 1200];

                    const toneDuration = 0.35;
                    const gap = 0.4;
                    const extraTime = 2.0;

                    baseFreqs.forEach((baseFreq, i) => {
                    const osc = ctx.createOscillator();

                    osc.type = "square";
                    osc.frequency.setValueAtTime(baseFreq, now + i * gap);

                    sirenDepth.connect(osc.frequency);

                    osc.connect(master);

                    osc.start(now + i * gap);
                    osc.stop(now + i * gap + toneDuration + extraTime);
                    });

                    sirenLFO.start(now);
                    sirenLFO.stop(now + baseFreqs.length * gap + extraTime + 0.2);

                    gain.gain.linearRampToValueAtTime(0.6, now + 0.01);
                    gain.gain.linearRampToValueAtTime(
                        0.0001,
                        now + baseFreqs.length * gap + extraTime + 0.2
                    );

                } catch (e) {
                    console.warn('Beep failed', e)
                }
            },

            closeTicketIframe() {
                this.sharedTicketId = null
                $('#ticketContentArea').html('<div class="loading-ticket">Carregando ticket...</div>')
            },

            loadTicketContent(ticketId) {
                /**
                 * Mensagem para quem for dar manutenção. Em homologação vai apontar pra produção, então cuidado.
                 * Eu podia parametrizar, mas azar, é verão.
                 * ass. Dev God Supremo, oh melhor tech leader 
                 */
                const proxyUrl = `https://sistemas.canoas.rs.gov.br/kanboard/?controller=PlanningPokerController&action=proxyTicket&plugin=Ctec&task_id=${ticketId}`
                const directUrl = `https://sistemas.canoas.rs.gov.br/kanboard/?controller=TaskViewController&action=show&task_id=${ticketId}`
                
                $('#ticketContentArea').html('<div class="loading-ticket"><div class="spinner"></div>Carregando ticket #' + ticketId + '...</div>')
                
                $('#ticketContentArea').load(proxyUrl, function(response, status, xhr) {
                    if (status === "error") {
                        const msg = "Erro ao carregar ticket: " + xhr.status + " " + xhr.statusText
                        $('#ticketContentArea').html(`
                            <div class="error-ticket">
                                <div class="error-icon">⚠️</div>
                                <div class="error-message">${msg}</div>
                                <a href="${directUrl}" target="_blank" class="btn-open-external">Abrir em nova aba</a>
                            </div>
                        `)
                    }
                })
            }
        },

       
    });
</script>

<style>
    #app {
        border-radius: 12px;
    }
    .form-group {
        margin-bottom: 12px;
    }

    input {
        width: 100% !important;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    input:focus {
        outline: none;
        border-color: #667eea;
    }

    input.readonly-ticket {
        background-color: #f0f0f0;
        color: #555;
        cursor: pointer;
        border-color: #ccc;
    }

    label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
        font-size: 14px;
    }

    button {
        flex: 1;
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .loading-spinner {
        display: inline-block;
    }

    .btn-send {
        background: #28a745;
        color: white;
    }

    .btn-send:hover:not(:disabled) {
        background: #218838;
        transform: translateY(-1px);
    }
    .btn-call-attention {
        font-size: 12px;
        width: 110px;
        background: #FF9500;
        color: white;
        padding: 7px;
        margin-bottom: 10px;
    }

    .btn-call-attention:hover:not(:disabled) {
        background: #E68600;
        transform: translateY(-1px);
    }

    .card-container {
        display: flex;
        flex-direction: column;
        gap: 5em;
        align-items: flex-start;
    }

    .card-container-flex {
        flex: 1;
        margin-bottom: 4px;
        width: 92vw;
    }

    .card-container-title {
        font-weight: 700;
        color: #555;
        display: block;
        margin-bottom: 8px;
    }

    .card-container-info {
        width: 92vw;
    }

    .card-container-info-flex {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px;
    }
    .card-container-info-users-title {
        font-weight: 700;
        margin-bottom: 8px;
        color: #333;
    }

    .card-container-info-users {
        background: #f7f7f7;
        padding: 8px;
        border-radius: 6px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        min-width: 500px;
    }

    .card-container-info-users-name {
        font-weight: 700;
        color: #333;
    }

    .card-container-info-users-role {
        font-size: 12px;
        color: #666;
    }

    .card-container-info-users-selection {
        min-width: 60px;
        text-align: center;
    }

    .no-card-selection {
        font-size: 12px;
        color: #999;
    }

    .card-container-info-score {
        padding: 10px;
        color: #fff !important;
        background: #333;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        min-width: 200px;
    }

    .card-container-info-score-title {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .card-container-info-score-avg {
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .card-container-info-score-suggestion {
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin-top: 6px;
    }

    .card-container-info-score-value {
        font-weight: 800;
    }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 12px;
        margin-top: 6px;
    }

    .card-item {
        width: 110px;
        height: 150px;
        perspective: 900px;
    }

    .card-item .inner {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 28px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        transition: transform 0.6s;
        transform-style: preserve-3d;
        user-select: none;
    }

    .card-item .face {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        backface-visibility: hidden;
        border-radius: 12px;
    }

    .card-item .back {
        background: rgba(0, 0, 0, 0.12);
        color: #333;
    }

    .card-item:hover .inner {
        transform: translateY(-6px) rotateX(0deg);
    }

    .small-card {
        width: 54px;
        height: 36px;
        perspective: 800px;
        display: inline-block;
    }

    .small-card .inner {
        position: relative;
        width: 100%;
        height: 100%;
        transform-style: preserve-3d;
        transition: transform 0.6s;
    }

    .small-card.flipped .inner {
        transform: rotateY(180deg);
    }

    .small-card .face {
        position: absolute;
        inset: 0;
        backface-visibility: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-weight: 700;
        color: #fff;
        font-size: 14px;
    }

    .small-card .back {
        background: #bbb;
        color: #333;
    }

    .small-card .front {
        transform: rotateY(180deg);
    }

    .toast {
        position: fixed;
        right: 20px;
        bottom: 20px;
        background: rgba(0, 0, 0, 0.85);
        color: #fff;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: 400px;
    }

    .toast-link {
        color: #4CAF50;
        text-decoration: underline;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        padding: 4px 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        transition: all 0.3s;
    }

    .toast-link:hover {
        color: #66BB6A;
        background: rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 800px) {
        body {
            padding: 12px;
        }

        .card-item {
            width: 100px;
            height: 140px;
            font-size: 24px;
        }
    }

    .card-item.disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Attention effects */
    @keyframes attentionShake {
        0% { transform: translateX(0); }
        10% { transform: translateX(-10px); }
        20% { transform: translateX(10px); }
        30% { transform: translateX(-8px); }
        40% { transform: translateX(8px); }
        50% { transform: translateX(-6px); }
        60% { transform: translateX(6px); }
        70% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
        90% { transform: translateX(-2px); }
        100% { transform: translateX(0); }
    }

    @keyframes attentionBlink {
        0% { background-color: inherit; }
        25% { background-color: #fffbcc; }
        50% { background-color: #ffe6b3; }
        75% { background-color: #fffbcc; }
        100% { background-color: inherit; }
    }

    body.attention-shake #app {
        animation: attentionShake 1.5s ease-in-out;
    }

    body.attention-blink {
        animation: attentionBlink 3s ease-in-out;
    }

    /* Ticket iframe styles */
    .ticket-iframe-container {
        margin-top: 20px;
        border: 2px solid #012034;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        background: #fff;
        width: 100%;
        min-width: 500px;
    }

    .ticket-iframe-header {
        background: linear-gradient(135deg, #012034 0%, #7da62c 100%);
        color: white;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
    }

    .ticket-iframe-title {
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-close-iframe {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 50%;
        max-width: 30px;
        max-height: 30px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: all 0.3s;
        padding: 0;
    }

    .btn-close-iframe:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .ticket-content-wrapper {
        position: relative;
        width: 100%;
        min-height: 600px;
        max-height: 800px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .ticket-content-area {
        width: 100%;
        min-height: 600px;
        background: #fff;
        padding: 20px;
    }

    .ticket-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: transparent;
        z-index: 10;
        cursor: default;
        pointer-events: auto;
    }

    /* Permitir scroll mesmo com overlay */
    .ticket-content-wrapper::-webkit-scrollbar {
        width: 12px;
    }

    .ticket-content-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 0 0 10px 0;
    }

    .ticket-content-wrapper::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 6px;
    }

    .ticket-content-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .loading-ticket {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        color: #667eea;
        font-size: 16px;
        font-weight: 600;
        gap: 15px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .error-ticket {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        text-align: center;
        gap: 15px;
    }

    .error-icon {
        font-size: 48px;
    }

    .error-message {
        color: #dc3545;
        font-weight: 600;
        font-size: 14px;
    }

    .btn-open-external {
        background: #667eea;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        margin-top: 10px;
        z-index: 20;
    }

    .btn-open-external:hover {
        background: #764ba2;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 800px) {
        .ticket-iframe-container {
            min-width: auto;
        }
        
        .ticket-content-wrapper {
            min-height: 400px;
            max-height: 600px;
        }

        .ticket-content-area {
            min-height: 400px;
        }
    }
</style>