/** @format */

import http from "k6/http";
import { check } from "k6";

export const options = {
  vus: 1000,
  duration: "30s",

  thresholds: {
    http_req_failed: ["rate<0.01"],
    http_req_duration: ["p(95)<100", "p(99)<250", "p(99.9)<1000"],
  },
};

const BASE_URL = "http://localhost:8080";

export default function () {
  const res = http.get(`${BASE_URL}/api/users`);

  check(res, {
    "status 200": (r) => r.status === 200,
    "has users": (r) => {
      if (r.status !== 200) return false;
      try {
        const data = r.json("data");
        return data && data.length > 0;
      } catch (e) {
        return false;
      }
    },
  });
}
