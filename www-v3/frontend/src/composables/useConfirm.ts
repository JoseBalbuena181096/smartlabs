import { reactive } from "vue";

interface ConfirmState {
  open: boolean;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  color: string;
  resolve: ((v: boolean) => void) | null;
}

const state = reactive<ConfirmState>({
  open: false,
  title: "",
  message: "",
  confirmText: "Confirmar",
  cancelText: "Cancelar",
  color: "primary",
  resolve: null,
});

interface ConfirmOpts {
  title?: string;
  confirmText?: string;
  cancelText?: string;
  color?: string;
}

export function useConfirm() {
  function confirm(message: string, opts: ConfirmOpts = {}): Promise<boolean> {
    state.open = true;
    state.message = message;
    state.title = opts.title ?? "Confirmar acción";
    state.confirmText = opts.confirmText ?? "Confirmar";
    state.cancelText = opts.cancelText ?? "Cancelar";
    state.color = opts.color ?? "primary";
    return new Promise((resolve) => {
      state.resolve = resolve;
    });
  }
  function answer(v: boolean) {
    state.open = false;
    state.resolve?.(v);
    state.resolve = null;
  }
  return { confirm, answer, state };
}
