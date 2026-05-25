/** @format */

import ws from "k6/ws";
import { check } from "k6";
import { Counter, Rate, Trend } from "k6/metrics";

export const options = {
  vus: Number(__ENV.VUS || 1000),
  duration: __ENV.DURATION || "60s",
  thresholds: {
    ws_connecting: ["p(95)<10000"],
    ws_session_duration: ["p(95)>5000"],
    ws_messages_sent: ["count>0"],
    ws_messages_received: ["count>0"],
    ws_errors: ["rate<0.01"],
    ws_roundtrip_ms: ["p(95)<1000", "p(99)<5000"],
  },
};

const URL = __ENV.WS_URL || "ws://localhost:8081/ws";
const MESSAGE_INTERVAL_MS = Number(__ENV.MESSAGE_INTERVAL_MS || 1000);
const SESSION_MS = Number(__ENV.SESSION_MS || 60000);
const ACTIVE_SENDERS = Number(__ENV.ACTIVE_SENDERS || 1);
const SEND_ON_OPEN = (__ENV.SEND_ON_OPEN || "false") === "true";

const messagesSent = new Counter("ws_messages_sent");
const messagesReceived = new Counter("ws_messages_received");
const errors = new Rate("ws_errors");
const roundtrip = new Trend("ws_roundtrip_ms");

export default function () {
  const sentAt = new Map();
  const isSender = __VU <= ACTIVE_SENDERS;

  const res = ws.connect(URL, {}, function (socket) {
    socket.on("open", function () {
      errors.add(false);
      if (isSender && SEND_ON_OPEN) {
        sendTracked(socket, sentAt, "open");
      }
    });

    socket.on("message", function (data) {
      messagesReceived.add(1);

      try {
        const msg = JSON.parse(data);
        const key = msg.data;
        if (sentAt.has(key)) {
          roundtrip.add(Date.now() - sentAt.get(key));
          sentAt.delete(key);
        }
      } catch (e) {
        errors.add(false);
      }
    });

    socket.on("ping", function () {
      errors.add(false);
    });

    socket.on("pong", function () {
      errors.add(false);
    });

    socket.on("error", function () {
      errors.add(true);
    });

    let interval = null;
    if (isSender) {
      interval = socket.setInterval(function () {
        sendTracked(socket, sentAt, "tick");
      }, MESSAGE_INTERVAL_MS);
    }

    socket.setTimeout(function () {
      if (interval) {
        socket.clearInterval(interval);
      }
      socket.close();
    }, SESSION_MS);
  });

  check(res, {
    "status 101": (r) => r && r.status === 101,
  });
}

function sendTracked(socket, sentAt, label) {
  const id = `${__VU}-${__ITER}-${label}-${Date.now()}`;
  sentAt.set(id, Date.now());
  socket.send(id);
  messagesSent.add(1);
}
