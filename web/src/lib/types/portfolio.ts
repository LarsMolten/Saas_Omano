export interface PortfolioMedia {
  id: number;
  portfolio_item_id: number;
  type: "image" | "video";
  url: string;
  url_processed: string | null;
  position: number;
  processed: boolean;
  created_at: string;
  updated_at: string;
}

export interface PortfolioItem {
  id: number;
  provider_id: number;
  title: string;
  description: string | null;
  event_date: string | null;
  location: string | null;
  budget_approx: string | null;
  position: number;
  media: PortfolioMedia[];
  created_at: string;
  updated_at: string;
}
