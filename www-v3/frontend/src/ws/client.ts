/**
 * Cliente WS con reconnect-backoff y handlers tipados.
 * No reusable entre conexiones — cada vista crea su propia instancia.
 */

export type WsHandler = (ev: any) => void;

export interface WsClient {
  close(): void;
}

export function openAdminWs(token: string, capture = false, onEvent: WsHandler): WsClient {
  return openGeneric(`/ws/admin?token=${encodeURIComponent(token)}${capture ? "&capture=tag" : ""}`, onEvent);
}

export function openStationWs(sn: string, onEvent: WsHandler): WsClient {
  return openGeneric(`/ws/station/${encodeURIComponent(sn)}`, onEvent);
}

function openGeneric(path: string, onEvent: WsHandler): WsClient {
  let ws: WebSocket | null = null;
  let closedByUser = false;
  let backoff = 500;

  const proto = location.protocol === "https:" ? "wss" : "ws";
  const url = `${proto}://${location.host}${path}`;

  const connect = () => {
    ws = new WebSocket(url);
    ws.onopen = () => {
      backoff = 500;
    };
    ws.onmessage = (ev) => {
      try {
        const data = JSON.parse(ev.data);
        onEvent(data);
      } catch {
        /* ignore */
      }
    };
    ws.onclose = () => {
      if (closedByUser) return;
      setTimeout(connect, backoff);
      backoff = Math.min(backoff * 2, 10000);
    };
    ws.onerror = () => ws?.close();
  };

  connect();

  return {
    close() {
      closedByUser = true;
      ws?.close();
    },
  };
}
