import axios from "axios";

export const api = axios.create({
  baseURL: "/api",
  timeout: 15000,
});

export function setAuthToken(token: string | null) {
  if (token) {
    api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
    localStorage.setItem("smartlabs_token", token);
  } else {
    delete api.defaults.headers.common["Authorization"];
    localStorage.removeItem("smartlabs_token");
  }
}

const stored = localStorage.getItem("smartlabs_token");
if (stored) setAuthToken(stored);

api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) {
      setAuthToken(null);
      if (location.pathname !== "/login") location.href = "/login";
    }
    return Promise.reject(err);
  },
);
