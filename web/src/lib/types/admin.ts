export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: "client" | "prestataire" | "admin";
  status: "active" | "suspended" | "banned";
  created_at: string;
}

export interface AdminCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  sort_order: number;
  active: boolean;
}

export interface AdminSubscription {
  id: number;
  provider_id: number;
  plan: string;
  period: string;
  status: string;
  starts_at: string;
  ends_at: string;
  provider_name: string;
  provider_email: string;
}

export interface AdminReport {
  id: number;
  reporter_id: number;
  reportable_type: string;
  reportable_id: number;
  reason: string;
  description: string | null;
  status: "pending" | "dismissed" | "sanctioned";
  resolved_by: number | null;
  resolved_at: string | null;
  resolution_note: string | null;
  reporter: { id: number; name: string } | null;
  resolver: { id: number; name: string } | null;
  created_at: string;
}

export interface AdminStats {
  users: {
    total: number;
    clients: number;
    prestataires: number;
    active_prestataires: number;
    suspended: number;
    banned: number;
  };
  subscriptions: {
    active: number;
    by_plan: Record<string, number>;
  };
  revenue: {
    by_plan: Record<string, number>;
    total: number;
  };
  reports: { pending: number };
  reviews: { total: number; reported: number };
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
