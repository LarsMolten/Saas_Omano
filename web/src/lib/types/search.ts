import type { Service } from "./service";

export interface SearchProvider {
  id: number;
  name: string;
  bio: string | null;
  category: string | null;
  city: string | null;
  latitude: string | null;
  longitude: string | null;
  average_rating: string;
  email_verified_at: string | null;
  services: Service[];
  distance_km?: number;
}

export interface SearchMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface SearchResponse {
  data: SearchProvider[];
  meta: SearchMeta;
}

export interface SearchFilters {
  q?: string;
  category?: string;
  city?: string;
  lat?: number;
  lng?: number;
  radius?: number;
  price_min?: number;
  price_max?: number;
  rating_min?: number;
  verified?: string;
  page?: number;
  per_page?: number;
}

export const CATEGORIES = [
  "Traiteur",
  "Decoration",
  "Photographe",
  "DJ & Musique",
  "Wedding Planner",
  "Fleuriste",
  "Pâtissier",
  "Sonorisation",
  "Videaste",
  "Maquillage",
  "Coiffure",
  "Location materiel",
] as const;

export const CITIES = [
  "Muscat",
  "Salalah",
  "Sohar",
  "Nizwa",
  "Sur",
  "Seeb",
  "Ibri",
  "Barka",
  "Rustaq",
  "Ibra",
] as const;
