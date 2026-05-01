import { createApp } from "vue";
import { createPinia } from "pinia";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "@mdi/font/css/materialdesignicons.css";
import "vuetify/styles";
import "./styles/tec.css";

import App from "./App.vue";
import router from "./router";

const tec = {
  dark: false,
  colors: {
    background: "#FAFAFA",
    surface: "#FFFFFF",
    "surface-variant": "#F2F2F2",
    primary: "#0039A6",
    "primary-darken-1": "#002C80",
    secondary: "#2962FF",
    accent: "#0072CE",
    error: "#C62828",
    info: "#0072CE",
    success: "#2E7D32",
    warning: "#EF6C00",
    "on-primary": "#FFFFFF",
    "on-secondary": "#FFFFFF",
    "on-background": "#212121",
    "on-surface": "#212121",
  },
};

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: "tec",
    themes: { tec },
  },
  defaults: {
    VBtn: { rounded: "lg", style: "text-transform: none; letter-spacing: 0;" },
    VCard: { rounded: "lg" },
    VTextField: { variant: "outlined", density: "comfortable" },
    VSelect: { variant: "outlined", density: "comfortable" },
    VTextarea: { variant: "outlined", density: "comfortable" },
    VAppBar: { flat: true },
  },
});

createApp(App).use(createPinia()).use(router).use(vuetify).mount("#app");
