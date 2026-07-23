export interface User {
  id: number;
  name: string;
  slug: string;
  email: string;
  role: "client" | "prestataire" | "admin";
  status: "active" | "suspended" | "banned";
  phone: string | null;
  phone_verified: boolean;
  email_verified_at: string | null;
  bio: string | null;
  category: string | null;
  city: string | null;
  average_rating: string;
  rating_count: number;
  created_at: string;
}
