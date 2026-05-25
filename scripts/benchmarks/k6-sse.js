import http from "k6/http";
import { check } from "k6";
import { Counter, Rate, Trend } from "k6/metrics";

const BASE_URL = __ENV.SSE_URL || "http://localhost:8082/events";
const VUS = Number(__ENV.VUS || 1000);
const DURATION = __ENV.DURATION || "1m";
const SESSION_MS = Number(__ENV.SESSION_MS || 60000);

export const sseErrors = new Rate("sse_errors");
export const sseEvents = new Counter("sse_events_received");
export const sseConnecting = new Trend("sse_connecting");

export const options = {
  scenarios: {
    default: {
      executor: "constant-vus",
      vus: VUS,
      duration: DURATION,
      gracefulStop: "30s",
    },
  },
  thresholds: {
    sse_connecting: ["p(95)<1000"],
    sse_errors: ["rate<0.01"],
    sse_events_received: ["count>0"],
  },
};

export default function () {
  const url = `${BASE_URL}${BASE_URL.includes("?") ? "&" : "?"}duration=${SESSION_MS}`;
  const res = http.get(url, { timeout: `${Math.ceil(SESSION_MS / 1000) + 10}s` });
  sseConnecting.add(res.timings.connecting);

  const ok = check(res, {
    "status 200": (r) => r.status === 200,
    "event-stream": (r) => String(r.headers["Content-Type"] || "").includes("text/event-stream"),
    "connected": (r) => String(r.body || "").includes(": connected"),
  });

  if (!ok) {
    sseErrors.add(1);
    return;
  }

  sseErrors.add(0);
  const body = String(res.body || "");
  const events = (body.match(/\n\n/g) || []).length;
  sseEvents.add(events);
}
