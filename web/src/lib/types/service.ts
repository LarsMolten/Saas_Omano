export interface ServiceOption {
  id: number;
  service_id: number;
  label: string;
  extra_price: string;
}

export interface Service {
  id: number;
  provider_id: number;
  title: string;
  description: string | null;
  price: string | null;
  price_type: "fixed" | "from" | "quote";
  position: number;
  options: ServiceOption[];
  created_at: string;
  updated_at: string;
}
