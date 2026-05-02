import { api } from "./client";

export interface FaceUserStatus {
  user_id: number;
  full_name: string;
  email: string;
  rfid: string;
  role: string;
  active: boolean;
  has_face: boolean;
  positions: string[];
  last_captured_at: string | null;
}

export interface CaptureTickResult {
  user_id: number;
  status: "captured" | "waiting" | "no_face" | "no_camera";
  captured: string[];
  pending: string[];
  progress_percent: number;
  ready_to_commit: boolean;
  just_captured: string | null;
  instruction?: string;
  current_pose?: { yaw: number; pitch: number } | null;
  current_position?: string | null;
  det_score?: number;
  message?: string;
  bbox?: [number, number, number, number] | null;
  frame_w?: number;
  frame_h?: number;
  distance?: "unknown" | "far" | "okay" | "good" | "close" | "too_close";
}

export interface CommitResult {
  user_id: number;
  vectors_count: number;
  positions: string[];
}

export const faceApi = {
  listUsers: () =>
    api.get<FaceUserStatus[]>("/face/users").then((r) => r.data),

  registerStart: (user_id: number) =>
    api.post("/face/register/start", { user_id }).then((r) => r.data),

  registerCommit: (user_id: number) =>
    api.post<CommitResult>("/face/register/commit", { user_id }).then((r) => r.data),

  registerCancel: (user_id: number) =>
    api.post("/face/register/cancel", { user_id }).then((r) => r.data),

  // Polling al face-service vía nginx (/face-svc/*).
  captureTick: (user_id: number) =>
    fetch(`/face-svc/capture/${user_id}`).then(async (r) => {
      if (!r.ok) throw new Error(`face-svc ${r.status}`);
      return (await r.json()) as CaptureTickResult;
    }),

  // URL para <img :src> con cache-busting timestamp.
  snapshotUrl: (ts: number) => `/face-svc/camera/snapshot?ts=${ts}`,

  deleteFace: (user_id: number) =>
    api.delete(`/face/${user_id}`),
};
