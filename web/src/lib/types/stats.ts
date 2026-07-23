export interface StatsTotals {
  visits: number;
  clicks: number;
  contacts: number;
  favorites_count: number;
  quote_requests_count: number;
}

export interface StatsDay {
  date: string;
  visits: number;
  clicks: number;
  contacts: number;
  favorites_count: number;
  quote_requests_count: number;
}

export interface StatsResponse {
  period: "7d" | "30d" | "12m";
  totals: StatsTotals;
  daily: StatsDay[];
}
