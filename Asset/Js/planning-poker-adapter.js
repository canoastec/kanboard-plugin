/**
 * Planning Poker WebSocket Adapter para Supabase
 *
 * Substitui o WebSocket PHP mantendo 100% de compatibilidade
 * com o frontend Vue.js existente.
 *
 * IMPORTANTE:
 * - O clientId é baseado no username (não gera ID aleatório)
 * - Suporta múltiplas abas do mesmo usuário sem duplicar
 * - Cada aba tem um sessionId único para rastreamento
 * - Usuário só é removido quando TODAS as abas forem fechadas
 * - Heartbeat a cada 10s mantém sessões ativas sincronizadas
 * - A primeira mensagem DEVE ser 'join' com o campo 'username'
 * - AUTO-REVEAL: Quando todos os DEVELOPER votarem, revela automaticamente
 *
 * USO:
 * Troque:
 *   this.wsManager.connect(this.wsAddress)
 * Por:
 *   this.wsManager.connect(SUPABASE_URL, SUPABASE_KEY, 'planning-poker')
 */

class PlanningPokerAdapter {
  constructor() {
    this.supabase = null;
    this.channel = null;
    this.clientId = null;
    this.username = null; // Armazena o username para usar como ID
    this.sessionId = null; // ID único da sessão/aba atual
    this.readyState = 0; // 0=CONNECTING, 1=OPEN, 2=CLOSING, 3=CLOSED

    // Estado local para sincronização
    this.users = new Map();
    this.revealed = false;
    this.activeSessions = new Map(); // Map<username, Set<sessionId>>
    this.currentTicketId = null; // Ticket ID compartilhado atual

    // Callbacks (compatível com WebSocket)
    this.onopen = null;
    this.onmessage = null;
    this.onclose = null;
    this.onerror = null;

    // Handlers para cleanup
    this._beforeUnloadHandler = null;
    this._visibilityHandler = null;
    this._heartbeatInterval = null;
  }

  /**
   * Conecta ao Supabase
   * @param {string} supabaseUrl - URL do projeto Supabase
   * @param {string} supabaseKey - Chave anônima do Supabase
   * @param {string} roomId - ID da sala (ex: 'planning-poker')
   */
  async connect(supabaseUrl, supabaseKey, roomId = "planning-poker") {
    try {
      this.readyState = 0; // CONNECTING

      // Importa Supabase do CDN
      const { createClient } = await import(
        "https://esm.sh/@supabase/supabase-js@2"
      );

      this.supabase = createClient(supabaseUrl, supabaseKey);

      // Gera ID único para esta sessão/aba
      this.sessionId = this._generateSessionId();

      // clientId será definido quando enviar mensagem 'join' com username

      // Cria canal Supabase
      this.channel = this.supabase.channel(`room:${roomId}`, {
        config: {
          broadcast: { self: true },
        },
      });

      // Listener para todas as mensagens
      this.channel.on("broadcast", { event: "*" }, ({ event, payload }) => {
        this._handleBroadcast(event, payload);
      });

      // Subscreve ao canal
      await this.channel.subscribe((status) => {
        if (status === "SUBSCRIBED") {
          this.readyState = 1; // OPEN

          // Solicita estado atual
          this._requestState();

          // Configura listener para fechar aba
          this._setupBeforeUnloadListener();

          // Inicia heartbeat para manter sessão ativa
          this._startHeartbeat();

          // Dispara onopen
          if (this.onopen) {
            this.onopen();
          }
        } else if (status === "CLOSED") {
          this.readyState = 3; // CLOSED
          if (this.onclose) {
            this.onclose();
          }
        }
      });
    } catch (error) {
      this.readyState = 3; // CLOSED
      if (this.onerror) {
        this.onerror(error);
      }
    }
  }

