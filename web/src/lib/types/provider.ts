import type { Service } from "./service";
import type { PortfolioItem } from "./portfolio";

export interface ProviderProfile {
  id: number;
  name: string;
  slug: string;
  bio: string | null;
  category: string | null;
  city: string | null;
  average_rating: string;
  rating_count: number;
  email_verified_at: string | null;
  created_at: string;
}

export interface Review {
  id: number;
  user_id: number;
  provider_id: number;
  rating: number;
  comment: string | null;
  status: string;
  user: { id: number; name: string };
  created_at: string;
}

export interface ReviewsPaginated {
  data: Review[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ProviderProfileResponse {
  provider: ProviderProfile;
  plan: string;
  services: Service[];
  portfolio: PortfolioItem[];
  reviews: ReviewsPaginated;
}
