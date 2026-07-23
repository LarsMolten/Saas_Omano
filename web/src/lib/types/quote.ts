export interface QuoteRequest {
  id: number;
  user_id: number;
  provider_id: number;
  service_type: string;
  event_date: string | null;
  location: string | null;
  budget: string | null;
  description: string | null;
  status: "pending" | "accepted" | "declined" | "answered";
  provider_response: string | null;
  provider?: {
    id: number;
    name: string;
    city: string | null;
    category: string | null;
  };
  user?: {
    id: number;
    name: string;
    email: string;
    phone?: string;
  };
  created_at: string;
  updated_at: string;
}
