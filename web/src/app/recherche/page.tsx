import type { Metadata } from "next";
import SearchPage from "@/components/search/SearchPage";
import type { SearchResponse } from "@/lib/types/search";

export const metadata: Metadata = {
  title: "Recherche de prestataires | Omano",
  description:
    "Trouvez les meilleurs prestataires pour votre evenement a Oman. Traiteurs, photographes, DJs et plus encore.",
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

async function getInitialResults(): Promise<SearchResponse> {
  try {
    const res = await fetch(`${API_BASE}/v1/search?per_page=20`, {
      cache: "no-store",
    });

    if (!res.ok) {
      return { data: [], meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 } };
    }

    return res.json() as Promise<SearchResponse>;
  } catch {
    return { data: [], meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 } };
  }
}

export default async function RecherchePage() {
  const initial = await getInitialResults();

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-6">
          <h1 className="text-2xl font-bold text-gray-900">
            Recherche de prestataires
          </h1>
          <p className="mt-1 text-gray-600">
            Trouvez le prestataire ideal pour votre evenement
          </p>
        </div>
      </header>
      <main className="max-w-7xl mx-auto px-4 py-8">
        <SearchPage initialData={initial} />
      </main>
    </div>
  );
}
