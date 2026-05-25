/** @format */

import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
  scenarios: {
    stress_test: {
      executor: "constant-vus",
      vus: 1000,
      duration: "30s",
    },
  },

  thresholds: {
    http_req_failed: ["rate<0.01"],
    http_req_duration: ["p(95)<50"],
    http_req_duration: ["p(99)<100"],
  },
};

const BASE_URL = "http://localhost:8080";

export default function () {
  const res = http.get(`${BASE_URL}/api/ping`, {
    headers: {
      Connection: "keep-alive",
    },
  });

  check(res, {
    "status is 200": (r) => r.status === 200,
  });

  sleep(0.001);
}
