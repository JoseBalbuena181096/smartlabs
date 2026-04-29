import { defineStore } from "pinia";
import { api, setAuthToken } from "../api/client";

interface User {
  id: number;
  full_name: string;
  email: string;
  role: string;
  rfid: string;
}

export const useAuth = defineStore("auth", {
  state: () => ({
    user: null as User | null,
    token: localStorage.getItem("smartlabs_token") as string | null,
  }),
  getters: {
    isAuthed: (s) => !!s.token,
    isAdmin: (s) => s.user?.role === "admin",
  },
  actions: {
    async login(email: string, password: string) {
      const { data } = await api.post("/auth/login", { email, password });
      this.token = data.token;
      this.user = data.user;
      setAuthToken(data.token);
    },
    async fetchMe() {
      try {
        const { data } = await api.get("/auth/me");
        this.user = data;
      } catch {
        this.logout();
      }
    },
    logout() {
      this.user = null;
      this.token = null;
      setAuthToken(null);
    },
  },
});