  /**
   * Envia mensagem (compatível com WebSocket)
   * @param {string} data - JSON string com a mensagem
   */
  send(data) {
    if (this.readyState !== 1) {
      throw new Error("WebSocket is not open");
    }

    try {
      const message = JSON.parse(data);

      // Define clientId baseado no username na primeira mensagem 'join'
      if (message.type === "join" && message.username && !this.clientId) {
        this.clientId = message.username;
        this.username = message.username;
      }

      // Garante que clientId está definido
      if (!this.clientId) {
        console.error(
          'clientId não definido. Envie uma mensagem "join" com username primeiro.',
        );
        return;
      }

      const eventType = `msg:${message.type}`;

      // Adiciona metadata
      const payload = {
        ...message,
        clientId: this.clientId,
        sessionId: this.sessionId, // Adiciona sessionId
        timestamp: Date.now(),
      };

      // Processa localmente primeiro
      this._processLocalMessage(payload);

      // Envia via Supabase Broadcast
      this.channel.send({
        type: "broadcast",
        event: eventType,
        payload,
      });
    } catch (error) {
      if (this.onerror) {
        this.onerror(error);
      }
    }
  }

  /**
   * Fecha conexão (compatível com WebSocket)
   */
  close() {
    // Para heartbeat
    this._stopHeartbeat();

    this._sendLeaveMessage();

    this.readyState = 3; // CLOSED

    if (this.onclose) {
      this.onclose();
    }
  }

  /**
   * Envia mensagem de saída e desconecta
   */
  _sendLeaveMessage() {
    if (this.channel && this.clientId) {
      // Envia evento de saída com sessionId
      this.channel.send({
        type: "broadcast",
        event: "msg:leave",
        payload: {
          clientId: this.clientId,
          sessionId: this.sessionId,
          timestamp: Date.now(),
        },
      });

      // Remove apenas esta sessão do estado local
      this._removeSession(this.clientId, this.sessionId);

      this.channel.unsubscribe();
      this.channel = null;
    }
  }

  /**
   * Configura listener para detectar fechamento da aba
   */
  _setupBeforeUnloadListener() {
    // Remove listener anterior se existir
    if (this._beforeUnloadHandler) {
      window.removeEventListener("beforeunload", this._beforeUnloadHandler);
      window.removeEventListener("pagehide", this._beforeUnloadHandler);
    }

    // Cria handler para fechar conexão
    this._beforeUnloadHandler = () => {
      this._sendLeaveMessage();
    };

    // beforeunload: para desktop browsers
    window.addEventListener("beforeunload", this._beforeUnloadHandler);

    // pagehide: para mobile browsers (iOS Safari)
    window.addEventListener("pagehide", this._beforeUnloadHandler);

    // visibilitychange: backup para quando a aba fica oculta por muito tempo
    this._visibilityHandler = () => {
      if (document.hidden) {
        // Opcional: enviar heartbeat ou marcar como inativo
        setTimeout(() => {
          if (document.hidden && this.channel) {
            this._sendLeaveMessage();
          }
        }, 3600000); // 1h de inatividade
      }
    };
    document.addEventListener("visibilitychange", this._visibilityHandler);
  }

  /**
   * Inicia heartbeat para manter sessão ativa
   */
  _startHeartbeat() {
    // Limpa heartbeat anterior se existir
    this._stopHeartbeat();

    // Envia heartbeat a cada 10 segundos
    this._heartbeatInterval = setInterval(() => {
      if (this.channel && this.clientId) {
        this.channel.send({
          type: "broadcast",
          event: "msg:heartbeat",
          payload: {
            clientId: this.clientId,
            sessionId: this.sessionId,
            timestamp: Date.now(),
          },
        });
      }
    }, 10000); // 10 segundos
  }

  /**
   * Para heartbeat
   */
  _stopHeartbeat() {
    if (this._heartbeatInterval) {
      clearInterval(this._heartbeatInterval);
      this._heartbeatInterval = null;
    }
  }

  /**
   * Adiciona uma sessão ativa para um usuário
   */
  _addSession(username, sessionId) {
    if (!this.activeSessions.has(username)) {
      this.activeSessions.set(username, new Set());
    }
    this.activeSessions.get(username).add(sessionId);
  }

