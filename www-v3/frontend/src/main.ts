import { createApp } from "vue";
import { createPinia } from "pinia";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "@mdi/font/css/materialdesignicons.css";
import "vuetify/styles";

import App from "./App.vue";
import router from "./router";

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: "light",
    themes: {
      light: {
        colors: {
          primary: "#1976d2",
          secondary: "#26a69a",
          accent: "#f9a825",
        },
      },
    },
  },
});

createApp(App).use(createPinia()).use(router).use(vuetify).mount("#app");
