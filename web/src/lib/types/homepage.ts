import type { SearchProvider } from "./search";

export interface HomepageCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  sort_order: number;
  active: boolean;
  provider_count: number;
}

export interface HomepageProvider {
  id: number;
  name: string;
  slug: string;
  bio: string | null;
  category: string | null;
  city: string | null;
  average_rating: string;
  rating_count: number;
  email_verified_at: string | null;
  services: { id: number; title: string; price: string | null }[];
}

export interface HomepageData {
  featured: HomepageProvider[];
  categories: HomepageCategory[];
  recent: HomepageProvider[];
}