  /**
   * Remove uma sessão e verifica se deve remover o usuário
   */
  _removeSession(username, sessionId) {
    if (this.activeSessions.has(username)) {
      const sessions = this.activeSessions.get(username);
      sessions.delete(sessionId);

      // Se não há mais sessões ativas, remove o usuário
      if (sessions.size === 0) {
        this.activeSessions.delete(username);
        this.users.delete(username);
        return true; // Usuário removido
      }
    }
    return false; // Usuário ainda tem sessões ativas
  }

  /**
   * Gera ID único para esta sessão/aba
   */
  _generateSessionId() {
    return `session_${Date.now()}_${Math.random()
      .toString(36)
      .substr(2, 9)}`;
  }

  /**
   * Processa mensagens recebidas do broadcast
   */
  _handleBroadcast(event, payload) {
    // Ignora mensagens antigas (> 5 segundos)
    if (Date.now() - payload.timestamp > 5000) {
      return;
    }

    const type = event.replace("msg:", "");

    switch (type) {
      case "join":
        this._handleJoin(payload);
        break;
      case "leave":
        this._handleLeave(payload);
        break;
      case "heartbeat":
        this._handleHeartbeat(payload);
        break;
      case "select":
        this._handleSelect(payload);
        break;
      case "reveal":
        this._handleReveal(payload);
        break;
      case "reset":
        this._handleReset(payload);
        break;
      case "attention":
        this._handleAttention(payload);
        break;
      case "share-ticket":
        this._handleShareTicket(payload);
        break;
      case "request-state":
        this._handleStateRequest(payload);
        break;
      case "sync-state":
        this._handleStateSync(payload);
        break;
    }
  }

  /**
   * Processa mensagem localmente antes de enviar
   */
  _processLocalMessage(payload) {
    switch (payload.type) {
      case "join":
        // Define clientId baseado no username
        if (!this.clientId && payload.username) {
          this.clientId = payload.username;
          this.username = payload.username;
        }

        // Adiciona sessão ativa
        this._addSession(this.clientId, this.sessionId);

        this.users.set(this.clientId, {
          id: this.clientId,
          name: payload.username,
          role: payload.role || "DEVELOPER",
          selection: null,
        });
        // Broadcast estado para novos usuários
        setTimeout(() => this._broadcastState(), 100);
        break;

      case "select":
        const user = this.users.get(this.clientId);
        if (user) {
          user.selection = payload.value;
        }

        // Verifica se todos os developers votaram após processar localmente
        setTimeout(() => this._checkAutoReveal(), 50);
        break;

      case "reveal":
        this.revealed = true;
        break;

      case "reset":
        this.revealed = false;
        this.users.forEach((user) => {
          user.selection = null;
        });
        this.currentTicketId = null; // Limpa ticket compartilhado
        break;

      case "share-ticket":
        // Armazena o ticket compartilhado
        this.currentTicketId = payload.ticketId;
        break;
    }
  }

  /**
   * Handlers para cada tipo de mensagem
   */
  _handleJoin(payload) {
    const userId = payload.clientId;
    const sessionId = payload.sessionId;

    // Adiciona ou atualiza sessão ativa
    this._addSession(userId, sessionId);

    // Se já existe um usuário com esse username, atualiza em vez de duplicar
    if (this.users.has(userId)) {
      // Atualiza informações do usuário (pode ter mudado de aba/reconectado)
      const existingUser = this.users.get(userId);
      this.users.set(userId, {
        ...existingUser,
        name: payload.username,
        role: payload.role || "DEVELOPER",
      });
    } else {
      // Novo usuário
      this.users.set(userId, {
        id: userId,
        name: payload.username,
        role: payload.role || "DEVELOPER",
        selection: null,
      });

      // Envia estado atual para o novo usuário
      this._broadcastState();
    }

    this._notifyUsersUpdate();

    // Verifica auto-reveal após adicionar usuário
    setTimeout(() => this._checkAutoReveal(), 100);
  }

  _handleLeave(payload) {
    const userId = payload.clientId;
    const sessionId = payload.sessionId;

    // Remove sessão e verifica se deve remover o usuário
    const shouldRemoveUser = this._removeSession(userId, sessionId);

    // Só notifica atualização se o usuário foi realmente removido
    if (shouldRemoveUser) {
      this._notifyUsersUpdate();
    }
  }

