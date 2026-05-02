import { createRouter, createWebHistory } from "vue-router";
import { useAuth } from "./stores/auth";

const routes = [
  { path: "/", redirect: "/admin/dashboard" },
  { path: "/login", component: () => import("./views/Login.vue") },

  {
    path: "/admin",
    component: () => import("./layouts/AdminLayout.vue"),
    meta: { requiresAdmin: true },
    children: [
      { path: "", redirect: "/admin/dashboard" },
      { path: "dashboard", component: () => import("./views/admin/Dashboard.vue") },
      { path: "users", component: () => import("./views/admin/Users.vue") },
      { path: "tools", component: () => import("./views/admin/Tools.vue") },
      { path: "loans", component: () => import("./views/admin/Loans.vue") },
      { path: "stations", component: () => import("./views/admin/Stations.vue") },
      { path: "inventory", component: () => import("./views/admin/Inventory.vue") },
      { path: "faces", component: () => import("./views/admin/FaceRegister.vue") },
    ],
  },

  // Kiosko sin auth
  { path: "/station/:sn", component: () => import("./views/station/Station.vue") },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(async (to) => {
  if (to.meta.requiresAdmin) {
    const auth = useAuth();
    if (!auth.token) return "/login";
    if (!auth.user) await auth.fetchMe();
    if (!auth.isAdmin) return "/login";
  }
});

export default router;
