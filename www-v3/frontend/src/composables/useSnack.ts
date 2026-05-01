import { reactive } from "vue";

type SnackKind = "success" | "error" | "warning" | "info";

interface SnackState {
  open: boolean;
  message: string;
  kind: SnackKind;
}

const state = reactive<SnackState>({
  open: false,
  message: "",
  kind: "success",
});

let timer: number | null = null;

export function useSnack() {
  function show(message: string, kind: SnackKind = "success", ms = 3500) {
    state.message = message;
    state.kind = kind;
    state.open = true;
    if (timer) clearTimeout(timer);
    timer = window.setTimeout(() => (state.open = false), ms);
  }
  return {
    state,
    success: (m: string) => show(m, "success"),
    error: (m: string) => show(m, "error", 5000),
    warning: (m: string) => show(m, "warning", 4500),
    info: (m: string) => show(m, "info"),
  };
}