  _handleHeartbeat(payload) {
    const userId = payload.clientId;
    const sessionId = payload.sessionId;

    // Atualiza sessão ativa (mantém viva)
    if (this.activeSessions.has(userId)) {
      this._addSession(userId, sessionId);
    }
  }

  _handleSelect(payload) {
    const userId = payload.clientId;
    const user = this.users.get(userId);

    if (user) {
      user.selection = payload.value;
      this._notifyUsersUpdate();

      // Verifica se todos os developers votaram
      this._checkAutoReveal();
    }
  }

  /**
   * Verifica se todos os developers votaram e revela automaticamente
   */
  _checkAutoReveal() {
    // Se já foi revelado, não faz nada
    if (this.revealed) {
      return;
    }

    // Filtra apenas usuários DEVELOPER
    const developers = Array.from(this.users.values()).filter((user) => {
      return user.role === "DEVELOPER" || user.role === "developer";
    });

    // Se não há developers, não faz nada
    if (developers.length === 0) {
      return;
    }

    // Verifica se TODOS os developers votaram (têm seleção)
    const allDevelopersVoted = developers.every((dev) => {
      return (
        dev.selection !== null &&
        dev.selection !== undefined &&
        dev.selection !== ""
      );
    });

    // Se todos votaram, revela automaticamente
    if (allDevelopersVoted) {
      // Envia mensagem de reveal
      this.revealed = true;

      if (this.channel) {
        this.channel.send({
          type: "broadcast",
          event: "msg:reveal",
          payload: {
            clientId: this.clientId,
            sessionId: this.sessionId,
            autoReveal: true, // Marca como reveal automático
            timestamp: Date.now(),
          },
        });
      }

      this._notifyUsersUpdate();
    }
  }

  _handleReveal(payload) {
    this.revealed = true;
    this._notifyUsersUpdate();
  }

  _handleReset(payload) {
    this.revealed = false;
    this.currentTicketId = null; // Limpa ticket compartilhado
    this.users.forEach((user) => {
      user.selection = null;
    });

    // Notifica frontend para limpar ticket
    if (this.onmessage) {
      const data = JSON.stringify({
        type: "ticket-cleared",
      });
      this.onmessage({ data });
    }

    this._notifyUsersUpdate();
  }

  _handleAttention(payload) {
    // Repassa mensagem de atenção
    if (this.onmessage) {
      const data = JSON.stringify({
        type: "attention",
        target: payload.target,
      });
      this.onmessage({ data });
    }
  }

  _handleShareTicket(payload) {
    // Armazena o ticket compartilhado localmente
    this.currentTicketId = payload.ticketId;

    // Repassa mensagem de ticket compartilhado
    if (this.onmessage) {
      const data = JSON.stringify({
        type: "ticket-shared",
        ticketId: payload.ticketId,
        sharedBy: payload.sharedBy,
      });
      this.onmessage({ data });
    }
  }

  _handleStateRequest(payload) {
    // Outro cliente solicitou estado - envia o nosso
    if (this.users.size > 0) {
      this._broadcastState();
    }
  }

  _handleStateSync(payload) {
    // Recebeu estado de outro cliente - atualiza local
    if (payload.users && payload.users.length > 0) {
      payload.users.forEach((user) => {
        if (!this.users.has(user.id)) {
          this.users.set(user.id, user);
        }
      });

      // Sincroniza sessões ativas
      if (payload.activeSessions) {
        Object.keys(payload.activeSessions).forEach((username) => {
          const sessions = payload.activeSessions[username];
          if (sessions && sessions.length > 0) {
            if (!this.activeSessions.has(username)) {
              this.activeSessions.set(username, new Set());
            }
            sessions.forEach((sessionId) => {
              this.activeSessions.get(username).add(sessionId);
            });
          }
        });
      }

      // Sincroniza ticket compartilhado
      if (payload.currentTicketId) {
        this.currentTicketId = payload.currentTicketId;

        // Notifica frontend sobre ticket compartilhado
        if (this.onmessage) {
          const ticketData = JSON.stringify({
            type: "ticket-shared",
            ticketId: payload.currentTicketId,
            sharedBy: "Sistema", // Não sabemos quem compartilhou no sync
            fromSync: true, // Flag para identificar que veio do sync
          });
          this.onmessage({ data: ticketData });
        }
      }

      this.revealed = payload.revealed || false;
      this._notifyUsersUpdate();
    }
  }

