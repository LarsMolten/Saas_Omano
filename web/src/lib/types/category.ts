export interface CategoryPageCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  sort_order: number;
  active: boolean;
}

export interface CategoryProvider {
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

export interface CategoryPageResponse {
  category: CategoryPageCategory;
  providers: {
    data: CategoryProvider[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
}