  /**
   * Solicita estado atual (quando conecta)
   */
  _requestState() {
    if (this.channel) {
      this.channel.send({
        type: "broadcast",
        event: "msg:request-state",
        payload: {
          clientId: this.clientId,
          timestamp: Date.now(),
        },
      });
    }
  }

  /**
   * Envia estado atual para outros clientes
   */
  _broadcastState() {
    if (this.channel && this.users.size > 0) {
      // Converte activeSessions para formato serializável
      const sessionsData = {};
      this.activeSessions.forEach((sessions, username) => {
        sessionsData[username] = Array.from(sessions);
      });

      this.channel.send({
        type: "broadcast",
        event: "msg:sync-state",
        payload: {
          clientId: this.clientId,
          sessionId: this.sessionId,
          users: Array.from(this.users.values()),
          revealed: this.revealed,
          activeSessions: sessionsData,
          currentTicketId: this.currentTicketId, // Inclui ticket compartilhado
          timestamp: Date.now(),
        },
      });
    }
  }

  /**
   * Notifica o frontend sobre atualização de usuários
   * (compatível com formato esperado pelo Vue.js)
   */
  _notifyUsersUpdate() {
    if (this.onmessage) {
      const data = JSON.stringify({
        type: "users",
        users: Array.from(this.users.values()),
        revealed: this.revealed,
        youId: this.clientId,
      });

      this.onmessage({ data });
    }
  }
}

/**
 * Wrapper para manter compatibilidade com WebSocketManager
 */
class SupabaseWebSocket {
  constructor(url, supabaseKey, roomId) {
    this._adapter = new PlanningPokerAdapter();
    this._url = url;
    this._key = supabaseKey;
    this._roomId = roomId;
    this._connected = false;
  }

  // Getter para simular WebSocket.readyState
  get readyState() {
    return this._adapter.readyState;
  }

  // Setters para callbacks
  set onopen(fn) {
    this._adapter.onopen = fn;
  }
  set onmessage(fn) {
    this._adapter.onmessage = fn;
  }
  set onclose(fn) {
    this._adapter.onclose = fn;
  }
  set onerror(fn) {
    this._adapter.onerror = fn;
  }

  // Métodos
  send(data) {
    this._adapter.send(data);
  }

  close() {
    this._adapter.close();
    this._connected = false;
  }

  // Método auxiliar para conectar
  async _connect() {
    if (!this._connected) {
      await this._adapter.connect(this._url, this._key, this._roomId);
      this._connected = true;
    }
  }

  // Cleanup dos listeners
  destroy() {
    this.close();
    if (this._adapter._beforeUnloadHandler) {
      window.removeEventListener(
        "beforeunload",
        this._adapter._beforeUnloadHandler,
      );
      window.removeEventListener(
        "pagehide",
        this._adapter._beforeUnloadHandler,
      );
    }
    if (this._adapter._visibilityHandler) {
      document.removeEventListener(
        "visibilitychange",
        this._adapter._visibilityHandler,
      );
    }
    // Limpa heartbeat
    this._adapter._stopHeartbeat();
  }
}

// Constantes do WebSocket (para compatibilidade)
SupabaseWebSocket.CONNECTING = 0;
SupabaseWebSocket.OPEN = 1;
SupabaseWebSocket.CLOSING = 2;
SupabaseWebSocket.CLOSED = 3;

// Exporta para uso global
if (typeof window !== "undefined") {
  window.PlanningPokerAdapter = PlanningPokerAdapter;
  window.SupabaseWebSocket = SupabaseWebSocket;
}
